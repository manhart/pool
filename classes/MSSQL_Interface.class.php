<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class MSSQL_Interface ist ein Datenbank-Layer fuer MSSQL (Microsoft SQL).
* Diese Klasse implementiert die Schnittstelle zu MSSQL. Ueber
* sie ist der Aufbau einer Verbindung moeglich. Sie behandelt
* alle MySQL spezifischen PHP API Befehle (z.B. mssql_connect).
*
* Dabei kapselt sie nicht nur einfach die API Befehle, sondern
* beherrscht eine komplette Verbindungs-Kennung-Verwaltung
* zur Resourcen-Sharing bereit.
* Sie liefert eine Statistik ueber Anzahl der ausgefuehrten
* Queries in einem Script, hilft beim Debug durch den Log des
* zuletzt ausgefuehrten SQL Statements und das geilste:
* sie kann mit einer Cluster-Datenbank, die in ein
* Schreib und Lese-Cluster aufgeteilt ist, umgehen!!!
*
* Verbindungen werden nur einmalig geoffnet und am Ende
* der Script Ausfuehrung ueber MSSQL_Interface::close
* geschlossen.
*
* $Log: MSSQL_Interface.class.php,v $
* Revision 1.1  2005/06/03 07:03:48  manhart
* Initial Import
*
*
* @version $Id: MSSQL_Interface.class.php,v 1.1 2005/06/03 07:03:48 manhart Exp $
* @version $Revision: 1.1 $
*
* @see DataInterface.class.php
* @since 2004/03/31
* @author Alexander Manhart <alexander.manhart@freenet.de>
* @link http://www.misterelsa.de
*/

