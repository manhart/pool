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

    private int $defaultExpiration = 0;

    private function __construct(string $servers)
    {
        parent::__construct();
        if (ini_get("session.save_handler") === 'memcached') {
            $servers = $servers ?: ini_get("session.save_path");
        }
        // Configure default servers if no servers have been configured
        $servers = $servers ?: "localhost:11211";
        $servers = explode(",", $servers);
        $servers = array_map(curry(explode(...), ":"), $servers);
        $this->addServers($servers);
        if (!$this->getVersion()) {
            throw new \MemcachedException($this->getResultMessage() . $this->getLastErrorMessage());
        }
    }

    public static function getInstance(string $servers = ''): self
    {
        return self::$instance ??= new static($servers);
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
