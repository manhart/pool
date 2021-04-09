<?php
/**
* -= Rapid Module Library (RML) =-
*
* Resultset.class.php
*
* Resultsets als Mittel zur komfortablen Suche. Resultsets verwalten effektiv Mengen von Datensaetze.
* Ein Resultset besteht aus einem Container (Array) und Iteratoren. Ein kleiner Filter und eine Sortiermethode verfeinern
* die Ergebnisse im Container.
*
* Resultsets werden im Zusammenhang mit Data Access Objects benutzt. Data Access Objekte liefern immer ein Resultset als
* Ergebnis zurueck (egal ob dahinter eine MySQL, PostgreSQL, XML Datenbank steckt).
*
* @date $Date: 2007/08/06 11:59:39 $
* @version $Id: Resultset.class.php,v 1.34 2007/08/06 11:59:39 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-07-10
* @author Alexander Manhart <alexander@manhart.bayern>
* @link https://alexander-manhart.de
*/

if(!defined('CLASS_RESULTSET')) {

    define('CLASS_RESULTSET', 1); 	// Prevent multiple loading

    /**
     * Resultset
     *
     * Abstrakte Klasse Resultset als Mittel zur komfortablen Datenverwaltung.
     *
     * @package pool
     * @access public
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: Resultset.class.php,v 1.34 2007/08/06 11:59:39 manhart Exp $
     * @access public
     **/
    class Resultset extends PoolObject implements Countable
    {
        //@var array Entitaetsmenge (Sammlung von Datensaetzen)
        //@access protected
        var $rowset = array();

        /**
         * Interner Zeiger (Index)
         *
         * @access private
         * @var int
         */
        var $index = -1;

        /**
         * Fehlerstapel
         *
         * @access private
         * @var array
         */
        var $errorStack = array();

        /**
         * Felder bzw. Reihenfolge der Felder (beeinflusst Ausgabe der Arrays), die zurueck gegeben werden sollen
         *
         * @var null|array
         */
        var $fields = null;

        /**
         * Sortiert eine oder mehrere Spalten.
         *
         * Beispiel Code-Schnipsel:
         * <code>
         * // einspaltig
         * $Resultset -> sort('vorname', SORT_DESC, SORT_STRING);
         * // mehrspaltig
         * $Resultset -> sort(array(array('nachname', SORT_ASC, SORT_STRING), array('vorname', SORT_ASC, SORT_STRING)));
         * </code>
         *
         * @access public
         * @param string $column Spaltenname oder Array.
         * @param mixed $sort PHP Konstante SORT_ASC oder SORT_DESC
         * @param mixed $sorttype PHP Konstante SORT_REGULAR, SORT_NUMERIC, SORT_STRING
         **/
        function sort($column, $sort=SORT_ASC, $sorttype=SORT_REGULAR)
        {
            if (count($this -> rowset)) {
                if (!is_array($column)) {

                    $sortarr = array_column($this->rowset, $column);
                    if ($sorttype == SORT_STRING) {
                        $sortarr = array_map('strtolower', $sortarr);
                    }

                    array_multisort($sortarr, $sort, $sorttype, $this->rowset);
                }
                else {
                    $params = '';
                    $z=0;
                    foreach($this -> rowset as $row) {
                        foreach($column as $col) {
                            $col_name = $col[0];
                            if($z==0) {
                                if($params != '') $params .= ', ';
                                $params .= "\$sortarr['$col_name']";
                                $params .= ', ' . (isset($col[1]) ? $col[1] : $sort);
                                $params .= ', ' . (isset($col[2]) ? $col[2] : $sorttype);
                            }
                            $sortarr[$col_name][] = $row[$col_name];
                            if ($sorttype == SORT_STRING) {
                                $sortarr[$col_name] = array_map('strtolower', $sortarr[$col_name]);
                            }
                        }
                        $z++;
                    }

                    // echo "array_multisort($params, \$this -> rowset);";
                    eval ("array_multisort($params, \$this->rowset);");
                }
            }
        }

        /**
         * Sortiert ein Array nach Werten mittels einer benutzerdefinierten Vergleichsfunktion.
         *
         * @param array $cmp_function Aufbau array(Object, 'Function')
         **/
        function usort($cmp_function)
        {
            usort($this -> rowset, $cmp_function);
        }

        /**
         * Verschiebt einen Datensatz innerhalb des Resultsets an die uebergebene Position.
         *
         * @access public
         * @param string $fieldname Feldname
         * @param string $unique_value Eindeutiger Wert
         * @param integer $pos Position wohin der Datensatz verschoben werden soll (beginnend mit 1)
         * @return boolean
         **/
        function move($fieldname, $unique_value, $pos)
        {
            $ok=false;
            $new_rowset = array();
            $count = count($this -> rowset);
            $z = -1;
            $pos = ($pos-1);
            for ($r=0; $r < $count; $r++) {
                $row = $this -> rowset[$r];
                if ($row[$fieldname] == $unique_value) {
                    if ($r == 0 and $pos == 0) {
                        return true;
                    }
                    $new_rowset[$pos] = $row;
                    $ok = true;
                }
                else {
                    if (($z+1) == $pos) {
                        $z += 2;
                    }
                    else {
                        $z++;
                    }
                    $new_rowset[$z] = $row;
                }

            }
            if ($ok) {
                $this -> rowset = $new_rowset;
            }
            return $ok;
        }

        /**
         * Liefert alle Spalten der Ergebnismenge.
         *
         * @access public
         * @return array alle Spalten der Ergebnismenge, wenn keine Daten da sind, wird false zurueck gegeben.
         **/
        function getColumns()
        {
            if ($this -> count() > 0) {
                return array_keys($this -> rowset[$this -> index]);
            }
            else {
                return false;
            }
        }

        /**
         * Setzt den internen Zeiger auf den ersten Datensatz zurueck (Iterator).
         *
         * @access public
         * @return integer Index
         **/
        function reset()
        {
            if ($this->count() > 0) {
                $this->index = 0;
            }
            else {
                $this->index = -1;
            }
            return $this->index;
        }

        /**
         * Setzt den internen Zeiger auf den ersten Datensatz zurueck (Iterator).
         *
         * @access public
         * @return array Datensatz (oder false; wenn es keine Datensaetze gibt)
         **/
        function first()
        {
            $this->reset();
            return $this->getRow();
        }

        /**
         * Fraegt ab, ob es sich um den ersten Datensatz handelt.
         *
         * @access public
         * @return boolean Ergebnisstatus
         **/
        function isFirst()
        {
            return $this -> index == 0;
        }

        /**
         * Setzt den internen Zeiger auf den letzten Datensatz (Iterator).
         *
         * @access public
         * @return array|bool Datensatz als Array oder FALSE
         **/
        function last()
        {
            $count = $this -> count();
            if ($count > 0) {
                $this -> index = ($count-1);
            }
            else {
                $this -> index = -1;
            }
            return $this -> getRow();
        }

        /**
         * Fraegt ab, ob es sich um den letzten Datensatz handelt.
         *
         * @access public
         * @return boolean Erfolgsstatus
         **/
        function isLast()
        {
            return $this -> index == ($this -> count() - 1);
        }

        /**
         * Liefert den aktuellen Datensatz
         *
         * @access public
         * @return array Datensatz
         **/
        function current()
        {
            return $this -> getRow();
        }

        /**
         * Sucht einen Datensatz  (Iterator).
         *
         * @param integer $index Record-Offset
         * @return array Datensatz
         **/
        function seek($index)
        {
            if ($this -> count() > $index) {
                $this -> index = $index;
            }
            else {
                $this -> index = -1;
            }
            return $this -> getRow();
        }

        /**
         * Aktuelle Zeigerposition  (Iterator).
         *
         * @access public
         * @return integer (beginnend bei 0 fuer den ersten Datensatz; -1 entspricht einer leeren Ergebnismenge)
         **/
        function pos()
        {
            return $this -> index;
        }

        /**
         * Zum naechsten Datensatz. Bewegt den internen Zeiger um eins hoeher (Iterator).
         *
         * @access public
         * @return array|bool naechster Datensatz (falls kein Datensatz an dieser Position existiert, wird false zurueck gegeben)
         **/
        function next()
        {
            if ($this->index < $this->count()) {
                $this->index++;
                return $this->getRow();
            }
            else {
                return false;
            }
        }

        /**
         * Zum vorherigen Datensatz. Bewegt den internen Zeiger um eins tiefer (Iterator).
         *
         * @access public
         * @return array|bool vorheriger Datensatz (falls kein Datensatz an dieser Position existiert, wird false zurueck gegeben)
         **/
        function prev()
        {
            if ($this->index > 0) {
                $this->index--;
                return $this->getRow();
            }
            else {
                return false;
            }
        }

        /**
         * Anzahl Datensaetze in der Ergebnismenge (Iterator)
         *
         * @access public
         * @return integer Anzahl
         **/
        public function count()
        {
            return count($this->rowset);
        }

        /**
         * eof gibt an, ob der letzte Datensatz der Datenmenge aktiv ist. (end of file)
         *
         * @return bool
         */
        function eof()
        {
            return ($this->index == ($this->count() - 1));
        }

        /**
         * bof gibt an, ob der erste Datensatz der Datenmenge aktiv ist. (begin of file)
         *
         * Mit Bof (Beginning Of File) können Sie feststellen, ob der erste Datensatz der Datenmenge aktiv ist, also eindeutig die erste Zeile in der Datenmenge darstellt. In diesem Fall hat die Eigenschaft den Wert true.
         *
         * @return bool
         */
        function bof()
        {
            return ($this->index == 0);
        }

        /**
         * Returns a value of a field of the current record
         *
         * @param string $key name of column (fieldname)
         * @param mixed $default
         * @return mixed value
         */
        public function getValue(string $key, $default=null)
        {
            if(!isset($this->rowset[$this->index])) return $default;
            if(!array_key_exists($key, $this->rowset[$this->index])) return $default;
            return $this->rowset[$this->index][$key];
        }

        /**
         * Returns a value of a field of the current record as a string
         *
         * @param string $key
         * @param string $default
         * @return string
         */
        public function getValueAsString(string $key, $default=''): string
        {
            return (string)$this->getValue($key, $default);
        }

        /**
         * Returns a value of a field of the current record as an integer
         *
         * @param string $key
         * @param int $default
         * @return int
         */
        public function getValueAsInt(string $key, $default=0): int
        {
            return (int)$this->getValue($key, $default);
        }

        /**
         * Returns a value of a field of the current record as a boolean
         *
         * @param string $key
         * @param bool $default
         * @return bool
         */
        public function getValueAsBool(string $key, $default=false): bool
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
         * @throws Exception
         */
        public function getValueAsDateTime(string $key, $default=null, ?DateTimeZone $timezone=null): ?DateTime
        {
            $value = $this->getValue($key, $default);
            if(is_null($value) == false) {
                if(strpos($value, '-') === false) {
                    $value = '@'.$value; // should be an unix timestamp (integer)
                }
                return new \DateTime($value, $timezone);
            }
            return null;
        }

        /**
        * Setzt einen neuen/Uberschreibt einen Wert eines Feldes in der Ergebnismenge.
        *
        * @param string $key Schluessel (bzw. Name des Feldes)
        * @param mixed $value Wert der Variable
        */
        function setValue(string $key, $value = '')
        {
            if($this->count() == 0) {
                $this->addValue($key, $value);
            }
            else {
                if (!is_array($key)) {
                    $this->rowset[$this->index][$key] = $value;
                }
                else {
                    $this->rowset[$this->index] = $key + $this->rowset[$this->index];
                }
            }
        }

        /**
         * Das ist das equivalent zur Funktion setValue, nur dass die Felder hinten an das Array angefuegt werden
         *
         * @access public
         * @param string $key Spaltenname oder Array[Spalte] = Wert
         * @param string $value Wert des neuen Feldes
         */
        function addFields($key, $value = '')
        {
            if($this->count() == 0) {
                $this->addValue($key, $value);
            }
            else {
                if (!is_array($key)) {
                    $this->rowset[$this->index][$key] = $value;
                }
                else {
                    $this->rowset[$this->index] = array_merge($this->rowset[$this->index], $key);
                }
            }
        }

        /**
        * Fuegt einen neuen Datensatz in die Ergebnismenge ein.
        *
        * @access public
        * @param string $key Schluessel (bzw. Name des Feldes)
        * @param string $value Wert der Variable
        */
        function addValue($key, $value = '')
        {
            if (!is_array($key)) {
                $this -> rowset[$this->count()][$key] = $value;
            }
            else {
                $this -> rowset[$this->count()] = $key;
            }
            $this->index = $this->count()-1;
        }

        /**
         * Loescht ein Feld (Key) inkl. Inhalt (Value) aus dem Datensatz
         *
         * @param string $key
         */
        function delKey($key) {
            unset($this->rowset[$this->index][$key]);
        }

        /**
         * Aendert den Namen eines Schluessels
         *
         * @access public
         * @param string $old_key alter Schluesselname (oder array fuer mehrere Schluesselaenderungen)
         * @param string $new_key neuer Schluesselname (oder array fuer mehrere Schluesselaenderungen)
         **/
        function changeKey($old_key, $new_key)
        {
            $count = $this -> count();
            for ($i=0; $i < $count; $i++) {
                if (is_array($old_key)) {
                    for ($k=0; $k<count($old_key); $k++) {
                        if(array_key_exists($old_key[$k], $this -> rowset[$i])) {
                            $this -> rowset[$i][$new_key[$k]] = $this -> rowset[$i][$old_key[$k]];
                            unset($this -> rowset[$i][$old_key[$k]]);
                        }
                    }
                }
                else {
                    if(array_key_exists($old_key, $this -> rowset[$i])) {
                        $this -> rowset[$i][$new_key] = $this -> rowset[$i][$old_key];
                        unset($this -> rowset[$i][$old_key]);
                    }
                }
            }
        }

        /**
         * Format ein Feld mittels einer benutzerdefinierten Formatierungsfunktion.
         *
         * z.B.
         * function formatDate($row)
         * {
         * 		$row['date'] = strftime('%d.%m.%Y', $row['date']);
         * 		return $row;
         * }
         * $Resultset -> uformat('formatDate');
         *
         * @access public
         * @param string|array $callback_function
         **/
        function uformat($callback_function)
        {
            $this -> rowset = array_map($callback_function, $this -> rowset);
        }

        /**
         * Füllt eine Spalte über das ganze Ergebnis (Rowset) mit einem Wert (verschiebt nicht den Satzzeiger).
         *
         * @access public
         * @param string $key Schlüssel
         * @param string $value Wert
         * @return bool true
         */
        function fillValues($key, $value)
        {
            $count = $this -> count();
            for($i=0; $i<$count; $i++) {
                $this -> rowset[$i][$key] = $value;
            }
            return true;
        }

        /**
         * Formatiert Felder in ein Datum- und/oder Zeitformat. Wendet die Funktion auf die ganze Ergebnismenge an, nicht nur auf den aktuellen Datensatz.
         *
         * @param string|array $fieldnames Felder, die umgewandelt werden sollen
         * @param string $format gültiger Formatierungsstring (siehe PHP Funktion strftime)
         **/
        function formatAsDateTime($fieldnames, $format='%d.%m.%Y %H:%M')
        {
            if (is_array($fieldnames)) {
                foreach($fieldnames as $fieldname) {
                    $i=0;
                    foreach($this -> rowset as $row) {
                        $this -> rowset[$i][$fieldname] = formatDateTime($row[$fieldname], $format);
                        $i++;
                    }
                }
            }
            else {
                $i=0;
                foreach($this -> rowset as $row) {
                    $this -> rowset[$i][$fieldnames] = formatDateTime($row[$fieldnames], $format);
                    $i++;
                }
            }
        }

        /**
         * Formatiert Datenbank-Timestamp-Felder in ein beliebiges Datum- und/oder Zeitformat um. Wendet die Funktion auf die ganze Ergebnismenge an, nicht nur aktuellen Datensatz.
         *
         * @param string|array $fieldnames Timestamp-Felder, die umformatiert werden sollen
         * @param string $format gültiger Formatierungsstring (siehe PHP Funktion strftime)
         */
        function formatDBTimestampAsDatetime($fieldnames, $format='%d.%m.%Y %H:%M')
        {
            if (is_array($fieldnames)) {
                foreach($fieldnames as $fieldname) {
                    $i=0;
                    foreach($this -> rowset as $row) {
                        $this -> rowset[$i][$fieldname] = formatDBTimestampAsDatetime($row[$fieldname], $format);
                        $i++;
                    }
                }
            }
            else {
                $i=0;
                foreach($this -> rowset as $row) {
                    $this -> rowset[$i][$fieldnames] = formatDBTimestampAsDatetime($row[$fieldnames], $format);
                    $i++;
                }
            }
        }

        /**
         * Liefert einen ganzen Datensatz als Array zurueck.
         *
         * @access public
         * @param integer $index Record-Offset
         * @return array Datensatz
         **/
        function getRow($index = -1)
        {
            if ($index != $this->index and $index >= 0) {
                $this->seek($index);
            }
            if ($this->index >= 0 and $this->index < $this->count()) {
                return $this->rowset[$this->index];
            }
            else {
                return false;
            }
        }

        /**
         * Loescht eine Zeile und setzt internen Zeiger auf vorherigen Datensatz zurueck.
         *
         * @access public
         * @param integer $index Index
         * @return boolean Erfolgsstatus
         **/
        function deleteRow($index = -1)
        {
            if($index != $this->index and $index >= 0) {
                $this -> seek($index);
            }
            if($this -> index >= 0) {
                unset($this -> rowset[$this -> index]);
                $this -> rowset = array_values($this -> rowset);
                $this -> index--;
                return true;
            }
            else {
                return false;
            }
        }

        /**
         * Liefert die komplette Ergebnismenge als indiziertes Array (enthaelt je Satz ein assoziatives Array mit Feldnamen) zurueck.
         *
         * @access public
         * @return array Recordset
         **/
        function getRowset()
        {
            return $this->rowset;
        }

        /**
         * Füllt das Resultset mit Daten. Als Übergabeparameter erwartet die Funktion ein indiziertes Array (enthaelt je Satz ein assoziatives Array mit Feldnamen). Satzzeiger wird auf ersten Satz gelegt.
         *
         * @access public
         * @param array $rowset
         * @return int Index (Satzzeiger)
         **/
        function setRowset($rowset)
        {
            $this->rowset = $rowset;
            $this->reset();
            return $this->index;
        }

        /**
         * Fügt ein anderes Resultset an das eigene Resultset an. Satzzeiger wird nicht beeinflusst.
         *
         * @param array $rowset
         * @return boolean erfolgreich ja/nein
         */
        function addRowset($rowset)
        {
            if(!is_array($rowset)) {
                return false;
            }

            $this->rowset = array_merge($this->rowset, $rowset);
            if(($this->index < 0) and $this->count() > 0) {
                $this->reset();
            }
            return true;
        }

        /**
         * Entfernt einen Teil der Ergebnismenge und ersetzt ihn optional durch etwas anderes.
         * Die Funktion entfernt die durch offset und length angegebenen Elemente der Ergebnismenge,
         * und ersetzt diese durch die Elemente des Arrays replacement, wenn angegeben und gibt ein
         * Array mit den entfernten Elemente zurueck
         *
         * @access public
         * @param int $offset Ist offset positiv, beginnt der zu entfernende Bereich bei diesem Offset vom Anfang der Ergebnismenge. Ist offset negativ, beginnt der zu entfernende Bereich offset Elemente vor dem Ende der Ergebnismenge.
         * @param int $length Ist length nicht angegeben, wird alles von offset bis zum Ende der Ergebnismenge entfernt. Ist length positiv, wird die angegebene Anzahl Elemente entfernt. Ist length negativ, dann wird der Bereich von length Elementen vor dem Ende, bis zum Ende der Ergebnismenge entfernt. Tipp: Um alles von offset bis zum Ende der Ergebenismenge zu entfernen wenn replacement ebenfalls angegeben ist, verwenden Sie Resultset::count() als length. (optional)
         * @param array $replacement Ist das Array replacement angegeben, werden die entfernten Elemente durch die Elemente dieses Arrays ersetzt. Sind offset und length so angegeben dass nichts entfernt wird, werden die Elemente von replacement an der von offset spezifizierten Stelle eingefuegt. Tipp: Soll die Ersetzung durch nur ein Element erfolgen ist es nicht noetig ein Array zu anzugeben es sei denn, dieses Element ist selbst ein Array. (optional)
         * @return array Gibt das Array mit den entfernten Element zurueck.
         **/
        function spliceRowset($offset, $length=null, $replacement=null)
        {
            if(!is_null($replacement)) {
                return array_splice($this -> rowset, $offset, $length, $replacement);
            }
            elseif(!is_null($length)) {
                return array_splice($this -> rowset, $offset, $length);
            }
            return array_splice($this -> rowset, $offset);
        }

        /**
         * Liefert alle Werte eines Feldes bzw. einer Tabellenspalte zurueck.
         *
         * @access public
         * @param string|array $fieldname Feldname bzw. Spaltenname
         * @param boolean $asKey Werte als Schluessel im Array
         * @return array Felddaten als Array z.B. array('Alex', 'Florian', 'Andreas')
         **/
        function getFieldData($fieldname, $asKey=false)
        {
            $arrResult = array();
            if(is_array($fieldname)) {
                $fieldname = array_flip($fieldname);
                foreach ($this->rowset as $row) {
                    $record = array_intersect_key($row, $fieldname);
                    $arrResult[] = $record;
                }
            }
            else {
                foreach($this->rowset as $row) {
                    if(isset($row[$fieldname])) {
                        if($asKey) {
                            $arrResult[$row[$fieldname]] = true;
                        }
                        else {
                            $arrResult[] = $row[$fieldname];
                        }
                    }
                }

            }
            return $arrResult;
        }

        /**
         * Sucht Spalte und Wert innerhalb der Ergebnismenge und liefert bei Erfolg Index zurueck. Andernfalls false.
         *
         * @param string $fieldname Spaltenname
         * @param string $value Wert
         * @param bool $begin True=beginnt mit der Suche ab ersten Datensatz, False=beginnt mit der Suche ab dem aktuellen Datensatz
         * @return int|bool Index oder False
         */
        function find($fieldname, $value, $begin=true)
        {
            if($fieldname == '') return false;

            if($begin) {
                if(!$this->first()) return false;
            }
            else {
                if(!$this->next()) return false;
            }

            // Mehrere Spalten überprüfen (Array-Übergabe)
            if(is_array($fieldname) and is_array($value)) {
                // Suche solange bis der Wert des Feldes übereinstimmt oder das Ende erreicht wurde
                $len = sizeof($fieldname) - 1;
                do {
                    $found = false;
                    for($i=0; $i<=$len; $i++) {
                        $found = ($this -> getValue($fieldname[$i]) == $value[$i]);
                        if(!$found) break;
                    }
                    if($found) {
                        return $this -> index;
                    }
                } while($this -> next());
            }
            // Eine Spalte überprüfen
            else {
                // Suche solange bis der Wert des Feldes übereinstimmt oder das Ende erreicht wurde
                do {
                    if($this->getValue($fieldname) == $value) {
                        return $this->index;
                    }
                } while($this->next());
            }

            return false;
        }

        /**
         * Ermittle nächsten übereinstimmenden Datensatz. Kann nur in Verbindung mit "find" aufgerufen werden!
         *
         * @param string $fieldname zu suchender Feldname
         * @param string $value zu suchender Wert
         * @return int|bool Index oder False
         */
        function findNext($fieldname, $value)
        {
            return $this->find($fieldname, $value, false);
        }

        /**
         * Vergleicht ein Resultset, ob es identisch ist. Ist das Resultset nicht identisch, bleibt der Satzzeiger auf diesem stehen.
         *
         * @param Resultset $Resultset
         * @return boolean
         */
        function isEqual($Resultset)
        {
            if($this->count() != $Resultset->count()) return false;
            $this->first();
            $Resultset->first();
            do {
                if(count(array_diff_assoc($this->getRow(), $Resultset->getRow())) != 0 or
                    count(array_diff_assoc($Resultset->getRow(), $this->getRow())) != 0) return false;
            } while($this->next() and $Resultset->next());
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
         **/
        function getCSV($with_headline=true, $separator=';', $line_break="\n", $text_clinch='"')
        {
            $csv='';
            if($this->count()) {
                if($with_headline) {
                    $csv .= implode($separator, array_keys($this->rowset[0])).$line_break;
                }
                foreach($this->rowset as $row) {
                    $line = '';
                    $values = array_values($row);
                    foreach($values as $val) {
                        $val = $this->_maskTextCSVcompliant($val, $separator, $text_clinch);
                        $line .= ($line != '') ? ($separator.$val) : (''.$val);
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
        function setFields($fields)
        {
            if(!is_array($fields)) {
                $E = new Xception('Der Parameter $fields ist kein Array!', 0, magicInfo(__FILE__, __LINE__,
                    __FUNCTION__, __CLASS__));
                $E->raiseError();
            }
            else {
                $this->fields = $fields;
            }
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
        function getRowAsCSV($with_headline=true, $separator=';', $line_break="\n", $text_clinch='"')
        {
            $csv = '';
            if($this->count()) {
                if(is_array($this->fields)) {
                    if($with_headline) $csv .= implode($separator, array_values(($this->fields))).$line_break;
                    $row = '';
                    foreach ($this->fields as $key) {
                        if($row != '') $row .= $separator;
                        $val = $this->_maskTextCSVcompliant($this->rowset[$this->index][$key], $separator, $text_clinch);
                        $row .= $val;
                    }
                    $row .= $line_break;

                    $csv .= $row;
                }
                else {
                    if($with_headline) {
                        $csv .= implode($separator, array_keys($this->rowset[$this->index])).$line_break;
                    }

                    $values = array_values($this->getRow());
                    $i = 0;
                    foreach($values as $val) {
                        $val = $this->_maskTextCSVcompliant($val, $separator, $text_clinch);
                        $csv .= ($i == 0) ? ''.$val : $separator.$val;
                        $i++;
                    }
                    $csv .= $line_break;
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
        private function _maskTextCSVcompliant($val, $separator=';', $text_clinch='"')
        {
            $hasTextClinch = false;
            if($text_clinch != '') {
                $hasTextClinch = strpos($val, $text_clinch);
            }
            if($hasTextClinch !== false) {
                $val = str_replace($text_clinch, $text_clinch.$text_clinch, $val);
            }
            if ($hasTextClinch !== false or strpos($val, $separator) !== false or strpos($val, chr(10)) !== false or strpos($val, chr(13)) !== false) {
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
        function getRowAsIni($key_value_separator='=', $separator="\n"/*, $text_clinch=''*/)
        {
            $string = '';
            if($this->count()) {
                foreach($this->rowset[$this->index] as $key => $val) {
                    if($string != '') $string .= $separator;
                    $string .= $key.$key_value_separator.$val;
                }
            }
            return $string;
        }

        /**
         * Creates data format for the bootstrap table
         */
        public function getRowSetAsBSTable(int $total): array
        {
            $return = [];
            $return['total'] = $this->count();
            $return['totalNotFiltered'] = $total;
            $return['rows'] = $this->rowset;
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
         */
        function getRowsetAsXGrid($pk, $total_count, $pos=0, $without_pk=true, $encoding='ISO-8859-1', $encode=false,
            $callbackRow=array(), $callbackCell=array())
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
                if(is_array($this->fields)) {
                    $keys = $this->fields;
                }
                else {
                    $keys = array_keys($this->rowset[0]);
                }

                // Primärschlüssel entfernen (AM, 18.11.2010, optimiert)
                if($without_pk) {
                    $keys = array_diff($keys, $pk);
                }

//					foreach($keys as $key => $val) {
//						$xml .= '<column width="50" type="ro">'.$val.'</column>';
//					}

                $z = 1;
                foreach($this->rowset as $row) {
                    $id = '';
                    foreach ($pk as $key => $val) {
                        if($id != '') $id .= '-';
                        $id .= $row[$val];
                    }
                    $rowSettings = '';
                    if($callbackRow) {
                        $rowSettings = ' '.$callbackRow[0]->$callbackRow[1]($id, $row, $z, $count);
                    }
                    $xml .= '<row id=\''.$id.'\''.$rowSettings.'>';

                    foreach ($keys as $key) {
                        $val = $row[$key];

                        $cellSettings = '';
                        if($callbackCell) $cellSettings = ' '.$callbackCell[0]->$callbackCell[1]($key, $val, $row, $z, $count);
                        $xml .= '<cell'.$cellSettings.'>';
                        if(is_numeric($val)) {
                            $xml .= $val;
                        }
                        else {
                            $xml .= '<![CDATA['.str_replace('&', '&amp;', ($encode) ? utf8_encode($val) : $val).']]>';
                        }
                        $xml .= '</cell>';
                    }
/*						foreach ($row as $key => $val) {
                        if($without_pk and in_array($key, $pk)) continue;
                        $xml .= '<cell>';
                        if(is_numeric($val)) {
                            $xml .= $val;
                        }
                        else {
                            $xml .= '<![CDATA['.str_replace('&', '&amp;', ($encode) ? utf8_encode($val) : $val).']]>';
                        }
                        $xml .= '</cell>';
                    }*/

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
         * @param null|boolean $add [optional]
         * @param string $encoding
         * @return string
         */
        function getRowsetAsXCombo($pkAsValue, $fieldnameAsOption, $add=null, $encoding='ISO-8859-1')
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
                foreach ($this->rowset as $row) {
                    $id = '';
                    foreach ($pkAsValue as $key => $val) {
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
         * Letzte Fehlermeldung ausgeben.
         *
         * @access public
         * @return array Fehlercode und Fehlermeldung oder false (falls kein Fehler auftrat)
         **/
        function getLastError()
        {
            $sizeof = sizeof($this->errorStack);
            if ($sizeof > 0) {
                $result = $this->errorStack[$sizeof-1];
            }
            else {
                $result = false;
            }
            return $result;
        }

        /**
         * Liefert eine Liste aller Fehlermeldungen
         *
         * @return array
         */
        function getErrorList()
        {
            return $this->errorStack;
        }

        /**
         * Fügt dem Fehler-Stack einen Fehler hinzu.
         *
         * @param string $message Fehlermeldung
         * @param int $code Fehler-Code (Standard 0)
         * @return $this
         */
        function addError($message, $code=0)
        {
            if(is_array($message)) {
                $this->errorStack = array_merge($this->errorStack, $message);
            }
            else {
                $this->errorStack[] = array('message' => $message, 'code' => $code);
            }
            return $this;
        }
    }
}