<?php
	/**
	* # PHP Object Oriented Library (POOL) #
	*
	* Class C16_Resultset abgeleitet von der abstrakten Basisklasse Resultset.
	* Diese Klasse kuemmert sich um das Ergebnis der C16 Abfragen und Selektionen.
	*
	* $Log$
	*
	* @version $Id$
	* @version $Revision$
	*
	* @see Resultset.class.php
	* @see MySQL_db.class.php
	* @see MySQL_DAO.class.php
	*
	* @since 2006-05-11
	* @author Alexander Manhart <alexander.manhart@freenet.de>
	* @link http://www.misterelsa.de
	*/

	if(!defined('CLASS_C16_RESULTSET')) {

		#### Prevent multiple loading
		define('CLASS_C16_RESULTSET', 1);

		/**
		 * C16_Resultset
		 *
		 * Siehe Datei fuer ausfuehrliche Beschreibung!
		 *
		 * @package pool
		 * @author Alexander Manhart <alexander.manhart@gmx.de>
		 * @version $Id$
		 * @access public
		 **/
		class C16_Resultset extends Resultset
		{
			/**
			 * Datenbankschnittstellen-Objekt MySQL_Interface
			 *
			 * @var C16_Interface
			 */
			var $db=null;

			/**
			 * Parameter für Selektion oder ab welchem Key er zu lesen beginnt
			 *
			 * @var array
			 */
			var $fldval = array();

			var $updval = array();

			var $keynr = 0;

			var $fields = array();

			/**
			 * Konstruktor
			 *
			 * Erwartet Datenbank Layer als Parameter.
			 * Der Datenbank Layer ist die Schnittstelle zur C16 Datenbank.
			 * Die C16_db Klasse uebt die eigentlichen datenbankspezfischen
			 * Operationen (z.B. c16_connect, ...) aus.
			 *
			 * @access public
			 * @param C16_Interface $db Datenbank Layer
			 * @see C16_Interface
			 **/
			function __construct(& $db)
			{
				$this->db = &$db;
			}

			/**
			 * Vorbereitung auf die Abfrage
			 *
			 * @param array $fldval Filter bzw. Positionieren des Zeigers anhand dieser Schlüsselwerte
			 * @param int $filenr Dateinummer bzw Tabelle
			 * @param int $keynr Schlüssel oder Selektion
			 * @param array $fields Angeforderte Felder
			 * @param array $updval Werte zum Setzen
			 */
			function prepare($fldval, $filenr, $keynr, $fields, $updval=array())
			{
				$this->fldval = $fldval;
				$this->filenr = $filenr;
				$this->keynr = $keynr;
				$this->fields = $fields;
				$this->updval = $updval;
			}

			/**
			 * Limit
			 *
			 * @param array $limit z.B. array(0, 1) entspricht einem Datensatz ab Position 0
			 */
			function prepareLimit($limit=array())
			{
				$this->limit = $limit;
			}

			/**
			 * Die Funktion "execute" fuehrt das uebergebene SQL Statement aus
			 * und speichert die Ergebnismenge zwischen. Ueber vererbte Iteratoren
			 * kann durch das Ergebnis navigiert werden (z.B. $this -> prev()).
			 *
			 * Fehlermeldungen landen im $this -> errorStack und koennen ueber
			 * $this -> getLastError() abgefragt werden.
			 *
			 * @access public
			 * @param string $sql SQL Statement
			 * @param string $dbname Datenbankname
			 * @return boolean Erfolgsstatus (SQL Fehlermeldungen koennen ueber $this -> getLastError() abgefragt werden)
			 * @see Resultset::getLastError()
			 **/
			function &execute($cmd='read', $database='', $equalFieldValues=true)
			{
				$this->rowset = array();

				// Abfrage anhand Schlüssel
				// $query=array(['KundNr']=>100004);
				$keyval = $this->fldval;
				// Sonderzeichen bzw. Umlaute maskieren
				$keyval = $this->db->escapeColumns($keyval);

				// Angeforderte Felder:
				// Schlüssel mit Werte tauschen, muss so aussehen: $fields=array(['KundNr']=>'');
				$fields = array_flip($this->fields);
				$escaped_fields = $this->db->escapeColumns($fields, true);

				// Schlüsselfeld
				$keynr = $this->keynr;


				if (!is_a($this->db, 'DataInterface')) {
				    $this->raiseError(__FILE__, __LINE__, 'Kein DataInterface vorhanden (@execute).');
				}
				else {
					$default_database = $this->db->default_database;
					if($database != '') $this->db->selectdb($database);

					$rowset = array();
//					echo 'Kommando: '.$cmd.'<br>';
					switch($cmd) {
						case 'read':
							$flags = _RecLock;
							#$flags = _RecTest;

							$numKeyVal = count($keyval);
							$this->db->fldset($keyval, $database);

							$result = $this->db->read($this->filenr, $this->keynr, $flags);
							if($result >= _rOk and $result < _rNoRec) {
								$fldval = $this->db->fldget($escaped_fields);
//								echo pray($keyval);
//								echo pray($fldval);
//								echo 'numKeyVal:'.$numKeyVal.'<br>';
								if(count(array_intersect_assoc($keyval, $fldval)) == $numKeyVal) {
									$fldval = $this->db->unescapeColumns($fldval);
									$rowset = array($fldval);
								}
								$this->db->read($this->filenr, $this->keynr, _RecUnlock);
							}

							break;

						case 'readMultiple':
							$limit = $this->limit;
							#echo pray($limit);

							// Limit vorhanden...
							$curPos=0;
							$numPos=0;
							if(isset($limit[1])) {
								$numPos=(int)$limit[1];
								if(isset($limit[0])) {
									$curPos=(int)$limit[0];
								}
							}
							else if(isset($limit[0])) {
								$numPos=(int)$limit[0];
							}




							$numPos = $numPos+$curPos;

							$flags = 0x00; //_RecFirst
							$aAddInfo = null;

							$numKeyVal = count($keyval);
							#echo pray($keyval).' schlüsselnummer ist '.$keynr;
							// $keynr = 1;
							$this->db->fldset($keyval);
							$i=0;
							while(($result = $this->db->read($this->filenr, $keynr, $flags, $aAddInfo)) != _rNoRec) {
								$flags = _RecNext;
								if($result >= _rOk) {
									if($i>=$curPos) {
										#$fldval = $fields;
										$fields = $this->db->fldget($escaped_fields);
										$fields = $this->db->unescapeColumns($fields);
										#echo pray($fields);
										// Solange die Daten mit dem Filter "keyval" übereinstimmen, werden
										// sie im Resultset aufgenommen
										if($equalFieldValues) { // 06.06.2012, AM, Schalter der steuert, ob Feldinhalte uebereinstimmen sollen (standardmaessig ja fuer getMultiple)
											if(count(array_intersect_assoc($keyval, $fields)) == $numKeyVal) {
												array_push($rowset, $fields);
												$escaped_fields = array_map('emptyString', $escaped_fields);
											}
											else break;
										}
										else {
											// no equalFieldValues bedeutet, der Zeiger springt auf den ersten Datensatz der uebereinstimmt und liest bis zum Ende oder LIMIT weiter
											array_push($rowset, $fields);
											$escaped_fields = array_map('emptyString', $escaped_fields);
										}

									}
								} else break;

								$i++;
								if($numPos != 0 and $i >= $numPos) {
									break;
								}
							}
							#echo $kundnr;

//							for($i=1; $i<$numPos; $i++) {
//								echo '...lese nachsten Datensatz-Nr ' . $i .'...<br>';
//								$result=$this->db->read(array(), $this->filenr, $this->keynr, $this->fields, _RecNext);
//								echo 'Ergebnis: ';
//								echo pray($result) . ' ' . $result;
//
//								#if($result >= _rOk and $result < _rNoKey) {
//								if($result) {
//									array_push($rowset, $result);
//								}
//								else break;
//
//								#}
//							}
							$result=true;
							break;

						case 'readSelection':
							$limit = $this->limit;

							// Limit zusammen setzen
							$curPos=0;
							$numPos=0;
							if(isset($limit[1])) {
								$numPos=(int)$limit[1];
								if(isset($limit[0])) {
									$curPos=(int)$limit[0];
								}
							}
							else if(isset($limit[0])) {
								$numPos=(int)$limit[0];
							}

							$numPos = $numPos+$curPos;

							$flags = _RecFirst;

							if($this->db->fldset($keyval) == _rOk) {
								if($this->db->selopen($this->filenr, $keynr) == _rOk) {
									$i=0;
									for($result=$this->db->read($this->filenr, null, _RecFirst); $result==_rOk;
										$result=$this->db->read($this->filenr, null, _RecNext)) {

										if($i>=$curPos) {
											#$fldval = $fields;
											$fields = $this->db->fldget($escaped_fields);
											$fields = $this->db->unescapeColumns($fields);
											array_push($rowset, $fields);
											$escaped_fields=array_map('emptyString', $escaped_fields);
										}

										$i++;
										if($numPos != 0 and $i >= $numPos) {
											break;
										}
									}
								}
								else {
									echo 'Die Selektionsmenge konnte nicht ermittelt werden.';
								}
							}
							else {
								echo 'Die Formulardaten konnten nicht &uuml;bertragen werden.';
							}
							$result = $this->db->numrows($this->filenr);
							$this->db->selclose();
							break;

						case 'insert':
							$flags = 0x00;
							$result = $this->db->insert($this -> fldval, $this -> filenr, $flags);
							$rowset = array($result);
							break;

						case 'replace':
							$result = $this->db->replace($this -> fldval, $this -> filenr);
							$rowset = array($result);
							break;

						case 'update':
							$result = $this->db->update($this->fldval, $this->updval, $this->filenr, $this->keynr);
							$rowset = array($result);
							break;

						case 'delete':
							$result = $this->db->delete($this -> fldval, $this -> filenr, 0);
							$rowset = array($result);
							break;

						case 'deleteAll':
							break;

						case 'count':
							$result = $this->db->numrows($this->filenr);
							$rowset = array(0 => array('count' => $result));
							break;

					}
				}
				$this->db->default_database = $default_database;

//				if (!$result) { // result stimmt nicht, da
					//$error_msg = $this -> db -> getErrormsg();
//			    	$this -> raiseError(__FILE__, __LINE__, $error_msg);
					//$this -> errorStack[] = $this -> db -> getError();
	//			}
		//		else {
					$this->rowset=array();
					if(sizeof($rowset)>0) {
						$this->rowset = $rowset;
						$this->reset();
					}
					$bResult=$result;
			//	}

/*					$sql = ltrim($sql);
					$cmd = strtolower(substr($sql, 0, 6));
					if ($cmd == 'select' or substr($cmd, 0, 4) == 'show' or substr($cmd, 0, 1) == '(') { // ( z.B. UNION
						if ($this -> db -> numrows($result) > 0) {
						    $this -> rowset = $this -> db -> fetchrowset($result);
							$this -> reset();
						}
						@$this -> db -> freeresult($result);
					}
					elseif ($cmd == 'insert') {
						$last_insert_id = $this -> db -> nextid();
					    $this -> rowset = array(0 => array(0 => $last_insert_id, 'last_insert_id' => $last_insert_id, 'id' => $last_insert_id));
						$this -> reset();
					}
					elseif ($cmd == 'update' or $cmd == 'delete') {
						$affected_rows = $this -> db -> affectedrows();
						$this -> rowset = array(0 => array(0 => $affected_rows, 'affected_rows' => $affected_rows));
						$this -> reset();
					}
					$bResult = true;
				}
*/
/*				if (defined('ACTIVATE_RESULTSET_SQL_LOG')) {
					if(ACTIVATE_RESULTSET_SQL_LOG == 1) {
						global $Log;
						$Log -> addLine('SQL ON DB ' . $dbname . ': "' . $sql . '"');
						if(!$bResult) $Log -> addlIne('SQL-ERROR ON DB ' . $dbname . ': ' . $this -> db -> getErrormsg());
					}
				}
*/				return $bResult;
			}

			/**
			 * Gibt die komplette Ergebnismenge im als SQL Insert Anweisungen (String) zurueck.
			 *
			 * @access public
			 * @param string $table
			 **/
/*			function getSQLInserts($table = null)
			{
				$sql = '';
				$line_break = '\n';
				if($this -> count() && $table)
				{
					// Zuerst die Insert Anweisung und die Feldnamen
					foreach($this -> rowset as $row)
					{
						$sql .= 'INSERT INTO '.$table.' (';
						$sql .= implode(',', array_keys($this -> rowset[0]));
						$sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'."\n";
					}
				}
				return $sql;
			}
*/		}
	}
?>