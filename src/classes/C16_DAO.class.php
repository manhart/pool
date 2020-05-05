<?php
	/**
	* # PHP Object Oriented Library (POOL) #
	*
	* Class C16_DAO abgeleitet von der abstrakten Basisklasse DAO.
	* Diese Klasse kapselt die C16 Aktionen!
	*
	* $Log$
	*
	* @version $Id$
	* @version $Revision$
	*
	* @see DAO.class.php
	* @see C16_Interface.class.php
	* @see C16_Resultset.class.php
	*
	* @since 2006-05-10
	* @author Alexander Manhart <alexander.manhart@freenet.de>
	* @link http://www.misterelsa.de
	*/

	if(!defined('CLASS_C16DAO')) {

		#### Prevent multiple loading
		define('CLASS_C16DAO', 1);

		/**
		 * C16_DAO
		 *
		 * Siehe Datei fuer ausfuehrliche Beschreibung!
		 *
		 * @package pool
		 * @author Alexander Manhart <alexander.manhart@freenet.de>
		 * @version $Id$
		 * @access public
		 **/
		class C16_DAO extends DAO
		{
			/**
			 * C16_Interface
			 *
			 * @access private
			 * @var C16_Interface
			 */
			var $db;

			/**
			 * Datenbankname
			 *
			 * @access protected
			 * @var string
			 */
			var $dbname = '';

			//@var string Spalten einer Tabelle, getrennt mit Komma
			//@access protected
			//var $column_list;

			/**
			 * Feld-/Spalteninformationen der Datei/Tabelle
			 *
			 * @var array Mehrdimensonales assoziatives Array
			 */
			var $fields=array();

			/**
			 * Feld-/Spaltennamen der Datei/Tabelle
			 *
			 * @var array Indiziertes Array
			 */
			var $fieldnames=array();

			/**
			 * Schl�sselinformationen der Datei/Tabelle (indiziertes array)
			 *
			 * @var array Mehrdimensionales indiziertes Array
			 */
			var $all_keys = array();

			/**
			 * Eindeutige Schl�ssel (indiziertes array)
			 *
			 * @var array Mehrdimensionales indiziertes Array
			 */
			var $unique_keys = array();

			/**
			 * Schl�sselinformationen der Datei/Tabelle (assoziatives array)
			 *
			 * @var array Mehrdimensonales assoziatives Array
			 */
			var $keys=array();

			/**
			 * Schl�sselnamen der Datei/Tabelle
			 *
			 * @var array Indiziertes Array
			 */
			var $keynames=array();

			/**
			 * Prim�rschl�ssel im Detail
			 *
			 * @var array Mehrdimensionales Array (am besten einmal ausgeben, enth�lt C16 Aufbau)
			 */
			var $pkDetails=array();

			/**
			 * Datei-Name oder Datei-Nummer
			 *
			 * @var mixed
			 */
			var $file;

			/**
			 * Letzte Ergebnis
			 *
			 * @var unknown_type
			 */
			var $last_result=null;

			/**
			 * Alle Ergebnisse werden mit den Feldinhalten abgeglichen auf Uebereinstimmung
			 *
			 * @var boolean
			 */
			var $equalFieldValues = true;

			/**
			 * Konstruktor
			 *
			 * @access public
			 **/
			function __construct()
			{
				parent::__construct();
			}

			/**
			 * Initialisiert Objekteigenschaften: Die Funktion "init" liest automatisch alle Felder und
			 * Primaerschluessel der Tabelle ein.
			 *
			 * Beim Setzen der Spalten/Felder wird das Ereignis
			 * $this -> onSetColumns() aufgerufen
			 *
			 * @access public
			 **/
			function init()
			{
				// $this->db->__get_db_conid();
				$fldnames = array();
				$keynames = array();
				
				$fields = $this->db->listfields($this->file, $fldnames, $this->dbname);

				$this->fields = $fields;
				$this->fieldnames = $fldnames;
				$this->columns = &$fldnames;

				$key_list = $this->db->listkeys($this->file, $keynames, $this->dbname);
				$this->keys = $key_list;
				$this->keynames = $keynames;
				$this->unique_keys = array();
				$this->all_keys = array();

				// Eindeutigen Schl�ssel ermitteln (=PK); wir speichern nur den ersten eindeutigen Key!
				$pkDetails = array();
				if(is_array($key_list)) {
					$z=0;
					foreach($key_list as $keyname => $info) {
						if((int)$info['_KeyIsUnique'] == 1) {
							if($z==0) {
								$pkDetails = $info; // Hauptprim�rschl�ssel
							}
							// alle eindeutigen Schl�ssel
							array_push($this->unique_keys, $info);
							$z++;
						}
						array_push($this->all_keys, $info);
					}
				}

				$this->__setPrimaryKey($pkDetails);
				$this->onSetColumns();
			}

			/**
			 * Setzt den Prim�rschl�ssel samt Details
			 *
			 * @param array $pkDetails Details
			 */
			function __setPrimaryKey($pkDetails)
			{
				$this->pkDetails = $pkDetails;

				$pk=array();
				if(isset($pkDetails['_FldInfo'])) {
					foreach($pkDetails['_FldInfo'] as $field) {
						array_push($pk, $field['_FldName']);
					}
				}
				$this->pk = $pk;
			}

			/**
			 * Setzt einen Primaer Schluessel. Der Primaer Schluessel findet bei den global Funktionen DAO::get(), DAO::update(), DAO::delete(), ... hauptsaechlich Verwendung.
			 * F�r C16 kann jeder beliebige Schl�ssel als Prim�rschl�ssel fungieren. Ob das Sinn macht, muss der Entwickler selbst bestimmen k�nnen!
			 *
			 * @access public
			 * @param string $pk [, mixed ... ]
			 **/
			function setPrimaryKey($pk)
			{
				$pk = Array($pk);
				$num_args = func_num_args();
				if ($num_args > 1) {
				    for ($a=1; $a<$num_args; $a++) {
						$arg = func_get_arg($a);
						array_push($pk, $arg);
					}
				}

				// all keys can act as primary key
				$numFields = count($pk);
				for($i=0; $i<$numFields; $i++) {
					$buf['_FldInfo'] = array();
					array_push($buf, array('_FldName' => $pk[$i]));
				}

				foreach ($this->all_keys as $info) {
					if(count($info['_FldInfo']) == $numFields) {
						$equal = 0;
						for($i=0; $i<$numFields; $i++) {
							if($pk[$i]==$info['_FldInfo'][$i]['_FldName']) {
								$equal++;
							}
						}
						if($equal == $numFields) {
							$this->__setPrimaryKey($info);
							return true;
						}
					}
				}

				return false;
			}

			/**
			 * Liefert alle Felder der Tabelle.
			 *
			 * @access public
			 * @return array Felder der Tabelle
			 **/
			function getFieldlist()
			{
				if (count($this -> columns) == 0) {
					$this -> init();
				}
				return $this -> columns;
			}

			function getFields()
			{
				if (count($this->fields) == 0) {
					$this -> init();
				}
				return array_flip($this->fields);
			}

			function getFieldType($fieldname)
			{
				if(!$this->fields) $this->init();

				foreach ($this->fields as $field) {
					if($field['_FldName'] == $fieldname) {
						return $field['_FldType'];
					}
				}
				return false;
			}

			/**
			 * Das Ereignis onSetColumns tritt nachdem aufrufen von setColumns auf (virtuelle Methode).
			 * Die gesetzten Spalten koennen hier fuer das jeweilige Speichermedium praepariert werden.
			 *
			 * @access private
			 **/
			function onSetColumns()
			{
				//$this->columns = $this->columns;
			}


			/**
			 * Die Funktion "insert" fuegt einen neuen Datensatz in die MySQL Tabelle ein.
			 *
			 * Bei Erfolg enthaelt das Objekt MySQL_Resultset die "last_insert_id"! Sie kann
			 * ueber MySQL_Resultset::getValue('last_insert_id') ausgegeben werden.
			 *
			 * @access public
			 * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
			 * Feldname und als Wert/Value den einzufuegenden Feldwert
			 * @return MySQL_Resultset
			 * @see MySQL_Resultset
			 **/
			function &insert($data)
			{
				if(is_array($this -> fieldnames)) {
					foreach($this -> fieldnames as $fieldname) {
						if(!isset($data[$fieldname])) $data[$fieldname] = null;
					}
				}

				$keynr = 0;
				$C16_Resultset = &$this->__createC16_Resultset($data, $keynr, 'insert');
				return $C16_Resultset;
			}

			/**
			 * Die Funktion "update" aendert einen Datensatz. Ein Datensatz kann nur geaendert
			 * werden, wenn auch der entsprechende Primaerschluessel mituebergeben wurde!! Der
			 * Schluessel wird automatisch erkannt und die Daten landen in den richtigen Datensatz.
			 * Der Primaerschluessel ist nicht aenderbar!
			 *
			 * Bei Erfolg enthaelt das Objekt MySQL_Resultset die "affected_rows"! Sie kann
			 * ueber MySQL_Resultset::getValue('affected_rows') ausgegeben werden.
			 *
			 * @access public
			 * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
			 * Feldname und als Wert/Value den einzufuegenden Feldwert
			 * @return MySQL_Resultset
			 * @see MySQL_Resultset
			 **/
			function &update($data)
			{
				$countPk = count($this -> pk);

				// eindeutigen Key suchen, der in Data vorhanden ist.
				$keyFound = false;
				if($countPk > 0)
					for($i=0; $i<$countPk; $i++) {
						$keynr = $this -> pk[$i]['_KeyNumber'];
						$fldinfos = $this -> pk[$i]['_FldInfo'];
						$keyval = array();
						foreach($fldinfos as $fldinfo) {
							if(isset($data[$fldinfo['_FldName']])) {
								$keyval[$fldinfo['_FldName']] = $data[$fldinfo['_FldName']];
								$keyFound = true;
							}
							else {
								$keyFound = false;
								break;
							}

						}
						if($keyFound) break;
					}

				if($keyFound) {
					$buf = array_map('blank', $keyval);
					$fldval = $this -> db -> fldget(array_map('blank', $keyval));
					if($keyval != $fldval) {
						$this -> __createC16_Resultset($data, $keynr, 'update', $keyval);;
						#$this -> db -> read($fldval, $this -> file, $keynr, '*', _RecLock);
					}
					else {
						$this -> __createC16_Resultset($data, 0, 'replace');
					}

				}
				else {
					$Exception = new Exception('Es wurde kein eindeutiger Schl�ssel �bergeben!', 0,
						magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
					$this -> throwException($Exception);
				}
			}

			/**
			 * Loescht "einen" Datensatz! Dabei muss ein eindeutiger Schluessel uebergeben werden.
			 *
			 * @access public
			 * @param integer|array $id Eindeutige ID eines Datensatzes (z.B. Primaerschluessel verwenden)
			 * @return C16_Resultset C16_Resultset
			 * @see C16_Resultset
			 **/
			function &delete($id)
			{
				// Key ermitteln anhand von $id (bzw. Anzahl der in $id enthaltenen Felder)
				$countPk = count($this->unique_keys);
				$numFields = is_array($id) ? count($id) : 1;
				for($i=0; $i<$countPk; $i++) {
					if($this->unique_keys[$i]['_KeyFldCount'] == $numFields) {
						$fldinfo = $this->unique_keys[$i]['_FldInfo'];
						break;
					}
				}

				// fldval zurecht formatieren, => array('schl�sselname/feldname' => 'zu suchender wert')
				if(!is_array($id)) {
					$fldval = array($fldinfo[0]['_FldName'] => $id);
				}
				else {
					list($testkey, $testval) = each($id);
					if(is_int($testkey)) {
						$fldval = array();
						for($i=0; $i<$numFields; $i++) {
							$fldval[$fldinfo[$i]['_FldName']] = $id[$i];
						}
					}
					else {
						$fldval = $id;
					}
				}

				$C16_Resultset = &$this -> __createC16_Resultset($fldval, $keynr, 'delete');
				return $C16_Resultset;
			}

			/**
			 * Holt einen Datensatz anhand der uebergebenen ID aus einer Tabelle.
			 * Wenn ein anderer unique Index abgefragt werden soll und nicht standardmaessig
			 * der Primaer Schluessel, kann dieser Feldname (/Spaltenname) ueber den
			 * 2. Parameter "$key" gesetzt werden.
			 *
			 * @access public
			 * @param integer $id Eindeutige Wert (z.B. ID) eines Datensatzes
			 * @param string $key Spaltenname (Primaer Schluessel oder Index); kein Pflichtparameter
			 * @return object C16_Resultset
			 * @see C16_Resultset
			 **/
			function &get($fldval, $key=null)
			{
				$keyname='';
				$keynr=0;

				// Key ermitteln anhand von $fldval (bzw. Anzahl der in $fldval enthaltenen Felder)
				if(is_null($key)) {
					$key = isset($this->pkDetails['_KeyNumber']) ? $this->pkDetails['_KeyNumber'] : 1;
/*					$countPk = count($this->pk);
					$numFields = is_array($fldval) ? count($fldval) : 1;

					for($i=0; $i<$countPk; $i++) {
						if($this -> pk[$i]['_KeyFldCount'] == $numFields) {
							$key = (int)$this -> pk[$i]['_KeyNumber'];
							break;
						}
					}*/
				}

				// Keyname und Keynr
				if(is_numeric($key)) {
					// Schl�sselname
					$keyname = $this->keynames[$key-1];
					// Schl�sselnummer
					$keynr = $key;
				}
				else {
					if(is_string($key) and isset($this->keys[$key])) {
						// Schl�sselname
						$keyname = $key;
						// Schl�sselnummer
						$keynr = $this->keys[$key]['_KeyNumber'];
					}
					else {
						// falls kein Schl�sselname �bergeben wurde als Key, sondern ein Feldname.... dann
						// eindeutigen Key suchen, der in Key vorhanden ist.
						$countKeys=count($this->unique_keys);
//						echo 'countUK:'.$countKeys;
//						echo pray($this->pk);
						$keyFound = false;
						for($i=0; $i<$countKeys; $i++) {
							$keynr = $this->unique_keys[$i]['_KeyNumber'];
							$fldinfos = $this->unique_keys[$i]['_FldInfo'];
							$keyval = array();
							if(sizeof($fldinfos) == sizeof($key)) {
								foreach($fldinfos as $fldinfo) {
									if((is_array($key) and in_array($fldinfo['_FldName'], $key)) or ($key==$fldinfo['_FldName'])) {
										#$keyval[$fldinfo['_FldName']] = $data[$fldinfo['_FldName']];
										$keyFound = true;
									}
									else {
										$keyFound = false;
										break;
									}

								}
							}
							if($keyFound) {
								$keyname = $this->keynames[$keynr-1];
								break;
							}
							else $keynr=-1;
						}
					}
				}
//				echo $keyname.'<br>';
//				echo 'keynr: ' . $keynr.'<br>';
				// fldval zurecht formatieren, => array('schl�sselname/feldname' => 'zu suchender wert')
				if(!is_array($fldval)) {
					//echo pray($this->keys);
					$fldval = array($this->keys[$keyname]['_FldInfo'][0]['_FldName'] => $fldval);
				}
				else {
					list($testfld, $testval) = each($fldval);
					if(is_int($testfld)) {
						$buf=$fldval;
						$fldval=array();
						foreach($buf as $fldnr => $fldvalue) {
							if(isset($this->keys[$keyname])) {
								$fldval[$this->keys[$keyname]['_FldInfo'][$fldnr]['_FldName']] = $fldvalue;
							}
						}
					}
				}


				if($keynr>0) {
					$C16_Resultset = &$this->__createC16_Resultset($fldval, $keynr, 'read');
				}
				else {
					$C16_Resultset = new Resultset();
					$C16_Resultset->addError('C16 Schl�ssel $keynr='.$keynr . ' nicht gefunden. Abfrage gescheitert.');
				}
				return $C16_Resultset;
			}

			/**
			 * Liefert mehrere Datensaetze anhand uebergebener ID's, Filter-Regeln.
			 *
			 * @access public
			 * @param unknown $id ID's (array oder integer)
			 * @param unknown $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
			 * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
			 * @param array $sorting Sortierung (siehe MySQL_DAO::__buildSorting())
			 * @param array $limit Limit -> array(Position, Anzahl Datensaetze)
			 * @return MySQL_Resultset Ergebnismenge
			 * @see MySQL_Resultset
			 * @see MySQL_DAO::__buildFilter
			 * @see MySQL_DAO::__buildSorting
			 * @see MySQL_DAO::__buildLimit
			 **/
			function &getMultiple($id=NULL, $key=NULL, $filter_rules=array(), $sorting=array(), $limit=array())
			{
				#echo pray($filter_rules);
				$C16_Resultset = new C16_Resultset($this -> db);
				/*$this -> debug('C16_Resultset -> execute(method: ' . $method . ', file: ' . $this -> file. ', keynr: ' .
					$keynr . ', fldval: ' . pray($fldval) . ')');*/

				$fldval = $this->__buildFilter($filter_rules);

				// Keyname und Keynr
				if(is_numeric($key)) {
					// Schl�sselname
					/*$keyname = $this->keynames[$key-1];*/
					// Schl�sselnummer
					/*$keynr = $key;*/
				}
				else {
					$key = $this->__findKey($fldval, $this->all_keys); //zugeh�rigen key

					# if($this->file==360) echo pray($this->all_keys);
					if($key != false) {
						$key = $key['_KeyNumber'];
					}
					elseif(count($sorting)) {
						$keynames=array();
						foreach($sorting as $keyname => $sortorder) $keynames[$keyname] = '';
						$key = $this->__findKey($keynames, $this->all_keys);
						if($key != false) $key = $key['_KeyNumber'];
					}

					if(!$key) {
						$key = $this->pkDetails['_KeyNumber'];
					}
				}
//				echo 'key:'.$key.'<br>';

				#$C16_Resultset -> prepareSorting($sorting);
				#echo pray($fldval);
/*				if($this->file==360) {
					echo ' '.$key.' ';
					echo pray($fldval);
				}*/
				#echo 'benutze key:'.$key.chr(10);
				#echo 'fldval:'.pray($fldval);
				$C16_Resultset->prepare($fldval, $this->file, $key, $this->columns, array());
				$C16_Resultset->prepareLimit($limit);

				$C16_Resultset->execute('readMultiple', $this->dbname);
				return $C16_Resultset;
			}

			/**
			 * Liefert die Anzahl getroffener Datensaetze
			 *
			 * @access public
			 * @param unknown $id ID's (array oder integer)
			 * @param unknown $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
			 * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
			 * @param array $sorting Sortierung (siehe MySQL_DAO::__buildSorting())
			 * @return MySQL_Resultset Ergebnismenge
			 * @see MySQL_Resultset
			 * @see MySQL_DAO::__buildFilter
			 **/
			function &getCount($id=NULL, $key=NULL, $filter_rules=array())
			{
				$C16_Resultset = new C16_Resultset($this->db);

				$fldval = $this->__buildFilter($filter_rules);

				$key=$this->__findKey($fldval, $this->all_keys); //zugeh�rigen key
				if($key != false) {
					$key=$key['_KeyNumber'];
				}
				if(!$key) {
					$key=$this->pkDetails['_KeyNumber'];
				}

				$C16_Resultset->prepare($fldval, $this->file, $key, $this->columns);
				$C16_Resultset->execute('count', $this->dbname);
				return $C16_Resultset;
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
			 * MySQL_DAO::__createMySQL_Resultset()
			 *
			 * @access private
			 * @param string $sql Statement
			 * @return MySQL_Resultset Ergebnismenge
			 * @see MySQL_Resultset
			 **/
			function &__createC16_Resultset($fldval, $keynr, $method, $keyval=array(), $limit=array())
			{
				$C16_Resultset = new C16_Resultset($this -> db);
				$this -> debug('C16_Resultset -> execute(method: ' . $method . ', file: ' . $this -> file. ', keynr: ' .
					$keynr . ', fldval: ' . pray($fldval) . ')');

				$C16_Resultset->prepare($fldval, $this->file, $keynr, $this->columns, $keyval);
				if(is_array($limit) and count($limit)>0) $C16_Resultset->prepareLimit($limit);
				$result = $C16_Resultset->execute($method, $this->dbname, $this->equalFieldValues);

				$this->last_result = $result;

				# ich denk mal, execSel, execLink
				return $C16_Resultset;
			}

			/**
			 * Erstellt einen Filter anhand der uebergebenen Regeln. (teils TODO!)
			 *
			 * Verfuegbare Regeln:
			 * equal : '='
			 * unequal : '!='
			 * greater : '>'
			 * less : '<'
			 * in : 'in' erwartet ein Array aus Werten (Sonderbehandlung)
			 * not in : 'not in' erwartet ein Array aus Werten (Sonderbehandlung)
			 *
			 * @access private
			 * @param array $filter_rules Filter Regeln im Format $arr = Array(feldname, regel, wert)
			 * @return string Teil eines SQL Queries
			 **/
			function __buildFilter($filter_rules, $operator='and', $skip_first_operator=false)
			{
				$filter = array();
				if (is_array($filter_rules)) {

					//$z=-1;
				    foreach($filter_rules as $record) {
				    	if(isset($record[1]) and isset($record[2])) {
							$command=$record[1];
							if($command=='=' or $command=='like' or $command=='equal') {
								$filter[$record[0]]=str_replace('%', '*', $record[2]);
							}
				    	}
					}
				}


				//echo pray($this->all_keys);
				//echo pray($filter);

				//echo pray($filter);
/*				while(list($keyname, $keyval)=each($filter)) {
					echo $keyname . '=' . $keyval . '<br>';
				}*/
				return $filter;
			}

			/**
			 * Sucht den bestm�glichen Schl�ssel f�r z.B. die Filter-Regel. Die richtige Reihenfolge der Filterangaben sind
			 * f�r die korrekte Schl�sselsuche erforderlich.
			 *
			 * @param array $needle Zu suchender Key
			 * @param array $haystack Alle Keys
			 * @return array Key
			 */
			function __findKey($needle, $haystack)
			{
				if(!is_array($needle)) if($needle != '') $needle = array($needle=>'');
//				echo pray($needle);
//				echo '<hr>';
//				echo pray($haystack);
//				echo '<hr>';
				# echo pray($haystack).'<hr>';
				if(is_array($needle) and is_array($haystack)) {
					$filter_keys = array_keys($needle);
					$numNeedle = count($filter_keys);
					$bester_key = null;
					$prev_z = 0;

					foreach($haystack as $key) { // alle vorhanden keys
//						echo ' <b>KEY '.($b+1).'</b><br>';
						$z = 0;
						$keynr = $key['_KeyNumber'];
						$numKeyFlds = count($key['_FldInfo']);

						for($i=0; $i<$numNeedle; $i++) {
//							if($this->file==334) echo 'PR�FE:'.$i.':'.$filter_keys[$i].'=='.@$key['_FldInfo'][$i]['_FldName'].'<br>';
							if(isset($key['_FldInfo'][$i]) and ($filter_keys[$i] == $key['_FldInfo'][$i]['_FldName'])) {
								#if($this->file==360) echo 'STIMMT �BEREIN:'.$i.':'.$filter_keys[$i].'<br>';
								$z++;
//								echo 'i:'.($i+1).'<br>';
								if($numKeyFlds == $numNeedle and ($i+1) == $numNeedle) {
									$bester_key = $key;
//									echo 'prev_z='.$prev_z.' ';
//									echo $numKeyFlds.'='.$numNeedle.' - ';
//									echo 'BESTER KEY:'. ($bester_key['_KeyNumber']).'<br>';
									break 2;
								}
								else if($i > $prev_z) {
									$bester_key = $key;
									$prev_z = $i;
//									echo '2BESTER KEY:'. ($bester_key['_KeyNumber']).' ('.$prev_z.')<br>';
								}
							}
							else break 1;
						}
					}
//					echo 'bester_key:'.pray($bester_key).'<br>';
					return $bester_key;
				}
				return false;
			}

			/**
			 * Erstellung einer Sortierung fuer ein SQL Statement
			 *
			 * @access private
			 * @param array $sorting Array im Format $array('feldname' => 'ASC', 'feldname' => 'DESC')
			 * @return string ORDER eines SQL Statements
			 **/
			function __buildSorting($sorting)
			{
				$sql = '';
				if (is_array($sorting)) {
					//$sql = implode(' ', $sorting);
					foreach ($sorting as $column => $sort) {
						if ($sql == '') {
						    $sql = ' ORDER BY ';
						}
						else {
							$sql .= ', ';
						}
						$sql .= $column . ' ' . $sort;
					}
				}
				return $sql;
			}

			/**
			 * MySQL_DAO::__buildLimit()
			 *
			 * @access private
			 * @param array $limit Array im Format $array([offset], max). Beispiel $array(5) oder auch $array(0, 5)
			 * @return string LIMIT eines SQL Statements
			 **/
			function __buildLimit($limit)
			{
				$sql = '';
				if (is_array($limit)) {
					if (SizeOf($limit) > 0) {
					    $sql = ' LIMIT ' . implode(', ', $limit);
					}
				}
				return $sql;
			}

			/**
			 * MySQL_DAO::__buildWhere()
			 *
			 * Erstellt die Abfrage auf Primaer Schluessel (Indexes, Unique Keys etc.).
			 *
			 * @access private
			 * @param mixed $id integer oder array (ID's)
			 * @param mixed $key integer oder array (Spalten)
			 * @return string Teil eines SQL Queries
			 **/
			function __buildWhere($id, $key)
			{
				$result='';
				if (is_null($id)) {
				    return '1';
				}
				if (is_null($key)) {
				    $key = $this -> pk;
				}
				if (is_array($key)) {
					if (!is_array($id)) {
					    $id = array($id);
					}
					$count = count($key);
					for ($i=0; $i<$count; $i++) {
						$result .= sprintf('%s="%s"', $key[$i], $this -> db -> escapestring($id[$i]));
						if ($i < ($count-1)) {
						    $result .= ' and ';
						}
					}
				}
				else {
					$result = sprintf('%s="%s"', $key, $this -> db -> escapestring($id));
				}
				return $result;
			}
		}
	}


/* -------------------------- */
####### CustomC16_DAO ########
/* -------------------------- */


	/**
	 * CustomC16_DAO
	 *
	 * Globales uebergreifendes MySQL Data Access Objects. Sofern kein spezielles Data Access Object fuer eine Tabelle existiert, wird
	 * eine Instanz der Klasse CustomMySQL_DAO angelegt.
	 *
	 * @package pool
	 * @author Alexander Manhart <alexander.manhart@gmx.de>
	 * @version $Id: MySQL_DAO.class.php,v 1.22 2006/05/03 09:09:27 manhart Exp $
	 * @access public
	 **/
	class CustomC16_DAO extends C16_DAO
	{
		/**
		 * Sets up the object.
		 *
		 * @access public
		 * @param C16_Interface $db Datenbankhandle
		 * @param string $dbname Datenbank
		 * @param string $file Datei-Name oder Datei-Nummer
		 * @param boolean $autoload_fields Felder/Spaltennamen der Tabelle automatisch ermitteln
		 **/
		function CustomC16_DAO(&$db, $dbname, $file, $autoload_fields=true)
		{
			if(!is_a($db, 'DataInterface')) {
				$Exeption = new Xception('Es wurde kein DataInterface �bergeben!', 0,
					magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__));
				$this->throwException($Exeption);
			}
			$this->db = &$db;
			$this->dbname = $dbname;
			if(!is_numeric($file)) {
				$file = $this->db->getFileNr($file, $this->dbname);
			}
			$this->file = $file;

			if ($autoload_fields) {
			    $this->init();
			}
		}
	}
?>