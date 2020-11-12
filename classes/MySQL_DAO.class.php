<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class MySQL_DAO abgeleitet von der abstrakten Basisklasse DAO.
* Diese Klasse kapselt die MySQL Aktionen "select, select count(), insert, update, delete"!
* Dabei wurden die Basisfunktionen "get, getMultiple, update, insert, delete" so angepasst, dass
* die korrekte SQL Syntax fuer die Kommunikation mit dem MySQL Server erstellt wird.
*
* $Log: MySQL_DAO.class.php,v $
* Revision 1.39  2007/05/02 11:35:41  manhart
* -/-
*
* Revision 1.38  2007/04/04 10:43:06  manhart
* Fix 'leerzeichen' in Spaltennamen
*
* Revision 1.37  2007/03/29 09:20:57  manhart
* n/a
*
* Revision 1.36  2007/02/16 07:45:03  manhart
* no message
*
* Revision 1.35  2007/02/12 14:14:36  manhart
* Fix: primarykey exception
*
* Revision 1.34  2007/02/01 13:43:15  manhart
* Fixed: "mysql_escape_string am falschen Platz"
*
* Revision 1.33  2007/01/30 13:04:06  manhart
* Fixed constructor call in CustomMySQL_DAO
*
* Revision 1.32  2007/01/30 12:32:10  manhart
* Fix reserved words in column_list
*
* Revision 1.30  2006/12/28 17:28:43  manhart
* Fixed "tableAlias in __buildWhere"
*
* Revision 1.26  2006/09/27 11:24:59  manhart
* n/a
*
* Revision 1.25  2006/09/22 12:31:40  manhart
* new function deleteMultiple
*
* Revision 1.24  2006/09/07 14:16:58  manhart
* new setTableAlias
*
* Revision 1.23  2006/08/07 11:36:59  manhart
* Exception -> Xception (PHP5 kompatibel)
*
* Revision 1.22  2006/05/03 09:09:27  manhart
* new feature update mit KEY
*
* Revision 1.21  2006/05/02 08:15:37  manhart
* Fix
*
* Revision 1.20  2006/04/11 11:57:53  manhart
* Aenderung listfields, Fix E_NOTICE
*
* Revision 1.19  2006/03/21 09:57:26  manhart
* new foundRows()
*
* Revision 1.18  2006/02/23 08:40:33  manhart
* na
*
* Revision 1.17  2006/02/17 09:41:46  schmidseder
* no message
*
* Revision 1.16  2006/02/15 09:08:43  manhart
* Bugfix bei Verwendung von get ohne Parameter
*
* Revision 1.15  2006/02/02 10:05:53  manhart
* no message
*
* Revision 1.14  2005/12/30 12:45:06  manhart
* no message
*
* Revision 1.12  2005/11/16 16:26:42  manhart
* Big Improvement: check variable type for sql statement
*
* Revision 1.11  2005/10/06 14:21:28  schmidseder
* Bruttoabschluss
*
* Revision 1.10  2005/10/04 11:05:04  manhart
* no message
*
* Revision 1.9  2005/10/04 11:04:06  manhart
* Fix null in update
*
* Revision 1.8  2005/10/04 11:00:12  manhart
* reserved word list in update
*
* Revision 1.7  2005/09/15 14:33:17  manhart
* Spezielle MySQL Kommandos nicht maskieren!
*
* Revision 1.5  2005/06/16 10:31:32  manhart
* Fix undefined Variable
*
* Revision 1.4  2005/06/14 11:47:12  manhart
* Fix raise Xception
*
* Revision 1.3  2005/02/18 10:49:38  manhart
* k
*
* Revision 1.2  2005/01/07 14:01:49  manhart
* debug!
*
* Revision 1.1.1.1  2004/09/21 07:49:25  manhart
* initial import
*
* Revision 1.31  2004/09/02 12:44:09  manhart
* Fix: fieldnames with space aborted!
*
* Revision 1.30  2004/08/19 15:05:58  manhart
* Fix rueckgabe bei MySQL_DAO::update muss immer ein Objekt sein
*
* Revision 1.29  2004/08/19 06:48:50  manhart
* fix reference at MySQL_DAO::__createMySQLResultset
*
* Revision 1.28  2004/08/03 16:14:42  manhart
* Fix debug
*
* Revision 1.27  2004/08/02 11:16:13  manhart
* New: Debugging Rows
*
* Revision 1.26  2004/06/29 14:10:45  manhart
* Fix in getCount. * instead of primary keys
*
* Revision 1.25  2004/04/29 14:42:44  manhart
* __buildFilter now supports operators like or, and, ( ...
*
* Revision 1.24  2004/04/29 10:39:28  manhart
* New Feature, or Verkn�pfungen
*
* Revision 1.23  2004/04/20 16:17:57  manhart
* update
*
* Revision 1.22  2004/04/01 15:11:44  manhart
* Interface Types implemented, Comments/Description
*
*
* @version $Id: MySQL_DAO.class.php,v 1.39 2007/05/02 11:35:41 manhart Exp $
* @version $Revision: 1.39 $
*
* @see DAO.class.php
* @see MySQL_Interface.class.php
* @see MySQL_Resultset.class.php
*
* @since 2003/07/10
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
*/

use pool\classes\Translator;

// Reservierte Wörter kompatibel mit MySQL 5.1 (und abwärts)
$GLOBALS['MySQL_RESERVED_WORDS'] = array_flip(array('ACCESSIBLE', 'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'ASENSITIVE',
    'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY', 'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR',
    'CHARACTER', 'CHECK', 'COLLATE', 'COLUMN', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE', 'CONVERT',
    'CREATE', 'CROSS', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE',
    'DATABASES', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT',
    'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP',
    'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE', 'FETCH',
    'FLOAT', 'FLOAT4', 'FLOAT8', 'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GRANT', 'GROUP', 'HAVING',
    'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE',
    'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INT1', 'INT2', 'INT3', 'INT4', 'INT8', 'INTEGER',
    'INTERVAL', 'INTO', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE',
    'LIMIT', 'LINEAR', 'LINES', 'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT',
    'LOOP', 'LOW_PRIORITY', 'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND',
    'MINUTE_SECOND', 'MOD', 'MODIFIES', 'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON',
    'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY',
    'PROCEDURE', 'PURGE', 'RANGE', 'READ', 'READS', 'READ_ONLY', 'READ_WRITE', 'REAL', 'REFERENCES', 'REGEXP',
    'RELEASE', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE', 'RIGHT', 'RLIKE',
    'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 'SHOW', 'SMALLINT',
    'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING', 'SQL_BIG_RESULT',
    'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE', 'TERMINATED', 'THEN',
    'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE', 'UNDO', 'UNION', 'UNIQUE', 'UNLOCK',
    'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY',
    'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE', 'WITH', 'WRITE', 'X509', 'XOR', 'YEAR_MONTH',
    'ZEROFILL', 'DATE_FORMAT', 'LAST_DAY'));

if(!defined('CLASS_MYSQLDAO')) {

    #### Prevent multiple loading
    define('CLASS_MYSQLDAO', 1);

    function is_subquery($op, $value) {
        return (strpos($value, '(SELECT ') !== false/* and ($op == 'IN' or $op == 'ANY' or $op == 'SOME' or $op == 'ALL')*/);
    }

    /**
     * MySQL_DAO
     *
     * Siehe Datei fuer ausfuehrliche Beschreibung!
     *
     * @package pool
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: MySQL_DAO.class.php,v 1.39 2007/05/02 11:35:41 manhart Exp $
     * @access public
     **/
    class MySQL_DAO extends DAO
    {
        /**
         * MySQL_Interface
         *
         * @access private
         * @var MySQL_Interface
         */
        var $db = null;

        //@var string Datenbankname
        //@access protected
        var $dbname = '';

        //@var string Spalten einer Tabelle, getrennt mit Komma
        //@access protected
        var $column_list;

        //@var string Tabellenname
        //@access protected
        var $table;

        var $tableAlias = '';

        var $reserved_words = array();

        var $MySQL_trans = array(
            'equal'	=> '=',
            'unequal' => '!=',
            'greater' => '>',
            'greater than' => '>=',
            'less' => '<',
            'less than' => '<=',
            'in' => 'in',
            'not in' => 'not in',
            'is' => 'is'
        );

        /**
         * columns to translate
         *
         * @var array
         */
        protected array $translate = [];

        /**
         * translates field values within filter / sorting methods
         *
         * @var array|string[][]
         */
        protected array $translateValues = [];

        /**
         * @var object|Translator|null
         */
        protected Translator $Translator;

        /**
         * MySQL_DAO constructor.
         */
        function __construct()
        {
            parent::__construct();

            // only if Translator needed
            if($this->translate) {
                $this->Translator = Translator::getInstance();
            }

            $this->reserved_words = &$GLOBALS['MySQL_RESERVED_WORDS'];
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
            $this->pk = array();
            $this->columns = array();
            $this->field_list = $this->db->listfields($this->table, $this->dbname, $this->columns, $this->pk);

/*				$this->pk = array();

            $count = count($field_list);
            for ($i=0; $i < $count; $i++) {
                $fieldname=$field_list[$i]['name'];
                array_push($this -> columns, $fieldname);

                $flags = explode(' ', $field_list[$i]['flags']);
                if (in_array('primary_key', $flags)) {
                    array_push($this -> pk, $fieldname);
                }
            }*/

            $this->onSetColumns();
        }

        /**
         * Das Ereignis "onSetColumns" wird immer nachdem setzen der Columns
         * mit der Funktion "setColumns" ausgefuehrt und baut die Eigenschaft
         * $this -> column_list fuer die Funktionen $this -> get und
         * $this -> getMultiple zusammen.
         *
         * @access private
         **/
        function onSetColumns($withAlias=false)
        {
            $column_list = '';
            $count = count($this->columns);
            $alias = '';
            if($withAlias and $this->tableAlias) {
                $alias = $this->tableAlias.'.';
            }
            for($i=0; $i < $count; $i++) {
                $column = trim($this->columns[$i]);

                $custom_column = $alias.$column;

                if(strpos($column, ' ') !== false) { // column contains space
                    // complex column construct should not be masked
                    if(strpos($column, '(', 0) === false and
                       strpos($column, '\'', 0) === false and
                       strpos($column, '"', 0) === false and
                       stripos($column, 'as ', 0) === false) {
                        $custom_column = '`'.$column.'`'; // should be a column with space
                    }
                }
                elseif(array_key_exists(strtoupper($column), $this->reserved_words)) { // column name is reserved word
                    $custom_column = '`'.$column.'`';
                }
                //$column_list .=  /*(($this -> table) ? $this -> table . '.' : '') . */$column;
                $column_list .= $custom_column;

                if ($i < ($count - 1)) {
                    $column_list .= ', ';
                }
            }

            $this->column_list = $column_list;
        }

        /**
         * Sets columns to be translated
         *
         * @param array $columns
         */
        public function setTranslationColumns(array $columns)
        {
            $this->translate = $columns;
        }

        /**
         * Liefert alle Felder der Tabelle.
         *
         * @access public
         * @param boolean $reInit Feldliste erneuern
         * @return array Felder der Tabelle
         **/
        function getFieldlist($reInit=false)
        {
            if (count($this->columns) == 0 or $reInit) {
                $this->init();
            }
            return $this->columns;
        }

        /**
         * Liefert den MySQL Datentypen des uebergebenen Feldes
         *
         * @param string $fieldname Spaltenname
         * @return string Datentyp
         */
        function getFieldType($fieldname)
        {
            if(!$this->field_list) $this->init();
            foreach ($this->field_list as $field) {
                if($field['Field'] == $fieldname) {
                    $buf = explode(' ', $field['Type']);
                    $type = $buf[0];
                    if(($pos = strpos($type, '(')) !== false) {
                        $type = substr($type, 0, $pos);
                    }
                    return $type;
                }
            }
            return false;
        }

        /**
         * Liefert alle Informationen zu dieser Spalte (siehe SHOW COLUMNS FROM <table>)
         *
         * @param array|false $fieldname
         */
        function getFieldinfo($fieldname)
        {
            if(!$this->field_list) $this->init();
            foreach ($this->field_list as $field) {
                if($field['Field'] == $fieldname) {
                    return $field;
                }
            }
            return false;
        }

        /**
         * Formatiert eingehende Benutzerdaten �ber ein Formular oder sogar MySQL Ergebnisse einheitlich um. Praktisch im Einsatz mit array_diff_assoc
         *
         * @param array $data Daten z.B. aus Input, Resultset, etc.
         */
        function formatData(&$data)
        {
            foreach ($data as $fieldname => $fieldvalue) {
                $colinfo = $this->getFieldinfo($fieldname);

                $coltype = array();
                $enclosure = '\'';
                $delim = ' ';
                $fldcount = 0;
                $fldval = '';
                $enclosed = false;
                $coltype_mysql = $colinfo['Type'];
                for($i=0, $len=strlen($coltype_mysql); $i<$len; $i++) {
                    $chr = $coltype_mysql[$i];
                    switch($chr) {
                        case $enclosure:
                            if($enclosed && $coltype_mysql[$i+1] == $enclosure) {
                                $fldval .= $chr;
                                ++$i; //skip next char
                            }
                            else $enclosed = !$enclosed;
                            break;

                        case $delim:
                            if(!$enclosed) {
                                $coltype[$fldcount++] = $fldval;
                                $fldval = '';
                            }
                            else $fldval .= $chr;
                            break;

                        default:
                            $fldval .= $chr;
                    }
                }
                if($fldval) $coltype[$fldcount] = $fldval;

                $typeinfo = array_shift($coltype);
                $len = null;
                // $enum_values = array();
                if(($pos = strpos($typeinfo, '(')) !== false) {
                    $type = substr($typeinfo, 0, $pos);
                    if($type != 'enum') {
                        $len = substr($typeinfo, $pos+1, strlen($typeinfo)-$pos-2);
                    }
                    //else {
                    //    $enum_values = explode(',', substr($typeinfo, $pos+1, strlen($typeinfo)-$pos-2));
                    //}
                }
                else $type = $typeinfo;



                //echo $type.' mit len:'.$len.'<br>';
                switch ($type) {
                    case 'int':
                        if($fieldvalue == '') $data[$fieldname] = '0';
                        if(in_array('zerofill', $coltype)) {
                            $data[$fieldname] = sprintf('%0'.$len.'d', $fieldvalue);
                        }
                        break;

                    case 'varchar':
                    case 'enum':
                        break;

                    case 'integer': // tinyint, smallint, mediumint, int, bigint, integer
                        break;

                    case 'boolean': // boolean, bool
                        break;

                    case 'double': // float, double, decimal, real, dec, numeric, fixed
                        #$data[$fieldname] = number_format($fieldvalue, $locale[''])
                        // floatde_2php hier nicht, wenn dann mit pr�fung auf . als tausender!
                        $data[$fieldname] = floatval(str_replace(',', '.', $fieldvalue));
                        break;

                    case 'decimal':
                        $len = explode(',', $len);
                        $fieldvalue = floatval(str_replace(',', '.', $fieldvalue));
                        if(isset($len[1])) {
                            $data[$fieldname] = sprintf('%01.'.$len[1].'f', $fieldvalue);
                        }
                        else $data[$fieldname] = $fieldvalue;
                        break;

                    case 'date':
                        break;
                }
            }
        }


        function setTableAlias($alias)
        {
            $this->tableAlias = $alias;
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
            $keys = '';
            $values = '';
            foreach($data as $field => $value) {
                $keys .= sprintf('`%s`,', $field);
                // 18.06.2018, AM @deprecated $values .= sprintf('\'%s\',', $this->db->escapestring($value, $this->dbname));
                if(is_null($value)) {
                    $values .= 'NULL,';
                }
                elseif(is_integer($value) or (is_float($value))) {
                    $values .= (string)$value.',';
                }
                elseif(is_bool($value)) {
                    $values .= (int)$value.',';
                }
                else {
                    $values .= sprintf('\'%s\',', $this->db->escapestring($value, $this->dbname));
                }
            }

            if ('' == $keys) {
                $ResultSet = new Resultset();
                $ResultSet->addError('MySQL_DAO::insert failed. No fields stated!');
                return $ResultSet;
            }

            $sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table,
                substr($keys, 0, -1), substr($values, 0, -1));
            $MySQL_ResultSet = $this->__createMySQL_Resultset($sql);
            return $MySQL_ResultSet;
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
            $sizeof = sizeof($this -> pk); //$this->pk=array('idKunde')
            for ($i=0; $i<$sizeof; $i++) {
                if(!isset($data[$this -> pk[$i]])) {
                    $Resultset = new Resultset();
                    $Resultset->addError('Update is wrong. No primary key found.');
                    return $Resultset;
                }
                else {
                    $pkValue = $data[$this -> pk[$i]];
                    if(!is_array($pkValue)) {
                        $pk[] = $pkValue;
                        unset($data[$this -> pk[$i]]);
                    }
                    elseif(is_array($pkValue)) {
                        $pk[] = $pkValue[0];
                        $data[$this -> pk[$i]] = $pkValue[1];
                    }
                }
            }

            $update = '';
            foreach ($data as $field => $value) {
                if (is_null($value)) {
                    $update .= sprintf('`%s`=NULL,', $field);
                    continue;
                }
                $format = '`%s`=\'%s\',';
                // reservierte Wörter
                if(in_array(strtoupper($value) , array('NOW()', 'CURRENT_DATE()', 'CURRENT_TIMESTAMP()'))) {
                    $format = '`%s`=%s,';
                }
                $update .= sprintf($format, $field, $this->db->escapestring($value, $this->dbname));
            }

            if (!$update) {
                $MySQL_Resultset = new MySQL_Resultset($this -> db);
                return $MySQL_Resultset;
            }

            $where = $this -> __buildWhere($pk, $this -> pk);
            if ($where == '1') {
                $error_msg = 'Update maybe wrong! Do you really want to update all records in the table: '. $this -> table;
                $this -> raiseError(__FILE__, __LINE__, $error_msg);
                die($error_msg);
            }
            $sql = sprintf('update `%s` set %s where %s', $this -> table, substr($update, 0, -1), $where);
            #echo "update: ".$sql."<br>";
            $MySQL_Resultset = $this -> __createMySQL_Resultset($sql);
            return $MySQL_Resultset;
        }

        /**
         * Die Funktion "delete" loescht einen Datensatz! Dabei muss der Primaerschluessel
         * (z.B. id) uebergeben werden. Es kann pro Aufruf nur ein Datensatz geloescht werden.
         *
         * @access public
         * @param integer $id Eindeutige ID eines Datensatzes (Primaerschluessel!!)
         * @return object MySQL_Resultset
         * @see MySQL_Resultset
         **/
        function &delete($id)
        {
            // $query = sprintf('update %s set _removed=1, _modified=now() where %s="%s"', $this -> table, $this -> pk, addslashes($id));
            $where = $this -> __buildWhere($id, $this -> pk);
            if ($where == '1') {
                $error_msg = 'Delete maybe wrong! Do you really want to delete all records in the table: '. $this -> table;
                $this -> raiseError(__FILE__, __LINE__, $error_msg);
                die($error_msg);
            }
            $sql = sprintf('delete from `%s` where %s', $this -> table, $where);
            $MySQL_Resultset = $this->__createMySQL_Resultset($sql);
            return $MySQL_Resultset;
        }

        /**
         * L�scht einen oder mehrere Datens�tze anhand des �bergebenen Filters! Achtung: immer auf korrekte Filter-Syntax achten.
         *
         * @param array $filter_rules Filter-Regeln (siehe MySQL_DAO::__buildFilter())
         * @return MySQL_Resultset Ergebnismenge
         * @see MySQL_Resultset
         * @see MySQL_DAO::__buildFilter
         */
        function deleteMultiple($filter_rules=array())
        {
            $sql = sprintf('DELETE FROM `%s` WHERE %s', $this->table, $this->__buildFilter($filter_rules, 'and', true));
            $MySQL_Resultset = $this->__createMySQL_Resultset($sql);
            return $MySQL_Resultset;
        }

        /**
         * Holt einen Datensatz anhand der uebergebenen ID aus einer Tabelle.
         * Wenn ein anderer unique Index abgefragt werden soll und nicht standardmaessig
         * der Primaer Schluessel, kann dieser Feldname (/Spaltenname) ueber den
         * 2. Parameter "$key" gesetzt werden.
         *
         * @access public
         * @param mixed $id Eindeutige Wert (z.B. ID) eines Datensatzes
         * @param mixed $key Spaltenname (Primaer Schluessel oder Index); kein Pflichtparameter
         * @return MySQL_Resultset Ergebnismenge
         * @see MySQL_Resultset
         **/
        function &get($id, $key=NULL)
        {
            // Bugfix Alexander M.; ^^ansonsten liefert __buildWhere alle Datens�tze like getMultiple
            if(is_null($id)) $id = 0;

            #echo 'id: '.$id.' key:'.$key.'<br>';
            $sql = sprintf('select %s from `%s` where %s', $this->column_list, $this->table, $this->__buildWhere($id, $key));
            #echo "get: ".$sql."<br>";

            $MySQL_Resultset = $this->__createMySQL_Resultset($sql);

            return $MySQL_Resultset;
        }

        /**
         * Liefert mehrere Datensaetze anhand uebergebener ID's, Filter-Regeln.
         *
         * @access public
         * @param mixed $id ID's (array oder integer)
         * @param mixed $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
         * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
         * @param array $sorting Sortierung (siehe MySQL_DAO::__buildSorting())
         * @param array $limit Limit -> array(Position, Anzahl Datensaetze)
         * @param array $groupby Gruppierung
         * @param array $having Filter Regeln auf die Gruppierung
         * @param array $options Optionale Parameter in der Select-Anweisung
         * @return MySQL_Resultset Ergebnismenge
         * @see MySQL_Resultset
         * @see MySQL_DAO::__buildFilter
         * @see MySQL_DAO::__buildSorting
         * @see MySQL_DAO::__buildLimit
         * @see MySQL_DAO::__buildGroupby
         **/
        public function &getMultiple($id=NULL, $key=NULL, $filter_rules=array(), $sorting=array(), $limit=array(),
            $groupby=array(), $having=array(), $options=array())
        {
            $sql = sprintf('SELECT %s %s FROM `%s` WHERE %s %s%s%s%s%s',
                implode(' ', $options),
                $this->column_list,
                $this->table,
                $this->__buildWhere($id, $key),
                $this->__buildFilter($filter_rules),
                $this->__buildGroupby($groupby),
                $this->__buildHaving($having),
                $this->__buildSorting($sorting),
                $this->__buildLimit($limit)
            );

            $MySQL_Resultset = $this->__createMySQL_Resultset($sql);
            return $MySQL_Resultset;
        }

        /**
         * Liefert die Anzahl getroffener Datensaetze
         *
         * @access public
         * @param unknown $id ID's (array oder integer)
         * @param unknown $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
         * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
         * @return MySQL_Resultset Ergebnismenge
         * @see MySQL_Resultset
         * @see MySQL_DAO::__buildFilter
         **/
        function &getCount($id=NULL, $key=NULL, $filter_rules=array())
        {
            $sql = sprintf('SELECT COUNT(%s) AS `count` FROM `%s`%s WHERE %s %s',
                '*',
                $this->table,
                $this->tableAlias,
                $this->__buildWhere($id, $key),
                $this->__buildFilter($filter_rules)
            );

            $MySQL_Resultset = $this->__createMySQL_Resultset($sql);
            return $MySQL_Resultset;
        }

        /**
         * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück
         *
         * @return int
         */
        function foundRows()
        {
            return $this->db->foundRows();
        }

        /**
         * executes sql statement and returns resultset
         *
         * @param string $sql sql statement to execute
         * @param callable|null $customCallback
         * @return MySQL_Resultset
         */
        protected function __createMySQL_Resultset(string $sql, ?callable $customCallback = null): MySQL_Resultset
        {
            $MySQL_ResultSet = new MySQL_Resultset($this->db);
            $MySQL_ResultSet->onFetchingRow($customCallback ? $customCallback : [$this, 'fetchingRow']);
            $MySQL_ResultSet->execute($sql, $this->dbname);
            return $MySQL_ResultSet;
        }

        /**
         * fetching rows
         *
         * @param array $row
         * @return array
         * @throws Exception
         */
        public function fetchingRow(array $row): array
        {
            if($this->translate) {
                return $this->translate($row);
            }
            return $row;
        }

        /**
         * translate table content
         *
         * @param array $row
         * @return array
         * @throws Exception
         */
        protected function translate(array $row): array
        {
            foreach($this->translate as $key) {
                if(isset($row[$key])) {
                    $row[$key] = $this->Translator->get($row[$key]) ?: $row[$key];
                }
            }
            return $row;
        }

        protected function translateValues(string $field): string
        {
            if(isset($this->translateValues[$field])) {
                $tmp = 'case '.$field;
                foreach($this->translateValues[$field] as $key => $transl) {
                    $tmp .= ' when \''.$transl.'\' then \''.$this->Translator->get($transl).'\'';
                }
                $tmp .= ' else '.$field.' end';
                $field = $tmp;
            }
            return $field;
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
         * @param string $operator MySQL Operator AND/OR
         * @param boolean $skip_first_operator False setzt zu Beginn keinen Operator
         * @return string Teil eines SQL Queries
         **/
        function __buildFilter($filter_rules, $operator='and', $skip_first_operator=false)
        {
            $query = '';
            $z = -1;
            if(is_array($filter_rules)) {
                foreach($filter_rules as $record) {
                    $z++;
                    if(!is_array($record)) { // operator or something manual
                        // where 1 xxx fehlendes and
                        if($z==0 and strtolower($record) != 'or') {
                            $query .= ' and ';
                        }
                        // Verknuepfungen or, and
                        $query .= ' ' . $record . ' ';
                        $skip_first_operator = true;
                        continue;
                    }
                    if($skip_first_operator) {
                        $skip_first_operator = false;
                    }
                    else {
                        $query .= ' ' . $operator . ' ';
                    }

                    if(is_array($record[0])) { // nesting
                        $query .= ' (' . $this -> __buildFilter($record[0], $record[1], true) . ') ';
                        continue;
                    }

                    // 24.07.2012, Anfuehrungszeichen steuerbar
                    $noQuotes = false;
                    $noEscape = false;
                    if(isset($record[3])) { // Optionen
                        $noQuotes = ($record[3] & DAO_NO_QUOTES);
                        $noEscape = ($record[3] & DAO_NO_ESCAPE);
                    }

                    if($this->translateValues) {
                        $record[0] = $this->translateValues($record[0]);
                    }

                    // Sonderregel "in", "not in"
                    if(isset($record[2]) and is_array($record[2])) {
                        $first = true;
                        $query .= $record[0] . ' ' . strtr($record[1], $this->MySQL_trans) . ' (';
                        foreach ($record[2] as $value) {
                            if (!$first) {
                                $query .= ', ';
                            }
                            if(is_integer($value) or is_float($value)) {
                                $query .= ' ' . $value;
                            }
                            else {
                                if($noEscape == false) $value = $this->db->escapestring($value, $this->dbname);
                                if($noQuotes == false) $value = '\''.$value.'\'';
                                $query .= $value;
                            }
                            $first = false;
                        }
                        $query .= ')';
                    }
                    else {
                        $query .= $record[0].' '.strtr($record[1], $this->MySQL_trans);
                        if (is_null($record[2])) {
                            $query .= ' NULL';
                        }
                        else {
                            if(is_integer($record[2]) or is_float($record[2]) or is_subquery($record[1], $record[2])) {
                                $query .= ' ' . $record[2];
                            }
                            else {
                                $value = $record[2];
                                if($noEscape == false) $value = $this->db->escapestring($value, $this->dbname);
                                if($noQuotes == false) $value = '\''.$value.'\'';

//									if(mb_detect_encoding($value, array('UTF-8', 'ISO-8859-1'), true) == 'ISO-8859-1') {
//										if(strpos($value, '_latin1') === false) {
//											if($noQuotes == false) $value = '_latin1'.$value;
//										}
//									}

                                $query .= ' '.$value;
                            }
                        }
                    }
                }
            }
            if($z == -1 and $skip_first_operator) { // kein Durchlauf stattgefunden
                return 1;
            }
            return $query;
        }

        /**
         * Filter-Regeln fuer die Gruppierung
         *
         * @param array $filter_rules Filter Regeln (siehe __buildFilter)
         * @return string SQL-Abfrage
         */
        function __buildHaving($filter_rules)
        {
            $query = $this->__buildFilter($filter_rules, 'AND', true);
            if($query != '') $query = ' HAVING '.$query;
            return $query;
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
            if (is_array($sorting) and count($sorting)) {
                $alias = '';
                if($this->tableAlias) $alias = $this->tableAlias.'.';

                foreach ($sorting as $column => $sort) {
                    if ($sql == '') {
                        $sql = ' ORDER BY ';
                    }
                    else {
                        $sql .= ', ';
                    }

                    $column = $alias.$column;
                    if($this->translateValues) {
                        $column = $this->translateValues($column);
                    }
                    $sql .= $column.' '.$sort;
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
            if (is_array($limit) and count($limit)) {
                $sql = ' LIMIT ' . implode(', ', $limit);
            }
            return $sql;
        }

        /**
         * Erstelle Gruppierung fuer das SQL-Statement
         *
         * @param array $groupby
         * @return string SQL-Statement
         */
        function __buildGroupby($groupby)
        {
            // GROUP BY a.test ASC WITH ROLLUP
            // array('test' => 'ASC', 'WITH ROLLUP');
            $sql = '';
            if (is_array($groupby) and count($groupby)) {
                $alias = '';
                if($this->tableAlias) $alias = $this->tableAlias.'.';

                foreach ($groupby as $column => $sort) {
                    if ($sql == '') {
                        $sql = ' GROUP BY ';
                    }
                    elseif($column == 'WITH ROLLUP') {
                        $sql .= ' '.$column;
                        break;
                    }
                    else {
                        $sql .= ', ';
                    }
                    $sql .= $alias.$column.' '.$sort;
                }
            }
            return $sql;
        }

        /**
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
            $alias = '';
            if($this->tableAlias) $alias = $this->tableAlias.'.'; // besser w�re gleich bei setTableAlias den . (Punkt) dazu; zu viele code stellen
            if (is_null($key)) {
                $key = $this->pk;
            }
            if (is_array($key)) {
                if (!is_array($id)) {
                    $id = array($id);
                }
                $count = count($key);
                for ($i=0; $i<$count; $i++) {
                    $keyName = $key[$i];
                    $result .= sprintf('%s="%s"', $alias.$keyName, $this->db->escapestring($id[$i], $this->dbname));
                    if(!isset($id[$i+1])) break;
                    $result .= ' and ';
                }
            }
            else {
                $result = sprintf('%s="%s"', $alias.$key, $this->db->escapestring($id, $this->dbname));
            }
            return $result;
        }
    }
}


/* -------------------------- */
####### CustomMySQL_DAO ########
/* -------------------------- */


/**
 * CustomMySQL_DAO
 *
 * Globales uebergreifendes MySQL Data Access Objects. Sofern kein spezielles Data Access Object fuer eine Tabelle existiert, wird
 * eine Instanz der Klasse CustomMySQL_DAO angelegt.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@gmx.de>
 * @version $Id: MySQL_DAO.class.php,v 1.39 2007/05/02 11:35:41 manhart Exp $
 * @access public
 **/
class CustomMySQL_DAO extends MySQL_DAO
{
    /**
     * Konstruktor
     *
     * Sets up the object.
     *
     * @access public
     * @param object $db Datenbankhandle
     * @param string $dbname Datenbank
     * @param string $table Tabelle
     * @param boolean $autoload_fields Felder/Spaltennamen der Tabelle automatisch ermitteln
     **/
    public function __construct(& $db, $dbname, $table, $autoload_fields=true)
    {
        parent::__construct();

        if(!is_a($db, 'DataInterface')) {
            $Xeption = new Xception('No data interface was passed!', 0, array('file' => __FILE__,
                'line' => __LINE__), null);
            $this -> throwException($Xeption);
        }
        $this->db = & $db;
        $this->dbname = $dbname;
        $this->table = $table;

        if ($autoload_fields) {
            //$this -> column_list = '*';
            //$this -> pk = 'id';
            $this->init();
        }
        else {
            // Maybe there are columns in the "columns" property
            $this->rebuildColumnList();
        }
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * rebuild column list
     */
    private function rebuildColumnList()
    {
        // Columns are predefined as property "columns".
        if(count($this->columns) > 0) {
            $table = '`'.$this->table.'`';
            $glue = '`, '.$table.'.`';
            $column_list = $table.'.`' . implode($glue, $this->columns).'`';
            $this->column_list = $column_list;
        }
    }
}