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
     * Constructor.
     */
    protected function __construct(?string $databaseAlias = null, ?string $table = null)
    {
        parent::__construct($databaseAlias, $table);
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
            $columnMeta = $this->getMetaData('columns')[$column['field']] ?? [];
            $columnPhpType =
                $columnMeta['phpType'] ?? '';
            $filterExpr = match ($column['type'] ?? '') {// poolType
                'date', 'date.time' => //temporal type
                ($sqlTimeFormat = Weblication::getInstance()->getDefaultFormat("mysql.date_format.{$column['type']}")) ?//try fetch format-string
                    "DATE_FORMAT($originalExpr, '$sqlTimeFormat')" : $originalExpr,//set SQL to format temporal value
                default => $originalExpr//unchanged
            };
            $columnName = $column['alias'] ?? $column;
            // Use exact matching for numeric columns.
            // Partial LIKE searches on numbers are usually not useful and can force full table scans.
            [$operator, $searchStringCasted] = match ($columnPhpType) {
                'int', 'bool' => [Operator::equal, (int)$searchString],
                'float' => [Operator::equal, (float)$searchString],
                default => [Operator::like, $searchString],
            };
            if (isset($definedSearchKeywords[$columnName])) {//found additional metadata
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
                        $operator = Operator::like;
                        break;
                    default:
                        $filterByValue = match ($columnPhpType) {
                            'int', 'bool' => (int)$filterByValue,
                            'float' => (float)$filterByValue,
                            default => $filterByValue,
                        };
                        $filterByValue = $operator === Operator::like ? "%$filterByValue%" : $filterByValue;
                }
                $filterByColumn = $column['filterByDbColumn'] ?? false ?: $filterExpr;
                $definedFilter[] = [$filterByColumn, $operator, $filterByValue];
            } elseif ($searchStringCasted) {//column isn't filtered -> look for searchString
                if ($hasSearchFilter) {
                    $filter[] = Operator::or;
                }
                $searchValue = $operator === Operator::like ? "%$searchStringCasted%" : $searchStringCasted;
                $filter[] = [$filterExpr, $operator, $searchValue];
                $hasSearchFilter = true;
            }//add condition, one column must match the searchString
        }
        return ($definedFilter && $filter) ?
            array_merge(['('], $filter, [')'], [Operator::and], $definedFilter) ://both combined
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
