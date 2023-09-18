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
use Log;
use mysqli_sql_exception;
use pool\classes\Core\PoolObject;
use pool\classes\Core\RecordSet;
use pool\classes\Database\Exception\DatabaseConnectionException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use pool\classes\Utils\Singleton;
use Stopwatch;
use function constant;
use function defined;

class DataInterface extends PoolObject
{
    /**
     * Date and time constants (@todo rethink maybe move to a separate database class?)
     */
    public const ZERO_DATE = '0000-00-00';
    public const ZERO_TIME = '00:00:00';
    public const ZERO_DATETIME = '0000-00-00 00:00:00';
    public const MAX_DATE = '9999-12-31';
    public const MAX_DATETIME = '9999-12-31 23:59:59';

    /**
     * @var array Array of registered resources
     */
    private static array $register = [];

    /**
     * @var string Last executed query for debugging purposes
     */
    public string $last_Query;

    /** Alle verfügbaren Master u. Slave Hosts */
    private string|array $available_hosts = [];

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
     * @var \pool\classes\Database\Connection|null Last used connection link in query()
     * @see DataInterface::_query()
     */
    private ?Connection $lastConnection;

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

    /**
     * @var array array of hosts for read and write operations
     */
    private array $hosts = [];

    /**
     * Enthält den Variablennamen des Authentication-Arrays
     */
    private string $auth = '';

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
     * Available options
     * host = (array)|(string) Hosts of the database servers (clusters can also be defined: host[0] = read; host[1] = write)
     * database = (array)|(string) Databases to connect to
     * port = (int) Port to connect to
     * charset = (string) Character set for the connection
     * auth = (array) Authentication Array, Default 'mysql_auth'
     * force_backend_read = (bool) Enforce read operations over the master host for write operations
     *
     * @see access.inc.php
     * @param array $connectionOptions Einstellungen
     * @return boolean Erfolgsstatus
     * @throws \pool\classes\Exception\InvalidArgumentException
     */
    public function setOptions(array $connectionOptions): bool
    {
        // $this->persistence = array_key_exists('persistence', $Packet) ? $Packet['persistence'] : false;
        $this->force_backend_read = $connectionOptions['force_backend_read'] ?? false;

        $this->available_hosts = $connectionOptions['host'] ??
            throw new InvalidArgumentException('DataInterface::setOptions Bad Packet: no key "host"');

        $dataBases = (array)($connectionOptions['database'] ?? []);

        $this->default_database = $dataBases[0] ?? throw new InvalidArgumentException('DataInterface::setOptions Bad Packet: no key "database"');

        // @todo eliminate port and put it into the host string
        if(array_key_exists('port', $connectionOptions))
            $this->port = $connectionOptions['port'];

        if(array_key_exists('charset', $connectionOptions))
            $this->charset = $connectionOptions['charset'];

        $this->auth = $connectionOptions['auth'] ?? 'mysql_auth';// fallback verwendet zentrale, globale Authentifizierung

        $this->findHostForConnection();
        foreach($dataBases as $alias => $dataBase) {
            if(!is_string($alias))
                $alias = $dataBase;
             $alias = "{$this->hosts[ConnectionMode::READ->value]}|{$this->hosts[ConnectionMode::WRITE->value]}|$alias";
            self::registerResource([$alias => ['interface' => $this, 'name' => $dataBase]]);
        }
        return true;
    }

    /**
     * When using clusters moves random hosts from $this->available_hosts to $this->hosts
     */
    protected function findHostForConnection(ConnectionMode $connectionMode = null): int
    {
        $available_hosts =& $this->available_hosts;
        $alternativeHosts = 0;
        if(is_array($available_hosts))
            /** Multiple Clusters: move one random host to the hosts list*/
            foreach($this->modes as $clusterMode => $clusterModeSpecificIndexUsedInAvailableHosts) {
                /** @var array|null $hostList reference to hosts available in this mode */
                $hostList =& $available_hosts[$clusterMode];
                if((!$connectionMode || $connectionMode === $clusterMode) && $hostList) {//targeting that specific mode or no specific one
                    $key = array_rand($hostList);//changed from random int-key
                    $host = $hostList[$key];
                    unset($hostList[$key]);//remove option
                    if($clusterMode === ConnectionMode::READ)//is this just an error in the original code?
                        $hostList = array_values($hostList);//reindex; should be unnecessary with array_rand
                    $alternativeHosts += count($hostList);
                }
                else//requested connectionMode isn't matching clusterMode or the cluster mode has no remaining hosts
                    // no clue what's going on here I presume this fetches a default
                    $host = $available_hosts[$clusterModeSpecificIndexUsedInAvailableHosts] ?? false;

                if($host) $this->hosts[$clusterMode] = $host;
            }
        else {
            /** One database server for reading and writing */
            $this->hosts = [
                ConnectionMode::READ->value => $available_hosts,
                ConnectionMode::WRITE->value => $available_hosts,
            ];
        }

        return $alternativeHosts;
    }