if(!defined('MYSQL_LAYER'))
{
    #### Prevent multiple loading
    define('MYSQL_LAYER', 'mssql');

    $dbaccessfile = @constant('DBACCESSFILE');
    if (file_exists($dbaccessfile)) {
        require_once ($dbaccessfile);
    }

    define('SQL_READ', 'READ');
    define('SQL_WRITE', 'WRITE');

    /**
     * MSSQL_Interface
     *
     * MySQL Datenbank Layer (Schnittstelle zum MySQL Server)
     *
     * @package pool
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: MSSQL_Interface.class.php,v 1.1 2005/06/03 07:03:48 manhart Exp $
     * @access public
     **/
    class MSSQL_Interface extends DataInterface
    {
        //@var array Array of MySQL Links der Default Datenbank (wird mit dem Constructor bestimmt), Aufbau $var[$mode]["default"]
        //@access private
        var $db_connect_id = array(SQL_READ => array(), SQL_WRITE => array());

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

        //@var array Enthaelt ein Array bestehend aus zwei Hosts f�r Lese- und Schreibvorgaenge
        //@access private
        var $host = array();

        //@var string Enthaelt den Variablennamen des Authentication-Arrays; Der Variablenname wird vor dem Connect aufgeloest; Das Database Objekt soll keine USER und PASSWOERTER intern speichern. Vorsicht wegem ERRORHANDLER!
        //@access private
        var $auth = "";

        //@var array Array of Mysql Links; Aufbau $var[$mode][$database] = resource
        //@access private
        var $connections = array(SQL_READ => array(), SQL_WRITE => array());

        //@var boolean Erzwingt Lesevorgaenge ueber den Host f�r Schreibvorgaenge (Wird gebraucht, wenn geschrieben wird, anschlie�end wieder gelesen. Die Replikation hinkt etwas nach.)
        //@access private
        var $force_backend_read = false;

        //@var string Standard Datenbank
        //@access private
        var $default_database = '';


        /**
        * MSSQL_Interface::MSSQL_Interface()
        *
        * Constructor
        *
        * @access public
        */
        function __construct()
        {
        }

        /**
         * MSSQL_Interface::setOptions()
         *
         * Sets up the object.
         *
         * Einstellungen:
         *
         * persistency = (boolean) Persistente Verbindung (Default true)
         * host = (array)|(string) Hosts des MySQL Servers (es koennen auch Cluster bedient werden host[0] = read; host[1] = write)
         * database = (string) Standard Datenbank
         * auth = (array) Authentication Array, Default 'mssql_auth' (siehe access.inc.php)
         *
         * @param array $Packet Einstellungen
         * @return boolean Erfolgsstatus
         **/
        function setOptions($Packet)
        {
            $this -> persistency = array_key_exists('persistency', $Packet) ? $Packet['persistency'] : false;
            $this -> force_backend_read = array_key_exists('force_backend_read', $Packet) ? $Packet['force_backend_read'] : false;

            if (array_key_exists('host', $Packet)) {
                $host =  $Packet['host'];
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, 'MSSQL_Interface::setOptions Bad Packet: no key "host"');
                return false;
            }

            if (array_key_exists('database', $Packet)) {
                $this -> default_database = $Packet['database'];
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, 'MSSQL_Interface::setOptions Bad Packet: no key "database"');
                return false;
            }

            if (array_key_exists('auth', $Packet)) {
                $this -> auth = $Packet['auth'];
            }
            else {
                $this -> auth = 'mssql_auth';
            }

            #### MySQL Server aufgeteilt in Lesecluster und Schreibcluster:
            if (is_array($host)) {
                $this -> host = array(
                    SQL_READ => $host[0],
                    SQL_WRITE => $host[1]
                );
            }
            #### Ein MySQL Server fuer Lesen und Schreiben
            elseif (is_string($host)) {
                $this -> host = array(
                    SQL_READ => $host,
                    SQL_WRITE => $host
                );
            }

            return true;
        }

        /**
        * MSSQL_Interface::__get_auth()
        *
        * Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
        *
        * @param $mode constant Beschreibt den Zugriffsmodus Schreib-Lesevorgang
        * @return Array mit Key username und password
        *
        * @access private
        */
        function __get_auth($mode) {
            $name_of_array = $this -> auth;
            global $$name_of_array;
            $auth = $$name_of_array;

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
        * MSSQL_Interface::__get_db_pass()
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
        * Holt die Authentication-Daten und gibt den Usernamen zur�ck
        *
        * @param $database string Datenbank
        * @param $mode constant Lese- oder Schreibmodus
        * @return string Gibt den Usernamen zur�ck
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

            if ($this -> host[SQL_READ] == $this -> host[SQL_WRITE]) {
                $mode = SQL_READ; // same as WRITE
            }

            if ($database == '') {
                $database = $this -> default_database;
            }

            if ($database != '') {
                if (array_key_exists($database, $this -> connections[$mode])) {
                    $conid = $this -> connections[$mode][$database];
                }
                else {
                    $user = $this -> __get_db_user($database, $mode);
                }

                if (!$conid) {
                    $conid = ($this -> persistency) ? mssql_pconnect($this->host[$mode], $user, $this -> __get_db_pass($database, $mode)) :
                                                        mssql_connect($this->host[$mode], $user, $this -> __get_db_pass($database, $mode));

                    if (is_resource($conid)) {
                        $dbselect = @mssql_select_db($database, $conid);
                        if (!$dbselect) {
                            $this -> raiseError(__FILE__, __LINE__, mssql_error());
                            @mssql_close($conid);
                            $conid = $dbselect;
                        }
                        else {
                            $this -> connections[$mode][$database] = $conid;
                        }
                    }
                    else {
                        $this -> raiseError(__FILE__, __LINE__, 'MySQL connect failed, using database \'' . $database . '\'');
                    }
                }
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, 'No database selected (__get_db_conid)!');
            }

            return $conid;
        }

        /**
        * MSSQL_Interface::connect()
        *
        * Baut eine Verbindung zur Datenbank auf.
        *
        * @param string $database Datenbank
        *
        * @access public
        */
        function connect($database='')
        {
            $this -> __get_db_conid($database, SQL_READ);
            $this -> __get_db_conid($database, SQL_WRITE);
            return ($this -> isConnected($database, SQL_READ) and $this -> isConnected($database, SQL_WRITE));
        }

        /**
        * isConnected()
        *
        * Ueberprueft ob eine MySQL Verbindung besteht
        *
        * @param string $database Datenbank
        * @param constant $mode Lese- oder Schreibmodus
        * @return boolean Gibt TRUE/FALSE zurueck
        *
        * @access public
        */
        function isConnected($database='', $mode=SQL_READ)
        {
            if ($mode == '') {
                $mode = SQL_READ;
            }
            elseif ($this -> host[SQL_READ] == $this -> host[SQL_WRITE]) {
                $mode = SQL_READ; // same as host
            }
            if ($database == '') {
                $database = $this -> default_database;
            }

            return is_resource($this -> connections[$mode][$database]);
        }

        /**
         * MSSQL_Interface::close()
         *
         * Schliesst alle Verbindungs-Kennungen.
         *
         * @access public
         * @return boolean true
         **/
        function close()
        {
            if (is_array($this -> connections[SQL_READ])) {
                foreach ($this -> connections[SQL_READ] as $database => $conid) {
                    // workaround, sonst schlaegt die Schleife mit SQL_WRITE fehl.
                    if ($this -> connections[SQL_READ][$database] == $this -> connections[SQL_WRITE][$database]) {
                        unset($this -> connections[SQL_WRITE][$database]);
                    }
                    if (is_resource($conid)) {
                        @mssql_close($conid);
                    }
                    unset($this -> connections[SQL_READ][$database]);
                }
            }

            if (is_array($this -> connections[SQL_WRITE])) {
                foreach ($this -> connections[SQL_WRITE] as $database => $conid) {
                    if (is_resource($conid)) {
                        @mssql_close($conid);
                    }
                    unset($this -> connections[SQL_WRITE][$database]);
                }
            }
            return true;
        }

        /**
         * MSSQL_Interface::query()
         *
         * Fuehrt ein SQL-Statement aus
         *
         * @access public
         * @param string $query SQL-Statement
         * @param string $database Datenbankname (default '')
         * @return boolean Erfolgsstatus
         **/
        function query($query='', $database='')
        {
            //
            // Remove any pre-existing queries
            //
            $this -> sql = $query;
            unset($this -> query_result);

            if($query != '') {
                $this -> num_queries++;

                if ((strtoupper(trim(substr($query, 0, 7))) == 'SELECT')
                      and ($this -> force_backend_read == false)) {
                    // read
                    $conid = $this -> __get_db_conid($database, SQL_READ);
                    if (!$conid) {
                        return false;
                    }

                    $this -> num_local_queries++;
                    $this -> query_result = @mssql_query($query, $conid);
                    $this -> last_connect_id = $conid;
                }
                else {
                    // write
                    $conid = $this -> __get_db_conid($database, SQL_WRITE);
                    if (!$conid) {
                        return false;
                    }

                    $this -> num_remote_queries++;
                    $this -> query_result = @mssql_query($query, $conid);
                    $this -> last_connect_id = $conid;
                }
            }
            else {
            }

            if($this -> query_result) {
                unset($this -> row[$this -> query_result]);
                unset($this -> rowset[$this -> query_result]);

                return $this -> query_result;
            }
            else {
                return false;
            }
        }

        /**
         * MSSQL_Interface::numrows()
         *
         * Anzahl gefundener Datensaetze (Rows)
         *
         * @access public
         * @param resource $query_id Query Ergebnis-Kennung
         * @return integer Bei Erfolg einen Integer, bei Misserfolg false
         **/
        function numrows($query_id = 0)
        {
            if(!$query_id) {
                if (isset($this -> query_result)) {
                    $query_id = $this -> query_result;
                }
            }

            return ($query_id) ? mssql_num_rows($query_id) : false;
        }

        /**
         * MSSQL_Interface::affectedrows()
         *
         * Anzahl betroffener Datensaetze (Rows) der letzen SQL Abfrage
         *
         * @access public
         * @return integer Bei Erfolg einen Integer Wert, bei Misserfolg false
         **/
        function affectedrows()
        {
            return ($this -> last_connect_id) ? mssql_rows_affected($this -> last_connect_id) : false;
        }

        /**
         * MSSQL_Interface::numfields()
         *
         * Ermittelt die Spaltenanzahl einer SQL Abfrage
         *
         * @access public
         * @param integer $query_id Query Ergebnis-Kennung
         * @return integer Bei Erfolg einen Integer Wert, bei Misserfolg false
         **/
        function numfields($query_id = 0)
        {
            if( !$query_id ) {
                if (isset($this -> query_result)) {
                    $query_id = $this -> query_result;
                }
            }

            return ($query_id) ? mssql_num_fields($query_id) : false;
        }

        /**
         * MSSQL_Interface::fieldname()
         *
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

            return ($query_id) ? mssql_field_name($query_id, $offset) : false;
        }

        /**
         * MSSQL_Interface::fieldtype()
         *
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

            return ($query_id) ? mssql_field_type($query_id, $offset) : false;
        }

        /**
         * MSSQL_Interface::fetchrow()
         *
         * Liefert einen Datensatz als assoziatives Array und indiziertes Array
         *
         * @access public
         * @param integer $query_id Query Ergebnis-Kennung
         * @return array Datensatz in einem assoziativen Array
         **/
        function fetchrow($query_id = 0)
        {
            if( !$query_id ) {
                if (isset($this->query_result)) {
                    $query_id = $this->query_result;
                }
            }

            if( $query_id ) {
                $this->row[$query_id] = mssql_fetch_array($query_id, MSSQL_ASSOC);
                return $this->row[$query_id];
            }
            else {
                return false;
            }
        }

        /**
         * MSSQL_Interface::fetchrowset()
         *
         * Liefert einen Datensatz als assoziatives Array und numerisches Array
         *
         * @access public
         * @param integer $query_id
         * @return array Bei Erfolg ein Array mit allen Datensaetzen ($array[index]['feldname'])
         **/
        function fetchrowset($query_id = 0)
        {
            if( !$query_id ) {
                if (isset($this -> query_result)) {
                    $query_id = $this -> query_result;
                }
            }

            if( $query_id ) {
                unset($this -> rowset[$query_id]);
                unset($this -> row[$query_id]);

                $result = array();
                while($this -> rowset[$query_id] = mssql_fetch_array($query_id, MYSQL_ASSOC)) {
                    array_push($result, $this -> rowset[$query_id]);
                }
                return $result;
            }
            else {
                return false;
            }
        }

        /**
         * MSSQL_Interface::fetchfield()
         *
         * Liefert ein Objekt mit Feldinformationen aus einem Anfrageergebnis
         *
         * @param string $field Feldname
         * @param integer $rownum Feld-Offset
         * @param integer $query_id Query Ergebnis-Kennung
         * @return string Wert eines Feldes
         **/
        function fetchfield($field, $rownum = -1, $query_id = 0)
        {
            if( !$query_id ) {
                if (isset($this->query_result)) {
                    $query_id = $this->query_result;
                }
            }

            if( $query_id ) {
                if( $rownum > -1 ) {
                    $result = mssql_result($query_id, $rownum, $field);
                }
                else {
                    if( empty($this->row[$query_id]) && empty($this->rowset[$query_id]) ) {
                        if( $this->sql_fetchrow() ) {
                            $result = $this->row[$query_id][$field];
                        }
                    }
                    else {
                        if( $this->rowset[$query_id] ) {
                            $result = $this->rowset[$query_id][$field];
                        }
                        else if( $this->row[$query_id] ) {
                            $result = $this->row[$query_id][$field];
                        }
                    }
                }

                return $result;
            }
            else {
                return false;
            }
        }

        /**
         * MSSQL_Interface::rowseek()
         *
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

            return ( $query_id ) ? mssql_data_seek($query_id, $rownum) : false;
        }

        /**
         * MSSQL_Interface::nextid()
         *
         * Liefert die ID einer vorherigen INSERT-Operation.
         *
         * Hinweis:
         * mssql_insert_id() konvertiert den Typ der Rueckgabe der nativen MySQL C API Funktion mssql_insert_id() in den Typ long (als int in PHP bezeichnet). Falls Ihre AUTO_INCREMENT Spalte vom Typ BIGINT ist, ist der Wert den mssql_insert_id() liefert, nicht korrekt. Verwenden Sie in diesem Fall stattdessen die MySQL interne SQL Funktion LAST_INSERT_ID() in einer SQL-Abfrage
         *
         * @access public
         * @return integer Bei Erfolg die letzte ID einer INSERT-Operation
         **/
        function nextid()
        {
            return ($this -> last_connect_id) ? mssql_insert_id($this -> last_connect_id) : false;
        }

        /**
         * MSSQL_Interface::listfields()
         *
         * Gibt eine Liste aller Felder eine Datenbank-Tabelle zurueck
         *
         * Ergebnis:
         * $array['name'][index]
         * $array['type'][index]
         * $array['len'][index]
         * $array['flags'][index]
         *
         * @access public
         * @param $table
         * @param $database
         * @return array Liste mit Feldern ($array['name'][index], etc.)
         **/
        function listfields($table, $database)
        {
            $arr = array(
                'name' => array(),
                'type' => array(),
                'len' => array(),
                'flags' => array()
            );

            $fields = mssql_list_fields($database, $table, $this -> __get_db_conid($database, SQL_READ));
            if (is_resource($fields)) {
                $columns = mssql_num_fields($fields);

                for ($i=0; $i < $columns; $i++) {
                    array_push($arr['name'], mssql_field_name($fields, $i));
                    array_push($arr['type'], mssql_field_type($fields, $i));
                    array_push($arr['len'], mssql_field_len($fields, $i));
                    array_push($arr['flags'], mssql_field_flags($fields, $i));
                }

                mssql_free_result($fields);
            }

            return $arr;
        }

        /**
         * MSSQL_Interface::freeresult()
         *
         * Gibt belegten Speicher wieder frei
         *
         * Die Funktion muss nur dann aufgerufen werden, wenn Sie sich bei Anfragen, die grosse Ergebnismengen liefern, Sorgen
         * ueber den Speicherverbrauch zur Laufzeit des PHP-Skripts machen. Nach Ablauf des PHP-Skripts wird der Speicher ohnehin
         * freigegeben.
         *
         * @access public
         * @param integer $query_id Query Ergebnis-Kennung
         * @return boolean Bei Erfolg true, bei Misserfolg false
         **/
        function freeresult($query_id = 0)
        {
            if( !$query_id ) {
                if (isset($this->query_result)) {
                    $query_id = $this->query_result;
                }
            }

            return ( $query_id ) ? mssql_free_result($query_id) : false;
        }

        /**
         * MSSQL_Interface::getError()
         *
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
            $result['message'] = mssql_error($this -> last_connect_id);
            $result['code'] = mssql_errno($this -> last_connect_id);

            return $result;
        }

        /**
         * MSSQL_Interface::getErrormsg()
         *
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
         * MSSQL_Interface::enable_force_backend()
         *
         * Mit diesem Schalter werden alle Lesevorgaenge auf die Backend Datenbank umgeleitet.
         *
         * @access public
         **/
        function enable_force_backend()
        {
            $this->force_backend_read = true;
        }

        /**
         * MSSQL_Interface::disable_force_backend()
         *
         * Deaktiviert Lesevorgaenge auf der Backend Datenbank.
         *
         * @access public
         **/
        function disable_force_backend()
        {
            $this->force_backend_read = false;
        }

        /**
         * MSSQL_Interface::escapestring()
         *
         * Maskiert einen String zur Benutzung in mssql_query
         *
         * @param string $string Text
         * @return string Maskierter Text
         **/
        function escapestring($string)
        {
            return mssql_escape_string($string);
        }
    }
}