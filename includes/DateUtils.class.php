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
        $kw = strftime('%V', $date); // KW nach ISO 8601:1988
        $weekday = strftime('%u', $date);
        if ($weekday >= $breakpoint) {
            $kw = strftime('%V', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return sprintf('%02d', $kw);
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
        $monat = strftime('%m', $date); // KW nach ISO 8601:1988
        $weekday = strftime('%u', $date);
        if ($weekday >= $breakpoint) {
            $monat = strftime('%m', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return sprintf('%02d', $monat);
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
        $jahr = strftime('%G', $date);
        $weekday = strftime('%u', $date);
        if ($weekday >= $breakpoint) {
            $jahr = strftime('%G', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return sprintf('%04d', $jahr);
    }
}