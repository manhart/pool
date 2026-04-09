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
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\Database\Operator;
use pool\classes\Exception\SecurityException;

class DAOTest extends TestCase
{
    public function testGetMultipleWithoutConditionsOmitsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()
                ->setColumns('emailAddress')
                ->getMultiple(),
        );

        $this->assertSame(
            'SELECT `User`.`emailAddress` FROM `testDB`.`User`',
            $sql,
        );
    }

    public function testGetMultipleWithFilterBuildsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()
                ->setColumns('emailAddress', 'deleted')
                ->getMultiple(
                    filter: [
                        ['emailAddress', Operator::like, '%@mail.local'],
                        ['deleted', Operator::equal, false],
                    ],
                    sorting: ['emailAddress' => 'ASC'],
                    limit: [0, 10],
                ),
        );

        $this->assertSame(
            "SELECT `User`.`emailAddress`, `User`.`deleted` FROM `testDB`.`User` WHERE emailAddress like '%@mail.local' and deleted = false ORDER BY emailAddress ASC LIMIT 0, 10",
            $sql,
        );
    }

    public function testGetMultipleCombinesIdAndFilterWithAnd(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()
                ->setColumns('emailAddress')
                ->getMultiple(
                    id: 5,
                    filter: [['deleted', Operator::equal, false]],
                ),
        );

        $this->assertSame(
            'SELECT `User`.`emailAddress` FROM `testDB`.`User` WHERE idUser=5 AND deleted = false',
            $sql,
        );
    }

    public function testGetMultipleWithParenthesizedFilterDoesNotInjectLeadingAnd(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()
                ->setColumns('deleted')
                ->getMultiple(filter: [
                    '(',
                    ['deleted', Operator::equal, false],
                    ')',
                ]),
        );

        $this->assertSame(
            'SELECT `User`.`deleted` FROM `testDB`.`User` WHERE ( deleted = false )',
            $sql,
        );
    }

    public function testGetCountWithoutConditionsOmitsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->getCount(),
        );

        $this->assertSame(
            'SELECT COUNT(*) AS `count` FROM `testDB`.`User`',
            $sql,
        );
    }

    public function testGetCountWithGroupedCollisionFilterBuildsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->getCount(filter: [
                '(',
                ['emailAddress', Operator::like, '%@mail.local'],
                Operator::or,
                ['deleted', Operator::equal, false],
                ') and',
                ['idUser', Operator::notEqual, 5],
            ]),
        );

        $this->assertSame(
            "SELECT COUNT(*) AS `count` FROM `testDB`.`User` WHERE ( emailAddress like '%@mail.local' or deleted = false ) and idUser != 5",
            $sql,
        );
    }

    public function testGetCountWithNestedGroupFilterBuildsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->getCount(filter: [
                [
                    [
                        ['emailAddress', Operator::equal, 'user@mail.local'],
                        ['deleted', Operator::equal, false],
                    ],
                    Operator::or,
                ],
                ['idUser', Operator::notEqual, 5],
            ]),
        );

        $this->assertSame(
            "SELECT COUNT(*) AS `count` FROM `testDB`.`User` WHERE (emailAddress = 'user@mail.local' or deleted = false) and idUser != 5",
            $sql,
        );
    }

    public function testGetMultipleWithHavingBuildsHavingClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()
                ->setColumns('deleted')
                ->getMultiple(
                    groupBy: ['deleted' => 'ASC'],
                    having: [
                        '(',
                        ['deleted', Operator::equal, false],
                        ')',
                    ],
                ),
        );

        $this->assertSame(
            'SELECT `User`.`deleted` FROM `testDB`.`User` GROUP BY deleted ASC HAVING ( deleted = false )',
            $sql,
        );
    }

    public function testDeleteMultipleWithoutFilterOmitsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->deleteMultiple(),
        );

        $this->assertSame(
            'DELETE FROM `testDB`.`User`',
            $sql,
        );
    }

    public function testDeleteBuildsWhereClauseFromPrimaryKey(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->delete(5),
        );

        $this->assertSame(
            'DELETE FROM `testDB`.`User` WHERE idUser=5',
            $sql,
        );
    }

    public function testUpdateBuildsWhereClauseFromPrimaryKey(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->update([
                'idUser' => 5,
                'deleted' => true,
            ]),
        );

        $this->assertSame(
            'UPDATE `testDB`.`User` SET `deleted`=true WHERE idUser=5',
            $sql,
        );
    }

    public function testUpdateMultipleWithoutFilterThrowsSecurityException(): void
    {
        $this->expectException(SecurityException::class);

        TestUserDao::create()->updateMultiple(
            data: ['deleted' => true],
            filter_rules: [],
        );
    }

    public function testUpdateMultipleWithFilterBuildsWhereClause(): void
    {
        $sql = $this->sqlFrom(
            TestUserDao::create()->updateMultiple(
                data: ['deleted' => true],
                filter_rules: [['idUser', Operator::notEqual, 5]],
            ),
        );

        $this->assertSame(
            'UPDATE `testDB`.`User` SET `deleted`=true WHERE idUser != 5',
            $sql,
        );
    }

    private function sqlFrom(RecordSet $recordSet): string
    {
        $sql = $recordSet->getRaw()[0] ?? '';
        return preg_replace('/\s+/', ' ', trim((string)$sql)) ?? '';
    }
}

class SqlCapturingMySqlDao extends MySQL_DAO
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

class TestUserDao extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';

    protected static ?string $tableName = 'User';

    protected array $pk = [
        'idUser',
    ];

    protected array $columns = [
        'idUser',
        'emailAddress',
        'user',
        'password',
        'deleted',
        'deactivated',
        'creator',
        'created',
    ];
}
