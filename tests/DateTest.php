<?php
declare(strict_types=1);

namespace pool\tests;

use DateTimeImmutable;
use IntlDateFormatter;
use IntlDatePatternGenerator;
use PHPUnit\Framework\TestCase;
use pool\utils\Date;

final class DateTest extends TestCase
{
    public function testNumericFormatsMatchIntlForDifferentLocales(): void
    {
        $date = new DateTimeImmutable('2026-10-08');
        foreach (['de_DE' => 'd.m.Y', 'en_GB' => 'd/m/Y', 'en_US' => 'm/d/Y'] as $locale => $expected) {
            $pattern = new IntlDatePatternGenerator($locale)->getBestPattern('yyyyMMdd');
            $format = Date::icuToPhpDateFormat($pattern);
            self::assertSame($expected, $format, $locale);
            self::assertSame(IntlDateFormatter::formatObject($date, $pattern, $locale), $date->format($format));
        }
    }

    public function testSingleDigitFieldsAndShortYearsKeepTheirMeaning(): void
    {
        $date = new DateTimeImmutable('2006-03-09');
        foreach (['d.M.yy' => '9.3.06', 'dd.MM.yyyy' => '09.03.2006', 'yyyy-MM-dd' => '2006-03-09', 'M/d/y' => '3/9/2006'] as $pattern => $expected) {
            self::assertSame($expected, $date->format(Date::icuToPhpDateFormat($pattern)));
        }
    }
}
