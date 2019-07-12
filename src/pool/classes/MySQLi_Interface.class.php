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
	* @version $Id: MySQLi_Interface.class.php 38240 2019-05-31 09:09:13Z manhart $
	* @version $Revision: 38240 $
	*
	* @see DataInterface.class.php
	* @since 2019/02/28
	* @author Alexander Manhart <alexander@manhart.bayern>
	* @link https://www.manhart-it.de
	*/

	if(!defined('MYSQLi_LAYER'))
	{
		#### Prevent multiple loading
		define('MYSQLi_LAYER', 'mysql5');

		$dbaccessfile = @constant('DBACCESSFILE');
		if (file_exists($dbaccessfile)) {
	    	require_once $dbaccessfile;
		}

		if(!defined('SQL_READ')) define('SQL_READ', 'READ');
		if(!defined('SQL_WRITE')) define('SQL_WRITE', 'WRITE');

		/**
		 * MySQLi_Interface
		 *
		 * MySQL Datenbank Layer (Schnittstelle zum MySQL Server)
		 *
		 * @package pool
		 * @author Alexander Manhart <alexander@manhart.bayern>
		 * @version $Id: MySQLi_Interface.class.php 38240 2019-05-31 09:09:13Z manhart $
		 * @access public
		 **/
		class MySQLi_Interface extends DataInterface
		{
            /**
             * @var array Array of MySQL Links der Default Datenbank (wird mit dem Constructor bestimmt), Aufbau $var[$mode]["default"]
             */
			private $db_connect_id = array(SQL_READ => array(), SQL_WRITE => array());

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
			 * @var array
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
			* Constructor
		    *
	    	* @access public
		    */
			function __construct()
			{
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
			 * @param array $Packet Einstellungen
			 * @return boolean Erfolgsstatus
			 **/
			function setOptions($Packet)
			{
				$this->persistency = array_key_exists('persistency', $Packet) ? $Packet['persistency'] : false;
				$this->force_backend_read = array_key_exists('force_backend_read', $Packet) ? $Packet['force_backend_read'] : false;

				if (!array_key_exists('host', $Packet)) {
					$this->raiseError(__FILE__, __LINE__, 'MySQL_Interface::setOptions Bad Packet: no key "host"');
					return false;
				}
				$this->available_hosts =  $Packet['host'];

				if (!array_key_exists('database', $Packet)) {
					$this->raiseError(__FILE__, __LINE__, 'MySQL_Interface::setOptions Bad Packet: no key "database"');
					return false;
				}
				$this->default_database = $Packet['database'];
				
				if(array_key_exists('port', $Packet)) {
				    $this->port = $Packet['port'];
                }

				if (array_key_exists('auth', $Packet)) {
					$this->auth = $Packet['auth'];
				}
				else {
					$this->auth = 'mysql_auth'; // verwendet zentrale, globale Authentifizierung
				}

				if(array_key_exists('charset', $Packet)) {
					$this->default_charset = $Packet['charset'];
				}

				$this->__findHostForConnection();

				return true;
			}

			/**
			 * Nimmt nach dem Zufallsprinzip einen Server-Host fuer die Verbindung
			 *
			 * @return boolean
			 */
			function __findHostForConnection($connect=false, $database=null, $mode=null)
			{
				#### MySQL Server aufgeteilt in Lesecluster und Schreibcluster:
				if (is_array($this->available_hosts)) {

					$read_key = 0;
					$write_key = 1;

					$host_read = false;
#					echo '<br> var SQL_READ is '.(bool2string(isset($this->available_hosts[SQL_READ])));
					if(isset($this->available_hosts[SQL_READ]) and (is_null($mode) or $mode == SQL_READ)) {
#						echo '<br>I ve '.sizeof($this->available_hosts[SQL_READ]).' hosts for reading<br>';
#						echo pray($this->available_hosts[SQL_READ]);
						mt_srand(getMicrotime(10000));
						$read_key = mt_rand(1, sizeof($this->available_hosts[SQL_READ]))-1;
						#$read_key = array_rand($this->available_hosts[SQL_READ]);
						$host_read = $this->available_hosts[SQL_READ][$read_key];
						unset($this->available_hosts[SQL_READ][$read_key]);
						$this->available_hosts[SQL_READ] = array_values($this->available_hosts[SQL_READ]);
					}
					elseif(isset($this->available_hosts[$read_key])) {
						$host_read = $this->available_hosts[$read_key];
					}

					$host_write = false;
					if(isset($this->available_hosts[SQL_WRITE]) and (is_null($mode) or $mode == SQL_WRITE)) {
						mt_srand(getMicrotime(10000));
						$write_key = mt_rand(1, sizeof($this->available_hosts[SQL_WRITE]))-1;
						#$write_key = array_rand($this->available_hosts[SQL_WRITE], 1);
						$host_write = $this->available_hosts[SQL_WRITE][$write_key];
						unset($this->available_hosts[SQL_WRITE][$write_key]);
					}
					elseif(isset($this->available_hosts[$write_key])) {
						$host_write = $this->available_hosts[$write_key];
					}

					if($host_read) $this->host[SQL_READ] = $host_read;
					if($host_write) $this->host[SQL_WRITE] = $host_write;
				}
				#### Ein MySQL Server fuer Lesen und Schreiben
				elseif (is_string($this->available_hosts)) {
					$this->host = array(
						SQL_READ => $this->available_hosts,
						SQL_WRITE => $this->available_hosts
					);
				}

				if($connect and $database and $mode) {
					return $this->__get_db_conid($database, $mode);
				}
				return true;
			}

			/**
			 * Ermittelt, ob noch Master-/Slave Hosts zur Verfuegung stehen
			 *
			 * @param string $mode
			 * @return boolean
			 */
			function hasAnotherHost($mode)
			{
				return (is_array($this->available_hosts) and
					isset($this->available_hosts[$mode]) and
					is_array($this->available_hosts[$mode]) and
					sizeof($this->available_hosts[$mode]));
			}

			/**
			* MySQL_Interface::__get_auth()
			*
			* Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
			*
			* @param $mode constant Beschreibt den Zugriffsmodus Schreib-Lesevorgang
	    	* @return Array mit Key username und password
			*
    		* @access private
	    	*/
			function __get_auth($mode) {
				$name_of_array = $this->auth;
//				echo $name_of_array;
//				echo pray($GLOBALS);
				$auth = $GLOBALS[$name_of_array];
#				echo ' auth:'.pray($auth);
#				global $$name_of_array;
#				$auth = $$name_of_array;

				$authentication = array();
				if (is_array($auth)) {
					if ($mode == SQL_READ) {
						if (array_key_exists($this -> host[SQL_READ], $auth)) {
							$authentication = $auth[$this -> host[SQL_READ]];
						}
					}
					else {
						if (array_key_exists($this -> host[SQL_READ], $auth)) {
							$authentication = $auth[$this -> host[SQL_WRITE]];
						}
					}
				}
				else {
					$this -> raiseError(__FILE__, __LINE__, 'MySQL access denied! No authentication data available ' .
						'(Database: '.$this -> host[$mode] . ' Mode: '.$mode.').');
				}
				return $authentication;
			}

			/**
			* MySQL_Interface::__get_db_pass()
			*
			* Holt die Authentication-Daten und gibt das Passwort zurueck
			*
			* @param string $database Datenbank
			* @param constant $mode Lese- oder Schreibmodus
		    * @return string Gibt das Passwort zurueck
			*
	    	* @access private
		    */
        	function __get_db_pass($database, $mode)
			{
				$auth = $this -> __get_auth($mode);

				$pass = '';
				if (array_key_exists('all', $auth)) {
				    $database = 'all'; // Special
				}
				if (array_key_exists($database, $auth)) {
					$pass = $auth[$database]['password'];
				}
            	return $pass;
	        }

			/**
			* __get_db_user()
			*
			* Holt die Authentication-Daten und gibt den Usernamen zurück
			*
			* @param $database string Datenbank
			* @param $mode constant Lese- oder Schreibmodus
		    * @return string Gibt den Usernamen zurück
			*
	    	* @access private
		    */
        	function __get_db_user($database, $mode)
			{
				$auth = $this -> __get_auth($mode);

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
			* @param $mode constant Lese- oder Schreibmodus
		    * @return resource Gibt Resource der MySQL Verbindung zurueck
			*
	    	* @access private
		    */
        	function __get_db_conid($database, $mode)
			{
				$conid = false;

				if ($this->host[SQL_READ] == $this->host[SQL_WRITE]) {
					$mode = SQL_READ; // same as WRITE
				}

				if ($database == '') {
					$database = $this->default_database;
				}

				if ($database != '') {
		            if (array_key_exists($database, $this->connections[$mode])) {
    		        	$conid = $this->connections[$mode][$database];
        			}
					else {
						$user = $this->__get_db_user($database, $mode);
					}

					#echo chr(10).'db: '.$database.' user:'.$user.' pass:'.$this -> __get_db_pass($database, $mode).chr(10);

					if (!$conid) {
		                $host = $this->host[$mode].':'.$this->port;
		                $conid = mysqli_connect($host, $user, $this->__get_db_pass($database, $mode), '', $this->port);
//                        $conid = ($this->persistency) ? @mysql_pconnect($host, $user, $this->__get_db_pass($database, $mode), 0) :
//                                                        @mysql_connect($host, $user, $this->__get_db_pass($database, $mode), true, 0);
							# echo '#connected to ' . $database . ' with mode ' . $mode . ' (conid: '.$conid.')<br>';
							#if(basename($_SERVER['PHP_SELF']) != 'index.php') echo '#connected to mysql server: <b>'.$this->host[$mode].'</b> (mode: '.$mode.')<br>';

						$connection_success = (!($conid) === false);

						// SQL Statement Logging:
						if (defined('LOG_ENABLED') and LOG_ENABLED and defined('ACTIVATE_INTERFACE_SQL_LOG')) {
							if(ACTIVATE_INTERFACE_SQL_LOG >= 1) {
								$Log = &Singleton('Log');
								$mode_txt = $mode;
								if($Log->isLogging()) {
									$Log->addLine('CONNECTED TO '.$this->host[$mode].' MODE: '.$mode_txt.' DB: '.$database);
									if(!$connection_success) $Log->addLine('FAILED TO CONNECT TO '.$this->host[$mode].' MODE: '.$mode.' DB: '.$database.
                                                                           ' (MySQL-Error: '.mysqli_connect_errno().': '.mysqli_connect_error().')');
								}
							}
						}

						if ($connection_success) {
							// Standard Zeichensatz fuer die Verbindung setzen
							if($this->default_charset) if(!$this->_setNames($this->default_charset, $conid)) {
								$errmsg = 'MySQL ErrNo '.mysqli_errno($conid).': '.mysqli_error($conid);
								$this->raiseError(__FILE__, __LINE__, $errmsg);
							}
							$dbselect = @mysqli_select_db($conid, $database);
							if (!$dbselect) {
								$this->raiseError(__FILE__, __LINE__, mysqli_error($conid));
								mysqli_close($conid);
								$conid = null;
							}
							else {
								$this->connections[$mode][$database] = $conid;
							}
						}
						else {
							if($this->hasAnotherHost($mode)) {
								#echo 'hasAnotherHost with mode: '.$mode;
								return $this->__findHostForConnection($reconnect=true, $database, $mode);
							}

							$errmsg = 'MySQL connection to host \''.$this->host[$mode].'\' with mode '.$mode.' failed! Used default database \''.$database.
                                      '\' (MySQL ErrNo '.mysqli_connect_errno().': '.mysqli_connect_error().')!';
//							ACHTUNG!!!!!! verursacht über ExceptionHandler eine Endlosschleife, wenn POOL_ERROR_DB als Modus angegeben wurde!!!
//							__error2db in ExceptionHandler wurden jedoch MySQL Fehler >2000 <2055 abgefangen und sollte derartige Szenarien verhindern
//							am besten Object::raiseError verwenden, denn das verwendet die PHP Funktion trigger_error und verhindert, dass innerhalb
//							eines Fehlers nochmals Fehler entstehen!

/*							$Xception = new Xception($errmsg, 0, magicInfo(__FILE__, __LINE__, __FUNCTION__,	__CLASS__, compact('mode', 'database', 'user')), null);
							$Xception->raiseError();*/
							$this->raiseError(__FILE__, __LINE__, $errmsg);
						}
					}
				}
				else {
					$this->raiseError(__FILE__, __LINE__, 'No database selected (__get_db_conid)!');
				}

				return $conid;
	        }

			/**
			* Baut eine Verbindung zur Datenbank auf.
			*
			* @param string $database Datenbank
    		* @access public
	    	*/
			function connect($database='')
			{
				$result = $this->__get_db_conid($database, SQL_READ);
				if($result != false and $this->host[SQL_READ] != $this->host[SQL_WRITE]) {
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
			*
	    	* @access public
		    */
			function isConnected($database='', $mode=SQL_READ)
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

				if(!isset($this->connections[$mode]) or
				  !isset($this->connections[$mode][$database]) or
				  !is_resource($this->connections[$mode][$database])) {
					return false;
				}
				return mysqli_ping($this->connections[$mode][$database]);
				//return is_resource($this -> connections[$mode][$database]);
			}

			/**
			 * Schliesst alle Verbindungs-Kennungen.
			 *
			 * @access public
			 * @return boolean true
			 **/
			function close()
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
             * Fuehrt ein SQL-Statement aus
			 *
			 * @access public
 			 * @param string $query SQL-Statement
			 * @param string $database Datenbankname (default '')
			 * @return boolean Erfolgsstatus
			 **/
			function query($query, $database='')
			{
				//
				// Remove any pre-existing queries
				//
				$this->sql = ltrim($query);

				$command = '';

				// reset query result
				if($this->query_result) {
//					$query_id = intval($this->query_result);
//					unset($this->row[$query_id]);
//					unset($this->rowset[$query_id]);
					$this->query_result = false;
				}

				if($this->sql != '') {
					$this->num_queries++;

					$buf = $this->sql;
					#echo '<hr>'.$buf.'<br>';
					if($buf{0} == '(') $buf = ltrim(substr($buf, 1));
					$posSpace = strpos($buf, chr(32), 2);
					$posLN = strpos($buf, chr(10), 2); // TODO MySQL Syntax DO, USE?
					$posCR = strpos($buf, chr(13), 2);

					$pos = -1;
					if($posLN !== false and $posLN < $posSpace) {
						$pos = $posLN;
					}
					if($posCR !== false and $posCR < $posSpace) {
						$pos = $posCR;
					}
					if($pos == -1) {
					    $pos = $posSpace;
                    }
                    if($pos == false) {
                        $pos = strlen($buf);
                    }

					#echo 'pos: '.$pos.'<br>';
					#echo 'pos2:'.strpos($buf, chr(20), 3).'<br>';
					$command = strtoupper(substr($buf, 0, $pos));
					if(IS_TESTSERVER and $command != 'SELECT' and $command != 'SHOW' and $command != 'INSERT' and
						$command != 'UPDATE' and $command != 'DELETE' and $command != 'EXPLAIN' and $command != 'ALTER'
						and $command != 'CREATE' and $command != 'DROP' and $command != 'RENAME' and $command != 'CALL'
						and $command != 'REPLACE' and $command != 'TRUNCATE' and $command != 'LOAD' and $command != 'HANDLER'
						and $command != 'DESCRIBE' and $command != 'START' and $command != 'COMMIT' and $command != 'ROLLBACK'
						and $command != 'LOCK' and $command != 'SET' and $command != 'STOP' and $command != 'RESET'
						and $command != 'CHANGE' and $command != 'PREPARE' and $command != 'EXECUTE' and $command != 'DEALLOCATE'
						and $command != 'DECLARE' and $command != 'OPTIMIZE' and $command != 'ROLLBACK') {
						echo 'Unknown command: "'.$command.'"<br>';
						echo 'in '.$this->sql;
						echo '<hr>Please contact Alexander Manhart for MySQL_Interface in the function query()';
					}
					unset($buf);

					$isSELECT = ($command == 'SELECT');
					if ($isSELECT and ($this->force_backend_read == false)) {
						// read
						$mode = SQL_READ;
						// sollte überarbeitet werden:
						$this->num_local_queries++;
					}
					else {
						// write
						$mode = SQL_WRITE;
						// sollte überarbeitet werden:
						if($isSELECT) {
							$this->num_local_queries++;
						}
						else {
							$this->num_remote_queries++;
						}
					}

					$conid = $this->__get_db_conid($database, $mode);
					if (!$conid) {
						return false;
					}

					$this->query_result = @mysqli_query($conid, $this->sql, MYSQLI_STORE_RESULT);
					$this->last_connect_id = $conid;

					if (defined('LOG_ENABLED') and LOG_ENABLED and defined('ACTIVATE_INTERFACE_SQL_LOG')) {
						if(ACTIVATE_INTERFACE_SQL_LOG == 2) {
							$Log = &Singleton('Log');
							$mode_txt = $mode;
							if($Log->isLogging()) {
								$Log->addLine('SQL MODE: '.$mode_txt);
							}
						}
					}
				}

				if($this->query_result) {
					$this->last_command = $command;
                }
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
				if(!$query_result) {
					if (isset($this->query_result)) {
                        $query_result = $this->query_result;
					}
				}

				$numrows = 0;
				if($query_result instanceof mysqli_result) {
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
				if(!$query_result ) {
					if (isset($this->query_result)) {
                        $query_result = $this->query_result;
					}
				}

				$numfields = 0;
				if($query_result instanceof mysqli_result) {
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
				if( !$query_id ) {
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
				if(!$query_id) {
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
				if(!$query_resource) {
					if (isset($this->query_result)) {
						$query_resource = $this->query_result;
					}
				}
				

				if($query_resource) {
					$row = mysqli_fetch_assoc($query_resource);
					return $row;
				}
				else {
					return false;
				}
			}

			/**
			 * Liefert einen Datensatz als assoziatives Array und numerisches Array
			 *
			 * @access public
			 * @param mysqli_result|false $query_result
			 * @return array Bei Erfolg ein Array mit allen Datensaetzen ($array[index]['feldname'])
			 **/
			public function fetchrowset($query_result = false)
			{
				if(!$query_result) {
					if (isset($this->query_result)) {
                        $query_result = $this->query_result;
					}
				}

                $rowset = array();
				if($query_result instanceof mysqli_result) {
					while(($row = mysqli_fetch_assoc($query_result)) != null) {
					    $rowset[] = $row;
					}
				}
                return $rowset;
			}

			/**
			 * Liefert ein Objekt mit Feldinformationen aus einem Anfrageergebnis
			 *
			 * @param string $field Feldname
			 * @param integer $rownum Feld-Offset
			 * @param integer $query_id Query Ergebnis-Kennung
			 * @return string Wert eines Feldes
			 **/
			function fetchfield($field, $rownum=-1, $query_resource=null)
			{
                $result = false;
				if(!$query_resource) {
					if (isset($this->query_result)) {
						$query_resource = $this->query_result;
					}
				}

				if($query_resource) {
					if($rownum > -1) {
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
			private function mysqli_result($query_result, $rownum, $field=0)
            {
                if(!mysqli_data_seek($query_result, $rownum)) return false;
                if(!($row = mysqli_fetch_array($query_result))) return false;
                if(!array_key_exists($field, $row)) return false;
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
				if( !$query_id ) {
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
				if($query_id) {
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
			 * @return array Liste mit Feldern ($array['name'][index], etc.)
			 **/
			function listfields($table, $database, &$fields, &$pk)
			{
				$arr = array();

				$query = 'SHOW COLUMNS FROM `'.$table.'`';
				$result = mysqli_query($this->__get_db_conid($database, SQL_READ), $query, MYSQLI_STORE_RESULT);

				// @deprecated in PHP 5, 6.8.09, AM
				# $fields = mysql_list_fields($database, $table, $this -> __get_db_conid($database, SQL_READ));

				if ($result !== false) {
					if(mysqli_num_rows($result) > 0) {
						$i = 0;
						while ($row = mysqli_fetch_assoc($result)) {
							$arr[] = $row;
							$fields[] = $row['Field'];
							if($row['Key'] == 'PRI') {
								$pk[] = $row['Field'];
							}
							$arr[$i]++;
						}
					}
					$this->freeresult($result);
				}

				return $arr;
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
				if(!$query_result) {
					if (isset($this->query_result)) {
                        $query_result = $this->query_result;
					}
				}

				if($query_result instanceof mysqli_result) {
				    $xdebug_is_debugger_active = false;
				    if(function_exists('xdebug_is_debugger_active')) {
                        $xdebug_is_debugger_active = xdebug_is_debugger_active();
                    }
                    // attention: xdebug shows strange error messages: Can't fetch mysqli_result
				    if(!$xdebug_is_debugger_active) {
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
				$message = $result["code"].": ".$result["message"];
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
			 **/
			function escapestring($string, $database='')
			{
				$conid = $this->__get_db_conid($database, SQL_READ);
				return mysqli_real_escape_string($conid, $string);
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
			function _setNames($charset_name, $conid=null)
			{
			    return mysqli_set_charset($conid, $charset_name);
				// return mysql_query('SET NAMES \''.$charset_name.'\'', $conid);
			}
		}
	}