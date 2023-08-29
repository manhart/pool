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

use Exception;

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
        if(static::$provider && !extension_loaded(static::$provider)) {
            throw new Exception('Provider '.static::$provider.' not loaded');
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
     */
    abstract public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options): ConnectionWrapper;

    /**
     * Executes a query and returns the query result
     *
     * @param \pool\classes\Database\ConnectionWrapper $connectionWrapper
     * @param string $query SQL query
     * @param ...$params
     * @return mixed query result
     */
    abstract public function query(ConnectionWrapper $connectionWrapper, string $query, ...$params): mixed;

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
    abstract public function escape(ConnectionWrapper $connectionWrapper, string $string): string;

    /**
     * Closes the connection
     */
    abstract public function close(ConnectionWrapper $connectionWrapper): void;

    /**
     * Returns a list of errors from the last command executed
     */
    abstract public function errors(?ConnectionWrapper $connectionWrapper = null): array;

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     */
    abstract public function getLastId(ConnectionWrapper $connectionWrapper): int|string;

    /**
     * Gets the number of affected rows in a previous SQL operation
     */
    abstract public function affectedRows(ConnectionWrapper $connectionWrapper, mixed $result): int|false;

    /**
     * Get the columns info of a table
     */
    abstract public function getTableColumnsInfo(ConnectionWrapper $connectionWrapper, string $database, string $table): array;
}