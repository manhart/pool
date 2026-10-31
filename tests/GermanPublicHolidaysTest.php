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
        self::assertCount(20, $allHolidays);
        self::assertSame('2026-08-08', $allHolidays[GermanPublicHolidays::AUGSBURGERFRIEDENSFEST]->format('Y-m-d'));
        self::assertSame('2026-09-20', $allHolidays[GermanPublicHolidays::WELTKINDERTAG]->format('Y-m-d'));
    }

    #[DataProvider('stateHolidays2026')]
    public function testCompleteStateHolidayDates(string $state, array $regionalDates): void
    {
        $holidays = new GermanPublicHolidays();
        $expected = array_merge([
            '01-01', '04-03', '04-06', '05-01', '05-14', '05-25', '10-03', '12-25', '12-26',
        ], $regionalDates);
        sort($expected);
        $dates = $holidays->getLegalHolidays(2026, $state);
        $actual = array_values(array_map(fn($date) => $date->format('m-d'), $dates));
        sort($actual);
        self::assertSame($expected, $actual);
        self::assertSame($dates, $holidays->getLegalHolidays(2026, "DE-$state"));

        foreach ($holidays->getLegalHolidays(2026) as $date) {
            $isHoliday = in_array($date->format('m-d'), $expected, true);
            self::assertSame($isHoliday, GermanPublicHolidays::check($date, $state), $date->format('Y-m-d'));
            $holiday = GermanPublicHolidays::which(DateTime::createFromImmutable($date), "DE-$state");
            self::assertSame($isHoliday, $holiday !== null, $date->format('Y-m-d'));
            if ($holiday) {
                self::assertSame($date->format('Y-m-d'), $holiday->getDate()->format('Y-m-d'));
                self::assertNotSame('', $holiday->getName());
            }
        }
    }

    public static function stateHolidays2026(): array
    {
        return [
            'BW' => ['BW', ['01-06', '06-04', '11-01']],
            // Preserve the statewide approximation of Assumption, but exclude Augsburg's Friedensfest.
            'BY' => ['BY', ['01-06', '06-04', '08-15', '11-01']],
            'BE' => ['BE', ['03-08']],
            'BB' => ['BB', ['04-05', '05-24', '10-31']],
            'HB' => ['HB', ['10-31']],
            'HH' => ['HH', ['10-31']],
            'HE' => ['HE', ['06-04']],
            'MV' => ['MV', ['03-08', '10-31']],
            'NI' => ['NI', ['10-31']],
            'NW' => ['NW', ['06-04', '11-01']],
            'RP' => ['RP', ['06-04', '11-01']],
            'SL' => ['SL', ['06-04', '08-15', '11-01']],
            'SN' => ['SN', ['10-31', '11-18']],
            'ST' => ['ST', ['01-06', '10-31']],
            'SH' => ['SH', ['10-31']],
            'TH' => ['TH', ['09-20', '10-31']],
        ];
    }

    #[DataProvider('holidayIntroductionYears')]
    public function testStateHolidaysRespectTheirIntroductionYear(string $state, string $date, int $key, bool $expected): void
    {
        $day = new DateTimeImmutable($date);
        $holidays = new GermanPublicHolidays();
        $dates = $holidays->getLegalHolidays((int)$day->format('Y'), $state);
        self::assertSame($expected, isset($dates[$key]));
        self::assertSame($expected, GermanPublicHolidays::check($day, "DE-$state"));
        $holiday = GermanPublicHolidays::which(new DateTime($date), $state);
        self::assertSame($expected, $holiday !== null);
        if ($holiday) self::assertSame($date, $holiday->getDate()->format('Y-m-d'));
    }

    public static function holidayIntroductionYears(): iterable
    {
        foreach ([
            ['BE', '03-08', GermanPublicHolidays::FRAUENTAG, 2019],
            ['MV', '03-08', GermanPublicHolidays::FRAUENTAG, 2023],
            ['TH', '09-20', GermanPublicHolidays::WELTKINDERTAG, 2019],
        ] as [$state, $date, $key, $firstYear]) {
            foreach ([$firstYear - 1, $firstYear, $firstYear + 1] as $year) {
                yield "$state-$year" => [$state, "$year-$date", $key, $year >= $firstYear];
            }
        }
        foreach (['HB', 'HH', 'NI', 'SH'] as $state) {
            foreach ([2016 => false, 2017 => true, 2018 => true, 2019 => true] as $year => $expected) {
                yield "$state-$year" => [$state, "$year-10-31", GermanPublicHolidays::REFORMATIONSTAG, $expected];
            }
        }
        foreach ([2016 => false, 2017 => true, 2018 => false] as $year => $expected) {
            yield "BE-reformation-$year" => ['BE', "$year-10-31", GermanPublicHolidays::REFORMATIONSTAG, $expected];
        }
    }

    #[DataProvider('berlinOneOffHolidays')]
    public function testBerlinOneOffHolidays(string $date, int $key, ?string $name): void
    {
        $day = new DateTimeImmutable($date);
        $year = (int)$day->format('Y');
        $holidays = new GermanPublicHolidays();
        self::assertSame($name !== null, isset($holidays->getLegalHolidays($year)[$key]));
        self::assertSame($name !== null, isset($holidays->getLegalHolidays($year, 'BE')[$key]));
        self::assertSame($name !== null, GermanPublicHolidays::check($day, 'DE-BE'));
        self::assertSame($name, GermanPublicHolidays::which(new DateTime($date), 'BE')?->getName());
        foreach (['', 'BY', 'BB', 'HE'] as $state) {
            self::assertFalse(GermanPublicHolidays::check($day, $state));
            self::assertNull(GermanPublicHolidays::which(new DateTime($date), $state));
        }
    }

    public static function berlinOneOffHolidays(): iterable
    {
        foreach ([2019 => false, 2020 => true, 2021 => false, 2024 => false, 2025 => true, 2026 => false] as $year => $expected) {
            yield "liberation-$year" => ["$year-05-08", GermanPublicHolidays::TAGDERBEFREIUNG, $expected ? 'Tag der Befreiung' : null];
        }
        foreach ([2027 => false, 2028 => true, 2029 => false] as $year => $expected) {
            yield "uprising-$year" => ["$year-06-17", GermanPublicHolidays::TAGDESVOLKSAUFSTANDS, $expected ? 'Tag des Volksaufstandes' : null];
        }
    }

    public function testWorldChildrensDayNameAndTheUnfilteredDateCatalogue(): void
    {
        $holidays = new GermanPublicHolidays();
        self::assertSame('Weltkindertag', GermanPublicHolidays::which(new DateTime('2026-09-20'), 'TH')?->getName());
        self::assertSame('2018-09-20', $holidays->getLegalHolidays(2018)[GermanPublicHolidays::WELTKINDERTAG]->format('Y-m-d'));
        self::assertSame('2018-03-08', $holidays->getLegalHolidays(2018)[GermanPublicHolidays::FRAUENTAG]->format('Y-m-d'));
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