    /**
     * Registers a resource for the DataInterface
     */
    public static function registerResource(array $resourceDefinition): void
    {
        foreach($resourceDefinition as $alias => $item){
            if(array_key_exists($alias, self::$register)){
                throw new InvalidArgumentException("A database with the alias '$alias' has already been registered before");
            }
            self::$register[$alias] = $item;
        }
    }

    /**
     * Retrieves a registered resource
     * @throws \pool\classes\Exception\InvalidArgumentException
     */
    public static function getInterfaceForResource(string $alias):self
    {
        return self::$register[$alias]['interface'] ?? throw new InvalidArgumentException("The requested database '$alias' has not (yet) registered an interface");
    }

    /**
     * Create alias for a database
     */
    public function getAlias(string $database): string
    {
        return "{$this->hosts[ConnectionMode::READ->value]}|{$this->hosts[ConnectionMode::WRITE->value]}|$database";
    }

    /**
     * Execute an SQL statement and return the result as a pool\classes\Core\ResultSet.
     *
     * @throws \Exception
     */
    public function execute(string $sql, string $dbname, ?callable $callbackOnFetchRow = null, array $metaData = []): RecordSet
    {
        $interface = static::getInterfaceForResource($this->getAlias($dbname));
        /** @var ?Stopwatch $Stopwatch Logging Stopwatch */
        $doLogging = defined($x = 'LOG_ENABLED') && constant($x);
        $Stopwatch = $doLogging && defined($x = 'ACTIVATE_RESULTSET_SQL_LOG') && constant($x) === 1 ?
            Singleton::get('Stopwatch')?->start('SQL-QUERY') : null;// start time measurement
        try {//run
            $query_resource = $interface->query($sql, $dbname);
        }
        catch(Exception $e) {
            if($e instanceof mysqli_sql_exception) {//keeping old behavior for g7Logistics
                throw $e;
            }
        }
        if($query_resource ??= false) {//success
            switch($interface->getLastQueryCommand()) {
                case 'SELECT':
                case 'SHOW':
                case 'DESCRIBE':
                case 'EXPLAIN': //? or substr($cmd, 0, 1) == '('
                    //? ( z.B. UNION
                    if($interface->numRows($query_resource)) {
                        $RecordSet = new RecordSet($interface->fetchRowSet($query_resource, $callbackOnFetchRow, $metaData));
                    }
                    else {
                        $RecordSet = new RecordSet();
                    }
                    $interface->free($query_resource);
                    break;
                /** @noinspection PhpMissingBreakStatementInspection */
                case 'INSERT'://DML commands
                    $last_insert_id = $interface->lastId();
                    $idColumns = [
                        'last_insert_id' => $last_insert_id,
                        'id' => $last_insert_id,
                    ];
                case 'UPDATE':
                case 'DELETE':
                case 'CREATE':
                case 'ALTER':
                case 'DROP':
                case 'RENAME':
                case 'TRUNCATE':
                case 'OPTIMIZE':
                case 'ANALYZE':
                case 'CHECK':
                    $affected_rows = $interface->affectedRows();
                    $row = [//id of inserted record or number of rows
                            0 => $last_insert_id ?? $affected_rows,
                            'affected_rows' => $affected_rows,
                        ] + ($idColumns ?? []);
                    $RecordSet = new RecordSet([0 => $row]);
                    break;
                default:
                    throw new RuntimeException("Unknown command: '{$interface->getLastQueryCommand()}' in $sql");
            }
        }
        else {//statement failed
            $error_msg = $e?->getMessage() ?? "{$interface->getErrorAsText()} SQL Statement failed: $sql";
            $interface->raiseError(__FILE__, __LINE__, $error_msg);//can this be replaced with an Exception?
            $error = $interface->getError();
            $error['sql'] = $sql;
            $RecordSet = (new RecordSet())->addError($error);
            // SQL Statement Error Logging:
            if($doLogging && defined($x = 'ACTIVATE_RESULTSET_SQL_ERROR_LOG') && constant($x) === 1)
                Log::error($error_msg, configurationName: Log::SQL_LOG_NAME);
        }

        // SQL Statement Performance Logging:
        if($Stopwatch && ($metaData['ResultSetSQLLogging'] ?? true)) {
            $timeSpent = $Stopwatch->stop('SQL-QUERY')->getDiff('SQL-QUERY');
            $onlySlowQueries = defined($x = 'ACTIVATE_RESULTSET_SQL_ONLY_SLOW_QUERIES') && constant($x);
            $slowQueriesThreshold = defined($x = 'ACTIVATE_RESULTSET_SQL_SLOW_QUERIES_THRESHOLD') ? constant($x) : 0.01;
            if(!$onlySlowQueries || $timeSpent > $slowQueriesThreshold)
                Log::message("SQL ON DB $dbname: '$sql' in $timeSpent sec.", $timeSpent > $slowQueriesThreshold ? Log::LEVEL_WARN : Log::LEVEL_INFO,
                    configurationName: Log::SQL_LOG_NAME);
        }
        return $RecordSet;
    }

