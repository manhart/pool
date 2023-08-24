<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

use pool\classes\Core\Weblication;
use pool\classes\Database\Commands;
use pool\classes\Database\DAO;
use pool\classes\Database\DataInterface;
use pool\classes\translator\Translator;

class MySQL_DAO extends DAO
{
    /**
     * @var string|null name of the default interface type for mysql
     */
    protected static ?string $interfaceType = DataInterface::class;

    /**
     * @var string contains the columns to select
     */
    protected string $column_list = '*';

    protected string $tableAlias = '';

    /**
     * Columns to translate
     *
     * @var array
     */
    protected array $translate = [];

    /**
     * Translates field values within filter / sorting methods
     *
     * @var array|string[][]
     */
    protected array $translateValues = [];

    /**
     * @var Translator
     */
    protected Translator $Translator;

    /**
     * @var array|string[] operators for the filter method
     */
    private array $MySQL_trans = [
        'equal' => '=',
        'unequal' => '!=',
        'greater' => '>',
        'greater than' => '>=',
        'less' => '<',
        'less than' => '<=',
        'in' => 'in',
        'not in' => 'not in',
        'is' => 'is'
    ];

    /**
     * @var array
     */
    private array $cache = [
        'translatedValues' => [],
        'translate' => []
    ];

    /**
     * MySQL_DAO constructor.
     */
    protected function __construct(?DataInterface $DataInterface = null, ?string $databaseName = null, ?string $table = null)
    {
        parent::__construct($DataInterface, $databaseName, $table);

        // Maybe there are columns in the "columns" property
        // todo rework this shit
        $this->rebuildColumnList();
    }

