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
use Tobento\App\Profiler\View\Table;
use Tobento\App\Profiler\VarDumper;
use Tobento\App\AppInterface;
use Tobento\App\Logging\LoggersInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\AbstractLogger;
use Closure;

/**
 * Logs
 */
class Logs implements CollectorInterface
{
    /**
     * @var null|object
     */
    protected null|object $collector = null;
    
    /**
     * Create a new Logs.
     *
     * @param AppInterface $app
     * @param array $exceptLoggers
     */
    public function __construct(
        protected AppInterface $app,
        array $exceptLoggers = [],
    ) {
        $app->on(
            LoggersInterface::class,
            function (LoggersInterface $loggers) use ($exceptLoggers) {
                return $this->createLoggers($loggers, $exceptLoggers);
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
        return 'Logs';
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
        if (is_null($this->collector)) {
            return [];
        }
        
        return $this->collector->getLogs();
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
        $html = '';
        
        foreach($data as $loggerName => $logs) {
            $html .= $view->render('profiler/table', [
                'table' => new Table(
                    rows: $logs,
                    title: sprintf('Logger [%s]', $loggerName),
                    html: ['context'],
                ),
            ]);
        }
        
        return $html;
    }
    
    /**
     * Returns any data used for menu e.g.
     *
     * @param array $data The collected data.
     * @return array<string, mixed>
     */
    public function data(array $data): array
    {
        $logsCount = 0;
        
        foreach($data as $logs) {
            $logsCount += count($logs);
        }
        
        return [
            //'icon' => 'name',
            'badge' => $logsCount,
            'badgeAttributes' => ['title' => sprintf('%d logs', $logsCount)],
        ];
    }

    /**
     * Returns the created loggers.
     *
     * @param LoggersInterface $loggers
     * @param array $exceptLoggers
     * @return LoggersInterface
     */
    protected function createLoggers(LoggersInterface $loggers, array $exceptLoggers): LoggersInterface
    {
        $this->collector = new class()
        {
            private array $logs = [];

            public function log(string $name, array $data): void
            {
                $this->logs[$name][] = $data;
            }
            
            public function getLogs(): array
            {
                return $this->logs;
            }
        };
        
        return new class($loggers, $this->collector, $exceptLoggers) implements LoggersInterface
        {
            private array $data = [];
            
            public function __construct(
                private LoggersInterface $loggers,
                private object $collector,
                private array $exceptLoggers,
            ) {}

            public function add(string $name, Closure|LoggerInterface $logger): static
            {
                return $this->loggers->add($name, $logger);
            }

            public function addAlias(string $alias, string $logger): static
            {
                return $this->loggers->addAlias($alias, $logger);
            }
            
            public function aliases(): array
            {
                return $this->loggers->aliases();
            }

            public function logger(null|string $name = null): LoggerInterface
            {
                if (is_null($name)) {
                    $name = 'default';    
                }
                
                $logger = $this->loggers->logger($name);
                
                if (in_array($name, $this->exceptLoggers)) {
                    return $logger;
                }
                
                return $this->wrapLogger($logger, $name);
            }

            public function get(string $name): null|LoggerInterface
            {
                $logger = $this->loggers->get($name);
                
                if (is_null($logger) || in_array($name, $this->exceptLoggers)) {
                    return $logger;
                }
                
                return $this->wrapLogger($logger, $name);
            }

            public function has(string $name): bool
            {
                return $this->loggers->has($name);
            }

            public function names(): array
            {
                return $this->loggers->names();
            }

            public function created(): array
            {
                return $this->loggers->created();
            }
            
            private function wrapLogger(LoggerInterface $logger, string $name): LoggerInterface
            {
                if ((new \ReflectionClass($logger))->isAnonymous()) {
                    return $logger;
                }
                
                return new class($logger, $name, $this->collector) extends AbstractLogger
                {
                    public function __construct(
                        private LoggerInterface $logger,
                        private string $name,
                        private object $collector,
                    ) {}

                    public function log($level, string|\Stringable $message, array $context = []): void
                    {
                        $this->collector->log($this->name, [
                            'level' => $level,
                            'message' => (string)$message,
                            'context' => VarDumper::dump($context),
                        ]);
                        
                        $this->logger->log($level, $message, $context);
                    }
                };
            }
        };
    }
}