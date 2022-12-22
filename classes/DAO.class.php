<?php declare (strict_types = 1);
/**
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * Die abstrakte base class of the Data Access Objects.
 * Mit DAOs ist es mir gelungen, SQL-Statements aus dem Script zur Anzeige zu entfernen. Dies verkuerzt den Quellcode und entlastet
 * das Script vom Umgang mit Implementierungs-abhaengigen Funktionen des Datenbankherstellers. Der neue Layer kapselt den Zugriff
 * auf das Speichermedium sogar so weit, dass ein Austausch des Speichermediums unproblematisch ist.
 *
 * Siehe dazu auch database.inc.php im DOCUMENT_ROOT des Webservers!!
 *
 * Mit der Funktion DAO::createDAO() werden abgeleitete Data Access Objects erzeugt. Siehe DAO::createDAO() fuer genauere Details.
 * @see DAO::createDAO()
 *
 * @version $Id: DAO.class.php,v 1.12 2007/05/07 11:15:01 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-25
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

#### Prevent auto. quotes
use pool\classes\DAOException;

const DAO_NO_QUOTES = 1;
const DAO_NO_ESCAPE = 2;
// const DAO_IS_EXPRESSION = 4;

const PWD_TILL_DAOS_MYSQL = 'mysql';
const PWD_TILL_DAOS_CISAM = 'cisam';
const PWD_TILL_DAOS_POSTGRESQL = 'postgresql';
const PWD_TILL_DAOS_MSSQL = 'mssql';
const PWD_TILL_DAOS_INFORMIX = 'informix';

#### Datainterface Types:
if(!defined('DATAINTERFACE_MYSQL')) define('DATAINTERFACE_MYSQL', 'MySQL_Interface');
if(!defined('DATAINTERFACE_MYSQLI')) define('DATAINTERFACE_MYSQLI', 'MySQLi_Interface');
if(!defined('DATAINTERFACE_MARIADB')) define('DATAINTERFACE_MARIADB', 'MySQLi_Interface');
if(!defined('DATAINTERFACE_CISAM')) define('DATAINTERFACE_CISAM', 'CISAM_Interface');
if(!defined('DATAINTERFACE_MSSQL')) define('DATAINTERFACE_MSSQL', 'MSSQL_Interface');
if(!defined('DATAINTERFACE_INFORMIX')) define('DATAINTERFACE_INFORMIX', 'Informix_Interface');
if(!defined('DATAINTERFACE_POSQL')) define('DATAINTERFACE_POSQL', 'PostgreSQL_Interface');
if(!defined('DATAINTERFACE_C16')) define('DATAINTERFACE_C16', 'C16_Interface');

/**
 * define general commands for DAO's
 * @todo namespaces
 */
enum Commands {
    case Now;
    case CurrentDate;
    case CurrentTimestamp;
    case Increase;
    case Decrease;
    case Reset;
}

/**
 * abstract Data Access Object
 */
abstract class DAO extends PoolObject
{
    /**
     * @var string interface type
     */
    protected string $interfaceType = '';

    /**
     * columns of table
     *
     * @var array|string[]
     */
    protected array $columns = [];

    /**
     * primary key of table
     *
     * @var array|string[]
     */
    protected array $pk = [];

    /**
     * @var array overwrite this array in the constructor to create the commands needed for the database.
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
            Commands::Increase->name => fn($field) => "$field+1",
            Commands::Decrease->name => fn($field) => "$field-1",
            Commands::Reset->name => fn($field) => "DEFAULT($field)",
        ];
        $this->commands = $commands;
    }

    /**
     * Einen Datensatz einfuegen (virtuelle Methode).
     */
    abstract public function insert(array $data): Resultset;

    /**
     * Einen Datensatz aendern (virtuelle Methode).
     */
    abstract public function update(array $data): Resultset;

    /**
     * Einen Datensatz loeschen (virtuelle Methode).
     */
    abstract public function delete($id): Resultset;

    /**
     * @return Resultset
     */
    abstract public function deleteMultiple(): Resultset;

    /**
     * Einen Datensatz zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt Resultset zurueck gegeben.
     */
    abstract public function get($id, $key=NULL): Resultset;

    /**
     * Mehrere Datensaetze zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt Resultset zurueck gegeben.
     */
    abstract public function getMultiple(): Resultset;

