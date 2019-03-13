<?php
/**
 * POOL (PHP Object Oriented Library): die Datei PublicHoliday.class.php berechnet Feiertage f�r Deutschland.
 *
 * Letzte �nderung am: $Date: 2006/04/05 08:40:53 $
 *
 * @version $Id: PublicHoliday.class.php,v 1.4 2006/04/05 08:40:53 aziz Exp $
 * @version $Revision: 1.4 $
 * @version
 *
 * @since 2006-01-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 * @package pool
 */

if(!defined('CLASS_PUBLICHOLIDAY')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_PUBLICHOLIDAY',			1);



    /**
     * Klasse zum Berechnen von gesetzlichen Feiertagen
     *
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
     * @package pool
     */
    class PublicHoliday extends PoolObject
    {
        /**
         * Ostersonntag Zeitstempel
         *
         * @var int
         */
        var $easterDate=null;

        var $year=null;

        /**
         * Konstruktor ermittelt Ostersonntag
         *
         * @param int $year
         * @return PublicHoliday
         */
        function __construct($year=null)
        {
            if(!$year) $year = date('Y');
            $this -> year = $year;

            $this -> __makeEasterDate();
        }

        /**
         * Ostersonntag ausrechnen
         *
         * @access private
         */
        function __makeEasterDate()
        {
            $easter_days = easter_days($this -> year);
            $this -> easterDate = mktime(0, 0, 0, 3, 21 + $easter_days, $this -> year);
        }

        /**
         * Berechnet gesetzliche Feiertage f�r einen bestimmten Zeitraum
         *
         * @param int $fromDate Unix Zeitstempel
         * @param int $toDate Unix Zeitstempel
         * @return array
         */
        function calcHolidaysByRange($fromDate, $toDate)
        {
            $holidaysByRange = array();
            $fromYear = (int)date('Y', $fromDate);
            $toYear = (int)date('Y', $toDate);

            for($i=$fromYear; $i <= $toYear; $i++) {
                $this -> year = $i;
                $this -> __makeEasterDate();

                $holidays = $this -> calcHolidays();

                asort($holidays);

                while(list($key, $val) = each($holidays)) {
                    if($fromDate <= $val and $val <= $toDate) {
                        $holidaysByRange[$val] = $key;
                    }
                }
            }
            return $holidaysByRange;
        }

        /**
         * Berechnet gesetzliche Feiertage f�r ein Jahr
         *
         * @access public
         * @return array
         */
        function calcHolidays()
        {
            $easterDay = date('d', $this -> easterDate);
            $easterMonth = date('m', $this -> easterDate);
            $easterYear = date('Y', $this -> easterDate);

            $holidays = array();

            $holidays['Neujahr'] = mktime(0, 0, 0, 1, 1, $easterYear);

            //if($fs=='BA' or $fs=='BW' or $fs=='SA' or $fs=='A') {
                // BW, BA, SA, A
            $holidays['Hl. 3 K�nige'] = mktime(0, 0, 0, 1, 6, $easterYear);
            //}

            $holidays['Karfreitag'] = mktime(0, 0, 0, $easterMonth, ($easterDay - 2), $easterYear);

            $holidays['Ostersonntag'] = $this -> easterDate;

            $holidays['Ostermontag'] = mktime(0, 0, 0, $easterMonth, ($easterDay + 1), $easterYear);

            $holidays['Tag der Arbeit'] = mktime(0, 0, 0, 5, 1, $easterYear);
            $holidays['Maifeiertag'] = $holidays['Tag der Arbeit'];

            $holidays['Christi Himmelfahrt'] = mktime(0, 0, 0, $easterMonth, ($easterDay + 39), $easterYear);

            $holidays['Pfingstsonntag'] = mktime(0, 0, 0, $easterMonth, ($easterDay + 49), $easterYear);

            $holidays['Pfingstmontag'] = mktime(0, 0, 0, $easterMonth, ($easterDay + 50), $easterYear);

            // BW, BA, H, NW, RP, SR, S, TH, A
            //if($fs=='BW' or $fs=='BA' or $fs=='H' or $fs=='NW' or $fs=='RP' or $fs=='SR' or $fs=='S' or $fs=='TH' or $fs=='A') {
            $holidays['Fronleichnam'] = mktime(0, 0, 0, $easterMonth, ($easterDay + 60), $easterYear);
            //}

            // A
            //if($fs=='A') {
            $holidays['Friedensfest'] = mktime(0, 0, 0, 8, 8, $easterYear);
            //}

            // BA, SR, A
            //if($fs=='BA' or $fs=='SR' or $fs=='A') {
            $holidays['Mari� Himmelfahrt'] = mktime(0, 0, 0, 8, 15, $easterYear);
            //}

            $holidays['Tag der Deutschen Einheit'] = mktime(0, 0, 0, 10, 3, $easterYear);

            // BR, MV, S, SA, TH
            //if($fs=='BR' or $fs=='MV' or $fs=='S' or $fs=='SA' or $fs=='TH') {
            $holidays['Reformationsfest'] = mktime(0, 0, 0, 10, 31, $easterYear);
            //}

            // BW, BA, NW, RP, SR, A
            //if($fs=='BW' or $fs=='BA' or $fs=='NW' or $fs=='RP' or $fs=='SR' or $fs=='A') {
            $holidays['Allerheiligen'] = mktime(0, 0, 0, 11, 1, $easterYear);
            //}

            $holidays['1. Weihnachtstag'] = mktime(0, 0, 0, 12, 25, $easterYear);

            // S
            // Bu� und Bettag (in Bayern Feiertag, aber nur Sch�ler haben hier frei
            $wochenTag=date('w', mktime(0, 0, 0, 11, 1, $easterYear));
            if($wochenTag < 3) {
                $wochenTag += 7;
            }
            $holidays['Bu� und Bettag'] = mktime(0, 0, 0, 11, (25 - $wochenTag), $easterYear);

            $holidays['2. Weihnachtstag'] = mktime(0, 0, 0, 12, 26, $easterYear);

            return $holidays;
        }

        /**
         * Gibt die Feiertagsnamen f�r das entsprechende Bundesland aus.
         *
         * @param string $fs BW: Baden W�rttemberg  |  BA: Bayern  |  B: Berlin  |  BR: Brandenburg  |  HB: Bremen  |  HH: Hamburg  |  H: Hessen  |  MV: Mecklenburg-Vorpommern  |  N: Niedersachsen  |  NW: Nordrhein-Westfalen  |  RP: Rheinland-Pfalz  |  SR: Saarland  |  S: Sachsen  |  SA: Sachsen-Anhalt  |  SH: Schleswig-Holstein  |  TH: Th�ringen  |  A: Stadt Augsburg
         * @return array
         */
        function getHolidayNames($fs='BA')
        {
            $names = array();
            $names[] = 'Neujahr';
            if($fs=='BA' or $fs=='BW' or $fs=='SA' or $fs=='A') {
                $names[] = 'Hl. 3 K�nige';
            }
            $names[] = 'Karfreitag';
            $names[] = 'Ostersonntag';
            $names[] = 'Ostermontag';
            $names[] = 'Tag der Arbeit';
            $names[] = 'Maifeiertag';
            $names[] = 'Christi Himmelfahrt';
            $names[] = 'Pfingstsonntag';
            $names[] = 'Pfingstmontag';
            if($fs=='BW' or $fs=='BA' or $fs=='H' or $fs=='NW' or $fs=='RP' or $fs=='SR' or $fs=='S' or $fs=='TH' or $fs=='A') {
                $names[] = 'Fronleichnam';
            }
            if($fs=='A') {
                $names[] = 'Friedensfest';
            }
            if($fs=='BA' or $fs=='SR' or $fs=='A') {
                $names[] = 'Mari� Himmelfahrt';
            }
            $names[] = 'Tag der Deutschen Einheit';
            if($fs=='BR' or $fs=='MV' or $fs=='S' or $fs=='SA' or $fs=='TH') {
                $names[] = 'Reformationsfest';
            }
            if($fs=='BW' or $fs=='BA' or $fs=='NW' or $fs=='RP' or $fs=='SR' or $fs=='A') {
                $names[] = 'Allerheiligen';
            }
            $names[] = '1. Weihnachtstag';
            if($fs=='S') {
                $names[] = 'Bu� und Bettag';
            }
            $names[] = '2. Weihnachtstag';
            return $names;
        }
    }
}

