<?php

/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */
declare(strict_types = 1);

namespace g7portal\tests;

use commons\daos\mysql\g7logistics\ParcelServiceBarcode;
use commons\daos\mysql\g7podupload\PodFileApprove;
use commons\daos\mysql\g7podupload\PodFileViewedForApprove;
use commons\daos\mysql\g7podupload\PodUpload;
use commons\daos\mysql\g7portal\Address;
use commons\daos\mysql\g7portal\AddressBook;
use commons\daos\mysql\g7portal\AddressBookEntry;
use commons\daos\mysql\g7portal\Avise;
use commons\daos\mysql\g7portal\AviseSubset;
use commons\daos\mysql\g7portal\CargosoftData;
use commons\daos\mysql\g7portal\ChangeLogTotalByGroup;
use commons\daos\mysql\g7portal\ChangeLogTotalSeenByUser;
use commons\daos\mysql\g7portal\Comment;
use commons\daos\mysql\g7portal\CommentTopic;
use commons\daos\mysql\g7portal\Container;
use commons\daos\mysql\g7portal\ContainerType;
use commons\daos\mysql\g7portal\Customer;
use commons\daos\mysql\g7portal\CustomerAddress;
use commons\daos\mysql\g7portal\CustomerCountry;
use commons\daos\mysql\g7portal\CustomerIncoterm;
use commons\daos\mysql\g7portal\DocumentType;
use commons\daos\mysql\g7portal\EBookingAddress;
use commons\daos\mysql\g7portal\EBookingShipment;
use commons\daos\mysql\g7portal\File;
use commons\daos\mysql\g7portal\Incoterm;
use commons\daos\mysql\g7portal\OfficeLocation;
use commons\daos\mysql\g7portal\OrderType;
use commons\daos\mysql\g7portal\Package;
use commons\daos\mysql\g7portal\ParcelService;
use commons\daos\mysql\g7portal\Purchaser;
use commons\daos\mysql\g7portal\Reeder;
use commons\daos\mysql\g7portal\Shipment;
use commons\daos\mysql\g7portal\ShipmentFile;
use commons\daos\mysql\g7portal\Status;
use commons\daos\mysql\g7portal\SubCustomer;
use commons\daos\mysql\g7portal\Supplier;
use commons\daos\mysql\g7portal\TrackAndTraceShipment;
use commons\daos\mysql\g7portal\TransportName;
use commons\daos\mysql\g7portal\TransportType;
use commons\daos\mysql\g7portal\Truck;
use commons\daos\mysql\g7portal\UserCustomer;
use commons\daos\mysql\g7system\Application;
use commons\daos\mysql\g7system\Authorisation;
use commons\daos\mysql\g7system\CostCenter;
use commons\daos\mysql\g7system\CustomerLogistics;
use commons\daos\mysql\g7system\Grid;
use commons\daos\mysql\g7system\GridClass;
use commons\daos\mysql\g7system\Group;
use commons\daos\mysql\g7system\GroupUser;
use commons\daos\mysql\g7system\Language;
use commons\daos\mysql\g7system\Locales;
use commons\daos\mysql\g7system\MenuItem;
use commons\daos\mysql\g7system\ParcelServiceLogistics;
use commons\daos\mysql\g7system\Permission;
use commons\daos\mysql\g7system\Role;
use commons\daos\mysql\g7system\RoleAuthorisation;
use commons\daos\mysql\g7system\User;
use commons\daos\mysql\g7userlist\Databaselist;
use commons\daos\mysql\g7userlist\Deletionmodes;
use commons\daos\mysql\geoBible\Continent;
use commons\daos\mysql\geoBible\Country;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use pool\classes\Core\RecordSet;
use pool\classes\Database\DataInterface;
use pool\classes\Database\Join;
use pool\classes\Database\Operator;

