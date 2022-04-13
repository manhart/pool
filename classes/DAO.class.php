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
const DAO_NO_QUOTES = 1;
const DAO_NO_ESCAPE = 2;
const DAO_IS_EXPRESSION = 2;

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
 * abstract Data Access Object
 */
abstract class DAO extends PoolObject
{
    //@var string DAO Type
    //@access private
    var $type = null;

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
     * Spalten in detaillierter Form (siehe MySQL: SHOW COLUMNS)
     *
     * @access private
     * @var array
     */
    var $field_list = false;

    /**
     * Enth�lt Anzahl betroffener Zeilen (Rows) ohne Limit
     *
     * @var int $foundRows
     */
    var $foundRows = 0;

    /**
     * Einen Datensatz einfuegen (virtuelle Methode).
     *
     * @access protected
     **/
    abstract public function insert($data): Resultset;

    /**
     * Einen Datensatz aendern (virtuelle Methode).
     **/
    abstract public function update($data): Resultset;

    /**
     * Einen Datensatz loeschen (virtuelle Methode).
     **/
    abstract public function delete($id): Resultset;

    /**
     * @return Resultset
     */
    abstract public function deleteMultiple(): Resultset;

    /**
     * Einen Datensatz zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt Resultset zurueck gegeben.
     **/
    abstract public function get($id, $key=NULL): Resultset;

    /**
     * Mehrere Datensaetze zurueck geben (virtuelle Methode).
     * Datensaetze werden als Objekt Resultset zurueck gegeben.
     **/
    abstract public function getMultiple(): Resultset;

    /**
     * Liefert die Anzahl gefundener Datensaete zurueck (virtuelle Methode).
     * Gleicher Aufbau wie DAO::getMultiple() mit dem Unterschied, es liefert keine riesige Ergebnismenge zurueck,
     * sondern nur die Anzahl.
     **/
    abstract public function getCount(): Resultset;

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
    function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Das Ereignis onSetColumns tritt nachdem aufrufen von setColumns auf (virtuelle Methode).
     * Die gesetzten Spalten koennen hier fuer das jeweilige Speichermedium praepariert werden.
     *
     * @access protected
     **/
    function onSetColumns()
    {
    }

    /**
     * Liefert ein Array mit "allen" Spaltennamen zurueck
     *
     * @abstract
     * @access protected
     **/
    abstract public function getFieldlist(): array;

    /**
     * Liefert den Typ einer Spalte
     *
     * @param string $fieldname
     * @return null
     */
    function getFieldType($fieldname)
    {
        return null;
    }

    /**
     * @param $fieldName
     * @return array
     */
    function getFieldInfo($fieldName): array
    {
        return [];
    }

    function formatData(&$data) {}

