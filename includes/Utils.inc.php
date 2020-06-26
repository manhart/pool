<?php
/**
 * Utils.inc.php
 *
 * @version $Id: Utils.inc.php,v 1.65 2007/07/17 13:49:10 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 07/28/2003
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 */


/**
 * Gibt den aktuellen UNIX-Timestamp/Zeitstempel in Mikrosekunden zurueck
 *
 * @return float Zeitstempel in Mikrosekunden
 **/
function getMicrotime($seed = 1)
{
    list($usec, $sec) = explode(' ', microtime());

    return ((float)$usec + ((float)$sec * $seed));
}

/**
 * pray ()
 * Erweiterte var_dump Funktion mit formatierter Ausgabe
 * Durchlaeuft die Argumente (Arrays/Objects) rekursiv und gibt eine formatierte Liste aus.
 * Optional werden Funktionsnamen von Objekten ausgegeben .
 * Es zeigt alle Variablen an, die es finden kann.
 * @method static
 *
 * @access public
 * @param mixed $data Variable jeden Datentyps
 * @param boolean|int $functions Zeige Funktionsnamen der Objekte (Standard = 0)
 * @return string
 */
function pray($data, $functions=0)
{
    $result = "";
    if($functions != 0) {
        $sf = 1;
    }
    else {
        $sf = 0;
    }

    if (isset ($data)) {
        if ((is_array($data) and count($data)) || (is_object($data) and !isEmptyObject($data))) {
            $result .= "<OL>\n";
            foreach($data as $key => $value) {
	            // while (list ($key, $value) = each ($data)) {
                $type = gettype($value);

                if ($type == "array" || $type == "object") {
                    $result .= sprintf("<li>(%s) <b>%s</b>:\n", $type, $key);

                    if (strtolower($key) != 'owner' and (strtolower($key) != 'weblication')
                        and strtolower($key) != 'parent' and strtolower($key) != 'backtrace') { // prevent recursion
                        $result .= pray($value, $sf);
                    }
                    else {
                        $result .= 'no follow, infinite loop';
                    }
                }
                elseif (stripos($type, 'function') !== false) {
                    if ($sf) {
                        $result .= sprintf("<li>(%s) <b>%s</b> </LI>\n", $type, $key, $value);
                        // There doesn't seem to be anything traversable inside functions.
                    }
                }
                else {
                    /*	if (!$value){
                        $value = "(none)";
                    }*/
                    $result .= sprintf("<li>(%s) <b>%s</b> = %s</LI>\n", $type, $key, $value);
                }
                unset($key, $value);
            }
            $result .= "</OL>end.\n";
        }
        else {
            $result .= "(empty)";
        }
    }

    return $result;
}

/**
 * formatBytes()
 *
 * @param integer $bytes Anzahl der Bytes
 * @param bool $shortVersion Abgekuerzt
 * @return string Formatierter String z.B.  33,44 MBytes
 */
function formatBytes($bytes, $shortVersion = false)
{
    // Bytes
    if ($bytes < 1024) {
        return (number_format($bytes, 2, ',', '.').(($shortVersion) ? ' b' : ' Bytes'));
    }

    // KBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, 2, ',', '.').(($shortVersion) ? ' KB' : ' KBytes'));
    }

    // MBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, 2, ',', '.').(($shortVersion) ? 'MB' : ' MBytes'));
    }

    // GBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, 2, ',', '.').(($shortVersion) ? 'GB' : ' GBytes'));
    }

    // TBytes
    $bytes = $bytes / 1024;

    return (number_format($bytes, 3, ',', '.').(($shortVersion) ? 'TB' : 'TBytes'));
}


/**
 * splitcsv()
 *
 * @access public
 * @param string $line Text / Zeile
 * @param string $delim Trennzeichen (the delimiter to split by)
 * @param boolean $removeQuotes Sollen Quotes (") vom Ergebnis entfernt werden
 * @return array Aufgeteilte Felder
 **/
function splitcsv($line, $delim=',', $removeQuotes=true, $quote='"')
{
    $fields = array();
    $fldCount = 0;
    $inQuotes = false;
    for ($i = 0; $i < strlen($line); $i++) {
        if (!isset($fields[$fldCount])) $fields[$fldCount] = '';
        $tmp = substr($line, $i, strlen($delim));
        if ($tmp === $delim && !$inQuotes) {
            $fldCount++;
            $i += strlen($delim)-1;
        }
        else if ($fields[$fldCount] == '' && $line[$i] == $quote && !$inQuotes) {
            if (!$removeQuotes) $fields[$fldCount] .= $line[$i];
            $inQuotes = true;
        }
        else if ($line[$i] == $quote) {
            if ($line[$i+1] == $quote) {
                $i++;
                $fields[$fldCount] .= $line[$i];
            }
            else {
                if (!$removeQuotes) $fields[$fldCount] .= $line[$i];
                $inQuotes = false;
            }
        }
        else {
            $fields[$fldCount] .= $line[$i];
        }
    }

    return $fields;
}

/**
 * Dröselt nach Trennzeichen einen String $data auf. Dabei werden Steuerzeichen #10 und #13 berücksichtigt, sowie umschlossener (enclosure) Text
 *
 * @param string $data Inhalt (z.B. einer Datei)
 * @param string $delim Trennzeichen (Standard = ';')
 * @param string $enclosure Umklammerung [optional]
 * @return array mehrdimensional (Zeilen, Felder)
 */
function splitcsvByContent(&$data, $delim=';', $enclosure='"')
{
    $ret_array = array();
    $enclosed = false;
    $fldcount = 0;
    $linecount = 0;
    $fldval = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $chr = $data[$i];
        switch ($chr) {
            case $enclosure:
                if ($enclosed && $data[$i + 1] == $enclosure) {
                    $fldval .= $chr;
                    ++$i; //skip next char
                }
                else $enclosed = !$enclosed;
                break;

            case $delim:
                if (!$enclosed) {
                    $ret_array[$linecount][$fldcount++] = $fldval;
                    $fldval = '';
                }
                else $fldval .= $chr;
                break;

            case "\r":
                if (!$enclosed && $data[$i + 1] == "\n") {
                    continue 2;
                }

            case "\n":
                if (!$enclosed) {
                    $ret_array[$linecount++][$fldcount] = $fldval;
                    $fldcount = 0;
                    $fldval = '';
                }
                else $fldval .= $chr;
                break;

            default:
                $fldval .= $chr;
        }
    }
    if ($fldval) $ret_array[$linecount][$fldcount] = $fldval;
    unset($fldval);

    return $ret_array;
}

/**
 * Wandelt alle Werte eines Arrays in Kleinbuchstaben um. Pendent zu array_change_key_case
 *
 * @param array $input Array, das umgewandelt werden soll
 * @param int $case CASE_LOWER|CASE_UPPER
 * @return array Ergebnis
 **/
function array_change_value_case($input, $case=CASE_LOWER)
{
    /**
     * Diese private Funktion gehoert zur Funktion array_change_value_case!
     *
     * @access private
     * @param string $item Item
     * @param $key
     **/
    function __arrayStrToLower(& $item, $key)
    {
        $item = strtolower($item);
    }

    /**
     * Diese private Funktion gehoert zur Funktion array_change_value_case!
     *
     * @access private
     * @param string $item Item
     * @param string $key Schluessel
     **/
    function arrayStrToUpper(& $item, $key)
    {
        $item = strtoupper($item);
    }

    if ($case == CASE_LOWER) {
        array_walk($input, 'arrayStrToLower');
    }
    elseif ($case == CASE_UPPER) {
        array_walk($input, 'arrayStrToUpper');
    }

    return $input;
}

/**
 * Gibt den ersten Montag und letzten Sonntag der Kalenderwoche $week im Jahr $year zurueck
 * example:
 *    $date = GetDateOfWeeknum(1, 2003);
 *    echo date('d.m.Y H:i:s', $date[1]);
 *    echo "<br>";
 *    echo date('d.m.Y H:i:s', $date[7]);
 *
 * @param integer $week Kalenderwoche
 * @param integer $year Jahr
 * @return array Datum
 */
function getDateOfWeeknum($week, $year = '')
{
    // 1.1. eines Jahres
    $firstdate = mktime(0, 0, 0, 1, 1, ($year != '' ? $year : date('Y')));
    // echo date('d.m.Y', $firstdate) . '<br>';
    $kw = strftime('%V', $firstdate)/* . '<br>'*/
    ;

    // Sekunden eines Tages / Woche
    $secday = 60 * 60 * 24;
    $secweek = 7 * $secday;

    if (date('D', $firstdate) == 'Mon') {
        $mondate = $firstdate;
    }
    else {
        // In der Version 4.3.10 errechnet PHP nicht den korrekten Montag. Ab Version 4.4.0 ist es wieder korrekt
        // z.B. 01.01.2006 next Monday liefert bei Version 4.3.10 den 9.1.2006, richtig w�re 2.1.2006
        if (version_compare('4.3.10', phpversion()) == 0) {
            $var = 'first';
        }
        else {
            $var = 'next';
        }

        if ((int)$kw == 1) {
            $mondate = (strtotime($var.' Monday', $firstdate) - $secweek);
        }
        else {
            $mondate = strtotime($var.' Monday', $firstdate);
        }
    }
    //	echo 'datum: ' . date('d.m.Y', $mondate);

    // create Array
    $date = Array();

    // Fuer jeden Tag genau das Datum
    $date['Monday'] = strtotime('+'.($week - 1).' week', $mondate);
    $date['Montag'] = $date['Monday'];
    $date[1] = $date['Monday'];

    $date['Tuesday'] = ($date['Monday'] + (1 * $secday));
    $date['Dienstag'] = $date['Tuesday'];
    $date[2] = $date['Tuesday'];

    $date['Wednesday'] = ($date['Monday'] + (2 * $secday));
    $date['Mittwoch'] = $date['Wednesday'];
    $date[3] = $date['Wednesday'];

    $date['Thursday'] = ($date['Monday'] + (3 * $secday));
    $date['Donnerstag'] = $date['Thursday'];
    $date[4] = $date['Thursday'];

    $date['Friday'] = ($date['Monday'] + (4 * $secday));
    $date['Freitag'] = $date['Friday'];
    $date[5] = $date['Friday'];

    $date['Saturday'] = ($date['Monday'] + (5 * $secday));
    $date['Samstag'] = $date['Saturday'];
    $date[6] = $date['Saturday'];

    $date['Sunday'] = ($date['Monday'] + (6 * $secday));
    $date['Sonntag'] = $date['Sunday'];
    $date[7] = $date['Sunday'];

    return $date;
}

/**
 * Ermittelt den Zeitstempel einer Kalenderwoche
 */
function getTimestmapOfCalenderWeek($calenderWeek, $year)
{
    $sommertime = false;
    $day = 60 * 60 * 24;
    $week = $day * 7;

    $monday = firstCW($year) + ($week * ($calenderWeek - 1));
    if (date('I', $monday)) { // Sommerzeit
        $monday -= 3600;
        $sommertime = true;
    }
    $tuesday = $monday + $day;
    $wednesday = $tuesday + $day;
    $thursday = $wednesday + $day;
    $friday = $thursday + $day;
    $saturday = $friday + $day;
    $sunday = $saturday + $day;
    $achter = $sunday + $day;
    if ($sommertime and date('I', $achter) == 0) { // Winderzeit
        $achter += 3600;
    }

    return array(
        1 => $monday,
        2 => $tuesday,
        3 => $wednesday,
        4 => $thursday,
        5 => $friday,
        6 => $saturday,
        7 => $sunday,
        8 => $achter // fuer Beschraenkungen sinnvoll
    );
}

/**
 * Liefert das Monat einer Kalenderwoche
 *
 * @param int $week
 * @param int $year
 * @return int Monat
 */
function getMonthOfWeek($week, $year = '')
{
    $weekdays = getDateOfWeeknum($week, $year);

    return date('m', $weekdays[4]);
}

/**
 * Pr�ft ob es sich um den String um ein deutsches Datum handelt
 *
 * @param string $string eventl. Datum
 * @return bool
 */
function is_date($string, &$date)
{
    $date = false;
    $datepieces = explode('.', $string, 3);
    if (isset($datepieces[2]) and checkdate((int)$datepieces[1], (int)$datepieces[0], (int)$datepieces[2])) {
        $date = $datepieces;

        return true;
    }

    return false;
}

/**
 * Pr�ft ob es sich um den String um ein englisches Datum handelt
 *
 * @param string $string eventl. Datum
 * @return bool
 */
function is_date_en($date, $delim = '-')
{
    $datepieces = explode('-', $date, 3);
    if (isset($datepieces[2]) and checkdate((int)$datepieces[1], (int)$datepieces[2], (int)$datepieces[0])) {
        return true;
    }

    return false;
}

/**
 * Errechnet eine Terminserie (ber�cksichtigt Sommer- & Winterzeit)
 *
 * @param int $von Startzeitpunkt
 * @param int $bis Endzeitpunkt
 * @param int $intervall Intervall in Tage (Standard 7 f�r eine Woche)
 * @param int $step Schritte (schrittweise) bedeutet zu jedem $step 'ten Intervall. Z.B. bei 2 wird jeder 2. Termin �bersprungen (bzw. 2 Wochen Intervall bei 7 Tagen erreicht)
 * @return array
 */
function getSeriesOfAppointments($from, $to, $intervall = 7, $step = 1)
{
    $dates = array();

    if (empty($to)) {
        return array(0 => array('timestamp' => $from, 'date' => date('d.m.Y', $from), 'step' => $step));
    }

    if ($intervall > 0 and $step > 0 and $to >= $from) {
        $secDay = 86400; // Sekunden eines Tages
        $rhythmus = $intervall * $secDay;

        $diff = ($to + ($secDay - 1)) - $from;
        $numAppointments = ceil(($diff / $rhythmus) / $step);
        if (@constant('DEBUG')) echo 'Anzahl generierter Termine: '.$numAppointments."\n";
        for ($i = 0; $i < $numAppointments; $i++) {
            $new_date = array();
            $time = ($from + ($i * $step * $rhythmus));
            if (date('I', $from) < date('I', $time)) {
                $time -= 3600;
            }
            elseif (date('I', $from) > date('I', $time)) {
                $time += 3600;
            }
            $new_date['timestamp'] = $time;
            $new_date['date'] = date('d.m.Y', $time);
            $new_date['step'] = $step;
            if ($time <= $to) {
                array_push($dates, $new_date);
            }
            unset($new_date);
        }
    }

    return $dates;
}

/**
 * Berechnung des ersten Montags in der 1. KW eines Jahres
 *
 * @access public
 * @param int $jahr Jahreszahl im Format CCYY
 * @return int Zeitstempel des ersten Montags in der ersten KW eines Jahres
 * @see http://www.pjh2.de/datetime/iso8601/date.php
 */
function firstCW($jahr)
{
    $erster = mktime(0, 0, 0, 1, 1, $jahr);
    $wtag = date('w', $erster);

    if ($wtag <= 4) {
        /**
         * Donnerstag oder kleiner: auf den Montag zur�ckrechnen.
         */
        $montag = mktime(0, 0, 0, 1, 1 - ($wtag - 1), $jahr);
    }
    else {
        /**
         * auf den Montag nach vorne rechnen.
         */
        $montag = mktime(0, 0, 0, 1, 1 + (7 - $wtag + 1), $jahr);
    }

    return $montag;
}

/**
 * Berechnung des ersten Montags einer Kalenderwoche eines Jahres
 *
 * @access public
 * @param int $kw Kalenderwoche
 * @param int $jahr Jahreszahl im Format CCYY
 * @return int Zeitstempel des ersten Montags eines KW eines Jahres
 */
function mondayCW($kw, $jahr)
{
    $firstmonday = firstCW($jahr);
    $mon_monat = date('m', $firstmonday);
    $mon_jahr = date('Y', $firstmonday);
    $mon_tage = date('d', $firstmonday);

    $tage = ($kw - 1) * 7;

    $mondaykw = mktime(0, 0, 0, $mon_monat, $mon_tage + $tage, $mon_jahr);

    return $mondaykw;
}

