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
use Tobento\App\Profiler\LateCollectorInterface;
use Tobento\App\Profiler\View;
use Tobento\App\Profiler\VarDumper;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Boots
 */
class Boots implements LateCollectorInterface
{
    /**
     * Create a new Boots.
     *
     * @param AppInterface $app
     */
    public function __construct(
        protected AppInterface $app,
    ) {}
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'Boots';
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
        $registered = [];
        
        foreach($this->app->booter()->getBoots() as $bootRegistry) {
            $registered[] = [
                'name' => $bootRegistry->name(),
                'priority' => $bootRegistry->priority(),
            ];
        }
        
        $boots = [];
        
        foreach($this->app->booter()->getBooted() as $booted) {
            $booted['info'] = VarDumper::dump($booted['info']);
            $boots[] = $booted;
        }
        
        return [
            'registered' => $registered,
            'booted' => $boots,
        ];
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
        return
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['booted'] ?? [],
                    title: 'Booted',
                    html: ['info'],
                )
            ]).
            $view->render('profiler/table', [
                'table' => new View\Table(
                    rows: $data['registered'] ?? [],
                    title: 'Registered Boots'
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