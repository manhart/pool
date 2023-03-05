<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\PoolObject;

final class PublicHolidays extends PoolObject
{
    /**
     * Feiertage (Public Holidays)
     *
     * @see https://www.welt-der-zahlen.info/berechnung.html
     */

    const NEUJAHRSTAG = 0;
    const HEILIGEDREIKOENIGE = 1;
    const ROSENMONTAG = 2;
    const ASCHERMITTWOCH = 3;
    const FRAUENTAG = 4;
    const GRUENDONNERSTAG = 5;
    const KARFREITAG = 6;
    const OSTERSONNTAG = 7;
    const OSTERMONTAG = 8;
    const TAGDERARBEIT = 9;
    const CHRISTIHIMMELFAHRT = 10;
    const PFINGSTSONNTAG =11;
    const PFINGSTMONTAG = 12;
    const FRONLEICHNAM = 13;
    const AUGSBURGERFRIEDENSFEST = 14;
    const MARIAEHIMMELFAHRT = 15;
    const TAGDERDEUTSCHENEINHEIT = 16;
    const REFORMATIONSTAG = 17;
    const ALLERHEILIGEN = 18;
    const BUSSUNDBETTAG = 19;
    const ERSTERWEIHNACHTSTAG = 20;
    const ZWEITERWEIHNACHTSTAG = 21;

    /**
     * Bundesländer
     *
     * @see https://www.datenportal.bmbf.de/portal/de/G122.html
     */

