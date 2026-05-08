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
use pool\classes\Core\RecordSet;
use pool\classes\Database\Commands;
use pool\classes\Database\DAO\Informix_DAO;
use pool\classes\Database\Operator;
use pool\classes\Exception\DAOException;

class InformixDAOTest extends TestCase
{
    public function testGetMultipleWithoutConditionsUsesUndelimitedInformixIdentifiersByDefault(): void
    {
        $sql = $this->sqlFrom(TestInformixUserDao::create(throws: true)->setColumns('email')->getMultiple());

        $this->assertSame(
            'SELECT User.email FROM User',
            $sql,
        );
    }

    public function testGetMultipleCanUseDelimitedInformixIdentifiers(): void
    {
        $sql = $this->sqlFrom(TestInformixDelimitedUserDao::create(throws: true)->setColumns('email')->getMultiple());

        $this->assertSame(
            'SELECT "User"."email" FROM "User"',
            $sql,
        );
    }

    public function testGetMultipleWithLimitUsesSkipFirstInSelectClause(): void
    {
        $sql = $this->sqlFrom(
            TestInformixUserDao::create(throws: true)
                ->setColumns('email', 'deleted')
                ->getMultiple(
                    filter: [
                        ['email', Operator::like, '%@mail.local'],
                        ['deleted', Operator::equal, false],
                    ],
                    sorting: ['email' => 'ASC'],
                    limit: [5, 10],
                ),
        );

        // language=genericsql
        $this->assertSame(
            'SELECT SKIP 5 FIRST 10 User.email, User.deleted FROM User WHERE email like \'%@mail.local\' and deleted = false ORDER BY email ASC',
            $sql,
        );
    }

    public function testGetMultipleWithSchemaOmitsDatabaseName(): void
    {
        $sql = $this->sqlFrom(
            TestInformixSchemaDao::create(throws: true)
                ->setColumns('order_id')
                ->getMultiple(id: 'ORD123', key: 'order_id'),
        );

        $this->assertSame(
            'SELECT Orders.order_id FROM testSchema.Orders WHERE order_id=\'ORD123\'',
            $sql,
        );
    }

    public function testInsertBuildsInformixInsert(): void
    {
        $sql = $this->sqlFrom(
            TestInformixUserDao::create(throws: true)->insert([
                'email' => 'user@mail.local',
                'deleted' => false,
            ]),
        );

        // language=genericsql
        $this->assertSame(
            'INSERT INTO User (email,deleted) VALUES (\'user@mail.local\',false)',
            $sql,
        );
    }

    public function testUpdateWithResetCommandUsesInformixDefaultExpression(): void
    {
        $sql = $this->sqlFrom(
            TestInformixUserDao::create(throws: true)->update([
                'idUser' => 5,
                'deleted' => Commands::Reset,
            ]),
        );

        $this->assertSame(
            'UPDATE User SET deleted=DEFAULT WHERE idUser=5',
            $sql,
        );
    }

    public function testDeleteBuildsInformixDelete(): void
    {
        $sql = $this->sqlFrom(
            TestInformixUserDao::create(throws: true)->delete(5),
        );

        $this->assertSame(
            'DELETE FROM User WHERE idUser=5',
            $sql,
        );
    }

    public function testInsertModeThrowsForUnsupportedMySqlMode(): void
    {
        $this->expectException(DAOException::class);

        TestInformixUserDao::create(throws: true)->insert([
            'email' => 'user@mail.local',
        ], 'ignore');
    }

    public function testUpsertThrowsUnsupportedException(): void
    {
        $this->expectException(DAOException::class);

        TestInformixUserDao::create(throws: true)->upsert([
            'idUser' => 5,
            'email' => 'user@mail.local',
        ]);
    }

    private function sqlFrom(RecordSet $recordSet): string
    {
        $sql = $recordSet->getRaw()[0] ?? '';
        return preg_replace('/\s+/', ' ', trim((string)$sql)) ?? '';
    }
}

class SqlCapturingInformixDao extends Informix_DAO
{
    public function escapeSQL(mixed $value): string
    {
        return (string)$value;
    }

    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        return new RecordSet([trim($sql)]);
    }
}

class TestInformixUserDao extends SqlCapturingInformixDao
{
    protected static ?string $databaseName = 'testDB';

    protected static ?string $tableName = 'User';

    protected array $pk = [
        'idUser',
    ];

    protected array $columns = [
        'idUser',
        'email',
        'deleted',
    ];
}

class TestInformixDelimitedUserDao extends TestInformixUserDao
{
    protected static bool $delimitedIdentifiers = true;
}

class TestInformixSchemaDao extends SqlCapturingInformixDao
{
    protected static ?string $databaseName = 'testDB';

    protected static ?string $schemaName = 'testSchema';

    protected static ?string $tableName = 'Orders';

    protected array $pk = [
        'order_id',
    ];

    protected array $columns = [
        'order_id',
    ];
}
