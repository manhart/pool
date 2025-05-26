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

use mysqli_result;
use mysqli_sql_exception;
use pool\classes\Database\Connection;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;
use pool\classes\Database\Exception\DatabaseConnectionException;

use function mysqli_connect_errno;
use function mysqli_connect_error;
use function mysqli_init;
use function parse_url;

use const MYSQLI_STORE_RESULT;
use const MYSQLI_USE_RESULT;
use const PHP_URL_SCHEME;

class MySQLi extends Driver
{
    /**
     * @var int Default port
     */
    protected static int $port = 3306;

    /**
     * @var string Driver name
     */
    protected static string $name = 'mysql';

    /**
     * @var string Extension name
     */
    protected static string $provider = 'mysqli';

    /**
     * @var \mysqli MySQLi connection
     */
    private \mysqli $mysqli;

    /**
     * @var string Default charset
     */
    private string $charset = 'utf8';

    public function connect(
        DataInterface $dataInterface,
        string $hostname,
        int $port = 0,
        string $username = '',
        string $password = '',
        string $database = '',
        ...$options
    ): Connection {
        $this->mysqli = mysqli_init();
        $scheme = parse_url($hostname, PHP_URL_SCHEME);
        $connectionParameters = parse_url(($scheme ? '' : '//').$hostname);
        try {
            $this->mysqli->real_connect(
                $connectionParameters["host"] ?? null,
                $connectionParameters["user"] ?? $username,
                $connectionParameters['pass'] ?? $password,
                $database,
                $connectionParameters["port"] ?? $port,
                $connectionParameters["path"] ?? null,
            );
            $this->setCharset($options['charset'] ?? $this->charset);
        } catch (mysqli_sql_exception $e) {
            throw new DatabaseConnectionException($e->getMessage(), $e->getCode(), $e);
        }
        return new Connection($this->mysqli, $this);
    }

    /**
     * Sets the charset for the connection
     */
    public function setCharset(string $charset): static
    {
        $this->mysqli->set_charset($charset);
        return $this;
    }

    /**
     * Closes the connection
     */
    public function close(Connection $connection): void
    {
        $connection->getConnection()->close();
    }

    /**
     * Returns a list of errors from the last command executed
     */
    public function errors(?Connection $connection = null): array
    {
        $errors = $connection?->getConnection()->error_list ?: [];
        mysqli_connect_errno() && $errors[] = [
            'errno' => mysqli_connect_errno(),
            'error' => mysqli_connect_error(),
            'sqlstate' => '',
        ];
        return $errors;
    }

    /**
     * @param mysqli_result $result
     */
    public function numRows(mixed $result): int
    {
        return $result->num_rows;
    }

    /**
     * @param mysqli_result $result
     */
    public function hasRows(mixed $result): bool
    {
        return $result->num_rows > 0;
    }

    /**
     * Gets the number of affected rows in a previous SQL operation
     *
     * @param mysqli_result $result
     */
    public function affectedRows(Connection $connection, mixed $result): int|false
    {
        return $connection->getConnection()->affected_rows;
    }

    /**
     * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
     */
    public function escape(Connection $connection, string $string): string
    {
        return $connection->getConnection()->real_escape_string($string);
    }

    /**
     * Returns the value generated for an AUTO_INCREMENT column by the last query
     */
    public function getLastId(Connection $connection): int|string
    {
        return $connection->getConnection()->insert_id;
    }

