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

use pool\classes\Database\Connection;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;
use pool\classes\Database\Exception\DatabaseConnectionException;
use function print_r;
use function sqlsrv_close;
use function sqlsrv_connect;
use function sqlsrv_errors;
use function sqlsrv_fetch_array;
use function sqlsrv_free_stmt;
use function sqlsrv_num_rows;
use function sqlsrv_query;
use function sqlsrv_rows_affected;

class MSSQL extends Driver
{
    /**
     * @var int Default port
     */
    protected static int $port = 1433;

    /**
     * @var string Driver name
     */
    protected static string $name = 'mssql';

    /**
     * @var string Extension name
     */
    protected static string $provider = 'sqlsrv';

    /**
     * @var resource SQLSRV connection
     */
    private $sqlsrv;

    /**
     * @var string Default charset
     */
    private string $charset = 'UTF-8';

    /**
     * @param DataInterface $dataInterface
     * @param string $hostname
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string $database
     * @param mixed ...$options
     * @return resource
     */
    public function connect(DataInterface $dataInterface, string $hostname, int $port = 0, string $username = '', string $password = '',
        string $database = '', ...$options): Connection
    {
        $connection_info = [
            'Database' => $database,
            'UID' => $username,
            'PWD' => $password,
            'CharacterSet' => $this->charset = $options['charset'] ?? $this->charset,
        ];
        if(($resource = sqlsrv_connect($hostname, $connection_info)) === false) {
            throw new DatabaseConnectionException(print_r(sqlsrv_errors(), true));
        }
        return new Connection($resource, $this);
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
    }

    public function close(Connection $connection): void
    {
        sqlsrv_close($connection->getConnection());
    }

    public function errors(?Connection $connection = null): array
    {
        return sqlsrv_errors() ?? [];
    }

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     */
    public function getLastId(Connection $connection): int|string
    {
        $stmt = $this->query($connection, 'SELECT SCOPE_IDENTITY() AS last_id');
        if(!$stmt) {
            return 0;
        }
        $last_id = $this->fetch($stmt)['last_id'] ?: 0;
        $this->free($stmt);
        return $last_id;
    }

    /**
     * Executes a query and returns the query result
     *
     * @param Connection $connection
     * @param string $query SQL query
     * @param ...$params
     * @return mixed query result
     */
    public function query(Connection $connection, string $query, ...$params): mixed
    {
        return sqlsrv_query($connection->getConnection(), $query, $params);
    }

    /**
     * Fetch the next row of a result set as an associative array
     */
    public function fetch(mixed $result): array|null|false
    {
        return sqlsrv_fetch_array($result, \SQLSRV_FETCH_ASSOC);
    }

    /**
     * Frees the memory associated with a result
     */
    public function free(mixed $result): void
    {
        sqlsrv_free_stmt($result);
    }

    /**
     * Gets the number of rows in the result set
     *
     * @param resource $result
     */
    public function numRows(mixed $result): int
    {
        return sqlsrv_num_rows($result);
    }

    /**
     * Indicates whether a specified statement has rows
     *
     * @param resource $result
     */
    public function hasRows(mixed $result): bool
    {
        return \sqlsrv_has_rows($result);
    }

    /**
     * Gets the number of affected rows in a previous SQL operation
     *
     * @param Connection $connection
     * @param resource $result
     * @return int|false
     */
    public function affectedRows(Connection $connection, mixed $result): int|false
    {
        return sqlsrv_rows_affected($result);
    }

    /**
     * Escaping special characters not available for MSSQL
     */
    public function escape(Connection $connection, string $string): string
    {
        return $string;
    }

    /**
     * Get the columns info of a table
     */
    public function getTableColumnsInfo(Connection $connection, string $database, string $table): array
    {
        /** @noinspection SqlResolve */
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
        $result = $this->query($connection, $query);
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
            if($row['COLUMN_KEY'] === 'PRI') {
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

    /**
     * Turns on or off auto-committing database modifications
     */
    public function autocommit(Connection $connection, bool $enable): bool
    {
        return true;
    }

    /**
     * Starts a transaction
     */
    public function beginTransaction(Connection $connection): bool
    {
        return \sqlsrv_begin_transaction($connection->getConnection());
    }

    /**
     * Commits a transaction
     */
    public function commit(Connection $connection): bool
    {
        return \sqlsrv_commit($connection->getConnection());
    }

    /**
     * Rolls back a transaction
     */
    public function rollback(Connection $connection): bool
    {
        return \sqlsrv_rollback($connection->getConnection());
    }

    /**
     * Creates a savepoint
     */
    public function createSavePoint(Connection $connection, string $savepoint): bool
    {
        return \is_resource($connection->query("SAVE TRANSACTION $savepoint"));
    }

    /**
     * Releases a savepoint
     */
    public function releaseSavePoint(Connection $connection, string $savepoint): bool
    {
        return \is_resource($connection->rollbackToSavePoint($connection, $savepoint));
    }

    /**
     * Rolls back a savepoint
     */
    public function rollbackToSavePoint(Connection $connection, string $savepoint): bool
    {
        return \is_resource($connection->query("ROLLBACK TRANSACTION $savepoint"));
    }

    /**
     * Sets the transaction isolation level
     */
    public function setTransactionIsolationLevel(Connection $connection, string $level): bool
    {
        return \is_resource($connection->query("SET TRANSACTION ISOLATION LEVEL $level"));
    }

    /**
     * Returns the transaction isolation level
     */
    public function getTransactionIsolationLevel(Connection $connection): string
    {
        // needs GRANT VIEW SERVER STATE TO YourUsername;
        /** @noinspection SqlResolve **/
$result = $connection->query('SELECT CASE transaction_isolation_level
            WHEN 0 THEN \'Unspecified\'
            WHEN 1 THEN \'ReadUncommitted\'
            WHEN 2 THEN \'ReadCommitted\'
            WHEN 3 THEN \'RepeatableRead\'
            WHEN 4 THEN \'Serializable\'
            WHEN 5 THEN \'Snapshot\'
        END AS TRANSACTION_ISOLATION_LEVEL
        FROM sys.dm_exec_sessions
        WHERE session_id = @@SPID');
        $row = $this->fetch($result);
        $this->free($result);
        return $row['TRANSACTION_ISOLATION_LEVEL'];
    }

    /**
     * Returns the transaction state
     */
    public function inTransaction(Connection $connection): bool
    {
        $result = $connection->query('SELECT @@trancount');
        $row = $this->fetch($result);
        $this->free($result);
        return (int)$row['@@trancount'] > 0;
    }

    /**
     * Returns the version of the server
     */
    public function getServerVersion(Connection $connection): string
    {
        $result = $connection->query('SELECT @@version');
        $row = $this->fetch($result);
        $this->free($result);
        return $row['@@version'];
    }

    /**
     * Returns information about the server
     */
    public function getServerInfo(Connection $connection): array
    {
        return \sqlsrv_server_info($connection->getConnection());
    }

    /**
     * Returns information about the client and specified connection
     */
    public function getClientInfo(Connection $connection): array
    {
        return \sqlsrv_client_info($connection->getConnection());
    }
}