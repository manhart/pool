<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database;

use Closure;
use CustomMySQL_DAO;
use pool\classes\Core\PoolObject;
use pool\classes\Core\RecordSet;
use pool\classes\Core\Weblication;
use pool\classes\Exception\DAOException;

/**
 * Class DAO - Data Access Object
 *
 * @package pool\classes\Database
 * @since 2003/07/10
 */
abstract class DAO extends PoolObject
{
    /**
     * don't quote the value in the (sql) query
     */
    public const DAO_NO_QUOTES = 1;
    /**
     * don't escape the value in the (sql) query
     */
    public const DAO_NO_ESCAPE = 2;

    /**
     * @var string|null name of the interface type (must be declared in derived class)
     */
    protected static ?string $interfaceType = null;

    /**
     * @var string|null Name of the table / file / view (must be declared in derived class)
     */
    protected static ?string $tableName = null;

    /**
     * @var string|null Name of the database (must be declared in derived class)
     */
    protected static ?string $databaseName = null;

    /**
     * @var DataInterface instance of the interface
     */
    protected DataInterface $DataInterface;

    /**
     * @var string Internal Name of the table
     */
    protected string $table;

    /**
     * @var string Internal Name of the database
     */
    protected string $database;

    /**
     * Table meta data
     *
     * @var array
     */
    protected array $metaData = [];

    /**
     * Primary key of table
     *
     * @var array|string[]
     */
    protected array $pk = [];

    /**
     * Columns of table
     *
     * @var array|string[]
     */
    protected array $columns = [];

    /**
     * @var array<string, string|Closure> overwrite this array in the constructor to create the commands needed for the database.
     * @see Commands
     */
    protected array $commands;

    /**
     * Spalten in detaillierter Form (siehe MySQL: SHOW COLUMNS)
     *
     * @var array
     */
    protected array $field_list = [];

    /**
     * Defines the default commands.
     */
    protected function __construct(?DataInterface $DataInterface = null, ?string $databaseName = null, ?string $table = null)
    {
        $this->DataInterface = $DataInterface ?? Weblication::getInstance()->getInterface(static::$interfaceType);
        $this->database ??= $databaseName ?? static::$databaseName;
        $this->table ??= $table ?? static::$tableName;

        $commands = [
            Commands::Now->name => 'NOW()',
            Commands::CurrentDate->name => 'CURRENT_DATE()',
            Commands::CurrentTimestamp->name => 'CURRENT_TIMESTAMP()',
            Commands::CurrentTimestampUs6->name => 'CURRENT_TIMESTAMP(6)',
            Commands::Increase->name => fn($field) => "$field+1",
            Commands::Decrease->name => fn($field) => "$field-1",
            Commands::Reset->name => fn($field) => "DEFAULT($field)",
        ];
        $this->commands = $commands;
    }

    /**
     * Escape a column name
     *
     * @param string $column
     * @return string
     */
    abstract public static function escapeColumn(string $column): string;

    /**
     * @return string
     */
    public static function getDatabaseName(): string
    {
        return static::$databaseName;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return static::$tableName;
    }

    /**
     * Creates a Data Access Object
     */
    public static function create(?string $tableName = null, ?string $databaseName = null, DataInterface|null $DataInterface = null): static
    {
        // class stuff
        if(!$tableName) {
            return new static($DataInterface);
        }

        if(static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }

        $DataInterface = $DataInterface ?? Weblication::getInstance()->getInterface(static::$interfaceType);

        $DAO = new static($DataInterface, $databaseName, $tableName);
        $DAO->fetchColumns();
        return $DAO;
    }

    /**
     * fetches the columns automatically from the driver / interface
     *
     * @return DAO
     */
    abstract public function fetchColumns(): static;

