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

use DateTime;
use DateTimeZone;

use function mktime;
use function str_pad;

use const STR_PAD_LEFT;

final class Date
{
    public static function hasChanged(string|\DateTimeInterface|null $new, string|\DateTimeInterface|null $existing): bool
    {
        if (!$new xor !$existing) {
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

    /**
     * Checks if the given string contains a time indicator, such as a space or the letter 'T'.
     */
    public static function hasTime(string $input): bool
    {
        return str_contains($input, ' ') || str_contains($input, 'T');
    }

    public static function isParseErrorFree(null|array|false $errors): bool
    {
        return $errors === false || (isset($errors['warning_count'], $errors['error_count']) && $errors['warning_count'] === 0 && $errors['error_count'] === 0);
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
     * @param int $breakpoint calendar week based on this weekday
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

   /** @noinspection PhpUnhandledExceptionInspection */
    public static function validateAndParseDate(string|null $date, string $format = 'Y-m-d H:i:s', ?DateTimeZone $timeZone = null, bool $strict = true): DateTime|null {
        if (!$date) return null;
        $dateTime = DateTime::createFromFormat($format, $date, $timeZone ?? new DateTimeZone(date_default_timezone_get()));
        if ($dateTime === false) return null;
        if (!Date::isParseErrorFree(DateTime::getLastErrors())) return null;
        if ($strict && $dateTime->format($format) !== $date) return null;
        return $dateTime;
    }

    /** Validates a given date string against a specified format. *
     */
    public static function isValidDateString(string|null $date, string $format = 'Y-m-d H:i:s', ?DateTimeZone $timeZone = null, bool $strict = true): bool
    {
        return (bool)self::validateAndParseDate($date, $format, $timeZone, $strict);
    }

    public static function validateAndParseTimeString(string|null $time, bool $strict = true):DateTime|null
    {
        if (!$time) return null;
        $timeParts = explode(':', $time, 3);
        $countTimeParts = count($timeParts);

        $formatParts = ['H', 'i', 's'];
        $format = implode(':', array_slice($formatParts, 0, $countTimeParts));

        $dateTime = DateTime::createFromFormat($format, $time);
        if ($dateTime === false) return null;
        if (!Date::isParseErrorFree(DateTime::getLastErrors())) return null;
        if ($strict && $dateTime->format($format) !== $time) return null;
        return $dateTime;
    }

    /** Validates a given time string */
    public static function isValidTimeString(string|null $time, bool $strict = true): bool
    {
        return (bool)self::validateAndParseTimeString($time, $strict);
    }

    public static function getDayOfWeek(\DateTimeInterface $date, bool $short = false, ?string $locale = null): string
    {
        if (\extension_loaded('intl')) {
            $pattern = $short ? 'EEE' : 'EEEE';
            $fmt = new \IntlDateFormatter(
                $locale ?? \Locale::getDefault(), \IntlDateFormatter::FULL, \IntlDateFormatter::NONE,
                $date->getTimezone(), null, $pattern,
            );
            
            $w = $fmt->format($date);
            if ($w !== false) {
                return rtrim($w, '.'); // e.g. "Mo." -> "Mo"
            }
        }
        return $short ? date('D', $date->getTimestamp()) : date('l', $date->getTimestamp());
    }
}
