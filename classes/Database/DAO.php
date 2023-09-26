<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database;

use Closure;
use CustomMySQL_DAO;
use DateTimeInterface;
use pool\classes\Core\PoolObject;
use pool\classes\Core\RecordSet;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\Database\Exception\DatabaseConnectionException;
use pool\classes\Exception\DAOException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use function addEndingSlash;
use function array_diff;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function bool2string;
use function chr;
use function class_exists;
use function count;
use function explode;
use function file_exists;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function str_contains_any;
use function strtolower;

/**
 * Class DAO - Data Access Object
 *
 * @package pool\classes\Database
 * @since 2003/07/10
 */
abstract class DAO extends PoolObject implements IDatabaseAccess, \Stringable
{
    /**
     * don't quote the value in the (sql) query
     */
    public const DAO_NO_QUOTES = 1;
    /**
     * don't escape the value in the (sql) query
     */
    public const DAO_NO_ESCAPE = 2;

    /**
     * @var string|null Name of the table / file / view (must be declared in derived class)
     */
    protected static ?string $tableName = null;

    /**
     * @var string|null Name of the database (must be declared in derived class)
     */
    protected static ?string $databaseName = null;

    /**
     * @var string Internal Name of the table
     */
    protected readonly string $table;

    /**
     * @var string Internal Name of the database or alias
     */
    protected string $database;

    /**
     * @var string Quoted table name
     */
    protected readonly string $quotedTable;

    /**
     * @var array Table meta data
     */
    protected array $metaData = [];

    /**
     * @var array|string[] Primary key of table
     */
    protected array $pk = [];

    /**
     * @var array|string[] Columns of table
     */
    protected array $columns = [];

    /**
     * @var array<string, string|Closure> overwrite this array in the constructor to create the commands needed for the database.
     * @see Commands
     */
    protected array $commands;

    /**
     * Columns in detailed form (siehe MySQL: SHOW COLUMNS)
     *
     * @var array
     */
    protected array $field_list = [];

    /**
     * @var array|true[] valid logical operators
     */
    protected array $validLogicalOperators = ['or' => true, 'and' => true, 'xor' => true];

    /**
     * Translates field values within filter / sorting methods
     *
     * @var array|string[][]
     */
    protected array $translateValues = [];

    /**
     * @var array|string[] operators for the filter method
     */
    protected array $operatorMap = [
        'equal' => '=',
        'unequal' => '!=',
        'greater' => '>',
        'greater than' => '>=',
        'less' => '<',
        'less than' => '<=',
        'in' => 'in',
        'not in' => 'not in',
        'is' => 'is',
        'like' => 'like',
    ];

    protected string $tableAlias = '';

    /**
     * @var string Contains the columns to select
     */
    protected string $column_list = '*';

    /**
     * @var array|string[] Contains the opening and closing characters for escaping column and table names
     */
    protected array $symbolQuote = ['`', '`'];

    /**
     * @var string Dummy always true where clause
     */
    protected string $dummyWhere = '1=1';

    private string $quotedTableAlias = '';

    /**
     * @var array|string[] Contains the characters that do not need to be escaped
     */
    private array $nonWrapSymbols = ['*', '.', '(', 'as', '\''];

    /**
     * Defines the default commands.
     */
    protected function __construct(?string $databaseAlias = null, ?string $table = null)
    {
        $this->database ??= $databaseAlias ?? static::$databaseName ?:
            throw new InvalidArgumentException('The static property databaseName is not defined within DAO '.static::class.'!');
        $this->table ??= $table ?? static::$tableName ?:
            throw new InvalidArgumentException('The static property tableName is not defined within DAO '.static::class.'!');

        $this->quotedTable = $this->wrapSymbols($this->table);
        $this->commands = $this->createCommands();
        $this->nonWrapSymbols = array_merge($this->symbolQuote, $this->nonWrapSymbols);
    }

    /**
     * Wrap a column or table name with backticks
     */
    protected function wrapSymbols(string $string): string
    {
        return $string ? "{$this->symbolQuote[0]}$string{$this->symbolQuote[1]}" : '';
    }

    /**
     * @return array
     */
    private function createCommands(): array
    {
        return [
            Commands::Now->name => 'NOW()',
            Commands::CurrentDate->name => 'CURRENT_DATE()',
            Commands::CurrentTimestamp->name => 'CURRENT_TIMESTAMP()',
            Commands::CurrentTimestampUs6->name => 'CURRENT_TIMESTAMP(6)',
            Commands::Increase->name => fn($field) => "$field+1",
            Commands::Decrease->name => fn($field) => "$field-1",
            Commands::Reset->name => fn($field) => "DEFAULT($field)",
        ];
    }

