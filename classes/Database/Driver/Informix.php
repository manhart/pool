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
use pool\classes\Database\DAO\Informix_DAO;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;
use pool\classes\Database\Exception\DatabaseConnectionException;

use WeakMap;

use function array_map;
use function constant;
use function defined;
use function explode;
use function implode;
use function odbc_autocommit;
use function odbc_close;
use function odbc_commit;
use function odbc_connect;
use function odbc_error;
use function odbc_errormsg;
use function odbc_exec;
use function odbc_execute;
use function odbc_fetch_array;
use function odbc_free_result;
use function odbc_num_rows;
use function odbc_prepare;
use function odbc_rollback;
use function range;
use function str_contains;
use function str_replace;

class Informix extends Driver
{
    /**
     * ODBC DSNs carry their own port configuration.
     */
    protected static int $port = 0;

    protected static string $name = 'informix';

    protected static string $provider = 'odbc';

    public static function getDefaultDAOClass(): string
    {
        return Informix_DAO::class;
    }

    /** @var WeakMap<Connection, bool> */
    private WeakMap $transactionState;

    /** @var WeakMap<Connection, true> */
    private WeakMap $transactionOwnership;

    /** @var WeakMap<Connection, string> */
    private WeakMap $transactionIsolation;

    protected function __construct()
    {
        parent::__construct();
        $this->transactionState = new WeakMap();
        $this->transactionOwnership = new WeakMap();
        $this->transactionIsolation = new WeakMap();
    }

    public function connect(
        DataInterface $dataInterface,
        string $hostname,
        int $port = 0,
        string $username = '',
        string $password = '',
        string $database = '',
        ...$options
    ): Connection {
        $cursorType = $options['cursorType'] ?? (defined('SQL_CUR_USE_DRIVER') ? constant('SQL_CUR_USE_DRIVER') : 2);
        $connection = @odbc_connect($hostname, $username, $password, $cursorType);
        if (!$connection) {
            $error = $this->errors()[0] ?? ['errno' => 0, 'error' => 'Unknown'];
            throw new DatabaseConnectionException($error['errno'].': '.$error['error']);
        }

        return new Connection($connection, $this);
    }

    public function setCharset(string $charset): static
    {
        return $this;
    }

    public function close(Connection $connection): void
    {
        odbc_close($connection->getConnection());
    }

    public function errors(?Connection $connection = null): array
    {
        $resource = $connection?->getConnection();
        $error = $resource ? odbc_error($resource) : odbc_error();
        $message = $resource ? odbc_errormsg($resource) : odbc_errormsg();

        if (!$error && !$message) {
            return [];
        }

        return [
            [
                'errno' => $error ?: 0,
                'error' => $message ?: 'Unknown ODBC error',
                'sqlstate' => '',
            ],
        ];
    }

    /**
     * Stays at mixed to honor the abstract Driver::query contract. Narrowing here would diverge from the MySQLi/MSSQL siblings
     * and tie the file to the PHP 8.4 \Odbc\Result class symbol.
     *
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function query(Connection $connection, string $query, array $statementParams = []): mixed
    {
        if (!$statementParams) {
            return @odbc_exec($connection->getConnection(), $query);
        }

        $stmt = @odbc_prepare($connection->getConnection(), $query);
        if (!$stmt) {
            return false;
        }

        return @odbc_execute($stmt, $statementParams) ? $stmt : false;
    }

    public function fetch(mixed $result): array|null|false
    {
        return odbc_fetch_array($result);
    }

    public function free(mixed $result): void
    {
        if ($result) {
            @odbc_free_result($result);
        }
    }

    public function numRows(mixed $result): int|false
    {
        $rows = odbc_num_rows($result);
        return $rows < 0 ? false : $rows;
    }

    public function hasRows(mixed $result): bool
    {
        // Informix's ODBC driver returns -1 for SELECT — the row count is unknown
        // until the cursor is consumed. Report "yes" so callers proceed to fetch;
        // an empty cursor naturally yields an empty result set.
        return odbc_num_rows($result) !== 0;
    }

    public function affectedRows(Connection $connection, mixed $result): int|false
    {
        $rows = odbc_num_rows($result);
        return $rows < 0 ? false : $rows;
    }

    public function escape(Connection $connection, string $string): string
    {
        return str_replace("'", "''", $string);
    }

    public function getLastId(Connection $connection): int|string
    {
        /** @noinspection SqlResolve */
        $result = $this->query($connection, "SELECT DBINFO('sqlca.sqlerrd1') AS LAST_ID FROM systables WHERE tabid = 1");
        if (!$result) {
            return 0;
        }

