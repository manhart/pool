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
use pool\classes\Database\JoinType;
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

    // =========================================================================
    // Relation / JOIN system
    // =========================================================================

    public function testGeneratedRelationProducesLeftJoinSQL(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->getMultiple(filter: [['Item.name', Operator::equal, 'Widget']]),
        );

        $this->assertStringContainsString('LEFT JOIN `testDB`.`Item` AS `Item`', $sql);
        $this->assertStringContainsString('`Order`.`idItem` = `Item`.`idItem`', $sql);
    }

    public function testNoQueryReferenceProducesNoJoin(): void
    {
        $sql = $this->sqlFrom(TestOrderDao::create()->getMultiple());

        $this->assertStringNotContainsString('JOIN', $sql);
    }

    public function testAutoDetectionFromFilterAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->getMultiple(filter: [['Item.name', Operator::equal, 'Widget']]),
        );

        $this->assertStringContainsString('Item', $sql);
    }

    public function testAutoDetectionFromSortingAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->getMultiple(sorting: ['Item.name' => 'ASC']),
        );

        $this->assertStringContainsString('Item', $sql);
    }

    public function testAutoDetectionFromGroupByAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->getMultiple(groupBy: ['Item.name' => 'ASC']),
        );

        $this->assertStringContainsString('Item', $sql);
    }

    public function testAutoDetectionFromHavingAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->getMultiple(having: [['Item.name', Operator::equal, 'Widget']]),
        );

        $this->assertStringContainsString('Item', $sql);
    }

    public function testAutoDetectionFromColumnsAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::createWithColumns('Order.idOrder', 'Item.name')
                ->getMultiple(),
        );

        $this->assertStringContainsString('Item', $sql);
    }

    public function testWithExplicitlyAddsNamedRelation(): void
    {
        $sql = $this->sqlFrom(TestOrderDao::create()->with('Item')->getMultiple());

        $this->assertStringContainsString('Item', $sql);
    }

    public function testCustomRelationOverridesGeneratedRelation(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithCustomRelation::create()->with('Item')->getMultiple(),
        );

        $this->assertStringContainsString('INNER JOIN', $sql);
        $this->assertStringNotContainsString('LEFT JOIN', $sql);
    }

    public function testGetRelationsMergesInPriorityOrder(): void
    {
        $dao = TestOrderWithCustomRelation::create();

        $this->assertSame(JoinType::inner, $dao->getRelations()['Item']['joinType']);
    }

    public function testJoinMethodAddsRuntimeRelation(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()
                ->join('Alias', [
                    'target'    => TestItemDao::class,
                    'columnMap' => ['idItem' => 'idItem'],
                    'joinType'  => JoinType::inner,
                ])
                ->getMultiple(),
        );

        $this->assertStringContainsString('INNER JOIN', $sql);
        $this->assertStringContainsString('`Alias`', $sql);
    }

    public function testRuntimeRelationsAreResetAfterQuery(): void
    {
        $dao = TestOrderDao::create();
        $dao->join('Alias', ['target' => TestItemDao::class, 'columnMap' => ['idItem' => 'idItem']]);
        $dao->getMultiple();

        $sql = $this->sqlFrom($dao->getMultiple());
        $this->assertStringNotContainsString('Alias', $sql);
    }

    public function testRequestedRelationsAreResetAfterQuery(): void
    {
        $dao = TestOrderDao::create();
        $dao->with('Item')->getMultiple();

        $sql = $this->sqlFrom($dao->getMultiple());
        $this->assertStringNotContainsString('JOIN', $sql);
    }

    public function testOnArrayWithLiteralValueIsQuoted(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithOnArray::create()->with('Log')->getMultiple(),
        );

        $this->assertStringContainsString("`Log`.`status` = 'active'", $sql);
    }

    public function testOnArrayRightColumnResolvesRootPlaceholder(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithOnArray::create()->with('Log')->getMultiple(),
        );

        $this->assertStringContainsString('`Order`.idOrder', $sql);
        $this->assertStringNotContainsString('{root}', $sql);
    }

    public function testOnStringPlaceholdersAreResolved(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithOnString::create()->with('Ref')->getMultiple(),
        );

        $this->assertStringContainsString('`Order`.idOrder', $sql);
        $this->assertStringNotContainsString('{root}', $sql);
    }

    public function testChainedRelationJoinsSourceBeforeDependent(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithChain::create()->with('SubItem')->getMultiple(),
        );

        $this->assertStringContainsString('`Item`', $sql);
        $this->assertStringContainsString('`SubItem`', $sql);
        $this->assertLessThan(
            strpos($sql, '`SubItem`'),
            strpos($sql, '`Item`'),
            'source relation Item must appear before SubItem',
        );
    }

    public function testChainedRelationOnClauseUsesSourceAlias(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithChain::create()->with('SubItem')->getMultiple(),
        );

        $this->assertStringContainsString('`Item`.`idItem` = `SubItem`.`idItem`', $sql);
    }

    // Case 1: generatedRelations + customRelations with distinct keys — both joins appear
    public function testGeneratedAndCustomRelationsWithDistinctKeysBothJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderWithMixedRelations::create()->with('Item', 'Log')->getMultiple(),
        );

        // generated relation
        $this->assertStringContainsString('LEFT JOIN `testDB`.`Item` AS `Item`', $sql);
        // custom relation
        $this->assertStringContainsString('LEFT JOIN `testDB`.`Log` AS `Log`', $sql);
    }

    public function testGeneratedAndCustomRelationsAutoDetectedIndependently(): void
    {
        // 'Item' from filter (generatedRelations), 'Log' from sorting (customRelations)
        $sql = $this->sqlFrom(
            TestOrderWithMixedRelations::create()->getMultiple(
                filter:  [['Item.name', Operator::equal, 'Widget']],
                sorting: ['Log.status' => 'ASC'],
            ),
        );

        $this->assertStringContainsString('`Item`', $sql);
        $this->assertStringContainsString('`Log`', $sql);
    }

    // Case 2: all three levels — runtime overwrites alias from generated and custom
    public function testRuntimeRelationOverwritesGeneratedAlias(): void
    {
        // generatedRelations has 'Item' as LEFT JOIN
        // runtime join('Item', ...) with INNER JOIN should win
        $sql = $this->sqlFrom(
            TestOrderWithMixedRelations::create()
                ->join('Item', [
                    'target'    => TestItemDao::class,
                    'columnMap' => ['idItem' => 'idItem'],
                    'joinType'  => JoinType::inner,
                ])
                ->getMultiple(filter: [['Item.name', Operator::equal, 'Widget']]),
        );

        $this->assertStringContainsString('INNER JOIN', $sql);
        $this->assertStringNotContainsString('LEFT JOIN', $sql);
    }

    public function testAllThreeLevelsRuntimeOverwritesCustomAlias(): void
    {
        // generatedRelations: Item (LEFT), customRelations: Log (LEFT)
        // runtime join('Log', ...) with INNER JOIN overwrites custom 'Log', Item stays LEFT
        $sql = $this->sqlFrom(
            TestOrderWithMixedRelations::create()
                ->join('Log', [
                    'target'   => TestLogDao::class,
                    'on'       => [['left' => 'idOrder', 'operator' => Operator::equal, 'right' => '{root}.idOrder']],
                    'joinType' => JoinType::inner,
                ])
                ->getMultiple(
                    filter:  [['Item.name', Operator::equal, 'Widget']],
                    sorting: ['Log.status' => 'ASC'],
                ),
        );

        // Log overwritten to INNER by runtime
        $this->assertStringContainsString('INNER JOIN `testDB`.`Log` AS `Log`', $sql);
        // Item still LEFT from generatedRelations
        $this->assertStringContainsString('LEFT JOIN `testDB`.`Item` AS `Item`', $sql);
    }

    // =========================================================================
    // countFrom / getCount — relation system
    // =========================================================================

    public function testGetCountWithRelationFilterAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()->getCount(filter: [['Item.name', Operator::equal, 'Widget']]),
        );

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringContainsString('LEFT JOIN `testDB`.`Item` AS `Item`', $sql);
        $this->assertStringContainsString('Item.name =', $sql);
    }

    public function testGetCountWithoutRelationReferenceProducesNoJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()->getCount(filter: [['total', Operator::greater, 0]]),
        );

        $this->assertStringContainsString('SELECT COUNT(*)', $sql);
        $this->assertStringNotContainsString('JOIN', $sql);
    }

    public function testGetCountWithExplicitWithAddsJoin(): void
    {
        $sql = $this->sqlFrom(
            TestOrderDao::create()->with('Item')->getCount(),
        );

        $this->assertStringContainsString('LEFT JOIN `testDB`.`Item` AS `Item`', $sql);
    }

    public function testGetCountRelationResetAfterCall(): void
    {
        $dao = TestOrderDao::create();
        $dao->with('Item')->getCount();

        $sql = $this->sqlFrom($dao->getCount());
        $this->assertStringNotContainsString('JOIN', $sql);
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
    protected static ?string $tableName    = 'User';
    protected array $pk      = ['idUser'];
    protected array $columns = ['idUser', 'emailAddress', 'user', 'password', 'deleted', 'deactivated', 'creator', 'created'];
}

