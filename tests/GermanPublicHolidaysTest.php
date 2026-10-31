<?php
declare(strict_types=1);

namespace pool\tests;

use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pool\classes\Exception\InvalidArgumentException;
use pool\utils\GermanPublicHolidays;

final class GermanPublicHolidaysTest extends TestCase
{
    public function testGermanCalculationAndHolidayObjectAfterRename(): void
    {
        $holidays = new GermanPublicHolidays();
        self::assertSame('2024-03-31', $holidays->calculateEasterSunday(2024)->format('Y-m-d'));
        self::assertSame('2024-04-01', $holidays->getLegalHolidays(2024, 'BY')[GermanPublicHolidays::OSTERMONTAG]->format('Y-m-d'));
        self::assertTrue(GermanPublicHolidays::check(new DateTimeImmutable('2024-01-06'), 'BY'));
        self::assertFalse(GermanPublicHolidays::check(new DateTimeImmutable('2024-01-06'), 'HH'));
        // Omitting the state checks nationwide holidays only.
        self::assertFalse(GermanPublicHolidays::check(new DateTimeImmutable('2024-01-06')));

        $holiday = GermanPublicHolidays::which(new DateTime('2024-04-01'), 'BY');
        self::assertNotNull($holiday);
        self::assertSame('Ostermontag', $holiday->getName());
        self::assertSame('2024-04-01', $holiday->getDate()->format('Y-m-d'));
        self::assertNull(GermanPublicHolidays::which(new DateTime('2024-04-02'), 'BY'));

        $range = $holidays->getLegalHolidaysByRange(new DateTimeImmutable('2024-12-24'), new DateTimeImmutable('2024-12-26'));
        self::assertSame(['2024-12-25', '2024-12-26'], array_values(array_map(fn($date) => $date->format('Y-m-d'), $range)));
    }

    public function testIsoSubdivisionCodeMatchesStateCode(): void
    {
        $holidays = new GermanPublicHolidays();
        self::assertSame($holidays->getLegalHolidays(2024, 'BY'), $holidays->getLegalHolidays(2024, 'DE-BY'));
        self::assertTrue(GermanPublicHolidays::check(new DateTimeImmutable('2024-01-06'), 'DE-BY'));
        self::assertFalse(GermanPublicHolidays::check(new DateTimeImmutable('2024-01-06'), 'DE-HH'));
    }

    public function testNationwideDatesPreserveKeysAndLeaveTheFullListAvailable(): void
    {
        $holidays = new GermanPublicHolidays();
        $allHolidays = $holidays->getLegalHolidays(2026);
        self::assertSame([
            GermanPublicHolidays::NEUJAHRSTAG => '2026-01-01',
            GermanPublicHolidays::KARFREITAG => '2026-04-03',
            GermanPublicHolidays::OSTERMONTAG => '2026-04-06',
            GermanPublicHolidays::TAGDERARBEIT => '2026-05-01',
            GermanPublicHolidays::CHRISTIHIMMELFAHRT => '2026-05-14',
            GermanPublicHolidays::PFINGSTMONTAG => '2026-05-25',
            GermanPublicHolidays::TAGDERDEUTSCHENEINHEIT => '2026-10-03',
            GermanPublicHolidays::ERSTERWEIHNACHTSTAG => '2026-12-25',
            GermanPublicHolidays::ZWEITERWEIHNACHTSTAG => '2026-12-26',
        ], array_map(fn($date) => $date->format('Y-m-d'), $holidays->getNationwideHolidays(2026)));
        self::assertSame($allHolidays, $holidays->getLegalHolidays(2026, ''));
        self::assertCount(19, $allHolidays);
        self::assertSame('2026-08-08', $allHolidays[GermanPublicHolidays::AUGSBURGERFRIEDENSFEST]->format('Y-m-d'));
    }

    public function testReformationAnniversaryAppliesNationwideAndInEveryState(): void
    {
        $holidays = new GermanPublicHolidays();
        self::assertCount(10, $holidays->getNationwideHolidays(2017));
        self::assertSame('2017-10-31', $holidays->getNationwideHolidays(2017)[GermanPublicHolidays::REFORMATIONSTAG]->format('Y-m-d'));
        foreach (['', 'BW', 'BY', 'BE', 'BB', 'HB', 'HH', 'HE', 'MV', 'NI', 'NW', 'RP', 'SL', 'SN', 'ST', 'SH', 'TH', 'DE-BY'] as $state) {
            self::assertTrue(GermanPublicHolidays::check(new DateTimeImmutable('2017-10-31'), $state), $state);
            self::assertSame('Reformationstag', GermanPublicHolidays::which(new DateTime('2017-10-31'), $state)?->getName(), $state);
        }
        foreach ([2016, 2018] as $year) {
            self::assertCount(9, $holidays->getNationwideHolidays($year));
            foreach (['', 'BY', 'HE'] as $state) {
                self::assertFalse(GermanPublicHolidays::check(new DateTimeImmutable("$year-10-31"), $state));
                self::assertNull(GermanPublicHolidays::which(new DateTime("$year-10-31"), $state));
            }
        }
    }

    #[DataProvider('holidayChecks')]
    public function testCheckAndWhichUseTheSameHolidaySelection(string $date, string $state, bool $expected): void
    {
        self::assertSame($expected, GermanPublicHolidays::check(new DateTimeImmutable($date), $state));
        $holiday = GermanPublicHolidays::which(new DateTime($date), $state);
        self::assertSame($expected, $holiday !== null);
        if ($holiday) self::assertSame($date, $holiday->getDate()->format('Y-m-d'));
    }

    public static function holidayChecks(): array
    {
        return [
            ['2026-01-01', '', true],
            ['2026-01-06', '', false],
            ['2026-01-06', 'BY', true],
            ['2026-01-06', 'DE-BY', true],
            ['2026-01-06', 'HE', false],
            ['2026-06-04', '', false],
            ['2026-06-04', 'BY', true],
            ['2026-06-04', 'HE', true],
            ['2026-08-08', '', false],
            ['2026-03-08', '', false],
            ['2026-11-18', '', false],
            ['2026-12-24', '', false],
        ];
    }

    #[DataProvider('unknownStates')]
    public function testUnknownStateStillThrows(string $state): void
    {
        $this->expectException(InvalidArgumentException::class);
        new GermanPublicHolidays()->getLegalHolidays(2026, $state);
    }

    public static function unknownStates(): array
    {
        // A bare prefix must not fall back to all states; only the exact DE- prefix is stripped.
        return [['XX'], ['DE-'], ['XX-BY'], ['DE-XX']];
    }
}
