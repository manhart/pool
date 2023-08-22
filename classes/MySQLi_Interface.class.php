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
     * When using clusters moves random hosts from $this->available_hosts to $this->hosts
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
     */
    private function hasAnotherHost(ConnectionMode $mode): bool
    {
        return (is_array($hosts = $this->available_hosts[$mode->value]??0)
            && sizeof($hosts)>0);
    }


    private array $authentications = [];

    /**
     * Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
     *
     * @param ConnectionMode $mode Beschreibt den Zugriffsmodus Schreib-Lesevorgang
     * @return array mit Key username und password
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
     */
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
     * @throws \Exception
     * @see MySQLi_Interface::__get_db_conid
     */
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
     */
    public function getLastSQLCommand(): string
    {
        return $this->last_command;
    }

    /**
     * Anzahl gefundener Datensaetze (Rows)
     */
    public function numRows($query_result = false)
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
     */
    public function affectedRows(): int|false
    {
        return ($this->last_connect_id) ? mysqli_affected_rows($this->last_connect_id) : false;
    }

    /**
     * Ermittelt die Spaltenanzahl einer SQL Abfrage
     */
    private function numFields(false|mysqli_result $query_result = false): int
    {
        $query_result = $query_result ?: $this->query_result ?? false;
        return $query_result instanceof mysqli_result ? mysqli_num_fields($query_result) : 0;
    }

    /**
     * Set result pointer to a specified field offset
     */
    private function fieldSeek(int $index, false|mysqli_result $query_result = false): bool
    {
        $query_result = $query_result ?: $this->query_result ?? false;
        return $query_result instanceof mysqli_result && mysqli_field_seek($query_result, $index);
    }

    /**
     * Liefert den Typ eines Feldes in einem Ergebnis
     * Seems to be broken ^^
     */
    private function fieldType(int $offset, int $query_id = 0): false|object
    {
        $query_id = $query_id ?: $this->query_result ?? 0;
        return ($query_id) ? mysqli_fetch_field_direct($query_id, $offset) : false;
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und indiziertes Array
     */
    private function fetchRow(false|mysqli_result $query_result = false): array|null|false
    {
        $query_result = $query_result ?: $this->query_result;
        return $query_result ? mysqli_fetch_assoc($query_result) : false;
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und numerisches Array
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
     * Retrieves the contents of one cell from a MySQLi result set.
     */
    public function fetchField(string $field, int $rowNum = -1, false|mysqli_result|null $query_resource = false): mixed
    {
        $query_resource = $query_resource ?: $this->query_result;
        if ($rowNum <= -1 || !$query_resource)
            return false;//abort
        return $this->mysqli_result($query_resource, $rowNum, $field);
    }

    /**
     * Retrieves the contents of one cell from a MySQLi result set.
     */
    private function mysqli_result(mysqli_result $query_result, int $rowNum, int|string $field = 0): mixed
    {
        return mysqli_data_seek($query_result, $rowNum)//nice staged execution
            && ($row = mysqli_fetch_array($query_result))
            && array_key_exists($field, $row) ?
                $row[$field] : false;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in the result
     */
    private function rowSeek(int $rowNum, mysqli_result $query_id = null): bool
    {
        $query_id = $query_id ?: $this->query_result;
        return $query_id && mysqli_data_seek($query_id, $rowNum);
    }

    /**
     * Liefert die ID einer vorherigen INSERT-Operation.
     * Hinweis:
     * mysql_insert_id() konvertiert den Typ der Rueckgabe der nativen MySQL C API Funktion mysql_insert_id() in den Typ long (als int in PHP bezeichnet). Falls Ihre AUTO_INCREMENT Spalte vom Typ BIGINT ist, ist der Wert den mysql_insert_id() liefert, nicht korrekt. Verwenden Sie in diesem Fall stattdessen die MySQL interne SQL Funktion LAST_INSERT_ID() in einer SQL-Abfrage
     *
     * @return false|int|string Bei Erfolg die letzte ID einer INSERT-Operation
     */
    public function nextId(): false|int|string
    {
        return ($this->last_connect_id) ? mysqli_insert_id($this->last_connect_id) : false;
    }

    /**
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück.
     *
     * @return int Anzahl Zeilen
     * @throws \Exception
     */
    public function foundRows(): int
    {
        $sql = 'SELECT FOUND_ROWS() as foundRows';
        $query_id = $this->query($sql);
        if (!$query_id)
            return 0;
        $foundRows = $this->fetchField('foundRows', 0, $query_id);
        $this->freeResult($query_id);
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
     * @param $table
     * @param $database
     * @param $fields
     * @param $pk
     * @return array Liste mit Feldern ($array['name'][index], etc.)
     * @throws Exception
     */
    public function listfields($table, $database, &$fields, &$pk): array
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
            $this->freeResult($result);
        }

        return $rows;
    }

    /**
     * Get information about a column
     *
     * @throws \Exception
     */
    public function getColumnMetadata(string $database, string $table, string $field): array
    {
        if(!$database || !$table || !$field) {
            throw new \pool\classes\Exception\InvalidArgumentException('Database, table and field names must be non-empty strings.');
        }

        $result = mysqli_query($this->__get_db_conid($database, ConnectionMode::READ),
            "SHOW COLUMNS FROM `$table` like '$field'");
        $row = [];
        if(mysqli_num_rows($result)) $row = mysqli_fetch_assoc($result);
        $this->freeResult($result);
        return $row;
    }

    /**
     * Frees the memory associated with a result
     *
     * @param false|mysqli_result $query_result Query Ergebnis-Kennung
     * @return void Bei Erfolg true, bei Misserfolg false
     */
    public function freeResult(false|mysqli_result $query_result = false): void
    {
        $query_result = $query_result ?: $this->query_result;
        if($query_result) mysqli_free_result($query_result);
    }

    /**
     * Returns the mysqli error of the last executed query
     * @return array
     */
    public function getError(): array
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
    public function getErrorAsText(): string
    {
        $result = $this->getError();
        return "{$result["code"]}: {$result["message"]}";
    }

    /** Mit diesem Schalter werden alle Lesevorgänge auf die Backenddatenbank umgeleitet. **/
    public function forceMasterRead(): static
    {
        $this->force_backend_read = true;
        return $this;
    }

    /** Deaktiviert Lesevorgänge auf der Backenddatenbank. **/
    public function disableMasterRead(): static
    {
        $this->force_backend_read = false;
        return $this;
    }

    /**
     * Maskiert einen String zur Benutzung in mysql_query
     *
     * @param string $string Text
     * @return string Maskierter Text
     * @throws \Exception
     */
    public function escapeString(string $string, $database = ''): string
    {
        $connection = $this->__get_db_conid($database, ConnectionMode::READ);
        return mysqli_real_escape_string($connection, $string);
    }

    /**
     * Liefert eine Zeichenkette mit der Version der Client-Bibliothek.
     */
    private function getClientInfo()
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
    private function _setNames(string $charset_name, $conid = null): bool
    {
        return $conid instanceof mysqli && mysqli_set_charset($conid, $charset_name);
        // return mysql_query('SET NAMES \''.$charset_name.'\'', $conid);
    }
}