/**
 * Returns week of the year, first Sunday is first day of first week
 *
 * @param string day in format DD, default is current local day
 * @param string month in format MM, default is current local month
 * @param string year in format CCYY, default is current local year
 * @access public
 * @return integer $week_number
 */
function weekOfYear($day = '', $month = '', $year = '')
{
    if (empty($year)) {
        $year = dateNow('%Y');
    }
    if (empty($month)) {
        $month = dateNow('%m');
    }
    if (empty($day)) {
        $day = dateNow('%d');
    }
    $iso = gregorianToISO($day, $month, $year);
    $parts = explode('-', $iso);
    $week_number = intval($parts[1]);

    return $week_number;
} // end func weekOfYear

/**
 * Converts from Gregorian Year-Month-Day to
 * ISO YearNumber-WeekNumber-WeekDay
 * Uses ISO 8601 definitions.
 * Algorithm from Rick McCarty, 1999 at
 * http://personal.ecu.edu/mccartyr/ISOwdALG.txt
 *
 * @param string day in format DD
 * @param string month in format MM
 * @param string year in format CCYY
 * @return string
 * @access public
 */
// Transcribed to PHP by Jesus M. Castagnetto (blame him if it is fubared ;-)
function gregorianToISO($day, $month, $year)
{
    $mnth = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $y_isleap = isLeapYear($year);
    $y_1_isleap = isLeapYear($year - 1);
    $day_of_year_number = $day + $mnth[$month - 1];
    if ($y_isleap && $month > 2) {
        $day_of_year_number++;
    }
    // find Jan 1 weekday (monday = 1, sunday = 7)
    $yy = ($year - 1) % 100;
    $c = ($year - 1) - $yy;
    $g = $yy + intval($yy / 4);
    $jan1_weekday = 1 + intval((((($c / 100) % 4) * 5) + $g) % 7);
    // weekday for year-month-day
    $h = $day_of_year_number + ($jan1_weekday - 1);
    $weekday = 1 + intval(($h - 1) % 7);
    // find if Y M D falls in YearNumber Y-1, WeekNumber 52 or
    if ($day_of_year_number <= (8 - $jan1_weekday) && $jan1_weekday > 4) {
        $yearnumber = $year - 1;
        if ($jan1_weekday == 5 || ($jan1_weekday == 6 && $y_1_isleap)) {
            $weeknumber = 53;
        }
        else {
            $weeknumber = 52;
        }
    }
    else {
        $yearnumber = $year;
    }
    // find if Y M D falls in YearNumber Y+1, WeekNumber 1
    if ($yearnumber == $year) {
        if ($y_isleap) {
            $i = 366;
        }
        else {
            $i = 365;
        }
        if (($i - $day_of_year_number) < (4 - $weekday)) {
            $yearnumber++;
            $weeknumber = 1;
        }
    }
    // find if Y M D falls in YearNumber Y, WeekNumber 1 through 53
    if ($yearnumber == $year) {
        $j = $day_of_year_number + (7 - $weekday) + ($jan1_weekday - 1);
        $weeknumber = intval($j / 7);
        if ($jan1_weekday > 4) {
            $weeknumber--;
        }
    }
    // put it all together
    if ($weeknumber < 10) {
        $weeknumber = '0'.$weeknumber;
    }

    return "{$yearnumber}-{$weeknumber}-{$weekday}";
}

/**
 * Liefert das aktuelle lokale Datum. Hinweis: diese Funktion
 * erhaelt das lokale Datum anhand strftime(). Es ist oder
 * auch nicht 32-bit sicher auf verwendeten System.
 *
 * @param string Das strftime() Format um das Datum zurueck zu reichen
 * @access public
 * @return string Das aktuelle Datum im spezifizierten Format
 */

function dateNow($format = '%Y%m%d')
{
    return (strftime($format, time()));
} // end func dateNow


/**
 * Gibt true zurueck fuer ein Schaltjahr, andernfalls false
 *
 * @param string Jahr im Format CCYY
 * @access public
 * @return boolean true/false
 */
function isLeapYear($year = '')
{
    if (empty($year)) {
        $year = dateNow('%Y');
    }

    if (preg_match('/\D/', $year)) {
        return false;
    }

    if ($year < 1000) {
        return false;
    }

    if ($year < 1582) {
        // vor Gregorio XIII - 1582
        return ($year % 4 == 0);
    }
    else {
        // nach Gregorio XIII - 1582
        return ((($year % 4 == 0) and ($year % 100 != 0)) or ($year % 400 == 0));
    }
} // end func isLeapYear

/**
 * Pr�ft ob es sich bei dem Tag um ein Wochenende handelt.
 *
 * @param int $day
 * @param int $month
 * @param int $year
 * @return bool
 */
function isWeekendDay($day, $month, $year)
{
    $tmp = (int)date("w", mktime(0, 0, 0, $month, $day, $year));
    if ($tmp == 0 || $tmp == 6) {
        return true;
    }
    else return false;
}

/**
 * Berechnet anhand Datum die Kalenderwoche
 *
 * @param int $day
 * @param int $month
 * @param int $year
 * @return int Kalenderwoche
 */
function getWeekNumber($day, $month, $year)
{
    $weekNumber = (int)date('W', mktime(0, 0, 0, $month, $day, $year));

    return $weekNumber;
}

/**
 * Berechnet anhand eines Zeitstempels die Kalenderwoche
 *
 * @param int $ts Unix Zeitstempel
 * @return int Kalenderwoche
 */
function getWeekNumberFromTS($ts)
{
    $weekNumber = (int)date('W', $ts);

    return $weekNumber;
}

/**
 * Liefert wichtigste Informationen ueber ein Monat als Array:
 * $monthInfo['calendarWeeks']            = array (siehe print_r oder pray)
 * $monthInfo['firstCalendarWeek']        = erste vorkommende Kalenderwoche im Monat
 * $monthInfo['lastCalendarWeek']        = letzte vorkommende Kalenderwoche im Monat
 * $monthInfo['tsFirstDayOfMonth']        = Zeitstempel erster Tag im Monat
 * $monthInfo['tsLastDayOfMonth']        = Zeitstempel letzter Tag im Monat
 * $monthInfo['numberOfDays']            = Anzahl der Tage
 *
 * @access public
 * @param integer Monat
 * @param integer Jahr
 * @return array
 **/
function getMonthInfo($month, $year)
{
    #### erster Tag im Monat, erste KW im Monat
    $day = 1;
    $tsFirstDay_of_Month = mktime(0, 0, 0, $month, $day, $year);
    $firstCalendarWeek = (int)strftime('%V', $tsFirstDay_of_Month);
    $firstCalendarWeek_Year = (int)strftime('%G', $tsFirstDay_of_Month);
    // echo 'Fuer monat: ' . date('m.Y', $tsFirstDay_of_Month) . '<br>';

    #### Tage in diesem Monat
    $numberOfDays = (int)date('t', $tsFirstDay_of_Month);

    #### letzter Tag im Monat, letzte KW im Monat
    $tsLastDay_of_Month = mktime(0, 0, 0, $month, $numberOfDays, $year);
    $lastCalendarWeek = (int)strftime('%V', $tsLastDay_of_Month);

    $wochentag = (int)strftime('%u', $tsFirstDay_of_Month); // 1-7 (Mo-So)
    if ($wochentag > 1) $day = 1 - ($wochentag - 1);

    #### erster Tag der ersten Kalenderwoche im gesuchten Monat x
    $firstDayOfCalendarWeek = mktime(0, 0, 0, $month, $day, $year);

    $cws = array();
    do {
        $day = (int)date('d', $firstDayOfCalendarWeek);
        $month = (int)date('m', $firstDayOfCalendarWeek);
        $year = (int)strftime('%Y', $firstDayOfCalendarWeek);
        $calendarWeek = (int)strftime('%V', $firstDayOfCalendarWeek);

        array_push($cws, array(
                'nr' => $calendarWeek,
                'd' => $day,
                'm' => $month,
                'y' => $year
            )
        );

        $day = $day + 7; // oa Wocha dazua = 7 Dog
        $firstDayOfCalendarWeek = mktime(0, 0, 0, $month, $day, $year);
    } while ($calendarWeek != $lastCalendarWeek);

    $monthInfo = array(
        'calendarWeeks' => $cws,
        'firstCalendarWeek' => $firstCalendarWeek,
        'firstCalendarWeek_Year' => $firstCalendarWeek_Year,
        'lastCalendarWeek' => $lastCalendarWeek,
        'tsFirstDayOfMonth' => $tsFirstDay_of_Month,
        'tsLastDayOfMonth' => $tsLastDay_of_Month,
        'numberOfDays' => $numberOfDays
    );

    // echo pray($monthInfo);
    return $monthInfo;
}


/**
 * Gibt den Monat in deutscher und englischer Sprache zurueck.
 * Wird kein Dezimal-Wert uebergeben, gibt er den aktuellen Monat aus.
 * Der zweite Parameter bestimmt die Sprache. Wird er nicht angegeben,
 * liefert die Funktion ein Array mit allen Sprachen zurueck.
 *
 * @param integer $decimal_value Dezimal Wert fuer Monat 1-12 (Januar-Dezember)
 * @param string $locale Internationales Format fuer Laenderlokale
 * @return array or string Monat
 **/
function getMonth($decimal_value = 0, $locale = 'de_DE')
{
    if ($decimal_value == null) {
        $decimal_value = date('m');
    }
    switch ($decimal_value) {
        case 1:
            $result['de_DE'] = 'Januar';
            $result['en_US'] = 'January';
            break;

        case 2:
            $result['de_DE'] = 'Februar';
            $result['en_US'] = 'February';
            break;

        case 3:
            $result['de_DE'] = 'M&auml;rz';
            $result['en_US'] = 'March';
            break;

        case 4:
            $result['de_DE'] = 'April';
            $result['en_US'] = 'April';
            break;

        case 5:
            $result['de_DE'] = 'Mai';
            $result['en_US'] = 'May';
            break;

        case 6:
            $result['de_DE'] = 'Juni';
            $result['en_US'] = 'June';
            break;

        case 7:
            $result['de_DE'] = 'Juli';
            $result['en_US'] = 'July';
            break;

        case 8:
            $result['de_DE'] = 'August';
            $result['en_US'] = 'August';
            break;

        case 9:
            $result['de_DE'] = 'September';
            $result['en_US'] = 'September';
            break;

        case 10:
            $result['de_DE'] = 'Oktober';
            $result['en_US'] = 'October';
            break;

        case 11:
            $result['de_DE'] = 'November';
            $result['en_US'] = 'November';
            break;

        case 12:
            $result['de_DE'] = 'Dezember';
            $result['en_US'] = 'December';
            break;

        default:
            trigger_error('Unknown Month "'.$decimal_value.'" in '.__FUNCTION__);
    }

    if (!is_null($locale)) {
        return $result[$locale];
    }
    else {
        return $result;
    }
}

/**
 * Gibt den Wochentag in deutscher und englischer Sprache zurueck.
 * Wird kein Dezimal-Wert uebergeben, gibt er den aktuellen Wochentag aus.
 * Der zweite Parameter bestimmt die Sprache. Wird er nicht angegeben,
 * liefert die Funktion ein Array mit allen Sprachen zurueck.
 *
 * @param integer $decimal_value Dezimal Wert fuer Wochentag 1-7 (Mo-So)
 * @param string $locale Internationales Format fuer Laenderlokale
 * @return array or string Wochentag
 **/
function getWeekday($decimal_value = 0, $locale = 'de_DE')
{
    if ($decimal_value == null) {
        $decimal_value = date('w');
    }
    switch ($decimal_value) {
        case 1:
            $result['de_DE'] = 'montag';
            $result['en_US'] = 'monday';
            break;

        case 2:
            $result['de_DE'] = 'dienstag';
            $result['en_US'] = 'tuesday';
            break;

        case 3:
            $result['de_DE'] = 'mittwoch';
            $result['en_US'] = 'wednesday';
            break;

        case 4:
            $result['de_DE'] = 'donnerstag';
            $result['en_US'] = 'thursday';
            break;

        case 5:
            $result['de_DE'] = 'freitag';
            $result['en_US'] = 'friday';
            break;

        case 6:
            $result['de_DE'] = 'samstag';
            $result['en_US'] = 'saturday';
            break;

        case 0:
        case 7:
            $result['de_DE'] = 'sonntag';
            $result['en_US'] = 'sunday';
            break;

        default:
            trigger_error('Unknown Weekday "'.$decimal_value.'" in '.__FUNCTION__);
    }

    if (!is_null($locale)) {
        return $result[$locale];
    }
    else {
        return $result;
    }
}

/**
 * Gibt den Wochentag als Integer aus.
 *
 * @param string $weekday Wochentag in Deutsch oder Englisch als String
 * @return int 1-7
 */
function getWeekdayAsInt($weekday)
{
    $result = null;
    switch (strtolower($weekday)) {
        case 'montag'        :
        case 'mo'            :
        case 'monday'        :
            $result = 1;
            break;

        case 'dienstag'        :
        case 'di'            :
        case 'tuesday'        :
            $result = 2;
            break;

        case 'mittwoch'        :
        case 'mi'            :
        case 'wednesday'    :
            $result = 3;
            break;

        case 'donnerstag'    :
        case 'do'            :
        case 'thursday'        :
            $result = 4;
            break;

        case 'freitag'        :
        case 'fr'            :
        case 'friday'        :
            $result = 5;
            break;

        case 'samstag'        :
        case 'sa'            :
        case 'saturday'        :
            $result = 6;
            break;

        case 'sonntag'        :
        case 'so'            :
        case 'sunday'        :
            $result = 7;
            break;
    }

    return $result;
}


/**
 * Wandelt den Wochentag (1-7) in einen DayBar Wert um.
 *
 * @param int $weekday 1-7
 * @return int Bits
 */
function getWeekdayAsDayBarValue($weekday)
{
    if (!is_array($weekday)) $weekday = array($weekday);
    $retValue = 0;
    foreach ($weekday as $day) {
        $retValue += pow(2, ($day - 1));
    }

    return $retValue;
}

/**
 * Wandelt den Wochentag (String: Montag, Dienstag...) in einen DayBar Wert um.
 *
 * @param string $weekday Mo-So, Montag-Sonntag, Monday-Sunday
 * @return int Bits
 */
function getWeekdayStringAsDayBarValue($weekday)
{
    $weekday = getWeekdayAsInt($weekday);
    $weekday = pow(2, ($weekday - 1));

    return $weekday;
}

/**
 * Ermittelt die Wochentage (der GUI_DayBar) anhand der �bergebenen Bits und liefert einen String mit den Wochentagen (optimiert Mo-Fr) zur�ck
 *
 * @param int $value Bits
 * @param bool $short Verk�rzte Version z.B. Mo-Mi
 * @return string Wochentage (optimiert)
 */
function getDayBarValueAsString($value, $short = true)
{
    $result = '';
    $ayDays = array();
    if (1 & $value) $ayDays[] = 1;
    if (2 & $value) $ayDays[] = 2;
    if (4 & $value) $ayDays[] = 3;
    if (8 & $value) $ayDays[] = 4;
    if (16 & $value) $ayDays[] = 5;
    if (32 & $value) $ayDays[] = 6;
    if (64 & $value) $ayDays[] = 7;

    sort($ayDays);
    $count = count($ayDays);

    $bOrder = true;
    for ($w = 0; $w < $count; $w++) {
        if ($result != '') $result .= ', ';
        $wd = getWeekday($ayDays[$w]);
        if ($short) $wd = substr($wd, 0, 2);
        $result .= ucfirst($wd);
        if ($bOrder) $bOrder = ((@$ayDays[$w - 1] == ($ayDays[$w] - 1)) or ($w == 0));
    }
    if ($bOrder == true and $count > 2) {
        $wd1 = getWeekday($ayDays[0]);
        if ($short) $wd1 = substr($wd1, 0, 2);
        $wd2 = getWeekday($ayDays[$count - 1]);
        if ($short) $wd2 = substr($wd2, 0, 2);
        $result = ucfirst($wd1).'-'.ucfirst($wd2);
    }

    return $result;
}

/**
 * Ermittelt die Wochentage (der GUI_DayBar) anhand der �bergebenen Bits und liefert ein Array mit den Wochentagen zur�ck
 *
 * @param int $value
 * @return array
 */
