<?php
	/**
	* # PHP Object Oriented Library (POOL) #
	*
	* Class ActionHandler ist eine abstrakte Basisklasse.
	* Sie vereinfacht Datenbank Aktionen wie z.B. Loeschen,
	* Anlegen eines Datensatzes.
	*
	* Folgende Konstanten / Aktionen sind erlaubt:
	*
	* ACTION_SELECT : Selektion eines Datensatzes
	* ACTION_SEARCH : Auswahl mehrerer Datensaetze
	* ACTION_DELETE : Loeschen eines Datensatzes
	* ACTION_SAVE : Speichern (Anlegen / Aendern) eines Datensatzes
	* ACTION_CLEAR : Zuruecksetzen des ActionHandlers
	* ACTION_DRUCKEN : ausdruchen einer liste
	*
	*
	* $Log: ActionHandler.class.php,v $
	* Revision 1.16  2006/09/18 10:21:41  manhart
	* Fix Partner/Verlage l�schen
	*
	* Revision 1.15  2006/09/15 13:05:39  manhart
	* Fix in Partner/Verlage - doppelte Listeneintr�ge
	*
	* Revision 1.14  2006/09/15 12:42:50  manhart
	* Beschleunigung durch foreach
	*
	* Revision 1.13  2006/07/05 15:21:35  manhart
	* beim Selektieren wird autoamatisch der selektierte Datensatz aktualisiert (vor Selektion!), dabei wird after_search aufgerufen um die Listenformatierungen zu �bernehmen...
	*
	* Revision 1.12  2006/02/02 13:12:31  manhart
	* nur Kommentare
	*
	* Revision 1.11  2006/01/30 12:25:05  manhart
	* comment
	*
	* Revision 1.10  2005/12/06 13:17:59  manhart
	* L�schen nur, falls keine Auftr�ge existieren.
	*
	* Revision 1.9  2005/10/06 14:21:28  schmidseder
	* Bruttoabschluss
	*
	* Revision 1.8  2005/08/17 14:35:04  manhart
	* Sch�ne Fehlermeldung wenn kein PK gefunden wurde
	*
	* Revision 1.7  2005/07/20 14:56:29  manhart
	* Verlags-Mediensuche in Partner/Verlage
	*
	* Revision 1.6  2005/03/17 14:29:32  manhart
	* Fix: MySQL 4.1 nimmt es etwas genauer mit Limit
	*
	* Revision 1.5  2004/12/07 12:18:39  manhart
	* new: getFieldlist
	*
	* Revision 1.4  2004/11/26 07:37:13  manhart
	* Kommentiert
	*
	* Revision 1.3  2004/11/09 09:21:59  manhart
	* Fix Fehlermeldung
	*
	* Revision 1.2  2004/09/27 16:13:18  manhart
	* new function getSpecialCase
	*
	* Revision 1.1.1.1  2004/09/21 07:49:25  manhart
	* initial import
	*
	* Revision 1.40  2004/09/07 14:13:59  manhart
	* Fix: record for session fetched before after_update, now it's correct
	*
	* Revision 1.38  2004/09/06 14:56:35  manhart
	* Fix: after_search executes after_select with same Resultset (it's wrong, now a copy will be hand off)
	*
	* Revision 1.35  2004/09/01 14:22:36  manhart
	* data flow corrected
	*
	* Revision 1.34  2004/08/24 15:37:49  manhart
	* new: getFilterOnQuery and isQuery!
	*
	* Revision 1.33  2004/08/23 08:34:42  manhart
	* -
	*
	* Revision 1.27  2004/08/12 14:59:40  manhart
	* echo ausgabe removed!
	*
	* Revision 1.24  2004/08/04 10:13:27  manhart
	* new: buffer options!
	*
	* Revision 1.23  2004/07/15 11:49:50  manhart
	* Fixes
	*
	* Revision 1.22  2004/07/13 12:41:19  manhart
	* Fix: record
	* after_save 4. neuer Parameter
	*
	* Revision 1.21  2004/07/01 13:20:22  manhart
	* Fix in action_delete, wenn nur noch ein Datensatz in der Liste uebrig (autoselect)
	*
	* Revision 1.20  2004/06/29 14:08:55  manhart
	* Update: Fixes, Resultsets, ...
	*
	* Revision 1.19  2004/06/17 10:29:32  manhart
	* Ueberarbeitung des Objekts
	*
	* Revision 1.18  2004/06/14 14:11:35  manhart
	* enableDBSession!
	* ACTION_CLEAR fix: list must be empty
	*
	* Revision 1.17  2004/06/09 08:22:55  schulung
	* Kommentar
	*
	* Revision 1.9  2004/06/07 07:50:58  manhart
	* Kommentare gesetzt
	*
	*
	* @version $Id: ActionHandler.class.php,v 1.16 2006/09/18 10:21:41 manhart Exp $
	* @version $Revision: 1.16 $
	*
	* @see ActionHandler.class.php
	*
	* @since 2004-06-07
	* @author Alexander Manhart <alexander.manhart@freenet.de>
	* @link http://www.misterelsa.de
	*/

	#### Konstanten fuer alle Aktionen
	define('ACTION_SELECT', 	'selektieren');
	define('ACTION_SEARCH', 	'suchen');
	define('ACTION_DELETE', 	'entfernen');
	define('ACTION_SAVE',   	'speichern');
	define('ACTION_DRUCKEN',   	'drucken');
	define('ACTION_CLEAR',  	'leeren');
	define('ACTION_DUPLICATE',	'duplizieren');

	/**
	 * ActionHandler
	 *
	 * siehe Dateibeschreibung
	 *
	 * @package pool
	 * @author Alexander Manhart <alexander.manhart@freenet.de>
	 * @version $Id: ActionHandler.class.php,v 1.16 2006/09/18 10:21:41 manhart Exp $
	 * @abstract
	 * @access public
	 **/
	class ActionHandler extends Component
	{
		/**
		 * Data Access Object
		 *
		 * @var DAO $DAO
		 * @access private
		 */
		var $DAO = null;

		// @var string Typ (siehe database.inc.php) - wird verwendet, um die Tabellendefinition z.B. wobl_tbl_kundenstamm zu erstellen!!!
		// @access protected
		var $type = '';

		//@var array Einstellungen (fuer Trefferlisten, Beziehungskisten, etc.)
		//@access private
		var $options = array();

		var $pos = 0;
		var $limit = 10;
		var $action = '';

		var $optimized = false;

		//@var array temporaerer Datencontainer
		//@access private
		var $buffer = array('id' => null, 'record' => null, 'list' => array(), 'restartpage' => 0, 'options' => array());

		/**
		 * Konstruktor
		 *
		 * @access public
		 * @param Component $Owner Besitzer
		 **/
		function __construct(& $Owner)
		{
			parent::__construct($Owner);
		}

		/**
		 * Gibt den Typ aus.
		 *
		 * @return string Typ
		 **/
		function getType()
		{
			return $this -> type;
		}

		/**
		 * Initialisiert das Objekt. Erzeugt im ActionHandler ein Haupt - DAO: $this -> DAO.
		 *
		 * @param string $tabledefine Tabellendefinition aus database.inc.php
		 * @return string Typ
		 **/
		function initialize($tabledefine='')
		{
			$interfaces = $this -> Weblication -> getInterfaces();
			$this->DAO = DAO::createDAO($interfaces, (($tabledefine == '') ? $this -> tabledefine : $tabledefine), true);
		}

		/**
		 * Liefert Haupt - DAO.
		 *
		 * @access public
		 * @return DAO $DAO Data Access Object
		 **/
		function & getDAO()
		{
			return $this->DAO;
		}

		/**
		 * Setzt die Position, ab der er Datensaetze lesen soll.
		 *
		 * @access public
		 * @param int $pos Position
		 **/
		function setPos($pos)
		{
			$this -> pos = $pos;
		}

		/**
		 * ActionHandler::setLimit() setzt die maximale Anzahl an zu suchenden Datensaetzen.
		 *
		 * @access public
		 * @param int $limit Limit
		 **/
		function setLimit($limit)
		{
			$this -> limit = $limit;
		}

		/**
		 * ActionHandler::getTemplate() gibt einen zusammengebauten Dateinamen aus.
		 * Abgeleitete Klassen haben dadurch die Moeglichkeit separate Templates zu laden.
		 *
		 * @access public
		 * @param int $limit Limit
		 */
		function getTemplate($namespace)
		{
			return 'tpl_' . $namespace . '_' . $this -> type . '.html';
		}

		/**
		 * Liefert alle oder nur bestimmte Einstellungen.
		 *
		 * @access public
		 * @param string $key Schluessel (liefert bei Nichtangabe alle Einstellungen)
		 * @return unknown Array, String, Object, etc.
		 **/
		function getOptions($key=null)
		{
			if (is_null($key)) {
			    return $this -> options;
			}
			else {
				return $this -> options[$key];
			}
		}

		/**
		 * Datenaufbereitung bevor ueberhaupt eine Aktion ausgefuehrt wird.
		 *
		 * @abstract
		 * @param object $Input Daten (die aufbereitet werden koennen)
		 * @return boolean Status (bisher keine Auswirkung)
		 **/
		function prepareData(& $Input)
		{
			return 1;
		}

		/**
		 * Die Funktion validate wird vor dem Speichern und Aendern von Daten aufgerufen.
		 * Hier kommen Plausibilitaetspruefungen rein!!
		 *
		 * @abstract
		 * @param object $Input
		 * @return boolean Erfolgsstatus (1 = alles korrekt, 0 = falsch)
		 **/
		function validate(& $Input)
		{
			return 1;
		}


		function after_select(& $Resultset)
		{
			return 1;
		}

		function after_search(& $Resultset, $after_select=false)
		{
			return 1;
		}

		function before_delete($id, & $InputData)
		{
			return 1;
		}

		function before_save(& $InputData, & $Input, $action)
		{
			return 1;
		}

		function after_save(& $Resultset, & $InputData, & $Input, $action)
		{
			return 1;
		}

		/**
		 * Waehlt einen Datensatz aus (anhand Primaerschluessel).
		 * Ruft bei Erfolg (Ergebnis gefunden) die Methode "after_select"
		 * auf.
		 *
		 * IDs der Primaerschluessels muessen ueber GET oder POST
		 * gesendet werden: id_type=14;2 (type = Typ)
		 *
		 * @access private
		 * @param object $Input
		 * @return boolean
		 **/
		function actionSelect(& $Input)
		{
			$id = explode(';', $Input->getVar('id_'.$this->type));
			$pk = $this->DAO->getPrimaryKey();

			$Resultset = $this->get($id, $pk);
			if($error_message = $Resultset -> getLastError()) {
				$this->addError($error_message['message'], $error_message['code']);
				return false;
			}

			if ($Resultset->count() >= 1) {
				$this->after_select($Resultset);
				$this->buffer['id'] = $id;
				$this->buffer['record'] = $Resultset->getRow();


				//
				// Update der Daten in der Liste (auch beim Selektieren ... z.B. beim zur�ck klicken erforderlich)
				$list = $this->buffer['list'];

				$numRecords = count($list);
				if($numRecords>0) {
					$i=0;
					foreach($list as $activeRecord) {
						$override = true;
						for($k=0, $numPk=count($pk); $k<$numPk; $k++) {
							if($activeRecord[$pk[$k]] != $Resultset->getValue($pk[$k])) {
								$override = false;
								break;
							}
						}
						if($override) {
							// ver�ndert eventl. das Resultset (bzw. selektierten Datensatz)
							$this->after_search($Resultset, true);
							$Resultset->reset();

							$this->buffer['list'][$i] = $Resultset->getRow();
							break;
						}
						$i++;
					}
				}
			}
			else {
				// Error oder nichts gefunden $Resultset -> getLastError
				$this->buffer['id'] = null;
				$this->buffer['record'] = null;
				$this->addError('Keinen Datensatz mit Prim�rschl�ssel ('.
					implode(',', $pk) . ') ' . implode(',', $id) . ' gefunden! ' .
					'Generiertes SQL: ' . $this -> DAO -> db -> sql);
				return false;
			}
			return true;
		}

		/**
		 * Waehlt meherere Datensaetze aus (z.B. Suchanfrage).
		 * Ruft immer die Methode "after_search", in der
		 * die Ergebnismenge manipuliert werden kann, auf.
		 *
		 * Enthaelt die Ergebnismenge nur einen Datensatz
		 * wird zusaetzlich die Methode "after_select" aufgerufen
		 * und der Datensatz als Record im Buffer abgelegt.
		 *
		 * @access private
		 * @param Input $Input
		 * @return int
		 */
		function actionSearch(& $Input)
		{
			$pk = $this->DAO->getPrimaryKey();

			#### Filter
			$filter = $this->getFilter($Input);
			if ($this->isQuery($Input)) {
				$filterQuery = $this->getFilterOnQuery($Input);
				if (is_array($filterQuery)) {
				    $filter = array_merge($filter, $filterQuery);
				}
			}
			#### Sortierung
			$sorting = $this->getSorting($Input);
			#### Limitierung
			if($this -> limit) $limit = array($this->pos, $this->limit);

			$Resultset = $this->getMultiple(NULL, NULL, $filter, $sorting, $limit);
			if($error_message = $Resultset->getLastError()) {
				$this->addError($error_message['message'], $error_message['code']);
				return 0;
			}

			$Resultset_copy = clone $Resultset;

			$this->after_search($Resultset);
			$Resultset->reset();
			$list = $Resultset->getRowset();

			if ($Resultset->count() == 1) {

				$this->after_select($Resultset_copy);
				$record = $Resultset_copy->getRow();


				$id = array();
				foreach($pk as $pkfieldname) {
					$id[] = $Resultset->getValue($pkfieldname);
				}

			}
			else {
				$record = null;
				$id = null;
			}

			$this->buffer = array(
				'id' => $id,
				'record' => $record,
				'parent_id' => $Input->getVar('parent_id'),
				'list' => $list,
				'restartpage' => 1
			);
		}

		/**
		 * Speichert einen Datensatz ab. Erkennt selbstaendig, ob
		 * es sich um ein Einfuegen oder Aendern handelt
		 * (Erkennungsmerkmal: Primaerschluessel).
		 * Ruft vor dem Speichern "validate" und "before_save" auf.
		 * Ist der Rueckgabewert von "validate" oder "before_save" false (0),
		 * wird das Speichern der Daten verhindert.
		 *
		 * Beim Einfuegen wird die neue ID im Buffer abgelegt.
		 *
		 * @param object $Input Daten
		 * @see Input
		 **/
		function actionSave(& $Input)
		{
			$id = &$this->buffer['id'];
			$list = &$this->buffer['list'];

			$count = count($list);
			$pk = $this->DAO->getPrimaryKey();


			#### Trenne Daten (Datenfelder <-> Muell)
			$Input_filtered = $Input->filter($this->DAO->getFieldlist());

			$bool = $this->pk_exists($pk, new Input(I_POST));

			#### Plausibilitaetscheck
			if ($this->validate($Input_filtered)) {

				if ($bool) {

					#### Update
					$bResult = $this->before_save($Input_filtered, $Input, 'update');
					if (!$bResult) {
					    return 0;
					}
					$Resultset = $this->DAO->update($Input_filtered->getData());
					if($error_message = $Resultset -> getLastError()) {
						$this -> addError($error_message['message'], $error_message['code']);
						return 0;
					}
					$this->after_save($Resultset, $Input_filtered, $Input, 'update');
					$record = $Input_filtered->getData();
				}
				else {
					#### Insert
					$bResult = $this -> before_save($Input_filtered, $Input, 'insert');
					if (!$bResult) {
					    return 0;
					}
					$Resultset = $this -> DAO -> insert($Input_filtered -> getData());
					if($error_message = $Resultset -> getLastError()) {
						$this -> addError($error_message['message'], $error_message['code']);
						return 0;
					}
					$this->after_save($Resultset, $Input_filtered, $Input, 'insert');
					$record = $Input_filtered->getData();

					if(!is_array($record)) $record = array();
					//$Input_filtered -> setVar($pk[SizeOf($pk)-1], $Resultset -> getValue('last_insert_id'));
					$buf_id = $Resultset -> getValue('last_insert_id');
					$id = array();
					foreach($pk as $pkfieldname) {
						if ($Input_filtered->getVar($pkfieldname) != '' and $Input_filtered -> getVar($pkfieldname) != 0) {
						    $id[] = $Input_filtered->getVar($pkfieldname);
						}
						else {
							$id[] =  $buf_id;
							$record[$pkfieldname] = $buf_id;
						}
					}

					$this->buffer['id'] = $id;
				}

//				if($this -> optimized) {
				$MyResultset = new Resultset();
			    $MyResultset->addValue($record);
				$this->after_select($MyResultset);
				$record = $MyResultset->getRow();
	//			}

				#### back to buffer (session or whatever)
				if ($bool) {
				    #### update
					for ($i=0; $i < $count; $i++) {
						$l_id = array();
						foreach($pk as $pkfieldname) {
							$l_id[] = $list[$i][$pkfieldname];
						}
						if ($l_id == $id) {
							$list[$i] = array_merge($list[$i], $record); // 28.5.09, AM, FIXED ... schrieb die Werte von Record nicht in die Liste
							break;
						}
					}
					// $record = $list[$i];
				}
				else {
					#### insert
					array_unshift($list, $record);
					#array_push($list, $record);
				}

				$this->buffer['record'] = $record;
				$this->buffer['list'] = $list;
				$this->buffer['restartpage'] = 1;
			}
			else {
				if (!$bool) { // insert
					$this->buffer['id'] = null;
				}
				$this->buffer['record'] = $Input_filtered->getData();
				$this->buffer['restartpage'] = 0;
			}
		}

		/**
		 * ActionHandler::actionDrucken()
		 *
		 * @return
		 **/
		function actionDrucken()
		{

			#echo 'hier';
			#exit();
		}

		/**
		 * ActionHandler::actionDuplicate()
		 *
		 * @return
		 **/
		function actionDuplicate()
		{
		}

		/**
		 * ActionHandler::pk_exists()
		 *
		 * Ueberprueft, ob die Primaerschluessel einer Tabelle existieren.
		 * Je nachdem ob sie existieren oder nicht, entscheidet der ActionHandler
		 * einen "update" oder "insert" auszufuehren.
		 *
		 * Wenn sie existieren => "update", wenn nicht => "insert"
		 *
		 * @param array $pk Primaerschluessel
		 * @param object $Input Input
		 * @return boolean Ergebnis der Ueberpruefung (true=existiert, false=existiert nicht)
		 **/
		function pk_exists($pk, & $Input)
		{
			$bool=false;
			foreach($pk as $pkfieldname) {
				//echo $pkfieldname . ':'.$Input -> getVar($pkfieldname).' ';
				$bool = ($Input -> getVar($pkfieldname) > 0);
				if(!$bool) break;
			}
			return $bool;
		}


		/**
		* ActionHandler::addError()
		*
		* @return void
		* @param string $message
		* @param int $code
		* @param string $xtra
		* @desc addError nimmt eine Fehlermeldung auf und gibt am Ende eine Meldung aus.
		*/
		function addError($message, $code=0, $xtra='')
		{
			if (!is_array($this -> buffer['error'])) {
				$this -> buffer['error'] = array();
			}
			array_push($this -> buffer['error'], array('code' => $code, 'message' => $message, 'xtra' => $xtra));
		}

		function actionDelete(& $Input)
		{
			$id = $this->buffer['id']; // array of primary key
			$list = $this->buffer['list'];
			$count = count($list);
			$pk = $this->DAO->getPrimaryKey();

			// $Input_filtered = $Input -> filter($this -> DAO -> getFieldlist());

			$bool=false;
			foreach($id as $idvalue) {
				$bool = ($idvalue > 0);
				if(!$bool) break;
			}
			if ($bool) {
				$continue = $this->before_delete($id, $Input); // $Input_filtered

				if($continue) {
					$Resultset = &$this->DAO->delete($id);
					if(!$Resultset) {
						$this->addError('Loeschvorgang abgebrochen! Unbekannter Fehler.', 0);
						return 0;
					}
					if($error_message = $Resultset->getLastError()) {
						$this->addError($error_message['message'], $error_message['code']);
						return 0;
					}

                    $ah_status = $Resultset->getValue('ah_status');


                    #### Datensatz aus dem Buffer entfernen
                    for ($i = 0; $i < $count; $i++) {
                        $l_id = array();
                        foreach ($pk as $pkfieldname) {
                            $l_id[] = $list[$i][$pkfieldname];
                        }
                        if ($l_id == $id) {
                            if($ah_status != 'updated') {
                                unset($list[$i]);
                            }
                            break;
                        }
                    }
                    $list = array_values($list);
                    $numRecords = count($list);



                    if ($numRecords == 1) {
                        $select_nr = 0;
						$Resultset = new Resultset();
						$Resultset->addValue($list[$select_nr]);
						$this->after_select($Resultset);
						$record = $list[$select_nr];

						$id = array();
						foreach($pk as $pkfieldname) {
							$id[] = $list[$select_nr][$pkfieldname];
						}
					}
                    elseif($ah_status == 'updated') { // if the record was not deleted.
                        $Data = new Input();
                        $Data->setVar('id_'.$this->type, implode(';', $id));
                        $this->actionSelect($Data);
                        $record = $this->buffer['record'];
                        $list = $this->buffer['list'];
                    }
                    else {
						$record = null;
						$id = null;
					}

                    $this->buffer['id'] = $id;
                    $this->buffer['record'] = $record;
                    $this->buffer['list'] = $list;
				}

			}
			else {
				// Error oder nichts gefunden
			}

			$this->buffer['restartpage'] = 1;
		}

		/**
		 * ActionHandler::actionClear()
		 *
		 * Leert den internen Buffer. Damit sich das Leeren auf die Masken auswirkt,
		 * wird der Wert fuer "restartpage" auf 1 gesetzt.
		 *
		 * @access private
		 **/
		function actionClear()
		{
			$this -> buffer = array('id' => null, 'record' => null, 'list' => array(), 'restartpage' => 1, 'options' => array());
		}

		/**
		 * ActionHandler::loadFromSession()
		 *
		 * Laedt Daten aus der Session in den internen Buffer.
		 *
		 * @access public
		 * @param object $Session Session
		 **/
		function loadFromSession(& $Session)
		{
			$this->buffer['id'] = $Session -> getVar('id_'.$this->type);
			$this->buffer['list'] = $Session -> getVar('list_' . $this -> type);
			$this->buffer['record'] = $Session -> getVar('record_' . $this -> type);
			$this->buffer['options'] = array();
			$this->buffer['restartpage'] = 0;
			$this->buffer['error'] = array();
		}

		/**
		 * ActionHandler::saveToSession()
		 *
		 * Speichert die Daten aus dem internen Buffer in die Session.
		 *
		 * Folgende Werte werden abgelegt:
		 *
		 * id_type : Primaerschluessel
		 * record_type : gewaehlter Datensatz
		 * list_type : Auswahl von Datensaetzen
		 * restartpage : Boolean (1, 0), ob Seite neu geladen werden muss
		 *
		 * "type" wird ersetzt durch den ActionHandler Typ!
		 *
		 * @access public
		 * @param object $Session Session
		 **/
		function saveToSession(& $Session)
		{
			$saveData = array(
				'id_' . $this->type => $this->buffer['id'],
				'record_' . $this -> type => $this -> buffer['record'],
				'list_' . $this -> type => $this -> buffer['list'],
				'options_' . $this -> type => $this -> buffer['options'],
				'restartpage' => $this -> buffer['restartpage'],
				'error' => $this -> buffer['error']
			);
			if($this->buffer['parent_id']) $saveData['parent_id_'.$this->type] = $this->buffer['parent_id'];
			$Session->setVar($saveData);
		}

		function saveToDBSession(& $DBSession)
		{
			$this->saveToSession($DBSession);
		}

		function loadFromDBSession(& $DBSession)
		{
			$this->loadFromSession($DBSession);
		}

		function loadFromInput(& $Input)
		{
		}

		function saveToInput(& $Input)
		{
		}

		/**
		 * Aktion ausfuehren
		 *
		 * Um eine Aktion auszufuehren, muss zuerst eine Aktion uebergeben werden. Dies geschieht beispielsweise ueber:
		 * $Input -> setVar(ACTION_SELECT); $ActionHandler -> perform($Input);
		 * Siehe Konstanten in ActionHandler.class.php
		 *
		 * @param object $Input Klasse vom Typ Input (enthaelt Daten z.B. "action", Datensatz);
		 * @return boolean Erfolgsstatus
		 **/
		function perform(&$Input)
		{
			$this->action = $Input->getVar('action');

			if ($this->action == '') {
				return 0;
			}
			//$DAO = & $this -> DAO;
			$this->prepareData($Input);

			switch($this -> action){
				case ACTION_SELECT:
					#### ein Datensatz wurde gewaehlt (Input = POST|GET)
					$this->actionSelect($Input);
					break;

				case ACTION_SEARCH:
					#### mehrere Datensaetze werden gewaehlt (Input = POST|GET)
					$this->actionSearch($Input);
					break;

				case ACTION_SAVE:
					#### speichern eines Datensatzes (Input = POST|GET)
					$this->actionSave($Input);
					break;

				case ACTION_DELETE:
					#### loeschen eines Datensatzes
					$this->actionDelete($Input);
					break;

				case ACTION_DRUCKEN:
					#### ausdrucken einer Liste (Input = POST|GET)
					$this->actionDrucken();
					break;

				case ACTION_CLEAR:
					$this->actionClear();
					break;

				case ACTION_DUPLICATE:
					$this->actionDuplicate();
					break;

				default:
					die('Wrong action: ' . $this -> action . ' in File ' . __FILE__ . '(Line: ' . __LINE__ . ')');
			} // switch

			return 1;
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
		 * @return object MySQL_Resultset
		 * @see MySQL_DAO::get
		 * @see MySQL_Resultset
		 **/
		function &get($id, $key=NULL)
		{
			$Resultset = $this->DAO->get($id, $key);
			return $Resultset;
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
		 * @return object MySQL_Resultset
		 * @see MySQL_DAO::getMultiple
		 * @see MySQL_Resultset
		 * @see MySQL_DAO::__buildFilter
		 * @see MySQL_DAO::__buildSorting
		 * @see MySQL_DAO::__buildLimit
		 **/
		function & getMultiple($id=NULL, $key=NULL, $filter_rules=array(), $sorting=array(), $limit=array())
		{
			$Resultset = $this -> DAO -> getMultiple($id, $key, $filter_rules, $sorting, $limit);
			return $Resultset;
		}

		/**
		 * ActionHandler::getFilter()
		 *
		 * Filtert Datensaetze.
		 *
		 * @abstract
		 * @param object $Input Input-Daten
		 * @return array Filterung z.B. array_push($filter, array('kundennr', 'equal', $suchfeld));
		 **/
		function getFilter(& $Input)
		{
			return array();
		}

		/**
		 * ActionHandler::getFilterOnQuery()
		 *
		 * Filtert Datensaetze bei Suchanfrage.
		 *
		 * @abstract
		 * @param object $Input Input-Daten
		 * @return array Filterung
		 **/
		function getFilterOnQuery(& $Input)
		{
			return array();
		}

		/**
		 * ActionHandler::isQuery()
		 *
		 *
		 *
		 * @param $Input
		 * @return
		 **/
		function isQuery(& $Input)
		{
			return ($this -> action == ACTION_SEARCH) and ($Input -> getVar('submit' . $this -> type) == 1);
		}

		/**
		 * ActionHandler::getSorting()
		 *
		 * Sortiert Datensaetze
		 *
		 * @param object $Input Input-Daten
		 * @return array Sortierung
		 **/
		function getSorting(& $Input)
		{
			return array();
		}

		/**
		 * ActionHandler::getSpecialCase()
		 *
		 * Fuer Sonderfaelle gedacht: siehe sinnic classes/AHpreis.class.php
		 *
		 * @param $key
		 * @param unknown $forGUI
		 * @return
		 **/
		function getSpecialCase($key, $forGUI=null)
		{
			return false;
		}

		/**
		 * ActionHandler::getFieldlist()
		 *
		 * Alle Felder
		 *
		 * @param $key
		 * @param unknown $forGUI
		 * @return
		 **/
		function getFieldlist($fields=array())
		{
			$arrResult = array_merge($this -> DAO -> getFieldlist(), $fields);;
			return $arrResult;
		}
	}
?>