<?php
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

declare(strict_types = 1);

namespace pool\utils;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use pool\classes\Core\PoolObject;

final class PublicHolidays extends PoolObject
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

    /**
     * Welche Feiertage in welchem Bundesland
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
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
            self::FRONLEICHNAM,
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
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::PFINGSTSONNTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::REFORMATIONSTAG,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_BRANDENBURG => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
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
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ERSTERWEIHNACHTSTAG,
            self::ZWEITERWEIHNACHTSTAG,
        ],
        self::STATE_MECKLENBURGVORPOMMERN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
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
        self::STATE_NIEDERSACHSEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
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
        self::STATE_NORDRHEINWESTFALEN => [
            self::NEUJAHRSTAG,
            self::KARFREITAG,
            self::OSTERMONTAG,
            self::TAGDERARBEIT,
            self::CHRISTIHIMMELFAHRT,
            self::PFINGSTMONTAG,
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
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
            self::PFINGSTSONNTAG,
            self::FRONLEICHNAM,
            self::MARIAEHIMMELFAHRT,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
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
            self::PFINGSTSONNTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
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
            self::PFINGSTSONNTAG,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
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
            self::PFINGSTSONNTAG,
            self::FRONLEICHNAM,
            self::TAGDERDEUTSCHENEINHEIT,
            self::ALLERHEILIGEN,
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

    /** @var PublicHolidays|null Request-Cache */
    private static ?PublicHolidays $instance = null;

    /**
     * Checks date for a holiday
     */
    static function check(DateTimeInterface $Date, string $state = ''): bool
    {
        $self = self::$instance ??= new PublicHolidays();

        $year = (int)$Date->format('Y');
        $date = $Date->format('Y-m-d');

        foreach ($self->getLegalHolidays($year, $state) as $holiday) {
            if ($holiday->format('Y-m-d') === $date) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns legal German holidays
     *
     * @throws \pool\classes\Exception\InvalidArgumentException
     */
    public function getLegalHolidays(int $year, string $state = ''): array
    {
        $this->factory($year);

        if ($state == '') {
            return $this->publicHolidays[$year]['legal'];
        }

        if (!isset($this->publicHolidaysByState[$state])) {
            throw new \pool\classes\Exception\InvalidArgumentException("State $state unknown");
        }

        $out = [];
        foreach ($this->publicHolidaysByState[$state] as $key) {
            $out[$key] = $this->publicHolidays[$year]['legal'][$key];
        }
        return $out;
    }

    /**
     * Returns the holiday as a PublicHoliday object
     */
    static function which(DateTime $Date, string $state = ''): ?PublicHoliday
    {
        $self = self::$instance ??= new PublicHolidays();

        $year = (int)$Date->format('Y');
        $date = $Date->format('Y-m-d');

        foreach ($self->getLegalHolidays($year, $state) as $key => $holiday) {
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
        $phLegal[self::CHRISTIHIMMELFAHRT] = $easterSunday->modify('+39 days');
        $phLegal[self::PFINGSTSONNTAG] = $easterSunday->modify('+49 days');
        $phLegal[self::PFINGSTMONTAG] = $easterSunday->modify('+50 days');
        $phLegal[self::FRONLEICHNAM] = $easterSunday->modify('+60 days');
        $phLegal[self::AUGSBURGERFRIEDENSFEST] = new DateTimeImmutable("$year-08-08");
        $phLegal[self::MARIAEHIMMELFAHRT] = new DateTimeImmutable("$year-08-15");
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
            self::CHRISTIHIMMELFAHRT => 'Christi Himmelfahrt',
            self::PFINGSTMONTAG => 'Pfingstmontag',
            self::PFINGSTSONNTAG => 'Pfingstsonntag',
            self::FRONLEICHNAM => 'Fronleichnam',
            self::MARIAEHIMMELFAHRT => 'Mariä Himmelfahrt',
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
