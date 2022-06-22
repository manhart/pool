<?php
/*
 * g7system.local
 *
 * deprecated.php created at 13.05.22, 14:42
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */


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
 * Laedt ein Jpeg und gibt bei Misserfolg ein Fehlerbild zurueck
 *
 * @param string $imgname Dateiname (inkl. Pfad)
 * @param string $text Fehlertext im Bild
 * @return resource Resource ID (siehe GD Lib)
 **/
function loadJpeg($imgname, $text = 'Erren when opening jpeg: %s')
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

// Wochenende in PHP ermitteln
function isWeekend($intTag, $intMonat, $intJahr)
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
    return intval($parts[1]);
} // end func weekOfYear


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