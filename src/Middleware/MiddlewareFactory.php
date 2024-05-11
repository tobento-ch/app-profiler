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

namespace Tobento\App\Profiler\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Tobento\App\Profiler\Collector\Middleware as MiddlewareCollector;
use Tobento\Service\Autowire\Autowire;
use Tobento\Service\Middleware\AutowiringMiddlewareFactory;
use Tobento\Service\Middleware\InvalidMiddlewareException;

/**
 * MiddlewareFactory
 */
class MiddlewareFactory extends AutowiringMiddlewareFactory
{
    /**
     * @var array<string, int> The middleware with its priority.
     */
    protected array $middlewareToPriority = [];

    /**
     * Create a new MiddlewareFactory.
     *
     * @param ContainerInterface $container
     * @param MiddlewareCollector $collector
     */
    public function __construct(
        protected ContainerInterface $container,
        protected MiddlewareCollector $collector,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Add middleware priority.
     *
     * @param string $middleware
     * @param int $priority
     * @return void
     */
    public function addPriority(string $middleware, int $priority): void
    {
        $this->middlewareToPriority[$middleware] = $priority;
    }
    
    /**
     * Create middleware.
     *
     * @param mixed $middleware
     * @throws InvalidMiddlewareException
     * @return MiddlewareInterface
     */
    public function createMiddleware(mixed $middleware): MiddlewareInterface
    {
        $middleware = parent::createMiddleware($middleware);
        
        $priority = $this->middlewareToPriority[$middleware::class] ?? '';
            
        return new MiddlewareWrapper(
            collector: $this->collector,
            middleware: $middleware,
            priority: (string) $priority,
        );
    }
}