function getDayBarValueAsArray($value)
{
    $ayDays = array();
    if (1 & $value) $ayDays[] = 1;
    if (2 & $value) $ayDays[] = 2;
    if (4 & $value) $ayDays[] = 3;
    if (8 & $value) $ayDays[] = 4;
    if (16 & $value) $ayDays[] = 5;
    if (32 & $value) $ayDays[] = 6;
    if (64 & $value) $ayDays[] = 7;

    return $ayDays;
}

if (!function_exists('addEndingSlash')) {
    /**
     * Fuegt bei Verzeichnissen endenden Slash hinzu.
     *
     * @access public
     * @param string $value Wert (Ordner, Verzeichnis)
     * @return Wert mit endenden Slash
     **/
    function addEndingSlash($value)
    {
        if ($value != '') {
            if ($value[strlen($value) - 1] != '/') {
                $value .= '/';
            }
        }

        return $value;
    }
}

if (!function_exists('removeEndingSlash')) {
    /**
     * Entfernt endenden Slash im String
     *
     * @access public
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeEndingSlash($value)
    {
        if (!empty($value)) {
            $len = strlen($value) - 1;
            if ($value[$len] == '/') {
                $value = substr($value, 0, $len);
            }
        }

        return $value;
    }
}

if (!function_exists('removeBeginningSlash')) {
    /**
     * Entfernt endenden Slash im String
     *
     * @access public
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeBeginningSlash($value)
    {
        if (!empty($value)) {
            if ($value[0] == '/') {
                $value = substr($value, 1);
            }
        }

        return $value;
    }
}

// Workaround:
// erst ab PHP Version 4.2.0 verf�gbar
if (!function_exists('is_a')) {
    /**
     * is_a()
     * Die Funktion is_a() wird nur eingebunden, wenn die PHP Version < 4.2.0 ist.
     * Returns TRUE if the object is of this class or has this class as one of its parents
     *
     * @param object $obj Objekt
     * @param string $classname Klassenname
     * @return boolean siehe Beschreibung.
     **/
    function is_a($obj, $classname)
    {
        if (is_object($obj)) {
            if (is_subclass_of($obj, $classname) or (get_class($obj) == strtolower($classname))) {
                return true;
            }
        }

        return false;
    }
}

/**
 * Erstellt Verzeichnisse z.b. Uebergabe /var/log/prog/main/ups.log (prog und main werden angelegt)
 *
 * @param string $strPath
 * @param integer $mode
 * @return boolean Erfolgsstatus
 **/
function mkdirs($strPath, $mode = 0777)
{
    if (@is_dir($strPath)) {
        return true;
    }
    else {
        $pStrPath = dirname($strPath);
        if (!mkdirs($pStrPath, $mode)) {
            return false;
        }

        return @mkdir($strPath, $mode);
    }
}

/**
 * hex_encode()
 * Maskiert z.B. URIs:
 * Die Maskierung besteht darin, ein Prozentzeichen % zu notieren, gefolgt von dem hexadezimal ausgedrueckten Zeichenwert des gewuenschten Zeichens.
 *
 * @param string $text Bliebiger Text, URI, E-Mail, etc.
 * @return maskierter / codierter Text
 * @link http://selfhtml.teamone.de/html/verweise/email.htm
 **/
function hex_encode($text)
{
    $encoded = '';
    if (strlen($text) > 0) {
        $encoded = bin2hex((string)$text);
        $encoded = chunk_split($encoded, 2, '%');
        $encoded = '%'.substr($encoded, 0, strlen($encoded) - 1);
    }

    return $encoded;
}

/**
 * getJSEMailLink()
 * Gibt einen klickbaren JavaScript HEX kodierten E-Mail Link zurueck.
 * Vor allem gegen Spam Bots interessant!
 *
 * @param string $email E-Mail Adresse
 * @return string JavaScript E-Mail Link
 **/
function getJSEMailLink($email, $caption = null)
{
    if (strpos($email, '@') === false) {
        return '';
    }
    $email = explode('@', $email);
    $en_caption = hex_encode($email[0]);
    $en_at = hex_encode('@');
    $en_ext = hex_encode($email[1]);
    $js = '<script type="text/javascript" language="javascript">
				<!--
					var caption = "'.$en_caption.'";
					var at = "'.$en_at.'";
					var ext = "'.$en_ext.'";';

    $js .= 'document.write(\'<a href="mailto:\' + caption + at + ext + \'">\');';
    if ($caption) {
        $js .= '	document.write(\''.$caption.'\');';
    }
    else {
        $js .= '	document.write(urlDecode("'.$en_caption.'") + urlDecode("'.$en_at.'") + urlDecode("'.$en_ext.'"));';
    }
    $js .= '	document.write(\'</a>\');';

    $js .= '
				//-->
				</script>';

    return $js;
}

/**
 * deleteDir()
 * Loescht kompletten Inhalt  inkl. Unterverzeichnisse eines Verzeichnis
 *
 * @access public
 * @param string $dir Verzeichnis
 * @return boolean Erfolgsstatus
 **/
function deleteDir($dir, $rmSelf = true)
{
    if (!$opendir = opendir($dir)) {
        return false;
    }
    $dir = addEndingSlash($dir);
    while (false !== ($readdir = readdir($opendir))) {
        if ($readdir !== '..' && $readdir !== '.') {
            $readdir = trim($readdir);
            if (is_file($dir.$readdir)) {
                if (!unlink($dir.$readdir)) {
                    return false;
                }
            }
            elseif (is_dir($dir.$readdir)) {
                // Calls itself to clear subdirectories
                if (!deleteDir($dir.$readdir)) {
                    return false;
                }
            }
        }
    }
    closedir($opendir);
    if ($rmSelf) {
        if (!rmdir($dir)) {
            return false;
        }
    }

    return true;
}

/**
 * file_extension()
 * Gibt die Endung der uebergebenen Datei zurueck, z.B. irgendwas hinter dem Punkt.
 * siehe auch PHP Funktion pathinfo ab Version 4.0.3
 *
 * @access public
 * @param string $file Datei
 * @return string Dateiendung
 **/
function file_extension($file = "")
{
    return substr($file, (strrpos($file, ".") ? strrpos($file, ".") + 1 : strlen($file)), strlen($file));
}

/**
 * remove_extension()
 * Entfernt die Endung einer Datei und gibt sie zurueck.
 *
 * @access public
 * @param string $file Datei
 * @return string Neuer Dateiname
 **/
function remove_extension($file = "")
{
    return substr($file, 0, (strrpos($file, ".") ? strrpos($file, ".") : strlen($file)));
}

/**
 * countWords()
 * Zaehlt die Anzahl Woerter im Text.
 * Wenn der Parameter realwords gesetzt ist, werden Zeichen wie '-', '+', die von Leerzeichen umgeben sind, uebersprungen
 *
 * @access public
 * @param string $str Text
 * @param integer $realwords Zeichen wie '-', '+' entfernen
 * @return integer Anzahl Woerter
 **/
function countWords($str, $realwords = 1)
{
    if (is_array($str)) return false;
    if ($realwords) {
        $str = preg_replace("/(\s+)[^a-zA-Z0-9](\s+)/", " ", $str);
    }

    return (count(split("[[:space:]]+", $str)));
}

/**
 * countSentences()
 * Ermittelt die Anzahl Saetze in einem Text.
 *
 * @access public
 * @param string $str Text
 * @return integer Anzahl Saetze
 **/
function countSentences($str)
{
    if (is_array($str)) return false;

    return preg_match_all('/[^\s]\.(?!\w)/', $str, $blah);
}

/**
 * countParagraphs()
 * Ermittelt die Anzahl Paragraphen in einem Text.
 *
 * @access public
 * @param string $str Text
 * @return integer Anzahl Paragraphen
 **/
function countParagraphs($str)
{
    if (is_array($str)) return false;

    return count(preg_split('/[\r\n]+/', $str));
}

/**
 * stringInfo()
 * Gibt einige Informationen zum uebergebenen Text zurueck.
 * Falls der Parameter "realwords" == 1 dann werden Zeichen wie '-', '+' (die von Leerzeichen umgeben sind) entfernt.
 *
 * @param string $str Text
 * @param integer $realwords 0 oder 1 (boolean)
 * @return array Aufbau $info['characters'] Anzahl Zeichen; $info['words'] Anzahl Woerter; $info['sentences'] Anzahl Saetze; $info['paragraphs'] Anzahl Paragraphen
 **/
function stringInfo($str, $realwords = 1)
{
    if (is_array($str)) return false;
    $info['characters'] = ($realwords ? preg_match_all('/[^\s]/', $str, $blah) : strlen($str));
    $info['words'] = countWords($str, $realwords);
    $info['sentences'] = countSentences($str);
    $info['paragraphs'] = countParagraphs($str);

    return $info;
}

// html'izes and converts special tags into the html counterpart
// works on either a string or every element of an array
// special tags are formatted such as:
//
//   [b]text[/b] - bold text
//   [i]text[/i] - italicise text
//   php@amnuts.com - make a live mailto link
//   [link=place]text[/link] - makes a web link to place with text as link name
//   [list]       }
//     [*] text   }  - creates a bullet point list
//     [*] text   }
//   [/list]      }
//   [color=value]text to colour[/color] - colourize some text
//   [colour=value]text to colour[/colour] - as above, but with 'u' :)
//
function custom_tags($foo)
{
    if (!is_array($foo)) {
        $foo = htmlentities($foo);
        $foo = eregi_replace("[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-z]{2,3}", "<a href=\"mailto:\\0\">\\0</a>", $foo);
        $foo = preg_replace("/\[link=(\S+?)\](.*?)\[\/link\]/is", "<a href=\"\\1\">\\2</a>", $foo);
        $foo = preg_replace("/\[i\](.*?)\[\/i\]/is", "<i>\\1</i>", $foo);
        $foo = preg_replace("/\[b\](.*?)\[\/b\]/is", "<b>\\1</b>", $foo);
        $foo = preg_replace("/\[list\](\n)?/i", "<ul>", $foo);
        $foo = preg_replace("/\[\/list\](\n)?/i", "</ul>", $foo);
        $foo = preg_replace("/\[ul\](\n)?/i", "<ul>", $foo);
        $foo = preg_replace("/\[\/ul\](\n)?/i", "</ul>", $foo);
        $foo = preg_replace("/\[\*\](.*?)$/ism", "<li>\\1</li>", $foo);
        $foo = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/is", "<font color=\"\\1\">\\2</font>", $foo);
        $foo = preg_replace("/\[colour=(.*?)\](.*?)\[\/colour\]/is", "<font color=\"\\1\">\\2</font>", $foo);

        return nl2br($foo);
    }
    else {
        foreach ($foo as $k => $v) {
            if (is_array($foo[$k])) {
                $foo[$k] = custom_tags($foo[$k]);
            }
            else {
                $foo[$k] = htmlentities($foo[$k]);
                $foo[$k] = eregi_replace("[0-9a-z]([-_.]?[0-9a-z])*@[0-9a-z]([-.]?[0-9a-z])*\\.[a-z]{2,3}", "<a href=\"mailto:\\0\">\\0</a>", $foo[$k]);
                $foo[$k] = preg_replace("/\[link=(\S+?)\](.*?)\[\/link\]/is", "<a href=\"\\1\">\\2</a>", $foo[$k]);
                $foo[$k] = preg_replace("/\[i\](.*?)\[\/i\]/is", "<i>\\1</i>", $foo[$k]);
                $foo[$k] = preg_replace("/\[b\](.*?)\[\/b\]/is", "<b>\\1</b>", $foo[$k]);
                $foo[$k] = preg_replace("/\[list\](\n)?/i", "<ul>", $foo[$k]);
                $foo[$k] = preg_replace("/\[\/list\](\n)?/i", "</ul>", $foo[$k]);
                $foo[$k] = preg_replace("/\[ul\](\n)?/i", "<ul>", $foo[$k]);
                $foo[$k] = preg_replace("/\[\/ul\](\n)?/i", "</ul>", $foo[$k]);
                $foo[$k] = preg_replace("/\[\*\](.*?)$/ism", "<li>\\1</li>", $foo[$k]);
                $foo[$k] = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/is", "<font color=\"\\1\">\\2</font>", $foo[$k]);
                $foo[$k] = preg_replace("/\[colour=(.*?)\](.*?)\[\/colour\]/is", "<font color=\"\\1\">\\2</font>", $foo[$k]);
                $foo[$k] = nl2br($foo[$k]);
            }
        }
    }

    return $foo;
}

// strings any special characters from a string, or all elements of an array
function strip_custom_tags($foo)
{
    if (!is_array($foo)) {
        $foo = eregi_replace("\\[link=([^\\[]*)\\]", "", $foo);
        $foo = str_replace("[/link]", "", $foo);
        $foo = str_replace("[i]", "", $foo);
        $foo = str_replace("[/i]", "", $foo);
        $foo = str_replace("[b]", "", $foo);
        $foo = str_replace("[/b]", "", $foo);
        $foo = str_replace("[list]", "", $foo);
        $foo = str_replace("[/list]", "", $foo);
        $foo = str_replace("[ul]", "", $foo);
        $foo = str_replace("[/ul]", "", $foo);
        $foo = str_replace("[*]", "", $foo);
        $foo = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/is", "\\2", $foo);
        $foo = preg_replace("/\[colour=(.*?)\](.*?)\[\/colour\]/is", "\\2", $foo);
        $foo = eregi_replace("\\[\/colour\\]", "", $foo);
        $foo = eregi_replace("\\[\/color\\]", "", $foo);

        return $foo;
    }
    else {
        foreach ($foo as $k => $v) {
            if (is_array($foo[$k])) {
                $foo[$k] = strip_custom_tags($foo[$k]);
            }
            else {
                $foo[$k] = eregi_replace("\\[link=([^\\[]*)\\]", "", $foo[$k]);
                $foo[$k] = str_replace("[/link]", "", $foo[$k]);
                $foo[$k] = str_replace("[i]", "", $foo[$k]);
                $foo[$k] = str_replace("[/i]", "", $foo[$k]);
                $foo[$k] = str_replace("[b]", "", $foo[$k]);
                $foo[$k] = str_replace("[/b]", "", $foo[$k]);
                $foo[$k] = str_replace("[list]", "", $foo[$k]);
                $foo[$k] = str_replace("[/list]", "", $foo[$k]);
                $foo[$k] = str_replace("[ul]", "", $foo[$k]);
                $foo[$k] = str_replace("[/ul]", "", $foo[$k]);
                $foo[$k] = str_replace("[*]", "", $foo[$k]);
                $foo[$k] = preg_replace("/\[color=(.*?)\](.*?)\[\/color\]/is", "\\2", $foo[$k]);
                $foo[$k] = preg_replace("/\[colour=(.*?)\](.*?)\[\/colour\]/is", "\\2", $foo[$k]);
                $foo[$k] = eregi_replace("\\[\/colour\\]", "", $foo[$k]);
                $foo[$k] = eregi_replace("\\[\/color\\]", "", $foo[$k]);
            }
        }
    }

    return $foo;
}

/**
 * stripRs()
 * Entfernt alle \r 's von einem Text, oder allen Elementen eines Arrays.
 * Es funktioniert mit allen mehrdimensionalen rekursiven Arrays.
 *
 * @access public
 * @param string $foo Text (oder Array)
 * @return string (oder Array) ohne \r
 **/
function stripRs($foo)
{
    if (!is_array($foo)) {
        $foo = str_replace("\r", "", $foo);

        return $foo;
    }
    else {
        foreach ($foo as $k => $v) {
            if (is_array($foo[$k])) {
                $foo[$k] = stripRs($foo[$k]);
            }
            else $foo[$k] = str_replace("\r", "", $foo[$k]);
        }
    }

    return $foo;
}

/**
 * stripNs()
 * Entfernt alle \n 's von einem Text, oder allen Elementen eines Arrays.
 * Es funktioniert mit allen mehrdimensionalen rekusriven Arrays.
 *
 * @access public
 * @param string $foo Text (oder Array)
 * @return string (oder Array) ohne \n
 **/
