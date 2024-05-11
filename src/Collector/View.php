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
use Tobento\App\Profiler\View\Table;
use Tobento\App\Profiler\VarDumper;
use Tobento\App\AppInterface;
use Tobento\Service\View\ViewInterface;
use Tobento\Service\View\RendererInterface;
use Tobento\Service\View\ViewNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * View
 */
class View implements LateCollectorInterface
{
    /**
     * @var null|RendererInterface
     */
    protected null|RendererInterface $renderer = null;
    
    /**
     * Create a new View.
     *
     * @param AppInterface $app
     * @param bool $collectViews
     * @param bool $collectAssets
     */
    public function __construct(
        protected AppInterface $app,
        protected bool $collectViews = true,
        protected bool $collectAssets = true,
    ) {
        if ($collectViews) {
            $app->on(
                RendererInterface::class,
                function (RendererInterface $renderer) {
                    return $this->createRenderer($renderer);
                }
            );            
        }
    }
    
    /**
     * Returns the name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'View';
    }
    
    /**
     * Returns the collected data.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return array Must be serializable natively by json_encode().
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function collect(ServerRequestInterface $request, ResponseInterface $response): array
    {
        $data = [];
        
        if (
            !is_null($this->renderer)
            && (!empty($views = $this->renderer->getViews()))
        ) {
            $data['views'] = $views;
        }
        
        if ($this->collectAssets && $this->app->has(ViewInterface::class)) {
            $view = $this->app->get(ViewInterface::class);
            
            $assets = [];

            foreach($view->assets()->all() as $asset) {
                $assets[] = [
                    'file' => $asset->getFile(),
                    'group' => $asset->getGroup(),
                    'order' => $asset->getOrder(),
                    'attributes' => $asset->getAttributes(),
                ];
            }
            
            if (!empty($assets)) {
                $data['assets'] = $assets;
            }
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
        return
            $view->render('profiler/table', [
                'table' => new Table(
                    rows: $data['views']['rendered'] ?? [],
                    title: 'Views Stack',
                    html: ['data'],
                ),
            ]).
            $view->render('profiler/table', [
                'table' => new Table(
                    rows: $data['views']['missed'] ?? [],
                    title: 'Missed Views',
                    description: 'The views which are not exists or will be dynamically added depending on the context.',
                    html: ['data'],
                ),
            ]).
            $view->render('profiler/table', [
                'table' => new Table(
                    rows: $data['assets'] ?? [],
                    title: 'View Assets',
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

    /**
     * Returns the created renderer.
     *
     * @param RendererInterface $renderer
     * @return RendererInterface
     */
    protected function createRenderer(RendererInterface $renderer): RendererInterface
    {
        return $this->renderer = new class($renderer) implements RendererInterface
        {
            private array $views = [];
            
            public function __construct(
                private RendererInterface $renderer,
            ) {}

            public function getViews(): array
            {
                return $this->views;
            }
            
            public function render(string $view, array $data = []): string
            {
                try {
                    $this->views['rendered'][] = ['view' => $view, 'data' => VarDumper::dump(array_keys($data))];
                    return $this->renderer->render($view, $data);
                } catch (ViewNotFoundException $e) {
                    $this->views['missed'][] = ['view' => $view, 'data' => VarDumper::dump(array_keys($data))];
                    return '';
                }
            }

            public function exists(string $view): bool
            {
                return $this->renderer->exists($view);
            }
        };
    }
}