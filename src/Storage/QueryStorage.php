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

use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\DatabaseInterface;
use Tobento\Service\Database\DatabaseException;
use Tobento\Service\Database\Storage\StorageDatabaseInterface;
use Tobento\Service\Database\Storage\StorageDatabase;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\StorageAdapterInterface;
use Tobento\Service\Storage\ItemInterface;
use Tobento\Service\Storage\ItemsInterface;
use Tobento\Service\Storage\Grammar\GrammarInterface;
use Tobento\Service\Storage\Grammar\Grammar;
use Tobento\Service\Storage\Tables\TablesInterface;
use Tobento\Service\Storage\Query\SubQuery;
use Closure;
use Throwable;

/**
 * QueryStorage
 */
class QueryStorage implements StorageInterface, StorageAdapterInterface
{
    /**
     * Create a new QueryStorage.
     *
     * @param StorageInterface $storage
     * @param QueryRecorder $queryRecorder
     */
    public function __construct(
        protected StorageInterface $storage,
        protected QueryRecorder $queryRecorder,
    ) {}
    
    /**
     * Returns the adapted storage.
     *
     * @return StorageInterface
     */
    public function adaptedStorage(): StorageInterface
    {
        return $this->storage;
    }
    
    /**
     * Returns the storage
     *
     * @return static
     */
    public function storage(): StorageInterface
    {
        return $this->storage;
    }
    
    /**
     * Record a query.
     *
     * @param Closure $callback
     * @return mixed
     */
    protected function record(Closure $callback, string $statement = ''): mixed
    {
        $startTime = hrtime(true);
                
        $value = $callback();
        
        $executionTime = (hrtime(true) - $startTime) / 1e+6;
        
        $grammar = $this->grammar();
        
        $item = null;
        
        if ($grammar instanceof Grammar) {
            $item = $grammar->getItem();
        }
        
        $this->queryRecorder->add([
            'time (ms)' => $executionTime,
            'statement' => str_replace(',', ', ', (string)$grammar?->getStatement()).$statement,
            'bindings' => $grammar?->getBindings(),
            'item' => $item,
            'storage' => $this->storage::class,
        ]);
        
        return $value;
    }
    
    /**
     * Returns a new storage instance.
     *
     * @return static
     */
    public function new(): static
    {
        return new static($this->storage->new(), $this->queryRecorder);
    }
    
   /**
     * Get the tables.
     *
     * @return TablesInterface
     */    
    public function tables(): TablesInterface
    {
        return $this->storage->tables();
    }
    
    /**
     * Set the table name.
     *
     * @param string $table
     * @return static $this
     */
    public function table(string $table): static
    {
        $this->storage->table($table);
        return $this;
    }

    /**
     * Get the table name.
     *
     * @return string
     */    
    public function getTable(): string
    {
        return $this->storage->getTable();
    }
    
    /**
     * Fetches the table items.
     *
     * @param string $table The table name.
     * @return iterable The items fetched.
     */
    public function fetchItems(string $table): iterable
    {
        return $this->storage->fetchItems($table);
    }

    /**
     * Stores the table items.
     *
     * @param string $table The table name.
     * @param iterable $items The items to store.
     * @return iterable The stored items.
     */
    public function storeItems(string $table, iterable $items): iterable
    {
        return $this->storage->storeItems($table, $items);
    }
    
    /**
     * Deletes the specified table.
     *
     * @param string $table The table name.
     * @return void
     */
    public function deleteTable(string $table): void
    {
        $this->storage->deleteTable($table);
    }

    /**
     * The columns to select.
     *
     * @param string ...$columns
     * @return static $this
     */    
    public function select(string ...$columns): static
    {
        $this->storage->select(...$columns);
        return $this;
    }

    /**
     * Get a single item by id.
     *
     * @param int|string $id
     * @return null|ItemInterface
     */
    public function find(int|string $id): null|ItemInterface
    {
        return $this->record(function() use ($id): null|ItemInterface {
            return $this->storage->find($id);
        });
    }
    
    /**
     * Get a single item.
     *
     * @return null|ItemInterface
     */
    public function first(): null|ItemInterface
    {
        return $this->record(function(): null|ItemInterface {
            return $this->storage->first();
        });
    }
    
    /**
     * Get the items.
     *
     * @return ItemsInterface
     */
    public function get(): ItemsInterface
    {
        return $this->record(function(): ItemsInterface {
            return $this->storage->get();
        });
    }

