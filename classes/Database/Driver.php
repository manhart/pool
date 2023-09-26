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

abstract class Driver
{
    /**
     * @var int Default port
     */
    protected static int $port;

    /**
     * @var string Driver name
     */
    protected static string $name;

    /**
     * @var string Extension name
     */
    protected static string $provider = '';

    /**
     * @var array Instances of the drivers
     */
    private static array $instances = [];

    /**
     * Driver constructor (protected to prevent instantiation) - checks if the provider is loaded
     *
     * @throws \Exception
     */
    protected function __construct()
    {
        if(static::$provider && !\extension_loaded(static::$provider)) {
            throw new \RuntimeException('Provider '.static::$provider.' not loaded');
        }
    }

    /**
     * Gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): static
    {
        return self::$instances[static::class] ??= new static;
    }

    /**
     * Gets the number of rows in the result set
     */
    abstract public function numRows(mixed $result): int;

    /**
     * Returns the default port for the driver
     */
    public function getDefaultPort(): int
    {
        return static::$port;
    }

    /**
     * Returns the name of the driver
     */
    public function getName(): string
    {
        return static::$name;
    }

    /**
     * Sets the charset for the connection
     */
    abstract public function setCharset(string $charset): static;

    /**
     * Connects to the database
     * @throws \pool\classes\Database\Exception\DatabaseConnectionException
     */
    abstract public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options): Connection;

    /**
     * Executes a query and returns the query result
     *
     * @param Connection $connection
     * @param string $query SQL query
     * @param ...$params
     * @return mixed query result
     */
    abstract public function query(Connection $connection, string $query, ...$params): mixed;

    /**
     * Fetch the next row of a result set as an associative array
     */
    abstract public function fetch(mixed $result): array|null|false;

    /**
     * Frees the memory associated with a result
     */
    abstract public function free(mixed $result): void;

    /**
     * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
     */
    abstract public function escape(Connection $connection, string $string): string;

    /**
     * Closes the connection
     */
    abstract public function close(Connection $connection): void;

    /**
     * Returns a list of errors from the last command executed
     */
    abstract public function errors(?Connection $connection = null): array;

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     */
    abstract public function getLastId(Connection $connection): int|string;

    /**
     * Gets the number of affected rows in a previous SQL operation
     */
    abstract public function affectedRows(Connection $connection, mixed $result): int|false;

    /**
     * Get the columns info of a table
     */
    abstract public function getTableColumnsInfo(Connection $connection, string $database, string $table): array;

    /**
     * Set transaction isolation level
     */
    abstract public function setTransactionIsolationLevel(Connection $connection, string $level): bool;

    /**
     * Returns the transaction isolation level
     */
    abstract public function getTransactionIsolationLevel(Connection $connection): string;

    /**
     * Starts a transaction
     */
    abstract public function beginTransaction(Connection $connection): bool;

    /**
     * Commits a transaction
     */
    abstract public function commit(Connection $connection): bool;

    /**
     * Rolls back a transaction
     */
    abstract public function rollback(Connection $connection): bool;

    /**
     * Turns on or off auto-committing database modifications
     */
    abstract public function autocommit(Connection $connection, bool $enable): bool;

    /**
     * Returns the current transaction state
     */
    abstract public function inTransaction(Connection $connection): bool;

    /**
     * Creates a new savepoint
     */
    abstract public function createSavePoint(Connection $connection, string $savepoint): bool;

    /**
     * Rolls back to a savepoint
     */
    abstract public function rollbackToSavePoint(Connection $connection, string $savepoint): bool;

    /**
     * Releases a savepoint
     */
    abstract public function releaseSavePoint(Connection $connection, string $savepoint): bool;

    /**
     * Returns the server version
     */
    abstract public function getServerVersion(Connection $connection): string;
}