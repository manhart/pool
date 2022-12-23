<?php
/*
 * g7system.local
 *
 * Date.class.php created at 13.05.22, 14:55
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */


final class DateUtils
{
    /**
     * Calculates a custom week number
     *
     * @param int $mon month
     * @param int $day day
     * @param int $year year
     * @param int $breakpoint calendar week based on this weekday
     * @return string
     */
    static function getCustomWeekNumber(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $kw = date('W', $date); // KW nach ISO 8601:1988
        $weekday = date('N', $date);
        if ($weekday >= $breakpoint) {
            $kw = date('W', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return str_pad($kw, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculates a custom month
     *
     * @param int $mon month
     * @param int $day day
     * @param int $year year
     * @param int $breakpoint calendar week based on this weekday
     * @return string
     */
    static function getCustomMonth(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $monat = date('m', $date);
        $weekday = date('N', $date);
        if ($weekday >= $breakpoint) {
            $monat = date('m', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return str_pad($monat, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Calculates a custom year
     *
     * @param int $mon
     * @param int $day
     * @param int $year
     * @param int $breakpoint calendar week based on this weekday
     * @return string
     */
    static function getCustomYear(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $jahr = date('Y', $date);
        $weekday = date('N', $date);
        if ($weekday >= $breakpoint) {
            $jahr = date('Y', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return $jahr;
    }
}