<?php declare (strict_types = 1);
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
use pool\classes\Core\Weblication;
use pool\classes\Exception\DAOException;
use ResultSet;

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
     * @var string|null name of the table / file / view
     */
    protected static ?string $tableName = null;

    /**
     * @var string|null
     */
    protected static ?string $databaseName = null;

    /**
     * @var DataInterface instance of the interface
     */
    protected DataInterface $DataInterface;

    /**
     * @var string
     */
    protected string $table;

    /**
     * @var string name of the database (must be declared in derived class)
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
    abstract static function escapeColumn(string $column): string;

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
        elseif(static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }
        else {
            $DataInterface = $DataInterface ?? Weblication::getInstance()->getInterface(static::$interfaceType);

            $DAO = new static($DataInterface, $databaseName, $tableName);
            $DAO->fetchColumns();
            return $DAO;
        }
    }

    /**
     * Erzeugt ein Data Access Object (anhand einer Tabellendefinition)
     *
     * @param string|null $tableName table definition or the table name
     * @param string|null $databaseName database name
     * @param \pool\classes\Database\DataInterface|null $DataInterface
     * @return DAO Data Access Object (edited DAO->MySQL_DAO f�r ZDE)
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
     * fetches the columns automatically from the driver / interface
     *
     * @return DAO
     */
    abstract public function fetchColumns(): static;

    /**
     * Einen Datensatz einfuegen (virtuelle Methode).
     */
    abstract public function insert(array $data): ResultSet;

    /**
     * Einen Datensatz aendern (virtuelle Methode).
     */
    abstract public function update(array $data): ResultSet;

    /**
     * Einen Datensatz loeschen (virtuelle Methode).
     */
    abstract public function delete($id): ResultSet;

    /**
     * @return ResultSet
     */
    abstract public function deleteMultiple(): ResultSet;

    /**
     * Einen Datensatz zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt ResultSet zurueck gegeben.
     */
    abstract public function get($id, $key = null): ResultSet;

    /**
     * Mehrere Datensaetze zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt ResultSet zurueck gegeben.
     */
    abstract public function getMultiple(mixed $id = null, mixed $key = null, array $filter_rules = [], array $sorting = [], array $limit = [],
        array $groupBy = [], array $having = [], array $options = []): ResultSet;

    /**
     * Liefert die Anzahl gefundener Datensaete zurueck (virtuelle Methode).
     * Gleicher Aufbau wie DAO::getMultiple() mit dem Unterschied, es liefert keine riesige Ergebnismenge zurueck,
     * sondern nur die Anzahl.
     */
    abstract public function getCount(mixed $id = null, mixed $key = null, array $filter_rules = []): ResultSet;

    /**
     * set primary key
     *
     * @param string ...$primaryKey
     * @return DAO
     */
    public function setPrimaryKey(string ...$primaryKey): static
    {
        $this->pk = $primaryKey;
        return $this;
    }

    /**
     * returns primary key
     *
     * @return array primary key
     **/
    public function getPrimaryKey(): array
    {
        return $this->pk;
    }

    /**
     * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
     *
     * @param string $columns columns as string with separator
     * @param string $separator Trenner (Spaltentrenner im String)
     **/
    public function setColumnsAsString(string $columns, string $separator = ';'): DAO
    {
        $this->columns = explode($separator, $columns);
        $this->onSetColumns($this->columns);
        return $this;
    }

    /**
     * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
     *
     * @param array $columns Spalten
     * @return DAO
     */
    public function setColumnsAsArray(array $columns): DAO
    {
        $this->columns = $columns;
        $this->onSetColumns($columns);
        return $this;
    }

    /**
     * Liefert ein Array mit Spaltennamen zurueck (nicht unbedingt alle Spalten der Tabelle)
     *
     * @return array Spalten
     **/
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Sets the columns you want to query. The event DAO::onSetColumns() is triggered.
     *
     * @param string ...$columns columns
     */
    public function setColumns(string ...$columns): DAO
    {
        $this->columns = $columns;
        $this->onSetColumns($columns);
        return $this;
    }

    /**
     * event is triggered when the columns are set
     */
    abstract protected function onSetColumns(array $columns);

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
     * Liefert ein Array mit "allen" Spaltennamen zurueck
     */
    abstract public function getFieldList(): array;

    // function formatData(&$data) {}

    /**
     * Liefert den Typ einer Spalte
     *
     * @param string $fieldName
     * @return string
     */
    abstract public function getFieldType(string $fieldName): string;

    /**
     * @param string $fieldName
     * @return array
     */
    abstract public function getFieldInfo(string $fieldName): array;

    /**
     * @return int number of records / rows
     */
    abstract public function foundRows(): int;
}