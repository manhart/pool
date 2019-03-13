<?php
	/**
	* # PHP Object Oriented Library (POOL) #
	*
	* Class C16_Interface ist ein Datenbank-Layer fuer CONZEPT16.
	* Diese Klasse implementiert die Schnittstelle zu C16. Ueber
	* sie ist der Aufbau einer Verbindung moeglich. Sie behandelt
	* alle C16 spezifischen PHP API Befehle (z.B. c16_connect).
	*
	* Dabei kapselt sie nicht nur einfach die API Befehle, sondern
	* enth�lt eine Verbindungs-Kennung-Verwaltung zur Resourcen-Sharing.
	* Sie liefert eine Statistik ueber Anzahl der ausgefuehrten
	* Queries in einem Script, hilft beim Debug durch den Log des...
	*
	* Verbindungen werden nur einmalig geoffnet und am Ende
	* der Script Ausfuehrung ueber C16_Interface::close
	* geschlossen.
	*
	* $Log: C16_Interface.class.php,v $
	* Revision 1.4  2007/05/25 06:27:27  manhart
	* Pfad
	*
	* Revision 1.3  2007/05/07 11:06:39  manhart
	* C16 SINNIC
	*
	* Revision 1.1  2006/05/09 08:31:00  manhart
	* initial import
	*
	*
	* @version $Id: C16_Interface.class.php,v 1.4 2007/05/25 06:27:27 manhart Exp $
	* @version $Revision: 1.4 $
	*
	* @see DataInterface.class.php
	* @since 2006/05/05
	* @author Alexander Manhart <alexander.manhart@freenet.de>
	* @link http://www.misterelsa.de
	*/

	if(!defined('C16_LAYER'))
	{
		#### Prevent multiple loading
		define('C16_LAYER', 'c16');

		define('C16_A_LOCAL_IDS', '/tmp');

		$dbaccessfile = @constant('DBACCESSFILE');
		if (file_exists($dbaccessfile)) {
	    	require_once ($dbaccessfile);
		}

		/**
		 * C16 Datenbank Layer (Schnittstelle zu CONZEPT16)
		 *
		 * @package pool
		 * @author Alexander Manhart <alexander.manhart@freenet.de>
		 * @version $Id: C16_Interface.class.php,v 1.4 2007/05/25 06:27:27 manhart Exp $
		 * @access public
		 **/
		class C16_Interface extends DataInterface
		{
			//@var resource Letzter benutzer MySQL Link
			//@access private
			var $last_connect_id;

			//@var array Saves fetched Mysql results
			//@access private
			var $row = array();

			//@var array Saves fetched Mysql rowsets
			//@access private
			var $rowset = array();

			/**
			 * Enth�lt den Hostnamen
			 *
			 * @var string Servername
			 * @access private
			 */
			var $host = '';

			/**
			 * Enth�lt den Variablennamen des Authentication-Arrays; Der Variablenname wird vor dem Connect aufgeloest; Das Database Objekt soll keine USER und PASSWOERTER intern speichern. Vorsicht wegem ERRORHANDLER!
			 *
			 * @var array
			 * @access private
			 */
			var $auth = "";

			/**
			 * Sammlung von Verbindungskennungen
			 *
			 * @var array
			 * @access private
			 */
			var $connections = array();

			/**
			 * Puffer fuer die Dateiabfrage
			 *
			 * @var array
			 */
			var $files = array();

			/**
			 * Default database
			 *
			 * @var string Databasename
			 * @access private
			 */
			var $default_database = '';

			var $inSelection=false;

			var $sellink=null;

			/**
			* Constructor
		    *
	    	* @access public
		    */
			function __construct()
			{
				$this -> setInterfaceType(DATAINTERFACE_C16);
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
				// C16 Host
				if (array_key_exists('host', $Packet)) {
					$host =  $Packet['host'];
				}
				else {
					$Exception = new Xception('C16_Interface::setOptions Bad Packet: no key "host". Please define option host in packet!', 0,
						magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
					return false;
				}

				// C16 Server Passwort im auth Packet

				// C16 Datenbank
				if (array_key_exists('database', $Packet)) {
					$this -> default_database = $Packet['database'];
				}
				else {
					$Exception = new Xception('C16_Interface::setOptions Bad Packet: no key "database". Please define option database in packet!', 0,
						magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
					return false;
				}

				// C16 Benutzer - Authentifizierung
				if (array_key_exists('auth', $Packet)) {
					$this -> auth = $Packet['auth'];
				}
				else {
					$this -> auth = 'c16_auth';
				}

				$this -> host = $host;

				return true;
			}

			/**
			* Liest die Authentication-Daten aus Array und gibt sie als Array zurueck
			*
			* @param $mode constant Beschreibt den Zugriffsmodus Schreib-Lesevorgang
	    	* @return Array mit Key username und password
			*
    		* @access private
	    	*/
			function __get_auth() {
				$name_of_array = $this -> auth;
				global $$name_of_array;
				$auth = $$name_of_array;

				$authentication = array();
				if (is_array($auth)) {
					if (array_key_exists($this->host, $auth)) {
						$authentication = $auth[$this -> host];
					}
				}
				else {
					$Exception = new Xception('C16 access denied! No authentication data available ' .
						(!is_array($auth)? 'Please define  option \'auth\' in packet! @see C16_Interface::setOptions' : '') .
						'(Server Name: '.(($this -> host) ? $this -> host : 'no host passed!').')', 0,
						magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
				}
				return $authentication;
			}

			/**
			* Holt die Authentication-Daten und gibt das Passwort zurueck
			*
			* @param string $database Datenbank
		    * @return string Gibt das Passwort zurueck
			*
	    	* @access private
		    */
        	function __get_db_pass($database)
			{
				$auth = $this -> __get_auth();

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
			* Holt die Authentication-Daten und gibt den Usernamen zur�ck
			*
			* @param $database string Datenbank
		    * @return string Gibt den Usernamen zur�ck
			*
	    	* @access private
		    */
        	function __get_db_user($database)
			{
				$auth = $this->__get_auth();

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
			* Holt die Authentication-Daten und gibt das Server Passwort zur�ck
			*
			* @param $database string Datenbank
		    * @return string Gibt das Server Passwort zur�ck
			*
	    	* @access private
		    */
	        function __get_server_pass($database)
	        {
				$auth = $this -> __get_auth();

				$server_password = '';
				if (array_key_exists('all', $auth)) {
				    $database = 'all'; // Special
				}
				if (array_key_exists($database, $auth)) {
					$server_password = $auth[$database]['server_password'];
				}
            	return $server_password;
	        }

			/**
			* Stellt eine MySQL Verbindung her. Falls die Verbindungs-Kennung bereits existiert, wird die vorhandene Verbindung verwendet (Resourcen-Sharing)
			*
			* @param $database string Datenbank
		    * @return resource Gibt Resource der C16 Verbindung zurueck
			*
	    	* @access private
		    */
        	function __get_db_conid($database='')
			{
				$conid = false;

				if ($database == '') {
					$database = $this -> default_database;
				}

				if ($database != '') {
		            if (array_key_exists($database, $this -> connections)) {
    		        	$conid = $this -> connections[$database];
        			}
					else {
						$user = $this->__get_db_user($database);
					}

					if (!$conid) {
						$conid = c16_connect($this->host, $this->__get_server_pass($database), $database, $user,
							$this->__get_db_pass($database), C16_A_LOCAL_IDS);

						if(c16_error($conid) != C16_OK) {
							$Exception = new Xception('C16 (DatabaseName: '.$database.' User: '.$user.'): '.c16_errortext($conid), 0,
								magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__), POOL_ERROR_DIE);
							$this->throwException($Exception);
						}
						else {
							$this -> connections[$database] = $conid;
						}
					}
				}
				else {
					$Exception = new Xception('C16 (database ' . $database . '): ' .
						'No database selected (__get_db_conid in {FILE} at line: {LINE})!', 0,
						magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
				}

				return $conid;
	        }

			/**
			* Baut eine Verbindung zur Datenbank auf.
			*
			* @param string $database Datenbank
			*
    		* @access public
	    	*/
			function connect($database='')
			{
				$this->__get_db_conid($database);
				return ($this->isConnected($database));
			}

			/**
			* Ueberprueft ob eine C16 Verbindung besteht
			*
			* @param string $database Datenbank
		    * @return boolean Gibt TRUE/FALSE zurueck
			*
	    	* @access public
		    */
			function isConnected($database='')
			{
				if ($database == '') {
				    $database = $this->default_database;
				}

				return (c16_error($this->connections[$database]) == C16_OK);
				#return is_resource($this -> connections[$database]);
			}

			/**
			 * Liefert die Verbindungskennung der C16 - Datenbankverbindung
			 *
			 * @return resource
			 */
			function getConnectionId($database='')
			{
				if($database=='') $database = $this -> default_database;
				return $this -> connections[$database];
			}

			/**
			 * Schliesst alle Verbindungs-Kennungen.
			 *
			 * @access public
			 * @return boolean true
			 **/
			function close()
			{
				if (is_array($this -> connections)) {
					foreach ($this -> connections as $database => $conid) {
						if (is_resource($conid)) {
							c16_close($conid);
						}
						unset($this->connections[$database]);
					}
				}

				return true;
			}

			function fldset($keyval)
			{
				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid();

				#unset($fldval['KND.lEcm']);
				#echo pray($fldval);
				#$fields = array('BSP.aName' => 'Bin');
				if(is_array($keyval)) {
					return c16_fldset($c16_link, $keyval);
				}
				return false;
			}

			function fldget($fldval)
			{
				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid();

				//echo 'c16_link: ' . $c16_link . ' keyval: ' . $keyval;
				//if(is_array($keyval)) echo pray($keyval);
				#$fields = array('BSP.aName' => 'Bin');

				$result = @c16_fldget($c16_link, $fldval);
				/*echo 'result: ' . $result;*/
				return $fldval;
			}

			function selopen($filenr, $selname, $aFlags=0)
			{
				$bResult = false;

				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				$this->sellink = c16_selopen($c16_link);

				if($this->sellink) {

#					echo "c16_selread($this->sellink, $filenr, $selname, _SelLock)";
					$bResult = c16_selread($this->sellink, $filenr, _SelLock, $selname);
					if($bResult == _rOk) $bResult = c16_selrun($this->sellink, $aFlags);
					if($bResult == _rOk) {
						$this->inSelection=true;
					}
					//return $bResult;
				}
				else {
					$Exception = new Xception('C16 fatal error! Es konnte kein Selektionspuffer f�r '.$selname.
						' angelegt werden.', 0, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__), POOL_ERROR_DIE);
					$this->throwException($Exception);
				}

				// TODO sinnvoll, den Status von c16 zur�ck zu geben?
				// Nein, muss abge�ndert werden auf true | false und fehler via getError abfragen
				return $bResult;
			}

			function isInSelection()
			{
				return ($this->inSelection);
			}

			function selclose()
			{
				if($this->sellink) {
					c16_selclose($this->sellink);
				}
				$this->inSelection=false;
			}

			/**
			 * extended ASCII Zeichen Transformator
			 *
			 * @param string $value
			 * @return string
			 */
			function escapeValue($value)
			{
				// � = 138
				// � = 140
				// � = 156
				// � = 172
				// ? = 176
				// � = 186
				// � = 228
				// � = 235
				return $value;

				/**
				 *
				 * Hallo Frau Meinzinger,
				 * die Benutzung der Umlaute findet nun im ANSI Standard statt. In der vorher eingesetzten Version von Conzept16 wurden diese mit falschen Ascii Zeichen �bermittelt - das man nun erkennt wenn es jetzt richtig umgesetzt wird.
				 * Ich habe einen Link gefunden, der Ihnen die n�tigen Codes auflistet: http://www.asphelper.de/referenz/ASCIIANSI.asp
				 */
				// Alex Anmerkung: ? wird von Zend als Ascii 63 geschrieben. ISO-8859-1 enth�lt das Zeichen nicht. Unter Windows funktioniert jedoch 128 CP1252, daher chr(128)
				//return str_replace(array('�', '�', '�', '�', '�', '�', '�', chr(128)), array(chr(140), chr(156), chr(228), chr(138), chr(172), chr(186), chr(235), chr(176)), $value);
			}

			/**
			 * Konvertiert Umlaute in Spaltennamen
			 *
			 * @param string $column
			 * @return string
			 */
			function escapeColumn($column)
			{
				return str_replace(array('�', '�', '�'), array(chr(238), chr(163), chr(216)), $column);
			}

			/**
			 * Konvertiert Umlaute in Spaltennamen eines Arrays
			 *
			 * @param array $fields
			 * @param boolean $clear true=leert Wert
			 * @return array
			 */
			function escapeColumns($fields, $clear=false)
			{
				$new_fields = array();
				if(is_array($fields))
					while(list($keyname, $value)=each($fields)) {
						if($clear) $value='';
						$new_fields[$this->escapeColumn($keyname)] = $value;
					}
				return $new_fields;
			}

			/**
			 * Konvertiert Spaltennamen in ANSI (PHP Zeichensatz)
			 *
			 * @param string $column
			 * @return string
			 */
			function unescapeColumn($column)
			{
				return str_replace(array(chr(238), chr(163), chr(216)), array('�','�', '�'), $column);
			}

			/**
			 * Konvertiert Spaltennamen eines Arrays in ANSI (PHP Zeichensatz)
			 *
			 * @param array $fields
			 * @return array
			 */
			function unescapeColumns($fields)
			{
				$new_fields = array();
				if(is_array($fields))
					while(list($keyname, $value)=each($fields)) {
						$new_fields[$this->unescapeColumn($keyname)] = $value;
					}
				return $new_fields;
			}

			function read($filenr, $keynr=null, $flags=0x00, $aAddInfo=null)
			{
				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid();

				#echo 'filenr: ' . $filenr. ' keynr: ' . $keynr. ' flags: ' . $flags . ' aAddInfo: ' . $aAddInfo . ' <br>';

				if($this->inSelection) $keynr = $this->sellink;
				$result = c16_recread($c16_link, $filenr, $keynr, $flags, $aAddInfo);

				$this->__lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));

/*				if($result != _rOk) {
					switch($result) {
						case _rLocked:
							$errorMsg = 'Datensatz ist vorhanden und von einem anderen Benutzer gesperrt. Der Satz wurde geladen, sofern die Option _RecNoLoad nicht angegeben wurde.';
							break;

						case _rMultiKey:
							$errorMsg = 'Der Schl�ssel ist nicht eindeutig. In der Datei sind mehrere S�tze mit dem gew�nschten Schl�sselwert vorhanden, der erste Satz wurde geladen';
							break;

						case _rNoKey:
							$errorMsg = 'In der Datei ist kein Satz mit dem gew�nschten Schl�sselwert vorhanden. Es wurde der Satz mit dem n�chst gr��eren Schl�sselwert geladen.';
							break;

						case _rLastRec:
							$errorMsg = 'In der Datei ist weder ein Satz mit dem gew�nschten Schl�sselwert noch ein Satz mit einem gr��eren Schl�sselwert vorhanden. Es wurde der Satz mit dem gr��ten Schl�sselwert geladen.';
							break;

						case _rNoRec:
							$errorMsg = 'Es wurde kein Satz geladen, da entweder die Datei leer ist, oder kein vorhergehender bzw. nachfolgender Satz existiert';
							break;

						$Exception = &new Exception($errorMsg, $result, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
						$this->throwException($Exception);
						return false;
					}
				}*/

				return $result;


				if($result >= _rOk and $result < _rNoRec) {
					$result = $this->fldget($fields);
					return $result;
				}
				return false;
			}

			function replace($fldval, $filenr)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				// Aenderung des Datensatzes
				c16_fldset($c16_link, $fldval);

				$result = c16_recreplace($c16_link, $filenr, _RecUnlock);

				if($result != _rOk) {
					switch($result) {
						case _rExists:
							$errorMsg = 'Der Datensatz konnte nicht zur�ckgespeichert werden, da ein Satz mit einem identischen eindeutigen Schl�sselwert bereits existiert.';
							break;

						case _rNoLock:
							$errorMsg = 'Der Datensatz konnte nicht zur�ckgespeichert werden, da er nicht gesperrt ist.';
							break;

						case _rDeadLock:
							$errorMsg = 'Der Datensatz konnte aufgrund einer Verklemmung nicht ersetzt werden.';
							break;

					}
					$Exception = new Xception($errorMsg, $result, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
				}

				return true;
			}

			function update($fldval, $keyval, $filenr, $keynr)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				// Setze Schluesselfeld
				#$key_fld = array('KND.aFirmenBez' => 'vectorsoft AG');
				c16_fldset($c16_link, $keyval);

				// Lese Datensatz ueber Schluessel und sperre ihn
				$result = c16_recread($c16_link, $filenr, $keynr, _RecLock);

				switch($result != _rOk) {
					case _rLocked:
						$errorMsg = 'Datensatz ist vorhanden und von einem anderen Benutzer gesperrt. Der Satz wurde geladen, sofern die Option _RecNoLoad nicht angegeben wurde.';
						break;

					case _rMultiKey:
						$errorMsg = 'Der Schl�ssel ist nicht eindeutig. In der Datei sind mehrere S�tze mit dem gew�nschten Schl�sselwert vorhanden, der erste Satz wurde geladen';
						break;

					case _rNoKey:
						$errorMsg = 'In der Datei ist kein Satz mit dem gew�nschten Schl�sselwert vorhanden. Es wurde der Satz mit dem n�chst gr��eren Schl�sselwert geladen.';
						break;

					case _rLastRec:
						$errorMsg = 'In der Datei ist weder ein Satz mit dem gew�nschten Schl�sselwert noch ein Satz mit einem gr��eren Schl�sselwert vorhanden. Es wurde der Satz mit dem gr��ten Schl�sselwert geladen.';
						break;

					case _rNoRec:
						$errorMsg = 'Es wurde kein Satz geladen, da entweder die Datei leer ist, oder kein vorhergehender bzw. nachfolgender Satz existiert';
						break;

					$Exception = new Xception($errorMsg, $result, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);

					return false;
				}


				if ($result == _rOk) {
					$upd_result = $this -> replace($fldval, $filenr);
				}

				return true;
			}

			function insert($fldval, $filenr, $flags=0x00)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				// Einfuegen eines neuen Datensatzes
				$this -> fldset($fldval);

				$result = c16_recinsert($c16_link, $filenr, $flags);


				if(!$result == _rOk) {
					switch($result) {
						case _rExists:
							$errorMsg = 'Der Datensatz konnte nicht eingef�gt oder zur�ckgespeichert werden, da ein Satz mit einem identischen eindeutigen Schl�sselwert bereits existiert.';
							break;

						case _rDeadLock:
							$errorMsg = 'Der Datensatz konnte aufgrund einer Verklemmung nicht eingef�gt werden.';
							break;

						default:
							$errorMsg = 'Unbekannter Fehler aufgetreten. Fehler-Nr.: ' . $result;
							break;
					}

					$Exception = new Xception($errorMsg, $result, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this->throwException($Exception);

					return false;
				}

				return true;
			}

			function delete($keyfld, $filenr, $flags=0)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				// setzen des Schluesselwertes
				c16_fldset($c16_link, $keyfld);

				// Loeschen des Datensatzes
				$result = c16_recdelete($c16_link, $filenr, $flags);

				if(!$result == _rOk) {
					switch($result) {
						case _rLocked:
							$errorMsg = 'Der Datensatz konnte nicht gel�scht werden, da er von einem anderen Benutzer gesperrt ist.';
							break;

						case _rNoKey:
							$errorMsg = 'In der Datei ist kein Satz mit dem gew�nschten Schl�sselwert vorhanden. Es wurde der Satz mit dem n�chst gr��eren Schl�sselwert geladen.';
							break;

						case _rLastRec:
							$errorMsg = 'In der Datei ist weder ein Satz mit dem gew�nschten Schl�sselwert noch ein Satz mit einem gr��eren Schl�sselwert vorhanden. Es wurde der Satz mit dem gr��ten Schl�sselwert geladen.';
							break;

						case _rNoRec:
							$errorMsg = 'Es wurde kein Satz geladen, da entweder die Datei leer ist, oder kein vorhergehender bzw. nachfolgender Satz existiert.';
							break;

						case _rDeadLock:
							$errorMsg = 'Der Datensatz konnte aufgrund einer Verklemmung nicht gel�scht werden.';
							break;

						default:
							$errorMsg = 'Unbekannter Fehler aufgetreten. Fehler-Nr.: ' . $result;
							break;
					}

					$Exception = new Xception($errorMsg, $result, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this->throwException($Exception);

					return false;
				}

				return true;
			}

			/**
			 * Anzahl  Datensaetze (Rows) in der Datei
			 *
			 * @access public
			 * @param resource $query_id Query Ergebnis-Kennung
			 * @return integer Bei Erfolg einen Integer, bei Misserfolg false
			 **/
			function numrows($filenr)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				$aKeyNr=0;
				if($this->inSelection) {
					$aKeyNr = $this -> sellink;
				}
				$result = c16_recinfo($c16_link, $filenr, _RecCount, $aKeyNr);
				// $this -> __lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
				return $result;
			}

			function getPos($filenr, $aKeyNr=0)
			{
				// aktive Verbindungskennung
				$c16_link = $this -> __get_db_conid();

				if($this -> inSelection) {
					$aKeyNr = $this -> sellink;
				}
				$result = c16_recinfo($c16_link, $filenr, _RecGetPos, $aKeyNr);
				// $this -> __lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
				return $result;
			}

			/**
			 * Liefert einen Datensatz als assoziatives Array und indiziertes Array
			 *
			 * @access public
			 * @param integer $query_id Query Ergebnis-Kennung
			 * @return array Datensatz in einem assoziativen Array
			 **/
			function fetchrow($query_id = 0)
			{

			}

			/**
			 * Liefert einen Datensatz als assoziatives Array und numerisches Array
			 *
			 * @access public
			 * @param integer $query_id
			 * @return array Bei Erfolg ein Array mit allen Datensaetzen ($array[index]['feldname'])
			 **/
			function fetchrowset($query_id = 0)
			{
			}

			/**
			 * Liefert ein Objekt mit Feldinformationen aus einem Anfrageergebnis
			 *
			 * @param string $field Feldname
			 * @param integer $rownum Feld-Offset
			 * @param integer $query_id Query Ergebnis-Kennung
			 * @return string Wert eines Feldes
			 **/
			function fetchfield($field, $rownum = -1, $query_id = 0)
			{
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
			}

			/**
			 * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zur�ck.
			 *
			 * @return int Anzahl Zeilen
			 */
			function foundRows()
			{
			}

			/**
			 * Gibt eine Liste aller Felder einer Tabelle zurueck
			 *
			 * Ergebnis mehrdimensionales assoziatives Array:
			 * Schl�ssel Wert
			 * _FileNumber Dateinummer
			 * _SbrNumber Teildatensatznummer
			 * _FldNumber Feldnummer
			 * _FldName Feldname
			 * _FldType Typ des Feldes
			 * _FldMaxLen Maximale L�nge
			 * _FldInputRight Eingabeberechtigung
			 * _FldOutputRight Ausgabeberechtigung
			 *
			 * @access public
			 * @param int $filenr Datei-Nummer
			 * @param array $fldnames Parameter f�llt sich mit den Feldnamen (man erh�lt indiziertes Array mit Feldnamen)
			 * @param string $database Datenbankname (falls nicht angegeben, wird Standard Database verwendet)
			 * @return array mehrdimensionales assoziatives Array mit allen Feldinformationen zur Datei
			 **/
			function listfields($filenr, &$fldnames, $database='')
			{
				$flds = array();
				$fldnames = array();

				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid($database);

				if($c16_link) {
					### Ermittelt Felder einer Tabelle/Datei
					$sbrs = c16_sbrinfo($c16_link, $filenr);

					$this->__lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));

					foreach ($sbrs as $sbrkey => $sbrname) {
						$fld = c16_fldinfo($c16_link, $filenr, $sbrkey);

						//$fldnames = array_merge($fldnames, $fld);
						//echo pray($fld);
						foreach ($fld as $fldkey => $fldname) {
							$fldinfo = c16_fldinfo($c16_link, $filenr, $sbrkey, $fldkey);

							$flds[$fldname] = $fldinfo;
							array_push($fldnames, $fldname);
						}
					}
				}

				return $flds;
			}

			/**
			 * Gibt eine Liste aller Schl�ssel einer Tabelle zur�ck
			 *
			 * Ergebnis mehrdimensionales assoziatives Array:
			 * Schl�ssel Wert
			 * _FileNumber Nummer der Datei
			 * _SbrNumber Nummer des Teildatensatzes
			 * _FldNumber Nummer des Schl�sselfeldes
			 * _FldName Name des Schl�sselfeldes
			 * _FldType Feldtyp
			 * _KeyFldAttributes Attribute des Schl�sselfeldes
			 * _KeyFldMaxLen Die definierte Maximall�nge des Schl�sselfeldes
			 *
			 * @access public
			 * @param int $filenr Datei-Nummer
			 * @param array $keynames referenzierter Parameter f�llt sich mit den Schl�sselnamen (man erh�lt indiziertes Array mit Schl�sselnamen)
			 * @param string $database Datenbankname (falls nicht angegeben, wird Standard Database verwendet)
			 * @return array mehrdimensionales assoziatives Array mit allen Schl�sselinformationen zur Datei
			 */
			function listkeys($filenr, &$keynames, $database='')
			{
				$keys = array();
				$keynames = array();

				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid($database);

				$kys = c16_keyinfo($c16_link, $filenr);

				$this->__lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));

				foreach ($kys as $keynr => $keyname) {
					$keyinfo = c16_keyinfo($c16_link, $filenr, $keynr);
					$keyfld = c16_keyfldinfo($c16_link, $filenr, $keynr);

					$fldinfo = $keyinfo;
					$fldinfo['_FldInfo'] = array();
					foreach ($keyfld as $keyfldnr => $keyfldname) {
						$keyfldinfo = c16_keyfldinfo($c16_link, $filenr, $keynr, $keyfldnr);
						array_push($fldinfo['_FldInfo'], $keyfldinfo);
					}
					$keys[$keyname] = $fldinfo;
					array_push($keynames, $keyname);
				}

				return $keys;
			}

			/**
			 * Gibt eine Liste aller Dateien (Tabellen) einer Datenbank zur�ck
			 *
			 * Ergebnis mehrdimensionales assoziates Array:
			 * Schl�ssel Wert
			 * _FileNumber Nummer der Datei
			 * _SbrNumber Nummer des Teildatensatzes
			 * _FldNumber Nummer des Schl�sselfeldes
			 * _FldName Name des Schl�sselfeldes
			 * _FldType Feldtyp
			 * _KeyFldAttributes Attribute des Schl�sselfeldes
			 * _KeyFldMaxLen Die definierte Maximall�nge des Schl�sselfeldes
			 *
			 * @param array $filenames referenzierter Parameter (Array) f�llt sich mit den Namen der Dateien (Tabellen) - (man erh�lt indiziertes Array mit Dateinamen)
			 * @return array mehrdimensionales assoziates Array mit allen Datei-Informationen zur Datenbank
			 */
			function listfiles(&$filenames, $database='')
			{
				$list = array();
				$filenames = array();

				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid($database);

				$files = $this->c16_fileinfo($c16_link);
				$this->__lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));

				foreach($files as $filenr => $filename) {
					$fileinfo = $this->fileinfo($filenr); // TODO cache
					$list[$filename] = $fileinfo;
					array_push($filenames, $filename);
				}
				return $list;
			}

			/**
			 * Dateiinformationen abrufen (wird gecached)
			 *
			 * @param resource $c16_link
			 * @return array
			 */
			function c16_fileinfo($c16_link)
			{
				if(!isset($this->files[$c16_link])) {
					$this->files[$c16_link] = c16_fileinfo($c16_link);
				}
				return $this->files[$c16_link];
			}

			/**
			 * Ermittelt die Dateinummer einer Datei
			 *
			 * @param string $filename Dateiname
			 * @return int Dateinummer
			 */
			function getFileNr($filename, $database='')
			{
				// aktive Verbindungskennung
				$c16_link = $this->__get_db_conid($database);

				if(!$c16_link) {
					return false;
				}
				else {
					$files = $this->c16_fileinfo($c16_link);
					return array_search($filename, $files);
				}
			}

			/**
			 * Liefert Informationen zur Datei.
			 *
			 * Ergebnis assoziates Array:
			 *
			 * _FileNumber Dateinummer
			 * _FileName Dateiname
			 * _FileMaster Dateinummer der �bergeordneten Datei
			 * _FileSbrCount Anzahl der Teildatens�tze
			 * _FileKeyCount Anzahl der Schl�ssel
			 * _FileLnkCount Anzahl der Verkn�pfungen
			 * _FileUserLevel Benutzerberechtigung
			 * _FileOemMark Markierung f�r das OEM-Kit
			 *
			 * @access public
			 * @param int $filenr Datei-Nummer
			 * @return array assoziates Array mit Iinformationen zur Datei
			 */
			function fileinfo($filenr)
			{
				$c16_link = $this->__get_db_conid();

				if(empty($filenr)) {
					$Exception = new Xception('Paremter Datei-Nummer fehlt in C16_Interface::fileinfo!',
						0, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this->throwException($Exception);
				}

				$fileinfo = c16_fileinfo($c16_link, $filenr);
				$this->__lookupError($c16_link, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
				return $fileinfo;
			}

			/**
			 * Setzt, wechselt die Datenbank
			 *
			 * @access public
			 * @param string $database
			 * @return resource Verbindungskennung
			 */
			function selectdb($database)
			{
				$this->default_database = $database;
				return $this->__get_db_conid();
			}

			function __lookupResult()
			{

			}

			/**
			 * Guckt, ob ein Fehler aufgetreten ist.
			 *
			 * @param resource $c16_link Verbindungskennung
			 * @param array $magicInfo siehe magicInfo - Funktion in Utils
			 */
			function __lookupError($c16_link, $magicInfo=array())
			{
				$error = c16_error($c16_link);
				if($error < C16_OK) {
					$errortext = c16_errortext($c16_link);
					switch($error) {
						case C16ERR_NO_KEY:
							$errortext = 'Schl�ssel fehlend oder falsch (C16 ERR: ' . $errortext . ')!';
							break;

						case C16ERR_NO_FILE:
							$errortext = 'Datei-Nummer fehlend oder falsch (C16 ERR: ' . $errortext . ')!';
							break;

						default:
							break;
					}
					// $this -> error['message'] = $errortext;

					$Exception = new Xception($errortext, $error, $magicInfo);
					$this -> throwException($Exception);
				}
			}

			function getErrormsg()
			{
				$message = $this -> error['message'];
				return $message;
			}

			function getError()
			{
				$result['code'] = $this -> error['code'];
				$result['message'] = $this -> error['message'];

				return $result;
			}
		}
	}
?>