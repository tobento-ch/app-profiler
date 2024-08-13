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
use Tobento\App\Profiler\Middleware\MiddlewareFactory;
use Tobento\App\Profiler\View;
use Tobento\Service\Middleware\MiddlewareDispatcherInterface;
use Tobento\Service\Middleware\MiddlewareDispatcher;
use Tobento\Service\Middleware\MiddlewareFactoryInterface;
use Tobento\Service\Middleware\FallbackHandler;
use Tobento\Service\View\ViewInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Middleware
 */
class Middleware implements CollectorInterface
{
    /**
     * @var array The middlewares
     */
    protected array $middlewares = [];
    
    /**
     * Create a new Middleware.
     *
     * @param AppInterface $app
     */
    public function __construct(
        protected AppInterface $app,
    ) {
        $app->on(
            MiddlewareDispatcherInterface::class,
            function (MiddlewareDispatcherInterface $dispatcher) {
                if (! $dispatcher instanceof MiddlewareDispatcher) {
                    return $dispatcher;
                }
                
                return $this->createDispatcher();
            }
        );
    }

    /**
     * Returns the created dispatcher.
     *
     * @return MiddlewareDispatcherInterface
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function createDispatcher(): MiddlewareDispatcherInterface
    {
        $fallbackHandler = new FallbackHandler($this->app->get(ResponseInterface::class));
        $middlewareFactory = new MiddlewareFactory(
            container: $this->app->get(ContainerInterface::class),
            collector: $this,
            replaces: $this->app->get(MiddlewareFactoryInterface::class)->getReplaceMiddlewares(),
        );
        
        return new class($fallbackHandler, $middlewareFactory) extends MiddlewareDispatcher
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                foreach($this->middleware as $priority => $middlewares) {
                    foreach($middlewares as $name => $middleware) {
                        $priority = (int)($middleware['priority'] ?? $priority);
                        $this->middlewareFactory->addPriority($name, $priority);
                    }
                }
                
                return $this->dispatching($request);
            }
        };
    }
    
    /**
     * Add collected middleware.
     *
     * @param MiddlewareInterface $middleware
     * @param array $data
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware, array $data = []): void
    {
        $this->middlewares[] = $data;
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Middleware';
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
        
        if (!empty($this->middlewares)) {
            $data['middlewares'] = $this->middlewares;
        }
        
        if (
            $this->app->has(MiddlewareDispatcherInterface::class)
            && !empty($aliases = $this->app->get(MiddlewareDispatcherInterface::class)->getAliases())
        ) {
            $data['aliases'] = $aliases;
        }
        
        if (
            $this->app->has(MiddlewareDispatcherInterface::class)
            && !empty($groups = $this->app->get(MiddlewareDispatcherInterface::class)->getGroups())
        ) {
            $data['groups'] = $groups;
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
        $groups = [];
        
        foreach($data['groups'] ?? [] as $name => $group) {
            $groups[] = ['name' => $name, 'middlewares' => $group];
        }
        
        $aliases = [];
        
        foreach($data['aliases'] ?? [] as $alias => $md) {
            $aliases[] = ['alias' => $alias, 'middleware' => $md];
        }
        
        return
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['middlewares'] ?? [],
                    title: 'Dispatched Middleware',
                    html: ['data'],
                ),
            ]).
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $groups,
                    title: 'Middleware Groups',
                ),
            ]).            
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $aliases,
                    title: 'Middleware Aliases',
                ),
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
        return [];
    }
}