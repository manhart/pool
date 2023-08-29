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

use pool\classes\Database\ConnectionWrapper;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;
use pool\classes\Database\Exception\DatabaseConnectionException;

class MySQLi extends Driver
{
    protected static int $port = 3306;
    protected static string $name = 'mysql';
    protected static string $provider = 'mysqli';
    private \mysqli $mysqli;

    /**
     * @var string Default charset
     */
    private string $charset = 'utf8';

    /**
     * @param \pool\classes\Database\DataInterface $dataInterface
     * @param string $hostname
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $database
     * @param mixed ...$options
     * @return ConnectionWrapper
     */
    public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options): ConnectionWrapper
    {
        $this->mysqli = mysqli_init();
        try {
            $this->mysqli->real_connect($hostname, $username, $password, $database, $port);
            $this->setCharset($options['charset'] ?? $this->charset);
        } catch(\mysqli_sql_exception $e) {
            throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
        }
        return new ConnectionWrapper($this->mysqli, $this);
    }

    public function setCharset(string $charset): static
    {
        $this->mysqli->set_charset($charset);
        return $this;
    }

    public function close(ConnectionWrapper $connectionWrapper): void
    {
        $connectionWrapper->getConnection()->close();
    }

    public function query(ConnectionWrapper $connectionWrapper, string $query, ...$params): mixed
    {
        return @$connectionWrapper->getConnection()->query($query, $params['result_mode'] ?? MYSQLI_STORE_RESULT);
    }

    public function fetch(mixed $result): array|null|false
    {
        return $result->fetch_assoc();
    }

    /**
     * @param \mysqli_result $result
     * @return int
     */
    public static function numRows(mixed $result): int
    {
        return $result->num_rows;
    }

    public function errors(?ConnectionWrapper $connectionWrapper = null): array
    {
        $errors = $connectionWrapper?->getConnection()->error_list ?: [];
        mysqli_connect_errno() && $errors[] = [
            'errno' => mysqli_connect_errno(),
            'error' => mysqli_connect_error(),
            'sqlstate' => ''
        ];
        return $errors;
    }

    public function affectedRows(ConnectionWrapper $connectionWrapper, mixed $result): int|false
    {
        return $connectionWrapper->getConnection()->affected_rows;
    }

    public function free(mixed $result): void
    {
        $result->free();
    }

    public function escape(ConnectionWrapper $connectionWrapper, string $string): string
    {
        return $connectionWrapper->getConnection()->real_escape_string($string);
    }

    public function getLastId(ConnectionWrapper $connectionWrapper): int|string
    {
        return $connectionWrapper->getConnection()->insert_id;
    }

    public function getTableColumnsInfo(ConnectionWrapper $connectionWrapper, string $database, string $table): array
    {
        $query = <<<SQL
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = '$database'
  AND TABLE_NAME = '$table'
SQL;
        $result = $this->query($connectionWrapper, $query, result_mode: MYSQLI_USE_RESULT);
        $fieldList = $fields = $pk = [];
        while($row = $this->fetch($result)) {
            $phpType = match ($row['DATA_TYPE']) {
                'int', 'tinyint', 'bigint', 'smallint', 'mediumint' => 'int',
                'decimal', 'double', 'float', 'number' => 'float',
                default => 'string',
            };
            if(str_starts_with($row['COLUMN_TYPE'], 'tinyint(1)')) {
                $phpType = 'bool';
            }
            $row['phpType'] = $phpType;
            $fieldList[] = $row;
            $fields[] = $row['COLUMN_NAME'];
            if($row['COLUMN_KEY'] == 'PRI') {
                $pk[] = $row['COLUMN_NAME'];
            }
        }
        $this->free($result);
        return [
            $fieldList,
            $fields,
            $pk,
        ];
    }
}