    /**
     * Get the column info of a table
     */
    public function getTableColumnsInfo(Connection $connection, string $database, string $table): array
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
        $result = $this->query($connection, $query, result_mode: MYSQLI_USE_RESULT);
        $fieldList = $fields = $pk = [];
        while ($row = $this->fetch($result)) {
            $phpType = match ($row['DATA_TYPE']) {
                'int', 'tinyint', 'bigint', 'smallint', 'mediumint' => 'int',
                'decimal', 'double', 'float', 'number' => 'float',
                default => 'string',
            };
            if (str_starts_with($row['COLUMN_TYPE'], 'tinyint(1)')) {
                $phpType = 'bool';
            }
            $row['phpType'] = $phpType;
            $fieldList[] = $row;
            $fields[] = $row['COLUMN_NAME'];
            if ($row['COLUMN_KEY'] === 'PRI') {
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
     * Executes a query and returns the query result
     *
     * @param string $query SQL query
     * @return mixed query result
     */
    public function query(Connection $connection, string $query, ...$params): mixed
    {
        return @$connection->getConnection()->query($query, $params['result_mode'] ?? MYSQLI_STORE_RESULT);
    }

    /**
     * Fetch the next row of a result set as an associative array
     *
     * @param mysqli_result $result
     */
    public function fetch(mixed $result): array|null|false
    {
        return $result->fetch_assoc();
    }

    /**
     * Frees the memory associated with a result
     *
     * @param mysqli_result $result
     */
    public function free(mixed $result): void
    {
        $result->free();
    }

    /**
     * Turns on or off auto-committing database modifications
     */
    public function autocommit(Connection $connection, bool $enable): bool
    {
        return $connection->getConnection()->autocommit($enable);
    }

    /**
     * Starts a transaction
     */
    public function beginTransaction(Connection $connection): bool
    {
        return $connection->getConnection()->begin_transaction();
    }

    /**
     * Commits a transaction
     */
    public function commit(Connection $connection): bool
    {
        return $connection->getConnection()->commit();
    }

    /**
     * Rolls back a transaction
     */
    public function rollback(Connection $connection): bool
    {
        return $connection->getConnection()->rollback();
    }

    /**
     * Creates a savepoint
     */
    public function createSavePoint(Connection $connection, string $savepoint): bool
    {
        return $connection->getConnection()->query("SAVEPOINT $savepoint");
    }

    /**
     * Releases a savepoint
     */
    public function releaseSavePoint(Connection $connection, string $savepoint): bool
    {
        return $connection->getConnection()->query("RELEASE SAVEPOINT $savepoint");
    }

    /**
     * Rolls back a savepoint
     */
    public function rollbackToSavePoint(Connection $connection, string $savepoint): bool
    {
        return $connection->getConnection()->query("ROLLBACK TO SAVEPOINT $savepoint");
    }

    /**
     * Sets the transaction isolation level
     */
    public function setTransactionIsolationLevel(Connection $connection, string $level): bool
    {
        return $connection->getConnection()->query("SET TRANSACTION ISOLATION LEVEL $level");
    }

    /**
     * Returns the transaction isolation level
     */
    public function getTransactionIsolationLevel(Connection $connection): string
    {
        $result = $connection->getConnection()->query('SELECT @@tx_isolation');
        $row = $this->fetch($result);
        $this->free($result);
        return $row['@@tx_isolation'];
    }

    /**
     * Returns the transaction state
     */
    public function inTransaction(Connection $connection): bool
    {
        $result = $connection
            ->getConnection()
            ->query('SELECT count(1) as inTransaction FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = CONNECTION_ID()');
        $row = $this->fetch($result);
        $this->free($result);
        return (int)$row['inTransaction'] > 0;
    }

    /**
     * Returns the version of the MySQL server as an integer
     */
    public function getServerVersion(Connection $connection): string
    {
        return $connection->getConnection()->server_version;
    }

    /**
     * Returns the version of the MySQL server as a string
     */
    public function getServerInfo(Connection $connection): string
    {
        return $connection->getConnection()->server_info;
    }

    /**
     * Returns a string representing the type of connection used
     */
    public function getHostInfo(Connection $connection): string
    {
        return $connection->getConnection()->host_info;
    }

    /**
     * Returns the version of the client library as a string
     */
    public function getClientInfo(Connection $connection): string
    {
        return $connection->getConnection()->client_info;
    }
}
