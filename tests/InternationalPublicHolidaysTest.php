<?php
declare(strict_types=1);

namespace pool\tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use pool\utils\InternationalPublicHolidays;
use ReflectionProperty;

final class InternationalPublicHolidaysTest extends TestCase
{
    protected function tearDown(): void
    {
        InternationalPublicHolidays::resetInstance();
    }

    public function testGermanDatesMatchIndividualChecksAcrossStatesAndLeapYear(): void
    {
        new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl')->setValue(InternationalPublicHolidays::getInstance(), 'invalid-url');
        foreach (['', 'BY', 'HE', 'HH'] as $state) {
            $dates = InternationalPublicHolidays::getHolidayDates(2024, 'DE', $state === '' ? null : "DE-$state");
            for ($day = new DateTimeImmutable('2024-01-01'); $day->format('Y') === '2024'; $day = $day->modify('+1 day')) {
                self::assertSame(InternationalPublicHolidays::check($day, $state), isset($dates[$day->format('Y-m-d')]));
            }
        }
        self::assertArrayHasKey('2024-01-06', InternationalPublicHolidays::getHolidayDates(2024, 'DE', 'DE-BY'));
        self::assertArrayNotHasKey('2024-01-06', InternationalPublicHolidays::getHolidayDates(2024, 'DE', 'DE-HH'));
        // A bare state code previously fell back silently to all German states.
        self::assertSame(InternationalPublicHolidays::getHolidayDates(2024, 'DE', 'DE-BY'), InternationalPublicHolidays::getHolidayDates(2024, 'DE', 'BY'));
    }

    public function testDefaultCheckUsesBavariaAndGermanyWithoutAnApiRequest(): void
    {
        new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl')->setValue(InternationalPublicHolidays::getInstance(), 'invalid-url');
        self::assertTrue(InternationalPublicHolidays::check(new DateTimeImmutable('2026-01-06')));
        self::assertFalse(InternationalPublicHolidays::check(new DateTimeImmutable('2026-01-06'), 'HH'));
        self::assertArrayHasKey('2026-01-06', InternationalPublicHolidays::getHolidayDates(2026, 'DE', 'DE-BY'));
    }

    public function testGermanNationwideDatesAreLocalAndKeepRawApiResultsSeparate(): void
    {
        $client = InternationalPublicHolidays::getInstance();
        new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl')->setValue($client, 'invalid-url');
        $rawHolidays = [
            ['date' => '2026-05-05', 'types' => ['Public'], 'global' => true],
            ['date' => '2026-01-06', 'types' => ['Public'], 'global' => false],
        ];
        new ReflectionProperty(InternationalPublicHolidays::class, 'cache')->setValue($client, [2026 => ['DE' => $rawHolidays]]);

        $dates = InternationalPublicHolidays::getHolidayDates(2026, 'DE');
        self::assertCount(9, $dates);
        self::assertSame($dates, InternationalPublicHolidays::getHolidayDates(2026, 'DE', ''));
        self::assertArrayHasKey('2026-05-14', $dates);
        self::assertArrayNotHasKey('2026-05-05', $dates);
        self::assertArrayNotHasKey('2026-01-06', $dates);
        self::assertTrue(InternationalPublicHolidays::check(new DateTimeImmutable('2026-05-14'), '', 'DE'));
        self::assertFalse(InternationalPublicHolidays::check(new DateTimeImmutable('2026-01-06'), '', 'DE'));
        self::assertFalse(InternationalPublicHolidays::check(new DateTimeImmutable('2026-05-05'), '', 'DE'));

        foreach ([null, 'BY', 'DE-HE'] as $subdivision) {
            self::assertArrayHasKey('2017-10-31', InternationalPublicHolidays::getHolidayDates(2017, 'DE', $subdivision));
        }
        self::assertTrue(InternationalPublicHolidays::check(new DateTimeImmutable('2017-10-31'), '', 'DE'));
        self::assertTrue($client->isPublicHolidayInAnyRegion(new DateTimeImmutable('2026-01-06'), 'DE'));
        self::assertSame($rawHolidays, $client->getPublicHolidays(2026, 'DE'));
    }

    public function testApiDatesRespectCountryYearAndSubdivisionWithoutChangingCachedData(): void
    {
        new ReflectionProperty(InternationalPublicHolidays::class, 'cache')->setValue(InternationalPublicHolidays::getInstance(), [
            2026 => [
                'AT' => [
                    ['date' => '2026-01-01', 'types' => ['Public'], 'global' => true, 'counties' => null],
                    ['date' => '2026-03-19', 'types' => ['Public'], 'global' => false, 'counties' => ['AT-7', 'AT-8']],
                    ['date' => '2026-02-14', 'types' => ['Observance'], 'global' => false, 'counties' => ['AT-7']],
                    ['date' => '2026-02-15', 'types' => ['Observance'], 'global' => true, 'counties' => null],
                ],
            ],
            2027 => ['AT' => [['date' => '2027-01-01', 'types' => ['Public'], 'global' => true]]],
        ]);
        $rawHolidays = InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT');
        self::assertSame(['2026-01-01' => true, '2026-03-19' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT', 'AT-7'));
        self::assertSame(['2026-01-01' => true, '2026-03-19' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT', 'AT-8'));
        self::assertSame(['2026-01-01' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT', 'AT-9'));
        self::assertSame(['2026-01-01' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT'));
        self::assertSame(['2027-01-01' => true], InternationalPublicHolidays::getHolidayDates(2027, 'AT'));
        self::assertSame($rawHolidays, InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));

        // Single-date checks match getHolidayDates(); the default BY matches no AT subdivision.
        foreach (['', 'BY', 'AT-7', 'AT-9'] as $subdivision) {
            $dates = InternationalPublicHolidays::getHolidayDates(2026, 'AT', $subdivision ?: null);
            for ($day = new DateTimeImmutable('2026-01-01'); $day->format('Y') === '2026'; $day = $day->modify('+1 day')) {
                self::assertSame(isset($dates[$day->format('Y-m-d')]), InternationalPublicHolidays::check($day, $subdivision, 'AT'), "$subdivision {$day->format('Y-m-d')}");
            }
        }
        self::assertFalse(InternationalPublicHolidays::check(new DateTimeImmutable('2026-03-19'), countryCode: 'AT'));
        self::assertTrue(InternationalPublicHolidays::check(new DateTimeImmutable('2026-03-19'), 'AT-7', 'AT'));
        // isPublicHolidayInAnyRegion() stays the explicit opt-in for any regional holiday.
        self::assertTrue(InternationalPublicHolidays::getInstance()->isPublicHolidayInAnyRegion(new DateTimeImmutable('2026-03-19'), 'AT'));
        self::assertFalse(InternationalPublicHolidays::getInstance()->isPublicHolidayInAnyRegion(new DateTimeImmutable('2026-02-14'), 'AT'));
    }
}
