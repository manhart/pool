<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Weblication;
use pool\classes\Database\Commands;
use pool\classes\Database\DAO;
use pool\classes\Database\DataInterface;
use pool\classes\Exception\DAOException;
use pool\classes\translator\Translator;

/**
 * MySQL_DAO
 * @package pool
 * @since 2003/07/10
 */
class MySQL_DAO extends DAO
{
    /**
     * @var string|null Default DataInterface
     */
    protected static ?string $interfaceType = DataInterface::class;

    /**
     * @var string Contains the columns to select
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
    private array $operatorMap = [
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
     * @var array|true[] valid logical operators
     */
    private array $validLogicalOperators = ['or' => true, 'and' => true, 'xor' => true];

    /**
     * Constructor.
     */
    protected function __construct(?DataInterface $DataInterface = null, ?string $databaseName = null, ?string $table = null)
    {
        parent::__construct($DataInterface, $databaseName, $table);
        $this->rebuildColumnList();
    }

    /**
     * Rebuild column list
     * @todo rethink / rework rebuildColumnList
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
     * Set columns for translation into another language
     */
    public function setTranslatedColumns(array $columns): static
    {
        $this->translate = $columns;
        return $this;
    }

    /**
     * Sets columns to be translated
     */
    public function setTranslationColumns(array $columns): void
    {
        $this->translate = $columns;
    }

    /**
     * Enables auto translation of the columns defined in the property $translate
     */
    public function enableTranslation(): static
    {
        $this->translateValues = $this->cache['translatedValues'] ?: $this->translateValues;
        $this->translate = $this->cache['translate'] ?: $this->translate;
        return $this;
    }

    /**
     * Disables auto translation of the columns defined in the property $translate
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
     * Returns the defined columns of the table or rebuilds the column list by fetching the columns from the database
     */
    public function getFieldList(bool $rebuild = false): array
    {
        if(count($this->getColumns()) == 0 or $rebuild) {
            $this->fetchColumns();
        }
        return $this->getColumns();
    }

    /**
     * Fetches the columns automatically from the DataInterface / Driver
     * @see DataInterface::getTableColumnsInfo()
     */
    public function fetchColumns(): static
    {
        [$this->field_list, $columns, $this->pk] = $this->DataInterface->getTableColumnsInfo($this->database, $this->table);
        $this->setColumns(...$columns);
        return $this;
    }

    /**
     * Is called automatically after the columns are set
     */
    public function setColumns(string ...$columns): static
    {
        parent::setColumns(...$columns);
        // Escape each column
        $escapedColumns = array_map([static::class, 'escapeColumn'], $this->columns);
        // Concatenate the columns into a single string
        $this->column_list = implode(', ', $escapedColumns);
        return $this;
    }

    /**
     * Escape column name
     */
    public static function escapeColumn(string $column): string
    {
        if(!str_contains_any($column, ['`', '*', '.', '(', 'as', '\''])) {
            $column = "`$column`";
        }
        return $column;
    }

    /**
     * Liefert den MySQL Datentypen des uebergebenen Feldes
     */
    public function getColumnDataType(string $column): string
    {
        if(!$this->field_list)
            $this->fetchColumns();

        // Loop through each field to find the matching column
        foreach($this->field_list as $field) {
            if($field['COLUMN_NAME'] === $column) {
                $buf = explode(' ', $field['COLUMN_TYPE']);
                $type = $buf[0];
                if(($pos = strpos($type, '(')) !== false) {
                    $type = substr($type, 0, $pos);
                }
                return $type;
            }
        }
        throw new DAOException("Column $column not found in table $this->table");
    }

    /**
     * Returns the column info details
     */
    public function getColumnInfo(string $column): array
    {
        if(!$this->field_list) $this->fetchColumns();
        foreach($this->field_list as $field) {
            if($field['COLUMN_NAME'] == $column) {
                return $field;
            }
        }
        throw new DAOException("Column $column not found in table $this->table");
    }

