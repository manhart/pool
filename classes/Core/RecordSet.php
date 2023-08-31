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
use pool\classes\Exception\InvalidJsonException;
use UConverter;

/**
 * Class pool\classes\Core\ResultSet
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class RecordSet extends PoolObject implements Countable
{
    /**
     * @var array records
     */
    protected array $records = [];

    /**
     * @var int internal pointer
     */
    private int $index = -1;

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
        if($records) $this->reset();
    }

    /**
     * Sortiert eine oder mehrere Spalten.
     * Beispiel Code-Schnipsel:
     * <code>
     * // einspaltig
     * $Resultset -> sort('vorname', SORT_DESC, SORT_STRING);
     * // mehrspaltig
     * $Resultset -> sort(array(array('nachname', SORT_ASC, SORT_STRING), array('vorname', SORT_ASC, SORT_STRING)));
     * </code>
     */
    public function sort(array|string $column, int $sort = SORT_ASC, int $sortType = SORT_REGULAR): void
    {
        if(!$this->records) return;

        if(is_string($column)) {
            $sortarr = array_column($this->records, $column);
            if($sortType == SORT_STRING) {
                $sortarr = array_map('strtolower', $sortarr);
            }

            array_multisort($sortarr, $sort, $sortType, $this->records);
        }
        else {
            $params = '';
            $z = 0;
            foreach($this->records as $row) {
                foreach($column as $col) {
                    $col_name = $col[0];
                    if($z == 0) {
                        if($params != '') $params .= ', ';
                        $params .= "\$sortarr['$col_name']";
                        $params .= ', '.($col[1] ?? $sort);
                        $params .= ', '.($col[2] ?? $sortType);
                    }
                    $sortarr[$col_name][] = $row[$col_name];
                    if($sortType == SORT_STRING) {
                        $sortarr[$col_name] = array_map('strtolower', $sortarr[$col_name]);
                    }
                }
                $z++;
            }

            // echo "array_multisort($params, \$this -> rowset);";
            eval ("array_multisort($params, \$this->rowset);");
        }
    }

    /**
     * Sort an array by values using a user-defined comparison function
     *
     * @param callable $cmp_function
     */
    public function usort(callable $cmp_function): void
    {
        usort($this->records, $cmp_function);
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
        $this->records = array_values(array_filter($this->records, $callback, $mode));
    }

    /**
     * Verschiebt einen Datensatz innerhalb des Resultsets an die uebergebene Position.
     *
     * @param string $fieldname Feldname
     * @param mixed $unique_value Eindeutiger Wert
     * @param integer $pos Position wohin der Datensatz verschoben werden soll (beginnend mit 1)
     * @return boolean
     */
    public function move(string $fieldname, mixed $unique_value, int $pos): bool
    {
        $ok = false;
        $new_rowSet = [];
        $count = count($this->records);
        $z = -1;
        $pos = ($pos - 1);
        for($r = 0; $r < $count; $r++) {
            $row = $this->records[$r];
            if($row[$fieldname] == $unique_value) {
                if($r == 0 and $pos == 0) {
                    return true;
                }
                $new_rowSet[$pos] = $row;
                $ok = true;
            }
            else {
                if(($z + 1) == $pos) {
                    $z += 2;
                }
                else {
                    $z++;
                }
                $new_rowSet[$z] = $row;
            }
        }
        if($ok) {
            $this->records = $new_rowSet;
        }
        return $ok;
    }

    /**
     * Liefert alle Spalten der Ergebnismenge.
     *
     * @return array alle Spalten der Ergebnismenge, wenn keine Daten da sind, wird false zurueck gegeben.
     */
    public function getColumns(): array
    {
        if($this->count() > 0) {
            return array_keys($this->records[$this->index]);
        }
        else {
            return [];
        }
    }

    /**
     * reset pointer to first record
     *
     * @return int
     */
    public function reset(): int
    {
        $this->index = $this->count() > 0 ? 0 : -1;
        return $this->index;
    }

    /**
     * Setzt den internen Zeiger auf den ersten Datensatz zurueck (Iterator).
     *
     * @return array Datensatz (oder false; wenn es keine Datensaetze gibt)
     */
    public function first(): array
    {
        $this->reset();
        return $this->getRecord();
    }

    /**
     * Fraegt ab, ob es sich um den ersten Datensatz handelt.
     *
     * @return boolean Ergebnisstatus
     **/
    public function isFirst(): bool
    {
        return $this->index == 0;
    }

    /**
     * Setzt den internen Zeiger auf den letzten Datensatz (Iterator).
     *
     * @return array Datensatz als Array oder FALSE
     **/
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
     * Fraegt ab, ob es sich um den letzten Datensatz handelt.
     *
     * @return boolean Erfolgsstatus
     */
    public function isLast(): bool
    {
        return $this->index == ($this->count() - 1);
    }

    /**
     * Liefert den aktuellen Datensatz
     *
     * @return array Datensatz
     */
    public function current(): array
    {
        return $this->getRecord();
    }

    /**
     * Sucht einen Datensatz  (Iterator).
     *
     * @param integer $index Record-Offset
     * @return array Datensatz
     **/
    public function seek(int $index): array
    {
        $this->index = $index < $this->count() ? $index : -1;
        return $this->getRecord();
    }

    /**
     * current pointer position  (Iterator).
     *
     * @return int (beginnend bei 0 fuer den ersten Datensatz; -1 entspricht einer leeren Ergebnismenge)
     **/
    public function pos(): int
    {
        return $this->index;
    }

    /**
     * Advances the index by one position until the end is reached
     *
     * @return array the then current dataset, or an empty array if the end is already reached
     * @see self::getRecord()
     */
    public function next(): array
    {
        if($this->index >= $this->count() - 1)
            return [];
        $this->index++;
        return $this->getRecord();
    }

    /**
     * Rewinds the index by one position until the beginning is reached
     *
     * @return array the then current dataset, or an empty array if the beginning is already reached
     * @see self::getRecord()
     */
    public function prev(): array
    {
        if($this->index <= 0)
            return [];
        $this->index--;
        return $this->getRecord();
    }

    /**
     * Returns the number of records in the pool\classes\Core\ResultSet
     */
    public function count(): int
    {
        return count($this->records);
    }

    /**
     * Returns a value of a field of the current record
     *
     * @param string $key name of column (fieldname)
     * @param mixed|null $default
     * @return mixed value
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        if(!array_key_exists($key, $this->records[$this->index] ?? [])) return $default;
        return $this->records[$this->index][$key];
    }

    /**
     * Returns a value of a field of the current record as a string
     *
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getValueAsString(string $key, string $default = ''): string
    {
        return (string)$this->getValue($key, $default);
    }

    /**
     * Returns a value of a field of the current record as a decoded json
     *
     * @param string $key
     * @param string $defaultJson
     * @return string
     * @throws InvalidJsonException
     */
    public function getValueAsJson(string $key, string $defaultJson = '{}'): mixed
    {
        $json = (string)$this->getValue($key, $defaultJson) ?: $defaultJson;
        if(!isValidJSON($json)) {
            throw new InvalidJsonException();
        }
        return json_decode($json, true);
    }

    /**
     * Returns a value of a field of the current record as an integer. It is also possible to return null as default value.
     *
     * @param string $key
     * @param int|null $default
     * @return int|null
     */
    public function getValueAsInt(string $key, ?int $default = 0): ?int
    {
        $value = $this->getValue($key, $default);
        if($value === null) return $default;
        return (int)$value;
    }

    /**
     * Returns a value of a field of the current record as a float
     *
     * @param string $key
     * @param float|null $default
     * @return float|null
     */
    public function getValueAsFloat(string $key, ?float $default = 0.00): ?float
    {
        $value = $this->getValue($key, $default);
        if($value === null) return $default;
        return (float)$value;
    }

    /**
     * Returns a value of a field of the current record as a boolean
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getValueAsBool(string $key, bool $default = false): bool
    {
        return boolval($this->getValue($key, $default));
    }

    /**
     * Returns a value of a field of the current record as DateTime object
     *
     * @param string $key
     * @param null $default
     * @param DateTimeZone|null $timezone
     * @return DateTime|null
     */
    public function getValueAsDateTime(string $key, $default = null, ?DateTimeZone $timezone = null): ?DateTime
    {
        $value = $this->getValue($key, $default);
        if($value instanceof DateTime)
            return $value;
        if($value && $value !== '0000-00-00' && $value !== '0000-00-00 00:00:00') {
            if(!str_contains($value, '-') && is_numeric($value))
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
     * Returns a value of a field of the current record as formatted date string
     *
     * @param string $key
     * @param null $default
     * @return string|null
     * @throws Exception
     */
    public function getValueAsFormattedDate(string $key, $default = null): ?string
    {
        $DateTime = $this->getValueAsDateTime($key, $default);
        if(is_null($DateTime)) return null;
        $format = Weblication::getInstance()->getDefaultFormat('php.date');
        return $DateTime->format($format);
    }

    /**
     * Returns a value of a field of the current record as formatted date.time string
     *
     * @param string $key
     * @param null $default
     * @param bool $seconds
     * @return string|null
     * @throws Exception
     */
    public function getValueAsFormattedDateTime(string $key, $default = null, bool $seconds = true): ?string
    {
        $DateTime = $this->getValueAsDateTime($key, $default);
        if(is_null($DateTime)) return null;
        $format = Weblication::getInstance()->getDefaultFormat('php.date.time'.($seconds ? '.sec' : ''));
        return $DateTime->format($format);
    }

    /**
     * Returns a value of a field of the current record as formatted number
     *
     * @param string $key
     * @param null $default
     * @param null $decimals
     * @return mixed|string
     * @see Weblication::setDefaultFormats()
     */
    public function getValueAsNumber(string $key, $default = null, $decimals = null): ?string
    {
        $value = $this->getValue($key, $default);
        if(!is_null($value)) {
            $number = Weblication::getInstance()->getDefaultFormat('number');
            $value = number_format($value, $decimals ?? $number['decimals'], $number['decimal_separator'],
                $number['thousands_separator']);
        }
        return $value;
    }

    /**
     * Returns a value of a field of the current record as an array
     *
     * @param string $key
     * @param array $default
     * @param string $separator
     * @return array
     */
    public function getValueAsArray(string $key, array $default = [], string $separator = ','): array
    {
        $value = $this->getValue($key, $default);
        if(!is_array($value)) {
            $value = explode($separator, $value);
        }
        return $value;
    }

    /**
     * Sets a new/overwrites a value of a field in the result set.
     *
     * @param string $key name of key/field
     * @param mixed $value value
     * @return $this
     */
    public function setValue(string $key, mixed $value): RecordSet
    {
        if($this->count() == 0) {
            return $this->addValue($key, $value);
        }
        $this->records[$this->index][$key] = $value;
        return $this;
    }

    /**
     * Sets new/overwrites values of fields in the result set.
     *
     * @param array $assoc
     * @return $this
     */
    public function setValues(array $assoc): RecordSet
    {
        if($this->count() == 0)
            return $this->addValues($assoc);
        $this->records[$this->index] = $assoc + $this->records[$this->index];
        return $this;
    }

    /**
     * Das ist das equivalent zur Funktion setValue, nur dass die Felder hinten an das Array angefuegt werden
     *
     * @param array|string $key Spaltenname oder Array[Spalte] = Wert
     * @param string $value Wert des neuen Feldes
     */
    public function addFields(array|string $key, string $value = ''): RecordSet
    {
        if($this->count() == 0)
            return is_array($key) ? $this->addValues($key) : $this->addValue($key, $value);
        $insert = is_array($key) ? $key : [$key => $value];
        $this->records[$this->index] = array_merge($this->records[$this->index], $insert);
        return $this;
    }

    /**
     * Fuegt einen neuen Datensatz in die Ergebnismenge ein.
     *
     * @param string $key Schluessel (bzw. Name des Feldes)
     * @param mixed $value Wert der Variable
     */
    public function addValue(string $key, mixed $value): RecordSet
    {
        $this->records[$this->count()][$key] = $value;
        $this->index = $this->count() - 1;
        return $this;
    }

    /**
     * @param array $assoc
     * @return RecordSet
     */
    public function addValues(array $assoc): RecordSet
    {
        $this->records[$this->count()] = $assoc;
        $this->index = $this->count() - 1;
        return $this;
    }

    /**
     * Deletes a field (key) including its content (value) from the data record
     *
     * @param string $key
     * @return RecordSet
     */
    public function delKey(string $key): static
    {
        unset($this->records[$this->index][$key]);
        return $this;
    }

    /**
     * Deletes a field (key) including its content (value) from all data records
     *
     * @param string $key
     * @return $this
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
     * Füllt eine Spalte über das ganze Ergebnis (Rowset) mit einem Wert (verschiebt nicht den Satzzeiger).
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
     * Returns the current record (dataset) or an empty array if the position (index) is out of range
     *
     * @see static::seek()
     */
    public function getRecord(int $index = -1): array
    {
        return $index >= 0 && $index != $this->index ?
            $this->seek($index)
            : $this->records[$this->index] ?? [];
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
        if($index >= 0 && $index != $this->index)
            $this->seek($index);
        if($this->index >= 0) {
            $end = $this->index + $amount - $amount / abs($amount);
            if($end < 0 || $this->count() <= $end) return false;
            $index = min($this->index, $end);
            array_splice($this->records, $index, abs($amount));
            $this->index = $index - 1;
            return true;
        }
        return false;
    }

    /**
     * Liefert die komplette Ergebnismenge als indiziertes Array (enthaelt je Satz ein assoziatives Array mit Feldnamen) zurueck.
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
        array_unshift($this->records, $record);
        $this->reset();
        return $this;
    }

    /**
     * Entfernt einen Teil der Ergebnismenge und ersetzt ihn optional durch etwas anderes.
     * Die Funktion entfernt die durch offset und length angegebenen Elemente der Ergebnismenge,
     * und ersetzt diese durch die Elemente des Arrays replacement, wenn angegeben und gibt ein
     * Array mit den entfernten Elemente zurueck
     *
     * @param int $offset Ist offset positiv, beginnt der zu entfernende Bereich bei diesem Offset vom Anfang der Ergebnismenge. Ist offset negativ,
     *     beginnt der zu entfernende Bereich offset Elemente vor dem Ende der Ergebnismenge.
     * @param int|null $length Ist length nicht angegeben, wird alles von offset bis zum Ende der Ergebnismenge entfernt. Ist length positiv, wird die
     *     angegebene Anzahl Elemente entfernt. Ist length negativ, dann wird der Bereich von length Elementen vor dem Ende, bis zum Ende der
     *     Ergebnismenge entfernt. Tipp: Um alles von offset bis zum Ende der Ergebenismenge zu entfernen wenn replacement ebenfalls angegeben ist,
     *     verwenden Sie Resultset::count() als length. (optional)
     * @param array $replacement Ist das Array replacement angegeben, werden die entfernten Elemente durch die Elemente dieses Arrays ersetzt. Sind offset
     *     und length so angegeben dass nichts entfernt wird, werden die Elemente von replacement an der von offset spezifizierten Stelle eingefuegt.
     *     Tipp: Soll die Ersetzung durch nur ein Element erfolgen ist es nicht noetig ein Array zu anzugeben es sei denn, dieses Element ist selbst ein
     *     Array. (optional)
     * @return array Gibt das Array mit den entfernten Element zurueck.
     */
    public function spliceRowSet(int $offset, ?int $length = null, array $replacement = []): array
    {
        return array_splice($this->records, $offset, $length, $replacement);
    }

    /**
     * Liefert alle Werte eines Feldes bzw. einer Tabellenspalte zurueck.
     *
     * @param array|string $fieldName Feldname bzw. Spaltenname
     * @param string $fieldNameAsKey dieses Feld als Schlüssel
     * @param string $type type conversion (int, float, bool, string)
     * @return array Felddaten als Array z.B. array('Alex', 'Florian', 'Andreas')
     */
    public function getFieldData(array|string $fieldName, string $fieldNameAsKey = '', string $type = ''): array
    {
        $arrResult = [];
        if(is_array($fieldName)) {
            $fieldName = array_flip($fieldName);
            foreach($this->records as $row) {
                $record = array_intersect_key($row, $fieldName);
                $arrResult[] = $record;
            }
        }
        else {
            foreach($this->records as $row) {
                if(isset($row[$fieldName])) {
                    $row[$fieldName] = match ($type) {
                        'int' => (int)$row[$fieldName],
                        'float' => (float)$row[$fieldName],
                        'bool' => (bool)$row[$fieldName],
                        'string' => (string)$row[$fieldName],
                        default => $row[$fieldName],
                    };
                    if($fieldNameAsKey) {
                        $arrResult[$row[$fieldNameAsKey]] = $row[$fieldName];
                    }
                    else {
                        $arrResult[] = $row[$fieldName];
                    }
                }
            }
        }
        return $arrResult;
    }

    /**
     * Sucht Spalte und Wert innerhalb der Ergebnismenge und liefert bei Erfolg Index zurueck. Andernfalls false.
     *
     * @param string|array $fieldName Spaltenname
     * @param string|array $value Wert
     * @param bool $begin True=beginnt mit der Suche ab ersten Datensatz, False=beginnt mit der Suche ab dem aktuellen Datensatz
     * @return int|false Index oder False
     */
    public function find(string|array $fieldName, string|array $value, bool $begin = true): false|int
    {
        if($fieldName == '') return false;

        if($begin) {
            if(!$this->first()) return false;
        }
        else {
            if(!$this->next()) return false;
        }

        // Mehrere Spalten überprüfen (Array-Übergabe)
        if(is_array($fieldName) and is_array($value)) {
            // Suche solange bis der Wert des Feldes übereinstimmt oder das Ende erreicht wurde
            $len = sizeof($fieldName) - 1;
            do {
                $found = false;
                for($i = 0; $i <= $len; $i++) {
                    $found = ($this->getValue($fieldName[$i]) == $value[$i]);
                    if(!$found) break;
                }
                if($found) {
                    return $this->index;
                }
            } while($this->next());
        }
        // Eine Spalte überprüfen
        else {
            // Suche solange bis der Wert des Feldes übereinstimmt oder das Ende erreicht wurde
            do {
                if($this->getValue($fieldName) == $value) {
                    return $this->index;
                }
            } while($this->next());
        }

        return false;
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
     * Vergleicht ein Resultset, ob es identisch ist. Ist das Resultset nicht identisch, bleibt der Satzzeiger auf diesem stehen.
     *
     * @param RecordSet $ResultSet
     * @return boolean
     */
    public function isEqual(RecordSet $ResultSet): bool
    {
        if($this->count() != $ResultSet->count()) return false;
        $this->first();
        $ResultSet->first();
        do {
            if(count(array_diff_assoc($this->getRecord(), $ResultSet->getRecord())) != 0 or
                count(array_diff_assoc($ResultSet->getRecord(), $this->getRecord())) != 0) return false;
        } while($this->next() and $ResultSet->next());
        return true;
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
    function getCSV(bool $with_headline = true, string $separator = ';', string $line_break = "\n", string $text_clinch = '"'): string
    {
        $csv = '';
        if($this->count()) {
            if($with_headline) {
                $csv .= implode($separator, array_keys($this->records[0])).$line_break;
            }
            foreach($this->records as $row) {
                $line = '';
                $values = array_values($row);
                foreach($values as $val) {
                    $val = self::maskTextCSVcompliant($val, $separator, $text_clinch);
                    $line .= ($line != '') ? ($separator.$val) : ($val);
                }
                $csv .= $line.$line_break;
            }
        }
        return $csv;
    }

    /**
     * Setzt die Felder, bzw. die Reihenfolge der Felder die zurückegegeben werden soll
     *
     * @param array $fields
     */
    public function setReturnFields(array $fields): void
    {
        $this->returnFields = $fields;
    }

    /**
     * Zeile als CSV ausgeben
     *
     * @param boolean $with_headline
     * @param string $separator
     * @param string $line_break
     * @param string $text_clinch Textklammer
     * @return string
     */
    function getRecordAsCSV(bool $with_headline = true, string $separator = ';', string $line_break = "\n", string $text_clinch = '"'): string
    {
        $csv = '';
        if($this->count()) {
            if($this->returnFields) {
                if($with_headline) $csv .= implode($separator, array_values(($this->returnFields))).$line_break;
                $row = '';
                foreach($this->returnFields as $key) {
                    if($row != '') $row .= $separator;
                    $val = self::maskTextCSVcompliant((string)$this->records[$this->index][$key], $separator, $text_clinch);
                    $row .= $val;
                }
                $row .= $line_break;

                $csv .= $row;
            }
            else {
                if($with_headline) {
                    $csv .= implode($separator, array_keys($this->records[$this->index])).$line_break;
                }

                $values = array_values($this->getRecord());
                $i = 0;
                foreach($values as $val) {
                    $val = self::maskTextCSVcompliant((string)$val, $separator, $text_clinch);
                    $csv .= ($i == 0) ? $val : $separator.$val;
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
     * @param int $flags
     * @param int $depth
     * @return string
     */
    public function getRecordsAsJSON(int $flags, int $depth = 512): string
    {
        return json_encode($this->records, $flags, $depth);
    }

    /**
     * Maskiere Text CSV Konform
     *
     * @param string $val Wert
     * @param string $separator Trenner
     * @param string $text_clinch Zeichen für Textklammer
     * @return string
     */
    static function maskTextCSVcompliant(string $val, string $separator = ';', string $text_clinch = '"'): string
    {
        $hasTextClinch = false;
        if($text_clinch != '') {
            $hasTextClinch = strpos($val, $text_clinch);
        }
        if($hasTextClinch !== false) {
            $val = str_replace($text_clinch, $text_clinch.$text_clinch, $val);
        }
        if($hasTextClinch !== false or str_contains($val, $separator) or str_contains($val, chr(10)) or str_contains($val, chr(13))) {
            $val = $text_clinch.$val.$text_clinch;
        }
        return $val;
    }

    /**
     * Zeile als Ini Werte ausgeben
     *
     * @param string $key_value_separator
     * @param string $separator
     * @return string
     */
    function getRecordAsIni(string $key_value_separator = '=', string $separator = "\n"/*, $text_clinch=''*/): string
    {
        $string = '';
        if($this->records) {
            foreach($this->records[$this->index] as $key => $val) {
                if($string != '') $string .= $separator;
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
     * Liefert alle Daten im XML Format fuer die JS Komponente DHtmlXGrid
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
    public function getRowsetAsXGrid(array $pk, int $total_count, int $pos = 0, bool $without_pk = true, string $encoding = 'ISO-8859-1',
        bool $encode = false, array $callbackRow = [], array $callbackCell = []): string
    {
        $xml = '<?xml version=\'1.0\' encoding=\''.$encoding.'\'?>';
        $xml .= '<rows total_count=\''.$total_count.'\' pos=\''.$pos.'\'>';
        /*				if($row = $this->getRow()) {
                        $xml .= '<head>';
                        foreach($row as $key => $val) {
                            $xml .= '<column type="dyn" width="50">'.$key.'</column>';
                        }
                        $xml .= '</head>';
                    }*/
        $count = $this->count();
        if($count) {
            // Schluessel bzw. Felder im Voraus ermitteln
            if($this->returnFields) {
                $keys = $this->returnFields;
            }
            else {
                $keys = array_keys($this->records[0]);
            }

            // Primärschlüssel entfernen (AM, 18.11.2010, optimiert)
            if($without_pk) {
                $keys = array_diff($keys, $pk);
            }

            //					foreach($keys as $key => $val) {
            //						$xml .= '<column width="50" type="ro">'.$val.'</column>';
            //					}

            $z = 1;
            foreach($this->records as $row) {
                $id = '';
                foreach($pk as $val) {
                    if($id != '') $id .= '-';
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
                    if(is_numeric($val)) {
                        $xml .= $val;
                    }
                    else {
                        // Any encoding to UTF-8 using mbstring: mb_convert_encoding($string, 'UTF-8', mb_list_encodings());
                        $xml .= '<![CDATA['.str_replace('&', '&amp;', ($encode) ? UConverter::transcode($val, 'UTF8', 'ISO-8859-1') : $val).']]>';
                    }
                    $xml .= '</cell>';
                }

                $xml .= '</row>'.chr(10);
                $z++;
            }
        }
        $xml .= '</rows>';
        return $xml;
    }

    /**
     * Liefert alle Daten im XML Format fuer die JS Komponente dhtmlXCombo
     *
     * @param array $pkAsValue Primärschlüssel
     * @param string $fieldnameAsOption Feldname als Option-Text
     * @param boolean|null $add [optional]
     * @param string $encoding
     * @return string
     * @todo move into GUI_Combo for dhtmlxCombo
     */
    public function getRowsetAsXCombo(array $pkAsValue, string $fieldnameAsOption, ?bool $add = null, string $encoding = 'ISO-8859-1'): string
    {
        /*$xml = getXmlHeader('utf8');*/
        $xml = '<?xml version=\'1.0\' encoding=\''.$encoding.'\'?>';
        if(is_null($add)) {
            $xml .= '<complete>';
        }
        else {
            $xml .= '<complete add="'.bool2string($add).'">';
        }
        if($this->count()) {
            foreach($this->records as $row) {
                $id = '';
                foreach($pkAsValue as $val) {
                    if($id != '') $id .= '-';
                    $id .= $row[$val];
                }
                if(!isset($row[$fieldnameAsOption])) break;
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
                $xml .= '><![CDATA['.(str_replace('&', '&amp;', $row[$fieldnameAsOption])).']]></option>';
            }
        }

        $xml .= '</complete>';
        return $xml;
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
}