    /**
     * Erzeugt ein Data Access Object (anhand einer Tabellendefinition)
     *
     * @param string|null $tableName table definition or the table name
     * @param string|null $databaseName database name
     * @param \pool\classes\Database\DataInterface|null $DataInterface
     * @return DAO Data Access Object (edited DAO->pool\classes\Database\DAO\MySQL_DAO f�r ZDE)
     * @deprecated use create() instead
     * @see DAO::create()
     */
    public static function createDAO(?string $tableName = null, ?string $databaseName = null, DataInterface|null $DataInterface = null): static
    {
        // @todo remove workaround once relying projects are fixed
        if($tableName && !$databaseName && str_contains($tableName, '_')) {
            [$databaseName, $tableName] = explode('_', $tableName, 2);
        }

        // class stuff
        if(!$tableName) {
            return new static($DataInterface);
        }
        elseif(static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }
        else {
            // workaround
            $className = static::class === DAO::class ? CustomMySQL_DAO::class : static::class;
            $DataInterface = $DataInterface ?? Weblication::getInstance()->getInterface($className::$interfaceType);

            $class_exists = class_exists($tableName, false);

            $driver = $DataInterface->getDriverName();
            $dir = addEndingSlash(DIR_DAOS_ROOT)."$driver/$databaseName";
            $include = "$dir/$tableName.php";
            $file_exists = file_exists($include);
            if(!$class_exists && $file_exists) {
                require_once $include;
                $class_exists = true;
            }
            if($class_exists) {
                return new $tableName($DataInterface, $databaseName, $tableName);
            }

            $DAO = new $className($DataInterface, $databaseName, $tableName);
            $DAO->fetchColumns();
            return $DAO;
        }
    }

    /**
     * Return DataInterface
     *
     * @return DataInterface
     */
    public function getDataInterface(): DataInterface
    {
        return $this->DataInterface;
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Insert a new record based on the data passed as an array, with the key corresponding to the column name.
     */
    abstract public function insert(array $data): RecordSet;

    /**
     * Update a record by primary key (put the primary key in the data array)
     */
    abstract public function update(array $data): RecordSet;

    /**
     * Delete a record by primary key
     */
    abstract public function delete(int|string|array $id): RecordSet;

    /**
     * Delete multiple records at once
     */
    abstract public function deleteMultiple(array $filter_rules = []): RecordSet;

    /**
     * Returns a single record e.g. by primary key
     */
    abstract public function get(int|string|array $id, null|string|array $key = null): RecordSet;

    /**
     * Returns all data records of the assembled SQL statement as a RecordSet
     */
    abstract public function getMultiple(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = [], array $sorting = [], array $limit = [],
        array $groupBy = [], array $having = [], array $options = []): RecordSet;

    /**
     * Returns the number of records of the assembled SQL statement as a RecordSet
     */
    abstract public function getCount(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = []): RecordSet;

    /**
     * Set primary key
     */
    public function setPrimaryKey(string ...$primaryKey): static
    {
        $this->pk = $primaryKey;
        return $this;
    }

    /**
     * Returns primary key
     *
     * @return array primary key
     */
    public function getPrimaryKey(): array
    {
        return $this->pk;
    }

    /**
     * Setzt die Spalten, die abgefragt werden.
     *
     * @param string $columns columns as string with separator
     * @param string $separator Trenner (Spaltentrenner im String)
     **/
    public function setColumnsAsString(string $columns, string $separator = ';'): static
    {
        $this->setColumns(...explode($separator, $columns));
        return $this;
    }

    /**
     * Set columns as array
     */
    public function setColumnsAsArray(array $columns): static
    {
        $this->setColumns(...$columns);
        return $this;
    }

    /**
     * Returns the columns you want to query.
     *
     * @return array Spalten
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets the columns you want to query.
     */
    public function setColumns(string ...$columns): static
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Returns the metadata of the table
     *
     * @param string $which
     * @return array
     */
    public function getMetaData(string $which = ''): array
    {
        return $which ? $this->metaData[$which] : $this->metaData;
    }

    /**
     * Returns a column list of the table with information about the columns
     */
    abstract public function getFieldList(): array;

    /**
     * Liefert den Typ einer Spalte
     *
     * @param string $column
     * @return string
     */
    abstract public function getColumnDataType(string $column): string;

    /**
     * @param string $column
     * @return array
     */
    abstract public function getColumnInfo(string $column): array;

    /**
     * @return int Number of records / rows
     */
    abstract public function foundRows(): int;

    abstract public function fetchingRow(array $row): array;

    /**
     * Executes sql statement and returns RecordSet
     *
     * @param string $sql sql statement to execute
     * @param callable|null $customCallback
     * @return \pool\classes\Core\RecordSet
     */
    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        return $this->getDataInterface()->execute($sql, $this->database, $customCallback ?: [$this, 'fetchingRow'], $this->metaData);
    }
}