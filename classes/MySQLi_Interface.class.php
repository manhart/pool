<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * Class MySQLi_Interface ist ein Datenbank-Layer fuer MySQLi.
 * Diese Klasse implementiert die Schnittstelle zu MySQL. Ueber
 * sie ist der Aufbau einer Verbindung moeglich. Sie behandelt
 * alle MySQL spezifischen PHP API Befehle (z.B. mysqli_connect).
 *
 * Dabei kapselt sie nicht nur einfach die API Befehle, sondern
 * beherrscht eine komplette Verbindungskennung-Verwaltung
 * zum Resourcen-Sharing.
 * Sie liefert eine Statistik ueber Anzahl der ausgefuehrten
 * Queries in einem Script, hilft beim Debug durch den Log des
 * zuletzt ausgefuehrten SQL Statements und das geilste:
 * sie kann mit einer Cluster-Datenbank, die in ein
 * Schreib und Lese-Cluster aufgeteilt ist, umgehen!!!
 *
 * Verbindungen werden nur einmalig geoffnet und am Ende
 * der Script Ausfuehrung ueber MySQL_Interface::close
 * geschlossen.
 *
 *
 * @version $Id: MySQLi_Interface.class.php 38690 2019-09-03 15:08:59Z manhart $
 * @version $Revision: 38690 $
 *
 * @see DataInterface.class.php
 * @since 2019/02/28
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://www.manhart-it.de
 */

use pool\classes\Database\DataInterface;

if (!defined('SQL_READ')) define('SQL_READ', 'READ');
if (!defined('SQL_WRITE')) define('SQL_WRITE', 'WRITE');

/**
 * MySQLi_Interface
 *
 * MySQL Datenbank Layer (Schnittstelle zum MySQL Server)
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: MySQLi_Interface.class.php 38690 2019-09-03 15:08:59Z manhart $
 **/
class MySQLi_Interface extends DataInterface
{
    /**
     * @var array Array of MySQL Links der Default Datenbank (wird mit dem Constructor bestimmt), Aufbau $var[$mode]["default"]
     */
    private $db_connect_id = array(SQL_READ => array(), SQL_WRITE => array());

    private array $commands = ['SELECT', 'SHOW', 'INSERT', 'UPDATE', 'DELETE', 'EXPLAIN', 'ALTER', 'CREATE', 'DROP', 'RENAME',
            'CALL', 'REPLACE', 'TRUNCATE', 'LOAD', 'HANDLER', 'DESCRIBE', 'START', 'COMMIT', 'ROLLBACK',
            'LOCK', 'SET', 'STOP', 'RESET', 'CHANGE', 'PREPARE', 'EXECUTE', 'DEALLOCATE', 'DECLARE', 'OPTIMIZE'];

    //@var resource Letzter benutzer MySQL Link
    //@access private
    var $last_connect_id;

    //@var string Letzter ausgefuehrter Query;
    //@access private
    var $sql; // for pray to see the active/last sql statement;

    //@var array Saves fetched Mysql results
    //@access private
    var $row = array();

    //@var array Saves fetched Mysql rowsets
    //@access private
    var $rowset = array();

    //@var integer Anzahl insgesamt ausgefuehrter Queries
    //@access private
    var $num_queries = 0;

    //@var integer Anzahl insgesamt ausgefuehrter Lesevorgaenge
    //@access private
    var $num_local_queries = 0;

    //@var integer Anzahl insgesamt ausgefuehrter Schreibvorgaenge
    //@access private
    var $num_remote_queries = 0;

    //@var array Enthaelt ein Array bestehend aus zwei Hosts für Lese- und Schreibvorgaenge. Sie werden für die Verbindungsherstellung genutzt.
    //@access private
    var $host = array();

    //@var string Enthaelt den Variablennamen des Authentication-Arrays; Der Variablenname wird vor dem Connect aufgeloest; Das Database Objekt soll keine USER und PASSWOERTER intern speichern. Vorsicht wegem ERRORHANDLER!
    //@access private
    var $auth = "";

    //@var array Array of Mysql Links; Aufbau $var[$mode][$database] = resource
    //@access private
    var $connections = array(SQL_READ => array(), SQL_WRITE => array());

