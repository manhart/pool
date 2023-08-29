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

use Exception;
use pool\classes\Database\Exception\DatabaseConnectionException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\MissingArgumentException;
use pool\classes\Utils\Singleton;
use function constant;
use function defined;

class DataInterface
{
    /**---- class constants ----*/
    public const ZERO_DATE = '0000-00-00';

    public const ZERO_TIME = '00:00:00';

    public const ZERO_DATETIME = '0000-00-00 00:00:00';

    public const MAX_DATE = '9999-12-31';

    public const MAX_DATETIME = '9999-12-31 23:59:59';

    /**
     * @var string Last executed query for debugging purposes
     */
    public string $last_Query;

    /** Alle verfügbaren Master u. Slave Hosts */
    var string|array $available_hosts = [];

    /**
     * Erzwingt Lesevorgänge über den Master-Host für Schreibvorgänge
     * (Wird gebraucht, wenn geschrieben wird, anschließend wieder gelesen. Die Replikation hinkt etwas nach.)
     */
    public bool $force_backend_read = false;

    /**
     * @var \pool\classes\Database\Driver
     */
    protected Driver $driver;

    /**
     * @var array<string, int> available cluster modes <br>
     * Its unclear what the array values mean, they seem to refer to a default inside $this->available_hosts
     */
    private array $modes = [ConnectionMode::READ->value => 0, ConnectionMode::WRITE->value => 1];

    private array $commands = ['SELECT', 'SHOW', 'INSERT', 'UPDATE', 'DELETE', 'EXPLAIN', 'ALTER', 'CREATE', 'DROP', 'RENAME',
        'CALL', 'REPLACE', 'TRUNCATE', 'LOAD', 'HANDLER', 'DESCRIBE', 'START', 'COMMIT', 'ROLLBACK',
        'LOCK', 'SET', 'STOP', 'RESET', 'CHANGE', 'PREPARE', 'EXECUTE', 'DEALLOCATE', 'DECLARE', 'OPTIMIZE'];

    /**
     * @var \pool\classes\Database\ConnectionWrapper|null Last used connection link in query()
     * @see DataInterface::_query()
     */
    private ?ConnectionWrapper $lastConnectionWrapper;

    /**
     * @var int Total number of queries executed
     */
    private int $totalQueries = 0;

    /**
     * @var int Total number of read queries executed
     */
    private int $totalReads = 0;

    /**
     * @var int Total number of write queries executed
     */
    private int $totalWrites = 0;

    /** Enthält ein Array bestehend aus zwei Hosts für Lese- und Schreibvorgänge. Sie werden für die Verbindungsherstellung genutzt. */
    private array $hosts = [];

    /**
     * Enthält den Variablennamen des Authentication-Arrays; Der Variablenname wird vor dem Connect aufgelöst;
     * Das Database Objekt soll keine USER und PASSWOERTER intern speichern. Vorsicht wegem ERRORHANDLER!
     */
    private string $auth = "";

    /** @var array<String, array<String, resource>>  Array of Mysql Links; Aufbau $var[$mode][$database] = resource */
    private array $connections = [ConnectionMode::READ->value => [], ConnectionMode::WRITE->value => []];

    /**
     * @var string Default database
     */
    private string $default_database = '';

    /**
     * @var mixed Query result resource of the last query
     */
    private mixed $query_resource = false;

    /**
     * @var string Last executed command
     */
    private string $last_command = '';

    /**
     * @var string|null Character set for the connection
     */
    private ?string $charset = null;

    /**
     * @var int Network port for connecting to server
     */
    private int $port;

    private array $authentications = [];

    /**
     * @param \pool\classes\Database\Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
        $this->port = $driver->getDefaultPort();
    }

    /**
     * Factory method to create a data interface
     *
     * @throws \Exception
     */
    public static function createDataInterface(array $connectionOptions, ?Driver $driver = null): DataInterface
    {
        $DataInterface = new static($driver ?? Driver\MySQLi::getInstance());
        $DataInterface->setOptions($connectionOptions);
        return $DataInterface;
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

        if(array_key_exists('port', $connectionOptions))
            $this->port = $connectionOptions['port'];

        if(array_key_exists('charset', $connectionOptions))
            $this->charset = $connectionOptions['charset'];

        $this->auth = $connectionOptions['auth'] ?? 'mysql_auth';// fallback verwendet zentrale, globale Authentifizierung

        /* @noinspection PhpUnhandledExceptionInspection no connection is attempted */
        $this->__findHostForConnection();
        return true;
    }