    /**
     * Liefert die Anzahl gefundener Datensaete zurueck (virtuelle Methode).
     * Gleicher Aufbau wie DAO::getMultiple() mit dem Unterschied, es liefert keine riesige Ergebnismenge zurueck,
     * sondern nur die Anzahl.
     */
    abstract public function getCount(): Resultset;

    /**
     * fetches the columns automatically from the driver / interface
     * @return void
     */
    public function fetchColumns(): self {}

    /**
     * Sets the columns you want to query. The event DAO::onSetColumns() is triggered.
     *
     * @param string ...$columns columns
     */
    public function setColumns(string ...$columns): DAO
    {
        $this->columns = $columns;
        $this->onSetColumns();
        return $this;
    }

    /**
     * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
     *
     * @param string $cols Spalten
     * @param string $separator Trenner (Spaltentrenner im String)
     **/
    public function setColumnsAsString(string $cols, string $separator=';'): DAO
    {
        $this->columns = explode($separator, $cols);
        $this->columns = array_map('trim', $this->columns);
        $this->onSetColumns();
        return $this;
    }

    /**
     * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
     *
     * @param array $cols Spalten
     * @return DAO
     */
    public function setColumnsAsArray(array $cols): DAO
    {
        $this->columns = $cols;
        $this->onSetColumns();
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
     * Das Ereignis onSetColumns tritt nachdem aufrufen von setColumns auf (virtuelle Methode).
     * Die gesetzten Spalten koennen hier fuer das jeweilige Speichermedium praepariert werden.
     *
     * @access protected
     **/
    abstract protected function onSetColumns();

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
     * Setzt einen Primaer Schluessel. Der Primaer Schluessel findet bei den global Funktionen DAO::get(), DAO::update(), DAO::delete(), ... hauptsaechlich Verwendung.
     *
     * @param string $pk [, mixed ... ]
     * @return DAO
     */
    public function setPrimaryKey($pk): static
    {
        $this->pk = [$pk];
        $num_args = func_num_args();
        if ($num_args > 1) {
            for ($a=1; $a<$num_args; $a++) {
                $arg = func_get_arg($a);
                $this->pk[] = $arg;
            }
        }
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
     * Gibt den Interface Typ  des Speichermediums zur�ck (z.b. mysql_interface, cisam_interface, c16_interface, ...)
     *
     * @return string Interface Typ des Speichermediums
     */
    protected function getInterfaceType(): string
    {
        return $this->interfaceType;
    }

    /**
     * Setzt den Interface Typ des Speichermediums
     *
     * @param string $interfaceType Interface Typ des Speichermediums
     */
    protected function setInterfaceType(string $interfaceType): static
    {
        $this->interfaceType = $interfaceType;
        return $this;
    }

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
        $interfaceType = $tableDefine[0];
        $dbname = $tableDefine[1];
        $table = $tableDefine[2];
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

        if (is_array($interface)) {
            $interface = $interface[$type];
        }

        switch($type) {
            case DATAINTERFACE_MYSQL:
            case DATAINTERFACE_MYSQLI:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_MYSQL)."$dbname/$table.class.php";
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    /** @var MySQL_DAO $DAO */
                    $DAO = new $table($interface, $dbname, $table);
                }
                else {
                    $DAO = new CustomMySQL_DAO($interface, $dbname, $table);
                }
                break;

            case DATAINTERFACE_CISAM:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_CISAM)."/$table.class.php";
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    /** @var CISAM_DAO $DAO */
                    $DAO = new $table($interface, $table);
                }
                else {
                    $DAO = new CustomCISAM_DAO($interface, $table);
                }
                break;

            case DATAINTERFACE_C16:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_C16)."$dbname/$table.class.php";
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    $class_table = str_replace(' ', '_', $table);
                    /** @var C16_DAO $DAO */
                    $DAO = new $class_table($interface, $dbname, $table, false);
                }
                else {
                    $DAO = new CustomC16_DAO($interface, $dbname, $table, false);
                }
                break;

            default:
                if ($table) {
                    $msg = "Fatal error: DataInterface type $type of table definition $tableDefine unknown!";
                }
                else {
                    $msg = "Fatal error: Table definition $tableDefine is missing in the database.inc.php!";
                }

                throw new DAOException($msg, 1);
        }
        $DAO->setInterfaceType($type);
        if($autoFetchColumns) {
            $DAO->fetchColumns();
        }
        return $DAO;
    }
}