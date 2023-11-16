<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

use Countable;
use DateTime;
use DateTimeZone;
use Exception;
use Iterator;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\InvalidJsonException;
use UConverter;
use function array_column;
use function array_keys;
use function array_map;
use function array_multisort;
use function array_values;
use function count;
use function is_array;
use function str_contains;

/**
 * Class pool\classes\Core\ResultSet
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class RecordSet extends PoolObject implements Iterator, Countable
{
    /**
     * @var array records
     */
    protected array $records = [];

    /**
     * @var int internal pointer
     */
    protected int $index = -1;

    /**
     * @var array error stack
     */
    protected array $errorStack = [];

    /**
     * @var array fields returned in this order.
     */
    protected array $returnFields = [];

    /**
     * Constructor
     */
    public function __construct(array $records = [])
    {
        $this->records = $records;
        if($records) {
            $this->reset();
        }
    }

    /**
     * Reset pointer to first record
     */
    public function reset(): int
    {
        $this->index = $this->count() > 0 ? 0 : -1;
        return $this->index;
    }

    /**
     * Returns the number of records in the pool\classes\Core\ResultSet
     */
    public function count(): int
    {
        return count($this->records);
    }

    /**
     * Sort by a field / column
     */
    public function sort(string $fieldName, int $sort = \SORT_ASC, int $sortType = \SORT_REGULAR): void
    {
        $sortArr = array_column($this->records, $fieldName);
        if($sortType === \SORT_STRING) {
            $sortArr = array_map('\strtolower', $sortArr);
        }

        array_multisort($sortArr, $sort, $sortType, $this->records);
    }

    /**
     * Sorts by multiple columns
     */
    public function sortComplex(array $fieldNames, array|int $sort = \SORT_ASC, array|int $sortType = \SORT_REGULAR): void
    {
        $args = [];
        foreach($fieldNames as $fieldName) {
            $sortArr = array_column($this->records, $fieldName);
            if($sortType === \SORT_STRING) {
                $sortArr = array_map('\strtolower', $sortArr);
            }
            $args[] = $sortArr;
        }

        // Add sorting direction and type for each column
        foreach($fieldNames as $i => $fieldName) {
            $args[] = $sort[$i] ?? $sort;
            $args[] = $sortType[$i] ?? $sortType;
        }

        // Add the main array as the last argument
        $args[] = &$this->records;

        // Invoke array_multisort with dynamic arguments
        array_multisort(...$args);
    }

    /**
     * Sort an array by values using a user-defined comparison function
     *
     * @param callable $cmp_function
     */
    public function usort(callable $cmp_function): void
    {
        \usort($this->records, $cmp_function);
    }

    /**
     * Filters elements of the dataset using a callback function
     *
     * @param callable $callback
     * @param int $mode
     * @return void
     */
    public function filter(callable $callback, int $mode = 0): void
    {
        $this->records = array_values(\array_filter($this->records, $callback, $mode));
    }

    /**
     * Moves a record within the result set to the specified position
     *
     * @param string $fieldName field name
     * @param mixed $uniqueValue unique value to search for
     * @param integer $newPos Position where the record should be moved
     * @return boolean
     */
    public function move(string $fieldName, mixed $uniqueValue, int $newPos): bool
    {
        $currentPos = null;

        // find the current position of the item
        foreach($this->records as $index => $row) {
            if($row[$fieldName] === $uniqueValue) {
                $currentPos = $index;
                break;
            }
        }

        // if the item is not found cancel early
        if($currentPos === null) {
            return false;
        }

        // No action required if the current and new positions are the same
        if($currentPos === $newPos - 1) {
            return true;
        }

        // Remove the element and insert it in the new position
        $movedElement = $this->spliceRowSet($currentPos, 1)[0];
        $this->spliceRowSet($newPos - 1, 0, [$movedElement]);

        return true;
    }

    /**
     * Remove a portion of the record set (array) and replace it with something else.
     * Removes the elements designated by offset and length from the array, and replaces them with the elements of the replacement array, if
     * supplied.
     *
     * @see array_splice()
     * @link https://www.php.net/manual/en/function.array-splice
     */
    public function spliceRowSet(int $offset, ?int $length = null, mixed $replacement = []): array
    {
        return \array_splice($this->records, $offset, $length, $replacement);
    }

    /**
     * Returns all columns of the current record
     */
    public function getColumns(): array
    {
        if($this->count() > 0) {
            return array_keys($this->records[$this->index]);
        }
        return [];
    }

    /**
     * Is the pointer at the first record?
     */
    public function isFirst(): bool
    {
        return $this->index === 0;
    }

    /**
     * Set pointer to last record and return it
     */
    public function last(): array
    {
        $count = $this->count();
        if($count > 0) {
            $this->index = ($count - 1);
        }
        else {
            $this->index = -1;
        }
        return $this->getRecord();
    }

    /**
     * Returns the current record (dataset) or an empty array if the position (index) is out of range
     *
     * @see static::seek()
     */
    public function getRecord(int $index = -1): array
    {
        return $index >= 0 && $index !== $this->index ?
            $this->seek($index)
            : $this->records[$this->index] ?? [];
    }

    /**
     * Sets the pointer to the specified position and returns the record or an empty array if the position is out of range
     */
    public function seek(int $index): array
    {
        $this->index = $index < $this->count() ? $index : -1;
        return $this->getRecord();
    }

    /**
     * Is the pointer at the last record?
     */
    public function isLast(): bool
    {
        return $this->index === ($this->count() - 1);
    }

    /**
     * Returns the current record (dataset) or an empty array if the position (index) is out of range
     */
    public function current(): array
    {
        return $this->getRecord($this->index);
    }

    /**
     * Returns the current position / index (Iterator).
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * Move forward to next record
     */
    public function next(): void
    {
        $this->index++;
    }

    /**
     * Reset the index
     */
    public function rewind(): void
    {
        $this->reset();
    }

    public function valid(): bool
    {
        return $this->index !== -1 && isset($this->records[$this->index]);
    }

    /**
     * Backwards the index by one position until the beginning is reached
     *
     * @return bool true if the beginning is not reached yet, false otherwise
     */
    public function backward(): bool
    {
        if($this->index <= 0) {
            return false;
        }
        $this->index--;
        return true;
    }

    /**
     * Returns a value of a field of the current record as a string
     */
    public function getValueAsString(string $key, string $default = ''): string
    {
        return (string)$this->getValue($key, $default);
    }

    /**
     * Returns a value of a field of the current record
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        if(!\array_key_exists($key, $this->records[$this->index] ?? [])) return $default;
        return $this->records[$this->index][$key];
    }

    /**
     * Returns a value of a field of the current record as a decoded json
     *
     * @throws InvalidJsonException|\JsonException
     */
    public function getValueAsJson(string $key, string $defaultJson = '{}'): mixed
    {
        $json = (string)$this->getValue($key, $defaultJson) ?: $defaultJson;
        if(!\isValidJSON($json)) {
            throw new InvalidJsonException();
        }
        return \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * Returns a value of a field of the current record as an integer. It is also possible to return null as default value.
     */
    public function getValueAsInt(string $key, int $default = 0): int
    {
        $value = $this->getValue($key, $default);
        if($value === null) return $default;
        return (int)$value;
    }

    /**
     * Shorthand of getValueAsInt('count')
     * @return int
     */
    public function getCountValue(): int
    {
        return  $this->getValueAsInt('count');
    }

    /**
     * shorthand for getValueAsInt('last_insert_id')
     * @return int
     */
    public function getLastInsertID(): int
    {
        return $this->getValueAsInt('last_insert_id');
    }

    /**
     * Returns a value of a field of the current record as a float
     */
    public function getValueAsFloat(string $key, float $default = 0.00): float
    {
        $value = $this->getValue($key, $default);
        if($value === null) return $default;
        return (float)$value;
    }

    /**
     * Returns a value of a field of the current record as a boolean
     */
    public function getValueAsBool(string $key, bool $default = false): bool
    {
        return (bool)$this->getValue($key, $default);
    }

    /**
     * Returns a value of a field of the current record as formatted date string
     */
    public function getValueAsFormattedDate(string $key, $default = null): ?string
    {
        $DateTime = $this->getValueAsDateTime($key, $default);
        if(\is_null($DateTime)) return null;
        $format = Weblication::getInstance()->getDefaultFormat('php.date');
        return $DateTime->format($format);
    }

    /**
     * Returns a value of a field of the current record as DateTime object
     */
    public function getValueAsDateTime(string $key, $default = null, ?DateTimeZone $timezone = null): ?DateTime
    {
        $value = $this->getValue($key, $default);
        if($value instanceof DateTime)
            return $value;
        if($value && $value !== '0000-00-00' && $value !== '0000-00-00 00:00:00') {
            if(\is_numeric($value) && !str_contains($value, '-'))
                $value = "@$value";
            try {
                return new DateTime($value, $timezone);
            }
            catch(Exception) {
            }
        }
        return null;
    }

    /**
     * Returns a value of a field of the current record as formatted "date.time" string
     */
    public function getValueAsFormattedDateTime(string $key, $default = null, bool $seconds = true): ?string
    {
        $DateTime = $this->getValueAsDateTime($key, $default);
        if(\is_null($DateTime)) return null;
        $format = Weblication::getInstance()->getDefaultFormat('php.date.time'.($seconds ? '.sec' : ''));
        return $DateTime->format($format);
    }

    /**
     * Returns a value of a field of the current record as formatted number
     *
     * @see Weblication::setDefaultFormats()
     */
    public function getValueAsNumber(string $key, $default = null, $decimals = null): ?string
    {
        $value = $this->getValue($key, $default);
        if(!\is_null($value)) {
            $number = Weblication::getInstance()->getDefaultFormat('number');
            $value = \number_format($value, $decimals ?? $number['decimals'], $number['decimal_separator'],
                $number['thousands_separator']);
        }
        return $value;
    }

    /**
     * Returns a value of a field of the current record as an array
     */
    public function getValueAsArray(string $key, array $default = [], string $separator = ','): array
    {
        $value = $this->getValue($key, $default);
        if(!is_array($value)) {
            $value = \explode($separator, $value);
        }
        return $value;
    }

    /**
     * Sets a new/overwrites a value of a field in the result set.
     */
    public function setValue(string $key, mixed $value): RecordSet
    {
        if(!$this->count()) {
            return $this->addValue($key, $value);
        }
        $this->records[$this->index][$key] = $value;
        return $this;
    }

    /**
     * Add a value as a new record to the record set
     */
    public function addValue(string $key, mixed $value): RecordSet
    {
        $this->records[$this->count()][$key] = $value;
        $this->index = $this->count() - 1;
        return $this;
    }

    /**
     * Sets new/overwrites values of fields in the result set.
     */
    public function setValues(array $assoc): RecordSet
    {
        if(!$this->count()) {
            return $this->addValues($assoc);
        }
        $this->records[$this->index] = $assoc + $this->records[$this->index];
        return $this;
    }

    /**
     * Adds a new record into the record set.
     */
    public function addValues(array $assoc): RecordSet
    {
        $this->records[$this->count()] = $assoc;
        $this->index = $this->count() - 1;
        return $this;
    }

    /**
     * This is the equivalent of the setValue function, except that the fields are appended to the back of the array
     */
    public function addFields(array|string $key, string $value = ''): RecordSet
    {
        if(!$this->count())
            return is_array($key) ? $this->addValues($key) : $this->addValue($key, $value);
        $insert = is_array($key) ? $key : [$key => $value];
        $this->records[$this->index] = \array_merge($this->records[$this->index], $insert);
        return $this;
    }

    /**
     * Deletes a field (key) including its content (value) from the data record
     */
    public function delKey(string $key): static
    {
        unset($this->records[$this->index][$key]);
        return $this;
    }

    /**
     * Deletes a field (key) including its content (value) from all data records
     */
    public function delKeyFromAll(string $key): static
    {
        foreach($this->records as &$row) {
            unset($row[$key]);
        }
        return $this;
    }

    /**
     * Deletes fields (keys) including their content (values) from all data records
     *
     * @param array $keys
     * @return $this
     */
    public function delKeysFromAll(array $keys): static
    {
        foreach($this->records as &$row) {
            foreach($keys as $key) {
                unset($row[$key]);
            }
        }
        return $this;
    }

    /**
     * Changes the name of a key
     *
     * @param array $old_key old key name
     * @param array $new_key new key name
     * @return RecordSet
     */
    public function changeKeysFromAll(array $old_key, array $new_key): static
    {
        $oldCount = count($old_key);
        foreach($this->records as &$row) {
            for($k = 0; $k < $oldCount; $k++) {
                if(isset($row[$old_key[$k]])) {
                    $row[$new_key[$k]] = $row[$old_key[$k]];
                    unset($row[$old_key[$k]]);
                }
            }
        }
        return $this;
    }

    /**
     * Applies the callback to the records
     */
    public function applyCallbackToRows(callable $callback_function): static
    {
        $this->records = array_map($callback_function, $this->records);
        return $this;
    }

    /**
     * Fills a column over the entire result (Record Set) with a value (does not move the sentence pointer).
     *
     * @param string $key Schlüssel
     * @param string $value Wert
     * @return RecordSet
     */
    public function fillValues(string $key, string $value): static
    {
        $count = $this->count();
        for($i = 0; $i < $count; $i++) {
            $this->records[$i][$key] = $value;
        }
        return $this;
    }

    /**
     * Deletes a specified amount of records from the set. Iterator will be set to the record preceding the removed record(s).
     * Failure results in the Iterator being reset to -1
     *
     * @param int $amount number of records to delete including the selected.
     * @param integer $index start from this index defaults to the current position in the set
     * @return boolean success
     */
    public function deleteRows(int $amount = 1, int $index = -1): bool
    {
        if($index >= 0 && $index !== $this->index)
            $this->seek($index);
        if($this->index >= 0) {
            $end = $this->index + $amount - $amount / \abs($amount);
            if($end < 0 || $this->count() <= $end) return false;
            $index = \min($this->index, $end);
            \array_splice($this->records, $index, \abs($amount));
            $this->index = $index - 1;
            return true;
        }
        return false;
    }

    /**
     * Returns the complete result set as an indexed array in raw format.
     *
     * @return array Recordset
     */
    public function getRaw(): array
    {
        return $this->records;
    }

    /**
     * Prepends a record to the record set
     */
    public function prepend(array $record): static
    {
        \array_unshift($this->records, $record);
        $this->reset();
        return $this;
    }

    /**
     * Returns all values of a field name
     *
     * @param array|string $fieldNames
     * @param array|string $keyByFields
     * @param string $type type conversion (int, float, bool, string)
     * @return array Felddaten als Array z.B. array('Alex', 'Florian', 'Andreas')
     */
    public function getFieldData(array|string $fieldNames, array|string $keyByFields = '', string $type = ''): array
    {
        if(is_array($fieldNames)) {
            $keyByFields = (array)$keyByFields;
            if(!$keyByFields) {
                $keyFields = [];
            }
            else {
                $keyFields = (count($keyByFields) === count($fieldNames) ? $keyByFields :
                    throw new InvalidArgumentException('The number of key fields does not match the number of fields to be returned.'));
            }

            $result = $this->getMultipleFields($fieldNames, $keyFields);
        }
        else {
            $result = $this->getSingleField($fieldNames, $keyByFields, $type);
        }
        return $result;
    }

    /**
     * Returns an array of the values of multiple columns from a record set
     */
    private function getMultipleFields(array $fieldNames, array $keyByFields): array
    {
        $result = [];

        if(!$keyByFields) {
            foreach($fieldNames as $fieldName) {
                $result[$fieldName] = array_column($this->records, $fieldName);
            }
            return $result;
        }

        foreach($this->records as $row) {
            foreach($fieldNames as $index => $fieldName) {
                if(isset($row[$fieldName])) {
                    $keyField = $keyByFields[$index] ?? null;
                    if($keyField && isset($row[$keyField])) {
                        $key = $row[$keyField];
                        $result[$key] = $row[$fieldName];
                        continue; // Skip to the next record
                    }

                    $result[$fieldName][] = $row[$fieldName];
                }
            }
        }
        return $result;
    }

    /**
     * returns a single field stacked in an array
     */
    private function getSingleField(string $fieldName, string $keyByField, string $type): array
    {
        $result = [];
        foreach($this->records as $row) {
            if(!isset($row[$fieldName])) {
                continue;
            }
            $row[$fieldName] = match ($type) {
                'int' => (int)$row[$fieldName],
                'float' => (float)$row[$fieldName],
                'bool' => (bool)$row[$fieldName],
                'string' => (string)$row[$fieldName],
                default => $row[$fieldName],
            };
            if($keyByField) {
                $result[$row[$keyByField]] = $row[$fieldName];
            }
            else {
                $result[] = $row[$fieldName];
            }
        }
        return $result;
    }

    /**
     * Ermittle nächsten übereinstimmenden Datensatz. Kann nur in Verbindung mit "find" aufgerufen werden!
     *
     * @param string $fieldName zu suchender Feldname
     * @param string $value zu suchender Wert
     * @return int|bool Index oder False
     */
    public function findNext(string $fieldName, string $value): false|int
    {
        return $this->find($fieldName, $value, false);
    }

    /**
     * Searches column and value within the result set and returns index on success. Otherwise, false.
     *
     * @param string|array $fieldName field name
     * @param string|array $value value to search for
     * @param bool $begin start from the beginning
     * @return int|false index or false
     */
    public function find(string|array $fieldName, string|array $value, bool $begin = true): false|int
    {
        if($fieldName === '') {
            return false;
        }

        if($begin) {
            if(!$this->first()) {
                return false;
            }
        }
        else if(!$this->forward()) {
            return false;
        }

        // check multiple columns
        if(is_array($fieldName) && is_array($value)) {
            // Search until the value of the field matches or the end has been reached
            $len = count($fieldName) - 1;
            do {
                $found = false;
                for($i = 0; $i <= $len; $i++) {
                    $found = ($this->getValue($fieldName[$i]) == $value[$i]);
                    if(!$found) {
                        break;
                    }
                }
                if($found) {
                    return $this->index;
                }
            } while($this->forward());
        }
        // check only one column
        else {
            // Search until the value of the field matches or the end has been reached
            do {
                if($this->getValue($fieldName) == $value) {
                    return $this->index;
                }
            } while($this->forward());
        }

        return false;
    }

    /**
     * Goes to the first record
     */
    public function first(): array
    {
        $this->reset();
        return $this->getRecord();
    }

    /**
     * Forwards the index by one position until the end is reached
     *
     * @return bool true if the end is not reached yet, false otherwise
     */
    public function forward(): bool
    {
        if($this->index >= $this->count() - 1) {
            return false;
        }
        $this->index++;
        return true;
    }

    /**
     * Vergleicht ein Resultset, ob es identisch ist. Ist das Resultset nicht identisch, bleibt der Satzzeiger auf diesem stehen.
     *
     * @param RecordSet $ResultSet
     * @return boolean
     */
    public function isEqual(RecordSet $ResultSet): bool
    {
        if($this->count() !== $ResultSet->count()) return false;
        $this->first();
        $ResultSet->first();
        do {
            if(count(\array_diff_assoc($this->getRecord(), $ResultSet->getRecord())) !== 0 ||
                count(\array_diff_assoc($ResultSet->getRecord(), $this->getRecord())) !== 0) return false;
        } while($this->forward() and $ResultSet->forward());
        return true;
    }

    /**
     * Get the last error in the error stack. If there is no error, an empty array is returned.
     */
    public function getLastError(): array
    {
        $error = [];
        if($this->errorStack) {
            $error = $this->errorStack[count($this->errorStack) - 1];
        }
        return $error;
    }

    /**
     * Returns the error stack
     *
     * @return array
     */
    public function getErrorList(): array
    {
        return $this->errorStack;
    }

    /**
     * Add error message to the error stack
     */
    public function addErrorMessage(string $message, int $code = 0): static
    {
        $this->errorStack[] = ['message' => $message, 'code' => $code];
        return $this;
    }

    /**
     * Add the error to the error stack as an array with message and code
     */
    public function addError(array $error): static
    {
        $this->errorStack[] = $error;
        return $this;
    }

    /**
     * Clear the error stack
     */
    public function clearErrorStack(): static
    {
        $this->errorStack = [];
        return $this;
    }

    /**
     * Returns the result set in CSV format
     *
     * @access public
     * @param boolean $with_headline with header
     * @param string $separator column separator
     * @param string $line_break new line
     * @param string $text_clinch text clinch
     * @return string csv string
     */
    public function getCSV(bool $with_headline = true, string $separator = ';', string $line_break = "\n", string $text_clinch = '"'): string
    {
        $csv = '';
        if($this->count()) {
            if($with_headline) {
                $csv .= \implode($separator, array_keys($this->records[0])).$line_break;
            }
            foreach($this->records as $row) {
                $line = '';
                $values = array_values($row);
                foreach($values as $val) {
                    $val = self::maskTextCSVCompliant($val, $separator, $text_clinch);
                    $line .= ($line !== '') ? ($separator.$val) : ($val);
                }
                $csv .= $line.$line_break;
            }
        }
        return $csv;
    }

    /**
     * Maskiere Text CSV Konform
     *
     * @param string $val Wert
     * @param string $separator Trenner
     * @param string $text_clinch Zeichen für Textklammer
     * @return string
     */
    public static function maskTextCSVCompliant(string $val, string $separator = ';', string $text_clinch = '"'): string
    {
        $hasTextClinch = false;
        if($text_clinch !== '') {
            $hasTextClinch = \strpos($val, $text_clinch);
        }
        if($hasTextClinch !== false) {
            $val = \str_replace($text_clinch, $text_clinch.$text_clinch, $val);
        }
        if($hasTextClinch !== false || str_contains($val, $separator) || str_contains($val, \chr(10)) || str_contains($val, \chr(13))) {
            $val = $text_clinch.$val.$text_clinch;
        }
        return $val;
    }

    /**
     * Sets the fields, or the order of the fields to be returned
     *
     * @param array $fields
     * @see RecordSet::getRecordAsCSV()
     */
    public function setReturnFields(array $fields): void
    {
        $this->returnFields = $fields;
    }

    /**
     * Zeile als CSV ausgeben
     */
    public function getRecordAsCSV(bool $with_headline = true, string $separator = ';', string $line_break = "\n", string $text_clinch = '"'): string
    {
        $csv = '';
        if($this->count()) {
            if($this->returnFields) {
                if($with_headline) $csv .= \implode($separator, array_values(($this->returnFields))).$line_break;
                $row = '';
                foreach($this->returnFields as $key) {
                    if($row !== '') $row .= $separator;
                    $val = self::maskTextCSVCompliant((string)$this->records[$this->index][$key], $separator, $text_clinch);
                    $row .= $val;
                }
                $row .= $line_break;

                $csv .= $row;
            }
            else {
                if($with_headline) {
                    $csv .= \implode($separator, array_keys($this->records[$this->index])).$line_break;
                }

                $values = array_values($this->getRecord());
                $i = 0;
                foreach($values as $val) {
                    $val = self::maskTextCSVCompliant((string)$val, $separator, $text_clinch);
                    $csv .= ($i === 0) ? $val : $separator.$val;
                    $i++;
                }
                $csv .= $line_break;
            }
        }
        return $csv;
    }

    /**
     * Returns records as json
     *
     * @throws \JsonException
     */
    public function getRecordsAsJSON(int $flags, int $depth = 512): string
    {
        return \json_encode($this->records, \JSON_THROW_ON_ERROR | $flags, $depth);
    }

    /**
     * Zeile als Ini Werte ausgeben
     *
     * @param string $key_value_separator
     * @param string $separator
     * @return string
     */
    public function getRecordAsIni(string $key_value_separator = '=', string $separator = "\n"/*, $text_clinch=''*/): string
    {
        $string = '';
        if($this->records) {
            foreach($this->records[$this->index] as $key => $val) {
                if($string !== '') $string .= $separator;
                $string .= $key.$key_value_separator.$val;
            }
        }
        return $string;
    }

    /**
     * Creates data format for the bootstrap table
     *
     * @param int $total
     * @param int|null $totalNotFiltered (optional) Use totalNotFilteredField parameter to set the field from the json response which will used for
     *     showExtendedPagination
     * @return array
     */
    public function getRecordsAsBSTable(int $total, int $totalNotFiltered = null): array
    {
        // @todo move into GUI_Table and get Keys from Configuration e.g. https://bootstrap-table.com/docs/api/table-options/#datafield
        $return = [];
        $return['total'] = $total;
        $return['totalNotFiltered'] = $totalNotFiltered ?? $total;
        $return['rows'] = $this->records;
        return $return;
    }

    /**
     * Returns all data in XML format for the JS component DHtmlXGrid
     *
     * @param array $pk
     * @param int $total_count
     * @param int $pos
     * @param boolean $without_pk
     * @param string $encoding
     * @param boolean $encode
     * @param array $callbackRow
     * @param array $callbackCell
     * @return string
     * @todo move into GUI_Table for dhtmlxGrid
     */
    public function getRecordsAsXGrid(array $pk, int $total_count, int $pos = 0, bool $without_pk = true, string $encoding = 'ISO-8859-1',
        bool $encode = false, array $callbackRow = [], array $callbackCell = []): string
    {
        $xml = '<?xml version=\'1.0\' encoding=\''.$encoding.'\'?>';
        $xml .= '<rows total_count=\''.$total_count.'\' pos=\''.$pos.'\'>';
        $count = $this->count();
        if($count) {
            if($this->returnFields) {
                $keys = $this->returnFields;
            }
            else {
                $keys = array_keys($this->records[0]);
            }

            // Primärschlüssel entfernen (AM, 18.11.2010, optimiert)
            if($without_pk) {
                $keys = \array_diff($keys, $pk);
            }

            //					foreach($keys as $key => $val) {
            //						$xml .= '<column width="50" type="ro">'.$val.'</column>';
            //					}

            $z = 1;
            foreach($this->records as $row) {
                $id = '';
                foreach($pk as $val) {
                    if($id !== '') $id .= '-';
                    $id .= $row[$val];
                }
                $rowSettings = '';
                if($callbackRow) {
                    $rowSettings = ' '.$callbackRow[0]->$callbackRow[1]($id, $row, $z, $count);
                }
                $xml .= '<row id=\''.$id.'\''.$rowSettings.'>';

                foreach($keys as $key) {
                    $val = $row[$key];

                    $cellSettings = '';
                    if($callbackCell) $cellSettings = ' '.$callbackCell[0]->$callbackCell[1]($key, $val, $row, $z, $count);
                    $xml .= '<cell'.$cellSettings.'>';
                    if(\is_numeric($val)) {
                        $xml .= $val;
                    }
                    else {
                        // Any encoding to UTF-8 using multibyte string: mb_convert_encoding($string, 'UTF-8', mb_list_encodings());
                        $xml .= '<![CDATA['.\str_replace('&', '&amp;', ($encode) ? UConverter::transcode($val, 'UTF8', 'ISO-8859-1') : $val).']]>';
                    }
                    $xml .= '</cell>';
                }

                $xml .= '</row>'.\chr(10);
                $z++;
            }
        }
        $xml .= '</rows>';
        return $xml;
    }

    /**
     * Returns all data in XML format for the JS component dhtmlXCombo
     *
     * @param array $pkAsValue primary key
     * @param string $fieldNameAsOption field name as option text
     * @param boolean|null $add [optional]
     * @param string $encoding
     * @return string
     * @todo move into GUI_Combo for dhtmlxCombo
     */
    public function getRecordsAsXCombo(array $pkAsValue, string $fieldNameAsOption, ?bool $add = null, string $encoding = 'ISO-8859-1'): string
    {
        /*$xml = getXmlHeader('utf8');*/
        $xml = '<?xml version=\'1.0\' encoding=\''.$encoding.'\'?>';
        if(\is_null($add)) {
            $xml .= '<complete>';
        }
        else {
            $xml .= '<complete add="'.\bool2string($add).'">';
        }
        if($this->count()) {
            foreach($this->records as $row) {
                $id = '';
                foreach($pkAsValue as $val) {
                    if($id !== '') $id .= '-';
                    $id .= $row[$val];
                }
                if(!isset($row[$fieldNameAsOption])) break;
                $img_src = '';
                if(isset($row['img_src'])) $img_src = $row['img_src'];
                $selected = '';
                if(isset($row['selected'])) $selected = $row['selected'];
                $checked = '';
                if(isset($row['checked'])) $checked = $row['checked'];
                $css = '';
                if(isset($row['css'])) $css = $row['css'];

                $xml .= '<option value="'.$id.'"';
                if($css) $xml .= ' css="'.$css.'"';
                if($img_src) $xml .= ' img_src="'.$img_src.'"';
                if($checked) $xml .= ' checked="'.$checked.'"';
                if($selected) $xml .= ' selected="'.$selected.'"';
                //$xml .= '<option value="'.$id.'">a</option>';
                $xml .= '><![CDATA['.(\str_replace('&', '&amp;', $row[$fieldNameAsOption])).']]></option>';
            }
        }

        $xml .= '</complete>';
        return $xml;
    }

    public function dump(): void
    {
        echo '<pre>';
        /** @noinspection ForgottenDebugOutputInspection */
        \var_dump($this->records);
        echo '</pre>';
    }
}