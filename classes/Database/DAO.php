<?php
declare (strict_types=1);
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
use MySQL_DAO;
use MySQLi_Interface;
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
     * table meta data
     *
     * @var array
     */
    protected array $metaData = [];

    /**
     * primary key of table
     *
     * @var array|string[]
     */
    protected array $pk = [];

    /**
     * columns of table
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
    public function __construct()
    {
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
    abstract public function getMultiple(mixed $id = null, mixed $key = null, array $filter_rules=[], array $sorting=[], array $limit=[],
        array $groupBy=[], array $having=[], array $options=[]): ResultSet;

    /**
     * Liefert die Anzahl gefundener Datensaete zurueck (virtuelle Methode).
     * Gleicher Aufbau wie DAO::getMultiple() mit dem Unterschied, es liefert keine riesige Ergebnismenge zurueck,
     * sondern nur die Anzahl.
     */
    abstract public function getCount(mixed $id = null, mixed $key = null, array $filter_rules=[]): ResultSet;

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
     * fetches the columns automatically from the driver / interface
     *
     * @return DAO
     */
    abstract public function fetchColumns(): static;

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
     * escape a column name
     * @param string $column
     * @return string
     */
    abstract static function escapeColumn(string $column): string;

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
     * event is triggered when the columns are set
     */
    abstract protected function onSetColumns(array $columns);

    /**
     * Liefert ein Array mit "allen" Spaltennamen zurueck
     */
    abstract public function getFieldList(): array;

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

    // function formatData(&$data) {}

    /**
     * extract definitions of the table variable
     *
     * @param string $tableDefine
     * @param string $interfaceType
     * @param string $dbname
     * @param string $table
     */
    static function extractTabledefine(string $tableDefine, string &$interfaceType, string &$dbname, string &$table): void
    {
        global $$tableDefine;
        $tableDefine = $$tableDefine;
        $interfaceType = $tableDefine[0] ?? '';
        $dbname = $tableDefine[1] ?? '';
        $table = $tableDefine[2] ?? '';
    }

    /**
     * @return int number of records / rows
     */
    abstract public function foundRows(): int;

    /**
     * Erzeugt ein Data Access Object (anhand einer Tabellendefinition)
     *
     * @param string $tableDefine Tabellendefinition (siehe database.inc.php)
     * @param null $interface
     * @param bool $autoFetchColumns
     * @return DAO Data Access Object (edited DAO->MySQL_DAO f�r ZDE)
     *
     * @throws DAOException
     */
    public static function createDAO(string $tableDefine, $interface = null, bool $autoFetchColumns = false): DAO
    {
        $type = $dbname = $table = '';
        self::extractTabledefine($tableDefine, $type, $dbname, $table);

        // Interface Objekt
        $interface = $interface ?? Weblication::getInstance()->getInterfaces();

        if(is_array($interface)) {
            $interface = $interface[$type] ?? null;
        }

        // @todo remove switch
        switch($type) {
            case MySQLi_Interface::class:
                $class_exists = class_exists($table, false);

                /** @var $type DataInterface */
                $driver = $type::getDriverName();
                $dir = addEndingSlash(DIR_DAOS_ROOT) . "$driver/$dbname";
                $include = "$dir/$table.class.php";
                $file_exists = file_exists($include);
                if(!$class_exists && !$file_exists) {
                    $include = "$dir/$table.php";
                    $file_exists = file_exists($include);
                }
                if(!$class_exists && $file_exists) {
                    require_once $include;
                    $class_exists = true;
                }
                if($class_exists) {
                    /** @var MySQL_DAO $DAO */
                    $DAO = new $table($interface, $dbname, $table);
                }
                else {
                    // @todo use $driver
                    // $className = 'Custom'.$driver.'_DAO';
                    $DAO = new CustomMySQL_DAO($interface, $dbname, $table);
                }
                break;

            default:
                if($table) {
                    $msg = "Fatal error: DataInterface type $type of table definition $tableDefine unknown!";
                }
                else {
                    $msg = "Fatal error: Table definition $tableDefine is missing in the database.inc.php!";
                }

                throw new DAOException($msg, 1);
        }
        if($autoFetchColumns) {
            $DAO->fetchColumns();
        }
        return $DAO;
    }
}