    /**
     * Get a single column's value from the first item.
     *
     * @param string $column The column name
     * @return mixed
     */    
    public function value(string $column): mixed
    {
        return $this->record(function() use ($column): mixed {
            return $this->storage->value($column);
        });
    }

    /**
     * Get column's value from the items.
     *
     * @param string $column The column name
     * @param null|string $key The column name for the index
     * @return ItemInterface
     */
    public function column(string $column, null|string $key = null): ItemInterface
    {
        return $this->record(function() use ($column, $key): ItemInterface {
            return $this->storage->column($column, $key);
        });
    }

    /**
     * Get the count of the query.
     *
     * @return int
     */    
    public function count(): int
    {
        return $this->record(function(): int {
            return $this->storage->count();
        }, ' [count() query]');
    }

    /**
     * Insert an item.
     *
     * @param array $item The item data
     * @return ItemInterface The item inserted.
     */    
    public function insert(array $item): ItemInterface
    {
        return $this->record(function() use ($item): ItemInterface {
            return $this->storage->insert($item);
        });
    }
    
    /**
     * Insert items.
     *
     * @param iterable $items
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The items inserted.
     */
    public function insertItems(iterable $items, null|array $return = []): ItemsInterface
    {
        return $this->record(function() use ($items, $return): ItemsInterface {
            return $this->storage->insertItems($items, $return);
        }, 'insertItems() query');
    }
    
    /**
     * Update item(s).
     *
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The updated items.
     */
    public function update(array $item, null|array $return = []): ItemsInterface
    {
        return $this->record(function() use ($item, $return): ItemsInterface {
            return $this->storage->update($item, $return);
        });
    }

    /**
     * Update or insert item(s).
     *
     * @param array $attributes The attributes to query.
     * @param array $item The item data
     * @param null|array $return The columns to be returned.
     * @return ItemInterface|ItemsInterface
     */
    public function updateOrInsert(
        array $attributes,
        array $item,
        null|array $return = []
    ): ItemInterface|ItemsInterface {
        return $this->record(function() use ($attributes, $item, $return): ItemInterface|ItemsInterface {
            return $this->storage->updateOrInsert($attributes, $item, $return);
        });
    }
    
    /**
     * Delete item(s).
     *
     * @param null|array $return The columns to be returned.
     * @return ItemsInterface The deleted items.
     */
    public function delete(null|array $return = []): ItemsInterface
    {
        return $this->record(function() use ($return): ItemsInterface {
            return $this->storage->delete($return);
        });
    }
            
    /**
     * Add a inner join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function join(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->storage->join($table, $first, $operator, $second);
        return $this;
    }
    
    /**
     * Add a left join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function leftJoin(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->storage->leftJoin($table, $first, $operator, $second);
        return $this;
    }

    /**
     * Add a right join to the query.
     *
     * @param string $table The table name
     * @param string|Closure $first The first column name
     * @param string $operator The operator
     * @param null|string $second The second column name
     * @return static $this
     */
    public function rightJoin(
        string $table,
        string|Closure $first,
        string $operator = '=',
        null|string $second = null
    ): static {
        $this->storage->rightJoin($table, $first, $operator, $second);
        return $this;
    }
    
    /**
     * Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function where(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        $this->storage->where($column, $operator, $value);
        return $this;
    }

    /**
     * Where column clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function whereColumn(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        $this->storage->whereColumn($column, $operator, $value, $boolean);
        return $this;
    }
    
    /**
     * Or Where column clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orWhereColumn(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        $this->storage->orWhereColumn($column, $operator, $value);
        return $this;
    }
    
    /**
     * Or Where clause
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orWhere(string|Closure $column, string $operator = '=', mixed $value = null): static
    {
        $this->storage->orWhere($column, $operator, $value);
        return $this;
    }

    /**
     * Where IN clause
     *
     * @param string|Closure $column The column name. 
     * @param array $value The values
     * @return static $this
     */
    public function whereIn(string|Closure $column, mixed $value = null): static
    {
        $this->storage->whereIn($column, $value);
        return $this;
    }

    /**
     * Where or IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereIn(string|Closure $column, mixed $value = null): static
    {
        $this->storage->orWhereIn($column, $value);
        return $this;
    }

    /**
     * Where NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function whereNotIn(string|Closure $column, mixed $value = null): static
    {
        $this->storage->whereNotIn($column, $value);
        return $this;
    }

    /**
     * Where or NOT IN clause
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The values
     * @return static $this
     */
    public function orWhereNotIn(string|Closure $column, mixed $value = null): static
    {
        $this->storage->orWhereNotIn($column, $value);
        return $this;
    }