class TestItemDao extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Item';
    protected array $pk      = ['idItem'];
    protected array $columns = ['idItem', 'name'];
}

class TestLogDao extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Log';
    protected array $pk      = ['idLog'];
    protected array $columns = ['idLog', 'idOrder', 'status'];
}

class TestRefDao extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Ref';
    protected array $pk      = ['idRef'];
    protected array $columns = ['idRef', 'idOrder'];
}

/** Order DAO with a single generatedRelation for Item */
class TestOrderDao extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Order';
    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder', 'idItem'];

    protected array $generatedRelations = [
        'Item' => [
            'target'    => TestItemDao::class,
            'columnMap' => ['idItem' => 'idItem'],
        ],
    ];
}

/** TestOrderDao with customRelations overriding Item join type to INNER */
class TestOrderWithCustomRelation extends TestOrderDao
{
    protected array $customRelations = [
        'Item' => [
            'target'    => TestItemDao::class,
            'columnMap' => ['idItem' => 'idItem'],
            'joinType'  => JoinType::inner,
        ],
    ];
}

/**
 * Order DAO with generatedRelations['Item'] AND customRelations['Log'] (distinct keys).
 * Used to verify that both levels coexist and each join is independently resolvable.
 */
class TestOrderWithMixedRelations extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Order';
    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder', 'idItem'];

    protected array $generatedRelations = [
        'Item' => [
            'target'    => TestItemDao::class,
            'columnMap' => ['idItem' => 'idItem'],
        ],
    ];

    protected array $customRelations = [
        'Log' => [
            'target' => TestLogDao::class,
            'on'     => [
                ['left' => 'idOrder', 'operator' => Operator::equal, 'right' => '{root}.idOrder'],
            ],
        ],
    ];
}