    /**
     * Executes a SQL-Statement.<br>
     * Saves query to this->sql<br>
     * Resets query_result<br>
     * Gets a conid and saves it to last_connect_id<br>
     * Updates last command on success
     *
     * @return mixed query result / query resource
     * @throws \pool\classes\Exception\InvalidArgumentException|\Exception
     *@see DataInterface::getDBConnection
     */
    public function query(string $query, string $database): mixed
    {
        $interface = static::getInterfaceForResource($this->getAlias($database));
        //Store query in attribute
        $interface->last_Query = $sql = ltrim($query);
        // reset query result
        $interface->query_resource = false;
        if(!$sql) {//nothing to do
            return false;
        }
        //identify command
        $command = $interface::identifyCommand($sql);
        if(IS_TESTSERVER && !in_array($command, $interface->commands, true))
            echo "Unknown command: '$command'<br>".
                "in $sql<hr>".
                "Please contact the POOL's maintainer to analyze the DataInterface in the query() function.";
        $isSELECT = $command === 'SELECT';//mode selection
        $mode = !$isSELECT || $interface->force_backend_read ? ConnectionMode::WRITE : ConnectionMode::READ;
        if($isSELECT)
            $interface->totalReads++;
        else
            $interface->totalWrites++;
        $interface->totalQueries++;

        $Connection = $interface->getDBConnection($database, $mode);//connect
        $interface->query_resource = $Connection->query($sql);//run
        $interface->lastConnection = $Connection;
        if($interface->query_resource) $interface->last_command = $command;
        if(defined($x = 'LOG_ENABLED') && constant($x) &&
            defined($x = 'ACTIVATE_INTERFACE_SQL_LOG') && constant($x) === 2 &&
            ($Log = Singleton::get('LogFile'))->isLogging())
            //Logging enabled
            $Log->addLine('SQL MODE: '.$mode->name);
        return $interface->query_resource;
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
     * Opens a connection to a database
     *
     * @throws \Exception
     */
    public function open(string $database = ''): bool
    {
        $this->getDBConnection($database, ConnectionMode::READ);
        if($this->hosts[ConnectionMode::READ->value] !== $this->hosts[ConnectionMode::WRITE->value]) {
            $this->getDBConnection($database, ConnectionMode::WRITE);
        }
        return ($this->isConnected($database) and $this->isConnected($database, ConnectionMode::WRITE));
    }

    /**
     * Returns an existing DB connection for a specific database or creates a new connection
     * @throws DatabaseConnectionException|RuntimeException|\Exception
     */
    private function getDBConnection(string $database, ConnectionMode $mode): Connection
    {
        if(!($database || ($database = $this->default_database))) //No DB specified and no default given
            throw new RuntimeException('No database selected!');
        if($this->hosts[ConnectionMode::READ->value] === $this->hosts[ConnectionMode::WRITE->value])
            $mode = ConnectionMode::READ; // same as WRITE
        return $this->connections[$mode->value][$database] ?? //fetch from cache
            $this->openNewDBConnection($mode, $database);
    }

    /**
     * @throws DatabaseConnectionException|\Exception
     */
    private function openNewDBConnection(ConnectionMode $mode, string $database): Connection
    {
        $host = $this->hosts[$mode->value];
        $auth = $this->getAuth($mode);
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
            $this->findHostForConnection($mode);
            return $this->openNewDBConnection($mode, $database);
        }
        else {
            $errors = $this->driver->errors()[0] ?? ['errno' => 0, 'error' => 'Unknown'];
            throw new DatabaseConnectionException("Database connection to host '$host' with mode $mode->name failed!"
                ." Used default database '$database' (ErrNo "
                .$errors['errno'].': '.$errors['error'].')!');
        }
    }