function stripNs($foo)
{
    if (!is_array($foo)) {
        $foo = str_replace("\n", "", $foo);

        return $foo;
    }
    else {
        foreach ($foo as $k => $v) {
            if (is_array($foo[$k])) {
                $foo[$k] = stripNs($foo[$k]);
            }
            else $foo[$k] = str_replace("\n", "", $foo[$k]);
        }
    }

    return $foo;
}

/**
 * remove_first()
 * Entfernt das erste Wort eines Textes (z.B., irgendetwas bis zum naechsten Leerzeichen ' ')
 *
 * @access public
 * @param string $str Text
 * @return Text ohne erstes Wort
 **/
function remove_first($str = '')
{
    return remove_word($str, 0);
}

/**
 * remove_last()
 * Entfernt das letzte Wort von einem Text (z.B., irgendetwas nach dem letzten Leerzeichen ' ')
 *
 * @access public
 * @param string $str Text
 * @return Text ohne letztem Wort
 **/
function remove_last($str = '')
{
    return remove_word($str, 1);
}

/**
 * remove_word()
 * Entfernt ein beliebiges Wort am Ende oder am Anfang eines Textes
 *
 * @access public
 * @param string $str Text
 * @param integer $end 1 = am Ende, 0 = am Anfang
 * @return string Text mit entferntem Wort
 **/
function remove_word($str = '', $end = 0)
{
    if ($str == '') return $str;
    if (is_array($str)) return $str;
    $str = trim($str);
    if (!substr_count($str, ' ')) return $str;

    return ($end ? substr($str, 0, strrpos($str, ' ')) :
        substr($str, strpos($str, ' ') + 1, strlen($str)));
}

/**
 * Verkuerzt einen Text auf eine bestimme Laenge. Beim Abschneiden geht die Funktion jedoch bis zum letzten Leerzeichen zurueck, damit
 * er ein Wort nicht in der Mitte teilt.
 * Wenn der Text laenger ist als der Ausschnitt, kann mittels dem Parameter more == 1 die Zeichenfolge '...' angehaengt werden.
 *
 * @access public
 * @param string $str Text
 * @param integer $len Maximale Laenge
 * @param integer $more Fuege '...' hinzu, falls der Text gekuerzt wurde
 * @param bool $backtrack True bedeutet, die Funktion schneidet nicht innerhalb eines Wortes durch, sondern liefert nur vollst�ndige W�rter
 * @return string gekuerzte Version
 **/
function shorten($str = '', $len = 150, $more = 1, $backtrack = true)
{
    if ($str == '') return $str;
    if (is_array($str)) return $str;
    $str = trim($str);

    // if it's les than the size given, then return it
    if (strlen($str) <= $len) return $str;

    // else get that size of text
    $encoding = @mb_detect_encoding($str);
    if ($encoding === false) {
        $str = substr($str, 0, $len);
        $encoding = 'ISO-8859-1';
    }
    else {
        $str = mb_substr($str, 0, $len, $encoding);
    }

    // backtrack to the end of a word
    if ($str != '') {
        // check to see if there are any spaces left
        if (!substr_count($str, ' ')) {
            if ($more) $str .= (($more == 1) ? '...' : $more);

            return $str;
        }

        // backtrack
        if ($backtrack) {
            while (strlen($str) && ($str[strlen($str) - 1] != ' ')) {
                $str = mb_substr($str, 0, -1, $encoding);
            }
        }
        $str = mb_substr($str, 0, -1, $encoding);
        if ($more) $str .= (($more == 1) ? '...' : $more);
    }

    return $str;
}

/**
 * Laedt ein Jpeg und gibt bei Misserfolg ein Fehlerbild zurueck
 *
 * @param string $imgname Dateiname (inkl. Pfad)
 * @param string $text Fehlertext im Bild
 * @return resource Resource ID (siehe GD Lib)
 **/
function loadJpeg($imgname, $text = 'Fehler beim �ffnen von: %s')
{
    $im = ImageCreateFromJPEG($imgname); /* Versuch, Datei zu �ffnen */
    if (!$im) {                            /* Pr�fen, ob fehlgeschlagen */
        $im = ImageCreate(150, 30);       /* Erzeugen eines leeren Bildes */
        $bgc = ImageColorAllocate($im, 255, 255, 255);
        $tc = ImageColorAllocate($im, 0, 0, 0);
        ImageFilledRectangle($im, 0, 0, 150, 30, $bgc);
        /* Ausgabe einer Fehlermeldung */
        ImageString($im, 1, 5, 5, sprintf($text, $imgname), $tc);
    }

    return $im;
}

/**
 * Entfernt einen gleichfarbigen Rahmen eines Bildes.
 *
 * @param resource $im_src Resource ID aus der GD Lib
 * @param integer $red Rotanteil (0-255)
 * @param integer $green Gruenanteil (0-255)
 * @param integer $blue Blauanteil (0-255)
 * @return resource Resource ID fuer das neue beschnittene Bild
 **/
function imageremoveframe($im_src, $red = 255, $green = 255, $blue = 255, $toleranz = 0)
{
    $imagesizey = imagesy($im_src);
    $imagesizex = imagesx($im_src);

    $toplefty = -1;
    $topleftx = -1;
    for ($y = 0; $y < $imagesizey; $y++) {
        for ($x = 0; $x < $imagesizex; $x++) {
            $color = imagecolorat($im_src, $x, $y);
            $rgb = imagecolorsforindex($im_src, $color);
            if ($rgb['red'] != $red or $rgb['green'] != $green or $rgb['blue'] != $blue) {
                $toplefty = $y;
                $topleftx = $x;
                break 2;
            }
        }
    }

    $toprighty = -1;
    $toprightx = -1;
    for ($y = 0; $y < $imagesizey; $y++) {
        for ($x = $imagesizex - 1; $x >= 0; $x--) {
            $color = imagecolorat($im_src, $x, $y);
            $rgb = imagecolorsforindex($im_src, $color);
            if ($rgb['red'] != $red or $rgb['green'] != $green or $rgb['blue'] != $blue) {
                $toprighty = $y;
                $toprightx = $x;
                break 2;
            }
        }
    }

    $bottomlefty = -1;
    $bottomleftx = -1;
    for ($y = $imagesizey - 1; $y >= 0; $y--) {
        for ($x = 0; $x < $imagesizex; $x++) {
            $color = imagecolorat($im_src, $x, $y);
            $rgb = imagecolorsforindex($im_src, $color);
            if ($rgb['red'] != $red or $rgb['green'] != $green or $rgb['blue'] != $blue) {
                $bottomlefty = $y;
                $bottomleftx = $x;
                break 2;
            }
        }
    }

    $bottomrighty = -1;
    $bottomrightx = -1;
    for ($y = $imagesizey - 1; $y >= 0; $y--) {
        for ($x = $imagesizex - 1; $x >= 0; $x--) {
            $color = imagecolorat($im_src, $x, $y);
            $rgb = imagecolorsforindex($im_src, $color);
            if ($rgb['red'] != $red or $rgb['green'] != $green or $rgb['blue'] != $blue) {
                $bottomrighty = $y;
                $bottomrightx = $x;
                break 2;
            }
        }
    }

    $topX = ($topleftx < $bottomleftx) ? $topleftx : $bottomleftx;
    $topY = ($toplefty < $toprighty) ? $toplefty : $toprighty;
    $bottomX = ($toprightx > $bottomrightx) ? $toprightx : $bottomrightx;
    $bottomY = ($bottomlefty > $bottomrighty) ? $bottomlefty : $bottomrighty;

    /*echo $toplefty . ' - ' . $topleftx;
		echo '<br>';
		echo $toprighty . ' - ' . $toprightx;
		echo '<br>';
		echo $bottomlefty . ' - ' . $bottomleftx;
		echo '<br>';
		echo $bottomrighty . ' - ' . $bottomrightx;
		echo '<br>';
		echo $topX . ' - ' . $topY . ' | ' . $bottomX . ' - ' . $bottomY;
		*/
    $im_dst = imagecreatetruecolor($bottomX - $topX, $bottomY - $topY);
    imagecopy($im_dst, $im_src, 0, 0, $topX, $topY, $bottomX - $topX, $bottomY - $topY);

    return $im_dst;
}

/**
 * Erzeugt ein Thumbnail (verkleinertes Bild).
 *
 * @param resource $im_src Resource ID aus der GD Lib
 * @param integer $maxWidth maximale Breite
 * @param integer $maxHeight maximale Hoehe
 * @return resource Neue Resource ID (fuer gesampeltes Bild)
 **/
function image_createThumb($im_src, $maxWidth, $maxHeight)
{
    $imagesizey = imagesy($im_src);
    $imagesizex = imagesx($im_src);

    // image dest size $imagesizex = width, $imagesizey = height
    $srcRatio = $imagesizex / $imagesizey; // width/height ratio
    $destRatio = $maxWidth / $maxHeight;
    if ($destRatio > $srcRatio) {
        $destSize[1] = $maxHeight;
        $destSize[0] = $maxHeight * $srcRatio;
    }
    else {
        $destSize[0] = $maxWidth;
        $destSize[1] = $maxWidth / $srcRatio;
    }

    // true color image, with anti-aliasing
    $im_dest = imageCreateTrueColor($destSize[0], $destSize[1]);
    //imageAntiAlias($destImage, true);

    // resampling
    imageCopyResampled($im_dest, $im_src, 0, 0, 0, 0, $destSize[0], $destSize[1], $imagesizex, $imagesizey);

    return $im_dest;
}

/**
 * Entfernt leere Zeilen. Z.B. $lines = array_filter($lines, 'removeEmptyLines');
 *
 * @access public
 * @param string $line Wert
 * @return boolean
 **/
function removeEmptyLines($line)
{
    return trim($line) != '';
}

/**
 * Wandelt OEM Zeichensatz in extended Ascii Zeichensatz um.
 *
 * @param string $text Einfacher Textbaustein
 * @return string Konvertierter Text (nach ANSI)
 * @author Alexander Manhart
 * @access public
 */
function OEMtoAnsi(& $text)
{
    $extended_ascii = Array(
        128 => '�',
        129 => '�',
        130 => '�',
        131 => '�',
        132 => '�',
        133 => '�',
        134 => '�',
        135 => '�',
        136 => '�',
        137 => '�',
        138 => '�',
        139 => '�',
        140 => '�',
        141 => '�',
        142 => '�',
        143 => '�',
        144 => '�',
        145 => '�',
        146 => '�',
        147 => '�',
        148 => '�',
        149 => '�',
        150 => '�',
        151 => '�',
        152 => '�',
        153 => '�',
        154 => '�',
        155 => '�',
        156 => '�',
        157 => '�',
        158 => '�',
        159 => '�',
        160 => '�',
        161 => '�',
        162 => '�',
        163 => '�',
        164 => '�',
        165 => '�',
        166 => '�',
        167 => '�',
        168 => '�',
        169 => '�',
        170 => '�',
        171 => '�',
        172 => '�',
        173 => '�',
        174 => '�',
        175 => '�',
        176 => '�',
        177 => '�',
        178 => '�',
        179 => '�',
        180 => '�',
        181 => '�',
        182 => '�',
        183 => '�',
        184 => '�',
        185 => '�',
        186 => '�',
        187 => '+',
        188 => '+',
        189 => '�',
        190 => '�',
        191 => '+',
        192 => '+',
        193 => '-',
        194 => '-',
        195 => '+',
        196 => '-',
        197 => '+',
        198 => '�',
        199 => '�',
        200 => '+',
        201 => '+',
        202 => '-',
        203 => '�',
        204 => '�',
        205 => '-',
        206 => '+',
        207 => '�',
        208 => '�',
        209 => '�',
        210 => '�',
        211 => '�',
        212 => '�',
        213 => 'i',
        214 => '�',
        215 => '�',
        216 => '�',
        217 => '+',
        218 => '+',
        219 => '�',
        220 => '_',
        221 => '�',
        222 => '�',
        223 => '�',
        224 => '�',
        225 => '�',
        226 => '�',
        227 => '�',
        228 => '�',
        229 => '�',
        230 => '�',
        231 => '�',
        232 => '�',
        233 => '�',
        234 => '�',
        235 => '�',
        236 => '�',
        237 => '�',
        238 => '�',
        239 => '�',
        240 => '�',
        241 => '�',
        242 => '=',
        243 => '�',
        244 => '�',
        245 => '�',
        246 => '�',
        247 => '�',
        248 => '�',
        249 => '�',
        250 => '�',
        251 => '�',
        252 => '�',
        253 => '�',
        254 => '�',
        255 => '�'
    );

    for ($i = 0; $i < strlen($text); $i++) {
        $ord = ord($text[$i]);
        if ($ord > 127) {
            $text[$i] = $extended_ascii[$ord];
        }
    }
    unset($i, $ord, $extended_ascii);

    return $text;
}

/**
 * formatDateTime()
 *
 * @param $datetime
 * @param $format
 * @return
 **/
function formatDateTime($datetime, $format)
{
    if (is_numeric($datetime) == false) {
        $timestamp = strtotime($datetime);
        if ($timestamp !== -1) {
            $datetime = $timestamp;
        }
    }

    return strftime($format, $datetime);
}

/**
 * formatDEDateToEN()
 * Arbeitet etwas anders als formatDateTime, da es deutsches Format (01.01.2004) in
 * englisches Format (2004-01-01) umwandelt.
 *
 * @param $datetime
 * @param $format
 * @return
 **@author Andreas Horvath
 * @see formatDateTime
 */
function formatDEDateToEN($strDate, $delimiter = '.')
{
    $arrDate = explode($delimiter, $strDate);

    return strftime("%Y-%m-%d", strtotime($arrDate[2]."-".$arrDate[1]."-".$arrDate[0]));
}


/**
 * Konvertiert ein deutsches Datum in einen UNIX Zeitstempel
 *
 * @param string $strDate
 * @param string $delimiter Standard .
 * @return int UNIX Zeitstempel
 */
function convertDEDateToUnix($strDate, $delimiter = '.')
{
    $time = 0;
    if ($strDate != '') {
        $date = explode($delimiter, $strDate);
        $time = mktime(0, 0, 0, (int)$date[1], (int)$date[0], (int)$date[2]);
    }

    return $time;
}

/**
 * Konvertiert ein englisches Datum in einen UNIX Zeitstempel
 *
 * @param string $strDate
 * @param string $delimiter Standard .
 * @return int UNIX Zeitstempel
 */
//in php wie strtotime
//	function convertENDateToUnix($strDate, $delimiter='-')
//	{
//		$time = 0;
//		if($strDate != '') {
//			$date = explode($delimiter, $strDate);
//			$time = mktime(0, 0, 0, (int)$date[1], (int)$date[2], (int)$date[0]);
//		}
//		return $time;
//	}

/**
 * Konvertiert ein deutsches Datum mit uhrzeit in einen UNIX Zeitstempel
 *
 * @param string $strDate
 * @param string $delimiter Standard .
 * @return int UNIX Zeitstempel
 */
function convertDEDateAndTimeToUnix($strDate, $strZeit, $delimiterD = '.', $delimiterT = ':')
{
    $time = 0;
    if ($strDate != '') {
        $date = explode($delimiterD, $strDate);
        $stu = 0;
        $min = 0;
        $sek = 0;
        if ($strZeit != '') {
            list($stu, $min, $sek) = explode($delimiterT, $strZeit);
        }
        $time = mktime($stu, $min, $sek, (int)$date[1], (int)$date[0], (int)$date[2]);
    }

    return $time;
}


/**
 * Konvertiert ein englisches Datum mit uhrzeit in einen UNIX Zeitstempel
 *
 * @param string $strDate
 * @param string $delimiter Standard .
 * @return int UNIX Zeitstempel
 */
function convertENDateAndTimeToUnix($strDate, $strZeit, $delimiterD = '-', $delimiterT = ':')
{
    #echo "strDate: ".$strDate."\n";
    $time = 0;
    if ($strDate != '') {
        $date = explode($delimiterD, $strDate);
        $stu = 0;
        $min = 0;
        $sek = 0;
        if ($strZeit != '') {
            list($stu, $min, $sek) = explode($delimiterT, $strZeit);
        }
        #echo "#### ".(int)$date[1]." - ".(int)$date[2]." - ".(int)$date[0]."\n";
        $time = mktime($stu, $min, $sek, (int)$date[1], (int)$date[2], (int)$date[0]);
    }

    return $time;
}