    /**
     * Alle verfuegbaren Master u. Slave Hosts
     *
     * @var array|string
     */
    var $available_hosts = array();

    /**
     * Erzwingt Lesevorgaenge ueber den Master-Host für Schreibvorgaenge (Wird gebraucht, wenn geschrieben wird, anschließend wieder gelesen. Die Replikation hinkt etwas nach.)
     *
     * @access public
     * @var boolean
     */
    var $force_backend_read = false;

    //@var string Standard Datenbank
    //@access private
    var $default_database = '';

    /**
     * Speichert das Query Result zwischen
     *
     * @var mysqli_result|true|false
     */
    var $query_result = false;

    /**
     * Zuletzt ausgefuehrtes SQL Kommando
     *
     * @var string
     */
    var $last_command = '';

    /**
     * Zeichensatz fuer die MySQL Verbindung
     *
     * @var string
     */
    var $default_charset = '';

    /**
     * @var int Port
     */
    var $port = 3306;

    /**
     * class constants
     */
    const ZERO_DATE = '0000-00-00';
    const ZERO_TIME = '00:00:00';
    const ZERO_DATETIME = '0000-00-00 00:00:00';
    const MAX_DATE = '9999-12-31';
    const MAX_DATETIME = '9999-12-31 23:59:59';

    /**
     * @return string name of the driver. it is used to identify the driver in the configuration and for the factory to load the correct data access objects
     */
    public static function getDriverName(): string
    {
        return 'mysql';
    }

    /**
     * Sets up the object.
     *
     * Einstellungen:
     *
     * persistency = (boolean) Persistente Verbindung (Default true)
     * host = (array)|(string) Hosts des MySQL Servers (es koennen auch Cluster bedient werden host[0] = read; host[1] = write)
     * database = (string) Standard Datenbank
     * auth = (array) Authentication Array, Default 'mysql_auth' (siehe access.inc.php)
     *
     * @param array $connectionOptions Einstellungen
     * @return boolean Erfolgsstatus
     **/
    public function setOptions(array $connectionOptions): bool
    {
        // $this->persistency = array_key_exists('persistency', $Packet) ? $Packet['persistency'] : false;
        $this->force_backend_read =  $connectionOptions['force_backend_read'] ?? false;

        if (!array_key_exists('host', $connectionOptions)) {
            $this->raiseError(__FILE__, __LINE__, 'MySQL_Interface::setOptions Bad Packet: no key "host"');
            return false;
        }
        $this->available_hosts = $connectionOptions['host'];

        if (!array_key_exists('database', $connectionOptions)) {
            $this->raiseError(__FILE__, __LINE__, 'MySQL_Interface::setOptions Bad Packet: no key "database"');
            return false;
        }
        $this->default_database = $connectionOptions['database'];

        if (array_key_exists('port', $connectionOptions)) {
            $this->port = $connectionOptions['port'];
        }

        $this->auth = $connectionOptions['auth'] ?? 'mysql_auth';// fallback verwendet zentrale, globale Authentifizierung

        if (array_key_exists('charset', $connectionOptions)) {
            $this->default_charset = $connectionOptions['charset'];
        }

        $this->__findHostForConnection();

        return true;
    }

