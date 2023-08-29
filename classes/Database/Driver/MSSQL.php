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

class MSSQL extends Driver
{
    protected static int $port = 1433;
    protected static string $name = 'mssql';
    protected static string $provider = 'sqlsrv';
    /** @var resource */
    private $sqlsrv;

    /**
     * @var string Default charset
     */
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
        string $database = '', ...$options): ConnectionWrapper
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
        return new ConnectionWrapper($resource, $this);
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    public function close(ConnectionWrapper $connectionWrapper): void
    {
        sqlsrv_close($connectionWrapper->getConnection());
    }

    public function query(ConnectionWrapper $connectionWrapper, string $query, ...$params): mixed
    {
        return sqlsrv_query($connectionWrapper->getConnection(), $query, $params);
    }

    public static function numRows(mixed $result): int
    {
        return sqlsrv_num_rows($result);
    }

    public function errors(?ConnectionWrapper $connectionWrapper = null): array
    {
        return sqlsrv_errors() ?? [];
    }

    public function affectedRows(ConnectionWrapper $connectionWrapper, mixed $result): int|false
    {
        return sqlsrv_rows_affected($result);
    }

    public function fetch(mixed $result): array|null|false
    {
        return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    public function free(mixed $result): void
    {
        sqlsrv_free_stmt($result);
    }

    public function escape(ConnectionWrapper $connectionWrapper, string $string): string
    {
        return $string;
    }

    public function getLastId(ConnectionWrapper $connectionWrapper): int|string
    {
        $stmt = $this->query($connectionWrapper, 'SELECT SCOPE_IDENTITY() AS last_id');
        if(!$stmt) return 0;
        $last_id = $this->fetch($stmt)['last_id'] ?: 0;
        $this->free($stmt);
        return $last_id;
    }

    public function getTableColumnsInfo(ConnectionWrapper $connectionWrapper, string $database, string $table): array
    {
        $query = <<<SQL
SELECT
    c.name AS COLUMN_NAME,
    t.Name AS DATA_TYPE,
    c.max_length AS COLUMN_LENGTH,
    CASE
        WHEN ic.column_id IS NOT NULL THEN 'PRI'
        ELSE ''
    END AS COLUMN_KEY
FROM
    sys.columns c
INNER JOIN
    sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN
    sys.index_columns ic ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE
    c.object_id = OBJECT_ID('$database.dbo.$table')
SQL;
        $result = $this->query($connectionWrapper, $query);
        $fieldList = $fields = $pk = [];

        while($row = $this->fetch($result)) {
            $phpType = match ($row['DATA_TYPE']) {
                'int', 'tinyint', 'bigint', 'smallint' => 'int',
                'decimal', 'float', 'real' => 'float',
                default => 'string',
            };
            $row['phpType'] = $phpType;
            $fieldList[] = $row;
            $fields[] = $row['COLUMN_NAME'];
            if ($row['COLUMN_KEY'] == 'PRI') {
                $pk[] = $row['COLUMN_NAME'];
            }
        }

        $this->free($result);
        return [
            $fieldList,
            $fields,
            $pk
        ];
    }
}