    /**
     * Get enumerable values from field
     */
    public function getColumnEnumValues(string $column): array
    {
        $fieldInfo = $this->DataInterface->getColumnMetadata($this->database, $this->table, $column);
        if(!isset($fieldInfo['Type'])) return [];
        $type = substr($fieldInfo['Type'], 0, 4);
        if($type != 'enum') return [];
        $buf = substr($fieldInfo['Type'], 5, -1);
        return explode("','", substr($buf, 1, -1));
    }

    /**
     * Set the table alias
     * @todo rethink / rework setTableAlias
     */
    public function setTableAlias($alias): void
    {
        $this->tableAlias = $alias;
    }

    /**
     * Get columns with table alias
     */
    public function getColumnsWithTableAlias(): array
    {
        return array_map(function($val) {
            return "$this->tableAlias.$val";
        }, $this->getColumns());
    }

    /**
     * Insert a new record based on the data passed as an array, with the key corresponding to the column name.
     */
    public function insert(array $data): ResultSet
    {
        $columns = [];
        $values = [];
        foreach($data as $column => $value) {
            // make column
            $columns[] = "`$column`";
            // make value
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
                    $value = $expression($column);
                }
                else {
                    $value = $expression;
                }
            }
            elseif($value instanceof DateTimeInterface) {
                $value = "'{$value->format('Y-m-d H:i:s')}'";
            }
            elseif(!is_int($value) && !is_float($value)) {
                $value = $this->DataInterface->escape($value, $this->database);
                $value = "'$value'";
            }
            $values[] = $value;
        }

        if(!$columns) {
            return (new ResultSet())->addErrorMessage('DAO::insert failed. No columns specified!');
        }
        $columns = implode(',', $columns);
        $values = implode(',', $values);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
INSERT INTO `$this->table`
    ($columns)
VALUES
    ($values)
