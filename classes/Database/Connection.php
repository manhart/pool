<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database;

use pool\classes\Core\PoolObject;

class Connection extends PoolObject
{
    private mixed $connection;

    /**
     * @var Driver
     */
    private Driver $driver;

    private bool $closed = false;

    public function __construct(mixed $connection, Driver $driver)
    {
        $this->connection = $connection;
        $this->driver = $driver;
    }

    /**
     * Returns the connection as resource (of mysqli, sqlsrv, ...)
     */
    public function getConnection(): mixed
    {
        return $this->connection;
    }

    /**
     * Executes a query and returns the query result
     */
    public function query(string $query, ...$params): mixed
    {
        return $this->driver->query($this, $query, ...$params);
    }

    /**
     * Close the connection
     */
    public function close(): void
    {
        if (!$this->closed) {
            $this->driver->close($this);
            $this->closed = true;
        }
    }

    public function fetch(mixed $result): array|false
    {
        return $this->driver->fetch($result);
    }

    public function getNumRows(mixed $result): int
    {
        return $this->driver->numRows($result);
    }

    public function getAffectedRows(mixed $result): int|false
    {
        return $this->driver->affectedRows($this, $result);
    }

    public function escape(string $string): string
    {
        return $this->driver->escape($this, $string);
    }

    public function getTableColumnsInfo(string $database, string $table): array
    {
        return $this->driver->getTableColumnsInfo($this, $database, $table);
    }

    /**
     * Sets the transaction isolation level
     */
    public function setTransactionIsolationLevel(string $level): bool
    {
        return $this->driver->setTransactionIsolationLevel($this, $level);
    }

    /**
     * Turns on or off auto-committing database modifications
     */
    public function autocommit(bool $enable): bool
    {
        return $this->driver->autocommit($this, $enable);
    }

    /**
     * Starts a transaction
     */
    public function beginTransaction(): bool
    {
        return $this->driver->beginTransaction($this);
    }

    /**
     * Commits a transaction
     */
    public function commit(): bool
    {
        return $this->driver->commit($this);
    }

    /**
     * Rolls back a transaction
     */
    public function rollback(): bool
    {
        return $this->driver->rollback($this);
    }

    /**
     * Creates a savepoint
     */
    public function createSavePoint(string $savepoint): bool
    {
        return $this->driver->createSavePoint($this, $savepoint);
    }

    /**
     * Releases a savepoint
     */
    public function releaseSavePoint(string $savepoint): bool
    {
        return $this->driver->releaseSavePoint($this, $savepoint);
    }

    /**
     * Rolls back to a savepoint
     */
    public function rollbackToSavePoint(string $savepoint): bool
    {
        return $this->driver->rollbackToSavePoint($this, $savepoint);
    }

    /**
     * Ask the driver if the result has rows
     */
    public function hasRows(mixed $result): bool
    {
        return $this->driver->hasRows($result);
    }
}