final class DAOJoinTest extends G7PortalTestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!DataInterface::dataInterfaceExists(constant('DB_G7SYSTEM')) || !DataInterface::dataInterfaceExists(constant('DB_G7PORTAL'))) {
            try {
                DataInterface::createDataInterface([
                    'host' => MYSQL_HOST,
                    'database' => [
                        constant('DB_G7SYSTEM'),
                        constant('DB_G7PORTAL'),
                        constant('DB_GEOBIBLE'),
                        constant('DB_G7PODUPLOAD'),
                        constant('DB_G7USERLIST'),
                        constant('DB_G7LOGISTICS'),
                    ],
                ]);
            } catch (Exception $e) {
                echo 'Error: '.$e->getMessage()."\n";
            }
        }
    }

    #[Test]
    public function newJoinBuilder(): void
    {
        $data = MockCustomer::createWithColumns('customer')
            ->setJoins([new Join(UserCustomer::class)])
            ->getMultiple(filter: [['UserCustomer.idUser', Operator::equal, 1]])
            ->getRaw();

        //        $sql = $data[0];
        //        var_dump(trim($sql));
        $data = $data[1];
        $found = false;
        foreach ($data as $row) {
            if (in_array('geske', $row, true)) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'The value "geske" was not found in the $data array.');
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/File.php | File::getMultipleShipmentFile()
     */
    #[Test]
    public function rebuild_File_getMultipleShipmentFileAsJoin(): void
    {
        $id = 133; // geske
        $filter = [
            ['File.deleted', Operator::isNot],
            ['ShipmentFile.idShipment', Operator::equal, $id],
        ];

        $dataWithJoin = File::create()
            ->setColumns('*')
            ->setJoins([
                new Join(ShipmentFile::class),
                new Join(DocumentType::class),
                // todo: why no fk?
                new Join(User::class, File::class, [['User.idUser', Operator::equal, 'File.creator']]),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        // todo: why is everything "full"joined without setColumns? because.. column_list = '*' ? i dont get it..
        $dataWithDAOMethod = File::create()->setColumns('*')->getMultipleShipmentFile(filter: $filter)->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/Avise.php | File::getCountAvis()
     */
    #[Test]
    public function rebuild_Avise_getCountAvis(): void
    {
        $this->assertTrue(true);
        return;
        $idCustomer = 41; // geske
        $data = Avise::create()
            ->setColumns('COUNT(*) AS `count`')
            ->setJoins([
                new Join(TransportType::class, 'idTransportType', Avise::class, 'idTransportType'),
                // TODO: DAO nicht vorhanden ! - [Article::class, [], 'idArticle', Avise::class, 'idArticle'],
                new Join(Customer::class, 'idCustomer', Avise::class, 'idCustomer'),
            ])
            ->getMultiple(filter: [
                ['Avise.idCustomer', Operator::equal, $idCustomer],
                ['Avise.deleted', Operator::equal, false],
            ])
            ->getRaw();

        //        var_dump($data);

        // todo: assert original data - but not possible because of the missing DAO
        $this->assertIsArray($data);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/Comment.php | Comment::getComments()
     */
    #[Test]
    public function rebuild_Comment_getComments(): void
    {
        $idCommentTopic = 1;
        $last = 10;
        $columns = [
            'Comment.idComment',
            'Comment.message',
            'Comment.mode',
            'Comment.seenByCustomer',
            'Comment.seenByInhouse',
            'Comment.createdAt',
            'Comment.modifiedAt',
            'User.membership',
            'User.firstname',
            'User.lastname',
            'User.idUser',
        ];
        $filter = [
            ['Comment.mode', 'equal', Comment::MODE_PUBLIC],
            ['Comment.deleted', Operator::equal, false],
            ['Comment.idComment', 'greater', $last],
        ];
        $sorting = ['Comment.createdAt' => 'DESC'];

        $dataWithJoins = Comment::create()->setColumns(implode(', ', $columns))
            ->setJoins([
                new Join(CommentTopic::class),
                // TODO: why is ther no FK in comment to user set??? this is not good.. does this happens often.. i
                new Join(User::class, Comment::class, [['User.idUser', Operator::equal, 'Comment.idUser']]),
            ])
            ->getMultiple($idCommentTopic, 'Comment.idCommentTopic', filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = Comment::create()->setColumns(implode(', ', $columns))
            ->getComments($idCommentTopic, 'Comment.idCommentTopic', $filter, $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoins);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoins, $dataWithDAOMethod);
    }

    /**
     *  TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/CustomerCountry.php | CustomerCountry::getMultipleCustomerCountry()
     */
    #[Test]
    public function rebuild_CustomerCountry_getMultipleCustomerCountry(): void
    {
        $idCustomer = 41; // geske
        $columns = [
            'Country.idCountry',
            'Country.country',
            'Country.iso3316_alpha2',
        ];
        $dataWithJoin = CustomerCountry::createWithColumns(implode(', ', $columns))
            ->setJoins([
                new Join(Country::class),
                // todo: no fks set!
                new Join(ParcelService::class, CustomerCountry::class, [['ParcelService.idParcelService', Operator::equal, 'CustomerCountry.idParcelService']]),
            ])
            ->getMultiple(
                filter: [['CustomerCountry.idCustomer', Operator::equal, $idCustomer]],
                sorting: ['Country.country' => 'ASC'],
            )
            ->getRaw();

        $dataWithDAOMethod = CustomerCountry::createWithColumns(implode(', ', $columns))
            ->getMultipleCustomerCountry(
                filter: [['CustomerCountry.idCustomer', Operator::equal, $idCustomer]],
                sorting: ['Country.country' => 'ASC'],
            )
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * This TEST is not possible because of the aliases used in the joins ( multiple same table join)
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/Shipment.php | Shipment::getMultipleShipment()
     */
    #[Test]
    public function rebuild_Shipment_getMultipleShipment(): void
    {
        $this->assertTrue(true);
        return;
        $idShipment = 127; // geske
        $columns = ['Shipment.*', 'TransportType.*'];
        $filter = [['Shipment.idShipment', Operator::equal, $idShipment]];

        $dataWithJoin = MockDAO::createWithColumns(...$columns)
            ->setJoins([
                new Join(TransportName::class),
                new Join(Customer::class, Shipment::class), // TODO: this is the first join from dao to dao with no this dao and set FK!!
                new Join(Status::class),
                new Join(TransportType::class),
                new Join(Supplier::class),
                new Join(Purchaser::class),
                new Join(Address::class, Shipment::class, [['SupplierAddress.idAddress', Operator::equal, 'Shipment.idAddress_SupplierAddress',]], 'SupplierAddress'),
                new Join(Incoterm::class),
                new Join(Reeder::class),
                new Join(OrderType::class),
                // todo. fk is missing
                new Join(User::class, Shipment::class, [['User.idUser', Operator::equal, 'Shipment.creator']]),
                new Join(Truck::class),
                // todo: fk is missing
                new Join(
                    CommentTopic::class,
                    Shipment::class,
                    [['CommentTopic.tableName', Operator::equal, 'Shipment.idShipment'], ['CommentTopic.tableName', Operator::equal, '"Shipment"']],
                ),
                // todo: check why this is not wokring ... maybe fk .. maybe alias prob...
                new Join(Address::class, Shipment::class, [['DeliveryAddress.idAddress', Operator::equal, 'Shipment.idAddress_DeliveryAddress',]], 'DeliveryAddress'),
                new Join(Country::class, Address::class, [['DeliveryCountry.idCountry', Operator::equal, 'Address.idCountry']], 'DeliveryCountry'),
                // todo: fk is missing
                new Join(OfficeLocation::class, Shipment::class, [['OfficeLocation.idOfficeLocation', Operator::equal, 'Shipment.idOfficeLocation']]),
                new Join(SubCustomer::class),
                new Join(ParcelService::class),
                // todo: fk is missing
                new Join(
                    ChangeLogTotalByGroup::class,
                    Shipment::class,
                    [
                        ['ChangeLogTotalByGroup.groupName', Operator::equal, 'Shipment'],
                        ['ChangeLogTotalByGroup.groupRowId', Operator::equal, 'Shipment.idShipment'],
                    ],
                ),
                new Join(
                    ChangeLogTotalSeenByUser::class,
                    Shipment::class,
                    [
                        ['ChangeLogTotalSeenByUser.groupName', Operator::equal, 'Shipment'],
                        ['ChangeLogTotalSeenByUser.groupRowId', Operator::equal, 'Shipment.idShipment'],
                    ],

                ),
                new Join(
                    AddressBookEntry::class,
                    Shipment::class,
                    [['AddressBookEntryRecipient.type', Operator::equal, 'recipient']],
                    'AddressBookEntryRecipient',
                ),
                new Join(
                    AddressBookEntry::class,
                    Shipment::class,
                    [['AddressBookEntryDelivery.type', Operator::equal, 'delivery']],
                    'AddressBookEntryDelivery',
                ),
                new Join(
                    Address::class,
                    'AddressBookEntryRecipient',
                    [['Recipient.idAddress', Operator::equal, 'AddressBookEntryRecipient.idAddress']],
                    'Recipient',
                ),
                new Join(
                    Address::class,
                    'AddressBookEntryDelivery',
                    [['Delivery.idAddress', Operator::equal, 'AddressBookEntryDelivery.idAddress']],
                    'Delivery',
                ),
                new Join(
                    Country::class,
                    'Recipient',
                    [['CountryRecipient.idCountry', Operator::equal, 'Recipient.idCountry']],
                    'CountryRecipient',
                ),
                new Join(
                    Country::class,
                    'Delivery',
                    [['CountryDelivery.idCountry', Operator::equal, 'Delivery.idCountry']],
                    'CountryDelivery',
                ),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        var_dump(trim($dataWithJoin[0]));

        // INFO: this is not possible because g7app is already initialized
        $dataWithDAOMethod = Shipment::createWithColumns(...$columns)
            ->getMultipleShipment(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/RoleAuthorisation.php | RoleAuthorisation::getMultipleRoleAuthorisation()
     */
    #[Test]
    public function rebuild_RoleAuthorisation_getMultipleRoleAuthorisation(): void
    {
        $idRole = 120; // GESKE GmbH - POM-Manager (G7 Mitarbeiter)
        $filter = [
            ['RoleAuthorisation.idRole', Operator::equal, $idRole],
            ['Authorisation.idEntityType', Operator::equal, 'customer'],
        ];

        $dataWithJoin = RoleAuthorisation::create()->setColumns('*')
            ->setJoins([
                new Join(Role::class),
                new Join(Authorisation::class),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        $dataWithDAOMethod = RoleAuthorisation::create(throws: true)
            ->getMultipleRoleAuthorisation(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /*
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/User.php | User::getMultipleUserCustomer()
     */
    #[Test]
    public function rebuild_User_getMultipleUserCustomer(): void
    {
        $idCustomer = 41; // geske
        $columns = [
            'User.idUser',
            'User.firstname',
            'User.lastname',
            'IF(User.membership=\''.User::MEMBERSHIP_CUSTOMER.'\',\'Kunde\',IF(User.membership=\''.User::MEMBERSHIP_AGENT.'\',\'Agent\',\'Mitarbeiter\'))membership',
            'Customer.customerName',
        ];
        $sorting = ['User.lastname' => 'asc', 'User.firstname' => 'asc'];
        $filter = [
            ['UserCustomer.idCustomer', 'equal', $idCustomer],
        ];

        $dataWithJoin = User::create()
            ->setColumns(...$columns)
            ->setJoins([
                // todo: info! no fk in user OR usercustomer set!
                new Join(UserCustomer::class, User::class, [['UserCustomer.idUser', Operator::equal, 'User.idUser']]),
                new Join(Customer::class, UserCustomer::class, [['Customer.idCustomer', Operator::equal, 'UserCustomer.idCustomer']]),
                new Join(Language::class),
            ])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = User::create()
            ->setColumns(...$columns)
            ->getMultipleUserCustomer(filter: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * /commons/daos/mysql/g7system/Application.php | Application::getMultipleWithAuthorisation()
     */
    #[Test]
    public function rebuild_Application_getMultipleWithAuthorisation(): void
    {
        $columns = [
            'Application.idApplication',
            'Application.application',
            'Application.title',
            'Authorisation.idAuthorisation',
            'Authorisation.authorisation',
            'Application.startPoint',
            'Application.description',
            'Application.sortNumber',
            'Application.config',
            'Application.class',
            'Application.deleted',
        ];
        $dataWithJoin = Application::create()->setColumns(implode(', ', $columns))
            ->setJoins([
                // todo: no fk set..
                new Join(
                    Authorisation::class,
                    joinConditions: [
                        ['Authorisation.idEntityType', Operator::equal, '"application"'],
                        ['Authorisation.idEntity', Operator::equal, 'Application.idApplication'],
                    ],
                ),
            ])
            ->getMultiple(filter: [['Application.deleted', Operator::equal, false]])
            ->getRaw();

        $dataWithDAOMethod = Application::createWithColumns(implode(', ', $columns))
            ->getMultipleWithAuthorisation()
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /*
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/Permission.php | Permission::checkIfCustomerHasAuthorisation()
     */
    #[Test]
    public function rebuild_Permission_checkIfCustomerHasAuthorisation(): void
    {
        $idCustomer = 41; // geske
        $authorisation = 'g7portal_avisegrid.grid.access'; // geske
        $dataWithJoin = Permission::create()
            ->setColumns('DISTINCT IF(Authorisation.authorisation IS NOT NULL, TRUE, FALSE) AS authorised')
            ->setJoins([
                new Join(Role::class),
                new Join(RoleAuthorisation::class, Role::class),
                new Join(Authorisation::class, RoleAuthorisation::class),
            ])
            ->getMultiple(filter: [
                ['Permission.idCustomer', Operator::equal, $idCustomer],
                ['Authorisation.authorisation', Operator::equal, $authorisation],
            ])
            ->getRaw();

        $dataWithDAOMethod = Permission::create()
            ->checkIfCustomerHasAuthorisation($idCustomer, $authorisation)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/User.php | User::getUsers()
     */
    #[Test]
    public function rebuild_User_getUsers(): void
    {
        $columns = [
            'User.idUser as `idUser`',
            'User.fullNameReversed as `name`',
            'User.emailAddress as `emailAddress`',
            'User.user as `user`',
            'User.membership as `membership`',
            'Customer.customerName as `company`',
            'Group.caption as `group`',
            '(select IF(count(*)>1, count(*), Customer.customerName) from g7portal.UserCustomer left join g7portal.Customer ON Customer.idCustomer = UserCustomer.idCustomer where UserCustomer.idUser = User.idUser) as `customerName`',
            '(IF(User.deleted,"deleted",IF(User.blocked,"banned",IF(User.deactivated, "deactivated", "active")))) as `status`',
            '(IF(User.lastlogin, User.lastlogin, "")) as `lastlogin`',
            'User.idUser as `loginButton`',
            'User.firstname as `firstname`',
            'User.lastname as `lastname`',
            'User.salutation as `salutation`',
            'User.idLanguage as `idLanguage`',
            'User.idSecondLanguage as `idSecondLanguage`',
            'User.idLocales_Primary as `idLocales_Primary`',
            'User.idLocales_Secondary as `idLocales_Secondary`',
            'User.idCustomer as `idCustomer`',
            'User.idGroup as `idGroup`',
            'User.idCountry as `idCountry`',
            'User.resetPassword as `resetPassword`',
            'User.deactivated as `deactivated`',
            '(User.sessionLifetime / 60) as `sessionLifetime`',
            'User.noLdapAccount as `noLdapAccount`',
        ];
        $type = 'group7';
        $filter = [['User.membership', $type === 'System' ? Operator::equal : 'unequal', User::MEMBERSHIP_SYSTEM]];
        $sorting = ['User.lastname' => 'asc', 'User.firstname' => 'asc'];
        $limit = [0, 10];

        $dataWithJoinMethod = User::createWithColumns(...$columns)
            ->setJoins([
                // todo: no fk..
                new Join(Customer::class, User::class, [['Customer.idCustomer', Operator::equal, 'User.idCustomer']]),
                new Join(Group::class),
            ])
            ->getMultiple(
                filter: $filter,
                sorting: $sorting,
                limit: $limit,
            )
            ->getRaw();

        $dataWithDAOMethod = User::createWithColumns(...$columns)
            ->getUsers(
                filter: $filter,
                sorting: $sorting,
                limit: $limit,
            )
            ->getRaw();

        $this->assertIsArray($dataWithJoinMethod);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoinMethod, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/AddressBook | AddressBook::getMultipleWithCountry()
     */
    #[Test]
    public function rebuild_AddressBook_getMultipleWithCountry(): void
    {
        $idAddressBook_Recipient = 1;
        $filter = [['petname', Operator::equal, $idAddressBook_Recipient]];
        $dataWithJoin = AddressBook::create()
            ->setColumns('Country.iso3316_alpha2')
            ->setJoins([
                new Join(AddressBookEntry::class),
                new Join(Address::class, AddressBookEntry::class, [['Address.idAddress', Operator::equal, 'AddressBookEntry.idAddress']]),
                new Join(Country::class, Address::class, [['Country.idCountry', Operator::equal, 'Address.idCountry']]),
            ])
            ->getMultiple(filter: $filter)
            ->getValueAsString('iso3316_alpha2');

        $dataWithDAOMethod = AddressBook::createWithColumns('Country.iso3316_alpha2')
            ->getMultipleWithCountry(filter: $filter)
            ->getValueAsString('iso3316_alpha2');

        $this->assertIsString($dataWithJoin);
        $this->assertIsString($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/Container | Container::getMultipleContainer()
     */
    #[Test]
    public function rebuild_Container_getMultipleContainer(): void
    {
        $columns = [
            "Concat('mod_Container_Manager_Grid_',(Container.idContainer)) as rowId",
            "Container.idContainer",
            "IF(Container.container = '', CONCAT('Noch unbekannt ', IFNULL(ContainerType.foot, ''), '\"', ContainerType.abbr), Container.container) as virtualContainerName",
            "Container.container",
            "ContainerType.idContainerType",
            "ContainerType.description",
        ];
        $filter = [];
        $sorting = ['Container.container = \'\'' => 'DESC'];

        $dataWithJoin = Container::create()
            ->setColumns(...$columns)
            ->setJoins([
                new Join(ContainerType::class),
            ])
            ->getMultipleContainer(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = Container::create()
            ->setColumns(...$columns)
            ->getMultipleContainer(filter: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/Package | Package::getPackages()
     */
    #[Test]
    public function rebuild_Package_getPackage(): void
    {
        $dataWithJoin = Package::create()
            ->setJoins([
                // todo: no fk
                new Join(Application::class, Package::class, [['Application.idApplication', Operator::equal, 'Package.idApplication']]),
            ])
            ->getMultiple(filter: [])
            ->getRaw();

        $dataWithDAOMethod = Package::create()->getPackages(filter: [])->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/MenuItem | MenuItem::getMultipleLeftJoinAuthorisation()
     */
    #[Test]
    public function rebuild_Menu_getMultipleLeftJoinAuthorisation(): void
    {
        $idMenu = 1;
        $columns = [
            'MenuItem.*',
            '(MenuItem.menuLabel)translationKey',
            'Authorisation.idAuthorisation',
        ];
        $filter = [['MenuItem.idMenu', 'equal', $idMenu]];
        $sorting = ['sortNumber' => 'ASC'];

        // todo: no fk set!
        $dataWithJoin = MenuItem::create()
            ->setColumns(...$columns)
            ->setJoins([
                new Join(Authorisation::class, joinConditions: [
                    ['Authorisation.idEntityType', Operator::equal, '"menuitem"'],
                    ['Authorisation.idEntity', Operator::equal, 'MenuItem.idMenuItem'],
                ]),
            ])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = MenuItem::create()
            ->setColumns(...$columns)
            ->getMultipleLeftJoinAuthorisation(filter: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/AviseSubset | AviseSubset::getMultipleWithAvise()
     */
    #[Test]
    public function rebuild_AviseSubset_getMultipleWithAvise(): void
    {
        $columns = [
            'Avise.poNumber',
        ];
        $idShipment = 101; // geske
        $filter = [
            ['idShipment', Operator::equal, $idShipment],
            ['AviseSubset.deleted', Operator::isNot],
        ];
        $dataWithJoin = AviseSubset::create()
            ->setColumns(...$columns)
            ->setJoins([
                new Join(Avise::class),
            ])
            ->getMultiple(filter: $filter)
            ->getFieldData('poNumber');

        $dataWithDAOMethod = AviseSubset::create(throws: true)
            ->setColumns(...$columns)
            ->getMultipleWithAvise(filter: $filter)
            ->getFieldData('poNumber');

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/CustomerAddress | CustomerAddress::getMultipleWithCountry()
     */
    #[Test]
    public function rebuild_CustomerAddress_getMultipleWithCountry(): void
    {
        $idCustomer = 3; // muster
        $usage = 2;
        $filter = [
            ["CustomerAddress.idCustomer", Operator::equal, $idCustomer],
            ["CustomerAddress.deleted", Operator::isNot],
            ["CustomerAddress.usage", Operator::in, CustomerAddress::usageToArray($usage)],
        ];

        $dataWithJoin = CustomerAddress::create(throws: true)
            ->setJoins([
                // todo: no fk
                new Join(Country::class, joinConditions: [['Country.idCountry', Operator::equal, 'CustomerAddress.idCountry']]),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        $dataWithDAOMethod = CustomerAddress::create(throws: true)
            ->getMultipleWithCountry(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/CustomerIncoterm | CustomerIncoterm::getMultipleCustomerIncoterm()
     */
    #[Test]
    public function rebuild_CustomerIncoterm_getMultipleCustomerIncoterm(): void
    {
        $columns = [
            'Incoterm.idIncoterm',
            'Incoterm.code',
            'Incoterm.description',
        ];
        $idCustomer = 41; // geske
        $filter = [['idCustomer', Operator::equal, $idCustomer]];
        $sorting = ['code' => 'ASC'];

        $dataWithJoin = CustomerIncoterm::createWithColumns(...$columns)
            ->setJoins([new Join(Incoterm::class)])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = CustomerIncoterm::createWithColumns(...$columns)
            ->getMultipleCustomerIncoterm(filter: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7portal/EBookingShipment | EBookingShipment::getMultipleEBookingShipment()
     */
    #[Test]
    public function rebuild_EBookingShipment_getMultipleEBookingShipment(): void
    {
        $idEBookingShipment = 3;
        $columns = [
            'EBookingShipment.idEBookingShipment',
            'EBookingShipment.shipment',
            'EBookingShipment.caption',
            'EBookingShipment.pickupDate',
            'EBookingShipment.pickupTimeFrom',
            'EBookingShipment.pickupTimeUntil',
            'EBookingShipment.deliveryDate',
            'EBookingShipment.deliveryTimeFrom',
            'EBookingShipment.deliveryTimeUntil',
            'EBookingShipment.destination',
            'EBookingShipment.notifyTitle',
            'EBookingShipment.notifyFirstname',
            'EBookingShipment.notifyLastname',
            'EBookingShipment.notifyEmail',
            'EBookingShipment.notifyPhone',
            'EBookingShipment.notifyRefnr',
            'EBookingShipment.notice',
            'EBookingShipment.hasTransportInsurance',
            'EBookingShipment.transportInsuranceType',
            'EBookingShipment.isDangerousGoods',
            '(Customer.customerName)customer',
            'TransportType.transportType',
            'Incoterm.code',
            'Incoterm.description',
            '(ShipperAddress.name1)as senderName1',
            '(ShipperAddress.name2)as senderName2',
            '(ShipperAddress.name3)as senderName3',
            '(ShipperAddress.streetName)as senderStreetname',
            '(ShipperAddress.houseNumber)as senderHousenumber',
            '(ShipperAddress.postcode)as senderPostcode',
            '(ShipperAddress.city)as senderLocality',
            '(ShipperAddress.email)as senderEmail',
            '(ShipperAddress.phone)as senderPhone',
            '(ShipperAddress.telefax)as senderTelefax',
            '(ShipperAddress.externalReferenceNumber)as senderRefnr',
            '(ConsigneeAddress.name1)as recipientName1',
            '(ConsigneeAddress.name2)as recipientName2',
            '(ConsigneeAddress.name3)as recipientName3',
            '(ConsigneeAddress.streetName)as recipientStreetname',
            '(ConsigneeAddress.houseNumber)as recipientHousenumber',
            '(ConsigneeAddress.postcode)as recipientPostcode',
            '(ConsigneeAddress.city)as recipientLocality',
            '(ConsigneeAddress.email)as recipientEmail',
            '(ConsigneeAddress.phone)as recipientPhone',
            '(ConsigneeAddress.telefax)as recipientTelefax',
            '(ConsigneeAddress.externalReferenceNumber)as recipientRefnr',
            '(ShipperAddressAlternate.idEBookingAddress)as alternateIdSenderAddress',
            '(ShipperAddressAlternate.name1)as alternateSenderName1',
            '(ShipperAddressAlternate.name2)as alternateSenderName2',
            '(ShipperAddressAlternate.name3)as alternateSenderName3',
            '(ShipperAddressAlternate.streetName)as alternateSenderStreetname',
            '(ShipperAddressAlternate.houseNumber)as alternateSenderHousenumber',
            '(ShipperAddressAlternate.postcode)as alternateSenderPostcode',
            '(ShipperAddressAlternate.city)as alternateSenderLocality',
            '(ShipperAddressAlternate.email)as alternateSenderEmail',
            '(ShipperAddressAlternate.phone)as alternateSenderPhone',
            '(ShipperAddressAlternate.telefax)as alternateSenderTelefax',
            '(ShipperAddressAlternate.externalReferenceNumber)as alternateSenderRefnr',
            '(ConsigneeAddressAlternate.idEBookingAddress)as alternateIdRecipientAddress',
            '(ConsigneeAddressAlternate.name1)as alternateRecipientName1',
            '(ConsigneeAddressAlternate.name2)as alternateRecipientName2',
            '(ConsigneeAddressAlternate.name3)as alternateRecipientName3',
            '(ConsigneeAddressAlternate.streetName)as alternateRecipientStreetname',
            '(ConsigneeAddressAlternate.houseNumber)as alternateRecipientHousenumber',
            '(ConsigneeAddressAlternate.postcode)as alternateRecipientPostcode',
            '(ConsigneeAddressAlternate.city)as alternateRecipientLocality',
            '(ConsigneeAddressAlternate.email)as alternateRecipientEmail',
            '(ConsigneeAddressAlternate.phone)as alternateRecipientPhone',
            '(ConsigneeAddressAlternate.telefax)as alternateRecipientTelefax',
            '(ConsigneeAddressAlternate.externalReferenceNumber)as alternateRecipientRefnr',
            '(Creator.firstname)as creatorFirstname',
            '(Creator.lastname)as creatorLastname',
            '(Creator.emailAddress)as creatorEmailAddress',
            '(Modifier.firstname)as modifierFirstname',
            '(Modifier.lastname)as modifierLastname',
            '(Modifier.emailAddress)as modifierEmailAddress',
            'EBookingShipment.goodsValue',
        ];

        $dataWithJoin = EBookingShipment::createWithColumns(...$columns)
            ->setJoins([
                new Join(Customer::class),
                new Join(
                    EBookingAddress::class,
                    EBookingShipment::class,
                    [['ShipperAddress.idEBookingAddress', Operator::equal, 'EBookingShipment.idEBookingAddress_Shipper']],
                    'ShipperAddress',
                ),
                new Join(
                    EBookingAddress::class,
                    EBookingShipment::class,
                    [['ConsigneeAddress.idEBookingAddress', Operator::equal, 'EBookingShipment.idEBookingAddress_Consignee']],
                    'ConsigneeAddress',
                ),
                new Join(
                    EBookingAddress::class,
                    EBookingShipment::class,
                    [['ShipperAddressAlternate.idEBookingAddress', Operator::equal, 'EBookingShipment.idEBookingAddress_ShipperAlternate']],
                    'ShipperAddressAlternate',
                ),
                new Join(
                    EBookingAddress::class,
                    EBookingShipment::class,
                    [['ConsigneeAddressAlternate.idEBookingAddress', Operator::equal, 'EBookingShipment.idEBookingAddress_ConsigneeAlternate']],
                    'ConsigneeAddressAlternate',
                ),
                new Join(Incoterm::class,),
                new Join(TransportType::class),
                new Join(User::class, EBookingShipment::class, [['Creator.idUser', Operator::equal, 'EBookingShipment.creator']], 'Creator',),
                new Join(User::class, EBookingShipment::class, [['Modifier.idUser', Operator::equal, 'EBookingShipment.modifier']], 'Modifier',),
            ])
            ->getMultiple(filter: [['EBookingShipment.idEBookingShipment', Operator::equal, $idEBookingShipment]])
            ->getRaw();

        $dataWithDAOMethod = EBookingShipment::createWithColumns(...$columns)
            ->getMultipleEBookingShipment($idEBookingShipment)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/CostCenter | CostCenter::getMultipleCostCenter()
     */
    #[Test]
    public function rebuild_CostCenter_getMultipleCostCenter(): void
    {
        $columns = [
            "CostCenter.idCostCenter AS idCostCenter",
            "CostCenter.costCenterNumber AS costCenterNumber",
            "CostCenter.CostCenterName AS costCenterName",
            "Customer.idCustomer AS idCustomer",
            "Customer.customerName AS customerName",
            "(SELECT GROUP_CONCAT(OfficeLocation.officeLocation) FROM g7system.CostCenterLogistics LEFT JOIN g7portal.OfficeLocation using (idOfficeLocation) WHERE CostCenterLogistics.idCostCenter = CostCenter.idCostCenter) AS officeLocation",
            "(SELECT GROUP_CONCAT(OfficeLocation.idOfficeLocation) FROM g7system.CostCenterLogistics LEFT JOIN g7portal.OfficeLocation using (idOfficeLocation) WHERE CostCenterLogistics.idCostCenter = CostCenter.idCostCenter) AS idOfficeLocation",
            "CostCenter.modifiedOn AS modifiedOn",
            "(select count(*) from g7logistics.CostCenterBarcode where CostCenterBarcode.idCostCenter = CostCenter.idCostCenter AND CostCenterBarcode.deleted = false) AS barcodeCount",
            "CostCenter.activityJournalEnabled AS activityJournalEnabled",
            "(IF(CostCenter.deleted, 'deleted', 'active')) AS status",
        ];

        $dataWithJoin = CostCenter::create()
            ->setColumns(...$columns)
            // Todo: no fk
            ->setJoins([
                new Join(Customer::class, joinConditions: [['Customer.idCustomer', Operator::equal, 'CostCenter.idCustomer']]),
            ])
            ->getMultiple()
            ->getRaw();

        $dataWithDAOMethod = CostCenter::create()
            ->setColumns(...$columns)
            ->getMultipleCostCenter()
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/CustomerLogistics | CustomerLogistics::getMultipleCustomerLogistics()
     */
    #[Test]
    public function rebuild_CustomerLogistics_getMultipleCustomerLogistics(): void
    {
        $idOfficeLocation = 5;
        $columns = [
            'CustomerLogistics.idCustomerLogistics AS idCustomerLogistics',
            'Customer.customerName AS customerName',
            'Customer.customer AS customer',
            'User.fullNameReversed AS creator',
            'CustomerLogistics.createdOn AS createdOn',
        ];
        $filter = [['CustomerLogistics.idOfficeLocation', 'equal', $idOfficeLocation]];
        $sorting = ['Customer.customerName' => 'asc'];

        $dataWithJoin = CustomerLogistics::create()
            ->setColumns(...$columns)
            ->setJoins([
                // todo: no fk
                new Join(Customer::class, joinConditions: [['Customer.idCustomer', Operator::equal, 'CustomerLogistics.idCustomer']]),
                // todo: no fk
                new Join(User::class, joinConditions: [['User.idUser', Operator::equal, 'CustomerLogistics.creator']]),
            ])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = CustomerLogistics::create()
            ->setColumns(...$columns)
            ->getMultipleCustomerLogistics(filter_rules: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/Grid | Grid::getMultipleGrid()
     */
    #[Test]
    public function rebuild_Grid_getMultipleGrid(): void
    {
        $idGrid = 1;
        $columns = [
            'Grid.idGrid',
            'Grid.grid',
            'GridClass.idGridClass',
            'GridClass.gridClass',
        ];

        $dataWithJoin = Grid::create()
            ->setColumns(...$columns)
            ->setJoins([
                new Join(GridClass::class),
                new Join(User::class, joinConditions: [['User.idUser', Operator::equal, 'Grid.creator']]),
                new Join(Authorisation::class, joinConditions: [
                    ['Authorisation.idEntity', Operator::equal, 'Grid.idGrid'],
                    ['Authorisation.idEntityType', Operator::equal, '"grid"'],
                ]),
            ])
            ->getMultiple($idGrid)
            ->getRaw();

        $dataWithDAOMethod = Grid::create()
            ->setColumns(...$columns)
            ->getMultipleGrid($idGrid)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/GroupUser | GroupUser::getUsers()
     */
    #[Test]
    public function rebuild_GroupUser_getUsers(): void
    {
        $idGroup = 1;
        $columns = [
            'User.idUser',
            'User.firstname',
            'User.lastname',
            'GroupUser.idGroup',
        ];
        $filter = [
            ['GroupUser.idGroup', 'equal', $idGroup],
        ];
        $sorting = [
            'User.firstname' => 'asc',
            'User.lastname' => 'asc',
        ];

        $dataWithJoin = GroupUser::create()
            ->setColumns(...$columns)
            ->setJoins([new Join(User::class)])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = GroupUser::create()
            ->setColumns(...$columns)
            ->getUsers(null, null, $filter, $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7system/Locales | Locales::getMultipleWithLanguage()
     */
    #[Test]
    public function rebuild_Locales_getMultipleWithLanguage(): void
    {
        $columns = [
            'Locales.idLocales',
            'Locales.idLanguage',
            'Locales.idCountry',
            'Locales.locales',
            'Locales.supported',
            'Language.idLanguage',
            'Language.languageTranslationKey',
            'Language.language',
            'Country.idCountry',
            'Country.countryTranslationKey',
            'Country.country',
        ];
        $filter = [['Locales.supported', Operator::equal, 1]];

        $dataWithJoin = Locales::createWithColumns(...$columns)
            ->setJoins([
                new Join(Language::class, joinConditions: [['Language.idLanguage', Operator::equal, 'Locales.idLanguage']]),
                new Join(Country::class, joinConditions: [['Country.idCountry', Operator::equal, 'Locales.idCountry']]),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        $dataWithDAOMethod = Locales::createWithColumns(...$columns)
            ->getMultipleWithLanguage(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7podupload/PodUpload | PodUpload::getPodsForApproval()
     */
    #[Test]
    public function rebuild_PodUpload_getPodsForApproval(): void
    {
        $idCustomer = 642;
        $columns = [
            'PodUpload.idPodUpload',
            'PodUpload.positionNumber',
            'PodUpload.email',
            'PodUpload.podFilepath',
            'PodUpload.invoiceFilepath',
            'PodUpload.createdAt',
            'PodUpload.langCode',
            'PodFileApprove.idUser',
            'PodFileApprove.status',
            'PodFileApprove.approvalDate',
            'CargosoftData.positionNumber as cargosoftPositionNumber',
            'Customer.customerName',
            'Customer.idCustomer',
            'User.user',
            'PodFileViewedForApprove.podFileViewed',
            'PodFileViewedForApprove.invoiceFileViewed',
        ];
        $filter = [['Customer.idCustomer', Operator::equal, $idCustomer]];
        $sorting = ['PodUpload.createdAt' => 'desc'];
        $limit = [];
        $specialCaseStatusFilter = "";

        $dataWithJoin = PodUpload::createWithColumns(...$columns)
            ->setJoins([
                new Join(PodFileApprove::class),
                // no fks for positionnumber..
                new Join(CargosoftData::class, joinConditions: [['PodUpload.positionNumber', Operator::equal, 'CargosoftData.positionNumber']]),
                new Join(Shipment::class, joinConditions: [['PodUpload.positionNumber', Operator::equal, 'Shipment.cargosoftReference']]),
                new Join(TrackAndTraceShipment::class, joinConditions: [['PodUpload.positionNumber', Operator::equal, 'TrackAndTraceShipment.cargosoftReference']]),
                new Join(Customer::class, TrackAndTraceShipment::class, [
                    ['TrackAndTraceShipment.idCustomer', Operator::equal, 'Customer.idCustomer', 'OR'],
                    ['Shipment.idCustomer', Operator::equal, 'Customer.idCustomer'],
                ]),
                new Join(User::class, PodFileApprove::class),
                new Join(PodFileViewedForApprove::class),
            ])
            ->getMultiple(filter: [['PodUpload.deletedAt', Operator::isNull], ...$filter], sorting: $sorting, limit: $limit)
            ->getRaw();

        $dataWithDAOMethod = PodUpload::createWithColumns(...$columns)
            ->getPodsForApproval($filter, $sorting, $limit, specialCaseFilter: $specialCaseStatusFilter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7userlist/Databaselist | Databaselist::getMultipleDatabaselist()
     */
    #[Test]
    public function rebuild_Databaselist_getMultipleDatabaselist(): void
    {
        $userDatabasekey = 8;
        $filter = [
            ['idDatabaselist', 'equal', $userDatabasekey],
        ];

        $dataWithJoin = Databaselist::create()
            ->setColumns('*')
            ->setJoins([
                new Join(Deletionmodes::class, joinConditions: [['Deletionmodes.idDeletionmodes', Operator::equal, 'Databaselist.Modekey']]),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        $dataWithDAOMethod = Databaselist::create()
            ->setColumns('*')
            ->getMultipleDatabaselist(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/g7logistics/ParcelServiceBarcode | ParcelServiceBarcode::getMultipleParcelService()
     */
    #[Test]
    public function rebuild_ParcelServiceBarcode_getMultipleParcelService(): void
    {
        $idOfficeLocation = 1;
        $columns = [
            'ParcelServiceBarcode.idParcelServiceBarcode',
            'ParcelServiceBarcode.caption',
            'ParcelService.parcelService',
            'ParcelServiceBarcode.idParcelService',
            'ParcelServiceBarcode.barCode',
        ];
        $filter = [
            ['ParcelServiceBarcode.deleted', Operator::equal, 0],
            ['ParcelServiceLogistics.idOfficeLocation', Operator::equal, $idOfficeLocation],
        ];
        $sorting = [
            'ParcelService.parcelService' => 'ASC',
            'ParcelServiceBarcode.caption' => 'ASC',
        ];

        $dataWithJoin = ParcelServiceBarcode::create()
            ->setColumns(...$columns)
            ->setJoins([
                new Join(ParcelService::class),
                // todo: no fk
                new Join(ParcelServiceLogistics::class, ParcelService::class, [['ParcelService.idParcelService', Operator::equal, 'ParcelServiceLogistics.idParcelService']]),
            ])
            ->getMultiple(filter: $filter, sorting: $sorting)
            ->getRaw();

        $dataWithDAOMethod = ParcelServiceBarcode::create()
            ->setColumns(...$columns)
            ->getMultipleParcelService(filter: $filter, sorting: $sorting)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }

    /**
     * TODO: INFO: #THIS-TEST-IS-TEMPORARY! Remove on refactoring to new Join usage!
     * ./commons/daos/mysql/geoBible/Country | Country::getCountry()
     */
    #[Test]
    public function rebuild_Country_getCountry(): void
    {
        $columns = [
            'Country.idCountry AS idCountry',
            'Country.country AS country',
            'Country.iso3316_alpha2 AS iso3316_alpha2',
        ];
        $filter = [['Country.deleted', Operator::equal, 0]];

        $dataWithJoin = Country::createWithColumns(...$columns)
            ->setJoins([
                new Join(Continent::class, joinConditions: [['Continent.continentCode', Operator::equal, 'Country.continent']]),
            ])
            ->getMultiple(filter: $filter)
            ->getRaw();

        $dataWithDAOMethod = Country::createWithColumns(...$columns)
            ->getCountry(filter: $filter)
            ->getRaw();

        $this->assertIsArray($dataWithJoin);
        $this->assertIsArray($dataWithDAOMethod);
        $this->assertEquals($dataWithJoin, $dataWithDAOMethod);
    }
}

class MockCustomer extends Customer
{
    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        $data = DataInterface::execute($sql, $this->database, $customCallback ?: [$this, 'fetchingRow'], $this->metaData, $this->throwsOnError);
        return new RecordSet([$sql, $data]);
    }
}

// only for debug purposes! usage: change extend Class with desired DAO
class MockDAO extends ParcelServiceBarcode
{
    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        var_dump(trim($sql));
        return new RecordSet([$sql]);
    }
}