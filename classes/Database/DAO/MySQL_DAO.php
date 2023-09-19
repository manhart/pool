<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database\DAO;

use Closure;
use DateTimeInterface;
use pool\classes\Core\RecordSet;
use pool\classes\Core\Weblication;
use pool\classes\Database\Commands;
use pool\classes\Database\DAO;
use pool\classes\Database\DataInterface;
use pool\classes\Exception\DAOException;
use pool\classes\translator\Translator;
use function array_diff;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
use function array_values;
use function bool2string;
use function chr;
use function count;
use function date_parse;
use function explode;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function is_string;
use function sprintf;
use function str_contains_any;
use function strpos;
use function strtolower;
use function substr;

/**
 * pool\classes\Database\DAO\MySQL_DAO
 *
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
        'is' => 'is',
    ];

    /**
     * @var array
     */
    private array $cache = [
        'translatedValues' => [],
        'translate' => [],
    ];

    /**
     * @var array|true[] valid logical operators
     */
    private array $validLogicalOperators = ['or' => true, 'and' => true, 'xor' => true];

    /**
     * Constructor.
     */
    protected function __construct(?string $databaseName = null, ?string $table = null)
    {
        parent::__construct($databaseName, $table);
        $this->rebuildColumnList();
    }

    /**
     * Rebuild column list
     *
     * @todo rethink / rework rebuildColumnList
     */
    private function rebuildColumnList(): void
    {
        // Columns are predefined as property "columns".
        if(!$this->columns) {
            return;
        }

        $table = "`$this->table`";
        $glue = "`, $table.`";
        $this->column_list = $table.'.`'.implode($glue, $this->getColumns()).'`';
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
        if($rebuild || !count($this->getColumns())) {
            $this->fetchColumns();
        }
        return $this->getColumns();
    }

    /**
     * Fetches the columns automatically from the DataInterface / Driver
     *
     * @throws \pool\classes\Exception\InvalidArgumentException|\pool\classes\Database\Exception\DatabaseConnectionException|\pool\classes\Exception\RuntimeException|\Exception
     * @see DataInterface::getTableColumnsInfo()
     */
    public function fetchColumns(): static
    {
        [$this->field_list, $columns, $this->pk] = $this->getDataInterface()->getTableColumnsInfo($this->database, $this->table);
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
     * Returns the data type of the column
     */
    public function getColumnDataType(string $column): string
    {
        if(!$this->field_list) {
            $this->fetchColumns();
        }

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
        if(!$this->field_list) {
            $this->fetchColumns();
        }
        foreach($this->field_list as $field) {
            if($field['COLUMN_NAME'] === $column) {
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
        $fieldInfo = $this->getDataInterface()->getColumnMetadata($this->database, $this->table, $column);
        if(!isset($fieldInfo['Type'])) {
            return [];
        }
        $type = substr($fieldInfo['Type'], 0, 4);
        if($type !== 'enum') {
            return [];
        }
        $buf = substr($fieldInfo['Type'], 5, -1);
        return explode("','", substr($buf, 1, -1));
    }

    /**
     * Set the table alias
     *
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
    public function insert(array $data): RecordSet
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
                $value = $this->escapeSQL($value);
                $value = "'$value'";
            }
            $values[] = $value;
        }

        if(!$columns) {
            return (new RecordSet())->addErrorMessage('DAO::insert failed. No columns specified!');
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
    public function update(array $data): RecordSet
    {
        // Check if all primary keys are set in the data array
        $missingKeys = array_diff($this->pk, array_keys($data));
        if(!empty($missingKeys)) {
            return (new RecordSet())->addErrorMessage('Update is wrong. Missing primary keys: '.implode(', ', $missingKeys));
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

        $set = $this->buildAssignmentList($data);

        if(!$set) {
            return new RecordSet();
        }

        $where = $this->buildWhere($pk, $this->pk);
        if($where === '1') {
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
    protected function buildAssignmentList(array $data): string
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
                $value = "'{$this->escapeSQL($value)}'";
            }
            $assignments[] = "`$field`=$value";
        }
        return implode(', ', $assignments);
    }

    /**
     * Build where condition
     */
    protected function buildWhere(null|int|string|array $id, null|string|array $key): string
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
                $id = [$id];
            }
            $count = count($key);
            for($i = 0; $i < $count; $i++) {
                $keyName = $key[$i];
                $conditions[] = "$alias$keyName={$this->escapeWhereConditionValue($id[$i], false, false)}";
                if(!isset($id[$i + 1])) {
                    break;
                }
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
        if(is_int($value) || is_float($value)) {
            return $value;
        }// If the value is not a string that can be directly used in SQL escape and quote it.
        $value = $noEscape ? $value : $this->escapeSQL($value);
        return $noQuotes ? $value : "'$value'"; //quote
    }

    /**
     * Update multiple records at once
     *
     * @param array $data
     * @param array $filter_rules
     * @return RecordSet
     */
    public function updateMultiple(array $data, array $filter_rules): RecordSet
    {
        $set = $this->buildAssignmentList($data);
        if(!$set) {
            return new RecordSet();
        }

        $where = $this->buildFilter($filter_rules, 'and', true);
        if($where === '1') {
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
     * Build complex conditions for where or on clauses
     *
     * @param array $filter_rules Filter rules in following format [[columnName, operator, value], ...]
     * @param string $operator Logical operator for combining all conditions
     * @param boolean $skip_next_operator false skips first logical operator
     * @param string $initialOperator Initial logical operator, if first rule is not an array and not a logical operator
     * @return string conditions for where clause
     * @see MySQL_DAO::$operatorMap
     */
    protected function buildFilter(array $filter_rules, string $operator = 'and', bool $skip_next_operator = false,
        string $initialOperator = ' and'): string
    {
        if(!$filter_rules) {//not filter anything (terminate floating operators)
            return $skip_next_operator ? '1' : '';
        }

        $queryParts = [];
        $firstRule = $filter_rules[0];
        if(!is_array($firstRule) && !isset($this->validLogicalOperators[strtolower($firstRule)])) {//1. rule is a non joining operator
            $queryParts[] = $initialOperator;
        }//* we add an initial 'and' operator.

        foreach($filter_rules as $record) {
            $skipAutomaticOperator = $skip_next_operator;
            if($skip_next_operator = !is_array($record)) {//record is a manual operator/SQL-command/parentheses
                $queryParts[] = " $record "; //operator e.g. or, and
                continue;
            }

            if(is_array($record[0])) {// nesting detected
                $record = "({$this->buildFilter($record[0], $record[1], true)})";
            }
            //"($subFilter)"
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
                if(is_array($values)) {
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
                else {//sub query moved to escapeWhereConditionValue
                    $value = match (gettype($values)) {//handle by type
                        'NULL' => 'NULL',
                        'boolean' => bool2string($values),
                        'double', 'integer' => $values,//float and int
                        default => match ($this->isSubQuery($values)) {// TODO fix insecure sub-query check
                            true => $values,
                            default => $this->escapeWhereConditionValue($values, $noEscape, $noQuotes),
                        }
                    };
                }
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
        if(!$tokens || !Weblication::getInstance()->hasTranslator()) {
            return $column;
        }
        $Translator = Weblication::getInstance()->getTranslator();
        $tmp = "CASE $column";
        foreach($tokens as $token) {
            $tmp .= " WHEN '$token' THEN '{$Translator->getTranslation($token, $token)}'";
        }
        return "$tmp ELSE $column END";
    }

    /**
     * Checks value for sub-query
     *
     * @param mixed $value string?
     * @return bool
     */
    private function isSubQuery(mixed $value): bool
    {
        return str_contains($value, '(SELECT ');
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int|string|array $id): RecordSet
    {
        $where = $this->buildWhere($id, $this->pk);
        if($where === '1') {
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
    public function deleteMultiple(array $filter_rules = []): RecordSet
    {
        $where = $this->buildFilter($filter_rules, 'and', true);
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
     *
     * @see \MySQL_DAO::buildWhere
     */
    public function get($id, null|string|array $key = null): RecordSet
    {
        $id = $id ?? 0;
        $where = $this->buildWhere($id, $key);

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
     * Returns all data records of the assembled SQL statement as a pool\classes\Core\ResultSet
     *
     * @see \MySQL_DAO::buildWhere
     * @see MySQL_DAO::buildFilter
     * @see MySQL_DAO::buildGroupBy
     * @see MySQL_DAO::buildHaving
     * @see MySQL_DAO::buildSorting
     * @see MySQL_DAO::buildLimit
     */
    public function getMultiple(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = [], array $sorting = [],
        array $limit = [],
        array $groupBy = [], array $having = [], array $options = []): RecordSet
    {
        $optionsStr = implode(' ', $options);

        $where = $this->buildWhere($id, $key);
        $filter = $this->buildFilter($filter_rules);
        $groupByClause = $this->buildGroupBy($groupBy);
        $havingClause = $this->buildHaving($having);
        $sortingClause = $this->buildSorting($sorting);
        $limitClause = $this->buildLimit($limit);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
SELECT $optionsStr $this->column_list
FROM `$this->table`
WHERE
    $where
    $filter
$groupByClause
$havingClause
$sortingClause
$limitClause
SQL;
        return $this->execute($sql);
    }

    /**
     * Build a group by statement for a SQL query
     *
     * @param array $groupBy Group by columns with sort order in following format ['column1' => 'ASC', 'column2' => 'DESC']. With rollup is also possible,
     *     e.g. ['column1' => 'ASC', 'WITH ROLLUP']
     * @return string GROUP BY statement
     */
    protected function buildGroupBy(array $groupBy): string
    {
        if(!$groupBy) {
            return '';
        }

        $sql = '';
        $alias = '';
        if($this->tableAlias) {
            $alias = $this->tableAlias.'.';
        }

        foreach($groupBy as $column => $sort) {
            if($sql === '') {
                $sql = ' GROUP BY ';
            }
            elseif($column === 'WITH ROLLUP') {
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
     * @param array $filter_rules Filter rules in following format [[columnName, operator, value], ...]
     * @return string HAVING statement
     */
    protected function buildHaving(array $filter_rules): string
    {
        $query = $this->buildFilter($filter_rules, 'and', false, '');
        if($query) {
            $query = " HAVING $query";
        }
        return $query;
    }

    /**
     * Build a sorting statement for a SQL query
     *
     * @param null|array $sorting sorting format ['column1' => 'ASC', 'column2' => 'DESC']
     * @return string ORDER BY statement
     */
    protected function buildSorting(?array $sorting): string
    {
        $sql = '';
        if(is_array($sorting) && count($sorting)) {
            $alias = $this->tableAlias ? "$this->tableAlias." : '';

            foreach($sorting as $column => $sort) {
                if($sql === '') {
                    $sql = ' ORDER BY ';
                }
                else {
                    $sql .= ', ';
                }

                $column = $alias.$column;
                if($this->translateValues) {
                    $column = $this->translateValues($column);
                }
                $sql .= "$column $sort";
            }
        }
        return $sql;
    }

    /**
     * Build a LIMIT statement for a SQL query
     *
     * @param array $limit LIMIT with format [offset, length]
     * @return string LIMIT statement
     */
    protected function buildLimit(array $limit): string
    {
        return $limit ? ' LIMIT '.implode(', ', $limit) : '';
    }

    /**
     * Returns the number of records of the assembled SQL statement as a pool\classes\Core\ResultSet
     *
     * @see RecordSet
     * @see MySQL_DAO::buildFilter
     */
    public function getCount(null|int|string|array $id = null, null|string|array $key = null, array $filter_rules = []): RecordSet
    {
        $where = $this->buildWhere($id, $key);
        $filter = $this->buildFilter($filter_rules);
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
     * Returns the number of affected rows with no limit
     */
    public function foundRows(): int
    {
        return $this->getDataInterface()->foundRows($this->database);
    }

    /**
     * Fetching row is a hook that goes through all the retrieved rows. Can be used to modify the row (column content) before it is returned.
     */
    public function fetchingRow(array $row): array
    {
        return $this->translate ? $this->translate($row) : $row;
    }

    /**
     * Translate column content
     */
    protected function translate(array $row): array
    {
        $Weblication = Weblication::getInstance();
        if(!$Weblication->hasTranslator()) {//no translator
            return $row;
        }//unchanged
        $Translator = $Weblication->getTranslator();
        // another idea to handle columns which should be translated
        // $translationKey = "columnNames.{$this->getTableName()}.{$row[$key]}";
        foreach($this->translate as $key) {
            if(isset($row[$key]) && is_string($row[$key])) {
                $row[$key] = $Translator->getTranslation($row[$key], $row[$key], noAlter: true);
            }
        }
        return $row;
    }

    /**
     * Make filter rules based on search string or defined search keywords
     */
    public function makeFilter(array $columns = [], string $searchString = '', array $definedSearchKeywords = []): array
    {
        if(!$searchString && !$definedSearchKeywords) {
            return [];
        }
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
                        if($filterByValue) {//non-empty
                            $filterByValue = $this->reformatFilterDate($filterByValue);
                        }
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
            elseif($searchString) {//column not filtered -> look for searchString
                array_push($filter, [$filterExpr, 'like', "%$searchString%"], 'or');
            }//add condition, one column must match the searchString
        }
        array_pop($filter);//remove trailing or
        return ($defined_filter && $filter) ?
            array_merge(['('], $filter, [')'], ['and'], $defined_filter) ://both combined
            ($defined_filter ?: $filter);//the only filled one
    }

    /**
     * Reformat date for filter
     */
    private function reformatFilterDate(string $dateValue): string
    {
        if(($date = date_parse($dateValue)) &&
            $date['error_count'] === 0 && $date['warning_count'] === 0 &&
            $date['year'] && $date['month'] && $date['day']) {// is date?
            $format = "%d-%02d-%02d";//y-MM-DD
            if($date['hour'] || $date['minute']) {
                $format .= " %02d:%02d";// hh:mm
                $format .= $date['second'] ? ":%02d" : '';//:ss
            }
            $dateValue = sprintf($format, $date['year'], $date['month'],
                $date['day'], $date['hour'], $date['minute'], $date['second']);
        }
        /** Malformed date TODO */
        return $dateValue;
    }

    /**
     * Returns the SQL statement for inserting the data into the table
     *
     * @todo Rethink this method
     */
    public function getSQLInserts(string $table, RecordSet $ResultSet): string
    {
        $sql = '';

        if(!$ResultSet->count()) {
            return '';
        }
        $rowSet = $ResultSet->getRaw();
        foreach($rowSet as $row) {
            $sql .= 'INSERT INTO '.$table.' (';
            $sql .= implode(',', array_keys($rowSet[0]));
            $sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'.chr(10);
        }
        return $sql;
    }

    /**
     * @param mixed $value
     * @return string
     * @throws \Exception
     */
    public function escapeSQL(mixed $value): string
    {
        return $this->getDataInterface()->escape($value, $this->database);
    }
}