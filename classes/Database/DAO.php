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
use JetBrains\PhpStorm\Pure;
use mysqli_sql_exception;
use pool\classes\Core\PoolObject;
use pool\classes\Core\RecordSet;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\Database\Exception\DatabaseConnectionException;
use pool\classes\Exception\DAOException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use Stringable;

use function addEndingSlash;
use function array_diff;
use function array_flip;
use function array_keys;
use function array_map;
use function array_merge;
use function array_values;
use function bool2string;
use function chr;
use function class_exists;
use function count;
use function date;
use function explode;
use function file_exists;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function strtolower;

/**
 * Class DAO - Data Access Object
 *
 * @package pool\classes\Database
 * @since 2003/07/10
 */
abstract class DAO extends PoolObject implements DatabaseAccessObjectInterface, Stringable
{
    /**
     * don't quote the value in the (sql) query
     */
    public const int DAO_NO_QUOTES = 1;
    /**
     * don't escape the value in the (sql) query
     */
    public const int DAO_NO_ESCAPE = 2;
    /**
     * Data types
     */
    public const int STRING = 1;
    public const int INT = 2;
    public const int FLOAT = 3;

    /**
     * @var string|null Name of the table / file / view (must be declared in derived class)
     */
    protected static ?string $tableName = null;

    /**
     * @var string|null Name of the schema (must be declared in derived class if available)
     */
    protected static ?string $schemaName = null;

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
     * @var string Quoted database name
     */
    protected readonly string $quotedDatabase;

    /**
     * @var string Quoted schema name
     */
    protected readonly string $quotedSchema;

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
     * @var array|string[] Escaped columns of table
     */
    protected array $escapedColumns = [];

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

    protected string $quotedTableAlias = '';

    protected bool $throwsOnError = false;

    private array|false|null $defaultColumns;

    /**
     * @var array|string[] Contains the characters that do not need to be escaped
     */
    private array $nonWrapSymbols = ['*', '.', '(', ' as ', '\''];

    /**
     * @var array|int[]|string[] It is used to store the flipped non-wrap symbols for performance reasons.
     */
    private array $preCalculatedNonWrapSymbols;

    /**
     * @var array $formatter An array used for storing formatter functions for columns.
     */
    private array $formatter = [];

    /**
     * Defines the default commands.
     */
    protected function __construct(?string $databaseAlias = null, ?string $table = null)
    {
        $this->database ??= $databaseAlias ?? static::$databaseName ?:
            throw new InvalidArgumentException('The static property databaseName is not defined within DAO '.static::class.'!');
        $this->table ??= $table ?? static::$tableName ?:
            throw new InvalidArgumentException('The static property tableName is not defined within DAO '.static::class.'!');

        $this->quotedDatabase = $this->wrapSymbols(static::$databaseName ?: DataInterface::getDatabaseForResource($this->database));
        $this->quotedSchema = $this->wrapSymbols(static::$schemaName ?? '');
        $this->quotedTable = $this->wrapSymbols($this->table);
        $this->commands = $this->createCommands();
        $this->preCalculatedNonWrapSymbols = array_flip(array_merge($this->symbolQuote, $this->nonWrapSymbols));
    }

