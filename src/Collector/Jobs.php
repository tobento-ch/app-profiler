<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Profiler\Collector;

use Tobento\App\Profiler\CollectorInterface;
use Tobento\App\Profiler\View;
use Tobento\App\Profiler\VarDumper;
use Tobento\App\AppInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\Queues;

/**
 * Jobs
 */
class Jobs implements CollectorInterface
{
    /**
     * @var null|object
     */
    protected null|object $jobsCollector = null;
    
    /**
     * Create a new Jobs.
     *
     * @param AppInterface $app
     */
    public function __construct(
        AppInterface $app,
    ) {
        $app->on(
            QueuesInterface::class,
            function(QueuesInterface $queues): QueuesInterface {
                return $this->createQueues($queues);
            }
        );
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Jobs';
    }
    
    /**
     * Returns the collected data.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array Must be serializable natively by json_encode().
     */
    public function collect(ServerRequestInterface $request, ResponseInterface $response): array
    {
        $data = [];
        
        if (!is_null($this->jobsCollector)) {
            $data['pushed'] = $this->jobsCollector->getPushedJobs();
        }
        
        return $data;
    }
    
    /**
     * Render the collected data.
     *
     * @param ViewInterface $view
     * @param array $data The collected data.
     * @return string
     */
    public function render(ViewInterface $view, array $data): string
    {
        return $view->render('profiler/table', [
            'table' => new View\Table(
                rows: $data['pushed'] ?? [],
                title: 'Pushed Jobs',
                html: ['job'],
            )
        ]);
    }
    
    /**
     * Returns any data used for menu e.g.
     *
     * @param array $data The collected data.
     * @return array<string, mixed>
     */
    public function data(array $data): array
    {
        $jobsCount = count($data['pushed'] ?? []);
        
        return [
            'badge' => $jobsCount,
            'badgeAttributes' => ['title' => sprintf('%d pushed jobs', $jobsCount)],
        ];
    }

    /**
     * Returns the created queues.
     *
     * @param QueuesInterface $queues
     * @return QueuesInterface
     */
    protected function createQueues(QueuesInterface $queues): QueuesInterface
    {
        return new class($queues, $this) implements QueuesInterface
        {
            public function __construct(
                private QueuesInterface $queues,
                private Jobs $jobsCollector,
            ) {}
            
            public function queue(string $name): QueueInterface
            {
                return $this->jobsCollector->createQueue(
                    queue: $this->queues->queue($name),
                    jobsCollector: $this->jobsCollector->jobsCollector(),
                );
            }

            public function get(string $name): null|QueueInterface
            {
                $queue = $this->queues->get($name);
                
                if (is_null($queue)) {
                    return null;
                }
                
                return $this->jobsCollector->createQueue(
                    queue: $queue,
                    jobsCollector: $this->jobsCollector->jobsCollector(),
                );
            }

            public function has(string $name): bool
            {
                return $this->queues->has($name);
            }

            public function names(): array
            {
                return $this->queues->names();
            }
        };
    }
    
    /**
     * Returns the created queue.
     *
     * @param QueueInterface $queue
     * @return QueueInterface
     */
    public function createQueue(
        QueueInterface $queue,
        object $jobsCollector,
    ): QueueInterface {
        return new class($queue, $jobsCollector) implements QueueInterface
        {
            public function __construct(
                private QueueInterface $queue,
                private object $jobsCollector,
            ) {}

            public function name(): string
            {
                return $this->queue->name();
            }

            public function priority(): int
            {
                return $this->queue->priority();
            }

            public function push(JobInterface $job): string
            {
                $this->jobsCollector->addPushedJob($job);
                return $this->queue->push($job);
            }

            public function pop(): null|JobInterface
            {
                return $this->queue->pop();
            }

            public function getJob(string $id): null|JobInterface
            {
                return $this->queue->getJob($id);
            }

            public function getAllJobs(): iterable
            {
                return $this->queue->getAllJobs();
            }

            public function size(): int
            {
                return $this->queue->size();
            }

            public function clear(): bool
            {
                return $this->queue->clear();
            }
        };
    }
    
    /**
     * Returns the jobs collector.
     *
     * @return object
     */
    public function jobsCollector(): object
    {
        if (!is_null($this->jobsCollector)) {
            return $this->jobsCollector;
        }
        
        return $this->jobsCollector = new class()
        {
            private array $pushedJobs = [];

            public function addPushedJob(JobInterface $job): void
            {
                $this->pushedJobs[] = ['job' => VarDumper::dump($job)];
            }
            
            public function getPushedJobs(): array
            {
                return $this->pushedJobs;
            }
        };
    }
}