    /**
     * Reads the authentication data and returns it
     *
     * @param \pool\classes\Database\ConnectionMode $mode Beschreibt den Zugriffsmodus Schreib-Lesevorgang
     * @return array contains database, username and password
     * @throws Exception
     */
    private function getAuth(ConnectionMode $mode): array
    {
        $auth = &$this->authentications[$this->auth];
        $auth ??=
            (file_exists($authFile = constant('DBACCESSFILE')))
                ? (require $authFile)[$this->auth] ?? []
                : [];

        $hostname = $this->hosts[$mode->value];//normalize mode for lookup
        return $auth[$hostname] ??//now testing hostname that is returned instead of reading-host
            throw new DatabaseConnectionException("Access Denied: No authentication data available for host $hostname with $mode->value mode.");
    }

    /**
     * Determines if master/slave hosts are still available
     */
    private function hasAnotherHost(ConnectionMode $mode): bool
    {
        return (is_array($hosts = $this->available_hosts[$mode->value] ?? 0)
            && count($hosts) > 0);
    }

    /**
     * Checks if a database connection exists
     */
    public function isConnected(string $database = '', ConnectionMode $mode = ConnectionMode::READ): bool
    {
        if($this->hosts[ConnectionMode::READ->value] === $this->hosts[ConnectionMode::WRITE->value])
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
            foreach($readConnections as $conid)
                if($conid instanceof Connection)
                    $conid->close();
            $readConnections = [];
        }
        if(is_array($writeConnections)) {
            foreach($writeConnections as $conid) if($conid instanceof Connection)
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
        $affectedRows = $this->lastConnection?->getAffectedRows($query_resource ?? $this->query_resource);
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
            if($callbackOnFetchRow) {
                $row = $callbackOnFetchRow($row);
            }
            $rowSet[] = $row;
        }
        return $rowSet;
    }

    /**
     * Returns the ID of auto_increment primary keys from a previous INSERT operation.
     *
     * @return int|string Bei Erfolg die letzte ID einer INSERT-Operation
     */
    public function lastId(): int|string
    {
        return $this->driver->getLastId($this->lastConnection);
    }

    /**
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück.
     *
     * @return int Anzahl Zeilen
     * @throws \Exception
     */
    public function foundRows(string $database): int
    {
        $sql = 'SELECT FOUND_ROWS() as foundRows';
        $query_resource = $this->query($sql, $database);
        if(!$query_resource) return 0;
        $row = $this->fetchRow($query_resource);//fetch first row (only row
        $this->free($query_resource);
        return (int)$row['foundRows'];//default to 0
    }

    /**
     * Identifies the command of a query
     *
     * @param string $sql
     * @return string command (e.g. SELECT, INSERT, UPDATE, DELETE)
     */
    private static function identifyCommand(string $sql): string
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
     * @throws InvalidArgumentException|\Exception
     */
    public function getTableColumnsInfo(string $database, string $table): array
    {
        if(!$database || !$table) {
            throw new InvalidArgumentException('Database and table names must be non-empty strings.');
        }
        return $this->getDBConnection($database, ConnectionMode::READ)->getTableColumnsInfo($database, $table);
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

        $query_resource = $this->getDBConnection($database, ConnectionMode::READ)->query("SHOW COLUMNS FROM `$table` like '$field'");
        if(!$query_resource) {
            throw new RuntimeException("Could not get column metadata for $database.$table.$field");
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
        $result = $this->lastConnection?->getNumRows($query_resource) ?? 0;
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
        return $this->driver->errors($this->lastConnection)[0] ?? [];
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
        $connection = $this->getDBConnection($database, ConnectionMode::READ);
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