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

use pool\classes\Core\Weblication;
use pool\classes\Database\DAO;
use pool\classes\Database\Operator;
use pool\classes\Exception\DAOException;
use pool\classes\translator\Translator;

use function array_merge;
use function date_parse;
use function explode;
use function implode;
use function is_string;
use function sprintf;
use function strpos;
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
     * Columns to translate
     */
    protected array $translate = [];

    protected Translator $Translator;

    private array $cache = [
        'translatedValues' => [],
        'translate' => [],
    ];

    /**
     * @var array<string, array> Cached field metadata by column name.
     */
    private array $fieldInfoByColumn = [];

    /**
     * Constructor.
     */
    protected function __construct(?string $databaseAlias = null, ?string $table = null)
    {
        parent::__construct($databaseAlias, $table);
        $this->rebuildColumnList();
    }

    public function fetchColumns(): static
    {
        $this->fieldInfoByColumn = [];
        return parent::fetchColumns();
    }

    /**
     * Rebuild column list
     *
     * @todo rethink / rework rebuildColumnList
     */
    private function rebuildColumnList(): void
    {
        // Columns are predefined as property "columns".
        if (!$this->columns) {
            return;
        }

        $this->setColumns(...$this->columns);
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
     * @return array|string[][]
     */
    public function getTranslatedValues(): array
    {
        return $this->translateValues;
    }

    /**
     * Set columns for translation into another language
     *
     * @deprecated
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
     * Returns the data type of the column
     */
    public function getColumnDataType(string $column): string
    {
        $field = $this->getFieldInfo($column);
        $buf = explode(' ', $field['COLUMN_TYPE'] ?? '');
        $type = $buf[0] ?? '';
        if ($type === '') {
            throw new DAOException("Column $column type not found in table $this->table");
        }
        if (($pos = strpos($type, '(')) !== false) {
            $type = substr($type, 0, $pos);
        }
        return $type;
    }

    /**
     * Returns the column info details
     */
    public function getColumnInfo(string $column): array
    {
        return $this->getFieldInfo($column);
    }

    private function getFieldInfo(string $column): array
    {
        if (!$this->field_list) {
            $this->fieldInfoByColumn = [];
            $this->fetchColumns();
        }

        if (!$this->fieldInfoByColumn) {
            foreach ($this->field_list as $field) {
                $columnName = $field['COLUMN_NAME'] ?? null;
                if (is_string($columnName) && $columnName !== '') {
                    $this->fieldInfoByColumn[$columnName] = $field;
                }
            }
        }

        if (isset($this->fieldInfoByColumn[$column])) {
            return $this->fieldInfoByColumn[$column];
        }

        throw new DAOException("Column $column not found in table $this->table");
    }

    /**
     * Get enumerable values from a field
     *
     * @noinspection PhpDeprecatedPassingNonEmptyEscapeToCsvFunctionInspection, PhpRedundantOptionalArgumentInspection
     */
    public function getEnumValues(string $column): array
    {
        $fieldInfo = $this->getDataInterface()->getColumnMetadata($this->getDatabase(), static::getTableName(), $column);
        $type = $fieldInfo['Type'] ?? null;
        if ($type === null || !str_starts_with($type, 'enum(')) {
            return [];
        }
        $content = substr($type, 5, -1);
        return str_getcsv($content, ',', "'", '\\');
    }

    /**
     * Get columns with table alias
     */
    public function getColumnsWithTableAlias(): array
    {
        $columnsWithAlias = [];
        $aliasPrefix = "$this->tableAlias.";
        foreach ($this->getColumns() as $column) {
            $columnsWithAlias[] = $aliasPrefix.$column;
        }
        return $columnsWithAlias;
    }

    /**
     * Fetching row is a hook that goes through all the retrieved rows. Can be used to modify the row (column content) before it is returned.
     */
    public function fetchingRow(array $row): array
    {
        return parent::fetchingRow($this->translate ? $this->translate($row) : $row);
    }

    /**
     * Translate column content
     */
    protected function translate(array $row): array
    {
        $Weblication = Weblication::getInstance();
        if (!$Weblication->hasTranslator()) {//no translator
            return $row;
        }//unchanged
        $Translator = $Weblication->getTranslator();
        // another idea to handle columns which should be translated
        // $translationKey = "columnNames.{$this->getTableName()}.{$row[$key]}";
        foreach ($this->translate as $key) {
            if (isset($row[$key]) && is_string($row[$key])) {
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
        if (!$searchString && !$definedSearchKeywords) {
            return [];
        }
        $filter = [];
        $definedFilter = [];
        $hasSearchFilter = false;
        foreach ($columns as $column) {
            $originalExpr = $column['expr'] ?? $column; // column or expression
            $filterExpr = match ($column['type'] ?? '') {
                'date', 'date.time' => //temporal type
                ($sqlTimeFormat = Weblication::getInstance()->getDefaultFormat("mysql.date_format.{$column['type']}")) ?//try fetch format-string
                    "DATE_FORMAT($originalExpr, '$sqlTimeFormat')" : $originalExpr,//set SQL to format temporal value
                default => $originalExpr//unchanged
            };
            $columnName = $column['alias'] ?? $column;
            if (isset($definedSearchKeywords[$columnName])) {//found additional metadata
                $operator = Operator::like;
                /** @var string $filterByValue */
                $filterByValue = $definedSearchKeywords[$columnName];//get keyword for column name?
                switch (($column['filterControl'] ?? false ?: 'input')) {//type of input?
                    case 'select':
                        $operator = is_array($filterByValue) ? Operator::in : Operator::equal;
                        break;
                    case 'datepicker':
                        if ($filterByValue) {//non-empty
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
                $definedFilter[] = [$filterByColumn, $operator, $filterByValue];
            } elseif ($searchString) {//column not filtered -> look for searchString
                if ($hasSearchFilter) {
                    $filter[] = Operator::or;
                }
                $filter[] = [$filterExpr, Operator::equal, "%$searchString%"];
                $hasSearchFilter = true;
            }//add condition, one column must match the searchString
        }
        return ($definedFilter && $filter) ?
            array_merge(['('], $filter, [')'], ['and'], $definedFilter) ://both combined
            ($definedFilter ?: $filter);//the only filled one
    }

    /**
     * Reformat date for filter
     */
    private function reformatFilterDate(string $dateValue): string
    {
        if (($date = date_parse($dateValue)) &&
            $date['error_count'] === 0 && $date['warning_count'] === 0 &&
            $date['year'] && $date['month'] && $date['day']) {// is date?
            $format = "%d-%02d-%02d";//y-MM-DD
            if ($date['hour'] || $date['minute']) {
                $format .= " %02d:%02d";// hh:mm
                $format .= $date['second'] ? ":%02d" : '';//:ss
            }
            $dateValue = sprintf(
                $format,
                $date['year'],
                $date['month'],
                $date['day'],
                $date['hour'],
                $date['minute'],
                $date['second'],
            );
        }
        /** Malformed date TODO */
        return $dateValue;
    }
}
