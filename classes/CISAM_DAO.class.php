<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * Class CISAM_DAO abgeleitet von der abstrakten Basisklasse DAO (Data Access Object).
 * Diese Klasse behandelt die Aktionen "read (R), update (U), insert (W), delete (D) und count (C)"!
 * Dabei wurden die Basisfunktionen "get, getMultiple, update, insert, delete" so angepasst, dass
 * die korrekte Syntax/Paketbeschreibung/Kommando fuer die Kommunikation mit den COBOL Programmen
 * erstellt wird.
 *
 * Das erstellte Kommando/Paketbeschreibung wird von der Klasse CISAM_client ueber den Java
 * Kommunikationsserver an das COBOL Programm uebertragen. Das COBOL Programm uebernimmt das
 * Kommando als Parameteruebergabe und wertet es aus.
 *
 * Das Ergebnis wird vom Java Kommunikationsserver zurueck an den CISAM_client uebertragen.
 * Der CISAM_client verpackt das Ergebnis in eine Array Liste und fuellt damit unsere Klasse
 * CISAM_Resultset.
 *
 * Beispiele der Paketbeschreibung/Kommando oder auch Syntax fuer die COBOL Schnittstelle:
 *
 * Alle Datensaetze lesenn
 * R,0,0,01,,,
 *
 * Einen Datensatz lesen:
 * R,0,1,01,02,Mora
 *
 * Die ersten 20 Datensaetze einlesen:
 * R,0,20,01,,,
 *
 * R steht fuer "Read"
 * C steht fuer "Count"
 * W steht fuer "Write"
 * U steht fuer "Update"
 *
 * Aktion "R" - read:
 *
 * Gefolgt von dem Limit 0,20
 * Gefolgt von einer Nummer zur Feldidentifikation (p-feldnummer); bzw. welche Felder sollen angezeigt/uebertragen werden!
 * Gefolgt von einer Spalte, ebenfalls als Nummer oder , "leer" (gehoert zum Filter)
 * Gefolgt von dem Wert der in der Spalte gesucht werden soll oder , leer (gehoert zum Filter)
 *
 * Aktion "C" - count:
 *
 * Aktion "W" - write:
 *
 * Gefolgt von allen Feldwerten in der richtigen CISAM-Felder-Reihenfolge, getrennt durch ein Trennzeichen
 *
 * Aktion "U" - update:
 *
 * Aktion "D" - delete:
 *
 * Das Trennzeichen fuer die Paramteruebergabe wird hier in diesem Objekt als Eigenschaft definiert: $this -> delimiter!
 *
 * $Log: CISAM_DAO.class.php,v $
 * Revision 1.6  2007/04/25 11:52:03  schmidseder
 * update-funktion f�r cisam codiert.
 *
 * Beispiel: "U;D816P-ABKZ:=1#|;D816P-KEY1:=2007188900020#|"
 *
 * Revision 1.5  2007/04/17 13:48:28  manhart
 * Fix: get
 *
 * Revision 1.4  2006/10/11 08:38:18  manhart
 * na
 *
 * Revision 1.3  2005/01/07 14:01:49  manhart
 * debug!
 *
 * Revision 1.2  2004/10/26 08:05:44  manhart
 * Hm, ? mehr filter regeln erlauben
 *
 * Revision 1.1.1.1  2004/09/21 07:49:25  manhart
 * initial import
 *
 * Revision 1.24  2004/08/03 16:14:42  manhart
 * Fix debug
 *
 * Revision 1.23  2004/06/15 14:16:57  manhart
 * Filter Regeln ausgeweitet
 *
 * Revision 1.22  2004/06/08 14:25:49  manhart
 * Operator = for CISAM query
 *
 * Revision 1.21  2004/05/06 07:53:07  manhart
 * program_prefix flexibel
 *
 * Revision 1.19  2004/04/20 16:17:57  manhart
 * update
 *
 * Revision 1.18  2004/04/13 13:16:20  manhart
 * update
 *
 * Revision 1.17  2004/04/02 11:13:23  manhart
 * Fixed Errors
 *
 * Revision 1.15  2004/03/24 14:56:37  manhart
 * Dateikommentare verfeinert
 *
 * Revision 1.14  2004/03/24 14:41:31  manhart
 * Test mit Sonderzeichen
 *
 * Revision 1.13  2004/03/24 14:35:58  manhart
 * Kommentare hinzugefuegt
 *
 * @version $Id: CISAM_DAO.class.php,v 1.6 2007/04/25 11:52:03 schmidseder Exp $
 * @version $Revision: 1.6 $
 *
 * @see DAO.class.php
 * @see CISAM_client.class.php
 * @see CISAM_Resultset.class.php
 *
 * @since 2004/03/24
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