SQL;
        return $this->execute($sql);
    }

    /**
     * Update a record by primary key (put the primary key in the data array)
     */
    public function update(array $data): ResultSet
    {
        // Check if all primary keys are set in the data array
        $missingKeys = array_diff($this->pk, array_keys($data));
        if (!empty($missingKeys)) {
            return (new ResultSet())->addErrorMessage('Update is wrong. Missing primary keys: ' . implode(', ', $missingKeys));
        }

        $pk = [];
        foreach($this->pk as $key) {
            $pkValue = $data[$key];
            if(is_array($pkValue)) {
                $pk[] = $pkValue[0];
                $data[$key] = $pkValue[1];
            }
            else {
                $pk[] = $pkValue;
                unset($data[$key]);
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
        return $this->execute($sql);
    }

    /**
     * Build assignment list for update statements
     */
    protected function __buildAssignmentList(array $data): string
    {
        $assignments = [];
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
                $value = "'{$this->DataInterface->escape($value, $this->database)}'";
            }
            $assignments[] = "`$field`=$value";
        }
        return implode(', ', $assignments);
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
        $conditions = [];
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
                $conditions[] = "$alias$keyName={$this->escapeWhereConditionValue($id[$i], false, false)}";
                if(!isset($id[$i + 1])) break;
            }
        }
        else {
            $conditions[] = "$alias$key={$this->escapeWhereConditionValue($id, false, false)}";
        }
        return implode(' AND ', $conditions);
    }

    /**
     * Add value to where condition
     */
    private function escapeWhereConditionValue(mixed $value, false|int $noEscape, false|int $noQuotes): int|float|string
    {
        if(is_int($value) || is_float($value))
            return $value;// If the value is not a string that can be directly used in SQL escape and quote it.
        $value = $noEscape ? $value : $this->DataInterface->escape($value, $this->database);
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

        return $this->execute($sql);
    }

    /**
     * Erstellt einen Filter anhand der uebergebenen Regeln. (teils TODO!)
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

        $queryParts = [];
        $firstRule = $filter_rules[0];
        if(!is_array($firstRule) && !isset($this->validLogicalOperators[strtolower($firstRule)]))//1. rule is a non joining operator
            $queryParts[] = $initialOperator;//* we add an initial 'and' operator.

        foreach($filter_rules as $record) {
            $skipAutomaticOperator = $skip_next_operator;
            if($skip_next_operator = !is_array($record)) {//record is a manual operator/SQL-command/parentheses
                $queryParts[] = " $record "; //operator e.g. or, and
                continue;
            }
            elseif(is_array($record[0]))// nesting detected
                $record = "({$this->__buildFilter($record[0], $record[1], true)})";//"($subFilter)"
            else {//normal record
                $field = $this->translateValues ? //get field 'name'
                    $this->translateValues($record[0]) : $record[0];//inject replace command?
                $rawInnerOperator = $record[1];
                $innerOperator = $this->operatorMap[$rawInnerOperator] ?? $rawInnerOperator;//map operators for DBMS
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
            $queryParts[] = !$skipAutomaticOperator ? //automatic operator?
                " $operator $record" : $record;//automation puts operator between the last record and this one
        }
        return implode('', $queryParts);
    }

    /**
     * Check if the column has translation enabled and translate the values
     */
    protected function translateValues(string $column): string
    {
        $tokens = &$this->translateValues[$column];
        if(!Weblication::getInstance()->hasTranslator() || !$tokens)
            return $column;
        $Translator = Weblication::getInstance()->getTranslator();
        $tmp = "case $column";
        foreach($tokens as $token)
            $tmp .= " when '$token' then '{$Translator->getTranslation($token, $token)}'";
        return "$tmp else $column end";
    }

    /**
     * Checks value for sub-query
     *
     * @param mixed $value string?
     * @return bool
     */
    private function __isSubQuery(mixed $value): bool
    {
        return str_contains($value, '(SELECT ');
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int|string|array $id): ResultSet
    {
        $where = $this->__buildWhere($id, $this->pk);
        if($where == '1') {
            throw new DAOException("Delete maybe wrong! Do you really want to delete all records in the table: $this->table");
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
DELETE
FROM `$this->table`
WHERE
    $where
SQL;

        return $this->execute($sql);
    }

    /**
     * Delete multiple records at once
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

        return $this->execute($sql);
    }

    /**
     * Returns a single record e.g. by primary key
     */
    public function get($id, null|string|array $key = null): ResultSet
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

        return $this->execute($sql);
    }

    /**
     * Returns all data records of the assembled SQL statement as a ResultSet
     *
     * @return ResultSet Ergebnismenge
     * @see MySQL_DAO::__buildFilter
     * @see MySQL_DAO::__buildSorting
     * @see MySQL_DAO::__buildLimit
     * @see MySQL_DAO::__buildGroupBy
     * @see ResultSet
     */
    public function getMultiple(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = [], array $sorting = [], array $limit = [],
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

        return $this->execute($sql);
    }

    /**
     * Erstelle Gruppierung fuer das SQL-Statement
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
     * Returns the number of records of the assembled SQL statement as a ResultSet
     *
     * @see ResultSet
     * @see MySQL_DAO::__buildFilter
     */
    public function getCount(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = []): ResultSet
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

        return $this->execute($sql);
    }

    /**
     * Liefert Anzahl betroffener Zeilen (Rows) ohne Limit zurück
     *
     * @return int
     * @throws \Exception
     */
    public function foundRows(): int
    {
        return $this->DataInterface->foundRows();
    }

    /**
     * Fetching row (hook)
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

    /**
     * Gibt die komplette Ergebnismenge im als SQL Insert Anweisungen (String) zurueck.
     *
     * @param string $table
     * @param \ResultSet $ResultSet
     * @return string
     * @todo Rethink this method
     */
    public function getSQLInserts(string $table, ResultSet $ResultSet): string
    {
        $sql = '';

        if(!$ResultSet->count()) {
            return '';
        }
        $rowSet = $ResultSet->getRowSet();
        foreach($rowSet as $row) {
            $sql .= 'INSERT INTO '.$table.' (';
            $sql .= implode(',', array_keys($rowSet[0]));
            $sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'.chr(10);
        }
        return $sql;
    }
}