    /**
     * rebuild column list
     */
    private function rebuildColumnList(): void
    {
        // Columns are predefined as property "columns".
        if(!$this->columns) return;

        $columns = $this->getColumns();

        $table = "`$this->table`";
        $glue = "`, $table.`";
        $this->column_list = $table . '.`' . implode($glue, $columns) . '`';
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * return DataInterface e.g. MySQL-Connection like MySQLi_Interface
     *
     * @return DataInterface
     */
    public function getDataInterface(): DataInterface
    {
        return $this->DataInterface;
    }

    /**
     * Return columns to translate into another language
     */
    public function getTranslatedColumns(): array
    {
        return $this->translate;
    }

    /**
     * Returns the data values which will be translated
     *
     * @return array|\string[][]
     */
    public function getTranslatedValues(): array
    {
        return $this->translateValues;
    }

    /**
     * set columns for translation into another language
     *
     * @param array $translate
     * @return $this
     */
    public function setTranslatedColumns(array $translate): static
    {
        $this->translate = $translate;
        return $this;
    }

    /**
     * Sets columns to be translated
     *
     * @param array $columns
     */
    public function setTranslationColumns(array $columns): void
    {
        $this->translate = $columns;
    }

    /**
     * enables auto translation of the columns defined in the property $translate
     *
     * @return $this
     */
    public function enableTranslation(): static
    {
        $this->translateValues = $this->cache['translatedValues'] ?: $this->translateValues;
        $this->translate = $this->cache['translate'] ?: $this->translate;
        return $this;
    }

    /**
     * disables auto translation of the columns defined in the property $translate
     *
     * @return $this
     */
    public function disableTranslation(): static
    {
        $this->cache['translate'] = $this->translate;
        $this->cache['translatedValues'] = $this->translateValues;

        $this->translate = [];
        $this->translateValues = [];
        return $this;
    }

    /**
     * returns columns comma separated
     *
     * @return string
     */
    public function getColumnList(): string
    {
        return $this->column_list;
    }

    /**
     * Liefert alle Felder der Tabelle.
     *
     * @param boolean $reInit Feldliste erneuern
     * @return array Felder der Tabelle
     */
    public function getFieldList(bool $reInit = false): array
    {
        if(count($this->getColumns()) == 0 or $reInit) {
            $this->fetchColumns();
        }
        return $this->getColumns();
    }

    /**
     * fetches the columns automatically from the driver / interface
     *
     * Beim Setzen der Spalten/Felder wird das Ereignis
     * $this -> onSetColumns() aufgerufen
     */
    public function fetchColumns(): static
    {
        $this->pk = [];
        $this->columns = [];
        $this->field_list = $this->DataInterface->listfields($this->table, $this->database, $this->columns, $this->pk);
        $this->onSetColumns($this->columns);
        return $this;
    }

    /**
     * Das Ereignis "onSetColumns" wird immer nachdem setzen der Columns
     * mit der Funktion "setColumns" ausgefuehrt und baut die Eigenschaft
     * $this→column_list fuer die Funktionen $this -> get und
     * $this→getMultiple zusammen.
     */
    protected function onSetColumns(array $columns): void
    {
        $column_list = '';
        $count = count($columns);
        $last = $count - 1;

        // todo add table alias
        // todo introduce expression columns
        // todo consider column properties (e.g. type, length, ...)

        for($i = 0; $i < $count; $i++) {
            // don't escape column if it has already backticks, is an expression or contains a dot
            $column = static::escapeColumn($columns[$i]);
            // add column separator
            $column_list .= $i < $last ? "$column, " : $column;
        }

        $this->column_list = $column_list;
    }

    /**
     * escape column name
     */
    static function escapeColumn(string $column): string
    {
        if(!str_contains_any($column, ['`', '*', '.', '(', 'as', '\''])) {
            $column = "`$column`";
        }
        return $column;
    }

    /**
     * Liefert den MySQL Datentypen des uebergebenen Feldes
     *
     * @param string $fieldName Spaltenname
     * @return string Datentyp
     */
    public function getFieldType(string $fieldName): string
    {
        if(!$this->field_list) $this->fetchColumns();
        foreach($this->field_list as $field) {
            if($field['COLUMN_NAME'] == $fieldName) {
                $buf = explode(' ', $field['COLUMN_TYPE']);
                $type = $buf[0];
                if(($pos = strpos($type, '(')) !== false) {
                    $type = substr($type, 0, $pos);
                }
                return $type;
            }
        }
        return '';
    }

    /**
     * Liefert alle Informationen zu dieser Spalte (siehe SHOW COLUMNS FROM <table>)
     *
     * @param string $fieldName
     * @return array
     */
    public function getFieldInfo(string $fieldName): array
    {
        if(!$this->field_list) $this->fetchColumns();
        foreach($this->field_list as $field) {
            if($field['COLUMN_NAME'] == $fieldName) {
                return $field;
            }
        }
        return [];
    }

    /**
     * get enumerable values from field
     *
     * @param string $fieldName
     * @return array|string[]
     */
    public function getFieldEnumValues(string $fieldName): array
    {
        $fieldInfo = $this->DataInterface->getColumnMetadata($this->database, $this->table, $fieldName);
        if(!isset($fieldInfo['Type'])) return [];
        $type = substr($fieldInfo['Type'], 0, 4);
        if($type != 'enum') return [];
        $buf = substr($fieldInfo['Type'], 5, -1);
        return explode('\',\'', substr($buf, 1, -1));
    }

    /**
     * Set the table alias
     *
     * @param $alias
     * @return void
     */
    public function setTableAlias($alias): void
    {
        $this->tableAlias = $alias;
    }

    /**
     * get columns with table alias
     *
     * @return array
     */
    public function getColumnsWithTableAlias(): array
    {
        return array_map(function($val) {
            return "$this->tableAlias.$val";
        }, $this->getColumns());
    }

    /**
     * Die Funktion "insert" fuegt einen neuen Datensatz in die MySQL Tabelle ein.
     *
     * Bei Erfolg enthaelt das Objekt MySQL_Resultset die "last_insert_id"! Sie kann
     * ueber MySQL_Resultset::getValue('last_insert_id') ausgegeben werden.
     *
     * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
     * Feldname und als Wert/Value den einzufuegenden Feldwert
     * @return MySQL_ResultSet
     * @see MySQL_ResultSet
     **/
    public function insert(array $data): ResultSet
    {
        $columns = '';
        $values = '';

        foreach($data as $field => $value) {
            // key concatenation
            if($columns == '') {
                $columns = "`$field`";
            }
            else {
                $columns = "$columns,`$field`";
            }

            // value concatenating
            if(is_null($value)) {
                $value = 'NULL';
            }
            elseif(is_bool($value)) {
                $value = bool2string($value);
            }
            elseif(is_array($value)) {
                $value = is_null($value[0]) ? 'NULL' : $value[0];
            }
            elseif($value instanceof Commands) {
                // reserved keywords don't need to be masked
                $expression = $this->commands[$value->name];
                if($expression instanceof Closure) {
                    $value = $expression($field);
                }
                else {
                    $value = $expression;
                }
            }
            elseif($value instanceof DateTimeInterface) {
                $value = "'{$value->format('Y-m-d H:i:s')}'";
            }
            elseif(!is_int($value) && !is_float($value)) {
                $value = $this->DataInterface->escapeString($value, $this->database);
                $value = "'$value'";
            }

            if($values == '') {
                $values = $value;
            }
            else {
                $values = "$values,$value";
            }
        }

        if('' == $columns) {
            return (new ResultSet())->addError('MySQL_DAO::insert failed. No fields stated!');
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
INSERT INTO `$this->table`
    ($columns)
VALUES
    ($values)
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * executes sql statement and returns resultset
     *
     * @param string $sql sql statement to execute
     * @param callable|null $customCallback
     * @return MySQL_ResultSet
     */
    protected function __createMySQL_Resultset(string $sql, ?callable $customCallback = null): MySQL_ResultSet
    {
        $MySQL_ResultSet = new MySQL_ResultSet($this->DataInterface);
        $MySQL_ResultSet->execute($sql, $this->database, $customCallback ?: [$this, 'fetchingRow'], $this->metaData);
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
     * @param array $data Das assoziative Array (Parameter) erwartet als Schluessel/Key einen
     * Feldname und als Wert/Value den einzufuegenden Feldwert
     * @return ResultSet
     * @see MySQL_ResultSet
     **/
    public function update(array $data): ResultSet
    {
        $sizeof = count($this->pk);
        $pk = [];
        for($i = 0; $i < $sizeof; $i++) {
            if(!isset($data[$this->pk[$i]])) {
                return (new ResultSet())->addError('Update is wrong. No primary key found.');
            }
            else {
                $pkValue = $data[$this->pk[$i]];
                if(is_array($pkValue)) {
                    $pk[] = $pkValue[0];
                    $data[$this->pk[$i]] = $pkValue[1];
                }
                else {
                    $pk[] = $pkValue;
                    unset($data[$this->pk[$i]]);
                }
            }
        }

        $set = $this->__buildAssignmentList($data);

        if(!$set) {
            return new ResultSet();
        }

        $where = $this->__buildWhere($pk, $this->pk);
        if($where == '1') {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
UPDATE `$this->table`
SET
    $set
WHERE
    $where
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * Build assignment list for update statements
     *
     * @param array $data
     * @return string
     */
    protected function __buildAssignmentList(array $data): string
    {
        $set = '';
        foreach($data as $field => $value) {
            if(is_null($value)) {
                $value = 'NULL';
            }
            elseif(is_bool($value)) {
                $value = bool2string($value);
            }
            elseif($value instanceof Commands) {
                // reserved keywords don't need to be masked
                $expression = $this->commands[$value->name];
                if($expression instanceof Closure) {
                    $value = $expression($field);
                }
                else {
                    $value = $expression;
                }
            }
            elseif($value instanceof DateTimeInterface) {
                $value = "'{$value->format('Y-m-d H:i:s')}'";
            }
            elseif(!is_int($value) && !is_float($value)) {
                $value = "'{$this->DataInterface->escapeString($value, $this->database)}'";
            }
            if($set == '') $set = "`$field`=$value";
            else $set = "$set,`$field`=$value";
        }
        return $set;
    }

    /**
     * Erstellt die Abfrage auf Primaer Schluessel (Indexes, Unique Keys etc.).
     *
     * @param mixed $id integer oder array (ID's)
     * @param mixed $key integer oder array (Spalten)
     * @return string Teil eines SQL Queries
     */
    protected function __buildWhere(mixed $id, mixed $key): string
    {
        $result = '';
        if(is_null($id)) {
            return '1';
        }
        $alias = $this->tableAlias ? "$this->tableAlias." : '';
        if(is_null($key)) {
            $key = $this->pk;
        }
        if(is_array($key)) {
            if(!is_array($id)) {
                $id = array($id);
            }
            $count = count($key);
            for($i = 0; $i < $count; $i++) {
                $keyName = $key[$i];
                $result = "$result$alias$keyName={$this->escapeWhereConditionValue($id[$i], false, false)}";
                if(!isset($id[$i + 1])) break;
                $result .= ' and ';
            }
        }
        else {
            $result = "$alias$key={$this->escapeWhereConditionValue($id, false, false)}";
        }
        return $result;
    }

    /**
     * add value to where condition
     *
     * @param mixed $value
     * @param false|int $noEscape
     * @param false|int $noQuotes
     * @return string
     */
    private function escapeWhereConditionValue(mixed $value, false|int $noEscape, false|int $noQuotes): string
    {
        if(is_int($value) || is_float($value))
            return $value;//not a stringable or a 'subQuery'
        $value = $noEscape ? $value : $this->DataInterface->escapeString($value, $this->database);
        return $noQuotes ? $value : "'$value'"; //quote
    }

    /**
     * Update multiple records at once
     *
     * @param array $data
     * @param array $filter_rules
     * @return ResultSet
     */
    public function updateMultiple(array $data, array $filter_rules): ResultSet
    {
        $set = $this->__buildAssignmentList($data);

        if(!$set) {
            return new ResultSet();
        }

        $where = $this->__buildFilter($filter_rules, 'and', true);
        if($where == '1') {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
UPDATE `$this->table`
SET
    $set
WHERE
    $where
SQL;

        return $this->__createMySQL_Resultset($sql);
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
     * @param array $filter_rules Filter Regeln im Format $arr = Array(feldname, regel, wert)
     * @param string $operator MySQL Operator AND/OR
     * @param boolean $skip_next_operator False setzt zu Beginn keinen Operator
     * @param string $initialOperator
     * @return string filter part of sql statement
     */
    protected function __buildFilter(array $filter_rules, string $operator = 'and', bool $skip_next_operator = false, string $initialOperator = ' and'): string
    {
        if(!$filter_rules)//not filter anything (terminate floating operators)
            return $skip_next_operator ? '1' : '';
        $firstRule = $filter_rules[0];
        $query = !is_array($firstRule) && !in_array(strtolower($firstRule), ['or', 'and']) ?//1. rule is a non joining operator
            $initialOperator : '';//* we add an initial 'and' operator.
        foreach($filter_rules as $record) {
            $skipAutomaticOperator = $skip_next_operator;
            if($skip_next_operator = !is_array($record)) {//record is a manual operator/SQL-command/parentheses
                $record = " $record "; //operator e.g. or, and
                $skipAutomaticOperator = true;
            }
            elseif(is_array($record[0]))// nesting detected
                $record = "({$this->__buildFilter($record[0], $record[1], true)})";//"($subFilter)"
            else {//normal record
                $field = $this->translateValues ? //get field 'name'
                    $this->translateValues($record[0]) : $record[0];//inject replace command?
                $rawInnerOperator = $record[1];
                $innerOperator = strtr($rawInnerOperator, $this->MySQL_trans);//map operators for DBMS
                $values =& $record[2];//reference assignment doesn't emit warning upon undefined keys
                //parse quotation options (defaults to false)
                $quoteSettings = is_int($record[3] ?? false) ? $record[3] : 0;
                $noQuotes = $quoteSettings & DAO::DAO_NO_QUOTES;
                $noEscape = $quoteSettings & DAO::DAO_NO_ESCAPE;
                if(is_array($values))
                    switch($rawInnerOperator) {//multi value operation
                        case 'between':
                            $value = /* min */
                                $this->escapeWhereConditionValue($values[0], $noEscape, $noQuotes);
                            $value .= ' and ';
                            $value .= /* max */
                                $this->escapeWhereConditionValue($values[1], $noEscape, $noQuotes);
                            break;
                        default://enlist all values e.g. in, not in
                            //apply quotation rules
                            $values = array_map(fn($value) => $this->escapeWhereConditionValue($value, $noEscape, $noQuotes), $values);
                            $values = implode(', ', $values) ?: 'NULL';
                            $value = "($values)";
                            break;
                    }
                elseif($values instanceof Commands) {//resolve reserved keywords TODO add parameters to commands
                    $expression = $this->commands[$values->name];
                    $value = $expression instanceof Closure ?
                        $expression($field) : $expression;//TODO? Edgecase with translatedValues and Command Default
                }
                elseif($values instanceof DateTimeInterface) {//format date-objects
                    $dateTime = $values->format($record[3] ?? 'Y-m-d H:i:s');
                    $value = "'$dateTime'";
                }
                else//sub query moved to escapeWhereConditionValue
                    $value = match (gettype($values)) {//handle by type
                        'NULL' => 'NULL',
                        'boolean' => bool2string($values),
                        'double', 'integer' => $values,//float and int
                        default => match ($this->__isSubQuery($values)) {// TODO fix insecure sub-query check
                            true => $values,
                            default => $this->escapeWhereConditionValue($values, $noEscape, $noQuotes),
                        }
                    };
                //assemble record
                $record = "$field $innerOperator $value";
            }
            $query .= !$skipAutomaticOperator ? //automatic operator?
                " $operator $record" : $record;//automation puts operator between the last record and this one
        }
        return $query;
    }

    /**
     * @param string $field
     * @return string
     */
    protected function translateValues(string $field): string
    {
        $tokens = &$this->translateValues[$field];
        if(!Weblication::getInstance()->hasTranslator() || !$tokens)
            return $field;
        $Translator = Weblication::getInstance()->getTranslator();
        $tmp = "case $field";
        foreach($tokens as $token)
            $tmp .= " when '$token' then '{$Translator->getTranslation($token, $token)}'";
        return "$tmp else $field end";
    }

    /**
     * checks value for subquery
     *
     * @param mixed $value string?
     * @return bool
     */
    private function __isSubQuery(mixed $value): bool
    {
        return str_contains($value, '(SELECT ');
    }

    /**
     * Die Funktion "delete" loescht einen Datensatz! Dabei muss der Primaerschluessel
     * (z.B. id) uebergeben werden. Es kann pro Aufruf nur ein Datensatz geloescht werden.
     *
     * @param integer $id Eindeutige ID eines Datensatzes (Primaerschluessel!!)
     * @return ResultSet
     * @see MySQL_ResultSet
     */
    public function delete($id): ResultSet
    {
        $where = $this->__buildWhere($id, $this->pk);
        if($where == '1') {
            $error_msg = 'Delete maybe wrong! Do you really want to delete all records in the table: ' . $this->table;
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
DELETE
FROM `$this->table`
WHERE
    $where
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * L�scht einen oder mehrere Datens�tze anhand des �bergebenen Filters! Achtung: immer auf korrekte Filter-Syntax achten.
     *
     * @param array $filter_rules Filter-Regeln (siehe MySQL_DAO::__buildFilter())
     * @return ResultSet Ergebnismenge
     * @see MySQL_DAO::__buildFilter
     * @see MySQL_ResultSet
     */
    public function deleteMultiple(array $filter_rules = []): ResultSet
    {
        $where = $this->__buildFilter($filter_rules, 'and', true);
        /** @noinspection SqlResolve */
        $sql = <<<SQL
DELETE
FROM `$this->table`
WHERE
    $where
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * Holt einen Datensatz anhand der uebergebenen ID aus einer Tabelle.
     * Wenn ein anderer unique Index abgefragt werden soll und nicht standardmaessig
     * der Primaer Schluessel, kann dieser Feldname (/Spaltenname) ueber den
     * 2. Parameter "$key" gesetzt werden.
     *
     * @param mixed $id Eindeutige Wert (z.B. ID) eines Datensatzes
     * @param mixed $key Spaltenname (Primaer Schluessel oder Index); kein Pflichtparameter
     * @return ResultSet Ergebnismenge
     * @see MySQL_ResultSet
     */
    public function get($id, $key = null): ResultSet
    {
        $id = $id ?? 0;
        $where = $this->__buildWhere($id, $key);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
SELECT $this->column_list
FROM `$this->table`
WHERE
    $where
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * Liefert mehrere Datensaetze anhand uebergebener ID's, Filter-Regeln.
     *
     * @param mixed|null $id ID's (array oder integer)
     * @param mixed|null $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
     * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
     * @param array $sorting Sortierung (siehe MySQL_DAO::__buildSorting())
     * @param array $limit Limit -> array(Position, Anzahl Datensaetze)
     * @param array $groupBy Gruppierung
     * @param array $having Filter Regeln auf die Gruppierung
     * @param array $options Optionale Parameter in der Select-Anweisung
     * @return ResultSet Ergebnismenge
     * @see MySQL_ResultSet
     * @see MySQL_DAO::__buildFilter
     * @see MySQL_DAO::__buildSorting
     * @see MySQL_DAO::__buildLimit
     * @see MySQL_DAO::__buildGroupBy
     */
    public function getMultiple(mixed $id = null, mixed $key = null, array $filter_rules = [], array $sorting = [], array $limit = [],
        array $groupBy = [], array $having = [], array $options = []): ResultSet
    {
        $options = implode(' ', $options);

        $where = $this->__buildWhere($id, $key);
        $filter = $this->__buildFilter($filter_rules);
        $groupBy = $this->__buildGroupBy($groupBy);
        $having = $this->__buildHaving($having);
        $sorting = $this->__buildSorting($sorting);
        $limit = $this->__buildLimit($limit);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
SELECT $options $this->column_list
FROM `$this->table`
WHERE
    $where
    $filter
    $groupBy
    $having
    $sorting
    $limit
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * Erstelle Gruppierung fuer das SQL-Statement
     *
     * @param array $groupBy
     * @return string SQL-Statement
     */
    protected function __buildGroupBy(array $groupBy): string
    {
        if(!$groupBy) return '';

        // GROUP BY a.test ASC WITH ROLLUP
        // array('test' => 'ASC', 'WITH ROLLUP');
        $sql = '';
        $alias = '';
        if($this->tableAlias) $alias = $this->tableAlias . '.';

        foreach($groupBy as $column => $sort) {
            if($sql == '') {
                $sql = ' GROUP BY ';
            }
            elseif($column == 'WITH ROLLUP') {
                $sql .= " $column";
                break;
            }
            else {
                $sql .= ', ';
            }
            $sql .= "$alias.$column $sort";
        }
        return $sql;
    }

    /**
     * Build a having statement for a SQL query
     *
     * @param array $filter_rules Filter Regeln (siehe __buildFilter)
     * @return string SQL-Abfrage
     */
    protected function __buildHaving(array $filter_rules): string
    {
        $query = $this->__buildFilter($filter_rules, 'and', false, '');
        if($query) $query = " HAVING $query";
        return $query;
    }

    /**
     * Erstellung einer Sortierung fuer ein SQL Statement
     *
     * @param array|null $sorting sorting format ['column1' => 'ASC', 'column2' => 'DESC']
     * @return string ORDER eines SQL Statements
     */
    protected function __buildSorting(?array $sorting): string
    {
        $sql = '';
        if(is_array($sorting) && count($sorting)) {
            $alias = $this->tableAlias ? "$this->tableAlias." : '';

            foreach($sorting as $column => $sort) {
                if($sql == '') {
                    $sql = ' ORDER BY ';
                }
                else {
                    $sql .= ', ';
                }

                $column = $alias . $column;
                if($this->translateValues) {
                    $column = $this->translateValues($column);
                }
                $sql .= "$column $sort";
            }
        }
        return $sql;
    }

    /**
     * @param array $limit Array im Format $array([offset], max). Beispiel $array(5) oder auch $array(0, 5)
     * @return string LIMIT eines SQL Statements
     */
    protected function __buildLimit(array $limit): string
    {
        return $limit ? ' LIMIT ' . implode(', ', $limit) : '';
    }

    /**
     * Liefert die Anzahl getroffener Datensaetze
     *
     * @param mixed|null $id ID's (array oder integer)
     * @param mixed|null $key Spalten (array oder string) - Anzahl Spalten muss identisch mit der Anzahl ID's sein!!
     * @param array $filter_rules Filter Regeln (siehe MySQL_DAO::__buildFilter())
     * @return ResultSet Ergebnismenge
     * @see MySQL_ResultSet
     * @see MySQL_DAO::__buildFilter
     */
    public function getCount(mixed $id = null, mixed $key = null, array $filter_rules = []): ResultSet
    {
        $where = $this->__buildWhere($id, $key);
        $filter = $this->__buildFilter($filter_rules);
        $sql = <<<SQL
SELECT COUNT(*) AS `count`
FROM `$this->table`$this->tableAlias
WHERE
    $where
    $filter
SQL;

        return $this->__createMySQL_Resultset($sql);
    }

    /**
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück
     *
     * @return int
     */
    public function foundRows(): int
    {
        return $this->DataInterface->foundRows();
    }

    /**
     * fetching rows
     *
     * @param array $row
     * @return array
     */
    public function fetchingRow(array $row): array
    {
        return $this->translate ? $this->translate($row) : $row;
    }

    /**
     * Translate table content
     *
     * @param array $row
     * @return array
     */
    protected function translate(array $row): array
    {
        $Weblication = Weblication::getInstance();
        if(!$Weblication->hasTranslator())//no translator
            return $row;//unchanged
        $Translator = $Weblication->getTranslator();
        // another idea to handle columns which should be translated
        // $translationKey = "columnNames.{$this->getTableName()}.{$row[$key]}";
        foreach($this->translate as $key)
            if(isset($row[$key]) && is_string($row[$key]))
                $row[$key] = $Translator->getTranslation($row[$key], $row[$key], noAlter: true);
        return $row;
    }

    /**
     * Make filter rules based on search string or defined search keywords
     *
     * @param array $columns
     * @param string $searchString
     * @param array $definedSearchKeywords
     * @return array
     */
    public function makeFilter(array $columns = [], string $searchString = '', array $definedSearchKeywords = []): array
    {
        if(!$searchString && !$definedSearchKeywords)
            return [];
        $filter = [];
        $defined_filter = [];
        foreach($columns as $column) {
            $originalExpr = $column['expr'] ?? $column; // column or expression
            $filterExpr = match ($column['type'] ?? '') {
                'date', 'date.time' => //temporal type
                ($sqlTimeFormat = Weblication::getInstance()->getDefaultFormat("mysql.date_format.{$column['type']}")) ?//try fetch format-string
                    "DATE_FORMAT($originalExpr, '$sqlTimeFormat')" : $originalExpr,//set SQL to format temporal value
                default => $originalExpr//unchanged
            };
            $columnName = $column['alias'] ?? $column;
            if(isset($definedSearchKeywords[$columnName])) {//found additional metadata
                $operator = 'like';
                /** @var string $filterByValue */
                $filterByValue = $definedSearchKeywords[$columnName];//get keyword for column name?
                switch(($column['filterControl'] ?? false ?: 'input')) {//type of input?
                    case 'select':
                        $operator = 'equal';
                        break;
                    case 'datepicker':
                        if($filterByValue)//non-empty
                            $filterByValue = $this->reformatFilterDate($filterByValue);
                        $filterByValue .= '%';//empty -> '%'
                        // 29.04.2022, AM, no automatically date_format necessary; override filterByColumn
                        $filterExpr = $originalExpr;
                        break;
                    default:
                        $filterByValue = "%$filterByValue%";
                }
                $filterByColumn = $column['filterByDbColumn'] ?? false ?: $filterExpr;
                $defined_filter[] = [$filterByColumn, $operator, $filterByValue];
            }
            elseif($searchString)//column not filtered -> look for searchString
                array_push($filter, [$filterExpr, 'like', "%$searchString%"], 'or');//add condition, one column must match the searchString
        }
        array_pop($filter);//remove trailing or
        return ($defined_filter && $filter) ?
            array_merge(['('], $filter, [')'], ['and'], $defined_filter) ://both combined
            ($defined_filter ?: $filter);//the only filled one
    }

    /**
     * @param string $filterByValue
     * @return string
     */
    private function reformatFilterDate(string $filterByValue): string
    {
        if(($date = date_parse($filterByValue)) &&
            $date['error_count'] == 0 && $date['warning_count'] == 0 &&
            $date['year'] && $date['month'] && $date['day']) {// is date?
            $format = "%d-%02d-%02d";//y-MM-DD
            if($date['hour'] || $date['minute']) {
                $format .= " %02d:%02d";// hh:mm
                $format .= $date['second'] ? ":%02d" : '';//:ss
            }
            $filterByValue = sprintf($format, $date['year'], $date['month'],
                $date['day'], $date['hour'], $date['minute'], $date['second']);
        }
        else /** Malformed date TODO */ ;
        return $filterByValue;
    }
}