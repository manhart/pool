<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database\Driver;

use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;
use pool\classes\Database\Exception\DatabaseConnectionException;

class MySQLi extends Driver
{
    protected static int $port = 3306;
    protected static string $name = 'mysql';
    protected static string $provider = 'mysqli';
    private \mysqli $mysqli;

    private string $charset = 'utf8';

    /**
     * @param \pool\classes\Database\DataInterface $dataInterface
     * @param string $hostname
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $database
     * @param mixed ...$options
     * @return \mysqli
     */
    public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options): \mysqli
    {
        $this->mysqli = mysqli_init();
        try {
            $this->mysqli->real_connect($hostname, $username, $password, $database, $port);
            $this->setCharset($options['charset'] ?? $this->charset);
        } catch(\mysqli_sql_exception $e) {
            throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
        }
        return $this->mysqli;
    }

    public function setCharset(string $charset): static
    {
        $this->mysqli->set_charset($charset);
        return $this;
    }

    public function close(): void
    {
        $this->mysqli->close();
    }
}