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

class MSSQL extends Driver
{
    protected static int $port = 1433;
    protected static string $name = 'mssql';
    protected static string $provider = 'sqlsrv';
    /** @var resource */
    private $sqlsrv;

    private string $charset = 'UTF-8';

    /**
     * @param \pool\classes\Database\DataInterface $dataInterface
     * @param string $hostname
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $database
     * @param mixed ...$options
     * @return resource
     */
    public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options)
    {
        $connection_info = [
            'Database' => $database,
            'UID' => $username,
            'PWD' => $password,
            'CharacterSet' => $this->charset = $options['charset'] ?? $this->charset
        ];
        if(($resource = sqlsrv_connect($hostname, $connection_info)) === false) {
            throw new DatabaseConnectionException(print_r( sqlsrv_errors(), true));
        }
        return $this->sqlsrv = $resource;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    public function close()
    {
        sqlsrv_close($this->sqlsrv);
    }
}