    const STATE_BADENWUERTTEMBERG = 'BW';
    const STATE_BAYERN = 'BY';
    const STATE_BERLIN = 'BE';
    const STATE_BRANDENBURG = 'BB';
    const STATE_BREMEN = 'HB';
    const STATE_HAMBURG = 'HH';
    const STATE_HESSEN = 'HE';
    const STATE_MECKLENBURGVORPOMMERN = 'MV';
    const STATE_NIEDERSACHSEN = 'NI';
    const STATE_NORDRHEINWESTFALEN = 'NW';
    const STATE_RHEINLANDPFALZ = 'RP';
    const STATE_SAARLAND = 'SL';
    const STATE_SACHSEN = 'SN';
    const STATE_SACHSENANHALT = 'ST';
    const STATE_SCHLESWIGHOLSTEIN = 'SH';
    const STATE_THUERINGEN = 'TH';

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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
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
            self::ZWEITERWEIHNACHTSTAG
        ],
    ];

    /**
     * holds the public holidays
     * @var array
     */
    private array $publicHolidays = [];

    /**
     * checks date for a holiday
     *
     * @param DateTimeInterface $Date
     * @param string $state
     * @param bool $legal
     * @return bool
     * @throws Exception
     */
    static function check(DateTimeInterface $Date, string $state = '', bool $legal = true): bool
    {
        $year = (int)$Date->format('Y');

        $date = $Date->format(PHP_MARIADB_DATE_FORMAT);

        $PublicHolidays = new PublicHolidays();
        foreach($PublicHolidays->getLegalHolidays($year, $state) as $Holiday) {
            if($Holiday->format(PHP_MARIADB_DATE_FORMAT) == $date) {
                return true;
            }
        }
        return false;
    }

    /**
     * returns the holiday as an PublicHoliday object
     *
     * @param DateTime $Date
     * @param string $state
     * @return PublicHoliday|null
     * @throws Exception
     */
    static function which(DateTime $Date, string $state = ''): ?PublicHoliday
    {
        $year = (int)$Date->format('Y');

        $date = $Date->format(PHP_MARIADB_DATE_FORMAT);

        $PublicHolidays = new PublicHolidays();
        foreach($PublicHolidays->getLegalHolidays($year, $state) as $key => $Holiday) {
            if($Holiday->format(PHP_MARIADB_DATE_FORMAT) == $date) {
                return new PublicHoliday($key, $Holiday);
            }
        }
        return null;
    }

    /**
     * Returns the German name of the holiday.
     *
     * @param int $key
     * @return string|void
     */
    static function getHolidayName(int $key)
    {
        switch($key) {
            case self::NEUJAHRSTAG:
                return 'Neujahrstag';

            case self::HEILIGEDREIKOENIGE:
                return 'Heilige Drei Könige';

            case self::FRAUENTAG:
                return 'Frauentag';

            case self::KARFREITAG:
                return 'Karfreitag';

            case self::OSTERSONNTAG:
                return 'Ostersonntag';

            case self::OSTERMONTAG:
                return 'Ostermontag';

            case self::TAGDERARBEIT:
                return 'Tag der Arbeit';

            case self::CHRISTIHIMMELFAHRT:
                return 'Christi Himmelfahrt';

            case self::PFINGSTMONTAG:
                return 'Pfingstmontag';

            case self::PFINGSTSONNTAG:
                return 'Pfingstsonntag';

            case self::FRONLEICHNAM:
                return 'Fronleichnam';

            case self::MARIAEHIMMELFAHRT:
                return 'Mariä Himmelfahrt';

            case self::TAGDERDEUTSCHENEINHEIT:
                return 'Tag der Deutschen Einheit';

            case self::REFORMATIONSTAG:
                return 'Reformationstag';

            case self::ALLERHEILIGEN:
                return 'Allerheiligen';

            case self::BUSSUNDBETTAG:
                return 'Buß- und Bettag';

            case self::ERSTERWEIHNACHTSTAG:
                return '1. Weihnachtstag';

            case self::ZWEITERWEIHNACHTSTAG:
                return '2. Weihnachtstag';
        }
    }

    /**
     * Calculates public holidays for a specified period of time
     *
     * @param DateTimeInterface $FromDate
     * @param DateTimeInterface $ToDate
     * @return array
     */
    public function getLegalHolidaysByRange(DateTimeInterface $FromDate, DateTimeInterface $ToDate): array
    {
        $holidaysByRange = array();
        $fromYear = (int)$FromDate->format('Y');
        $toYear = (int)$ToDate->format('Y');

        for($i=$fromYear; $i <= $toYear; $i++) {
            try {
                $this->factory($i);
            }
            catch(Exception) {}

            foreach($this->publicHolidays as $key => $Holiday) {
                if($FromDate <= $Holiday and $Holiday <= $ToDate) {
                    $holidaysByRange[$key] = $Holiday;
                }
            }
        }
        return $holidaysByRange;
    }

    /**
     * returns legal german holidays
     *
     * @param int $year
     * @param string $state
     * @return array
     * @throws Exception
     */
    public function getLegalHolidays(int $year, string $state = ''): array
    {
        try {
            $this->factory($year);
        }
        catch(Exception) {}

        if($state == '') {
            return $this->publicHolidays[$year]['legal'];
        }

        if(!isset($this->publicHolidaysByState[$state])) {
            throw new Exception('state '.$state.' unknown');
        }

        $holidays = [];
        foreach($this->publicHolidaysByState[$state] as $key) {
            $holidays[$key] = $this->publicHolidays[$year]['legal'][$key];
        }
        return $holidays;
    }

    /**
     * creates internally all holidays of a year.
     *
     * @param int $year
     * @return void
     * @throws Exception
     */
    private function factory(int $year): void
    {
        if(isset($this->publicHolidays[$year])) {
            // already created
            return;
        }
        $Date = new DateTimeImmutable($year.'-01-01');
        $this->publicHolidays[$year]['legal'][self::NEUJAHRSTAG] = $Date;

        $Date = new DateTimeImmutable($year.'-01-06');
        $this->publicHolidays[$year]['legal'][self::HEILIGEDREIKOENIGE] = $Date;

        $EasterSunday = $this->calculateEasterSunday($year);

        $Date = $EasterSunday->modify('-46 days');
        $this->publicHolidays[$year]['other'][self::ASCHERMITTWOCH] = $Date;

        $Date = $EasterSunday->modify('-48 days');
        $this->publicHolidays[$year]['other'][self::ROSENMONTAG] = $Date;

        $Date = new DateTimeImmutable($year . '-03-08');
        $this->publicHolidays[$year]['legal'][self::FRAUENTAG] = $Date;


        $Date = $EasterSunday->modify('-3 days');
        $this->publicHolidays[$year]['other'][self::GRUENDONNERSTAG] = $Date;

        $Date = $EasterSunday->modify('-2 days');
        $this->publicHolidays[$year]['legal'][self::KARFREITAG] = $Date;

        $Date = $EasterSunday;
        $this->publicHolidays[$year]['legal'][self::OSTERSONNTAG] = $Date;

        $Date = $EasterSunday->modify('+1 days');
        $this->publicHolidays[$year]['legal'][self::OSTERMONTAG] = $Date;

        $Date = new DateTimeImmutable($year.'-05-01');
        $this->publicHolidays[$year]['legal'][self::TAGDERARBEIT] = $Date;

        $Date = $EasterSunday->modify('+39 days');
        $this->publicHolidays[$year]['legal'][self::CHRISTIHIMMELFAHRT] = $Date;

        $Date = $EasterSunday->modify('+49 days');
        $this->publicHolidays[$year]['legal'][self::PFINGSTSONNTAG] = $Date;

        $Date = $EasterSunday->modify('+50 days');
        $this->publicHolidays[$year]['legal'][self::PFINGSTMONTAG] = $Date;

        $Date = $EasterSunday->modify('+60 days');
        $this->publicHolidays[$year]['legal'][self::FRONLEICHNAM] = $Date;

        $Date = new DateTimeImmutable($year.'-08-08');
        $this->publicHolidays[$year]['legal'][self::AUGSBURGERFRIEDENSFEST] = $Date;

        $Date = new DateTimeImmutable($year.'-08-15');
        $this->publicHolidays[$year]['legal'][self::MARIAEHIMMELFAHRT] = $Date;

        $Date = new DateTimeImmutable($year.'-10-03');
        $this->publicHolidays[$year]['legal'][self::TAGDERDEUTSCHENEINHEIT] = $Date;

        $Date = new DateTimeImmutable($year.'-10-31');
        $this->publicHolidays[$year]['legal'][self::REFORMATIONSTAG] = $Date;

        $Date = new DateTimeImmutable($year.'-11-01');
        $this->publicHolidays[$year]['legal'][self::ALLERHEILIGEN] = $Date;

        $Date = new DateTimeImmutable($year.'-11-22');
        while ($Date->format('N') != 3) {
            $Date = $Date->modify('-1 days');
        }
        $this->publicHolidays[$year]['legal'][self::BUSSUNDBETTAG] = $Date;

        $Date = new DateTimeImmutable($year.'-12-25');
        $this->publicHolidays[$year]['legal'][self::ERSTERWEIHNACHTSTAG] = $Date;

        $Date = new DateTimeImmutable($year.'-12-26');
        $this->publicHolidays[$year]['legal'][self::ZWEITERWEIHNACHTSTAG] = $Date;
    }

    /**
     * divide
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    private static function div(int $a, int $b): int
    {
        return intval($a / $b);
    }

    /**
     * modulus
     *
     * @param int $a
     * @param int $b
     * @return int
     */
    private static function mod(int $a, int $b): int
    {
        return $a % $b;
    }

    /**
     * @see https://de.wikipedia.org/wiki/Gau%C3%9Fsche_Osterformel
     *
     * @param int $year
     * @return int
     */
    private static function gauss(int $year): int
    {
        $a = self::mod($year, 19);
        $b = self::mod($year, 4);
        $c = self::mod($year, 7);
        $H1 = self::div($year, 100);
        $H2 = self::div($year, 400);
        $N = 4 + $H1 - $H2;
        $M = 15 + $H1 - $H2 - self::div(8 * $H1 + 13, 25);
        $d = self::mod(19 * $a + $M, 30);
        $e = self::mod(2 * $b + 4 * $c + 6 * $d + $N, 7);
        $o = 22 + $d + $e;

        if ($o == 57) {
            $o = 50;
        }

        if ($d == 28 && $e == 6 && $a > 10) {
            $o = 49;
        }

        return $o;
    }

    /**
     * calculate easter sunday
     * @param int $year
     * @return DateTimeImmutable
     */
    public function calculateEasterSunday(int $year): DateTimeImmutable
    {
        $os = self::gauss($year);

        $monat = 3;

        // $os may be the 32. March = 1. April
        if (31 < $os) {
            $os = $os % 31;
            $monat = 4;
        }

        return new DateTimeImmutable("$year-$monat-$os");
    }
}