if(!defined('CLASS_CISAM_DAO')) {

    #### Prevent multiple loading
    define('CLASS_CISAM_DAO', 1);

    /**
     * CISAM_DAO
     *
     * Siehe Datei fuer ausfuehrliche Beschreibung!
     *
     * @package pool
     * @author manhart
     * @version $Id: CISAM_DAO.class.php,v 1.6 2007/04/25 11:52:03 schmidseder Exp $
     * @access public
     **/
    class CISAM_DAO extends DAO
    {
        //@var object Client Handle (Java Server oder was auch immer ...)
        //@access private
        var $client = null;

        //@var string Feldliste
        //@access protected
        var $column_list;

        //@var string COBOL Programm
        //@access protected
        var $program;

        var $program_prefix = 'I-';

        //@var array COBOL/CISAM Feldreihenfolge (WICHTIG fuer Inserts und Updates)
        //@access protected
        //var $felder = array();

        //@var array COBOL p-Felder Definitionen (transpose)
        //@access protected
        //var $felder_trans = array();

        //@var array COBOL p-Abfragenr Definitionen (transpose)
        //@access protected
        //var $abfragenr_trans = array();

        //@var string Trennzeichen fuer die Parameteruebergabe an das COBOL Programm
        //@access private
        var $delimiter = ';';

        /**
         * CISAM_DAO::CISAM_DAO()
         *
         * Konstruktor
         *
         **/
        function __construct($client, $program)
        {
            parent::__construct();

            $this->client = &$client;
            $this->program = $program;

            $this->init();
        }

        /**
         * CISAM_DAO::init()
         *
         * Die Virtuelle Funktion "init" initialisiert p-felder und p-abfragenr.
         * WICHTIG: Felder muessen speziell fuer das jeweilige Programm angegeben werden!
         * Siehe Objekteigenschaften $this -> felder_trans und $this -> abfragenr_trans.
         * Diese beiden Eigenschaften/Properties muessen in der "init" Funktion des
         * jeweiligen Programms (siehe z.B. DAO's D101.class.php) gesetzt werden.
         *
         * @abstract
         * @access protected
         **/
        function init()
        {
        }

        /**
         * CISAM_DAO::onSetColumns()
         *
         * Das Ereignis onSetColumns wird nachdem setzen der Columns ueber $this -> setColumns
         * ausgefuehrt und stellt die Property $this -> column_list fuer die Funktionen "get",
         * "getMultiple" und fuer eigene/custom Funktionen zusammen.
         *
         * @access private
         **/
        function onSetColumns()
        {
            $column_list = '';
            $count = count($this->columns);
            for($i = 0; $i < $count; $i++) {
                $column_list .= $this->columns[$i];
                if($i < ($count - 1)) {
                    $column_list .= '|';
                }
            }
            if($column_list != '') {
                $column_list .= '|';
            }
            $this->column_list = $column_list;
        }

        function getFieldList()
        {
            // TODO i
            return $this->field_list;
        }

        /**
         * CISAM_DAO::insert()
         *
         * Die Funktion "insert" baut das Kommando zum Einfuegen eines Datensatzes in CISAM
         * zusammen und fuehrt es auch gleich aus. Als Ergebnis erhaelt man ein Objekt
         * CISAM_Resultset.
         *
         * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
         * Feldname und als Wert/Value den einzufuegenden Feldwert
         * @return object CISAM_Resultset
         **@see CISAM_Resultset.class.php
         */
        public function insert(array $data): Resultset
        {
            $buffer = '';
            $next = false;
            foreach($this->field_list as $feldname) {
                if($next) {
                    $buffer .= $this->delimiter;
                }
                $buffer .= $data[$feldname];
                $next = true;
            }

            $params = sprintf(
                '"' .
                'W' . $this->delimiter .
                '%s' .
                '"',
                $buffer);

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        /**
         * CISAM_DAO::update()
         *
         * Die Funktion "update" baut das Kommando zum Aendern eines Datensatzes in CISAM
         * zusammen und fuehrt es auch gleich aus. Als Ergebnis erhaelt man ein Objekt
         * CISAM_Resultset.
         *
         * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
         * Feldnamen und als Wert/Value den zu aendernden Feldwert
         * @return object CISAM_Resultset
         **@see CISAM_Resultset.class.php
         */
        public function update(array $data): Resultset
        {
            $sizeof = sizeof($this->pk);
            for($i = 0; $i < $sizeof; $i++) {
                if(!isset($data[$this->pk[$i]])) {
                    $Resultset = new Resultset();
                    $Resultset->addError('Update is wrong. No primary key found.');
                    return $Resultset;
                }
                else {
                    $pkValue = $data[$this->pk[$i]];
                    if(!is_array($pkValue)) {
                        $pk[$this->pk[$i]] = $pkValue;
                        unset($data[$this->pk[$i]]);
                    }
                    elseif(is_array($pkValue)) {
                        $pk[$this->pk[$i]] = $pkValue[0];
                        if(isset($pkValue[1]))
                            $data[$this->pk[$i]] = $pkValue[1];
                        else
                            unset($data[$this->pk[$i]]);
                    }
                }
            }

            // set (was soll upgedatet werden)
            $set = array();
            foreach($data as $key => $val) {
                $set[] = array($key, 'equal', $val);
            }
            $set = $this->__buildFilter($set);

            // where (was upgedatet werden soll)
            $where = array();
            foreach($pk as $key => $val) {
                $where[] = array($key, 'equal', $val);
            }
            $where = $this->__buildFilter($where);

            $params = sprintf(
                '"' .
                'U' . $this->delimiter .
                '%s' . $this->delimiter .
                '%s' .
                '"',
                $set, $where);

            // Beispiel:
            // "U;feld1:=wert1#|feld2:=wert2#|;key1:=value1#|key2:=value2#|"
            //    +------------------------+   +------------------------+
            //             set							where

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        /**
         * CISAM_DAO::delete()
         *
         * Die Funktion "delete" baut das Kommando zum Loeschen eines Datensatzes in CISAM
         * zuzsammen und fuehrt es auch gleich aus. Als Ergebnis erhaelt man ein Objekt
         * CISAM_Resultset.
         *
         * @param string|int|array $id Primaerschluessel
         * @return object CISAM_Resultset
         **@see CISAM_Resultset.class.php
         */
        function delete($id)
        {
            if(!is_array($id)) $id = array($id);
            $params = sprintf(
                '"' .
                'D' . $this->delimiter .
                '%s' . $this->delimiter .
                '"',
                implode(';', $id));

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        /**
         * CISAM_DAO::get()
         *
         * Holt einen Datensatz anhand der uebergebenen ID aus einer CISAM Tabelle.
         * Wenn ein anderer Index abgefragt werden soll und nicht standardmaessig der
         * Primaer Schluessel, kann dieser Feldname (/Spaltenname) ueber den
         * 2. Parameter "$key" gesetzt werden.
         *
         * @access public
         * @param integer $id Eindeutiger Wert (z.B. ID) eines Datensatzes
         * @param string $key Spaltenname (Primaer Schluessel oder Index); kein Pflichtparameter
         * @return object CISAM_Resultset
         * @see CISAM_Resultset
         **/
        function get($id, $key = null)
        {
            /*
            $buffer = '';
            foreach($this -> field_list as $feldname) {
                $buffer .= $this -> delimiter;
            }
            */

            if(is_array($id)) {
                $filter = $this->__buildFilter(array(array($this->pk[0], 'equal', $id[0])));
            }
            else {
                $filter = $this->__buildFilter(array(array($this->pk[0], 'equal', $id)));
            }

            $params = sprintf(
                '"' .
                'R' . $this->delimiter .
                '0' . $this->delimiter .
                '1' . $this->delimiter .
                '%s' . $this->delimiter .
                '' . $this->delimiter . // Sortierung
                '%s' . $this->delimiter .
                '%s' .
                '"',
                ($this->column_list == '*') ? 0 : count($this->columns),
                $filter,
                ($this->column_list != '*') ? $this->column_list : '');

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        function getMultiple($id = null, $key = null, $filter_rules = array(), $sorting = array(), $limit = array())
        {
            #### R;0;0;3;lieferant;W;lieferant|plz|ort
            $params = sprintf(
                '"' .
                'R' . $this->delimiter .
                '%s' . $this->delimiter .
                '%s' . $this->delimiter .
                '' . $this->delimiter . // Sortierung
                '%s' . $this->delimiter .
                '%s' .
                '"',
                $this->__buildLimit($limit),
                ($this->column_list == '*') ? 0 : count($this->columns),
                $this->__buildFilter($filter_rules),
                ($this->column_list != '*') ? $this->column_list : '');

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        function getCount($id = null, $key = null, $filter_rules = array())
        {
            $params = sprintf(
                '"' .
                'C' . $this->delimiter .
                '%s' . $this->delimiter .
                '%s' . $this->delimiter .
                '' . $this->delimiter .
                '%s' . $this->delimiter .
                '%s' .
                '"',
                '0;0',
                ($this->column_list == '*') ? 0 : count($this->columns),
                $this->__buildFilter($filter_rules),
                ($this->column_list != '*') ? $this->column_list : '');

            return $this->__createCISAM_Resultset($params, $this->program);
        }

        /**
         * CISAM_Resultset::__createCISAM_Resultset()
         *
         * @access private
         * @param string $params Parameter
         * @return object CISAM_Resultset
         * @see CISAM_Resultset
         **/
        function __createCISAM_Resultset($params, $program = '')
        {
            $CISAM_Resultset = new CISAM_Resultset($this->client);
            $this->debug('CISAM_Resultset -> execute(' . $params . ', ' . addEndingSlash(COBOL_MODULE_PATH) .
                $this->program_prefix . $program . ')');

            $CISAM_Resultset->execute($params, addEndingSlash(COBOL_MODULE_PATH) . $this->program_prefix . $program);
            return $CISAM_Resultset;
        }

        /**
         * CISAM_DAO::__buildWhere()
         *
         * Erstellt die Abfrage auf Primaer Schluessel (Indexes, Unique Keys etc.).
         *
         * @access private
         * @param unknown $id integer oder array (ID's)
         * @param unknown $key integer oder array (Spalten)
         * @return string Teil eines SQL Queries
         **/
        function __buildWhere($id, $key)
        {
            if(is_null($id)) {
                return '';
            }
            if(is_null($key)) {
                $key = $this->pk;
            }
            if(is_array($key)) {
                if(!is_array($id)) {
                    $id = array($id);
                }
                $count = count($key);
                for($i = 0; $i < $count; $i++) {
                    $result .= sprintf('%s=%s', $key[$i], $id[$i]);
                    if($i < ($count - 1)) {
                        $result .= '&';
                    }
                }
            }
            else {
                $result = sprintf('%s=%s', $key, $id);
            }
            return $result;
        }

        /**
         * CISAM_DAO::__buildLimit()
         *
         * @access private
         * @param array $limit Array im Format $array([offset], max). Beispiel $array(5) oder auch $array(0, 5)
         * @return string LIMIT eines SQL Statements
         **/
        function __buildLimit($limit)
        {
            $return = '';
            $size = SizeOf($limit);
            if(is_array($limit) and $size > 0) {
                if($size == 1) {
                    $return = '0' . $this->delimiter . $limit[0];
                }
                else {
                    $return = $limit[0] . $this->delimiter;
                    $return .= (isset($limit[1]) ? $limit[1] : '0');
                }
            }
            else {
                $return = '0' . $this->delimiter . '0';
            }
            return $return;
        }

        function __buildFilter($filter_rules)
        {
            $result = '';
            if(is_array($filter_rules)) {
                $operator_trans = array(
                    'equal' => ':=',
                    '=' => ':=',
                    'in' => ':=',
                    '>' => ':=>#'
                );

                #### remove % (sql)
                foreach($filter_rules as $record) {

                    // Sonderregel "in", "not in"
                    if(is_array($record[2])) {
                        $first = true;
                        $result .= $record[0] . strtr($record[1], $operator_trans);
                        foreach($record[2] as $value) {
                            if(!$first) {
                                $result .= '#';
                            }
                            $result .= $value;
                            $first = false;
                        }
                        $result .= '!';
                    }
                    else {
                        $result .= sprintf('%s' . strtr($record[1], $operator_trans) . '%s', $record[0], str_replace(array('%', '"', '\'', ';', '=', ':', '#', '|'), array('', '\"', '', '', '', '', '', '|'), $record[2]) . '#|');
                    }
                    //strtr($this -> column_list,	array_flip($this -> felder_trans)),
                    //strtr($record[0], array_flip($this -> abfragenr_trans)));
                    //						break;
                }
            }
            else {
                $result = ';';
            }
            return $result;
        }

        /**
         * @return Resultset
         */
        public function deleteMultiple(): Resultset
        {
            // TODO: Implement deleteMultiple() method.
        }

        /**
         * @param string $fieldName
         * @return string
         */
        public function getFieldType(string $fieldName): string
        {
            // TODO: Implement getFieldType() method.
        }

        /**
         * @param string $fieldName
         * @return array
         */
        public function getFieldInfo(string $fieldName): array
        {
            // TODO: Implement getFieldInfo() method.
        }

        /**
         * @return int
         */
        public function foundRows(): int
        {
            // TODO: Implement foundRows() method.
        }
    }
}

/* -------------------------- */
####### CustomCISAM_DAO ########
/* -------------------------- */

/**
 * CustomCISAM_DAO
 *
 * Globales uebergreifendes MySQL Data Access Objects. Sofern kein spezielles Data Access Object fuer eine Tabelle existiert, wird
 * eine Instanz der Klasse CustomMySQL_DAO angelegt.
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@gmx.de>
 * @version $Id: CISAM_DAO.class.php,v 1.6 2007/04/25 11:52:03 schmidseder Exp $
 * @access public
 **/
class CustomCISAM_DAO extends CISAM_DAO
{
}