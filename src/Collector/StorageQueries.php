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
use Tobento\App\Profiler\Storage\Databases;
use Tobento\App\Profiler\Storage\QueryRecorder;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\View\ViewInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * StorageQueries
 */
class StorageQueries implements CollectorInterface
{
    /**
     * @var QueryRecorder
     */
    protected QueryRecorder $queryRecorder;
    
    /**
     * Create a new Boots.
     *
     * @param AppInterface $app
     */
    public function __construct(
        AppInterface $app,
    ) {
        $this->queryRecorder = new QueryRecorder();

        $app->on(
            DatabasesInterface::class,
            function (DatabasesInterface $databases): DatabasesInterface {
                return new Databases($databases, $this->queryRecorder);
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
        return 'Storage Queries';
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
        
        if (!empty($queries = $this->queryRecorder->all())) {
            $data['queries'] = $queries;
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
                rows: $data['queries'] ?? [],
                title: 'Storage Queries',
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
        $queriesCount = count($data['queries'] ?? []);
        
        return [
            //'icon' => 'name',
            'badge' => $queriesCount,
            'badgeAttributes' => ['title' => sprintf('%d queries executed', $queriesCount)],
        ];
    }
}