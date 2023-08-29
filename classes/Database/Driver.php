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
    protected static int $port;

    protected static string $name;

    protected static string $provider = '';

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

    abstract public function fetch(mixed $result): array|null|false;

    abstract public function free(mixed $result): void;

    abstract public function escape(ConnectionWrapper $connectionWrapper, string $string): string;

    /**
     * Closes the connection
     */
    abstract public function close(ConnectionWrapper $connectionWrapper): void;

    abstract public function errors(?ConnectionWrapper $connectionWrapper = null): array;

    abstract public function getLastId(ConnectionWrapper $connectionWrapper): int|string;

    // everything below is handling the result

    abstract public static function numRows(mixed $result): int;

    abstract public function affectedRows(ConnectionWrapper $connectionWrapper, mixed $result): int|false;

    abstract public function getTableColumnsInfo(ConnectionWrapper $connectionWrapper, string $database, string $table): array;
}