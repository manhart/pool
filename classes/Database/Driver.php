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
     * @throws \Exception
     */
    protected function __construct() {
        if(static::$provider && !extension_loaded(static::$provider)) {
            throw new Exception('Provider ' . static::$provider . ' not loaded');
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
        string $database = '', ...$options);

    /**
     * Closes the connection
     */
    abstract public function close();
}