    /**
     * When using clusters moves random hosts from $this->available_hosts to $this->hosts
     */
    protected function __findHostForConnection(ConnectionMode $connectionMode = null): int
    {
        $available_hosts =& $this->available_hosts;
        $alternativeHosts = 0;
        if(is_array($available_hosts))
            /** Multiple Clusters: move one random host to the hosts list*/
            foreach($this->modes as $clusterMode => $clusterModeSpecificIndexUsedInAvailableHosts) {
                /** @var array|null $hostList reference to hosts available in this mode */
                $hostList =& $available_hosts[$clusterMode];
                if((!$connectionMode || $connectionMode == $clusterMode) && $hostList) {//targeting that specific mode or no specific one
                    $key = array_rand($hostList);//changed from random int-key
                    $host = $hostList[$key];
                    unset($hostList[$key]);//remove option
                    if($clusterMode == ConnectionMode::READ)//is this just an error in the original code?
                        $hostList = array_values($hostList);//reindex; should be unnecessary with array_rand
                    $alternativeHosts += sizeof($hostList);
                }
                else//requested connectionMode isn't matching clusterMode or the cluster mode has no remaining hosts
                    // no clue what's going on here I presume this fetches a default
                    $host = $available_hosts[$clusterModeSpecificIndexUsedInAvailableHosts] ?? false;

                if($host) $this->hosts[$clusterMode] = $host;
            }
        else /**Ein MySQL Server fuer Lesen und Schreiben*/
            $this->hosts = [
                ConnectionMode::READ->value => $available_hosts,
                ConnectionMode::WRITE->value => $available_hosts,
            ];

        return $alternativeHosts;
    }

    /**
     * @return string name of the driver. it is used to identify the driver in the configuration and for the factory to load the correct data access
     *     objects
     */
    public function getDriverName(): string
    {
        return $this->driver->getName();
    }

    /**
     * Baut eine Verbindung zur Datenbank auf.
     *
     * @throws Exception
     */
    public function open(string $database = ''): bool
    {
        $this->__get_db_conid($database, ConnectionMode::READ);
        if($this->hosts[ConnectionMode::READ->value] != $this->hosts[ConnectionMode::WRITE->value]) {
            $this->__get_db_conid($database, ConnectionMode::WRITE);
        }
        return ($this->isConnected($database) and $this->isConnected($database, ConnectionMode::WRITE));
    }

    /**
     * @param $database string Datenbank
     * @param \pool\classes\Database\ConnectionMode $mode string Lese- oder Schreibmodus
     * @return ConnectionWrapper Gibt Resource der MySQL Verbindung zurueck
     * @throws Exception
     */
    private function __get_db_conid(string $database, ConnectionMode $mode): ConnectionWrapper
    {
        if(!($database || ($database = $this->default_database))) //No DB specified
            throw new Exception('No database selected (__get_db_conid)!');
        if($this->hosts[ConnectionMode::READ->value] == $this->hosts[ConnectionMode::WRITE->value])
            $mode = ConnectionMode::READ; // same as WRITE
        return $this->connections[$mode->value][$database] ?? //fetch from cache
            $this->openNewDBConnection($mode, $database);
    }

    /**
     * @throws Exception
     */
    private function openNewDBConnection(ConnectionMode $mode, string $database): ConnectionWrapper
    {
        $host = $this->hosts[$mode->value];
        $auth = $this->__get_auth($mode);
        $credentials = $auth[$database] ?? $auth['all'] ?? [];
        $db_pass = $credentials['password'] ?? '';
        $db_user = $credentials['username'] ?? '';

        //open connection
        try {
            $Connection = $this->driver->connect($this, $host, $this->port, $db_user, $db_pass, $database, charset: $this->charset);
        }
        catch(DatabaseConnectionException|Exception) {
            $Connection = null;
        }

        if($Connection) //set default and store connection
            return $this->connections[$mode->value][$database] = $Connection;
        elseif($this->hasAnotherHost($mode)) {//connection errored out but alternative hosts exist -> recurse
            $this->__findHostForConnection($mode);
            return $this->openNewDBConnection($mode, $database);
        }
        else {
            $errors = $this->driver->errors()[0] ?? ['errno' => 0, 'error' => 'Unknown'];
            throw new Exception("Database connection to host '$host' with mode $mode->name failed!"
                ." Used default database '$database' (ErrNo "
                .$errors['errno'].': '.$errors['error'].')!');
        }
    }

    /**
     * Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
     *
     * @param \pool\classes\Database\ConnectionMode $mode Beschreibt den Zugriffsmodus Schreib-Lesevorgang
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
     * Ermittelt, ob noch Master-/Slave Hosts zur Verfuegung stehen
     */
    private function hasAnotherHost(ConnectionMode $mode): bool
    {
        return (is_array($hosts = $this->available_hosts[$mode->value] ?? 0)
            && sizeof($hosts) > 0);
    }

