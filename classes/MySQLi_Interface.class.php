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
use pool\classes\Exception\MissingArgumentException;
use pool\classes\Utils\Singleton;

enum ConnectionMode: string
{
    case READ = 'READ';
    case WRITE = 'WRITE';
}



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
     * @var array<string, int> available cluster modes <br>
     * Its unclear what the array values mean, they seem to refer to a default inside $this->available_hosts
     */
    private array $modes = [ConnectionMode::READ->value => 0, ConnectionMode::WRITE->value => 1];

    private array $commands = ['SELECT', 'SHOW', 'INSERT', 'UPDATE', 'DELETE', 'EXPLAIN', 'ALTER', 'CREATE', 'DROP', 'RENAME',
            'CALL', 'REPLACE', 'TRUNCATE', 'LOAD', 'HANDLER', 'DESCRIBE', 'START', 'COMMIT', 'ROLLBACK',
            'LOCK', 'SET', 'STOP', 'RESET', 'CHANGE', 'PREPARE', 'EXECUTE', 'DEALLOCATE', 'DECLARE', 'OPTIMIZE'];

    /** Letzter benutzer MySQL Link */
    private ?mysqli $last_connect_id;

    /** Letzter ausgeführter Query */
    public string $last_Query; // for pray to see the active/last sql statement;

    /** Saves fetched Mysql results */
    private array $row = array();

    /** Saves fetched Mysql row-sets */
    private array $rowset = array();

    /** Anzahl insgesamt ausgeführter Queries */
    private int$num_queries = 0;

    /** Anzahl insgesamt ausgeführter Lesevorgänge */
    private int $num_local_queries = 0;

    /** Anzahl insgesamt ausgeführter Schreibvorgänge */
    private int $num_remote_queries = 0;

    /** Enthält ein Array bestehend aus zwei Hosts für Lese- und Schreibvorgänge. Sie werden für die Verbindungsherstellung genutzt. */
    private array $hosts = array();

    /**
     * Enthält den Variablennamen des Authentication-Arrays; Der Variablenname wird vor dem Connect aufgelöst;
     * Das Database Objekt soll keine USER und PASSWOERTER intern speichern. Vorsicht wegem ERRORHANDLER!
     */
    private string $auth = "";

    /** @var array<String, array<String, resource>>  Array of Mysql Links; Aufbau $var[$mode][$database] = resource */
    private array $connections = [ConnectionMode::READ->value => [], ConnectionMode::WRITE->value => []];

    /** Alle verfügbaren Master u. Slave Hosts */
    var string|array $available_hosts = array();

    /**
     * Erzwingt Lesevorgänge über den Master-Host für Schreibvorgänge
     * (Wird gebraucht, wenn geschrieben wird, anschließend wieder gelesen. Die Replikation hinkt etwas nach.)
     */
    public bool $force_backend_read = false;

    /** Standard Datenbank */
    private string $default_database = '';

    /** Speichert das Query Result zwischen */
    var bool|mysqli_result $query_result = false;

    /** Zuletzt ausgeführtes SQL Kommando */
    var string $last_command = '';

    /** Zeichensatz für die MySQL Verbindung */
    var string $default_charset = '';

    /** Network port for connecting to server */
    var int $port = 3306;

    /**---- class constants ----*/
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
     * Einstellungen:
     * persistency = (boolean) Persistente Verbindung (Default true)
     * host = (array)|(string) Hosts des MySQL Servers (es koennen auch Cluster bedient werden host[0] = read; host[1] = write)
     * database = (string) Standard Datenbank
     * auth = (array) Authentication Array, Default 'mysql_auth' (siehe access.inc.php)
     *
     * @param array $connectionOptions Einstellungen
     * @return boolean Erfolgsstatus
     * @throws Exception
     */
    public function setOptions(array $connectionOptions): bool
    {
        // $this->persistency = array_key_exists('persistency', $Packet) ? $Packet['persistency'] : false;
        $this->force_backend_read = $connectionOptions['force_backend_read'] ?? false;

        $this->available_hosts = $connectionOptions['host'] ??
            throw new MissingArgumentException('MySQL_Interface::setOptions Bad Packet: no key "host"');

        $this->default_database = $connectionOptions['database'] ??
            throw new MissingArgumentException('MySQL_Interface::setOptions Bad Packet: no key "database"');

        if (array_key_exists('port', $connectionOptions))
            $this->port = $connectionOptions['port'];

        if (array_key_exists('charset', $connectionOptions))
            $this->default_charset = $connectionOptions['charset'];

        $this->auth = $connectionOptions['auth'] ?? 'mysql_auth';// fallback verwendet zentrale, globale Authentifizierung

        /* @noinspection PhpUnhandledExceptionInspection no connection is attempted*/
        $this->__findHostForConnection();

        return true;
    }

    /**
     * when using clusters moves random hosts from $this->available_hosts to $this->hosts
     *
     * @param ConnectionMode|null $connectionMode
     * @return int number of remaining alternative hosts
     * @throws Exception
     */
    private function __findHostForConnection(ConnectionMode $connectionMode = null): int
    {
        $available_hosts =& $this->available_hosts;
        $alternativeHosts = 0;
        if (is_array($available_hosts))
            /** Multiple Clusters: move one random host to the hosts list*/
            foreach ($this->modes as $clusterMode => $clusterModeSpecificIndexUsedInAvailableHosts) {
                /** @var array|null $hostList reference to hosts available in this mode */
                $hostList =& $available_hosts[$clusterMode];
                if ((!$connectionMode || $connectionMode == $clusterMode) && $hostList) {//targeting that specific mode or no specific one
                    $key = array_rand($hostList);//changed from random int-key
                    $host = $hostList[$key];
                    unset($hostList[$key]);//remove option
                    if ($clusterMode == ConnectionMode::READ)//is this just an error in the original code?
                        $hostList = array_values($hostList);//reindex; should be unnecessary with array_rand
                    $alternativeHosts += sizeof($hostList);
                } else//requested connectionMode isn't matching clusterMode or the cluster mode has no remaining hosts
                    // no clue what's going on here I presume this fetches a default
                    $host = $available_hosts[$clusterModeSpecificIndexUsedInAvailableHosts] ?? false;

                if ($host) $this->hosts[$clusterMode] = $host;
            }
        else /**Ein MySQL Server fuer Lesen und Schreiben*/
            $this->hosts = [
                ConnectionMode::READ->value => $available_hosts,
                ConnectionMode::WRITE->value => $available_hosts,
            ];

        return $alternativeHosts;
    }

    /**
     * Ermittelt, ob noch Master-/Slave Hosts zur Verfuegung stehen
     *
     * @param ConnectionMode $mode
     * @return boolean
     */
    function hasAnotherHost(ConnectionMode $mode): bool
    {
        return (is_array($hosts = $this->available_hosts[$mode->value]??0)
            && sizeof($hosts)>0);
    }


    private array $authentications = [];

    /**
     * MySQL_Interface::__get_auth()
     * Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
     *
     * @param ConnectionMode $mode Beschreibt den Zugriffsmodus Schreib-Lesevorgang
     * @return Array mit Key username und password
     * @throws Exception
     */
    private function __get_auth(ConnectionMode $mode): array
    {
        $auth = &$this->authentications[$this->auth];
        $auth ??=
            (file_exists($authFile = constant('DBACCESSFILE')))
                ? (require $authFile)[$this->auth] ?? []
                : [];

        $hostname = $this->hosts[$mode->value];//normalize mode for lookup
        return $auth[$hostname] ??//now testing hostname that is returned instead of reading-host
            throw new Exception("MySQL access denied! No authentication data available (Database: $hostname Mode: $mode->value).");

    }

    /**
     * __get_db_conid()
     * Stellt eine MySQL Verbindung her. Falls die Verbindungs-Kennung bereits existiert,
     * wird die vorhandene Verbindung verwendet (Resourcen-Sharing)
     *
     * @param $database string Datenbank
     * @param ConnectionMode $mode string Lese- oder Schreibmodus
     * @return mysqli Gibt Resource der MySQL Verbindung zurueck
     * @throws Exception
     */
    private function __get_db_conid(string $database, ConnectionMode $mode): mysqli
    {
        if (!($database || ($database = $this->default_database))) //No DB specified
            throw new Exception('No database selected (__get_db_conid)!');
        if ($this->hosts[ConnectionMode::READ->value] == $this->hosts[ConnectionMode::WRITE->value])
            $mode = ConnectionMode::READ; // same as WRITE
        return $this->connections[$mode->value][$database] ?? //fetch from cache
            $this->openNewDBConnection($mode, $database);
    }

    /**
     * @param ConnectionMode $mode
     * @param string $database
     * @return mysqli
     * @throws Exception
     */
    private function openNewDBConnection(ConnectionMode $mode, string $database): mysqli
    {
        $host = $this->hosts[$mode->value].':'.$this->port;
        $auth = $this->__get_auth($mode);
        $credentials = $auth[$database] ?? $auth['all'] ?? [];
        $db_pass = $credentials['password'] ?? '';
        $db_user = $credentials['username'] ?? '';

        //open connection
        $conid = mysqli_connect($host, $db_user, $db_pass, '', $this->port);

        if(defined($x = 'LOG_ENABLED') && constant($x) &&
            defined($x = 'ACTIVATE_INTERFACE_SQL_LOG') && constant($x) == 2 &&
            ($Log = Singleton::get('LogFile'))->isLogging()) {
            //Logging enabled
            $sqlTarget = "TO $host MODE: $mode->name DB: $database";
            $Log->addLine(($conid) ? "CONNECTED $sqlTarget" :
                "FAILED TO CONNECT $sqlTarget (MySQL-Error: ".mysqli_connect_errno().': '.mysqli_connect_error().')');
        }

        if(($conid && $this->default_charset && !$this->_setNames($this->default_charset, $conid))// failed to set default charset of connection
            || (!@mysqli_select_db($conid, $database))) {//failed to set default database
            $mysqli_error = 'MySQL ErrNo '.mysqli_errno($conid).': '.mysqli_error($conid);
            mysqli_close($conid);//abort new connection due to Error
            $conid = null;
        }
        if($conid) //set default and store connection
            return $this->connections[$mode->value][$database] = $conid;
        elseif($this->hasAnotherHost($mode)) {//connection errored out but alternative hosts exist -> recurse
            $this->__findHostForConnection($mode);
            return $this->openNewDBConnection($mode, $database);
        }
        else throw new Exception($mysqli_error ?? //no alternative host left
                "MySQL connection to host '$host' with mode $mode->name failed!"
                ." Used default database '$database' (MySQL ErrNo "
                .mysqli_connect_errno().': '.mysqli_connect_error().')!');
    }

    /**
     * Baut eine Verbindung zur Datenbank auf.
     *
     * @param string $database Datenbank
     * @throws Exception
     */
    public function open(string $database = ''): bool
    {
        $this->__get_db_conid($database, ConnectionMode::READ);
        if ($this->hosts[ConnectionMode::READ->value] != $this->hosts[ConnectionMode::WRITE->value]) {
            $this->__get_db_conid($database, ConnectionMode::WRITE);
        }
        return ($this->isConnected($database) and $this->isConnected($database, ConnectionMode::WRITE));
    }

    /**
     * Ueberprueft ob eine MySQL Verbindung besteht und baut verloren gegangene Verbindung wieder auf (bis PHP 5.0.13)
     *
     * @param string $database Datenbank
     * @param ConnectionMode $mode Lese- oder Schreibmodus
     * @return boolean Gibt TRUE/FALSE zurueck
     */
    public function isConnected(string $database = '', ConnectionMode $mode = ConnectionMode::READ): bool
    {
        if ($this->hosts[ConnectionMode::READ->value] == $this->hosts[ConnectionMode::WRITE->value])
            $mode = ConnectionMode::READ; // same as host
        $database = $database ?: $this->default_database;
        $connection = &$this->connections[$mode->value][$database];
        return $connection instanceof mysqli && !$connection->connect_error &&
            mysqli_ping($connection);
    }

    /**
     * Closes all connections and clears them from the register
     *
     * @return boolean true
     **/
    public function close(): bool
    {
        $readConnections = &$this->connections[ConnectionMode::READ->value];
        $writeConnections = &$this->connections[ConnectionMode::WRITE->value];

        if (is_array($readConnections)) {
            foreach ($readConnections as $database => $conid) if ($conid instanceof mysqli) {
                @mysqli_close($conid);
                // workaround, sonst schlägt die Schleife für write mode fehl. // But why? The documentation doesn't say close isn't idempotent
                if ((isset($writeConnections[$database])) && ($conid == $writeConnections[$database]))
                    unset($writeConnections[$database]);
            }
            $readConnections = [];
        }
        if (is_array($writeConnections)) {
            foreach ($writeConnections as $conid) if ($conid instanceof mysqli)
                @mysqli_close($conid);
            $writeConnections = [];
        }
        return true;
    }

    /**
     * Executes a SQL-Statement.<br>
     * Saves query to this->sql<br>
     * Resets query_result<br>
     * Gets a conid and saves it to last_connect_id<br>
     * Updates last command on success
     *
     * @param string $query SQL-Statement
     * @param string $database Datenbankname (default '')
     * @return bool|mysqli_result Erfolgsstatus
     * @throws Exception
     * @see MySQLi_Interface::__get_db_conid
     **/
    public function query(string $query, string $database = ''): mysqli_result|bool
    {
        //Store query in attribute
        $this->last_Query = $sql = ltrim($query);
        // reset query result
        $this->query_result = false;
        if (!$sql)//nothing to do
            return false;
        //identify command
        $offset = strspn($sql, "( \n\t\r");//skip to the meat
        //find position of first whitespace, starting from magic value 2 from old code
        $pos = strcspn($sql, " \n\r\t", $offset + 2) + 2;// TODO MySQL Syntax DO, USE?
        $command = strtoupper(substr($sql, $offset, $pos));//cut command from Query
        if (IS_TESTSERVER && !in_array($command, $this->commands))
            echo "Unknown command: '$command'<br>" .
                "in $sql<hr>" .
                'Please contact Alexander Manhart for MySQL_Interface in the function query()';
        $isSELECT = ($command == 'SELECT');//mode selection
        $mode = !$isSELECT || $this->force_backend_read ? ConnectionMode::WRITE->name : ConnectionMode::READ->name;
        if ($isSELECT)
            $this->num_local_queries++;
        else
            $this->num_remote_queries++;
        $this->num_queries++;
        $conid = $this->__get_db_conid($database, ConnectionMode::READ);//connect
        $this->query_result = @mysqli_query($conid, $this->last_Query);//run
        $this->last_connect_id = $conid;
        if ($this->query_result)//Query successful
            $this->last_command = $command;
        if (defined($x='LOG_ENABLED') && constant($x) &&
            defined($x='ACTIVATE_INTERFACE_SQL_LOG') && constant($x) == 2 &&
            ($Log = Singleton::get('LogFile'))->isLogging())
            //Logging enabled
            $Log->addLine('SQL MODE: ' . $mode);
        return $this->query_result;
    }

    /**
     * Returns the first command of the most recently executed statement in uppercase e.g. SELECT
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
        if (!$query_result)//try object property
            $query_result = $this->query_result ?? false;
        $result = $query_result instanceof mysqli_result ?
            mysqli_num_rows($query_result) : 0;
        assert(is_int($result));
        return $result;
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
     * @param false|mysqli_result $query_result Query Ergebnis-Kennung
     * @return integer Bei Erfolg einen Integer Wert, bei Misserfolg false
     **/
    public function numFields(false|mysqli_result $query_result = false):int
    {
        $query_result = $query_result ?: $this->query_result ?? false;
        return $query_result instanceof mysqli_result ? mysqli_num_fields($query_result) : 0;
    }

    /**
     * Liefert den Namen eines Feldes in einem Ergebnis
     * Seems to be broken ^^
     * @param int $offset Feldindex
     * @param int $query_id Query Ergebnis-Kennung
     * @return string Bei Erfolg Feldnamen, bei Misserfolg false
     **/
    public function fieldName(int $offset, int $query_id = 0):bool
    {
        $query_id = $query_id ?: $this->query_result ?? 0;
        return $query_id && mysqli_field_seek($query_id, $offset);
    }

    /**
     * Liefert den Typ eines Feldes in einem Ergebnis
     * Seems to be broken ^^
     * @param int $offset Feldindex
     * @param int $query_id Query Ergebnis-Kennung
     * @return string Bei Erfolg Feldtyp, bei Misserfolg false
     **/
    public function fieldtype(int $offset, int $query_id = 0): false|object
    {
        $query_id = $query_id ?: $this->query_result ?? 0;
        return ($query_id) ? mysqli_fetch_field_direct($query_id, $offset) : false;
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und indiziertes Array
     *
     * @param integer $query_id Query Ergebnis-Kennung
     * @return array Datensatz in einem assoziativen Array
     **/
    public function fetchRow($query_resource = false): false|array
    {
        $query_resource = $query_resource ?: $this->query_result;
        return $query_resource ?
            mysqli_fetch_assoc($query_resource) : false;
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und numerisches Array
     *
     * @param false|mysqli_result $query_result
     * @param callable|null $callbackOnFetchRow
     * @param array $metaData
     * @return array Bei Erfolg ein Array mit allen Datensaetzen ($array[index]['feldname'])
     */
    public function fetchRowSet(false|mysqli_result $query_result = false, ?callable $callbackOnFetchRow = null, array $metaData = []): array
    {
        $query_result = $query_result ?: $this->query_result;

        $rowSet = [];
        // todo faster way?
        while (($row = mysqli_fetch_assoc($query_result)) != null) {
            if($metaData) {
                // todo faster way?
                foreach($row as $col => $val) {
                    if(isset($metaData['columns'][$col]) && $val !== null) {
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
     * @param integer $rowNum Feld-Offset
     * @param false|mysqli_result|null $query_resource
     * @return string Wert eines Feldes
     */
    function fetchField(string $field, int $rowNum = -1, false|mysqli_result|null $query_resource = false):mixed
    {
        $query_resource = $query_resource ?: $this->query_result;
        if ($rowNum <= -1 || !$query_resource)
            return false;//abort
        return $this->mysqli_result($query_resource, $rowNum, $field);
    }

    /**
     * Wrapper function fuer die fruehere mysql_result
     *
     * @param mysqli_result $query_result
     * @param int $rowNum
     * @param int|string $field
     * @return mixed
     */
    private function mysqli_result(mysqli_result $query_result, int $rowNum, int|string $field = 0): mixed
    {
        return mysqli_data_seek($query_result, $rowNum)//nice staged execution
        && ($row = mysqli_fetch_array($query_result))
        && array_key_exists($field, $row) ?
            $row[$field] : false;
    }

    /**
     * Bewegt den internen Ergebnis-Zeiger
     *
     * @param integer $rowNum Datensatznummer
     * @param mysqli_result|null $query_id Query Ergebnis-Kennung
     * @return boolean Bei Erfolg true, bei Misserfolg false
     */
    public function rowSeek(int $rowNum, mysqli_result $query_id = null): bool
    {
        $query_id = $query_id ?: $this->query_result;
        return $query_id && mysqli_data_seek($query_id, $rowNum);
    }

    /**
     * Liefert die ID einer vorherigen INSERT-Operation.
     *
     * Hinweis:
     * mysql_insert_id() konvertiert den Typ der Rueckgabe der nativen MySQL C API Funktion mysql_insert_id() in den Typ long (als int in PHP bezeichnet). Falls Ihre AUTO_INCREMENT Spalte vom Typ BIGINT ist, ist der Wert den mysql_insert_id() liefert, nicht korrekt. Verwenden Sie in diesem Fall stattdessen die MySQL interne SQL Funktion LAST_INSERT_ID() in einer SQL-Abfrage
     *
     * @return integer Bei Erfolg die letzte ID einer INSERT-Operation
     **/
    public function nextId(): false|int|string
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
        $sql = 'SELECT FOUND_ROWS() as foundRows';
        $query_id = $this->query($sql);
        if (!$query_id)
            return 0;
        $foundRows = $this->fetchField('foundRows', 0, $query_id);
        $this->freeresult($query_id);
        assert(is_int($foundRows));
        return $foundRows;
    }

    /**
     * Gibt eine Liste aller Felder eine Datenbank-Tabelle zurueck
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
     * @throws Exception
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
        $result = mysqli_query($this->__get_db_conid($database, ConnectionMode::READ), $sql, MYSQLI_USE_RESULT);

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
     * @throws Exception
     */
    public function listfield(string $database, string $table, string $field): array
    {
        $row = [];
        $result = mysqli_query($this->__get_db_conid($database, ConnectionMode::READ),
            'SHOW COLUMNS FROM `' . $table . '` like \''.$field.'\'', MYSQLI_STORE_RESULT);
        if(mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
        }
        $this->freeresult($result);
        return $row;
    }

    /**
     * Gibt belegten Speicher wieder frei
     * Die Funktion muss nur dann aufgerufen werden, wenn Sie sich bei Anfragen, die grosse Ergebnismengen liefern, Sorgen
     * ueber den Speicherverbrauch zur Laufzeit des PHP-Skripts machen. Nach Ablauf des PHP-Skripts wird der Speicher ohnehin
     * freigegeben.
     *
     * @param false|mysqli_result $query_result Query Ergebnis-Kennung
     * @return boolean Bei Erfolg true, bei Misserfolg false
     **/
    public function freeresult(false|mysqli_result $query_result = false):bool
    {
        $result = $query_result ?: $this->query_result;
        $hasResult = $result instanceof mysqli_result;
        if ($hasResult
            // attention: xdebug shows strange error messages: Can't fetch mysqli_result
            && (!function_exists('xdebug_is_debugger_active') || !xdebug_is_debugger_active()))
            mysqli_free_result($result);
        return $hasResult;
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
    function getError(): array
    {
        return [
            'message' => mysqli_error($this->last_connect_id),
            'code' => mysqli_errno($this->last_connect_id)
        ];
    }

    /**
     * Liefert den Fehlertext der zuvor ausgefuehrten MySQL Operation und liefert die Nummer einer Fehlermeldung
     * einer zuvor ausgefuehrten MySQL Operation
     * @return string Fehlercode + ': ' + Fehlertext
     **/
     public function getErrormsg():string
    {
        $result = $this->getError();
        return "{$result["code"]}: {$result["message"]}";
    }

    /** Mit diesem Schalter werden alle Lesevorgänge auf die Backenddatenbank umgeleitet. **/
    public function enable_force_backend()
    {
        $this->force_backend_read = true;
    }

    /** Deaktiviert Lesevorgänge auf der Backenddatenbank. **/
    public function disable_force_backend()
    {
        $this->force_backend_read = false;
    }

    /**
     * Maskiert einen String zur Benutzung in mysql_query
     *
     * @param string $string Text
     * @return string Maskierter Text
     * @throws Exception unable to acquire a database connection
     */
    public function escapeString(string $string, $database = ''): string
    {
        $connection = $this->__get_db_conid($database, ConnectionMode::READ);
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
     * Ändert den Verbindungszeichensatz und -sortierfolge. _setNames ist äquivalent zu den folgenden drei MySQL Anweisungen: SET character_set_client = x; SET character_set_results = x; SET character_set_connection = x;
     *
     * @param string $charset_name Zeichensatz
     * @param resource|null $conid Verbindungs-ID/Resource
     * @return boolean
     */
    function _setNames(string $charset_name, $conid = null): bool
    {
        return $conid instanceof mysqli && mysqli_set_charset($conid, $charset_name);
        // return mysql_query('SET NAMES \''.$charset_name.'\'', $conid);
    }


}