    /**
     * Nimmt nach dem Zufallsprinzip einen Server-Host fuer die Verbindung
     *
     * @param bool $connect
     * @param null $database
     * @param null $mode
     * @return bool|mysqli|null
     */
    function __findHostForConnection(bool $connect = false, $database = null, $mode = null): bool|mysqli|null
    {
        $available_hosts =& $this->available_hosts;
        if (is_array($available_hosts)) {
            /**MySQL Server aufgeteilt in Lesecluster und Schreibcluster */
            mt_srand(getMicrotime(10000));
            $targetMode = SQL_READ;
            $modeKey = 0;
            /** @var array|null $hostList reference to hosts available in this mode */
            $hostList =& $available_hosts[$targetMode];
            if ($mode == $targetMode || (!$mode && $hostList)) {
                $key = mt_rand(1, sizeof($hostList)) - 1;
                //$key = array_rand($this->available_hosts[SQL_READ]);
                $host = $hostList[$key];
                unset($hostList[$key]);//remove option
                $hostList = array_values($hostList);//reindex
            } else
                $host = $available_hosts[$modeKey] ?? false;
            if ($host) $this->host[$targetMode] = $host;

            $targetMode = SQL_WRITE;
            $modeKey = 1;
            /** @var array|null $hostList reference to hosts available in this mode */
            $hostList =& $available_hosts[$targetMode];
            if ($mode == $targetMode || (!$mode && $hostList)) {
                $key = mt_rand(1, sizeof($hostList)) - 1;
                //$key = array_rand($this->available_hosts[SQL_WRITE]);
                $host = $hostList[$key];
                unset($hostList[$key]);
            } else
                $host = $available_hosts[$modeKey] ?? false;
            if ($host) $this->host[$targetMode] = $host;
        } elseif (is_string($available_hosts)) {
            /**Ein MySQL Server fuer Lesen und Schreiben*/
            $this->host = array(
                SQL_READ => $available_hosts,
                SQL_WRITE => $available_hosts
            );
        }
        return $connect && $database && $mode ? //attempt connection?
            $this->__get_db_conid($database, $mode) : true;
    }

    /**
     * Ermittelt, ob noch Master-/Slave Hosts zur Verfuegung stehen
     *
     * @param string $mode
     * @return boolean
     */
    function hasAnotherHost(string $mode): bool
    {
        return (is_array($hosts = $this->available_hosts[$mode]??0)
            && sizeof($hosts)>0);
    }


    private array $authentications = [];

    /**
     * MySQL_Interface::__get_auth()
     *
     * Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
     *
     * @param string $mode constant Beschreibt den Zugriffsmodus Schreib-Lesevorgang
     * @return Array mit Key username und password
     */
    private function __get_auth(string $mode)
    {
        $name_of_array = $this->auth;

        if(!isset($this->authentications[$name_of_array])) {
            if (file_exists($authFile = constant('DBACCESSFILE'))) {
                include $authFile;
                if(isset($$name_of_array)) {
                    $this->authentications[$name_of_array] = $$name_of_array;
                }
            }
        }

        $authentication = $this->authentications[$name_of_array] ?? [];

        if ($mode == SQL_READ) {
            if (array_key_exists($this->host[SQL_READ], $authentication)) {
                return $authentication[$this->host[SQL_READ]];
            }
        }
        else {
            if (array_key_exists($this->host[SQL_READ], $authentication)) {
                return $authentication[$this->host[SQL_WRITE]];
            }
        }

        $this->raiseError(__FILE__, __LINE__, 'MySQL access denied! No authentication data available ' .
            '(Database: ' . $this->host[$mode] . ' Mode: ' . $mode . ').');
        return $authentication;
    }

    /**
     * MySQL_Interface::__get_db_pass()
     *
     * Holt die Authentication-Daten und gibt das Passwort zurueck
     *
     * @param string $database Datenbank
     * @param string $mode  Lese- oder Schreibmodus
     * @return string Gibt das Passwort zurueck
     *
     * @access private
     */
    function __get_db_pass($database, $mode)
    {
        $auth = $this->__get_auth($mode);
        if (array_key_exists('all', $auth))
            $database = 'all'; // Special
        return $auth[$database]['password'] ?? '';
    }

    /**
     * __get_db_user()
     *
     * Holt die Authentication-Daten und gibt den Usernamen zurück
     *
     * @param $database string Datenbank
     * @param string $mode constant Lese- oder Schreibmodus
     * @return string Gibt den Usernamen zurück
     *
     * @access private
     */
    function __get_db_user($database, $mode)
    {
        $auth = $this->__get_auth($mode);

        $user = '';
        if (array_key_exists('all', $auth)) {
            $database = 'all'; // Special
        }
        if (array_key_exists($database, $auth)) {
            $user = $auth[$database]['username'];
        }
        return $user;
    }

