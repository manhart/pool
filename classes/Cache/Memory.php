<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Cache;

use Memcached;

class Memory extends Memcached
{
    private static ?Memory $instance = null;

    private static bool $serverConfigured = false;

    private int $defaultExpiration = 0;

    private function __construct()
    {
        parent::__construct();
    }

    public static function getInstance(array $servers = []): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
            if ($servers) {
                self::configureServers($servers);
            } elseif (!self::$serverConfigured) {
                // Configure default servers if no servers have been committed
                // and no servers have been configured yet
                self::configureServers([['host' => 'localhost', 'port' => 11211]]);
            }
        }
        return self::$instance;
    }

    private static function configureServers(array $servers): void
    {
        foreach ($servers as $server) {
            self::$instance->addServer($server['host'], $server['port']);
        }
        self::$serverConfigured = true;
    }

    public static function hasInstance(): bool
    {
        return static::$instance !== null;
    }

    public function setDefaultExpiration(int $expiration): void
    {
        $this->defaultExpiration = $expiration;
    }

    public function setValue(string $key, $value, int $expiration = null): bool
    {
        return $this->set($key, $value, $expiration ?? $this->defaultExpiration);
    }

    public function getValue(string $key, callable $cache_cb = null, int $flags = 0): mixed
    {
        return ($value = $this->get($key, $cache_cb, $flags)) === false && !$this->lastKeyExists() ? null : $value;
    }

    public function keyExists(string $key): bool
    {
        return $this->get($key) === false && $this->lastKeyExists();
    }

    public function lastKeyExists(): bool
    {
        return $this->getResultCode() !== Memcached::RES_NOTFOUND;
    }
}