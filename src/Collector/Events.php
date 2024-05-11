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

use Tobento\App\AppInterface;
use Tobento\App\Profiler\CollectorInterface;
use Tobento\App\Profiler\View;
use Tobento\App\Profiler\VarDumper;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\Event\EventsFactoryInterface;
use Tobento\Service\Event\EventsInterface;
use Tobento\Service\Event\ListenersInterface;
use Tobento\Service\Event\DispatcherFactoryInterface;
use Tobento\Service\Autowire\Autowire;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Container\ContainerInterface;

/**
 * Events
 */
class Events implements CollectorInterface
{
    /**
     * @var null|object
     */
    protected null|object $eventsCollector = null;
    
    /**
     * Create a new Events.
     *
     * @param AppInterface $app
     */
    public function __construct(
        AppInterface $app,
    ) {
        $app->on(
            EventsFactoryInterface::class,
            function(EventsFactoryInterface $eventsFactory, ContainerInterface $container): EventsFactoryInterface {
                return $this->createEventsFactory($eventsFactory, $container);
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
        return 'Events';
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
        
        if (!is_null($this->eventsCollector)) {
            $data['dispatched'] = $this->eventsCollector->getDispatchedEvents();
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
                rows: $data['dispatched'] ?? [],
                title: 'Dispatched Events',
                html: ['event', 'listeners'],
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
        $eventsCount = count($data['dispatched'] ?? []);
        
        return [
            'badge' => $eventsCount,
            'badgeAttributes' => ['title' => sprintf('%d dispatched events', $eventsCount)],
        ];
    }

    /**
     * Returns the events collector.
     *
     * @return object
     */
    public function eventsCollector(): object
    {
        if (!is_null($this->eventsCollector)) {
            return $this->eventsCollector;
        }
        
        return $this->eventsCollector = new class()
        {
            private array $dispatchedEvents = [];

            public function addDispatchedEvent(object $event, null|object $listener = null): void
            {
                $id = $event::class.(string)spl_object_id($event);
                
                if (is_null($listener)) {
                    $this->dispatchedEvents[$id]['event'] = VarDumper::dump($event);
                } else {
                    if (isset($this->dispatchedEvents[$id]['listeners'])) {
                        $this->dispatchedEvents[$id]['listeners'] = $this->dispatchedEvents[$id]['listeners'].VarDumper::dump($listener);
                    } else {
                        $this->dispatchedEvents[$id]['listeners'] = VarDumper::dump($listener);
                    }
                }
            }
            
            public function getDispatchedEvents(): array
            {
                return $this->dispatchedEvents;
            }
        };
    }
    
    /**
     * Returns the created events factory.
     *
     * @param EventsFactoryInterface $eventsFactory
     * @param ContainerInterface $container
     * @return EventsFactoryInterface
     */
    protected function createEventsFactory(
        EventsFactoryInterface $eventsFactory,
        ContainerInterface $container,
    ): EventsFactoryInterface {
        return new class($eventsFactory, $container, $this) implements EventsFactoryInterface
        {
            public function __construct(
                private EventsFactoryInterface $eventsFactory,
                private ContainerInterface $container,
                private Events $eventsCollector,
            ) {}

            public function createEvents(
                null|ListenersInterface $listeners = null,
                null|DispatcherFactoryInterface $dispatcherFactory = null
            ): EventsInterface {
                $dispatcherFactory = $this->eventsCollector->createDispatcherFactory(
                    container: $this->container,
                    eventsCollector: $this->eventsCollector,
                );
                return $this->eventsFactory->createEvents($listeners, $dispatcherFactory);
            }
        };
    }
    
    /**
     * Returns the created dispatcher.
     *
     * @param ListenerProviderInterface $listenerProvider
     * @param ContainerInterface $container
     * @param object $eventsCollector
     * @return EventDispatcherInterface
     */
    public function createDispatcher(
        ListenerProviderInterface $listenerProvider,
        ContainerInterface $container,
        object $eventsCollector,
    ): EventDispatcherInterface {
        return new class($listenerProvider, $container, $eventsCollector) implements EventDispatcherInterface
        {
            private Autowire $autowire;
            
            public function __construct(
                private ListenerProviderInterface $listenerProvider,
                ContainerInterface $container,
                private object $eventsCollector,
            ) {
                $this->autowire = new Autowire($container);
            }

            public function dispatch(object $event): object
            {
                $stoppable = $event instanceof StoppableEventInterface;
                
                $this->eventsCollector->addDispatchedEvent(event: $event);
                
                foreach($this->listenerProvider->getListenersForEvent($event) as $listener) {
                    if (
                        $stoppable
                        && $event->isPropagationStopped()
                    ) {
                        return $event;
                    }
                    
                    $this->eventsCollector->addDispatchedEvent(event: $event, listener: $listener);
                    $this->autowire->call($listener, [$event]);
                }

                return $event;
            }
        };
    }    

    /**
     * Returns the created dispatcher factory.
     *
     * @param ContainerInterface $container
     * @param object $eventsCollector
     * @return DispatcherFactoryInterface
     */
    public function createDispatcherFactory(
        ContainerInterface $container,
        object $eventsCollector,
    ): DispatcherFactoryInterface {
        return new class($container, $this) implements DispatcherFactoryInterface
        {
            public function __construct(
                private ContainerInterface $container,
                private Events $eventsCollector,
            ) {}

            public function createDispatcher(ListenerProviderInterface $listenerProvider): EventDispatcherInterface
            {
                return $this->eventsCollector->createDispatcher(
                    $listenerProvider,
                    $this->container,
                    $this->eventsCollector->eventsCollector(),
                );
            }
        };
    }
}