    /**
     * __get_db_conid()
     *
     * Stellt eine MySQL Verbindung her. Falls die Verbindungs-Kennung bereits existiert,
     * wird die vorhandene Verbindung verwendet (Resourcen-Sharing)
     *
     * @param $database string Datenbank
     * @param $mode string Lese- oder Schreibmodus
     * @return bool|mysqli|null Gibt Resource der MySQL Verbindung zurueck
     *
     * @access private
     */
    function __get_db_conid(string $database, string $mode): bool|mysqli|null
    {
        if (!($database || ($database = $this->default_database))) {//No DB specified
            $this->raiseError(__FILE__, __LINE__, 'No database selected (__get_db_conid)!');
            return false;
        }
        if ($this->host[SQL_READ] == $this->host[SQL_WRITE])
            $mode = SQL_READ; // same as WRITE
        $conid = $this->connections[$mode][$database] ?? false;//fetch from cache
        if ($conid) //done
            return $conid;
        //open new DB connection
        $host = $this->host[$mode] . ':' . $this->port;
        $conid = mysqli_connect($host, $this->__get_db_user($database, $mode), $this->__get_db_pass($database, $mode), '', $this->port);
        if (constant('LOG_ENABLED') && constant('ACTIVATE_INTERFACE_SQL_LOG') >= 1 && ($Log = Singleton('Log'))->isLogging()) {
            //Logging enabled
            $sqlTarget = "TO $host MODE: $mode DB: $database";
            $Log->addLine(($conid) ? "CONNECTED " . $sqlTarget :
                "FAILED TO CONNECT $sqlTarget (MySQL-Error: " . mysqli_connect_errno() . ': ' . mysqli_connect_error() . ')');
        }
        if ($conid) {//success
            // Standard Zeichensatz fuer die Verbindung setzen
            if ($this->default_charset && !$this->_setNames($this->default_charset, $conid))
                $this->raiseError(__FILE__, __LINE__, 'MySQL ErrNo ' . mysqli_errno($conid) . ': ' . mysqli_error($conid));
            if (@mysqli_select_db($conid, $database)) {//set default and store connection
                $this->connections[$mode][$database] = $conid;
                return $conid;
            } else {//failed to set default database
                $this->raiseError(__FILE__, __LINE__, mysqli_error($conid));
                mysqli_close($conid);//abort connection
                return null;//?
            }
        } else if ($this->hasAnotherHost($mode))//connection failed but Alternative exists
            return $this->__findHostForConnection(true, $database, $mode);//potentially recursive
        else {//no alternative
            $errorMsg = "MySQL connection to host '$host' with mode $mode failed! Used default database '$database' (MySQL ErrNo "
                . mysqli_connect_errno() . ': ' . mysqli_connect_error() . ')!';
            $this->raiseError(__FILE__, __LINE__, $errorMsg);
            return false;
        }
    }

    /**
     * Baut eine Verbindung zur Datenbank auf.
     *
     * @param string $database Datenbank
     */
    public function open(string $database = ''): bool
    {
        $result = $this->__get_db_conid($database, SQL_READ);
        if ($result != false and $this->host[SQL_READ] != $this->host[SQL_WRITE]) {
            $this->__get_db_conid($database, SQL_WRITE);
        }
        return ($this->isConnected($database, SQL_READ) and $this->isConnected($database, SQL_WRITE));
    }

    /**
     * Ueberprueft ob eine MySQL Verbindung besteht und baut verloren gegangene Verbindung wieder auf (bis PHP 5.0.13)
     *
     * @param string $database Datenbank
     * @param string $mode Lese- oder Schreibmodus
     * @return boolean Gibt TRUE/FALSE zurueck
     */
    public function isConnected(string $database = '', string $mode = SQL_READ): bool
    {
        if ($mode == '') {
            $mode = SQL_READ;
        }
        elseif ($this->host[SQL_READ] == $this->host[SQL_WRITE]) {
            $mode = SQL_READ; // same as host
        }
        if ($database == '') {
            $database = $this->default_database;
        }

        if (!isset($this->connections[$mode]) or
            !isset($this->connections[$mode][$database]) or
            ($this->connections[$mode][$database]->connect_error)) {
            return false;
        }
        return mysqli_ping($this->connections[$mode][$database]);
    }