        $row = $this->fetch($result) ?: ['LAST_ID' => 0];
        $this->free($result);
        return $row['LAST_ID'] ?: 0;
    }

    public function getTableColumnsInfo(Connection $connection, string $database, string $table): array
    {
        $owner = null;
        if (str_contains($table, '.')) {
            [$owner, $table] = explode('.', $table, 2);
        }

        $ownerCondition = $owner ? 'AND t.owner = '.$this->quoteLiteral($connection, $owner) : '';
        $pkPartChecks = implode(
            "\n                    OR ",
            array_map(static fn(int $part) => "c.colno = ABS(i.part$part)", range(1, 16)),
        );

        $query = <<<SQL
            SELECT
                c.colname AS COLUMN_NAME,
                c.coltype AS COLTYPE,
                c.collength AS COLUMN_LENGTH,
                CASE
                    WHEN $pkPartChecks THEN 'PRI'
                    ELSE ''
                END AS COLUMN_KEY
            FROM systables t
            INNER JOIN syscolumns c ON c.tabid = t.tabid
            LEFT JOIN sysconstraints sc ON sc.tabid = t.tabid AND sc.constrtype = 'P'
            LEFT JOIN sysindexes i ON i.idxname = sc.idxname AND i.owner = sc.owner
            WHERE t.tabname = {$this->quoteLiteral($connection, $table)}
              $ownerCondition
            ORDER BY c.colno
            SQL;

        $result = $this->query($connection, $query);
        if (!$result) {
            return [[], [], []];
        }

        $fieldList = $fields = $pk = [];
        while ($row = $this->fetch($result)) {
            $columnName = $row['COLUMN_NAME'];
            $colType = (int)$row['COLTYPE'];
            $baseType = $colType & 0xff;
            $dataType = $this->getInformixTypeName($baseType);

            $field = [
                'COLUMN_NAME' => $columnName,
                'DATA_TYPE' => $dataType,
                'COLUMN_TYPE' => $dataType,
                'COLUMN_LENGTH' => $row['COLUMN_LENGTH'],
                'COLUMN_KEY' => $row['COLUMN_KEY'],
                'phpType' => $this->getPhpType($baseType),
            ];
            $fieldList[] = $field;
            $fields[] = $columnName;
            if ($field['COLUMN_KEY'] === 'PRI') {
                $pk[] = $columnName;
            }
        }

        $this->free($result);
        return [
            $fieldList,
            $fields,
            $pk,
        ];
    }

    private function quoteLiteral(Connection $connection, string $value): string
    {
        return "'".$this->escape($connection, $value)."'";
    }

    private function getInformixTypeName(int $baseType): string
    {
        return match ($baseType) {
            0 => 'char',
            1 => 'smallint',
            2 => 'integer',
            3 => 'float',
            4 => 'smallfloat',
            5 => 'decimal',
            6 => 'serial',
            7 => 'date',
            8 => 'money',
            10 => 'datetime',
            11 => 'byte',
            12 => 'text',
            13 => 'varchar',
            14 => 'interval',
            15 => 'nchar',
            16 => 'nvarchar',
            17 => 'int8',
            18 => 'serial8',
            40 => 'lvarchar',
            41 => 'clob',
            42 => 'blob',
            43 => 'boolean',
            45 => 'bigint',
            52 => 'bigserial',
            default => 'unknown',
        };
    }

    private function getPhpType(int $baseType): string
    {
        return match ($baseType) {
            1, 2, 6, 17, 18, 45, 52 => 'int',
            3, 4, 5, 8 => 'float',
            43 => 'bool',
            default => 'string',
        };
    }

    public function setTransactionIsolationLevel(Connection $connection, string $level): bool
    {
        $mappedLevel = match (\strtoupper($level)) {
            'READ UNCOMMITTED' => 'DIRTY READ',
            'READ COMMITTED' => 'COMMITTED READ',
            'REPEATABLE READ', 'SERIALIZABLE' => 'REPEATABLE READ',
            default => $level,
        };
        $success = $this->executeCommand($connection, "SET ISOLATION TO $mappedLevel");
        if ($success) {
            $this->transactionIsolation[$connection] = $level;
        }
        return $success;
    }

    public function getTransactionIsolationLevel(Connection $connection): string
    {
        return $this->transactionIsolation[$connection] ?? '';
    }

    public function beginTransaction(Connection $connection): bool
    {
        if (!$this->autocommit($connection, false)) {
            return false;
        }
        $this->transactionOwnership[$connection] = true;
        return true;
    }

    public function commit(Connection $connection): bool
    {
        return $this->endTransaction($connection, odbc_commit(...));
    }

    public function rollback(Connection $connection): bool
    {
        return $this->endTransaction($connection, odbc_rollback(...));
    }

    private function endTransaction(Connection $connection, callable $odbcOperation): bool
    {
        $ownedByDriver = $this->transactionOwnership[$connection] ?? false;
        $success = $odbcOperation($connection->getConnection());
        if ($success) {
            $this->transactionState[$connection] = false;
            unset($this->transactionOwnership[$connection]);
            if ($ownedByDriver) {
                odbc_autocommit($connection->getConnection(), true);
            }
        }
        return $success;
    }

    public function autocommit(Connection $connection, bool $enable): bool
    {
        $success = odbc_autocommit($connection->getConnection(), $enable);
        if ($success) {
            $this->transactionState[$connection] = !$enable;
        }
        return $success;
    }

    public function inTransaction(Connection $connection): bool
    {
        return $this->transactionState[$connection] ?? false;
    }

    public function createSavePoint(Connection $connection, string $savepoint): bool
    {
        return $this->executeCommand($connection, "SAVEPOINT $savepoint");
    }

    public function releaseSavePoint(Connection $connection, string $savepoint): bool
    {
        return $this->executeCommand($connection, "RELEASE SAVEPOINT $savepoint");
    }

    public function rollbackToSavePoint(Connection $connection, string $savepoint): bool
    {
        return $this->executeCommand($connection, "ROLLBACK WORK TO SAVEPOINT $savepoint");
    }

    public function getServerVersion(Connection $connection): string
    {
        /** @noinspection SqlResolve */
        $result = $this->query($connection, "SELECT DBINFO('version', 'full') AS VERSION FROM systables WHERE tabid = 1");
        if (!$result) {
            return '';
        }

        $row = $this->fetch($result) ?: ['VERSION' => ''];
        $this->free($result);
        return (string)$row['VERSION'];
    }

    private function executeCommand(Connection $connection, string $sql): bool
    {
        $result = $this->query($connection, $sql);
        if (!$result) {
            return false;
        }
        $this->free($result);
        return true;
    }
}