    /**
     * Creates a Data Access Object
     */
    public static function create(?string $tableName = null, ?string $databaseName = null): static
    {
        // class stuff
        if(!$tableName) {
            return new static($databaseName);
        }

        if(static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }

        $DAO = new static($databaseName, $tableName);
        $DAO->fetchColumns();
        return $DAO;
    }

    /**
     * Fetches the columns automatically from the DataInterface / Driver
     *
     * @throws InvalidArgumentException|DatabaseConnectionException|RuntimeException|\Exception
     * @see DataInterface::getTableColumnsInfo()
     */
    public function fetchColumns(): static
    {
        [$this->field_list, $columns, $this->pk] = $this->getDataInterface()->getTableColumnsInfo($this->database, $this->table);
        $this->setColumns(...$columns);
        return $this;
    }

    /**
     * Return DataInterface
     *
     * @return DataInterface
     */
    public function getDataInterface(): DataInterface
    {
        return DataInterface::getInterfaceForResource($this->getDatabase());
    }

    /**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Erzeugt ein Data Access Object (anhand einer Tabellendefinition)
     *
     * @param string|null $tableName table definition or the table name
     * @param string|null $databaseAlias database name
     * @return DAO Data Access Object (edited DAO->pool\classes\Database\DAO\MySQL_DAO f�r ZDE)
     * @deprecated use create() instead
     * @see DAO::create()
     */
    public static function createDAO(?string $tableName = null, ?string $databaseAlias = null): static
    {
        // @todo remove workaround once relying projects are fixed
        if($tableName && !$databaseAlias && str_contains($tableName, '_')) {
            [$databaseAlias, $tableName] = explode('_', $tableName, 2);
        }

        // class stuff
        if(!$tableName) {
            return new static();
        }

        if(static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }

        // workaround
        $className = static::class === __CLASS__ ? CustomMySQL_DAO::class : static::class;

        $class_exists = class_exists($tableName, false);

        $driver = DataInterface::getInterfaceForResource($databaseAlias)->getDriverName();
        $databaseName = DataInterface::getDatabaseForResource($databaseAlias);
        $dir = addEndingSlash(DIR_DAOS_ROOT)."$driver/$databaseName";
        $include = "$dir/$tableName.php";
        $file_exists = file_exists($include);
        if(!$class_exists && $file_exists) {
            require_once $include;
            $class_exists = true;
        }
        if($class_exists) {
            return new $tableName($databaseAlias, $tableName);
        }

        $DAO = new $className($databaseAlias, $tableName);
        $DAO->fetchColumns();
        return $DAO;
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
        array $limit = [], array $groupBy = [], array $having = [], array $options = []): RecordSet
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
FROM $this->quotedTable
WHERE
    $where $filter
$groupByClause
$havingClause
$sortingClause
$limitClause
SQL;
        return $this->execute($sql);
    }

    /**
     * Build where condition
     */
    protected function buildWhere(null|int|string|array $id, null|string|array $key): string
    {
        $conditions = [];
        if(is_null($id)) {
            return $this->dummyWhere;
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
    protected function escapeWhereConditionValue(mixed $value, false|int $noEscape, false|int $noQuotes): int|float|string
    {
        if(is_int($value) || is_float($value)) {
            return $value;
        }// If the value is not a string that can be directly used in SQL escape and quote it.
        $value = $noEscape ? $value : $this->escapeSQL($value);
        return $noQuotes ? $value : "'$value'"; //quote
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
            return $skip_next_operator ? $this->dummyWhere : '';
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
                $noQuotes = $quoteSettings & self::DAO_NO_QUOTES;
                $noEscape = $quoteSettings & self::DAO_NO_ESCAPE;
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
                else {
                    $value = match (gettype($values)) {//handle by type
                        'NULL' => 'NULL',
                        'boolean' => bool2string($values),
                        'double', 'integer' => $values,//float and int
                        default => match ($values instanceof SqlStatement) {
                            true => $values->getStatement(),
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
            $sql .= "$alias$column $sort";
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
     * @param array $sorting sorting format ['column1' => 'ASC', 'column2' => 'DESC']
     * @return string ORDER BY statement
     */
    protected function buildSorting(array $sorting): string
    {
        if(!count($sorting)) {
            return '';
        }
        $sql = '';
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
     * Executes sql statement and returns RecordSet
     *
     * @param string $sql sql statement to execute
     * @param callable|null $customCallback
     * @return RecordSet
     * @throws \mysqli_sql_exception
     * @throws InvalidArgumentException
     */
    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        return DataInterface::execute($sql, $this->database, $customCallback ?: [$this, 'fetchingRow'], $this->metaData);
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set primary key
     */
    public function setPrimaryKey(string ...$primaryKey): static
    {
        $this->pk = $primaryKey;
        return $this;
    }

    /**
     * Returns primary key
     *
     * @return array primary key
     */
    public function getPrimaryKey(): array
    {
        return $this->pk;
    }

    /**
     * Setzt die Spalten, die abgefragt werden.
     *
     * @param string $columns columns as string with separator
     * @param string $separator Trenner (Spaltentrenner im String)
     **/
    public function setColumnsAsString(string $columns, string $separator = ';'): static
    {
        $this->setColumns(...explode($separator, $columns));
        return $this;
    }

    /**
     * Set columns as array
     */
    public function setColumnsAsArray(array $columns): static
    {
        $this->setColumns(...$columns);
        return $this;
    }

    /**
     * Returns the columns you want to query.
     */
    public function getColumns(): array
    {
        if(!$this->columns) {
            $this->fetchColumns();
        }
        return $this->columns;
    }

    /**
     * Sets the columns you want to query.
     */
    public function setColumns(string ...$columns): static
    {
        $this->columns = $columns;
        // Escape each column
        $escapedColumns = array_map([$this, 'wrapColumn'], $this->columns);
        // Concatenate the columns into a single string
        $this->column_list = implode(', ', $escapedColumns);
        return $this;
    }

    /**
     * Returns the metadata of the table
     *
     * @param string $which
     * @return array
     */
    public function getMetaData(string $which = ''): array
    {
        return $which ? $this->metaData[$which] : $this->metaData;
    }

    /**
     * Fetching row is a hook that goes through all the retrieved rows. Can be used to modify the row (column content) before it is returned.
     */
    public function fetchingRow(array $row): array
    {
        return $row;
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
FROM $this->quotedTable
WHERE
    $where
SQL;
        return $this->execute($sql);
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int|string|array $id): RecordSet
    {
        $where = $this->buildWhere($id, $this->pk);
        if($where === $this->dummyWhere) {
            throw new DAOException("Delete maybe wrong! Do you really want to delete all records in the table: $this->table");
        }
        /** @noinspection SqlResolve */
        $sql = <<<SQL
DELETE
FROM $this->quotedTable
WHERE
    $where
SQL;
        return $this->execute($sql);
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
            $columns[] = $this->wrapSymbols($column);
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
INSERT INTO $this->quotedTable
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
        if($where === $this->dummyWhere) {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
UPDATE $this->quotedTable
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
     * Returns the SQL statement for inserting the data into the table
     *
     * @todo Rethink this method
     */
    public function getSQLInserts(RecordSet $ResultSet): string
    {
        if(!$ResultSet->count()) {
            return '';
        }
        $sql = '';
        foreach($ResultSet as $row) {
            $sql .= "INSERT INTO $this->quotedTable (";
            $sql .= implode(',', array_keys($row));
            $sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'.chr(10);
        }
        return $sql;
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
        if($where === $this->dummyWhere) {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
UPDATE $this->quotedTable
SET
    $set
WHERE
    $where
SQL;
        return $this->execute($sql);
    }

    /**
     * Returns the number of affected rows with no limit
     * Warning: When used after a CALL statement, this function returns the number of rows selected by the last query in the procedure, not by the whole
     * procedure. Attention: Statements using the FOUND_ROWS() function are not safe for replication.
     */
    public function foundRows(): int
    {
        return $this->getDataInterface()->foundRows($this->database);
    }

    /**
     * Set the table alias
     *
     * @todo rethink / rework setTableAlias
     */
    public function setTableAlias($alias): void
    {
        $this->tableAlias = $alias;
        $this->quotedTableAlias = $this->wrapSymbols($alias);
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
FROM $this->quotedTable
WHERE
    $where
SQL;
        return $this->execute($sql);
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
        $count = $this->wrapSymbols('count');
        $sql = <<<SQL
SELECT COUNT(*) AS $count
FROM $this->quotedTable$this->quotedTableAlias
WHERE
    $where
    $filter
SQL;
        return $this->execute($sql);
    }

    /**
     * Quote column name
     */
    public function wrapColumn(string $column): string
    {
        if($this->shouldWrapColumn($column)) {
            $column = $this->wrapSymbols($column);
        }
        return $column;
    }

    /**
     * Checks if column name should be wrapped
     */
    protected function shouldWrapColumn(string $column): bool
    {
        return !str_contains_any($column, $this->nonWrapSymbols);
    }

    /**
     * @return string Returns the database and table name in the format `database`.`table`
     */
    public function __toString(): string
    {
        return "{$this->wrapSymbols(self::getDatabaseName())}.{$this->wrapSymbols(self::getTableName())}";
    }

    /**
     * @return string
     */
    public static function getDatabaseName(): string
    {
        return static::$databaseName;
    }

    /**
     * @return string
     */
    public static function getTableName(): string
    {
        return static::$tableName;
    }
}