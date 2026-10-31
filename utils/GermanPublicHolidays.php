<?php
declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

namespace pool\utils;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use pool\classes\Core\PoolObject;

final class GermanPublicHolidays extends PoolObject
{
    /**
     * Feiertage (Public Holidays)
     *
     * @see https://www.welt-der-zahlen.info/berechnung.html
     */

    const int NEUJAHRSTAG = 0;
    const int HEILIGEDREIKOENIGE = 1;
    const int ROSENMONTAG = 2;
    const int ASCHERMITTWOCH = 3;
    const int FRAUENTAG = 4;
    const int GRUENDONNERSTAG = 5;
    const int KARFREITAG = 6;
    const int OSTERSONNTAG = 7;
    const int OSTERMONTAG = 8;
    const int TAGDERARBEIT = 9;
    const int CHRISTIHIMMELFAHRT = 10;
    const int PFINGSTSONNTAG = 11;
    const int PFINGSTMONTAG = 12;
    const int FRONLEICHNAM = 13;
    const int AUGSBURGERFRIEDENSFEST = 14;
    const int MARIAEHIMMELFAHRT = 15;
    const int TAGDERDEUTSCHENEINHEIT = 16;
    const int REFORMATIONSTAG = 17;
    const int ALLERHEILIGEN = 18;
    const int BUSSUNDBETTAG = 19;
    const int ERSTERWEIHNACHTSTAG = 20;
    const int ZWEITERWEIHNACHTSTAG = 21;
    const int WELTKINDERTAG = 22;
    const int TAGDERBEFREIUNG = 23;
    const int TAGDESVOLKSAUFSTANDS = 24;
    /**
     * Bundesländer
     *
     * @see https://www.datenportal.bmbf.de/portal/de/G122.html
     */

    const string STATE_BADENWUERTTEMBERG = 'BW';
    const string STATE_BAYERN = 'BY';
    const string STATE_BERLIN = 'BE';
    const string STATE_BRANDENBURG = 'BB';
    const string STATE_BREMEN = 'HB';
    const string STATE_HAMBURG = 'HH';
    const string STATE_HESSEN = 'HE';
    const string STATE_MECKLENBURGVORPOMMERN = 'MV';
    const string STATE_NIEDERSACHSEN = 'NI';
    const string STATE_NORDRHEINWESTFALEN = 'NW';
    const string STATE_RHEINLANDPFALZ = 'RP';
    const string STATE_SAARLAND = 'SL';
    const string STATE_SACHSEN = 'SN';
    const string STATE_SACHSENANHALT = 'ST';
    const string STATE_SCHLESWIGHOLSTEIN = 'SH';
    const string STATE_THUERINGEN = 'TH';

    private const array NATIONWIDE_HOLIDAYS = [
        self::NEUJAHRSTAG => true,
        self::KARFREITAG => true,
        self::OSTERMONTAG => true,
        self::TAGDERARBEIT => true,
        self::CHRISTIHIMMELFAHRT => true,
        self::PFINGSTMONTAG => true,
        self::TAGDERDEUTSCHENEINHEIT => true,
        self::ERSTERWEIHNACHTSTAG => true,
        self::ZWEITERWEIHNACHTSTAG => true,
    ];

    private const array HOLIDAY_START_YEARS = [
        self::STATE_BERLIN => [self::FRAUENTAG => 2019],
        self::STATE_BREMEN => [self::REFORMATIONSTAG => 2018],
        self::STATE_HAMBURG => [self::REFORMATIONSTAG => 2018],
        self::STATE_MECKLENBURGVORPOMMERN => [self::FRAUENTAG => 2023],
        self::STATE_NIEDERSACHSEN => [self::REFORMATIONSTAG => 2018],
        self::STATE_SCHLESWIGHOLSTEIN => [self::REFORMATIONSTAG => 2018],
        self::STATE_THUERINGEN => [self::WELTKINDERTAG => 2019],
    ];