    /**
     * Where null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNull(string|Closure $column): static
    {
        $this->storage->whereNull($column);
        return $this;
    }

    /**
     * Where or null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNull(string|Closure $column): static
    {
        $this->storage->orWhereNull($column);
        return $this;
    }

    /**
     * Where not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function whereNotNull(string|Closure $column): static
    {
        $this->storage->whereNotNull($column);
        return $this;
    }

    /**
     * Where or not null clause
     *
     * @param string|Closure $column The column name. 
     * @return static $this
     */
    public function orWhereNotNull(string|Closure $column): static
    {
        $this->storage->orWhereNotNull($column);
        return $this;
    }
 
    /**
     * Where between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereBetween(string|Closure $column, array $values): static
    {
        $this->storage->whereBetween($column, $values);
        return $this;
    }
    
    /**
     * Where between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereBetween(string|Closure $column, array $values): static
    {
        $this->storage->orWhereBetween($column, $values);
        return $this;
    }
    
    /**
     * Where not between clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function whereNotBetween(string|Closure $column, array $values): static
    {
        $this->storage->whereNotBetween($column, $values);
        return $this;
    }
    
    /**
     * Where not between or clause
     *
     * @param string|Closure $column The column name.
     * @param array $values The values [50, 100]
     * @return static $this
     */
    public function orWhereNotBetween(string|Closure $column, array $values): static
    {
        $this->storage->orWhereNotBetween($column, $values);
        return $this;
    }
    
    /**
     * Where Json contains clause
     *
     * @param string $column The column name.
     * @param mixed $value
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function whereJsonContains(
        string $column,
        mixed $value,
        string $boolean = 'and',
        bool $not = false
    ): static {
        $this->storage->whereJsonContains($column, $value, $boolean, $not);
        return $this;
    }
    
    /**
     * Where Json contains or clause
     *
     * @param string $column The column name.
     * @param mixed $value
     * @return static $this
     */
    public function orWhereJsonContains(
        string $column,
        mixed $value,
    ): static {
        $this->storage->orWhereJsonContains($column, $value);
        return $this;
    }
    
    /**
     * Where Json contains key clause
     *
     * @param string $column The column name.
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function whereJsonContainsKey(
        string $column,
        string $boolean = 'and',
        bool $not = false
    ): static {
        $this->storage->whereJsonContainsKey($column, $boolean, $not);
        return $this;
    }
    
    /**
     * Where Json contains key or clause
     *
     * @param string $column The column name.
     * @param string $boolean
     * @param bool $not
     * @return static $this
     */
    public function orWhereJsonContainsKey(
        string $column,
    ): static {
        $this->storage->orWhereJsonContainsKey($column);
        return $this;
    }
    
    /**
     * Where Json length clause
     *
     * @param string $column The column name.
     * @param string $operator
     * @param mixed $value
     * @param string $boolean
     * @return static $this
     */
    public function whereJsonLength(
        string $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        $this->storage->whereJsonLength($column, $operator, $value, $boolean);
        return $this;
    }
    
    /**
     * Where Json length or clause
     *
     * @param string $column The column name.
     * @param string $operator
     * @param mixed $value
     * @return static $this
     */
    public function orWhereJsonLength(
        string $column,
        string $operator = '=',
        mixed $value = null
    ): static {
        $this->storage->orWhereJsonLength($column, $operator, $value);
        return $this;
    }
    
    /**
     * Where Raw clause
     *
     * @param string $value The raw value. 
     * @param array $bindings Any bindings
     * @return static $this
     */
    public function whereRaw(string $value, array $bindings = []): static
    {
        $this->storage->whereRaw($value, $bindings);
        return $this;
    }
    
    /**
     * Group by clause.
     *
     * @param array|string $groups The column(s) name.
     * @return static $this
     */
    public function groupBy(...$groups): static
    {
        $this->storage->groupBy(...$groups);
        return $this;
    }
    
    /**
     * Having clause.
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function having(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): static {
        $this->storage->having($column, $operator, $value, $boolean);
        return $this;
    }
    
    /**
     * Or having clause.
     *
     * @param string|Closure $column The column name.
     * @param string $operator The operator for the compare statement.
     * @param mixed $value The value to compare.
     * @return static $this
     */
    public function orHaving(
        string|Closure $column,
        string $operator = '=',
        mixed $value = null,
    ): static {
        $this->storage->orHaving($column, $operator, $value);
        return $this;
    }

