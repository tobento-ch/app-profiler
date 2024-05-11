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
use Tobento\App\Http\ResponseEmitterInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * RequestResponse
 */
class RequestResponse implements CollectorInterface
{
    /**
     * @var null|ResponseInterface
     */
    protected null|ResponseInterface $response = null;
    
    /**
     * @var null|ServerRequestInterface
     */
    protected null|ServerRequestInterface $request = null;
    
    /**
     * @var int|float
     */
    protected int|float $executionTime = 0;
    
    /**
     * Create a new RequestResponse.
     *
     * @param AppInterface $app
     */
    public function __construct(
        AppInterface $app,
    ) {
        $startTime = hrtime(true);
        
        $app->on(
            ResponseEmitterInterface::class,
            function (ResponseEmitterInterface $emitter) use ($startTime) {
                $emitter->before(function (ResponseInterface $response, ServerRequestInterface $request) use ($startTime) {
                    $this->response = $response;
                    $this->request = $request;
                    $this->executionTime = round((hrtime(true) - $startTime) / 1e+6);
                    return $response;
                });
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
        return 'Request / Response';
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
        
        if (!is_null($this->request)) {
            $data[] = [
                'name' => 'Request',
                'value' => VarDumper::dump($this->request),
            ];
        }
        
        if (!is_null($this->response)) {
            $data[] = [
                'name' => 'Response',
                'value' => VarDumper::dump($this->response),
            ];
        }
        
        if ($this->executionTime > 0) {
            $data[] = [
                'name' => 'Total Execution time',
                'value' => (string)$this->executionTime.' ms',
            ];
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
                rows: $data,
                title: 'Request / Response',
                html: ['value']
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