    /**
     * Setzt einen Primaer Schluessel. Der Primaer Schluessel findet bei den global Funktionen DAO::get(), DAO::update(), DAO::delete(), ... hauptsaechlich Verwendung.
     *
     * @access public
     * @param string $pk [, mixed ... ]
     * @return bool
     **/
    function setPrimaryKey($pk)
    {
        $this->pk = Array($pk);
        $num_args = func_num_args();
        if ($num_args > 1) {
            for ($a=1; $a<$num_args; $a++) {
                $arg = func_get_arg($a);
                array_push($this->pk, $arg);
            }
        }
        return true;
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
     * Gibt den Typ des Speichermediums zurueck (z.B. dao_mysql, dao_cisam, ...).
     *
     * @return string Typ des Speichermediums
     **/
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Setzt den Typ des Speichermediums.
     *
     * @param string $type Typ des Speichermediums
     **/
    private function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Fraegt den Typ eines Speichermediums beim Objekt ab.
     *
     * @param string $type Typ
     * @return boolean true bei Erfolg, false wenn das Speichermedium nicht mit dem Typ uebereinstimmt.
     **/
    public function isType(string $type): bool
    {
        return $this->getType() == $type;
    }

    /**
     * Gibt den Interface Typ  des Speichermediums zur�ck (z.b. mysql_interface, cisam_interface, c16_interface, ...)
     *
     * @return string Interface Typ des Speichermediums
     */
    function getInterfaceType()
    {
        return $this->interfaceType;
    }

    /**
     * Setzt den Interface Typ des Speichermediums
     *
     * @param string $interfaceType Interface Typ des Speichermediums
     */
    function setInterfaceType($interfaceType)
    {
        $this->interfaceType = $interfaceType;
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
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zur�ck
     *
     * @return int
     */
    function foundRows()
    {
        return 0;
    }

    /**
     * Erzeugt ein Data Access Object (anhand einer Tabellendefinition)
     *
     * @param array|DataInterface $interfaces Schnittstellen zu den Speichermedien (es kann auch ein objekt uebergeben werden, falls man sich sicher ist, dass nur eine Schnittstelle benoetigt wird)
     * @param string $tableDefine Tabellendefinition (siehe database.inc.php)
     * @param boolean $autoload_fields Automatisch Lesen der Spaltendefinitionen
     * @return MySQL_DAO Data Access Object (edited DAO->MySQL_DAO f�r ZDE)
     **/
    public static function createDAO($interfaces, string $tableDefine, bool $autoload_fields=true)
    {
        $type = $dbname = $table = '';
        self::extractTabledefine($tableDefine, $type, $dbname, $table);

        // Interface Objekt
        if (is_array($interfaces)) {
            $interface = $interfaces[$type];
        }
        else {
            $interface = $interfaces;
        }

        switch($type) {
            case DATAINTERFACE_MYSQL:
            case DATAINTERFACE_MYSQLI:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_MYSQL).'/'.$dbname.'/'.utf8_encode($table).'.class.php';
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    $dao = new $table($interface, $dbname, $table, $autoload_fields);
                }
                else {
                    $dao = new CustomMySQL_DAO($interface, $dbname, $table, $autoload_fields);
                }
                $dao->setType($type);
                $dao->setInterfaceType(DATAINTERFACE_MYSQL);
                break;

            case DATAINTERFACE_CISAM:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_CISAM).'/'.utf8_encode($table).'.class.php';
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    $dao = new $table($interface, $table, $autoload_fields);
                }
                else {
                    $dao = new CustomCISAM_DAO($interface, $table, $autoload_fields);
                }
                $dao->setType($type);
                $dao->setInterfaceType(DATAINTERFACE_CISAM);
                break;

            case DATAINTERFACE_C16:
                $include = addEndingSlash(DIR_DAOS_ROOT).addEndingSlash(PWD_TILL_DAOS_C16).$dbname.'/'.utf8_encode($table).'.class.php';
                $file_exists = file_exists($include);
                if (!class_exists($table, false) and $file_exists) {
                    require_once $include;
                }
                if($file_exists) {
                    $class_table = str_replace(' ', '_', $table);
                    $dao = new $class_table($interface, $dbname, $table, $autoload_fields);
                }
                else {
                    $dao = new CustomC16_DAO($interface, $dbname, $table, $autoload_fields);
                }
                $dao->setType($type);
                $dao->setInterfaceType(DATAINTERFACE_C16);
                break;

            default:
                $dao = null;
                if (count($tableDefine) == 0) {
                    $msg = 'Fataler Fehler: ' . sprintf('Tabellendefinition \'%s\' fehlt in der database.inc.php!', $tableDefine);
                }
                else {
                    $msg = 'Fataler Fehler: ' . sprintf('DataInterface Typ \'%s\' der Tabellendefinition \'%s\' unbekannt!', $type, $tableDefine);
                }

                $Xception = new Xception($msg, E_ERROR, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__),
                    POOL_ERROR_DISPLAY|POOL_ERROR_DIE);
                $Xception -> raiseError();
        }
        return $dao;
    }
}