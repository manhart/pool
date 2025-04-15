<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\utils;

use function mktime;
use function str_pad;

use const STR_PAD_LEFT;

final class Date
{
    public static function hasChanged(string|\DateTimeInterface|null $new, string|\DateTimeInterface|null $existing): bool
    {
        if (!$new || !$existing) {
            return true;
        }

        try {
            $newDateRaw = $new instanceof \DateTimeInterface ? $new->format(\DateTimeInterface::ATOM) : $new;
            $existingDateRaw = $existing instanceof \DateTimeInterface ? $existing->format(\DateTimeInterface::ATOM) : $existing;

            $newDate = new \DateTimeImmutable($newDateRaw);
            $existingDate = new \DateTimeImmutable($existingDateRaw);
        } catch (\Exception) {
            return true;
        }

        // Compare date
        if ($newDate->format('Y-m-d') !== $existingDate->format('Y-m-d')) {
            return true;
        }

        // Compare the time - but only if both input time contains time
        $newHasRealTime = self::hasSignificantTime($newDateRaw);
        $existingHasRealTime = self::hasSignificantTime($existingDateRaw);

        if ($newHasRealTime !== $existingHasRealTime) {
            return true; // One has time, the other not
        }

        if ($newHasRealTime && $newDate->format('H:i:s') !== $existingDate->format('H:i:s')) {
            return true; // Both have time, but different
        }

        return false;
    }

    /**
     * Check if the input string has a significant time component, i.e. not just "00:00:00"
     */
    public static function hasSignificantTime(string $input): bool
    {
        $dt = new \DateTimeImmutable($input);
        return $dt->format('H:i:s') !== '00:00:00' && (strlen($input) > 10 || str_contains($input, 'T'));
    }

    public static function stringHasTimeComponent(string $input): bool
    {
        return str_contains($input, ' ') || str_contains($input, 'T');
    }

    /**
     * Calculates a custom week number
     *
     * @param int $mon month
     * @param int $day day
     * @param int $year year
     * @param int $breakpoint calendar week based on this weekday
     * @return string
     */
    public static function getCustomWeekNumber(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $kw = \date('W', $date); // KW nach ISO 8601:1988
        $weekday = \date('N', $date);
        if ($weekday >= $breakpoint) {
            $kw = \date('W', mktime(0, 0, 0, $mon, $day + 7, $year));
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
    public static function getCustomMonth(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $monat = \date('m', $date);
        $weekday = \date('N', $date);
        if ($weekday >= $breakpoint) {
            $monat = \date('m', mktime(0, 0, 0, $mon, $day + 7, $year));
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
    public static function getCustomYear(int $mon, int $day, int $year, int $breakpoint = 4): string
    {
        $date = mktime(0, 0, 0, $mon, $day, $year);
        $jahr = \date('Y', $date);
        $weekday = \date('N', $date);
        if ($weekday >= $breakpoint) {
            $jahr = \date('Y', mktime(0, 0, 0, $mon, $day + 7, $year));
        }

        return $jahr;
    }
}