    /**
     * Ueberprueft ob eine MySQL Verbindung besteht und baut verloren gegangene Verbindung wieder auf (bis PHP 5.0.13)
     */
    public function isConnected(string $database = '', ConnectionMode $mode = ConnectionMode::READ): bool
    {
        if($this->hosts[ConnectionMode::READ->value] == $this->hosts[ConnectionMode::WRITE->value])
            $mode = ConnectionMode::READ; // same as host
        $database = $database ?: $this->default_database;
        return isset($this->connections[$mode->value][$database]);
    }

    /**
     * Closes all connections and clears them from the register
     */
    public function close(): bool
    {
        $readConnections = &$this->connections[ConnectionMode::READ->value];
        $writeConnections = &$this->connections[ConnectionMode::WRITE->value];

        if(is_array($readConnections)) {
            foreach($readConnections as $database => $conid) if($conid instanceof ConnectionWrapper) {
                $conid->close();
                // workaround, sonst schlägt die Schleife für write mode fehl. // But why? The documentation doesn't say close isn't idempotent
                if((isset($writeConnections[$database])) && ($conid == $writeConnections[$database]))
                    unset($writeConnections[$database]);
            }
            $readConnections = [];
        }
        if(is_array($writeConnections)) {
            foreach($writeConnections as $conid) if($conid instanceof ConnectionWrapper)
                $conid->close();
            $writeConnections = [];
        }
        return true;
    }

    /**
     * Returns the first command of the most recently executed statement in uppercase e.g. SELECT
     */
    public function getLastQueryCommand(): string
    {
        return $this->last_command;
    }

    /**
     * Returns the number of rows affected by a previous INSERT, UPDATE, or DELETE operation.
     */
    public function affectedRows(mixed $query_resource = null): int|false
    {
        $affectedRows = $this->lastConnectionWrapper?->getAffectedRows($query_resource ?? $this->query_resource);
        return $affectedRows === -1 ? false : $affectedRows ?? false;
    }

    /**
     * Returns an array of all rows in a query resource
     */
    public function fetchRowSet(mixed $query_resource = null, ?callable $callbackOnFetchRow = null, array $metaData = []): array
    {
        $query_resource ??= $this->query_resource;

        $rowSet = [];
        while(($row = $this->driver->fetch($query_resource))) {
            if($metaData) {
                // convert to php types
                foreach($row as $col => $val) {
                    if(isset($metaData['columns'][$col]) && $val !== null) {
                        settype($row[$col], $metaData['columns'][$col]['phpType']);
                    }
                }
            }
            if($callbackOnFetchRow) $row = call_user_func($callbackOnFetchRow, $row);
            $rowSet[] = $row;
        }
        return $rowSet;
    }

    /**
     * Liefert die ID einer vorherigen INSERT-Operation.
     * Hinweis:
     * mysql_insert_id() konvertiert den Typ der Rueckgabe der nativen MySQL C API Funktion mysql_insert_id() in den Typ long (als int in PHP bezeichnet).
     * Falls Ihre AUTO_INCREMENT Spalte vom Typ BIGINT ist, ist der Wert den mysql_insert_id() liefert, nicht korrekt. Verwenden Sie in diesem Fall
     * stattdessen die MySQL interne SQL Funktion LAST_INSERT_ID() in einer SQL-Abfrage
     *
     * @return int|string Bei Erfolg die letzte ID einer INSERT-Operation
     */
    public function lastId(): int|string
    {
        return $this->driver->getLastId($this->lastConnectionWrapper);
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
        $query_resource = $this->query($sql);
        if(!$query_resource) return 0;
        $row = $this->fetchRow($query_resource);//fetch first row (only row
        $this->free($query_resource);
        return (int)$row['foundRows'];//default to 0
    }

