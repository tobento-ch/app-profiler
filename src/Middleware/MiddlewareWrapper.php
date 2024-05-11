<?php

/**
 * TOBENTO
 *
 * @copyright    Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Profiler\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tobento\App\Profiler\Collector\Middleware as MiddlewareCollector;
use Tobento\App\Profiler\VarDumper;

/**
 * MiddlewareWrapper
 */
final class MiddlewareWrapper implements MiddlewareInterface
{
    /**
     * Create the MiddlewareWrapper.
     *
     * @param MiddlewareCollector $collector
     * @param MiddlewareInterface $middleware
     * @param string $priority
     */
    public function __construct(
        private MiddlewareCollector $collector,
        private MiddlewareInterface $middleware,
        private string $priority,
    ) {}

    /**
     * Process the middleware.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->collector->addMiddleware(
            $this->middleware,
            [
                'process' => 'before',
                'priority' => $this->priority,
                'middleware' => $this->middleware::class,
                'data' => VarDumper::dump($request),
            ]
        );
        
        $response = $this->middleware->process($request, $handler);

        $this->collector->addMiddleware(
            $this->middleware,
            [
                'process' => 'after',
                'priority' => $this->priority,
                'middleware' => $this->middleware::class,
                'data' => VarDumper::dump($response),
            ]
        );
        
        return $response;
    }
}