    /**
     * Welche Feiertage in welchem Bundesland
     *
     * Municipal holidays are included only if they apply in more than half
     * of the state's municipalities. This includes Assumption in Bavaria,
     * but excludes Augsburg's Friedensfest and Corpus Christi in SN and TH.
     *
     * @see https://www.feiertage.net/bundeslaender.php
     */

    private array $publicHolidaysByState = [
        self::STATE_BADENWUERTTEMBERG => [
            self::NEUJAHRSTAG,
            self::HEILIGEDREIKOENIGE,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_BAYERN => [
            self::NEUJAHRSTAG,
            self::HEILIGEDREIKOENIGE,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            // Nager.Date omits Assumption Day in BY because it is not a statewide holiday.
            // POOL includes it because it applies in a majority of Bavaria's municipalities.
            // Discussion: https://github.com/nager/Nager.Date/issues/1185
            self::MARIAEHIMMELFAHRT,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_BERLIN => [
            self::NEUJAHRSTAG,
            self::FRAUENTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::TAGDERBEFREIUNG,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDESVOLKSAUFSTANDS,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_BRANDENBURG => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERSONNTAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::PFINGSTSONNTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_BREMEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_HAMBURG => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_HESSEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_MECKLENBURGVORPOMMERN => [
            self::NEUJAHRSTAG,
            self::FRAUENTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_NIEDERSACHSEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_NORDRHEINWESTFALEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_RHEINLANDPFALZ => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_SAARLAND => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::FRONLEICHNAM,
            self::MARIAEHIMMELFAHRT,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_SACHSEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::BUSSUNDBETTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_SACHSENANHALT => [
            self::NEUJAHRSTAG,
            self::HEILIGEDREIKOENIGE,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_SCHLESWIGHOLSTEIN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_THUERINGEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::WELTKINDERTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
    ];

    /**
     * holds the public holidays
     *
     * @var array
     */
    private array $publicHolidays = [];

    /** @var GermanPublicHolidays|null Request-Cache */
    private static ?GermanPublicHolidays $instance = null;

    /**
     * Checks nationwide holidays, or the holidays of the given state.
     */
    static function check(DateTimeInterface $Date, string $state = ''): bool
    {
        $self = self::$instance ??= new GermanPublicHolidays();

        $year = (int)$Date->format('Y');
        $date = $Date->format('Y-m-d');

        $holidays = $state === '' ? $self->getNationwideHolidays($year) : $self->getLegalHolidays($year, $state);
        foreach ($holidays as $holiday) {
            if ($holiday->format('Y-m-d') === $date) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns German holidays that apply nationwide in the given year.
     *
     * @return array<int, DateTimeImmutable> Dates keyed by holiday constants.
     */
    public function getNationwideHolidays(int $year): array
    {
        $this->factory($year);

        $keys = self::NATIONWIDE_HOLIDAYS;
        // The Reformation anniversary was a nationwide holiday only in 2017.
        if ($year === 2017) $keys[self::REFORMATIONSTAG] = true;

        return array_intersect_key($this->publicHolidays[$year]['legal'], $keys);
    }

    /**
     * Returns legal German holidays
     *
     * @param string $state State code (BY) or ISO 3166-2 code (DE-BY); empty returns all German holidays.
     * @throws \pool\classes\Exception\InvalidArgumentException
     */
    public function getLegalHolidays(int $year, string $state = ''): array
    {
        $this->factory($year);

        if ($state == '') {
            return $this->publicHolidays[$year]['legal'];
        }

        // Strip after the empty check so a bare "DE-" fails instead of returning all states.
        $state = str_starts_with($state, 'DE-') ? substr($state, 3) : $state;
        if (!isset($this->publicHolidaysByState[$state])) {
            throw new \pool\classes\Exception\InvalidArgumentException("State $state unknown");
        }

        $out = [];
        foreach ($this->publicHolidaysByState[$state] as $key) {
            // One-off holidays have no date outside their designated years.
            $holiday = $this->publicHolidays[$year]['legal'][$key] ?? null;
            if ($holiday && $year >= (self::HOLIDAY_START_YEARS[$state][$key] ?? 0)) {
                $out[$key] = $holiday;
            }
        }
        // Include nationwide one-off holidays in every state.
        return $out + $this->getNationwideHolidays($year);
    }

    /**
     * Returns a nationwide holiday, or a holiday of the given state, as a PublicHoliday object.
     */
    static function which(DateTime $Date, string $state = ''): ?PublicHoliday
    {
        $self = self::$instance ??= new GermanPublicHolidays();

        $year = (int)$Date->format('Y');
        $date = $Date->format('Y-m-d');

        $holidays = $state === '' ? $self->getNationwideHolidays($year) : $self->getLegalHolidays($year, $state);
        foreach ($holidays as $key => $holiday) {
            if ($holiday->format('Y-m-d') === $date) {
                return new PublicHoliday($key, $holiday);
            }
        }
        return null;
    }

    /**
     * Internally creates all holidays of a year.
     */
    private function factory(int $year): void
    {
        if (isset($this->publicHolidays[$year])) {
            return;// already created
        }
        $easterSunday = $this->calculateEasterSunday($year);

        $phLegal = [];
        $phOther = [];

        /* legal */
        $phLegal[self::NEUJAHRSTAG] = new DateTimeImmutable("$year-01-01");
        $phLegal[self::HEILIGEDREIKOENIGE] = new DateTimeImmutable("$year-01-06");
        $phLegal[self::FRAUENTAG] = new DateTimeImmutable("$year-03-08");
        $phLegal[self::KARFREITAG] = $easterSunday->modify('-2 days');
        $phLegal[self::OSTERSONNTAG] = $easterSunday;
        $phLegal[self::OSTERMONTAG] = $easterSunday->modify('+1 days');
        $phLegal[self::TAGDERARBEIT] = new DateTimeImmutable("$year-05-01");
        // Berlin's one-off holidays apply only in these years.
        if ($year === 2020 || $year === 2025) $phLegal[self::TAGDERBEFREIUNG] = new DateTimeImmutable("$year-05-08");
        $phLegal[self::CHRISTIHIMMELFAHRT] = $easterSunday->modify('+39 days');
        $phLegal[self::PFINGSTSONNTAG] = $easterSunday->modify('+49 days');
        $phLegal[self::PFINGSTMONTAG] = $easterSunday->modify('+50 days');
        $phLegal[self::FRONLEICHNAM] = $easterSunday->modify('+60 days');
        if ($year === 2028) $phLegal[self::TAGDESVOLKSAUFSTANDS] = new DateTimeImmutable("$year-06-17");
        $phLegal[self::AUGSBURGERFRIEDENSFEST] = new DateTimeImmutable("$year-08-08");
        $phLegal[self::MARIAEHIMMELFAHRT] = new DateTimeImmutable("$year-08-15");
        $phLegal[self::WELTKINDERTAG] = new DateTimeImmutable("$year-09-20");
        $phLegal[self::TAGDERDEUTSCHENEINHEIT] = new DateTimeImmutable("$year-10-03");
        $phLegal[self::REFORMATIONSTAG] = new DateTimeImmutable("$year-10-31");
        $phLegal[self::ALLERHEILIGEN] = new DateTimeImmutable("$year-11-01");
        $phLegal[self::ERSTERWEIHNACHTSTAG] = new DateTimeImmutable("$year-12-25");
        $phLegal[self::ZWEITERWEIHNACHTSTAG] = new DateTimeImmutable("$year-12-26");

        /* other */
        $phOther[self::ASCHERMITTWOCH] = $easterSunday->modify('-46 days');
        $phOther[self::ROSENMONTAG] = $easterSunday->modify('-48 days');
        $phOther[self::GRUENDONNERSTAG] = $easterSunday->modify('-3 days');

        $bb = new DateTimeImmutable("$year-11-22");
        while ($bb->format('N') != 3) {
            $bb = $bb->modify('-1 days');
        }
        $phLegal[self::BUSSUNDBETTAG] = $bb;
        $this->publicHolidays[$year] = [
            'legal' => $phLegal,
            'other' => $phOther,
        ];
    }

    /**
     * Calculate easter sunday
     */
    public function calculateEasterSunday(int $year): DateTimeImmutable
    {
        $day = self::gauss($year);

        $month = 3;
        if ($day > 31) {
            $month = 4;
            $day -= 31;
        }

        return new DateTimeImmutable("$year-$month-$day");
    }

    /**
     * @see https://de.wikipedia.org/wiki/Gau%C3%9Fsche_Osterformel
     */
    private static function gauss(int $year): int
    {
        $a = $year % 19;
        $b = $year % 4;
        $c = $year % 7;

        $H1 = intdiv($year, 100);
        $H2 = intdiv($year, 400);

        $N = 4 + $H1 - $H2;
        $M = 15 + $H1 - $H2 - intdiv(8 * $H1 + 13, 25);

        $d = (19 * $a + $M) % 30;
        $e = (2 * $b + 4 * $c + 6 * $d + $N) % 7;

        $o = 22 + $d + $e;

        if ($o == 57) {
            return 50;
        }

        if ($d == 28 && $e == 6 && $a > 10) {
            return 49;
        }

        return $o;
    }

    /**
     * Returns the German name of the holiday.
     */
    static function getHolidayName(int $key): string
    {
        static $MAP = [
            self::NEUJAHRSTAG => 'Neujahrstag',
            self::HEILIGEDREIKOENIGE => 'Heilige Drei Könige',
            self::FRAUENTAG => 'Frauentag',
            self::KARFREITAG => 'Karfreitag',
            self::OSTERSONNTAG => 'Ostersonntag',
            self::OSTERMONTAG => 'Ostermontag',
            self::TAGDERARBEIT => 'Tag der Arbeit',
            self::TAGDERBEFREIUNG => 'Tag der Befreiung',
            self::CHRISTIHIMMELFAHRT => 'Christi Himmelfahrt',
            self::PFINGSTMONTAG => 'Pfingstmontag',
            self::PFINGSTSONNTAG => 'Pfingstsonntag',
            self::FRONLEICHNAM => 'Fronleichnam',
            self::TAGDESVOLKSAUFSTANDS => 'Tag des Volksaufstandes',
            self::AUGSBURGERFRIEDENSFEST => 'Augsburger Hohes Friedensfest',
            self::MARIAEHIMMELFAHRT => 'Mariä Himmelfahrt',
            self::WELTKINDERTAG => 'Weltkindertag',
            self::TAGDERDEUTSCHENEINHEIT => 'Tag der Deutschen Einheit',
            self::REFORMATIONSTAG => 'Reformationstag',
            self::ALLERHEILIGEN => 'Allerheiligen',
            self::BUSSUNDBETTAG => 'Buß- und Bettag',
            self::ERSTERWEIHNACHTSTAG => '1. Weihnachtstag',
            self::ZWEITERWEIHNACHTSTAG => '2. Weihnachtstag',
        ];

        return $MAP[$key] ?? '';
    }

    /**
     * Calculates public holidays for a specified period of time
     */
    public function getLegalHolidaysByRange(DateTimeInterface $fromDate, DateTimeInterface $toDate): array
    {
        $out = [];

        $fromYear = (int)$fromDate->format('Y');
        $toYear = (int)$toDate->format('Y');

        for ($year = $fromYear; $year <= $toYear; $year++) {
            $this->factory($year);

            foreach ($this->publicHolidays[$year]['legal'] as $key => $date) {
                if ($date >= $fromDate && $date <= $toDate) {
                    $out[$key] = $date;
                }
            }
        }

        return $out;
    }
}