    /**
     * Executes a SQL-Statement.<br>
     * Saves query to this->sql<br>
     * Resets query_result<br>
     * Gets a conid and saves it to last_connect_id<br>
     * Updates last command on success
     *
     * @return mixed query result / query resource
     * @throws \Exception
     * @see DataInterface::__get_db_conid
     */
    public function query(string $query, string $database = ''): mixed
    {
        //Store query in attribute
        $this->last_Query = $sql = ltrim($query);
        // reset query result
        $this->query_resource = false;
        if(!$sql)//nothing to do
            return false;
        //identify command
        $command = $this->identifyCommand($sql);
        if(IS_TESTSERVER && !in_array($command, $this->commands))
            echo "Unknown command: '$command'<br>".
                "in $sql<hr>".
                'Please contact the POOL\'s maintainer to analyze the DataInterface in the query() function.';
        $isSELECT = $command == 'SELECT';//mode selection
        $mode = !$isSELECT || $this->force_backend_read ? ConnectionMode::WRITE : ConnectionMode::READ;
        if($isSELECT)
            $this->totalReads++;
        else
            $this->totalWrites++;
        $this->totalQueries++;

        $connectionWrapper = $this->__get_db_conid($database, $mode);//connect
        $this->query_resource = $connectionWrapper->query($sql);//run
        $this->lastConnectionWrapper = $connectionWrapper;
        if($this->query_resource) $this->last_command = $command;
        if(defined($x = 'LOG_ENABLED') && constant($x) &&
            defined($x = 'ACTIVATE_INTERFACE_SQL_LOG') && constant($x) == 2 &&
            ($Log = Singleton::get('LogFile'))->isLogging())
            //Logging enabled
            $Log->addLine('SQL MODE: '.$mode->name);
        return $this->query_resource;
    }

    /**
     * Identifies the command of a query
     *
     * @param string $sql
     * @return string command (e.g. SELECT, INSERT, UPDATE, DELETE)
     */
    private function identifyCommand(string $sql): string
    {
        $offset = strspn($sql, "( \n\t\r");//skip to the meat
        //find position of first whitespace, starting from magic value 2 from old code
        $pos = strcspn($sql, " \n\r\t", $offset + 2) + 2;// TODO MySQL Syntax DO, USE?
        return strtoupper(substr($sql, $offset, $pos));//cut command from Query
    }

    /**
     * Liefert einen Datensatz als assoziatives Array und indiziertes Array
     */
    private function fetchRow(mixed $query_resource = null): array|null|false
    {
        $query_resource ??= $this->query_resource;
        return $query_resource ? $this->driver->fetch($query_resource) : false;
    }

    /**
     * Frees the memory associated with a result
     *
     * @param mixed $query_resource Query Ergebnis-Kennung
     * @return void Bei Erfolg true, bei Misserfolg false
     */
    public function free(mixed $query_resource = null): void
    {
        $query_resource ??= $this->query_resource;
        $this->driver->free($query_resource);
    }

    /**
     * Returns three lists (fieldList with metadata, fieldNames, primary key) of a table
     *
     * @param string $database
     * @param string $table
     * @return array<string, array<string, array<string, string>|string>> fieldList, fieldNames, primary key
     * @throws \Exception
     */
    public function getTableColumnsInfo(string $database, string $table): array
    {
        if(!$database || !$table) {
            throw new InvalidArgumentException('Database and table names must be non-empty strings.');
        }
        return $this->__get_db_conid($database, ConnectionMode::READ)->getTableColumnsInfo($database, $table);
    }

    /**
     * Get information about a column
     *
     * @throws \Exception
     */
    public function getColumnMetadata(string $database, string $table, string $field): array
    {
        if(!$database || !$table || !$field) {
            throw new InvalidArgumentException('Database, table and field names must be non-empty strings.');
        }

        $query_resource = $this->__get_db_conid($database, ConnectionMode::READ)->query("SHOW COLUMNS FROM `$table` like '$field'");
        if(!$query_resource) {
            throw new Exception("Could not get column metadata for $database.$table.$field");
        }
        $row = [];
        if($this->numRows($query_resource)) $row = $this->fetchRow($query_resource);
        $this->free($query_resource);
        return $row;
    }

    /**
     * Returns the number of rows in a query resource
     */
    public function numRows(mixed $query_resource = null): int
    {
        $query_resource ??= $this->query_resource;
        $result = $this->lastConnectionWrapper?->getNumRows($query_resource) ?? 0;
        assert(is_int($result));
        return $result;
    }

    /**
     * Returns the last error of the last executed query as text
     *
     * @return string error code and message (code: message)
     */
    public function getErrorAsText(): string
    {
        $result = $this->getError();
        return "{$result["code"]}: {$result["message"]}";
    }

    /**
     * Returns the error of the last executed query
     *
     * @return array
     */
    public function getError(): array
    {
        return $this->driver->errors($this->lastConnectionWrapper)[0] ?? [];
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
     * Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
     *
     * @param string $string string
     * @return string escaped string
     * @throws \Exception
     */
    public function escape(string $string, $database = ''): string
    {
        $connection = $this->__get_db_conid($database, ConnectionMode::READ);
        return $this->driver->escape($connection, $string);
    }

    /**
     * For debugging purposes
     */
    public function getLastQuery(): string
    {
        return $this->last_Query;
    }
}