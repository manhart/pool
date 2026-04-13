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
use Override;
use pool\classes\Core\PoolObject;
use pool\classes\Core\RecordSet;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\Database\Exception\DatabaseConnectionException;
use pool\classes\Exception\DAOException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use pool\classes\Exception\SecurityException;

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
use function explode;
use function file_exists;
use function gettype;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_null;
use function str_contains;
use function str_replace;
use function strtolower;

/**
 * Class DAO - Data Access Object
 *
 * @package pool\classes\Database
 * @since 2003/07/10
 */
abstract class DAO extends PoolObject implements DatabaseAccessObjectInterface, \Stringable
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
     * Data types (Bitmask: types and flags (NULLABLE is a flag))
     */
    public const int NULLABLE = 1;
    public const int STRING = 2;
    public const int INT = 4;
    public const int FLOAT = 8;

    /**
     * @var string|null Name of the table / file / view (must be declared in derived class)
     */
    protected static ?string $tableName = null;

    /**
     * @var string|null Name of the schema (must be declared in a derived class if available)
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
     * @var array|string[] Primary key of the table
     */
    protected array $pk = [];

    /**
     * @var array|string[] Foreign keys of table
     */
    protected array $fk = [];

    /**
     * @var array|string[] Columns of the table
     */
    protected array $columns = [];

    /**
     * @var array|string[] Escaped columns of the table
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

    protected string $quotedTableAlias = '';

    protected bool $throwsOnError = false;

    private array|false|null $defaultColumns;

    /**
     * @var array|string[] Contains the opening and closing characters for escaping column and table names
     */
    protected array $symbolQuote = ['`', '`'];

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
     * Relations generated by pool-cli from the FK schema. Key = table name (or extracted alias for ambiguous FKs).
     * Do not edit manually — pool-cli will overwrite this array.
     *
     * @var array<string, array{
     *     target: class-string<DAO>,
     *     columnMap?: array<string, string>,
     *     on?: list<array{left: string, operator: Operator, value?: mixed, right?: string}>,
     *     joinType?: JoinType,
     *     source?: string,
     *     ddl?: bool,
     * }>
     */
    protected array $generatedRelations = [];

    /**
     * Manually defined relations — aliases, overrides, chained joins, or polymorphic conditions.
     * Key = alias (may override a key in $generatedRelations).
     * 'on' may additionally be a plain string here (not allowed in $generatedRelations).
     *
     * @var array<string, array{
     *     target: class-string<DAO>,
     *     columnMap?: array<string, string>,
     *     on?: list<array{left: string, operator: Operator, value?: mixed, right?: string}>|string,
     *     joinType?: JoinType,
     *     source?: string,
     *     ddl?: bool,
     * }>
     */
    protected array $customRelations = [];

    /**
     * Ad-hoc relations added at runtime via join(). Reset after each selectFrom() call.
     *
     * @var array<string, array{
     *     target: class-string<DAO>,
     *     columnMap?: array<string, string>,
     *     on?: list<array{left: string, operator: Operator, value?: mixed, right?: string}>|string,
     *     joinType?: JoinType,
     *     source?: string,
     * }>
     */
    private array $runtimeRelations = [];

    /**
     * Relations explicitly requested via with(). Reset after each selectFrom() call.
     *
     * @var array<string, true>
     */
    private array $requestedRelations = [];

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

    /**
     * Creates a Data Access Object
     */
    #[Pure]
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
        if (!is_int($targetType)) return $value;
        $baseType = $targetType & ~self::NULLABLE;

        if ($value === null && ($targetType & self::NULLABLE) !== 0) {// Null-Handling
            return null;
        }

        return match ($baseType) {
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
    public static function fetchDataStatic(array|int|string $pk, ...$fields)
    {
        return static::create(throws: true)->fetchData($pk, ...$fields);
    }

    /**
     * Shorthand for fetching one or multiple values of a record
     *
     * @param array|int|string $pk a unique identifier uses an array [$pk, $column] to specify the primary key column or search field. $pk and $column each can also be a list as is
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
     * Returns the foreign key metadata in legacy format.
     * Sobald pool-cli $generatedRelations befüllt und $fk leer bleibt, kann die Ableitung
     * auf $generatedRelations umgestellt werden — das Format bleibt identisch:
     * // $fkRelations = array_filter($this->generatedRelations, fn(array $r) => isset($r['columnMap']) && ($r['ddl'] ?? true));
     * // return $fkRelations ? array_merge(...array_values(array_map(
     * //     fn(array $rel) => array_map(
     * //         fn(string $srcCol, string $tgtCol) => [
     * //             'columnName'            => $srcCol,
     * //             'referencedTableSchema' => $rel['target']::getDatabaseName(),
     * //             'referencedTableName'   => $rel['target']::getTableName(),
     * //             'referencedColumnName'  => $tgtCol,
     * //         ],
     * //         array_keys($rel['columnMap']),
     * //         array_values($rel['columnMap']),
     * //     ),
     * //     $fkRelations,
     * // ))) : [];
     */
    public function getForeignKeys(): array
    {
        return $this->fk;
    }

    /** @deprecated use getForeignKeys() */
    public function getForeignKey(): array
    {
        return $this->fk ?? [];
    }

    public function getGeneratedRelations(): array
    {
        return $this->generatedRelations;
    }

    public function getCustomRelations(): array
    {
        return $this->customRelations;
    }

    public function getRuntimeRelations(): array
    {
        return $this->runtimeRelations;
    }

    /**
     * Returns the merged relation map. Resolution order: generated → custom → runtime.
     * Custom and runtime entries with the same key override generated ones.
     */
    public function getRelations(): array
    {
        return array_replace(
            $this->getGeneratedRelations(),
            $this->getCustomRelations(),
            $this->getRuntimeRelations(),
        );
    }

    /**
     * Explicitly request one or more named relations to be joined in the next query.
     * Named relations must exist in getRelations().
     */
    public function with(string ...$aliases): static
    {
        $relations = $this->getRelations();

        foreach ($aliases as $alias) {
            if (!isset($relations[$alias])) {
                throw new InvalidArgumentException("Unknown relation '$alias' in ".static::class);
            }
            $this->requestedRelations[$alias] = true;
        }

        return $this;
    }

    /**
     * Define an ad-hoc relation for the next query (runtime override).
     * Same structure as a $customRelations entry; 'on' may be array or string.
     * Example:
     *   ->join('VirtualAlias', ['target' => SomeDAO::class, 'on' => [...]])
     *
     * @param array{
     *     target: class-string<DAO>,
     *     columnMap?: array<string, string>,
     *     on?: list<array{left: string, operator: Operator, value?: mixed, right?: string}>|string,
     *     joinType?: JoinType,
     *     source?: string,
     * } $relation
     */
    public function join(string $alias, array $relation): static
    {
        $this->runtimeRelations[$alias] = $relation;
        $this->requestedRelations[$alias] = true;
        return $this;
    }

    /**
     * Returns a single record e.g., by primary key
     *
     * @see \MySQL_DAO::buildWhere
     */
    public function get(null|int|string|array $id, null|string|array $key = null): RecordSet
    {
        return $this->selectFrom($this, $id, $key);
    }

    /**
     * Returns all data records of the assembled SQL statement as a pool\classes\Core\ResultSet
     *
     * @see DAO::selectFrom()
     * @see DAO::buildWhereClause()
     * @see DAO::buildWhere()
     * @see DAO::buildFilter()
     * @see DAO::buildGroupBy()
     * @see DAO::buildHaving()
     * @see DAO::buildSorting()
     * @see DAO::buildLimit()
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
        return $this->selectFrom($this, $id, $key, $filter, $sorting, $limit, $groupBy, $having, $options);
    }

    /**
     * Build where condition
     */
    protected function buildWhere(null|int|string|array $id, null|string|array $key, bool $legacy = true): string
    {
        $conditions = [];
        if (is_null($id)) {
            return $legacy ? '1=1' : '';
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
     * Builds a WHERE clause from prebuilt conditions.
     */
    protected function buildWhereClause(
        null|int|string|array $id = null,
        null|string|array $key = null,
        array $filter = [],
        ?Operator $operator = Operator::and,
    ): string {
        $conditions = [];

        $idCondition = $id !== null ? $this->buildWhere($id, $key, false) : '';
        if ($idCondition !== '')
            $conditions[] = $idCondition;

        $filterCondition = $filter ? $this->buildFilter($filter, $operator, true, '') : '';
        if ($filterCondition !== '')
            $conditions[] = $filterCondition;

        $where = implode(' AND ', $conditions);
        if ($where === '') {
            return '';
        }

        return <<<SQL
            WHERE
                $where
            SQL;
    }

    protected function selectFrom(
        string $from,
        null|int|string|array $id = null,
        null|string|array $key = null,
        array $filter = [],
        array $sorting = [],
        array $limit = [],
        array $groupBy = [],
        array $having = [],
        array $options = [],
        ?string $select = null,
    ): RecordSet {
        $optionsStr = implode(' ', $options);
        $select ??= $this->column_list;
        $joins = $this->buildJoins($filter, $sorting, $groupBy, $having);
        $whereClause = $this->buildWhereClause($id, $key, $filter);
        $groupByClause = $this->buildGroupBy($groupBy);
        $havingClause = $this->buildHaving($having);
        $sortingClause = $this->buildSorting($sorting);
        $limitClause = $this->buildLimit($limit);

        $sql = <<<SQL
            SELECT $optionsStr $select
            FROM $from $joins
            $whereClause
            $groupByClause
            $havingClause
            $sortingClause
            $limitClause
            SQL;
        return $this->execute($sql);
    }

    protected function countFrom(
        string $from,
        null|int|string|array $id = null,
        null|string|array $key = null,
        array $filter = [],
    ): RecordSet {
        $joins = $this->buildJoins($filter, [], [], []);
        $whereClause = $this->buildWhereClause($id, $key, $filter);
        $count = $this->wrapSymbols('count');

        $sql = <<<SQL
            SELECT COUNT(*) AS $count
            FROM $from $joins
            $whereClause
            SQL;
        return $this->execute($sql);
    }

    /**
     * Escapes and/or quotes a value for safe usage in an SQL query.
     */
    protected function escapeValue(mixed $value, false|int $noEscape = false, false|int $noQuotes = false): int|float|string
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }// If the value is not a string that can be directly used, that can be directly used in SQL escape and quote it.
        $value = $noEscape ? $value : $this->escapeSQL($value);
        return $noQuotes ? $value : "'$value'"; //quote
    }

    private function toSqlLiteral(mixed $value, ?string $field = null, false|int $noEscape = false, false|int $noQuotes = false, ?string $dateFormat = null): string
    {
        if ($value instanceof Commands) {//TODO? Edge case with translatedValues and Command Default
            $expression = $this->commands[$value->name];
            return $expression instanceof Closure ? $expression($field ?? '') : $expression;
        }
        if ($value instanceof SqlStatement) {
            return $value->getStatement();
        }
        if ($value instanceof DateTimeInterface) {
            return "'{$value->format($dateFormat ?? 'Y-m-d H:i:s')}'";
        }

        return match (gettype($value)) {
            'NULL' => 'NULL',
            'boolean' => bool2string($value),
            'double', 'integer' => (string)$value,
            default => (string)$this->escapeValue($value, $noEscape, $noQuotes),
        };
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
     * @param array $filter_rules Filter rules in the following format [[columnName, operator, value], ...]
     * @param Operator|null $operator Logical operator for combining all conditions
     * @param boolean $skip_next_operator true skips the first logical operator
     * @param string $initialOperator Initial logical operator, if the first rule is not an array and not a logical operator
     * @return string conditions for where clause
     * @see MySQL_DAO::$operatorMap
     */
    protected function buildFilter(
        array $filter_rules,
        ?Operator $operator = Operator::and,
        bool $skip_next_operator = false,
        string $initialOperator = ' '.Operator::and->value,
    ): string {
        $operator ??= Operator::and;
        if (!$filter_rules) {//not filter anything (terminate floating operators)
            return '';
        }

        $queryParts = [];
        $firstRule = $filter_rules[0] ?? null;
        if ($initialOperator !== '' && !is_array($firstRule) && !isset($this->validLogicalOperators[strtolower($firstRule)])) {//1. rule is a non-joining operator
            $queryParts[] = $initialOperator;
        }//* we add an initial 'and' operator.

        $mappedOperator = $operator->value;
        foreach ($filter_rules as $record) {
            if (!$record) continue;
            $skipAutomaticOperator = $skip_next_operator;
            if ($skip_next_operator = !is_array($record)) {//record is a manual operator/SQL-command/parentheses
                $conditionalPart = $record instanceof Operator ? $record->value : $record;
                $queryParts[] = " $conditionalPart "; //operator or parentheses e.g., OR, AND, XOR, (, )
                continue;
            }
            if (is_array($record[0])) {// nesting detected
                $nestedFilter = $this->buildFilter($record[0], $record[1] ?? null, true);
                if ($nestedFilter === '') {
                    continue;
                }
                $record = "($nestedFilter)";
            } else {//normal record
                $record = $this->assembleFilterRecord($record);
            }
            $queryParts[] = !$skipAutomaticOperator ? //automatic operator?
                " $mappedOperator $record" : $record;//automation puts an operator between the last record and this one
        }
        return implode('', $queryParts);
    }

    private function assembleFilterRecord(array $record): string
    {
        $field = $this->translateValues ? //get field 'name'
            $this->translateValues($record[0]) : $record[0];//inject replace command?

        if (!($record[1] instanceof Operator) && !is_string($record[1])) // we assume that if an operator does not exist, an equal operator is meant
            array_splice($record, 1, 0, [Operator::equal]);
        $rawInnerOperator = $record[1];
        $innerOperator = match (true) {
            $rawInnerOperator instanceof Operator => $rawInnerOperator->value,
            default => $this->operatorMap[$rawInnerOperator] ?? $rawInnerOperator,
        };

        $values =& $record[2];//reference assignment doesn't emit warning upon undefined keys
        //parse quotation options (defaults to false)
        $quoteSettings = is_int($record[3] ?? false) ? $record[3] : 0;
        $noQuotes = $quoteSettings & self::DAO_NO_QUOTES;
        $noEscape = $quoteSettings & self::DAO_NO_ESCAPE;
        $dateFormat = is_string($record[3] ?? null) ? $record[3] : null;
        if (is_array($values)) {//multi-value operation
            if ($innerOperator === Operator::between->value || $innerOperator === Operator::notBetween->value) {
                $value = /* min */
                    $this->escapeValue($values[0], $noEscape, $noQuotes);
                $value .= ' '.Operator::and->value.' ';
                $value .= /* max */
                    $this->escapeValue($values[1], $noEscape, $noQuotes);
            } else {//enlist all values e.g., in, not in
                //apply the quotation rules
                $values = array_map(fn($value) => $this->escapeValue($value, $noEscape, $noQuotes), $values);
                $value = implode(', ', $values);//for some reason '0' is false
                if ($value === '') {
                    return 'false';
                } else {
                    $values = $value;
                }//https://www.php.net/manual/en/language.types.boolean.php#112190
                $value = "($values)";
            }
        } elseif (is_null($values)) {
            $value = match ($rawInnerOperator) {
                Operator::is, Operator::isNot => 'true',
                Operator::isNull, Operator::isNotNull => '',
                default => 'NULL'
            };
        } else {
            $value = $this->toSqlLiteral($values, $field, $noEscape, $noQuotes, $dateFormat);
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
     * @param array $groupBy Group by columns with sort order in the following format ['column1' => 'ASC', 'column2' => 'DESC']. With rollup it is also possible.
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
     * @param array $filter_rules Filter rules in the following format [[columnName, operator, value], ...]
     * @return string HAVING statement
     */
    protected function buildHaving(array $filter_rules): string
    {
        $query = $this->buildFilter($filter_rules, Operator::and, true, '');
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
     * @throws InvalidArgumentException|DAOException|mysqli_sql_exception
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
     * Set the primary key
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
     * Set columns as an array
     */
    public function setColumnsAsArray(array $columns): static
    {
        $this->setColumns(...$columns);
        return $this;
    }

    /**
     * Temporarily overrides the selected columns and restores the previous column state afterward.
     */
    protected function withColumns(array $columns, callable $callback): mixed
    {
        $columnsState = $this->columns;
        $escapedColumns = $this->escapedColumns;
        $columnList = $this->column_list;

        try {
            $this->setColumns(...$columns);
            return $callback();
        }
        finally {
            $this->columns = $columnsState;
            $this->escapedColumns = $escapedColumns;
            $this->column_list = $columnList;
        }
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
        if (!$this->columns) {
            $this->escapedColumns = [];
            $this->column_list = '*';
            return $this;
        }

        // Escape each column
        $escapedColumns = [];
        $defaultColumnsLookup = array_flip($this->defaultColumns ?: []);
        foreach ($columns as $column) {
            $isDefaultColumn = isset($defaultColumnsLookup[$column]);
            $escapedColumns[] = $this->encloseColumnName($column, $isDefaultColumn);
        }
        $this->escapedColumns = $escapedColumns;
        $this->column_list = implode(', ', $escapedColumns);
        return $this;
    }

    /**
     * Builds JOIN SQL from auto-detected and explicitly requested relations.
     * Detection order per query parameter group:
     *   filter/having : $rule[0] column name  (e.g. 'Customer.name')
     *   sorting/groupBy : array keys
     *   columns       : $this->columns array entries
     * Final join set = auto-detected prefixes ∪ with()-requested aliases, topologically sorted.
     * After building, requestedRelations and runtimeRelations are reset for the next call.
     */
    private function buildJoins(array $filter, array $sorting, array $groupBy, array $having): string
    {
        $relations = $this->getRelations();
        $toJoin = $this->requestedRelations;

        // Auto-detect table prefixes from all query parameters
        $rootAlias = $this->tableAlias ?: $this->table;
        foreach ($this->extractTablePrefixes($filter, $having, $sorting, $groupBy) as $prefix) {
            if ($prefix !== $rootAlias && isset($relations[$prefix])) {
                $toJoin[$prefix] = true;
            }
        }

        $this->requestedRelations = [];
        $this->runtimeRelations = [];

        if (!$toJoin) return '';

        // Transitively pull in source dependencies
        $toJoin = $this->resolveJoinDependencies($toJoin, $relations);

        // Topological sort: source before dependent
        $sorted = $this->topologicalSortRelations($toJoin, $relations);

        $parts = [];
        foreach ($sorted as $alias) {
            if (!isset($relations[$alias])) continue;
            $parts[] = $this->buildRelationSQL($alias, $relations[$alias], $rootAlias);
        }

        return $parts ? "\n".implode("\n", $parts) : '';
    }

    /**
     * Extracts table alias prefixes (e.g. 'Customer' from 'Customer.name') from filter/having rules
     * and from keys of sorting/groupBy arrays. Skips the root table alias.
     */
    private function extractTablePrefixes(array $filter, array $having, array $sorting, array $groupBy): array
    {
        $prefixes = [];

        $quoteStart = $this->symbolQuote[0];
        $quoteEnd = $this->symbolQuote[1];
        $quotes = $quoteStart.$quoteEnd;

        $qS = preg_quote($quoteStart, '/');
        $qE = preg_quote($quoteEnd, '/');
        // Sucht nach: (Bezeichner) gefolgt von einem Punkt und einem (Bezeichner)
        // der negative Lookahead (?!\s*\.) sorgt dafür, dass bei db.table.column
        // nur "table" (vor dem letzten Punkt) gematcht wird.
        // /(`[^`]+`|\w+)\s*\.\s*(?:`[^`]+`|\w+)(?!\s*\.)/u
        // $pattern = "/({$qS}[^$qE]+$qE|\w+)\s*\.\s*(?:{$qS}[^$qE]+$qE|\w+)(?!\s*\.)/u";
        $pattern = "/({$qS}[^$qE]++$qE|\w++)\s*\.\s*(?:{$qS}[^$qE]++$qE|\w++)(?!\s*\.)/u";

        $extractPrefixes = static function (string $expr) use (&$prefixes, $pattern, $quotes): void {
            if (!str_contains($expr, '.')) {
                return;// No dot, i.e. no table-column structure, skip
            }
            if (preg_match('/^\s*\(*\s*SELECT\b/i', $expr)) {
                return;// Subselect detected, skip
            }
            if (preg_match_all($pattern, $expr, $matches)) {
                foreach ($matches[1] as $match) {
                    $prefixes[trim($match, $quotes)] = true;
                }
            }
        };

        $extractFromRules = function (array $rules) use (&$extractFromRules, $extractPrefixes): void {
            foreach ($rules as $rule) {
                if (!is_array($rule)) continue;
                if (is_array($rule[0])) {
                    $extractFromRules($rule[0]);
                    continue;
                }
                if (is_string($rule[0])) {
                    $extractPrefixes($rule[0]);
                }
            }
        };

        $extractFromRules($filter);
        $extractFromRules($having);

        /* @todo SqlStatement */
        foreach (array_merge(array_keys($sorting), array_keys($groupBy)) as $expr) {
            $extractPrefixes($expr);
        }

        foreach ($this->columns as $expr) {
            $extractPrefixes($expr);
        }

        return array_keys($prefixes);
    }

    /**
     * Transitively adds source dependencies for chained relations.
     */
    private function resolveJoinDependencies(array $toJoin, array $relations): array
    {
        $changed = true;
        while ($changed) {
            $changed = false;
            foreach ($toJoin as $alias => $_) {
                $source = $relations[$alias]['source'] ?? null;
                if ($source !== null && !isset($toJoin[$source])) {
                    $toJoin[$source] = true;
                    $changed = true;
                }
            }
        }
        return $toJoin;
    }

    /**
     * Topological sort: a relation's source must appear before the relation itself.
     */
    private function topologicalSortRelations(array $toJoin, array $relations): array
    {
        $sorted = [];
        $visited = [];

        $visit = function (string $alias) use (&$visit, &$sorted, &$visited, $toJoin, $relations): void {
            if (isset($visited[$alias])) return;
            $visited[$alias] = true;
            $source = $relations[$alias]['source'] ?? null;
            if ($source !== null && isset($toJoin[$source])) {
                $visit($source);
            }
            $sorted[] = $alias;
        };

        foreach ($toJoin as $alias => $_) {
            $visit($alias);
        }

        return $sorted;
    }

    /**
     * Builds a single JOIN clause string for a relation.
     */
    private function buildRelationSQL(string $alias, array $relation, string $rootAlias): string
    {
        $joinType = ($relation['joinType'] ?? JoinType::left)->value;
        /** @var DAO $targetClass */
        $targetClass = $relation['target'];
        $targetTable = $this->wrapSymbols($targetClass::getDatabaseName()).'.'.$this->wrapSymbols($targetClass::getTableName());
        $sourceAlias = $relation['source'] ?? $rootAlias;
        $onClause = $this->buildRelationOnClause($alias, $relation, $sourceAlias, $rootAlias);

        return "$joinType JOIN $targetTable AS ".$this->wrapSymbols($alias)." ON ($onClause)";
    }

    /**
     * Builds the ON clause string for a relation.
     * Placeholders in 'on' strings and 'right' column values:
     *   {root}   → alias/table of the root (calling) DAO
     *   {source} → alias of the relation referenced in 'source'
     */
    private function buildRelationOnClause(string $alias, array $relation, string $sourceAlias, string $rootAlias): string
    {
        if (isset($relation['on'])) {
            if (is_string($relation['on'])) {
                return str_replace(['{root}', '{source}'], ["`$rootAlias`", "`$sourceAlias`"], $relation['on']);
            }
            $conditions = [];
            foreach ($relation['on'] as $clause) {
                $left = "`$alias`."."`{$clause['left']}`";
                $op = $clause['operator']->value;
                if (isset($clause['right'])) {
                    $right = str_replace(['{root}', '{source}'], ["`$rootAlias`", "`$sourceAlias`"], $clause['right']);
                } else {
                    $right = $this->escapeValue($clause['value']);
                }
                $conditions[] = "$left $op $right";
            }
            return implode(' AND ', $conditions);
        }

        // columnMap: sourceAlias.sourceCol = alias.targetCol
        $conditions = [];
        foreach ($relation['columnMap'] as $sourceCol => $targetCol) {
            $conditions[] = "`$sourceAlias`.`$sourceCol` = `$alias`.`$targetCol`";
        }
        return implode(' AND ', $conditions);
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
    public function deleteMultiple(array $filter = []): RecordSet
    {
        $where = $this->buildWhereClause(filter: $filter);

        /** @noinspection SqlWithoutWhere */
        $sql = <<<SQL
            DELETE
            FROM $this
            $where
            SQL;
        return $this->execute($sql);
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int|string|array $id): RecordSet
    {
        $where = $this->buildWhereClause($id, $this->pk);
        if ($where === '') {
            throw new SecurityException("Delete maybe wrong! Do you really want to delete all records in the table: $this->table");
        }
        /** @noinspection SqlWithoutWhere */
        $sql = <<<SQL
            DELETE
            FROM $this
            $where
            SQL;
        return $this->execute($sql);
    }

    /** @noinspection PhpUnused */
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
     */
    #[Override]
    public function insert(array $data, string $mode = 'normal'): RecordSet
    {
        [, $columnsStr, $valuesStr, $insertKeyword] = $this->prepareInsertParts($data, $mode);

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            $insertKeyword INTO $this
                ($columnsStr)
            VALUES
                $valuesStr
            SQL;

        return $this->execute($sql);
    }

    #[Override]
    public function upsert(array $data, array|true $onDuplicate = true, string $mode = 'normal'): RecordSet
    {
        if ($mode === 'replace' || $mode === 'delayed') {
            throw new DAOException(__CLASS__.'::upsert failed. Cannot use ON DUPLICATE KEY UPDATE with REPLACE or DELAYED mode.');
        }
        [$columns, $columnsStr, $valuesStr, $insertKeyword] = $this->prepareInsertParts($data, $mode);
        [$updateClause, $aliasForInserted] = $this->buildOnDuplicateClause($columns, $onDuplicate );

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
    #[Override]
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

        $where = $this->buildWhereClause($pk, $this->pk);
        if ($where === '') {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            throw new SecurityException($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            UPDATE $this
            SET
                $assignmentList
            $where
            SQL;
        return $this->execute($sql);
    }

    protected function formatSqlValue(mixed $value, string $column): string
    {
        $columnMeta = $this->getMetaData('columns')[$column] ?? [];
        $type = $columnMeta['type'] ?? '';
        if (is_array($value)) {
            //if(in_array($type, ['json', 'text', 'mediumtext', 'longtext'])) return json_encode($value);
            return is_null($value[0]) ? 'NULL' : $this->escapeValue($value[0]);//? json_encode would make more sense? Where is it used?
        }
        if (is_int($value) || is_float($value)) {
            return match ($type) {
                'int'   => (string)(int)$value,
                'float' => (string)(float)$value,
                default => (string)$value,
            };
        }
        return $this->toSqlLiteral($value, $column);
    }

    /**
     * Build assignment list for update statements
     */
    protected function buildAssignmentList(array $data): string
    {
        $assignments = [];
        foreach ($data as $column => $value) {
            $assignments[] = "{$this->encloseColumnName($column)}={$this->formatSqlValue($value, $column)}";
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
            $sql .= "INSERT INTO $this (";
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

        $where = $this->buildWhereClause(filter: $filter_rules);
        if ($where === '') {
            $error_msg = "Update maybe wrong! Do you really want to update all records in the table: $this->table?";
            throw new SecurityException($error_msg);
        }

        /** @noinspection SqlResolve */
        $sql = <<<SQL
            UPDATE $this
            SET
                $set
            $where
            SQL;
        return $this->execute($sql);
    }

    /**
     * Returns the number of affected rows with no limit
     * Warning: When used after a CALL statement, this function returns the number of rows selected by the last query in the procedure, not by the whole
     * procedure. Attention: Statements using the FOUND_ROWS() function are not safe for replication.
     *
     * @throws InvalidArgumentException|DatabaseConnectionException
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
        return $this->countFrom($this, $id, $key, $filter);
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

    protected function prepareInsertParts(array $data, string $mode): array
    {
        if (!$data) {
            throw new DAOException('No data specified in '.__CLASS__);
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

        $insertKeyword = match (strtolower($mode)) {
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
            $row = [];
            foreach ($columns as $column) {
                $row[] = $this->formatSqlValue($record[$column], $column);
            }
            $valuesList[] = '('.implode(',', $row).')';
        }
        $valuesStr = implode(',', $valuesList);
        return [$columns, $columnsStr, $valuesStr, $insertKeyword];
    }

    /** @noinspection PhpConditionAlreadyCheckedInspection */
    protected function buildOnDuplicateClause(array $columns, array|true $onDuplicate): array
    {
        $updateClause = '';
        $aliasForInserted = '';
        $isMaria = true;//@todo check if MariaDB or MySQL
        if (!$isMaria) $aliasForInserted = ' AS new';
        $primaryKey = $this->getPrimaryKey();
        if ($onDuplicate === true) {
            $nonPkCols = array_values(array_diff($columns, $primaryKey));
            /** @noinspection PhpRedundantOptionalArgumentInspection */
            $onDuplicate = $isMaria ? $this->valuesForColumns($nonPkCols) : /* mysql */
                $this->valuesForColumnsAlias($nonPkCols, 'new');
        }
        // If the table has a single primary key and the PK is not already
        // part of the ON DUPLICATE KEY UPDATE list, automatically inject
        // `pk = LAST_INSERT_ID(pk)` so that LAST_INSERT_ID() returns the
        // existing key on UPDATE as well as the newly generated key on INSERT.
        if (count($primaryKey) === 1 && !isset($onDuplicate[$primaryKey[0]])) {
            $col = $this->encloseColumnName($primaryKey[0]);
            $onDuplicate[$primaryKey[0]] = new SqlStatement("LAST_INSERT_ID($col)");
        }
        $updateList = $this->buildAssignmentList($onDuplicate);
        if ($updateList) $updateClause = "ON DUPLICATE KEY UPDATE $updateList";
        return [$updateClause, $aliasForInserted];
    }

    /**
     * Quote column name
     */
    public function encloseColumnName(string $column, bool $addTableIdentifier = false): string
    {
        // is it necessary to wrap the column?
        if ($this->shouldPrefixColumn($column)) {
            $wrappedColumn = $this->wrapSymbols($column);

            if ($addTableIdentifier) {
                $wrappedTable = $this->quotedTableAlias ?: $this->quotedTable;
                return "$wrappedTable.$wrappedColumn";
            }
            return $wrappedColumn;
        }
        return $column;
    }

    protected function shouldPrefixColumn(string $column): bool
    {
        return strtr($column, $this->preCalculatedNonWrapSymbols) === $column;
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
