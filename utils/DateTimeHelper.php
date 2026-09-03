<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\utils;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use pool\classes\Database\DataInterface;

final class DateTimeHelper
{
    /** convert to DateTime */
    public static function toDateTime(string|int|DateTimeInterface|null $date, ?DateTimeZone $timezone = null): ?DateTime
    {
        if ($date instanceof DateTime) return $date;
        if ($date instanceof DateTimeInterface) return DateTime::createFromInterface($date);
        if ($date === null || $date === '' || $date === DataInterface::ZERO_DATE || $date === DataInterface::ZERO_DATETIME) return null;
        try {
            if (is_numeric($date)) return new DateTime(timezone: $timezone)->setTimestamp((int)$date);
            return new DateTime($date, $timezone);
        } catch (Exception) {
            return null;
        }
    }

    public static function toDateTimeImmutable(string|int|DateTimeInterface|null $date, ?DateTimeZone $timezone = null): ?DateTimeImmutable
    {
        if ($date instanceof DateTimeImmutable) return $date;
        $dateTime = self::toDateTime($date, $timezone);
        return $dateTime ? DateTimeImmutable::createFromInterface($dateTime) : null;
    }
}
