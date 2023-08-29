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

class ConnectionWrapper
{
    private mixed $connection;

    /**
     * @var \pool\classes\Database\Driver
     */
    private Driver $driver;

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
    public function query(string $query, ...$params)
    {
        return $this->driver->query($this, $query, ...$params);
    }

    /**
     * Close the connection
     */
    public function close(): void
    {
        $this->driver->close($this);
    }

    public function fetch(mixed $result): array|false
    {
        return $this->driver->fetch($result);
    }


    public function getNumRows(mixed $result): int
    {
        return $this->driver::numRows($result);
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
}