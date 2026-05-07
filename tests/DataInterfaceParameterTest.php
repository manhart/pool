<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Database\Connection;
use pool\classes\Database\DAO;
use pool\classes\Database\DAO\Informix_DAO;
use pool\classes\Database\DAO\MSSQL_DAO;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Driver;

class DataInterfaceParameterTest extends TestCase
{
    public function testQueryWithoutParamsPassesEmptyParams(): void
    {
        $driver = new CapturingDriver();
        $interface = DataInterface::createDataInterface([
            'host' => 'fake',
            'database' => ['pool_test_params_empty' => 'fake_db'],
            'auth' => 'pool_test_no_auth',
        ], $driver);

        try {
            DataInterface::query('SELECT 1', 'pool_test_params_empty');

            $this->assertSame([], $driver->queries[0]['params']);
        }
        finally {
            $interface->unregister();
            $interface->close();
        }
    }

    public function testQueryWithParamsPassesStatementParams(): void
    {
        $driver = new CapturingDriver();
        $interface = DataInterface::createDataInterface([
            'host' => 'fake',
            'database' => ['pool_test_params_values' => 'fake_db'],
            'auth' => 'pool_test_no_auth',
        ], $driver);

        try {
            DataInterface::query('SELECT * FROM test WHERE id = ? AND name = ?', 'pool_test_params_values', [5, 'name']);

            $this->assertSame([5, 'name'], $driver->queries[0]['params']);
        }
        finally {
            $interface->unregister();
            $interface->close();
        }
    }

    public function testCreateDaoUsesInformixFallbackForInformixDriver(): void
    {
        $driver = new CapturingInformixDriver();
        $interface = DataInterface::createDataInterface([
            'host' => 'fake',
            'database' => ['pool_test_informix_dao' => 'fake_db'],
            'auth' => 'pool_test_no_auth',
        ], $driver);

        try {
            $dao = DAO::createDAO('RemoteTable', 'pool_test_informix_dao');

            $this->assertInstanceOf(Informix_DAO::class, $dao);
        }
        finally {
            $interface->unregister();
            $interface->close();
        }
    }

    public function testCreateDaoUsesMssqlFallbackForMssqlDriver(): void
    {
        $driver = new CapturingMssqlDriver();
        $interface = DataInterface::createDataInterface([
            'host' => 'fake',
            'database' => ['pool_test_mssql_dao' => 'fake_db'],
            'auth' => 'pool_test_no_auth',
        ], $driver);

        try {
            $dao = DAO::createDAO('RemoteTable', 'pool_test_mssql_dao');

            $this->assertInstanceOf(MSSQL_DAO::class, $dao);
        }
        finally {
            $interface->unregister();
            $interface->close();
        }
    }
}

class CapturingDriver extends Driver
{
    protected static int $port = 0;

    protected static string $name = 'capture';

    protected static string $provider = '';

    public array $queries = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function numRows(mixed $result): int|false
    {
        return 0;
    }

    public function hasRows(mixed $result): bool
    {
        return false;
    }

    public function setCharset(string $charset): static
    {
        return $this;
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
        return new Connection((object)['host' => $hostname, 'database' => $database], $this);
    }

    public function query(Connection $connection, string $query, array $statementParams = []): mixed
    {
        $this->queries[] = [
            'query' => $query,
            'params' => $statementParams,
        ];
        return (object)['query' => $query];
    }

    public function fetch(mixed $result): array|null|false
    {
        return false;
    }

    public function free(mixed $result): void
    {
    }

    public function escape(Connection $connection, string $string): string
    {
        return $string;
    }

    public function close(Connection $connection): void
    {
    }

    public function errors(?Connection $connection = null): array
    {
        return [];
    }

    public function getLastId(Connection $connection): int|string
    {
        return 0;
    }

    public function affectedRows(Connection $connection, mixed $result): int|false
    {
        return 0;
    }

    public function getTableColumnsInfo(Connection $connection, string $database, string $table): array
    {
        return [[], [], []];
    }

    public function setTransactionIsolationLevel(Connection $connection, string $level): bool
    {
        return true;
    }

    public function getTransactionIsolationLevel(Connection $connection): string
    {
        return '';
    }

    public function beginTransaction(Connection $connection): bool
    {
        return true;
    }

    public function commit(Connection $connection): bool
    {
        return true;
    }

    public function rollback(Connection $connection): bool
    {
        return true;
    }

    public function autocommit(Connection $connection, bool $enable): bool
    {
        return true;
    }

    public function inTransaction(Connection $connection): bool
    {
        return false;
    }

    public function createSavePoint(Connection $connection, string $savepoint): bool
    {
        return true;
    }

    public function rollbackToSavePoint(Connection $connection, string $savepoint): bool
    {
        return true;
    }

    public function releaseSavePoint(Connection $connection, string $savepoint): bool
    {
        return true;
    }

    public function getServerVersion(Connection $connection): string
    {
        return '';
    }
}

class CapturingInformixDriver extends CapturingDriver
{
    protected static string $name = 'informix';

    public static function getDefaultDAOClass(): string
    {
        return Informix_DAO::class;
    }
}

class CapturingMssqlDriver extends CapturingDriver
{
    protected static string $name = 'mssql';

    public static function getDefaultDAOClass(): string
    {
        return MSSQL_DAO::class;
    }
}