function win_to_utf8($str)
{
    $str = convert_cyr_string($str, 'w', 'i'); // w - windows-1251   to  i - iso8859-5
    $str = utf8_encode($str); //  iso8859-5   to  utf8

    return $str;
}

function utf8_to_win($str)
{
    $str = utf8_decode($str); //  utf8 to iso8859-5
    $str = convert_cyr_string($str, 'i', 'w'); // w - windows-1251   to  i - iso8859-5

    return $str;
}

/**
 * replaces the html tag <br> by a new line
 *
 * @param string $subject text
 * @return string replaced text
 **/
function br2nl($subject)
{
    return preg_replace('=<br(>|([\s/][^>]*)>)\r?\n?=i', chr(10), $subject);
}

/**
 * strips body from html page.
 * html, head and body tags will be dropped.
 *
 * @param string $file_content Datei
 * @return string Datei ohne Html, Head und Body Tags
 **/
function strip_body($file_content)
{
    $body = '';
    if (preg_match('#<body[^>]*?>(.*?)</body>#si', $file_content, $matches)) {
        $body = $matches[1];
    }

    return $body;
}

/**
 * strips head from html page
 *
 * @param string $html
 * @return Ambigous <string, unknown>
 */
function strip_head($html)
{
    $head = '';
    if (preg_match('#<head[^>]*?>(.*?)</head>#si', $html, $matches)) {
        $head = $matches[1];
    }

    return $head;
}

/**
 * cleanUpHTML()
 * This function strips tags and other proprietary tags from a Word document (or other word
 * processor) using "Save as Web Page" features. Could be extended to remove blank space (&nbsp;) // within otherwise empty tags.
 *
 * @param string $text
 * @return string
 **/
function cleanUpHTML($text)
{
    // remove escape slashes
    $text = stripslashes($text);

    // trim everything before the body tag right away, leaving possibility for body attributes
    $text = stristr($text, "<body");

    // strip tags, still leaving attributes, second variable is allowable tags
    $text = strip_tags($text, '<p><b><i><u><a><h1><h2><h3><h4><h4><h5><h6>');

    // removes the attributes for allowed tags, use separate replace for heading tags since a
    // heading tag is two characters
    $text = ereg_replace("<([p|b|i|u])[^>]*>", "<\\1>", $text);
    $text = ereg_replace("<([h1|h2|h3|h4|h5|h6][1-6])[^>]*>", "<\\1>", $text);

    return ($text);
}

/**
 * Liefert das Betriebssystem des Clients zur�ck.
 *
 * @return string Betriebssystem (Operating System) als K�rzel
 */
function getClientOS()
{
    // Betriebssystem
    $HTTP_USER_AGENT = getenv('HTTP_USER_AGENT');
    if (strstr($HTTP_USER_AGENT, 'win95')) {
        $os = 'Windows 95';
    }
    else if (strstr($HTTP_USER_AGENT, 'win98')) {
        $os = 'Windows 98';
    }
    else if (stristr($HTTP_USER_AGENT, 'Windows NT 6.1')) {
        $os = 'Windows 7';
    }
    else if (strstr($HTTP_USER_AGENT, 'NT 4.0')) {
        $os = 'NT';
    }
    else if (strstr($HTTP_USER_AGENT, 'NT 5.0')) {
        $os = 'Win2k';
    }
    else if (strstr($HTTP_USER_AGENT, 'NT 5.1')) {
        $os = 'WinXP';
    }
    else if (strstr($HTTP_USER_AGENT, 'Win')) {
        $os = 'Win';
    }
    else if (strstr($HTTP_USER_AGENT, 'Mac OS X')) {
        $os = 'MacOSX';
    }
    else if (strstr($HTTP_USER_AGENT, 'Mac')) {
        $os = 'Mac';
    }
    else if (strstr($HTTP_USER_AGENT, "Linux")) {
        $os = 'Linux';
    }
    else if (strstr($HTTP_USER_AGENT, "Unix")) {
        $os = 'Unix';
    }
    else {
        $os = 'Other';
    }

    return $os;
}

/**
 * Liefert ein paar Details zum Client (Browser, Plattform, etc. des Surfers)
 *
 * @return array
 */
function getBrowserOS()
{
    $u_agent = $_SERVER['HTTP_USER_AGENT'];
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version = "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }
    elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }
    elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>'.join('|', $known).
               ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
            $version = $matches['version'][0];
        }
        else {
            $version = $matches['version'][1];
        }
    }
    else {
        $version = $matches['version'][0];
    }

    // check if we have a number
    if ($version == null || $version == "") {
        $version = "?";
    }

    return array(
        'userAgent' => $u_agent,
        'name' => $bname,
        'version' => $version,
        'platform' => $platform,
        'pattern' => $pattern
    );
}


/**
 * Liefert den verwendeten Browser des Clients
 *
 * @return array Browser und Version
 */
function getClientBrowser()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (($pos = strpos($userAgent, 'MSIE')) !== false) {
        list($version) = sscanf(substr($userAgent, $pos), 'MSIE %f; ');
        $browser = 'IE';
    }
    else if (strpos($userAgent, 'Opera')) {
        $browser = 'Opera';
    }
    else if (strpos($userAgent, 'Mozilla/([0-9].[0-9]{1,2})')) {
        $browser = 'Mozilla';
    }
    else {
        $browser = 'Other';
    }

    return array(
        'name' => $browser,
        'version' => $version
    );
}

/**
 * Liefert die IP des Clients (kann jedoch durch proxy oder anonymizer verfaelscht werden)
 *
 * @return string Remote/Client IP Adresse
 **/
function getClientIP()
{
    foreach (array(
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ) as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
    }
    }
        }
    }
    return '';
    }

/**
 * creates a browser fingerprint
 *
 * @param bool $withClientIP
 * @return string
 */
function getBrowserFingerprint($withClientIP=true)
{
    $data = ($withClientIP ? getClientIp() : '');
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['HTTP_ACCEPT'];
    $data .= $_SERVER['HTTP_ACCEPT_CHARSET'];
    $data .= $_SERVER['HTTP_ACCEPT_ENCODING'];
    $data .= $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    $hash = md5($data);
    return $hash;
}

/**
 * getHTTPReferer()
 * Liefert den HTTP Referer, woher ein Besucher auf Ihre Seite gekommen ist.
 *
 * @return string HTTP Referer oder "DIRECT LINK"
 **/
function getHTTPReferer()
{
    return ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : 'DIRECT LINK');
}

/**
 * Berechnet die Wochenblatt Kalenderwoche
 *
 * @param int $mon Monat
 * @param int $day Tag
 * @param int $year Jhar
 * @return unknown
 */
function getWoblaKW($mon, $day, $year, $wobla_spec = 4)
{
    $date = mktime(0, 0, 0, $mon, $day, $year);
    $kw = strftime('%V', $date); // KW nach ISO 8601:1988
    $weekday = strftime('%u', $date);
    if ($weekday >= $wobla_spec) {
        $kw = strftime('%V', mktime(0, 0, 0, $mon, $day + 7, $year)); // KW der n�chsten Woche ausrechnen
    }

    return sprintf('%02d', $kw);
}

/**
 * Liefert das Wochenblatt Monat
 *
 * @param int $mon Monat
 * @param int $day Tag
 * @param int $year Jahr
 * @param int $wobla_spec Wochentagschalter
 * @return int
 */
function getWoblaMonat($mon, $day, $year, $wobla_spec = 4)
{
    $date = mktime(0, 0, 0, $mon, $day, $year);
    $monat = strftime('%m', $date); // KW nach ISO 8601:1988
    $weekday = strftime('%u', $date);
    if ($weekday >= $wobla_spec) {
        $monat = strftime('%m', mktime(0, 0, 0, $mon, $day + 7, $year)); // KW der naechsten Woche ausrechnen
    }

    return sprintf('%02d', $monat);
}


/**
 * Berechnet das Wochenblatt Jahr
 *
 * @param int $mon
 * @param int $day
 * @param int $year
 * @param int $wobla_spec
 * @return int
 */
function getWoblaJahr($mon, $day, $year, $wobla_spec = 4)
{
    $date = mktime(0, 0, 0, $mon, $day, $year);
    $jahr = strftime('%G', $date); // Jahr
    $weekday = strftime('%u', $date);
    if ($weekday >= $wobla_spec) {
        $jahr = strftime('%G', mktime(0, 0, 0, $mon, $day + 7, $year)); // KW der n�chsten Woche ausrechnen
    }

    return sprintf('%04d', $jahr);
}

/**
 * Holt sich den Inhalt von PHP Skripten und gibt ihn per return Wert zurueck.
 *
 * @param string $includeFile Absoluter Dateipfad
 * @return string
 */
function getContentFromInclude($includeFile)
{
    ob_start();
    include($includeFile);
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

/**
 * Formatiert eine Zahl als W�hrung.
 *
 * @param string $value Wert
 * @param string $num_decimal_places Dezimalstellen
 * @param string $currency W�hrungssymbol
 * @return string Zahl formatiert als W�hrung
 */
function formatCurrency($value, $num_decimal_places = 2, $currency = '?')
{
    return number_format(floatval($value), $num_decimal_places, ',', '.').$currency;
}

/**
 * Formatiert Datenbank Timestamp (z.B. bei MySQL Feldtyp:timestamp) in ein beliebiges Datumsformat.
 *
 * @param int $datetime Datenbank Timestamp im Format YYYYMMDDhhmmss
 * @param string $format
 * @return string formatiertes Datum
 */
function formatDBTimestampAsDatetime($datetime, $format = '%d.%m.%Y %H:%M')
{
    $year = substr($datetime, 0, 4);
    $mon = substr($datetime, 4, 2);
    $day = substr($datetime, 6, 2);
    $hour = substr($datetime, 8, 2);
    $min = substr($datetime, 10, 2);
    $sec = substr($datetime, 12, 2);

    return formatDateTime(mktime($hour, $min, $sec, $mon, $day, $year), $format);
}

/**
 * Wandelt ein Array in das HTML Attribute Format um: name="Manhart" vorname="Alexander"
 *
 * @param array $array Array
 * @return string
 */
function arrayToAttr($array)
{
    $strHtmlTagAttr = '';
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if ($strHtmlTagAttr != '') $strHtmlTagAttr .= ' ';
            $strHtmlTagAttr .= $key.'="'.$value.'"';
        }
    }

    return $strHtmlTagAttr;
}

/**
 * F�gt Anf�hrungszeichen an den Anfang und an das Ende des Strings.
 *
 * @param string $str String
 * @param string $qMark Anf�hrungszeichen
 * @return string String mit Anf�hrungszeichen
 */
function quotationMarks($str, $qMark = '\'')
{
    return $qMark.$str.$qMark;
}

/**
 * Gibt 0 zurueck (ganz n�tzlich im Zusammenhang mit Array Initialisierung array_map('zero', $arr)).
 *
 * @return int 0
 */
function zero()
{
    return 0;
}

/**
 * Gibt einen Leerstring zurueck (ganz n�tzlich im Zusammenhang mit Array Initialisierung array_map('emptyString', $arr)).
 *
 * @return int 0
 */
function emptyString()
{
    return '';
}

/**
 * Ersetzt Deutsche Umlaute z.B. � => ae (+ Sonderzeichen) - ISO-8859-1
 *
 * @param string $subject Text (muss ISO-8859-X sein)
 * @return string
 */
function replaceUmlauts($subject)
{
    $pattern = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü', chr(196), chr(228), chr(214), chr(246), chr(220), chr(252), chr(223), ' ', '\'', '`', '´', '/');
    $replace = array('ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue', 'Ae', 'ae', 'Oe', 'oe', 'Ue', 'ue', 'ss', '_', '', '', '', '_');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Ersetzt Sonderzeichen eines Dateinames
 *
 * @param string $filename
 * @param string $replace
 * @return string
 */
function formatFilename($filename, $replace = '')
{
    $filename = replaceUmlauts($filename);
    $pattern = array('|', '*', ':', '<', '>', '"', '?');

    return str_replace($pattern, $replace, $filename);
}

/**
 * Wandelt deutsche Umlaute in HTML Zeichen um.
 *
 * @param string $subject Text
 * @return string
 */