    /**
     * Wrap a column or table name with backticks
     */
    protected function wrapSymbols(string $string): string
    {
        return $string ? "{$this->symbolQuote[0]}$string{$this->symbolQuote[1]}" : '';
    }

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
            Commands::Self->name => fn($field) => $field,
        ];
    }

    /**
     * Creates a Data Access Object with the given columns
     */
    public static function createWithColumns(string ...$columns): static
    {
        $DAO = static::create(null, null, true);
        $DAO->setColumns(...$columns);
        return $DAO;
    }

    #[Pure]
    /**
     * Creates a Data Access Object
     */
    final public static function create(?string $tableName = null, ?string $databaseName = null, bool $throws = false): static
    {
        // class stuff
        if (!$tableName) {
            $DAO = new static($databaseName);
            $DAO->throwsOnError = $throws;
            return $DAO;
        }

        if (static::$tableName) {
            throw new DAOException("Fatal error: You can't use the static property \$tableName and the \$tableDefine parameter at the same time!", 2);
        }

        $DAO = new static($databaseName, $tableName);
        $DAO->throwsOnError = $throws;
        $DAO->fetchColumns();
        return $DAO;
    }

    /**
     * Fetches the columns automatically from the DataInterface / Driver
     *
     * @throws InvalidArgumentException|DatabaseConnectionException|RuntimeException|
     * @see DataInterface::getTableColumnsInfo()
     */
    public function fetchColumns(): static
    {
        $columns = $this->fetchColumnsList();
        $this->setColumns(...$columns);
        return $this;
    }

    private function fetchColumnsList(): array
    {
        [$this->field_list, $columns, $this->pk] = $this->getDataInterface()->getTableColumnsInfo($this->database, $this->table);
        return $columns;
    }

    /**
     * Return DataInterface
     */
    public function getDataInterface(): DataInterface
    {
        return DataInterface::getInterfaceForResource($this->getDatabase());
    }

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
     * @throws DAOException|
     * @see DAO::create()
     * @deprecated use create() instead
     */
    public static function createDAO(?string $tableName = null, ?string $databaseAlias = null): static
    {
        // @todo remove workaround once relying projects are fixed
        if ($tableName && !$databaseAlias && str_contains($tableName, '_')) {
            [$databaseAlias, $tableName] = explode('_', $tableName, 2);
        }

        // class stuff
        if (!$tableName) {
            return new static();
        }

        if (static::$tableName) {
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
        if (!$class_exists && $file_exists) {
            require_once $include;
            $class_exists = true;
        }
        if ($class_exists) {
            return new $tableName($databaseAlias, $tableName);
        }

        $DAO = new $className($databaseAlias, $tableName);
        $DAO->fetchColumns();
        return $DAO;
    }

    public static function getDatabaseName(): string
    {
        return static::$databaseName;
    }

    public static function getTableName(): string
    {
        return static::$tableName;
    }

    #[Pure]
    public static function castValue(mixed $value, mixed $targetType): mixed
    {
        return match ($targetType) {
            self::STRING => (string)$value,
            self::INT => (int)$value,
            self::FLOAT => (float)$value,
            default => $value,
        };
    }

    /**
     * Shorthand for fetching one or multiple values of a record
     *
     * @see self::fetchData()
     */
    public static function fetchDataStatic($pk, ...$fields)
    {
        return static::create(throws: true)->fetchData($pk, ...$fields);
    }

    /**
     * Shorthand for fetching one or multiple values of a record
     *
     * @param array|int|string $pk a unique identifier use an array [$pk, $column] to specify the primary key column or search field. $pk and $column each can also be a list as is
     *     usual with DAO::get()
     * @param mixed ...$fields a list of columns to retrieve if omitted will return the associated primary key (useful for reverse lookup)
     * @return array|mixed the result, returns a list if multiple columns were queried should there be no matching record returns null or an empty list respectively
     * @see static::get()
     */
    public function fetchData(array|int|string $pk, ...$fields): mixed
    {
        $fields = $fields ?: $this->getPrimaryKey();
        if (!array_is_list($fields)) {//cast instructions exist
            $casts = array_values($fields);
            $fields = array_filter($fields, is_string(...)) + array_filter(array_keys($fields), is_string(...));//screen out instructions and listify fields
        }
        $record = $this->setColumns(...$fields)->get(...(array)$pk)->getRecord();
        $record = isset($casts) && $record ? array_map($this->castValue(...), $record, $casts) : array_values($record);//unwrap values
        return count($fields) === 1 ? $record[0] ?? null : $record;
    }

    /**
     * Returns primary key
     */
    public function getPrimaryKey(): array
    {
        return $this->pk;
    }

    /**
     * Returns a single record e.g. by primary key
     *
     * @see \MySQL_DAO::buildWhere
     */
    public function get(null|int|string|array $id, null|string|array $key = null): RecordSet
    {
        $where = $this->buildWhere($id ?? 0, $key);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            SELECT $this->column_list
            FROM $this
            WHERE
                $where
            SQL;
        return $this->execute($sql);
    }

    /**
     * Shorthand for current date()
     */
    final public static function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
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
    public function getMultiple(
        null|int|string|array $id = null,
        null|string|array $key = null,
        array $filter = [],
        array $sorting = [],
        array $limit = [],
        array $groupBy = [],
        array $having = [],
        array $options = [],
    ): RecordSet {
        $optionsStr = implode(' ', $options);

        $whereClause = $this->buildWhere($id, $key).$this->buildFilter($filter);
        $groupByClause = $this->buildGroupBy($groupBy);
        $havingClause = $this->buildHaving($having);
        $sortingClause = $this->buildSorting($sorting);
        $limitClause = $this->buildLimit($limit);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            SELECT $optionsStr $this->column_list
            FROM $this
            WHERE
                $whereClause
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
        if (is_null($id)) {
            return $this->dummyWhere;
        }
        $alias = $this->tableAlias ? "$this->tableAlias." : '';
        if (is_null($key)) {
            $key = $this->pk;
        }
        if (is_array($key)) {
            if (!is_array($id)) {
                $id = [$id];
            }
            $count = count($key);
            for ($i = 0; $i < $count; $i++) {
                $keyName = $key[$i];
                $conditions[] = "$alias$keyName={$this->escapeValue($id[$i])}";
                if (!isset($id[$i + 1])) {
                    break;
                }
            }
        } else {
            $conditions[] = "$alias$key={$this->escapeValue($id)}";
        }
        return implode(' AND ', $conditions);
    }

    /**
     * Add value to query
     */
    protected function escapeValue(mixed $value, false|int $noEscape = false, false|int $noQuotes = false): int|float|string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }// If the value is not a string that can be directly used in SQL escape and quote it.
        $value = $noEscape ? $value : $this->escapeSQL($value);
        return $noQuotes ? $value : "'$value'"; //quote
    }

    /**
     * @throws DatabaseConnectionException|InvalidArgumentException
     */
    public function escapeSQL(mixed $value): string
    {
        return $this->getDataInterface()->escape($value, $this->database);
    }

    /**
     * Build complex conditions for where or on clauses
     *
     * @param array $filter_rules Filter rules in following format [[columnName, operator, value], ...]
     * @param Operator $operator Logical operator for combining all conditions
     * @param boolean $skip_next_operator false skips first logical operator
     * @param string $initialOperator Initial logical operator, if first rule is not an array and not a logical operator
     * @return string conditions for where clause
     * @see MySQL_DAO::$operatorMap
     */
    protected function buildFilter(
        array $filter_rules,
        ?Operator $operator = Operator::and,
        bool $skip_next_operator = false,
        string $initialOperator = ' and',
    ): string {
        $operator ??= Operator::and;
        if (!$filter_rules) {//not filter anything (terminate floating operators)
            return $skip_next_operator ? $this->dummyWhere : '';
        }

        $queryParts = [];
        $firstRule = $filter_rules[0] ?? null;
        if (!is_array($firstRule) && !isset($this->validLogicalOperators[strtolower($firstRule)])) {//1. rule is a non joining operator
            $queryParts[] = $initialOperator;
        }//* we add an initial 'and' operator.

        $mappedOperator = $this->mapOperator($operator);
        foreach ($filter_rules as $record) {
            if (!$record) continue;
            $skipAutomaticOperator = $skip_next_operator;
            if ($skip_next_operator = !is_array($record)) {//record is a manual operator/SQL-command/parentheses
                if ($record instanceof Operator) $record = $this->mapOperator($record);
                $queryParts[] = " $record "; //operator e.g. or, and
                continue;
            }
            if (is_array($record[0])) {// nesting detected
                $record = "({$this->buildFilter($record[0], $record[1] ?? null, true)})";
            } else {//normal record
                $record = $this->assembleFilterRecord($record);
            }
            $queryParts[] = !$skipAutomaticOperator ? //automatic operator?
                " $mappedOperator $record" : $record;//automation puts operator between the last record and this one
        }
        return implode('', $queryParts);
    }

    /**
     * Map operator to SQL
     */
    protected function mapOperator(Operator $operator): string
    {
        return match ($operator) {
            Operator::equal => '=',
            Operator::notEqual => '!=',
            Operator::greater => '>',
            Operator::greaterEqual => '>=',
            Operator::less => '<',
            Operator::lessEqual => '<=',
            Operator::like => 'like',
            Operator::notLike => 'not like',
            Operator::in => 'in',
            Operator::notIn => 'not in',
            Operator::is => 'is',
            Operator::isNot => 'is not',
            Operator::isNull => 'is null',
            Operator::isNotNull => 'is not null',
            Operator::between => 'between',
            Operator::notBetween => 'not between',
            Operator::exists => 'exists',
            Operator::notExists => 'not exists',
            Operator::all => 'all',
            Operator::any => 'any',
            Operator::or => 'or',
            Operator::and => 'and',
            Operator::xor => 'xor',
            Operator::not => 'not',
        };
    }

    private function assembleFilterRecord(array $record): string
    {
        $field = $this->translateValues ? //get field 'name'
            $this->translateValues($record[0]) : $record[0];//inject replace command?
        if (!($record[1] instanceof Operator) && !is_string($record[1])) // we assume that if an operator does not exist, an equal operator is meant
            array_splice($record, 1, 0, [Operator::equal]);
        $rawInnerOperator = $record[1];
        $innerOperator = match (true) {
            $rawInnerOperator instanceof Operator => $this->mapOperator($rawInnerOperator),
            default => $this->operatorMap[$rawInnerOperator] ?? $rawInnerOperator,
        };

        $values =& $record[2];//reference assignment doesn't emit warning upon undefined keys
        //parse quotation options (defaults to false)
        $quoteSettings = is_int($record[3] ?? false) ? $record[3] : 0;
        $noQuotes = $quoteSettings & self::DAO_NO_QUOTES;
        $noEscape = $quoteSettings & self::DAO_NO_ESCAPE;
        if (is_array($values)) {//multi value operation
            if ($innerOperator === 'between') {
                $value = /* min */
                    $this->escapeValue($values[0], $noEscape, $noQuotes);
                $value .= " {$this->mapOperator(Operator::and)} ";
                $value .= /* max */
                    $this->escapeValue($values[1], $noEscape, $noQuotes);
            } else {//enlist all values e.g. in, not in
                //apply quotation rules
                $values = array_map(fn($value) => $this->escapeValue($value, $noEscape, $noQuotes), $values);
                $value = implode(', ', $values);//for some reason '0' is false
                $values = $value === '' ? 'NULL' : $value;//https://www.php.net/manual/en/language.types.boolean.php#112190
                $value = "($values)";
            }
        } elseif ($values instanceof Commands) {//resolve reserved keywords TODO add parameters to commands
            $expression = $this->commands[$values->name];
            $value = $expression instanceof Closure ?
                $expression($field) : $expression;//TODO? Edgecase with translatedValues and Command Default
        } elseif ($values instanceof DateTimeInterface) {//format date-objects
            $dateTime = $values->format($record[3] ?? 'Y-m-d H:i:s');
            $value = "'$dateTime'";
        } else {
            $value = match (gettype($values)) {//handle by type
                'NULL' => match ($rawInnerOperator) {
                    Operator::is, Operator::isNot => 'true',
                    Operator::isNull, Operator::isNotNull => '',
                    default => 'NULL'
                },
                'boolean' => bool2string($values),
                'double', 'integer' => $values,//float and int
                default => match ($values instanceof SqlStatement) {
                    true => $values->getStatement(),
                    default => $this->escapeValue($values, $noEscape, $noQuotes),
                }
            };
        }
        return "$field $innerOperator $value";
    }

    /**
     * Check if the column has translation enabled and translate the values
     */
    protected function translateValues(string $column): string
    {
        $tokens = &$this->translateValues[$column];
        if (!$tokens || !Weblication::getInstance()->hasTranslator()) {
            return $column;
        }
        $Translator = Weblication::getInstance()->getTranslator();
        $tmp = "CASE $column";
        foreach ($tokens as $token) {
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
        if (!$groupBy) {
            return '';
        }

        $sql = '';
        $alias = '';
        if ($this->tableAlias) {
            $alias = $this->tableAlias.'.';
        }

        foreach ($groupBy as $column => $sort) {
            if ($sql === '') {
                $sql = ' GROUP BY ';
            } elseif ($column === 'WITH ROLLUP') {
                $sql .= " $column";
                break;
            } else {
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
        $query = $this->buildFilter($filter_rules, Operator::and, false, '');
        if ($query) {
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
        if (!$sorting) {
            return '';
        }
        $alias = $this->tableAlias ? "$this->tableAlias." : '';
        $sql = [];
        foreach ($sorting as $column => $sort) {
            $column = $alias.$column;
            if ($this->translateValues) {
                $column = $this->translateValues($column);
            }
            $sql[] = "$column $sort";
        }
        return ' ORDER BY '.implode(', ', $sql);
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
     * @throws mysqli_sql_exception
     * @throws InvalidArgumentException
     */
    protected function execute(string $sql, ?callable $customCallback = null): RecordSet
    {
        return DataInterface::execute($sql, $this->database, $customCallback ?: [$this, 'fetchingRow'], $this->metaData, $this->throwsOnError);
    }

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
     * @return array|string[] The value getColumns would return if this DAO had just been created
     * @see self::getColumns()
     */
    public function getDefaultColumns(): array
    {
        if (isset($this->defaultColumns) && $this->defaultColumns === false)
            return $this->defaultColumns = $this->fetchColumnsList();
        return $this->defaultColumns ??= $this->getColumns();
    }

    /**
     * Returns the columns you want to query.
     */
    public function getColumns(): array
    {
        if (!$this->columns) {
            $this->fetchColumns();
        }
        return $this->columns;
    }

    /**
     * Sets the columns you want to query.
     */
    public function setColumns(string ...$columns): static
    {
        $this->defaultColumns ??= ($this->columns ?: false);
        $this->columns = $columns;
        // Escape each column
        $this->escapedColumns = array_map([$this, 'encloseColumnName'], $this->columns);
        // Concatenate the columns into a single string
        $this->column_list = implode(', ', $this->escapedColumns);
        return $this;
    }

    /**
     * Returns the metadata of the table
     */
    public function getMetaData(string $which = ''): array
    {
        return $which ? $this->metaData[$which] ?? [] : $this->metaData;
    }

    /**
     * Fetching row is a hook that goes through all the retrieved rows and applies column formatters. Can be overridden to modify the row / column values before it is returned
     *
     * @return array The fetched row with applied column formatters
     */
    public function fetchingRow(array $row): array
    {
        // Formatter for the columns
        foreach ($this->formatter as $column => $formatter) {
            if (isset($row[$column])) {
                $row[$column] = $formatter($row[$column], $row);
            }
        }
        return $row;
    }

    /**
     * Set a formatter for a column
     */
    public function setFormatter(string $column, callable $formatter): static
    {
        $this->formatter[$column] = $formatter;
        return $this;
    }

    /**
     * Delete multiple records at once
     */
    public function deleteMultiple(array $filter_rules = []): RecordSet
    {
        $where = $this->buildFilter($filter_rules, Operator::and, true);

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
        if ($where === $this->dummyWhere) {
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

    public function optimize(): RecordSet
    {
        /** @noinspection SqlResolve */
        $sql = <<<SQL
            OPTIMIZE TABLE $this->quotedTable
            SQL;
        return $this->execute($sql);
    }

    /**
     * Insert new records based on the data passed as an array, with the key corresponding to the column name.
     *
     * @param array<string, mixed>|array<array<string, mixed>> $data Data to insert, as a single record or list of records.
     * @param string $mode Insert mode: 'normal', 'ignore', 'replace', 'delayed', 'low', or 'high'. Defaults to 'normal'.
     * @param array<string, mixed>|true|null $onDuplicate Columns to update on a duplicate key, or true to update non-primary key columns. Not compatible with 'replace' or 'delayed' modes.
     */
    public function insert(array $data, string $mode = 'normal', array|true|null $onDuplicate = null): RecordSet
    {
        if (!$data) {
            throw new DAOException(__CLASS__.'::insert failed. No data specified!');
        }
        if (!array_is_list($data)) {
            $data = [$data];
        }
        // Validate columns
        $columns = array_keys($data[0]);
        $validColumns = $this->getDefaultColumns();
        if ($invalidColumns = array_diff($columns, $validColumns)) {
            throw new DAOException('Invalid columns: '.implode(', ', $invalidColumns).' in '.__CLASS__);
        }

        $insertKeyword = match(strtolower($mode)) {
            'ignore' => 'INSERT IGNORE',
            'replace' => 'REPLACE',
            'delayed' => 'INSERT DELAYED',
            'low' => 'INSERT LOW_PRIORITY',
            'high' => 'INSERT HIGH_PRIORITY',
            default => 'INSERT',
        };

        $wrappedColumnNames = array_map([$this, 'wrapSymbols'], $columns);
        $columnsStr = implode(',', $wrappedColumnNames);

        $valuesList = [];
        foreach ($data as $record) {
            $values = [];
            foreach ($record as $column => $value) {
                $values[] = $this->formatSqlValue($value, $column);
            }
            $valuesList[] = '('.implode(',', $values).')';
        }
        $valuesStr = implode(',', $valuesList);

        $aliasForInserted = '';
        $updateClause = '';
        if ($onDuplicate !== null) {
            if ($mode === 'replace' || $mode === 'delayed')
                throw new DAOException(__CLASS__.'::insert failed. Cannot use ON DUPLICATE KEY UPDATE with REPLACE or DELAYED mode.');
            $isMaria = true;//@todo check if MariaDB
            if(!$isMaria) $aliasForInserted = ' AS new';
            if ($onDuplicate === true) {
                $nonPkCols = array_values(array_diff($columns, $this->getPrimaryKey()));
                $onDuplicate = $isMaria ? $this->valuesForColumns($nonPkCols) : $this->valuesForColumnsAlias($nonPkCols, 'new');
            }
            $updateList = $this->buildAssignmentList($onDuplicate);
            if ($updateList) $updateClause = "ON DUPLICATE KEY UPDATE $updateList";
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            $insertKeyword INTO $this$aliasForInserted
                ($columnsStr)
            VALUES
                $valuesStr
            $updateClause
            SQL;

        return $this->execute($sql);
    }

    /**
     * Update a record by primary key (put the primary key in the data array)
     */
    public function update(array $data): RecordSet
    {
        // Check if all primary keys are set in the data array
        if ($missingKeys = array_diff($this->pk, array_keys($data))) {
            throw new DAOException('DAO::update failed. Missing primary keys: '.implode(', ', $missingKeys));
        }

        $pk = [];
        foreach ($this->pk as $key) {
            $pkValue = $data[$key];
            if (is_array($pkValue)) {
                $pk[] = $pkValue[0];
                $data[$key] = $pkValue[1];
            } else {
                $pk[] = $pkValue;
                unset($data[$key]);
            }
        }

        $assignmentList = $this->buildAssignmentList($data);

        if (!$assignmentList) {
            return new RecordSet();
        }

        $where = $this->buildWhere($pk, $this->pk);
        if ($where === $this->dummyWhere) {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            $this->raiseError(__FILE__, __LINE__, $error_msg);
            die($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            UPDATE $this->quotedTable
            SET
                $assignmentList
            WHERE
                $where
            SQL;
        return $this->execute($sql);
    }

    protected function formatSqlValue(mixed $value, string $column): string
    {
        $columnMeta = $this->getMetaData('columns')[$column] ?? [];
        $type = $columnMeta['type'] ?? '';
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return bool2string($value);
        }
        if (is_array($value)) {
            //if(in_array($type, ['json', 'text', 'mediumtext', 'longtext'])) return json_encode($value);
            return is_null($value[0]) ? 'NULL' : $this->escapeValue($value[0]);//? json_encode would make more sense? Where is it used?
        }
        if ($value instanceof Commands) {
            // reserved keywords don't need to be masked
            $expression = $this->commands[$value->name];
            return $expression instanceof Closure ? $expression($column) : $expression;
        }
        if ($value instanceof SqlStatement) {
            return $value->getStatement();
        }
        if ($value instanceof DateTimeInterface) {
            return "'{$value->format('Y-m-d H:i:s')}'";
        }
        if (is_int($value) || is_float($value)) {
            return match ($type) {
                'int'   => (string)(int)$value,
                'float' => (string)(float)$value,
                default => (string)$value,
            };
        }
        return "'{$this->escapeSQL($value)}'";
    }

    /**
     * Build assignment list for update statements
     */
    protected function buildAssignmentList(array $data): string
    {
        $assignments = [];
        foreach ($data as $column => $value) {
            $assignments[] = "`$column`={$this->formatSqlValue($value, $column)}";
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
        if (!$ResultSet->count()) {
            return '';
        }
        $sql = '';
        foreach ($ResultSet as $row) {
            $sql .= "INSERT INTO $this->quotedTable (";
            $sql .= implode(',', array_keys($row));
            $sql .= ') VALUES (\''.implode('\',\'', array_values($row)).'\');'.chr(10);
        }
        return $sql;
    }

    /**
     * Update multiple records at once
     */
    public function updateMultiple(array $data, array $filter_rules): RecordSet
    {
        $set = $this->buildAssignmentList($data);
        if (!$set) {
            return new RecordSet();
        }

        $where = $this->buildFilter($filter_rules, Operator::and, true);
        if ($where === $this->dummyWhere) {
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
     *
     * @throws \Exception
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
     * Returns the number of records of the assembled SQL statement as a pool\classes\Core\ResultSet
     *
     * @see RecordSet
     * @see MySQL_DAO::buildFilter
     */
    public function getCount(null|int|string|array $id = null, null|string|array $key = null, array $filter = []): RecordSet
    {
        $whereClause = $this->buildWhere($id, $key).' '.$this->buildFilter($filter);
        $count = $this->wrapSymbols('count');
        $sql = <<<SQL
            SELECT COUNT(*) AS $count
            FROM $this->quotedTable$this->quotedTableAlias
            WHERE
                $whereClause
            SQL;
        return $this->execute($sql);
    }

    /**
     * Truncate table
     */
    public function truncate(): RecordSet
    {
        $sql = <<<SQL
            TRUNCATE TABLE $this->quotedTable
            SQL;
        return $this->execute($sql);
    }

    /**
     * Reset auto increment
     */
    public function resetAutoIncrement(): RecordSet
    {
        $sql = <<<SQL
            ALTER TABLE $this->quotedTable AUTO_INCREMENT = 1
            SQL;
        return $this->execute($sql);
    }

    /**
     * Quote column name
     */
    public function encloseColumnName(string $column): string
    {
        // is it necessary to wrap the column?
        if (strtr($column, $this->preCalculatedNonWrapSymbols) !== $column) {
            return $column;
        }
        return $this->wrapSymbols($column);
    }

    protected function valuesForColumns(array $columns): array
    {
        $assign = [];
        foreach ($columns as $col) {
            $assign[$col] = new SqlStatement("VALUES({$this->encloseColumnName($col)})");
        }
        return $assign;
    }

    protected function valuesForColumnsAlias(array $columns, string $alias = 'new'): array
    {
        $assign = [];
        foreach ($columns as $col) {
            $assign[$col] = new SqlStatement("{$this->encloseColumnName($alias)}.{$this->encloseColumnName($col)}");
        }
        return $assign;
    }

    /**
     * @return string Returns the database and table name in the format `database`.`table`
     */
    public function __toString(): string
    {
        return $this->quotedSchema ? "$this->quotedDatabase.$this->quotedSchema.$this->quotedTable" : "$this->quotedDatabase.$this->quotedTable";
    }
}