/** Order DAO with an on-array customRelation using {root} placeholder */
class TestOrderWithOnArray extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Order';
    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder'];

    protected array $customRelations = [
        'Log' => [
            'target' => TestLogDao::class,
            'on'     => [
                ['left' => 'status',   'operator' => Operator::equal, 'value'       => 'active'],
                ['left' => 'idOrder',  'operator' => Operator::equal, 'right' => '{root}.idOrder'],
            ],
        ],
    ];
}

/** Order DAO with an on-string customRelation using {root} placeholder */
class TestOrderWithOnString extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Order';
    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder'];

    protected array $customRelations = [
        'Ref' => [
            'target' => TestRefDao::class,
            'on'     => 'Ref.idOrder = {root}.idOrder',
        ],
    ];
}

/** Order DAO with a chained relation: Item (source) → SubItem */
class TestOrderWithChain extends SqlCapturingMySqlDao
{
    protected static ?string $databaseName = 'testDB';
    protected static ?string $tableName    = 'Order';
    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder'];

    protected array $generatedRelations = [
        'Item' => [
            'target'    => TestItemDao::class,
            'columnMap' => ['idItem' => 'idItem'],
        ],
        'SubItem' => [
            'source'    => 'Item',
            'target'    => TestItemDao::class,
            'columnMap' => ['idItem' => 'idItem'],
        ],
    ];
}