function umlauts2html($subject)
{
    $pattern = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü');
    $replace = array('&auml;', '&ouml;', '&uuml;', '&szlig;', '&Auml;', '&Ouml;', '&Uuml;');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Wandelt HTML Zeichen in deutsche Umlaute um.
 *
 * @param string $subject Text
 * @return string
 */
function html2umlauts($subject)
{
    $pattern = array('&auml;', '&ouml;', '&uuml;', '&szlig;', '&Auml;', '&Ouml;', '&Uuml;');
    $replace = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Wandelt HTML Zeichen in  ASCII-Zeichen f�r Mailto um.
 *
 * @param string $subject Text
 * @return string
 */
function sonderzeichen2Mailtozeichen($subject)
{
    $pattern = array('&auml;', '&ouml;', '&uuml;', '&szlig;', '&Auml;', '&Ouml;', '&Uuml;', '�', '�', '�', '�', '�', '�', '�', 'ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü');
    $replace = array('%E4', '%F6', '%FC', '%DF', '%C4', '%D6', '%DC', '%E4', '%F6', '%FC', '%DF', '%C4', '%D6', '%DC', '%E4', '%F6', '%FC', '%DF', '%C4', '%D6', '%DC');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Wandelt sonder-Zeichen in deutsche Umlaute um.
 *
 * @param string $subject Text
 * @return string
 */
function sonder2umlauts($subject)
{
    $pattern = array('%C3%A4', '%C3%B6', '%C3%BC', '%C3%9F', '%C3%84', '%C3%96', '%C3%9C', '%20', '%26', '%2C', '%2F', '%2B');
    $replace = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü', ' ', '&', ',', '/', '+');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Wandelt deutsche Umlaute in HTML Zeichen um.
 *
 * @param string $subject Text
 * @return string
 */
function umlauts2htmlv2($subject)
{
    $pattern = array('ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü');
    $replace = array('&#'.ord('�').';', '&#'.ord('�').';', '&#'.ord('�').';', '&#'.ord('�').';', '&#'.ord('�').';', '&#'.ord('�').';', '&#'.ord('�').';');

    return str_replace($pattern, $replace, $subject);
}

/**
 * Umwandlung booleschen Ausdruck in Integer (0, 1)
 *
 * @param bool $bool Boolean
 * @return int 0 oder 1
 */
function bool2int($bool)
{
    return $bool ? 1 : 0; # weitere M�glichkeit: (int)$bool;
}

/**
 * Umwandlung booleschen Ausdruck in String ('true', 'false')
 *
 * @param bool $bool Boolean
 * @return string 'false' oder 'true'
 */
function bool2string($bool)
{
    return $bool ? 'true' : 'false';
}

/**
 * Umwandlung string Ausdruck ('true', 'false') in booleschen Ausdruck
 *
 * @param string $string Boolean als String
 * @return bool booleschen Ausdruck
 */
function string2bool($string)
{
    return ($string == 'true') ? true : false;
}

/**
 * checks if the object is empty.
 *
 * @param $obj
 * @return bool
 */
function isEmptyObject($obj)
{
    foreach ($obj as $xyz) return false;

    return true;
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht leer ist z.B. $arr = array_filter($arr, 'isNotEmpty').
 *
 * @param mixed $var
 * @return bool
 */
function isNotEmpty($var)
{
    return !empty($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht NULL ist.
 *
 * @param mixed $var
 * @return bool
 */
function isNotNull($var)
{
    return !is_null($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable kein leerer String ist.
 *
 * @param mixed $var
 * @return bool
 */
function isNotEmptyString($var)
{
    return !($var === '');
}

/**
 * Erzeugt aus einem langen Wort/Satz/Text einen g�ltigen Verzeichnisnamen
 *
 * @param string $string Wort/Satz/Text
 * @param string $char Zeichen, dass mindestens "minOccurrence" vorkommen muss. Standard ' ' (Leerzeichen).
 * @param integer $minOccurrence Anzahl Vorkommen von "char". Standard 1.
 * @param integer $minLen Mindestl�nge des Verzeichnisnamens. Standard 10.
 * @return Verzeichnisname
 */
function generateDirectory($string, $minOccurrence = 1, $minLen = 10, $char = ' ')
{
    // Verzeichnis Name ermitteln
    while (substr_count($string, $char) > $minOccurrence) {
        $len = strrpos($string, $char);
        if ($len < $minLen) break;
        $string = substr($string, 0, $len);
    }
    $string = replaceUmlauts($string);

    return $string;
}

/**
 * Druckt Dateien aus
 *
 * @param string $printer Druckername
 * @param array $files Dateien (z.B. PDF Dokumente)
 */
function printFiles($printer, $files)
{
    $files = array_map('escapeshellarg', $files);
    $command = 'lp -d '.$printer.' '.implode(' ', $files);
    exec($command, $output, $return_value);

    return ($return_value == 0);
}

/**
 * Zerlegt einen Satz anhand einer Satzl�nge
 *
 * @param string $str Satz
 * @param mixed [...] Satzl�nge
 * @return array
 */
function splitFixedLength($str)
{
    $record = array();
    $pos = 0;
    for ($a = 1, $num_args = func_num_args(); $a < $num_args; $a++) {
        $arg = (int)func_get_arg($a);
        array_push($record, substr($str, $pos, $arg));
        $pos += $arg;
    }
    unset($a, $pos, $arg, $str, $num_args);

    return $record;
}

/**
 * �berpr�ft, ob die Datei lokal existiert (das PHP file_exists erkennt neu angelegte Dateien auf der Shell/NFS Laufwerke nicht)
 *
 * @param string $file
 * @param string $remote z.B. rsh root@blub.de
 * @return boolean
 */
function shellFileExists($file, $remote = '')
{
    $cmd = 'test -e '.$file.' && echo 1 || echo 0';
    if ($remote != '') $cmd = $remote.' "'.$cmd.'"';
    exec($cmd, $arrOutFileExists);
    if (!isset($arrOutFileExists[0])) return false;
    $file_exists = (trim($arrOutFileExists[0]) === '1') ? true : false;

    return $file_exists;
}

/**
 * Erstellt Suchmuster f�r SQL-Statement. Sehr hilfreich, wenn man Textfeldsuche ben�tigt.
 * Z.B. Eingabe von A listet alle mit A beginnenden Treffer auf. Eingabe > Parameter "$min" listet
 * alle Treffer die die Eingabe enthalten auf.
 *
 * @param string $wert
 * @param int $min
 * @return string
 */
function getSearchPattern4SQL($wert, $min = 2)
{
    $len_wert = strlen($wert);

    if ($len_wert > 0) {
        $pattern = $wert.'%';
        if ($len_wert > $min) {
            $pattern = '%'.$pattern;
        }

        return $pattern;
    }

    return '';
}

/**
 * Setzt eine globale Variable
 *
 * @param string $key
 * @param mixed $value
 */
function setGlobal($key, &$value)
{
    $GLOBALS[$key] = &$value;
}

/**
 * Liefert Wert einer globalen Variable
 *
 * @param string $key
 * @return mixed
 */
function &getGlobal($key)
{
    return $GLOBALS[$key];
}

/**
 * Abfrage ob die globale Variable existiert
 *
 * @param string $key
 * @return bool
 */
function global_exists($key)
{
    return isset($GLOBALS[$key]);
}

/**
 * Magische PHP Konstanten in ein Array zusammenf�hren
 *
 * @param mixed $file __FILE__
 * @param mixed $line __LINE__
 * @param mixed $function __FUNCTION__ ab PHP 4.3
 * @param mixed $class __CLASS__ ab PHP 4.3
 * @param mixed $method erst ab PHP 5
 * @return array
 */
function magicInfo($file, $line, $function, $class, $specific = array())
{
    if (!is_array($specific)) {
        if (!is_null($specific)) {
            $specific = array($specific);
        }
    else $specific = array();
    }

    return array_merge(array(
        'file' => $file,
        'line' => $line,
        'function' => $function,
        'class' => $class
    ), $specific);
}

/**
 * Simple Array 2 Html (empfehle eher pray)
 *
 * @param array $array
 * @return string HTML
 */
function array2html($array)
{
    return '<p>'.nl2br(str_replace(' ', '&nbsp;', print_r($array, true))).'</p>';
}

/**
 * Umwandlung eines Array in JSON (ALEX: diese Funktion muss ueberarbeitet werden!!! Schrott)
 *
 * @param array $arr
 * @return string JSON
 * @deprecated Verwende json_encode!
 */
function array2json($arr)
{
    if (!is_array($arr)) {
        return json_encode($arr);
    }

    $parts = array();
    $is_list = false;

    // Ermitteln ob es sich bei dem Uebergabe-Array um ein numerisches/indexiertes Array handelt
    $keys = array_keys($arr);
    $max_length = count($arr) - 1;
    // falls der erste Schl�ssel 0 ist und der letzte Schluessel Laenge - 1, handelt es sich um ein numerisches Array
    if ((isset($keys[0]) and $keys[0] == '0') and ($keys[$max_length] == $max_length)) {
        $is_list = true;
        for ($i = 0; $i < count($keys); $i++) { // ueberpruefe jeden Schluessel, ob er zur Position korespondiert
            if ($i != $keys[$i]) { // Positionscheck schl�gt fehl
                $is_list = false; // Es handelt sich um ein assoziatives Array
                break;
            }
        }
    }

    foreach ($arr as $key => $value) {
        if (is_array($value)) { // Spezial Behandlung fuer Arrays
            if ($is_list) {
                $parts[] = array2json($value); /* :RECURSION: */
            }
            else {
                $parts[] = '"'.$key.'":'.array2json($value); /* :RECURSION: */
            }
        }
        else {
            $str = '';
            if (!$is_list) $str = '"'.$key.'":';

            // Spezial Behandlungen fuer mehrere Datentypen
            if (is_numeric($value)) {
                $str .= $value;
            } //Numbers
            elseif ($value === false) $str .= 'false'; //booleans
            elseif ($value === true) $str .= 'true';
            else $str .= '"'.addslashes($value).'"'; // alles andere
            // :TODO: Andere Datentypen (Objekt?)

            $parts[] = $str;
        }
    }
    $json = implode(',', $parts);

    if ($is_list) return '['.$json.']';//Rueckgabe: indexiertes JSON

    return '{'.$json.'}';//Rueckgabe assoziatives JSON
}

/**
 * Kodiert alle String Werte eines Arrays (rekursiv) nach RFC1738
 *
 * @param array $array
 * @return array
 */
function arrayEncodeToRFC1738($array)
{
    if (is_array($array)) {
        foreach ($array as $key => $val) {
            if (is_string($val)) {
                $array[$key] = rawurlencode($val);
            }
            elseif (is_array($val)) {
                $array[$key] = arrayEncodeToRFC1738($val); /* :RECURSION: */
            }
        }
    }
    else {
        if (is_string($array)) {
            $val = $array;

            return rawurlencode($val);
        }
    }

    return $array;
}

if (!function_exists('mime_content_type')) {
    /**
     * Ermittelt den Mime Content Type f�r eine Datei
     *
     * @param string $f Datei
     * @return string
     */
    function mime_content_type($f)
    {
        return trim(exec('file -bi '.escapeshellarg($f)));
    }
}

if (!function_exists('array_combine')) {
    /**
     * Erzeugt ein Array, indem es ein Array f�r die Schl�sel und ein anderes f�r die Werte verwendet
     *
     * @param array $keys Array f�r die Schl�ssel
     * @param array $values Array f�r die Werte
     * @return array Kombiniertes Array
     * @author Alexander Manhart
     */
    function array_combine($keys, $values)
    {
        foreach ($keys as $key) $array[$key] = array_shift($values);

        return $array;
    }
}


/**
 * Silbentrennung
 *
 * @param string $word Zu trennendes Wort
 * @return array M�gliche Trennpositionen
 */
function hyphenation($word)
{
    $hyphenationPositions = array();

    $wordLen = strlen($word);
    if ($wordLen > 2) {
        $allowHyphenation = false;
        $vowels = array('a', 'e', 'i', 'o', 'u', 'ä', 'ü', 'ö');
        /*
			-- "sch" wie in "A_sche"
			-- "ch" wie in "Untersu_chen"
			-- "ph" wie in "Ste_phan"
			-- "ck" wie in "Zu_cker"
			-- "pf" wie "A_pfel"
			-- "br" wie in "Unter_brechung"
			-- "pl" wie "Finanz_plan"
			-- "tr" wie in "An_trag"
			-- "st" wie in "Auf_stehen"
			-- "gr" wie in "Hinter_grund"
			*/
        $splices = array('sch', 'ch', 'ph', 'ck', 'pf', 'br', 'pl', 'tr', 'st', 'gr');
        $divider = array('-', '/', '\\', '*', '#', ';', '.', '+', '=', ')', '(', '&', '!', '?', '<', '>', ':', ' ', '_', '~');

        for ($i = 2; $i < $wordLen - 1; $i++) {
            $c0 = $word[$i - 1];
            if ($allowHyphenation == false and in_array($c0, $vowels)) {
                $allowHyphenation = true;
            }
            if ($allowHyphenation) {
                $c = $word[$i];
                $c1 = $word[$i + 1];
                $v = $c0 + $c;
                if ($v == 'ch' and $i > 2 and $word[$i - 2] == 's') {
                    $v = 'sch';
                }
                if (in_array($c1, $vowels) and (in_array($c, $vowels) == false) and (in_array($c, $divider) == false) and
                                                                                    (in_array($c0, $divider) == false)) {
                    if (in_array($v, $splices)) {
                        array_push($hyphenationPositions, ($i - strlen($v) + 1));
                    }
                    else {
                        array_push($hyphenationPositions, $i);
                    }
                }
            }
        }
    }

    return $hyphenationPositions;
}

/**
 * HTTP Protocol defined status codes
 *
 * @param int $num
 */
function HTTPStatus($num)
{
    static $http = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );

    header($http[$num]);
}

/**
 * L�dt das Dokument neu.
 *
 * @param $params array
 */
function reloadUrl($params = array())
{
    if (class_exists('Url')) {
        $Url = new Url();
        foreach ($params as $key => $val) {
            $Url->setParam($key, $val);
        }
        $Url->reloadUrl();
    }
    else {
        $http = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $http.$host.$uri;
        header('Location: '.$url);
        exit;
    }
}

if (!function_exists('array_intersect_key')) {
    function array_intersect_key($isec, $keys)
    {
        $argc = func_num_args();
        if ($argc > 2) {
            for ($i = 1; !empty($isec) && $i < $argc; $i++) {
                $arr = func_get_arg($i);
                foreach (array_keys($isec) as $key) {
                    if (!isset($arr[$key])) {
                        unset($isec[$key]);
                    }
                }
            }

            return $isec;
        }
        else {
            $res = array();
            foreach (array_keys($isec) as $key) {
                if (isset($keys[$key])) {
                    $res[$key] = $isec[$key];
                }
            }

            return $res;
        }
    }
}

/**
 * Verschiebt eine Datei
 *
 * @param string $source Quelle
 * @param string $dest Ziel
 */
function move_file($source, $dest)
{
    $res_copy = copy($source, $dest);
    if ($res_copy) $res_unlink = unlink($source);

    return ($res_copy and $res_unlink);
}

/**
 * Verzeichnis auslesen: erstellt Dateiliste
 *
 * @param string $path Stammverzeichnis
 * @param boolean $absolute Datei mit absolutem Pfad zurückgeben
 * @param string $filePattern Dateifilter
 * @param string $subdir auszulesendes Unterverzeichnis
 * @return array Dateiliste
 */
function readFiles($path, $absolute = true, $filePattern = '/.JPG/i', $subdir = '')
{
    $files = array();

    $path = addEndingSlash($path).addEndingSlash($subdir);
    if ($res = opendir($path)) {
        while (($filename = readdir($res)) !== false) {
            $file = $path.$filename;
            if (is_file($file) and preg_match($filePattern, $filename)) {
                $fileRelative = addEndingSlash($subdir).$filename;
                array_push($files, ($absolute) ? $file : $fileRelative);
            }
        }
        closedir($res);
    }

    return $files;
}

/**
 * Liest ein Verzeichnis rekursiv aus. Dabei kann man per regulärem Ausdruck auf Datei- oder Verzeichnisebene filtern. Die Ergebnisse werden absolut oder relativ zum
 * übergebenen Pfad zurück gegeben.
 *
 * @param $path Stammpfad
 * @param bool $absolute Datei mit absolutem Pfad
 * @param string $filePattern Dateifilter
 * @param string $dirPattern Verzeichnisfilter
 * @param string $subdir auszulesendes Unterverzeichnis
 * @return array
 * @throws Exception
 */
function readFilesRecursive($path, $absolute = true, $filePattern = '', $dirPattern = '/^[^\.].*$/', $subdir = '', $callback = null)
{
    $files = array();

    $root = $path;
    $path = addEndingSlash($path).addEndingSlash($subdir);
    $res = @opendir($path);
    if (!$res) {
        throw new \Exception('Pfad '.$path.' existiert nicht oder ist kein Verzeichnis oder hat keine Zugriffsberechtigung!');
    }
    while (($filename = readdir($res)) !== false) {
        $file = $path.$filename;
        $fileRelative = addEndingSlash($subdir).$filename;

        $filetyp = filetype($file);
        switch ($filetyp) {
            case 'dir':
                $dir = $filename;
                if ($dirPattern) {
                    if (!preg_match($dirPattern, $dir)) {
                        continue 2;
                    }
                }
                $subdirectory = $fileRelative;
                $files = array_merge(readFilesRecursive($root, $absolute, $filePattern, $dirPattern, $subdirectory, $callback), $files);
                // Doppelte gleichnamige Dateien gibt es nicht. Aber afufgrund der Callback Funktion implementiert (u.a. basename):
                // $files = array_unique($files, SORT_STRING);
                break;

            case 'file':
                if ($filePattern) {
                    if (!preg_match($filePattern, $filename)) {
                        continue 2;
                    }
                }
                $file = ($absolute) ? $file : $fileRelative;
                if ($callback != null) {
                    $file = call_user_func($callback, $file);
                }
                if ($file != '') {
                    array_push($files, $file);
                }
                break;
        }
    }
    closedir($res);

    return $files;
}

/**
 * Sortiert mehrere oder multidimensionale Arrays
 *
 * @param array $hauptArray Zu sortierendes Array
 * @param string $columnName Spaltenname im multidimensionalen Array
 * @param int $sorttype PHP Konstante
 * @param int $sortorder PHP Konstante
 * @return array
 */
function multisort($hauptArray, $columnName, $sorttype = SORT_STRING, $sortorder = SORT_ASC)
{
    $sortarr = array();
    foreach ($hauptArray as $row) {
        $sortarr[] = $row[$columnName];
    }

    if ($sorttype == SORT_STRING) {
        $sortarr = array_map('strtolower', $sortarr);
    }
    array_multisort($sortarr, $sortorder, $sorttype, $hauptArray);

    return $hauptArray;
}

/**
 * Wandelt eine deutsche Flie�kommazahl in das PHP �bliche amerikanische Format
 *
 * @param string $float Deutsche Gleitkommazahl
 * @return float
 */
function float_de2php($float)
{
    return floatval(str_replace(array('.', ','), array('', '.'), $float));
}

/**
 * Schaut, ob die Anfrage per Ajax kommt.
 * Genauer gesagt, ob die Variable $_SERVER['HTTP_X_REQUESTED_WITH'] auf XMLHttpRequest gesetzt ist.
 * Dies macht z.B. das Javascript-Framework Prototype. (Ajax.Request)
 *
 * @return boolean
 */
function isAjax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
           || (isset($_REQUEST['HTTP_X_REQUESTED_WITH']) && $_REQUEST['HTTP_X_REQUESTED_WITH']);
}

/**
 * Wandelt Daten mit Sonderzeichen f�r XML Entities in maskierte ASCII-Zeichen um (aus php.net).
 *
 * @param string $s
 * @return string
 * @deprecated
 */
function xmlEntities($s)
{
    //build first an assoc. array with the entities we want to match
    $table1 = get_html_translation_table(HTML_ENTITIES, ENT_QUOTES);

    //now build another assoc. array with the entities we want to replace (numeric entities)
    foreach ($table1 as $k => $v) {
        $table1[$k] = "/$v/";
        $c = htmlentities($k, ENT_QUOTES, "UTF-8");
        $table2[$c] = "&#".ord($k).";";
    }

    //now perform a replacement using preg_replace
    //each matched value in array 1 will be replaced with the corresponding value in array 2
    $s = preg_replace($table1, $table2, $s);

    return $s;
}

/**
 * Wandelt alle Sonderzeichen in entsprechende XML Entities um.
 *
 * @param string $string Zeichenkette
 * @param string $charset Zeichensatz der Daten in Parameter 1 ($string)
 * @return string Zeichenkette
 */
function xml_entitiy_encode($string, $charset = 'ISO-8859-1')
{
    $charset = strtoupper($charset);
    if ($charset == 'UTF-8') {
        $strout = charset_decode_utf_8($string);
    }
    else {
        $strout = htmlentities($string, ENT_QUOTES, $charset, false);
    }

    /*        $strout = '';

        for ($i = 0, $len = strlen($string); $i < $len; $i++) {
			$ord = ord($string[$i]);

            if (($ord > 0 && $ord < 32) || ($ord >= 127)) {
                $strout .= '&#'.$ord.';';
            }
            else {
                switch ($string[$i]) {
                    case '<':
                        $strout .= '&lt;';
                        break;

                    case '>':
                        $strout .= '&gt;';
                        break;

                    case '&':
                        $strout .= '&amp;';
                        break;

                    case '"':
                        $strout .= '&quot;';
                        break;

                    default:
                        $strout .= $string[$i];
                }
            }
        }*/

    return $strout;
}


/**
 * Code-Snippet geklaut aus squirrelmail
 *
 * @param string $string
 * @return string
 */
function charset_decode_utf_8($string)
{
    /* Only do the slow convert if there are 8-bit characters */
    /* avoid using 0xA0 (\240) in ereg ranges. RH73 does not like that */
    if (!ereg("[\200-\237]", $string) and !ereg("[\241-\377]", $string)) {
        return $string;
    }

    // decode three byte unicode characters
    $string = preg_replace("/([\340-\357])([\200-\277])([\200-\277])/e",
        "'&#'.((ord('\\1')-224)*4096 + (ord('\\2')-128)*64 + (ord('\\3')-128)).';'",
        $string);

    // decode two byte unicode characters
    $string = preg_replace("/([\300-\337])([\200-\277])/e",
        "'&#'.((ord('\\1')-192)*64+(ord('\\2')-128)).';'",
        $string);

    return $string;
}

/**
 * Wandelt Daten mit Sonderzeichen f�r XML Entities  in maskierte ASCII-Zeichen um.
 *
 * @param string $text
 * @param string $charset
 * @return string
 */
function xmlNumericEntities($text, $charset = 'UTF-8')
{
    return preg_replace('/[^\x09\x0A\x0D\x20-\x7F]/e', '"&#".ord($0).";"', htmlentities($text, ENT_QUOTES, $charset));
}

/**
 * Anzahl Kalenderwochen in einem Jahr
 *
 * @param int $y Jahr
 * @return int
 */
function getNumCW($y)
{
    return date('W', mktime(0, 0, 0, 12, 28, $y));
}

/**
 * Umrechnung DTP-Punkt in Millimeter (Desktop Publishing Wobla);
 *
 * @link http://de.wikipedia.org/wiki/Pica_%28Typografie%29
 * @param float $pp
 * @return float
 */
function pt2mm($pt)
{
    $mm = $pt * 0.35277;

    return $mm;
}

/**
 * Erzwingt einen Download im Browser
 *
 * @param string $file Datei (mit absolutem Pfad)
 * @param string $mimetype Mimetype z.b. application/octet-stream
 */
function forceFileDownload($file, $mimetype = '')
{
    if (empty($mimetype)) $mimetype = mime_content_type($file);
    if (empty($mimetype)) $mimetype = 'application/octet-stream';
    $filesize = filesize($file);

    // Start sending headers
    header('Pragma: public'); // required
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required for certain browsers
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: '.$mimetype);
    header('Content-Length: '.(string)$filesize);
    header('Content-Disposition: attachment; filename="'.basename($file).'";');

    readfile($file);
    exit;
}

/**
 * Laedt eine Datei aus dem Web auf den lokalen Rechner
 *
 * @param string $sourceFile Quelldatei aus dem Web/Intranet
 * @param string $destFile Lokale Zieldatei
 * @return int|false gibt die Anzahl geschriebener Bytes oder False zurueck
 */
function downloadFile($sourceFile, $destFile)
{
    return file_put_contents($destFile, fopen($sourceFile, 'r'), LOCK_EX);
}

/**
 * Die Funktion pr�ft mit Shell-Komandos, ob ein entferntes Verzeichnis gemountet ist.
 *
 * @param string $mountPoint (der exakte Mount-Point(so wie er in der /etc/fstab steht.))
 * @return int [0|1]
 */
function isMounted($mountPoint)
{
    if (is_dir($mountPoint)) { # man kann nur in ein Verzeichnis rein-mounten
        $mountPoint = removeEndingSlash($mountPoint);
        $cmd = 'mount | grep "'.$mountPoint.'" | wc -l | tr -d " "';
        $isMounted = intval(shell_exec($cmd));

        return $isMounted;
    }

    return 0;
}

function getXmlHeader($encoding = 'iso-8859-1')
{
    return '<?xml version="1.0" encoding="'.$encoding.'"?>';
}

/**
 * Ersatz fuer das PHP 4.X range Kommando, da es erst ab PHP 5.0.0 range mit $step gibt
 *
 * @param int $start
 * @param int $end
 * @param int $step
 * @return array
 */
function array_range($start, $end, $step = 1)
{
    $result = array();
    for ($i = $start; $i <= $end; $i += $step) {
        //do something with array
        $result[] = $i;
    }

    return $result;
}

/**
 * Extrahiert den Wochentag (1-7) aus einem engl. Datum
 *
 * @param string $datum Englishes Datum
 * @return int 1-7
 */
function extractTagFromDatum($datum)
{
    list($jahr, $monat, $tag) = explode('-', $datum);
    $ts = mktime(0, 0, 0, $monat, $tag, $jahr);

    return strftime('%u', $ts); // %u liefert 1 - 7 (=> Mo - So)
}

/**
 * Macht ein SVN generiertes Datum im Code nutzabar
 *
 * @param string $date
 * @param string $format
 * @return datetime
 */
function stripSvnDate($date, $format = 'd.m.Y H:i')
{
    return date($format, @strtotime(substr($date, 7, 19)));
}

/**
 * Wandelt einen QueryString in ein Array um.
 *
 * @param string $string
 * @param string $separator
 * @return array
 */
function parseQueryString($string, $separator = '&')
{
    $array = array();
    if ($string) {
        $blub = explode($separator, $string);
        foreach ($blub as $val) {
            list($key, $val) = explode('=', $val);
            $array[$key] = $val;
        }
    }

    return $array;
}

function Sec2Time($time)
{
    if (is_numeric($time)) {
        $value = array(
            "years" => 0,
            "days" => 0,
            "hours" => 0,
            "minutes" => 0,
            "seconds" => 0,
        );
        if ($time >= 31556926) {
            $value["years"] = floor($time / 31556926);
            $time = ($time % 31556926);
        }
        if ($time >= 86400) {
            $value["days"] = floor($time / 86400);
            $time = ($time % 86400);
        }
        if ($time >= 3600) {
            $value["hours"] = floor($time / 3600);
            $time = ($time % 3600);
        }
        if ($time >= 60) {
            $value["minutes"] = floor($time / 60);
            $time = ($time % 60);
        }

        $value["seconds"] = floor($time);

        return (array)$value;
    }
    else {
        return (bool)false;
    }
}

/**
 * Konvertiert relative Links z.B. einer Webseite in absolute Links.
 *
 * @param string $content HTML-Inhalt
 * @param string $url Absolute URL
 * @author Peter M. Howard <peter@wintermute.com.au>
 * @link http://wintermute.com.au/bits/2005-09/php-relative-absolute-links/
 */
function convertRelative2AbsoluteLinks($content, $url)
{
    $content = preg_replace('#(href|src)="([^:"]*)("|(?:(?:%20|\s|\+)[^"]*"))#', '$1="'.$url.'$2$3', $content);
}

/**
 * Errechnet die beste lesbare Farbe auf beliebiger Hintergrundfarbe hexcolor.
 *
 * @param string $hexcolor Hintergrundfarbe
 * @param string $dark Dunkle Farbe
 * @param string $light Helle Farbe
 * @return string Helle oder dunkle Farbe
 */
function legibleColor($hexcolor, $dark = '#000000', $light = '#FFFFFF')
{
    return (hexdec($hexcolor) > 0xffffff / 2) ? $dark : $light;
}

/**
 * Erzeugt einen zuf�lligen Farbcode
 *
 * @return string
 */
function randColor()
{
    $red = dechex(mt_rand(0, 255));
    $green = dechex(mt_rand(0, 255));
    $blue = dechex(mt_rand(0, 255));

    $rgb = $red.$green.$blue;

    if ($red == $green && $green == $blue) $rgb = substr($rgb, 0, 3);

    return '#'.$rgb;
}

/**
 * Wandelt UTF-8 in RTF Text um
 *
 * @param string $utf8_text
 * @return string
 * @author Kyle Gibson
 * @see http://spin.atomicobject.com/2010/08/25/rendering-utf8-characters-in-rich-text-format-with-php/
 */
function utf8_to_rtf($utf8_text)
{
    $utf8_patterns = array(
        "[\xC2-\xDF][\x80-\xBF]",
        "[\xE0-\xEF][\x80-\xBF]{2}",
        "[\xF0-\xF4][\x80-\xBF]{3}",
    );
    $new_str = $utf8_text;
    foreach ($utf8_patterns as $pattern) {
        $new_str = preg_replace("/($pattern)/e", "'\u'.hexdec(bin2hex(iconv('UTF-8', 'UTF-16BE', '$1'))).'?'", $new_str);
    }

    return $new_str;
}

/**
 * Fuehrt ein Kommando auf der Shell im Hintergrund aus und gibt die PID zurueck
 *
 * @param string $cmd
 * @param integer $priority
 * @return string
 */
function shell_exec_background($cmd, $priority = 0)
{
    if ($priority) {
        $PID = shell_exec('nohup nice -n '.$priority.' '.$cmd.' >/dev/null 2>&1 & echo $!');
    }
    else {
        $PID = shell_exec('nohup '.$cmd.' >/dev/null 2>&1 & echo $!');
    }

    return trim($PID);
}

/**
 * Prueft, ob der uebergebene Prozess (PID) noch laeuft
 *
 * @param int $PID
 * @return boolean
 */
function is_process_running($PID)
{
    exec('ps '.$PID, $state);

    return (count($state) >= 2);
}

/**
 * Berechne Alter
 *
 * @param date $from Datum im englischen Format
 * @param date $to Datum im englischen Format
 * @return int Alter
 */
function calcAge($from, $to = 'now')
{
    // funktioniert leider erst ab PHP 5.3
//    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
//        $age = date_diff(date_create($from), date_create($to))->y;
//      funktioniert nicht mit Schaltjahren und to = 2020-02-29
//    }
//    else {
        if ($to == 'now') {
            $to = date('%Y-%m-%d');
        }

        $from = strtotime($from); // von (eventl. Geburtsdatum)
        $to = strtotime($to); // bis

        $age = (intval(date('Y', $to)) - intval(date('Y', $from)));
        $from_month = date('m', $from);
        $to_month = date('m', $to);
        if ($from_month > $to_month) {
            $age -= 1;
        }
        elseif ($from_month == $to_month) {
            if (date('d', $from) > date('d', $to)) {
                $age -= 1;
            }
        }

    return $age;
}

/**
 * Formatiere Minuten um als Stunde-Minuten Text
 *
 * @param int Minuten
 * @return string
 */
function formatStdMin($min)
{
    $val = intval($min);

    return floor($val / 60).' Std. '.($val % 60).' Min.';
}

/**
 * Formatiere Minuten in 24h Format um
 *
 * @param $min
 * @return string
 */
function format24h($min)
{
    $val = intval($min);

    return str_pad((($val < 0) ? ceil($val / 60) : floor($val / 60)), 2, '0', STR_PAD_LEFT).':'.str_pad((($val % 60) * (($val < 0) ? -1 : 1)), 2, '0', STR_PAD_LEFT);
}

// Wochenende in PHP ermitteln
function isWochenende($intTag, $intMonat, $intJahr)
{
    // Wochentag berechnen
    $datum = getdate(mktime(0, 0, 0, $intMonat, $intTag, $intJahr));
    $wochentag = $datum['wday'];                            // wday: Numerische Repr�sentation des Wochentags zwischen 0 (f�r Sonntag) und 6 (f�r Sonnabend)
    // Pr�fen, ob Wochenende
    if ($wochentag == 0 || $wochentag == 6) {
        return true;
    }

    return false;
}


// feste und nichtfeste Feiertage in PHP ermitteln
function isFeiertag($intTag, $intMonat, $intJahr)
{
    # festliegende Feiertage
    if ($intTag == 1 AND $intMonat == 1) {
        return true; /*return "Neujahr";*/
    }
    if ($intTag == 6 AND $intMonat == 1) {
        return true; /*return "Dreik�nigstag";*/
    }
    if ($intTag == 1 AND $intMonat == 5) {
        return true; /*return "1. Mai";*/
    }
    if ($intTag == 15 AND $intMonat == 8) {
        return true; /*return "Maria Himmelfahrt";*/
    }
    if ($intTag == 3 AND $intMonat == 10) {
        return true; /*return "Tag der deutschen Einheit";*/
    }
    if ($intTag == 1 AND $intMonat == 11) {
        return true; /*return "Allerheiligen";*/
    }
    if ($intTag == 25 AND $intMonat == 12) {
        return true; /*return "1. Weihnachtstag";*/
    }
    if ($intTag == 26 AND $intMonat == 12) {
        return true; /*return "2. Weihnachtstag";*/
    }

    # nichtfeste Feiertage
    if ($intTag == date("j", easter_date($intJahr)) AND $intMonat == date("n", easter_date($intJahr))) {
        return true; /*return "Ostersonntag";*/
    }
    if ($intTag == date("j", (easter_date($intJahr)) - 2 * 86400) AND $intMonat == date("n", (easter_date($intJahr)) - 2 * 86400)) {
        return true; /*return "Karfreitag";*/
    }
    if ($intTag == date("j", (easter_date($intJahr)) + 86400) AND $intMonat == date("n", (easter_date($intJahr)) + 86400)) {
        return true; /*return "Ostermontag";*/
    }
    if ($intTag == date("j", (easter_date($intJahr)) + 39 * 86400) AND $intMonat == date("n", (easter_date($intJahr)) + 39 * 86400)) {
        return true; /*return "Christi Himmelfahrt";*/            /* 39 Tage nach Ostersonntag */
    }
    if ($intTag == date("j", (easter_date($intJahr)) + 50 * 86400) AND $intMonat == date("n", (easter_date($intJahr)) + 50 * 86400)) {
        return true; /*return "Pfingstmontag";*/                    /* 49 Tage nach Ostersonntag */
    }
    if ($intTag == date("j", (easter_date($intJahr)) + 60 * 86400) AND $intMonat == date("n", (easter_date($intJahr)) + 60 * 86400)) {
        return true; /*return "Fronleichnam";*/                    /* 2. Donnerstag nach Ostersonntag */
    }

    return false;
}

// Halbfeiertage in PHP ermitteln
function isHalbfeiertag($intTag, $intMonat, $intJahr)
{
    if ($intTag == 24 AND $intMonat == 12) {
        return true; /*return "Heiligabend";*/
    }
    if ($intTag == 31 AND $intMonat == 12) {
        return true; /*return "Silvester";*/
    }

    return false;
}

/**
 * Zaehle alle Grossbuchstaben in einem String
 *
 * @param string $str Zu zaehlender String
 * @return int Anzahl Grossbuchstaben
 */
function count_chars_upper($str)
{
    $i_Count = 0;
    $pattern = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    for ($i = 0; $i < strlen($str); $i++) {
        if (strpos($pattern, substr($str, $i, 1)) !== false) {
            $i_Count++;
        }
    }

    return $i_Count;
}

/**
 * Zaehle alle Kleinbuchstaben in einem String
 *
 * @param string $str zu zaehlender String
 * @return int Anzahl Kleinbuchstaben
 */
function count_chars_lower($str)
{
    $i_Count = 0;
    $pattern = 'abcdefghijklmnopqrstuvwxyz';
    for ($i = 0; $i < strlen($str); $i++) {
        if (strpos($pattern, substr($str, $i, 1)) !== false) {
            $i_Count++;
        }
    }

    return $i_Count;
}

/**
 * Convert a shorthand byte value from a PHP configuration directive to an integer value
 *
 * @param string $value
 * @return   int
 */
function convertBytes($value)
{
    if (is_numeric($value)) {
        return $value;
    }
    else {
        $value_length = strlen($value);
        $qty = substr($value, 0, $value_length - 1);
        $unit = strtolower(substr($value, $value_length - 1));
        switch ($unit) {
            case 'k':
                $qty *= 1024;
                break;
            case 'm':
                $qty *= 1048576;
                break;
            case 'g':
                $qty *= 1073741824;
                break;
        }

        return $qty;
    }
}

/**
 * @param $code Passwort oder Coupon
 * @param string $pepper zusätzliche Verschlüsselung mit einem serverseitigen Schlüssel (= Pfeffer). Mit pepper ist der zurückgegebene Hash 108 Zeichen lang, ohne 60 Zeichen!
 * @param array $options individuelles Salt,
 * @return string Hash
 * @throws Exception, InvalidArgumentException
 */
function pool_hash_code($code, $pepper = '', array $options = [])
{
    if (!defined('CRYPT_BLOWFISH')) {
        throw new Exception('The CRYPT_BLOWFISH algorithm is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (empty($code)) {
        throw new InvalidArgumentException('Cannot hash an empty code.');
    }

    $length = 22;
    $binaryLength = ($length * 3 / 4 + 1);

    $options += [
        'salt' => substr(strtr(base64_encode(mcrypt_create_iv($binaryLength, MCRYPT_DEV_URANDOM)), '+', '.'), 0, $length),
        'cost' => 10
    ];

    if (version_compare(PHP_VERSION, '5.3.7') >= 0) {
        $algorithm = '2y'; // BCrypt, mit korrigiertem Unicode Problem
    }
    else {
        $algorithm = '2a'; // BCrypt
    }

    $cryptParams = sprintf('$%s$%02d$%s', $algorithm, $options['cost'], $options['salt']);
    $hash = crypt($code, $cryptParams);

    if ($pepper) {
        $encryptedHash = encryptTwofish($hash, $pepper);
        $hash = base64_encode($encryptedHash);
    }

    return $hash;
}

/**
 * Prüft, ob das Password einem gegebenen Hashwert entspricht. Damit kann ein
 * vom Benutzer eingegebenes Passwort, mit dem in der Datenbank gespeicherten
 * Hashwert verglichen werden.
 *
 * @param string $code Zu prüfendes Passwort.
 * @param string $existingHash Gespeicherter Hashwert aus der Datenbank.
 * @param string $pepper Übergeben Sie denselben key, welcher zur Verschlüsselung des Hashwertes benutzt wurde, oder lassen Sie den Parameter weg wenn kein key angegeben wurde.
 * @return bool Gibt true zurück, wenn das Passwort mit dem Hashwert übereinstimmt, sonst false.
 * @throws Exception
 */
function pool_verify_hash($code, $existingHash, $pepper = '')
{
    if (!defined('CRYPT_BLOWFISH')) {
        throw new Exception('The CRYPT_BLOWFISH algorithm is required (PHP 5.3).');
    }

    if (empty($code)) {
        return false;
    }

    // Hashwert mit dem serverseitigem Key entschlüsseln
    if ($pepper != '') {
        $encryptedHash = base64_decode($existingHash);
        $existingHash = decryptTwofish($encryptedHash, $pepper);
    }

    // Die Parameter, die urspruenglich zum Erstellen von $existingHash verwendet wurden,
    // werden automatisch aus den ersten 29 Zeichen von $existingHash extrahiert.
    $newHash = crypt($code, $existingHash);

    return $newHash === $existingHash;
}

function encryptRijndael256($data, $key)
{
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_RIJNDAEL_256')) {
        throw new Exception('The MCRYPT_RIJNDAEL_256 algorithm is required (PHP 5.3).');
    }


    // Der cbc mode ist dem ecb mode vorzuziehen
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');

    // Twofish akzeptiert einen Schlüssel von 32 Bytes. Da in der Regel längere Strings
    // mit nur lesbaren Zeichen übergeben werden, wird ein binärer String erzeugt.
    $binaryKey = hash('sha256', $key, true);

    // Erstelle Initialisierungsvektor mit 16 Bytes
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $encryptedData = mcrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Kombiniere iv und verschlüsselten Text
    return $iv.$encryptedData;
}

function decryptRijndael256($encryptedData, $key)
{
    if (!defined('MCRYPT_RIJNDAEL_256')) {
        throw new Exception('The MCRYPT_RIJNDAEL_256 algorithm is required (PHP 5.3).');
    }

    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');

    // Extrahiere Initialisierungsvektor
    $ivSize = mcrypt_enc_get_iv_size($td);
    $iv = substr($encryptedData, 0, $ivSize);
    $encryptedData = substr($encryptedData, $ivSize);

    $binaryKey = hash('sha256', $key, true);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $decryptedData = mdecrypt_generic($td, $encryptedData);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Originaldaten wurden ergänzt mit 0-Zeichen bis zur Blockgrösse
    return rtrim($decryptedData, "\0");
}

function encryptRijndael($data, $key)
{
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_RIJNDAEL_128')) {
        throw new Exception('The MCRYPT_RIJNDAEL_128 algorithm is required (PHP 5.3).');
    }


    // Der cbc mode ist dem ecb mode vorzuziehen
    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');

    // Twofish akzeptiert einen Schlüssel von 16 Bytes. Da in der Regel längere Strings
    // mit nur lesbaren Zeichen übergeben werden, wird ein binärer String erzeugt.
    $binaryKey = hash('md5', $key, true);

    // Erstelle Initialisierungsvektor mit 16 Bytes
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $encryptedData = mcrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Kombiniere iv und verschlüsselten Text
    return $iv.$encryptedData;
}

function decryptRijndael($encryptedData, $key)
{
    if (!defined('MCRYPT_RIJNDAEL_128')) {
        throw new Exception('The MCRYPT_RIJNDAEL_128 algorithm is required (PHP 5.3).');
    }

    $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');

    // Extrahiere Initialisierungsvektor
    $ivSize = mcrypt_enc_get_iv_size($td);
    $iv = substr($encryptedData, 0, $ivSize);
    $encryptedData = substr($encryptedData, $ivSize);

    $binaryKey = hash('md5', $key, true);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $decryptedData = mdecrypt_generic($td, $encryptedData);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Originaldaten wurden ergänzt mit 0-Zeichen bis zur Blockgrösse
    return rtrim($decryptedData, "\0");
}

/**
 * Verschlüsselt Daten mit dem TWOFISH Algorithmus. Der IV Vektor wird
 * Bestandteil des resultierenden binären Strings.
 *
 * @param string $data Zu verschlüsselnde Daten. \0 Zeichen am Schluss gehen verloren.
 * @param string $key Mit diesem Schlüssel werden die Daten verschlüsselt.
 * @return string Gibt die verschlüsselten Daten in Form eines binären Strings zurück.
 * @throws Exception
 */
function encryptTwofish($data, $key)
{
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_TWOFISH')) {
        throw new Exception('The MCRYPT_TWOFISH algorithm is required (PHP 5.3).');
    }

    // Der cbc mode ist dem ecb mode vorzuziehen
    $td = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_CBC, '');

    // Twofish akzeptiert einen Schlüssel von 32 Bytes. Da in der Regel längere Strings
    // mit nur lesbaren Zeichen übergeben werden, wird ein binärer String erzeugt.
    $binaryKey = hash('sha256', $key, true);

    // Erstelle Initialisierungsvektor mit 16 Bytes
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $encryptedData = mcrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Kombiniere iv und verschlüsselten Text
    return $iv.$encryptedData;
}

/**
 * Entschlüsselt Daten, welche vorher mit @param string $encryptedData Binärer string mit verschlüsselten Daten.
 *
 * @param string $key Dieser Schlüssel wird verwendet um die Daten zu entschlüsseln.
 * @return string Gibt die originalen entschlüsselten Daten zurück.
 * @throws Exception
 * @see encryptTwofish verschlüsselt wurden.
 */
function decryptTwofish($encryptedData, $key)
{
    if (!defined('MCRYPT_TWOFISH')) {
        throw new Exception('The MCRYPT_TWOFISH algorithm is required (PHP 5.3).');
    }

    $td = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_CBC, '');

    // Extrahiere Initialisierungsvektor
    $ivSize = mcrypt_enc_get_iv_size($td);
    $iv = substr($encryptedData, 0, $ivSize);
    $encryptedData = substr($encryptedData, $ivSize);

    $binaryKey = hash('sha256', $key, true);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $decryptedData = mdecrypt_generic($td, $encryptedData);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Originaldaten wurden ergänzt mit 0-Zeichen bis zur Blockgrösse
    return rtrim($decryptedData, "\0");
}

/**
 * Generiere Code, Coupon, Serial...
 *
 * @param int $bytes Anzahl Zeichen
 * @param int $parts Anzahl Blöcke
 * @param array $options
 * @return string
 */
function pool_generate_code($bytes = 10, $parts = 1, array $options = [])
{
    $ascii = array(
        0 => array(48, 57), // 0-9
        1 => array(97, 122) // a-z
    );

    $options += [
        'uppercase' => 1,
        'numbers' => 50,
        'delimiter' => '-'
    ];

    $code = '';
    for ($p = 0; $p < $parts; $p++) {
        if ($p > 0) {
            $code .= $options['delimiter'];
        }
        for ($b = 0; $b < $bytes; $b++) {
            $key = (mt_rand(1, 100) <= $options['numbers'] ? 0 : 1);
            $byte = chr(mt_rand($ascii[$key][0], $ascii[$key][1]));
            $code .= $byte;
        }
    }

    if ($options['uppercase']) {
        $code = strtoupper($code);
    }

    return $code;
}

/**
 * @param $pdf Quelle (PDF)
 * @param $jpg Ziel (JPEG)
 * @param $output GS Ausgabe
 * @param int $resolution dpi
 * @param boolean $sudo bei NFS notwendig
 * @return bool Erfolgsstatus
 */
function pdf2jpg($pdf, $jpg, &$output, $resolution = 72, $sudo = false)
{
    # Setzen der Fontmap
    //    GS_FONTMAP=/opt/AVE_Javaserver/EDV_ORG/gs/Fontmap

    # Setzen des Fontdirectories
    //    GS_LIB=/opt/AVE_Javaserver/EDV_ORG/gs/wobla_fonts:/opt/AVE_Javaserver/EDV_ORG/ghostscript-9.06/share/ghostscript/9.06/lib
    //    export GS_LIB

    //    $GS_BIN/gs -dSAFER -dNOPAUSE -dBATCH -dNOPROMPT -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dAlignToPixels=0 -dGridFitTT=2 -sFONTMAP=$GS_FONTMAP -sDEVICE=jpeg -dNumRenderingThreads=4 -dBufferSpace=300000000 -sOutputFile=$OUTPUT.${RESOLUTION}dpi.jpg -r$RESOLUTION -dUseCropBox $INPUT


    $cmd = ($sudo ? 'sudo ' : '').GHOSTSCRIPT_BIN.' -q  -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=jpeg" -dTextAlphaBits=4 '.
           '-dGraphicsAlphaBits=4 "-r'.$resolution.'x'.$resolution.'" -dUseCropBox "-sOutputFile='.$jpg.'" "-f'.$pdf.'"';
    exec($cmd, $output, $return_var);
    $result = ($return_var == 0 and file_exists($jpg));
    if (!$result and count($output) == 0 and $sudo) {
        $output[] = 'Sudo ist für Ghostscript auf dem Rechner '.$_SERVER['SERVER_NAME'].' nicht konfiguriert!';
    }

    return $result;
}

function getFieldData($array, $column)
{
    return array_filter($array, function($key) use ($column) {
        return ($key == $column);
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * @see http://php.net/manual/de/function.hash-equals.php
 */
if (!function_exists('hash_equals')) {
    function hash_equals($known_string, $user_string)
    {
        $ret = 0;

        if (strlen($known_string) !== strlen($user_string)) {
            $user_string = $known_string;
            $ret = 1;
        }

        $res = $known_string ^ $user_string;

        for ($i = strlen($res) - 1; $i >= 0; --$i) {
            $ret |= ord($res[$i]);
        }

        return !$ret;
    }
}

/**
 * PHP 7 - random_int : Generates cryptographically secure pseudo-random integers
 */
if (!function_exists('random_int')) {
    function random_int($min, $max)
    {
        if (!function_exists('mcrypt_create_iv')) {
            trigger_error(
                'mcrypt must be loaded for random_int to work',
                E_USER_WARNING
            );

            return null;
        }

        if (!is_int($min) || !is_int($max)) {
            trigger_error('$min and $max must be integer values', E_USER_NOTICE);
            $min = (int)$min;
            $max = (int)$max;
        }

        if ($min > $max) {
            trigger_error('$max can\'t be lesser than $min', E_USER_WARNING);

            return null;
        }

        $range = $counter = $max - $min;
        $bits = 1;

        while ($counter >>= 1) {
            ++$bits;
        }

        $bytes = (int)max(ceil($bits / 8), 1);
        $bitmask = pow(2, $bits) - 1;

        if ($bitmask >= PHP_INT_MAX) {
            $bitmask = PHP_INT_MAX;
        }

        do {
            $result = hexdec(
                          bin2hex(
                              mcrypt_create_iv($bytes, MCRYPT_DEV_URANDOM)
                          )
                      ) & $bitmask;
        } while ($result > $range);

        return $result + $min;
    }
}

/*
 * PHP 5.3/5.4 Wrapper for array_column
 */

if (!function_exists('array_column')) {
    function array_column($array, $column_name)
    {
        return array_map(
            function($element) use ($column_name) {
                return $element[$column_name];
            },
            $array);
    }
}

/**
 * creates path from last alphanumeric characters
 *
 * @param $chars
 * @param int $numberOfDirectories
 * @return string
 */
function createPathFromLastChars($chars, $numberOfDirectories=4)
{
    $result = '';
    for($i=(-1*$numberOfDirectories); $i<0; $i++) {
        $result .= addEndingSlash(substr($chars, $i, 1));
    }
    return $result;
}

/**
 * Liefert einen Filenamen, der noch nicht im Ordner existiert.
 * Existiert die Datei bereits, wird durchnummeriert:
 * meinDokument.pdf
 * meinDokument-01.pdf
 * meinDokument-02.pdf
 *
 */
function nextFreeFilename($dir, $filename, $delimiter='-') {
	$filepath = addEndingSlash($dir) . $filename;
	if (file_exists($filepath)) {

		$info = pathinfo($filepath);
		// echo pray($info);

		$filenameNoExtension = $info['filename'];
		$extension           = $info['extension'];

		$pos = strrpos($filenameNoExtension, $delimiter);
		if ($pos === false) {
			$nr = 1;
			$newFilename = $filenameNoExtension . $delimiter . sprintf('%02d', $nr) . '.' . $extension;
		}
		else {
			$filenameNoNumber =  mb_substr($filenameNoExtension, 0, $pos);
			$nr               =  mb_substr($filenameNoExtension, $pos+1);
			if (is_numeric($nr)) {
				$nr =  intval($nr) + 1;
				$newFilename = $filenameNoNumber . $delimiter . sprintf('%02d', $nr) . '.' . $extension;
			}
			else {
				$nr = 1;
				$newFilename = $filenameNoExtension . $delimiter  . sprintf('%02d', $nr) . '.' . $extension;
			}

		}
		return nextFreeFilename($dir, $newFilename, $delimiter);
	}
	else {
		return $filename;
	}
}