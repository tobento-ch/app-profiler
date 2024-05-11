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

use Tobento\App\Profiler\LateCollectorInterface;
use Tobento\App\Profiler\View;
use Tobento\Service\Routing\RouterInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Routes
 */
class Routes implements LateCollectorInterface
{
    /**
     * Create a new Routes.
     *
     * @param null|RouterInterface $router
     */
    public function __construct(
        protected null|RouterInterface $router,
    ) {}
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Routes';
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
        if (is_null($this->router)) {
            return [];
        }
        
        $rows = [];
        
        foreach($this->router->getRoutes() as $route) {
            $data = $route->toArray();
            $rows[] = [
                'method' => $data['method'],
                'uri' => $data['uri'],
                'name' => $route->getName(),
                'handler' => $data['handler'],
            ];
        }
        
        return $rows;
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
                rows: $data,
                title: 'Routes',
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
        $routesCount = count($data);
        
        return [
            //'icon' => 'name',
            'badge' => $routesCount,
            'badgeAttributes' => ['title' => sprintf('%d routes registered', $routesCount)],
        ];
    }
}