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

/**
 * Databases
 */
class Databases implements DatabasesInterface
{
    /**
     * Create a new Databases.
     *
     * @param DatabasesInterface $databases
     * @param QueryRecorder $queryRecorder
     */
    public function __construct(
        protected DatabasesInterface $databases,
        protected QueryRecorder $queryRecorder,
    ) {}
    
    /**
     * Add a database.
     *
     * @param DatabaseInterface $database
     * @return static $this
     */
    public function add(DatabaseInterface $database): static
    {
        $this->databases->add($database);
        return $this;
    }
    
    /**
     * Register a database.
     *
     * @param string $name The database name.
     * @param callable $database
     * @return static $this
     */
    public function register(string $name, callable $database): static
    {
        $this->databases->register($name, $database);
        return $this;
    }
    
    /**
     * Returns the database by name.
     *
     * @param string $name The database name
     * @return DatabaseInterface
     * @throws DatabaseException
     */
    public function get(string $name): DatabaseInterface
    {
        $database = $this->databases->get($name);
        
        if (
            $database instanceof StorageDatabaseInterface
            && ! $database->storage() instanceof QueryStorage
        ) {
            return new StorageDatabase(
                storage: new QueryStorage($database->storage(), $this->queryRecorder),
                name: $database->name(),
            );
        }
        
        return $database;
    }
    
    /**
     * Returns true if the database exists, otherwise false.
     *
     * @param string $name The database name.
     * @return bool
     */
    public function has(string $name): bool
    {
        return $this->databases->has($name);
    }
    
    /**
     * Returns all database names.
     *
     * @return array
     */
    public function names(): array
    {
        return $this->databases->names();
    }

    /**
     * Adds a default name for the specified database.
     *
     * @param string $name The default name.
     * @param string $database The database name.
     * @return static $this
     */
    public function addDefault(string $name, string $database): static
    {
        $this->databases->addDefault($name, $database);
        return $this;
    }

    /**
     * Get the default databases.
     *
     * @return array<string, string> ['name' => 'database']
     */
    public function getDefaults(): array
    {
        return $this->databases->getDefaults();
    }
    
    /**
     * Get the database for the specified default name.
     *
     * @param string $name The type such as pdo.
     * @return DatabaseInterface
     * @throws DatabaseException
     */
    public function default(string $name): DatabaseInterface
    {
        $database = $this->databases->default($name);

        if (
            $database instanceof StorageDatabaseInterface
            && ! $database->storage() instanceof QueryStorage
        ) {
            return new StorageDatabase(
                storage: new QueryStorage($database->storage(), $this->queryRecorder),
                name: $database->name(),
            );
        }
        
        return $database;
    }
 
    /**
     * Returns true if the default database exists, otherwise false.
     *
     * @param string $name The default name.
     * @return bool
     */
    public function hasDefault(string $name): bool
    {
        return $this->databases->hasDefault($name);
    }
}