    /**
     * Schliesst alle Verbindungs-Kennungen.
     *
     * @return boolean true
     **/
    public function close(): bool
    {
        if (is_array($this->connections[SQL_READ])) {
            foreach ($this->connections[SQL_READ] as $database => $conid) {
                // workaround, sonst schlaegt die Schleife mit SQL_WRITE fehl.
                if ((isset($this->connections[SQL_WRITE][$database])) and ($this->connections[SQL_READ][$database] == $this->connections[SQL_WRITE][$database])) {
                    unset($this->connections[SQL_WRITE][$database]);
                }
                if ($conid instanceof mysqli) {
                    @mysqli_close($conid);
                }
                unset($this->connections[SQL_READ][$database]);
            }
        }

        if (is_array($this->connections[SQL_WRITE])) {
            foreach ($this->connections[SQL_WRITE] as $database => $conid) {
                if ($conid instanceof mysqli) {
                    @mysqli_close($conid);
                }
                unset($this->connections[SQL_WRITE][$database]);
            }
        }
        return true;
    }

    /**
     * Fuehrt ein SQL-Statement aus.<br>
     * Saves query to this->sql<br>
     * Resets query_result<br>
     * Gets a conid and saves it to last_connect_id<br>
     * Updates last command on success
     * @access public
     * @param string $query SQL-Statement
     * @param string $database Datenbankname (default '')
     * @return bool|mysqli_result Erfolgsstatus
     * @see MySQLi_Interface::__get_db_conid
     **/
    function query(string $query, string $database = ''): mysqli_result|bool
    {
        //Store query in attribute
        $this->sql = ltrim($query);
        // reset query result
        $this->query_result = false;
        if (!$this->sql)//nothing to do
            return false;
        //identify command
        $offset = strspn($this->sql, '( \n\t\r');//skip to the meat
        //find position of first ?whitespace?, starting from magic value 2 from old code
        $pos = strcspn($this->sql, ' \n\r\t', $offset + 2);
        $command = strtoupper(substr($this->sql, $offset, $pos));//cut command from Query
        if (IS_TESTSERVER && !in_array($command, $this->commands))
            echo "Unknown command: '$command'<br>" .
                "in $this->sql<hr>" .
                'Please contact Alexander Manhart for MySQL_Interface in the function query()';
        $isSELECT = ($command == 'SELECT');//mode selection
        $mode = !$isSELECT || $this->force_backend_read ? SQL_WRITE : SQL_READ;
        if ($isSELECT)
            $this->num_local_queries++;
        else
            $this->num_remote_queries++;
        $this->num_queries++;
        $conid = $this->__get_db_conid($database, $mode);//connect
        if (!$conid)//cant connect
            return false;
        $this->query_result = @mysqli_query($conid, $this->sql);//run
        $this->last_connect_id = $conid;
        if ($this->query_result)//Query successful
            $this->last_command = $command;
        if (constant('LOG_ENABLED') && constant('ACTIVATE_INTERFACE_SQL_LOG') == 2 && ($Log = Singleton('Log'))->isLogging())
            //Logging enabled
            $Log->addLine('SQL MODE: ' . $mode);
        return $this->query_result;
    }

    /**
     * Liefert zuletzt ausgeführtes SQL Kommando in Großbuchstaben z.B. SELECT
     *
     * @return string
     */
    function getLastSQLCommand()
    {
        return $this->last_command;
    }

    /**
     * Anzahl gefundener Datensaetze (Rows)
     *
     * @param mysqli_result|false $query_result Query Ergebnis-Kennung
     * @return integer Bei Erfolg einen Integer, bei Misserfolg false
     **/
    public function numrows($query_result = false)
    {
        if (!$query_result) {
            if (isset($this->query_result)) {
                $query_result = $this->query_result;
            }
        }

        $numrows = 0;
        if ($query_result instanceof mysqli_result) {
            $numrows = mysqli_num_rows($query_result);
        }
        return $numrows;
    }

    /**
     * Anzahl betroffener Datensaetze (Rows) der letzen SQL Abfrage
     *
     * @access public
     * @return integer Bei Erfolg einen Integer Wert, bei Misserfolg false
     **/
    function affectedrows()
    {
        return ($this->last_connect_id) ? mysqli_affected_rows($this->last_connect_id) : false;
    }