    /**
     * Having between clause.
     *
     * @param string|Closure $column The column name. 
     * @param mixed $value The value to compare. [4, 5]
     * @param bool $not
     * @param string $boolean The boolean such as 'and', 'or'
     * @return static $this
     */
    public function havingBetween(
        string|Closure $column,
        mixed $value = null,
        bool $not = false,
        string $boolean = 'and'
    ): static {
        $this->storage->havingBetween($column, $value, $not, $boolean);
        return $this;
    }
        
    /**
     * Order clause.
     *
     * @param string $column The column name.
      * @param string $direction
     * @return static $this
     */
    public function order(string $column, string $direction = 'ASC'): static
    {
        $this->storage->order($column, $direction);
        return $this;
    }
            
    /**
     * Limit clause.
     *
     * @param null|int $number The number of rows to be returned. 
      * @param int $offset The offset where to start.
     * @return static $this
     */
    public function limit(null|int $number, int $offset = 0): static
    {
        $this->storage->limit($number, $offset);
        return $this;
    }

    /**
     * The index column for items.
     *
     * @param null|string $column The column name.
     * @return static $this
     */    
    public function index(null|string $column): static
    {
        $this->storage->index($column);
        return $this;
    }
        
    /**
     * Get the select
     *
     * @return null|array|string
     */
    public function getSelect(): null|array|string
    {
        return $this->storage->getSelect();
    }

    /**
     * Get the joins.
     *
     * @return array
     */    
    public function getJoins(): array
    {
        return $this->storage->getJoins();
    }
    
    /**
     * Get the wheres
     *
     * @return array
     */
    public function getWheres(): array
    {
        return $this->storage->getWheres();
    }

    /**
     * Set the wheres
     *
     * @param array $wheres
     * @return static $this
     */
    public function setWheres(array $wheres): static
    {
        $this->storage->setWheres($wheres);
        return $this;
    }

    /**
     * Get the groups
     *
     * @return array
     */
    public function getGroups(): array
    {
        return $this->storage->getGroups();
    }

    /**
     * Get the havings
     *
     * @return array
     */
    public function getHavings(): array
    {
        return $this->storage->getHavings();
    }
    
    /**
     * Get the orders
     *
     * @return array
     */
    public function getOrders(): array
    {
        return $this->storage->getOrders();
    }

    /**
     * Get the limit
     *
     * @return null|array
     */
    public function getLimit(): null|array
    {
        return $this->storage->getLimit();
    }

    /**
     * Get the index column for items.
     *
     * @return null|string The column name.
     */    
    public function getIndex(): null|string
    {
        return $this->storage->getIndex();
    }

    /**
     * Get the bindings
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->storage->getBindings();
    }

    /**
     * Get the query.
     *
     * param null|Closure $callback
     * @return array [statement, bindings[]]
     */    
    public function getQuery(null|Closure $callback = null): array
    {
        return $this->storage->getQuery($callback);
    }

    /**
     * Create a new SubQuery
     *
     * @param string $statement The statement
     * @param array $bindings The bindings
     * @return SubQuery
     */    
    public function createSubQuery(string $statement, array $bindings = []): SubQuery
    {
        return $this->storage->createSubQuery($statement, $bindings);
    }
    
    /**
     * Begin a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function begin(): bool
    {
        return $this->storage->begin();
    }
    
    /**
     * Commit a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function commit(): bool
    {
        return $this->storage->commit();
    }

    /**
     * Rollback a transaction.
     *
     * @return bool Returns true on success or false on failure.
     */
    public function rollback(): bool
    {
        return $this->storage->rollback();
    }
    
    /**
     * Execute a transaction.
     *
     * @param callable $callback
     * @return void
     * @throws Throwable
     */
    public function transaction(callable $callback): void
    {
        $this->storage->transaction($callback);
    }
    
    /**
     * Returns true if supporting nested transactions, otherwise false.
     *
     * @return bool
     */
    public function supportsNestedTransactions(): bool
    {
        return $this->storage->supportsNestedTransactions();
    }
    
    /**
     * Returns true if supports returning items, otherwise false.
     *
     * @param string $method The methods such as insert, insertMany, update, delete.
     * @return bool
     */
    public function supportsReturningItems(string $method): bool
    {
        return $this->storage->supportsReturningItems($method);
    }
    
    /**
     * Clear query
     *
     * @return static $this
     */
    public function clear(): static
    {
        $this->storage->clear();
        return $this;
    }
    
    /**
     * Get last grammar used.
     *
     * @return null|GrammarInterface
     */    
    public function grammar(): null|GrammarInterface
    {
        return $this->storage->grammar();
    }
}