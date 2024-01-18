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
use pool\classes\Exception\DAOException;
use pool\classes\translator\Translator;
use function array_map;
use function array_merge;
use function array_pop;
use function array_push;
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
     *
     * @var array
     */
    protected array $translate = [];

    /**
     * @var Translator
     */
    protected Translator $Translator;

    /**
     * @var array
     */
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

        $this->setColumns(...$this->columns);
        $escapedColumns = array_map(fn($column) => "$this->quotedTable.$column", $this->escapedColumns);
        // Concatenate the columns into a single string
        $this->column_list = implode(', ', $escapedColumns);
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
        $fieldInfo = $this->getDataInterface()->getColumnMetadata($this->getDatabase(), static::getTableName(), $column);
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
     * Get columns with table alias
     */
    public function getColumnsWithTableAlias(): array
    {
        return array_map(function($val) {
            return "$this->tableAlias.$val";
        }, $this->getColumns());
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
}