    /**
     * Ermittelt die Spaltenanzahl einer SQL Abfrage
     *
     * @access public
     * @param mysqli_result|false $query_result Query Ergebnis-Kennung
     * @return integer Bei Erfolg einen Integer Wert, bei Misserfolg false
     **/
    function numfields($query_result = false)
    {
        if (!$query_result) {
            if (isset($this->query_result)) {
                $query_result = $this->query_result;
            }
        }

        $numfields = 0;
        if ($query_result instanceof mysqli_result) {
            $numfields = mysqli_num_fields($query_result);
        }
        return $numfields;
    }

    /**
     * Liefert den Namen eines Feldes in einem Ergebnis
     *
     * @access public
     * @param integer $offset Feldindex
     * @param resource $query_id Query Ergebnis-Kennung
     * @return string Bei Erfolg Feldnamen, bei Misserfolg false
     **/
    function fieldname($offset, $query_id = 0)
    {
        if (!$query_id) {
            if (isset($this->query_result)) {
                $query_id = $this->query_result;
            }
        }

        return ($query_id) ? mysqli_field_seek($query_id, $offset) : false;
    }

    /**
     * Liefert den Typ eines Feldes in einem Ergebnis
     *
     * @access public
     * @param integer $offset Feldindex
     * @param integer $query_id Query Ergebnis-Kennung
     * @return string Bei Erfolg Feldtyp, bei Misserfolg false
     **/
    function fieldtype($offset, $query_id = 0)
    {
        if (!$query_id) {
            if (isset($this->query_result)) {
                $query_id = $this->query_result;
            }
        }

        return ($query_id) ? mysqli_fetch_field_direct($query_id, $offset) : false;
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und indiziertes Array
     *
     * @access public
     * @param integer $query_id Query Ergebnis-Kennung
     * @return array Datensatz in einem assoziativen Array
     **/
    function fetchrow($query_resource = null)
    {
        if (!$query_resource) {
            if (isset($this->query_result)) {
                $query_resource = $this->query_result;
            }
        }


        if ($query_resource) {
            return mysqli_fetch_assoc($query_resource);
        }
        else {
            return false;
        }
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und numerisches Array
     *
     * @param false|mysqli_result $query_result
     * @param callable|null $callbackOnFetchRow
     * @param array $metaData
     * @return array Bei Erfolg ein Array mit allen Datensaetzen ($array[index]['feldname'])
     */
    public function fetchrowset(false|mysqli_result $query_result = false, ?callable $callbackOnFetchRow = null, array $metaData = []): array
    {
        if (!$query_result && isset($this->query_result)) {
            $query_result = $this->query_result;
        }

        $rowSet = [];
        // todo faster way?
        while (($row = mysqli_fetch_assoc($query_result)) != null) {
            if($metaData) {
                // todo faster way?
                foreach($row as $col => $val) {
                    if(isset($metaData['columns'][$col])) {
                        settype($row[$col], $metaData['columns'][$col]['phpType']);
                    }
                }
            }
            if($callbackOnFetchRow) {
                $row = call_user_func($callbackOnFetchRow, $row);
            }
            $rowSet[] = $row;
        }
        return $rowSet;
    }

    /**
     * Liefert ein Objekt mit Feldinformationen aus einem Anfrageergebnis
     *
     * @param string $field Feldname
     * @param integer $rownum Feld-Offset
     * @param null $query_resource
     * @return string Wert eines Feldes
     */
    function fetchfield($field, $rownum = -1, $query_resource = null)
    {
        $result = false;
        if (!$query_resource) {
            if (isset($this->query_result)) {
                $query_resource = $this->query_result;
            }
        }

        if ($query_resource) {
            if ($rownum > -1) {
                $result = $this->mysqli_result($query_resource, $rownum, $field);
            }
            //					else {
            //						$query_id = intval($query_resource);
            //						if(empty($this->row[$query_id]) && empty($this->rowset[$query_id])) {
            //							if( $this->fetchrow() ) {
            //								$result = $this->row[$query_id][$field];
            //							}
            //						}
            //			  			else {
            //							if($this->rowset[$query_id]) {
            //								$result = $this->rowset[$query_id][$field];
            //							}
            //							else if($this->row[$query_id]) {
            //								$result = $this->row[$query_id][$field];
            //							}
            //						}
            //					}

            return $result;
        }
        else {
            return false;
        }
    }

    /**
     * Wrapper function fuer die fruehere mysql_result
     *
     * @param mysqli_result $query_result
     * @param int $rownum
     * @param int $field
     * @return mixed
     */
    private function mysqli_result($query_result, $rownum, $field = 0)
    {
        if (!mysqli_data_seek($query_result, $rownum)) return false;
        if (!($row = mysqli_fetch_array($query_result))) return false;
        if (!array_key_exists($field, $row)) return false;
        return $row[$field];
    }

    /**
     * Bewegt den internen Ergebnis-Zeiger
     *
     * @public
     * @param integer $rownum Datensatznummer
     * @param integer $query_id Query Ergebnis-Kennung
     * @return boolean Bei Erfolg true, bei Misserfolg false
     **/
    function rowseek($rownum, $query_id = 0)
    {
        if (!$query_id) {
            if (isset($this->query_result)) {
                $query_id = $this->query_result;
            }
        }

        return ($query_id) ? mysqli_data_seek($query_id, $rownum) : false;
    }

    /**
     * Liefert die ID einer vorherigen INSERT-Operation.
     *
     * Hinweis:
     * mysql_insert_id() konvertiert den Typ der Rueckgabe der nativen MySQL C API Funktion mysql_insert_id() in den Typ long (als int in PHP bezeichnet). Falls Ihre AUTO_INCREMENT Spalte vom Typ BIGINT ist, ist der Wert den mysql_insert_id() liefert, nicht korrekt. Verwenden Sie in diesem Fall stattdessen die MySQL interne SQL Funktion LAST_INSERT_ID() in einer SQL-Abfrage
     *
     * @access public
     * @return integer Bei Erfolg die letzte ID einer INSERT-Operation
     **/
    function nextid()
    {
        return ($this->last_connect_id) ? mysqli_insert_id($this->last_connect_id) : false;
    }

    /**
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück.
     *
     * @return int Anzahl Zeilen
     */
    function foundRows()
    {
        $foundRows = 0;

        $sql = 'SELECT FOUND_ROWS() as foundRows';
        $query_id = $this->query($sql);
        if ($query_id) {
            $foundRows = $this->fetchfield('foundRows', 0, $query_id);
            $this->freeresult($query_id);
        }

        return $foundRows;
    }

    /**
     * Gibt eine Liste aller Felder eine Datenbank-Tabelle zurueck
     *
     * Ergebnis:
     * $array['Field'][index]
     * $array['Type'][index]
     * $array['Null'][index]
     * $array['Key'][index]
     * $array['Default'][index]
     * $array['Extra'][index]
     *
     * @access public
     * @param $table
     * @param $database
     * @param $fields
     * @param $pk
     * @return array Liste mit Feldern ($array['name'][index], etc.)
     */
    function listfields($table, $database, &$fields, &$pk): array
    {
        $rows = [];

        $sql = <<<SQL
select
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    COLUMN_KEY
from information_schema.COLUMNS
where TABLE_SCHEMA = '$database'
  AND TABLE_NAME = '$table'
SQL;

        // $query = 'SHOW COLUMNS FROM `' . $table . '`';
        $result = mysqli_query($this->__get_db_conid($database, SQL_READ), $sql, MYSQLI_USE_RESULT);

        if ($result !== false) {
            while ($row = mysqli_fetch_assoc($result)) {
                $phpType = match ($row['DATA_TYPE']) {
                    'int', 'tinyint', 'bigint', 'smallint', 'mediumint' => 'int',
                    'decimal', 'double', 'float', 'number' => 'float',
                    default => 'string',
                };
                if(str_starts_with($row['COLUMN_TYPE'], 'tinyint(1)')) {
                    $phpType = 'bool';
                }
                $row['phpType'] = $phpType;
                $rows[] = $row;
                $fields[] = $row['COLUMN_NAME'];
                if ($row['COLUMN_KEY'] == 'PRI') {
                    $pk[] = $row['COLUMN_NAME'];
                }
            }
            $this->freeresult($result);
        }

        return $rows;
    }

    /**
     * get information about one column
     *
     * @param string $database
     * @param string $table
     * @param string $field
     * @return array
     */
    public function listfield(string $database, string $table, string $field): array
    {
        $row = [];
        $result = mysqli_query($this->__get_db_conid($database, SQL_READ),
            'SHOW COLUMNS FROM `' . $table . '` like \''.$field.'\'', MYSQLI_STORE_RESULT);
        if(mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
        }
        $this->freeresult($result);
        return $row;
    }

    /**
     * Gibt belegten Speicher wieder frei
     *
     * Die Funktion muss nur dann aufgerufen werden, wenn Sie sich bei Anfragen, die grosse Ergebnismengen liefern, Sorgen
     * ueber den Speicherverbrauch zur Laufzeit des PHP-Skripts machen. Nach Ablauf des PHP-Skripts wird der Speicher ohnehin
     * freigegeben.
     *
     * @access public
     * @param mysqli_result|false $query_result Query Ergebnis-Kennung
     * @return boolean Bei Erfolg true, bei Misserfolg false
     **/
    public function freeresult($query_result = false)
    {
        if (!$query_result) {
            if (isset($this->query_result)) {
                $query_result = $this->query_result;
            }
        }

        if ($query_result instanceof mysqli_result) {
            $xdebug_is_debugger_active = false;
            if (function_exists('xdebug_is_debugger_active')) {
                $xdebug_is_debugger_active = xdebug_is_debugger_active();
            }
            // attention: xdebug shows strange error messages: Can't fetch mysqli_result
            if (!$xdebug_is_debugger_active) {
                mysqli_free_result($query_result);
            }
            return true;
        }
        return false;
    }

    /**
     * Liefert den Fehlertext der zuvor ausgefuehrten MySQL Operation und liefert die Nummer einer Fehlermeldung
     * einer zuvor ausgefuehrten MySQL Operation
     *
     * Ergebnis:
     * $array['message']
     * $array['code']
     *
     * @access public
     * @return array
     **/
    function getError()
    {
        $result['message'] = mysqli_error($this->last_connect_id);
        $result['code'] = mysqli_errno($this->last_connect_id);

        return $result;
    }

    /**
     * Liefert den Fehlertext der zuvor ausgefuehrten MySQL Operation und liefert die Nummer einer Fehlermeldung
     * einer zuvor ausgefuehrten MySQL Operation
     *
     * @access public
     * @return string Fehlercode + ': ' + Fehlertext
     **/
    function getErrormsg()
    {
        $result = $this->getError();
        $message = $result["code"] . ": " . $result["message"];
        return $message;
    }

    /**
     * Mit diesem Schalter werden alle Lesevorgaenge auf die Backend Datenbank umgeleitet.
     *
     * @access public
     **/
    function enable_force_backend()
    {
        $this->force_backend_read = true;
    }

    /**
     * Deaktiviert Lesevorgaenge auf der Backend Datenbank.
     *
     * @access public
     **/
    function disable_force_backend()
    {
        $this->force_backend_read = false;
    }

    /**
     * Maskiert einen String zur Benutzung in mysql_query
     *
     * @param string $string Text
     * @return string Maskierter Text
     */
    public function escapeString(string $string, $database = ''): string
    {
        $connection = $this->__get_db_conid($database, SQL_READ);
        return mysqli_real_escape_string($connection, $string);
    }

    /**
     * Liefert eine Zeichenkette mit der Version der Client-Bibliothek.
     *
     * @return string
     */
    function getClientInfo()
    {
        return mysqli_get_client_info();
    }

    /**
     * Ueberschreibt den Zeichensatz fuer die MySQL-Verbindung mit $charset.
     *
     * @param string $charset
     * @return bool
     */
    /*			function _setCharSet($charset, $database='')
                {
                    return mysql_query('SET CHARACTER SET \''.$charset.'\'', $conid);
                }*/

    /**
     * Aendert den Verbindungszeichensatz und -sortierfolge. _setNames ist äquivalent zu den folgenden drei MySQL Anweisungen: SET character_set_client = x; SET character_set_results = x; SET character_set_connection = x;
     *
     * @param string $charset_name Zeichensatz
     * @param resource|null $conid Verbindungs-ID/Resource
     * @return boolean
     */
    function _setNames($charset_name, $conid = null)
    {
        return mysqli_set_charset($conid, $charset_name);
        // return mysql_query('SET NAMES \''.$charset_name.'\'', $conid);
    }
}