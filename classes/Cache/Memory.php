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

    private bool $initialized;

    private int $defaultExpiration = 0;

    private function __construct(string $servers)
    {
        parent::__construct('pool');
        $this->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
        if (ini_get('session.save_handler') === 'memcached') {
            $servers = $servers ?: ini_get('session.save_path');
        }

        if ($servers !== '' && $servers !== null) {
            $this->parseAndAddServers($servers);
        }
    }

    private function parseAndAddServers(string $servers): void
    {
        $currentServers = $this->getServerList();
        $existingServers = array_flip(
            array_map(fn($server) => "{$server['host']}:{$server['port']}", $currentServers)
        );

        $serversToAdd = [];
        $serverList = explode(',', $servers);

        foreach ($serverList as $serverString) {
            $serverString = trim($serverString); // Handle whitespace

            if ($serverString !== '' && !isset($existingServers[$serverString])) {
                $parts = explode(':', $serverString, 2);
                if (count($parts) === 2 && $parts[1] !== '') {
                    $serversToAdd[] = [$parts[0], (int)$parts[1]];
                }
            }
        }

        if ($serversToAdd !== []) {
            $this->addServers($serversToAdd);
        }
    }
    public function isAvailable(): bool
    {
        if (isset($this->initialized)) {
            return $this->initialized;
        }
        $versions = $this->getVersion();
        $this->initialized ??= $versions && count(array_filter($versions, fn($v) => $v !== false)) > 0;
        return $this->initialized;
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

    public function setValue(string $key, $value, ?int $expiration = null): bool
    {
        return $this->set($key, $value, $expiration ?? $this->defaultExpiration);
    }

    public function getValue(string $key, ?callable $cache_cb = null, int $flags = 0): mixed
    {
        return ($value = $this->get($key, $cache_cb, $flags)) === false && !$this->lastKeyExists() ? null : $value;
    }

    public function keyExists(string $key): bool
    {
        $this->get($key);// Just to set the result code
        return  $this->lastKeyExists();
    }

    public function lastKeyExists(): bool
    {
        return $this->getResultCode() !== Memcached::RES_NOTFOUND;
    }
}