/* 

@see www.kalenderlexikon.de
@see http://www.dagmar-mueller.de/wdz/html/feiertagsberechnung.html

BW: Baden W�rttemberg  |  BA: Bayern  |  B: Berlin  |  BR: Brandenburg  |  HB: Bremen  |  HH: Hamburg  |  
H: Hessen  |  MV: Mecklenburg-Vorpommern  |  N: Niedersachsen  |  NW: Nordrhein-Westfalen  |  
RP: Rheinland-Pfalz  |  SR: Saarland  |  S: Sachsen  |  SA: Sachsen-Anhalt  |  SH: Schleswig-Holstein  |  
TH: Th�ringen  |  A: Stadt Augsburg*/

// Neujahr setzen (fester Feiertag am 1. Januar) 
// Hl. Drei K�nige setzen (fester Feiertag am 6. Januar) 
// Rosenmontag berechnen (beweglicher Feiertag; 48 Tage vor Ostern) 
// Aschermittwoch berechnen (beweglicher Feiertag; 46 Tage vor Ostern) 
// Karfreitag berechnen (beweglicher Feiertag; 2 Tage vor Ostern) 
// Ostersonntag 
// Ostermontag berechnen (beweglicher Feiertag; 1 Tag nach Ostern) 
// Maifeiertag setzen (fester Feiertag am 1. Mai) 
// Christi Himmelfahrt berechnen (beweglicher Feiertag; 39 Tage nach Ostern) 
// Pfingstsonntag berechnen (beweglicher Feiertag; 49 Tage nach Ostern) 
// Pfingstmontag berechnen (beweglicher Feiertag; 50 Tage nach Ostern) 
// Fronleichnam berechnen (beweglicher Feiertag; 60 Tage nach Ostern) 
// Mari� Himmelfahrt setzen (fester Feiertag am 15. August)
// Tag der deutschen Einheit setzen (fester Feiertag am 3. Oktober) 
// Reformationstag setzen (fester Feiertag am 31. Oktober) 
// Allerheiligen setzen (fester Feiertag am 1. November) 
// Heiligabend setzen (fester 'Feiertag' am 24. Dezember) 
// Erster Weihnachtstag setzen (fester 'Feiertag' am 25. Dezember) 
// Zweiter Weihnachtstag setzen (fester 'Feiertag' am 26. Dezember) 
// Sylvester setzen (fester 'Feiertag' am 31. Dezember)