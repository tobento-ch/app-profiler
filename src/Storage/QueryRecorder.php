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

namespace Tobento\App\Profiler\Storage;

/**
 * QueryRecorder
 */
class QueryRecorder
{
    /**
     * @var array
     */
    protected array $queries = [];
    
    /**
     * Add a query.
     *
     * @param array $query
     * @return void
     */
    public function add(array $query): void
    {
        $this->queries[] = $query;
    }
    
    /**
     * Returns all queries.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->queries;
    }
}