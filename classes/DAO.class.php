<?php
/**
 * Dao.class.php
 *
 * Die abstrakte Grundklasse aller Data Access Objects.
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
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CLASS_DAO')) {

    #### Prevent multiple loading
    define('CLASS_DAO', 1);

    #### Prevent auto. quotes
    define('DAO_NO_QUOTES', 1);
    define('DAO_NO_ESCAPE', 2);

    define('PWD_TILL_DAOS_MYSQL', 'mysql');
    define('PWD_TILL_DAOS_CISAM', 'cisam');
    define('PWD_TILL_DAOS_POSTGRESQL', 'postgresql');

    #### Datainterface Types:
    if(!defined('DATAINTERFACE_MYSQL')) define('DATAINTERFACE_MYSQL', 'MySQL_Interface');
    if(!defined('DATAINTERFACE_MYSQLI')) define('DATAINTERFACE_MYSQLI', 'MySQLi_Interface');
    if(!defined('DATAINTERFACE_CISAM')) define('DATAINTERFACE_CISAM', 'CISAM_Interface');
    if(!defined('DATAINTERFACE_MSSQL')) define('DATAINTERFACE_MSSQL', 'MSSQL_Interface');
    if(!defined('DATAINTERFACE_POSQL')) define('DATAINTERFACE_POSQL',	'PostgreSQL_Interface');
    if(!defined('DATAINTERFACE_C16')) define('DATAINTERFACE_C16',			'C16_Interface');

    /**
     * DAO
     *
     * Abstract Data Access Object (siehe Dateibeschreibung fuer mehr Informationen).
     *
     * @package rml
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: DAO.class.php,v 1.12 2007/05/07 11:15:01 manhart Exp $
     * @access public
     **/
    class DAO extends PoolObject
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
         * Konstruktor
         *
         * @access protected
         **/
        function __construct()
        {
        }

        /**
         * Einen Datensatz einfuegen (virtuelle Methode).
         *
         * @access protected
         **/
        function insert($data)
        {
        }

        /**
         * Einen Datensatz aendern (virtuelle Methode).
         *
         * @access protected
         **/
        function update($data)
        {
        }

        /**
         * Einen Datensatz loeschen (virtuelle Methode).
         *
         * @access protected
         **/
        function delete($id)
        {
        }

        /**
         * Einen Datensatz zurueck geben (virtuelle Methode).
         * Datensaetze werden als Objekt Resultset zurueck gegeben.
         *
         * @access protected
         **/
        function get($id, $key=NULL)
        {
        }

        /**
         * Mehrere Datensaetze zurueck geben (virtuelle Methode).
         * Datensaetze werden als Objekt Resultset zurueck gegeben.
         *
         * @access protected
         **/
        function getMultiple()
        {
        }

        /**
         * Liefert die Anzahl gefundener Datensaete zurueck (virtuelle Methode).
         * Gleicher Aufbau wie DAO::getMultiple() mit dem Unterschied, es liefert keine riesige Ergebnismenge zurueck,
         * sondern nur die Anzahl.
         *
         * @access protected
         **/
        function getCount()
        {
        }

        /**
         * Setzt die Spalten die man abfragen moechte. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
         *
         * @access public
         * @param string $col [, mixed ... ]
         **/
        function setColumns($col)
        {
            $this->columns = Array($col);
            $num_args = func_num_args();
            if ($num_args >= 2) {
                for ($a=1; $a < $num_args; $a++) {
                    $arg = func_get_arg($a);
                    if ($arg != '') {
                        array_push($this->columns, $arg);
                    }
                }
            }
            $this->onSetColumns();
        }

        /**
         * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
         *
         * @access public
         * @param string $cols Spalten
         * @param string $separator Trenner (Spaltentrenner im String)
         **/
        function setColumnsAsString($cols, $separator=';')
        {
            $this->columns = explode($separator, $cols);
            $this->columns = array_map('trim', $this->columns);
            $this->onSetColumns();
        }

        /**
         * Setzt die Spalten, die abgefragt werden. Dabei wird das Ereignis DAO::onSetColumns() ausgeloest.
         *
         * @access public
         * @param array $cols Spalten
         * @return null
         **/
        function setColumnsAsArray($cols)
        {
            $this -> columns = $cols;
            return $this -> onSetColumns();
        }

        /**
         * Liefert ein Array mit Spaltennamen zurueck (nicht unbedingt alle Spalten der Tabelle)
         *
         * @return array Spalten
         **/
        function getColumns()
        {
            return $this -> columns;
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
        function getFieldlist()
        {
        }

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

        function getFieldinfo($fieldname)
        {
            return false;
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
         * Liefert den Prim�rschl�ssel als Array
         *
         * @access public
         * @return array Primaer Schluessel
         **/
        function getPrimaryKey()
        {
            return $this->pk;
        }

        /**
         * Gibt den Typ des Speichermediums zurueck (z.B. dao_mysql, dao_cisam, ...).
         *
         * @access public
         * @return string Typ des Speichermediums
         **/
        function getType()
        {
            return $this->type;
        }

        /**
         * Setzt den Typ des Speichermediums.
         *
         * @access private
         * @param string $type Typ des Speichermediums
         **/
        function setType($type)
        {
            $this->type = $type;
        }

        /**
         * Fraegt den Typ eines Speichermediums beim Objekt ab.
         *
         * @access public
         * @param string $type Typ
         * @return boolean true bei Erfolg, false wenn das Speichermedium nicht mit dem Typ uebereinstimmt.
         **/
        function isType($type)
        {
            return $this->type == strtolower($type);
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
         * @param string $tabledefine
         * @param string $interfaceType
         * @param string $dbname
         * @param string $table
         * @return string name of tabledefine
         */
        static function extractTabledefine(string $tabledefine, string &$interfaceType, string &$dbname, string &$table): string
        {
            global $$tabledefine;
            $tabledefine = $$tabledefine;
            $interfaceType = $tabledefine[0];
            $dbname = $tabledefine[1];
            $table = $tabledefine[2];
            return $tabledefine;
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
         * @param string $tabledefine Tabellendefinition (siehe database.inc.php)
         * @param boolean $autoload_fields Automatisch Lesen der Spaltendefinitionen
         * @return MySQL_DAO Data Access Object (edited DAO->MySQL_DAO f�r ZDE)
         **/
        public static function &createDAO($interfaces, string $tabledefine, $autoload_fields=true)
        {
            $type = $dbname = $table = '';
            $nameOfTabledefine = self::extractTabledefine($tabledefine, $type, $dbname, $table);

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
                    if (count($tabledefine) == 0) {
                        $msg = 'Fataler Fehler: ' . sprintf('Tabellendefinition \'%s\' fehlt in der database.inc.php!', $tabledefine);
                    }
                    else {
                        $msg = 'Fataler Fehler: ' . sprintf('DataInterface Typ \'%s\' der Tabellendefinition \'%s\' unbekannt!', $type, $nameOfTabledefine);
                    }

                    $Xception = new Xception($msg, E_ERROR, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__),
                        POOL_ERROR_DISPLAY|POOL_ERROR_DIE);
                    $Xception -> raiseError();
            }
            return $dao;
        }
    }
}