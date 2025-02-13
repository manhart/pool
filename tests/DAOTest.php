<?php
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */

// TODO: this should be in ./src/pool/tests - but tests are not working there!?!
declare(strict_types = 1);

namespace g7portal\tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use pool\classes\Core\RecordSet;
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\Database\Join;
use pool\classes\Database\Operator;

final class DAOTest extends TestCase
{
    #[Test]
    public function testNewSetJoinMethod(): void
    {
        $sqlStatement =
            "SELECT User.emailAddress, UserDescription.firstname, UserDescription.lastname FROM `testDB`.`User` LEFT JOIN testDB.UserDescription AS `UserDescription` ON (testDB.User.idUser = `UserDescription`.idUser) WHERE 1=1 and User.emailAddress like '%@group-7.de' and User.deleted = false ORDER BY UserDescription.lastname ASC LIMIT 0, 10";

        $dao = MOCK_DAO_A::create()
            ->setColumns(
                'User.emailAddress',
                'UserDescription.firstname',
                'UserDescription.lastname',
            )
            ->setJoins([
                new Join(MOCK_DAO_B::class),
            ])
            ->getMultiple(
                filter: [
                    ['User.emailAddress', Operator::like, '%@group-7.de'],
                    ['User.deleted', Operator::equal, false],
                ],
                sorting: ['UserDescription.lastname' => 'ASC'],
                limit: [0, 10],
            )
            ->getRaw();

        $cleanedSQL = preg_replace('/\s+/', ' ', trim($dao[0]));

        $this->assertSame($sqlStatement, $cleanedSQL);
    }
}

class TestDAO extends MySQL_DAO
{
    // has to be overwritten! dependency of 'real_escape_string' to a real mysqli connection!
    public function escapeSQL(mixed $value): string
    {
        return (string)$value;
    }

    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        return new RecordSet([trim($sql)]);
    }
}

class MOCK_DAO_A extends TestDAO
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

class MOCK_DAO_B extends TestDAO
{
    protected static ?string $databaseName = 'testDB';

    protected static ?string $tableName = 'UserDescription';

    protected array $pk = [
        'idUserDescription',
    ];

    protected array $fk = [
        [
            'columnName' => 'idUser',
            'constraintName' => 'fk_User',
            'referencedTableName' => 'User',
            'referencedColumnName' => 'idUser',
            'fullTableName' => 'testDB.User',
        ],
    ];

    protected array $columns = [
        'idUserDescription',
        'idUser',
        'salutation',
        'lastname',
        'firstname',
    ];
}
