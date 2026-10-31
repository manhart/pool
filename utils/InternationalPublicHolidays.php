<?php
declare(strict_types=1);

/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\utils;

use DateTimeInterface;
use pool\classes\Exception\RuntimeException;

use function in_array;
use function isValidJsonContainer;
use function json_decode;

use const CURLOPT_CONNECTTIMEOUT;
use const CURLOPT_FAILONERROR;
use const CURLOPT_TIMEOUT;

class InternationalPublicHolidays
{
    /**
     * Singleton instance
     */
    private static ?InternationalPublicHolidays $Instance;

    private string $apiUrl = 'https://date.nager.at/api/v3/';

    /**
     * cache api results
     */
    private array $cache = [];

    private function __construct() {}

    /**
     * Check Germany locally: nationwide holidays for an empty state, otherwise state holidays.
     * Other countries match nationwide holidays plus those of the given ISO 3166-2 subdivision (e.g. AT-9).
     *
     * Regional holidays deliberately do not apply to the whole country. In federal countries (e.g. CH, ES)
     * their union marks many days that are ordinary working days almost everywhere, which systematically
     * delays working-day calculations. Nationwide holidays are the only set that holds everywhere, and this
     * keeps check() consistent with getHolidayDates() and Germany. Use isPublicHolidayInAnyRegion() to
     * include every regional holiday on purpose.
     */
    public static function check(DateTimeInterface $Date, string $state = 'BY', string $countryCode = 'DE'): bool
    {
        if ($countryCode === 'DE') return GermanPublicHolidays::check($Date, $state);
        // German state codes such as the default BY match no foreign subdivision, which carries its country prefix.
        return isset(self::getHolidayDates((int)$Date->format('Y'), $countryCode, $state ?: null)[$Date->format('Y-m-d')]);
    }

    /**
     * Prepare holiday dates for calendar lookups.
     * @param string|null $subdivisionCode ISO 3166-2 code (e.g. DE-BY; German states also as BY); null selects nationwide holidays only.
     * @return array<string, true> Dates keyed by Y-m-d.
     * @throws \pool\classes\Exception\InvalidArgumentException|RuntimeException
     */
    public static function getHolidayDates(int $year, string $countryCode, ?string $subdivisionCode = null): array
    {
        $dates = [];
        if ($countryCode === 'DE') {
            $germanHolidays = new GermanPublicHolidays();
            $holidays = $subdivisionCode ? $germanHolidays->getLegalHolidays($year, $subdivisionCode) : $germanHolidays->getNationwideHolidays($year);
            foreach ($holidays as $holiday) {
                $dates[$holiday->format('Y-m-d')] = true;
            }
            return $dates;
        }
        foreach (self::getInstance()->getPublicHolidays($year, $countryCode) as $holiday) {
            $applies = $holiday['global'] || ($subdivisionCode && in_array($subdivisionCode, $holiday['counties'] ?? [], true));
            if ($applies && in_array('Public', $holiday['types'], true)) {
                $dates[$holiday['date']] = true;
            }
        }
        return $dates;
    }

    /**
     * Check any public holiday in the country, including regional holidays.
     * Use check() or getHolidayDates() to restrict results to nationwide holidays or a subdivision.
     */
    public function isPublicHolidayInAnyRegion(DateTimeInterface $dateTime, string $countryCode): bool
    {
        $publicHolidays = $this->getPublicHolidays((int)$dateTime->format('Y'), $countryCode);
        foreach ($publicHolidays as $publicHoliday) {
            if ($publicHoliday['date'] === $dateTime->format('Y-m-d') && in_array('Public', $publicHoliday['types'], true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get public holidays for a country and a year
     */
    public function getPublicHolidays(int $year, string $countryCode): array
    {
        $cache = &$this->cache[$year][$countryCode];
        return $cache ??= $this->makeApiRequest($this->apiUrl."PublicHolidays/$year/$countryCode");
    }

    /**
     * Get json data from api
     *
     * @throws RuntimeException
     */
    private function makeApiRequest(string $url): array
    {
        $response = Curl::get($url, options: [
            CURLOPT_FAILONERROR => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
        ], headers: ['Accept' => 'application/json']);
        if ($response['error']) {
            throw new RuntimeException("API request \"$url\" failed: ".$response['error']);
        }

        $httpStatus = $response['statusCode'];
        if ($httpStatus === 204 || $httpStatus === 404) { // No content or unknown country.
            return [];
        }
        if ($httpStatus !== 200) {
            throw new RuntimeException("API request \"$url\" failed with status code: $httpStatus", $httpStatus);
        }

        if (!isValidJsonContainer($response['body'])) {
            throw new RuntimeException("Invalid JSON response from API \"$url\" with status code $httpStatus");
        }

        $result = json_decode($response['body'], true);
        $apiStatus = (int)($result['status'] ?? $httpStatus);
        if ($apiStatus === 404) { // CountryCode is unknown
            return [];
        }

        if ($apiStatus !== 200) {
            $errors = $result['errors'] ?? '';
            if ($errors) throw new RuntimeException("API request \"$url\" failed with error message: ".var_export($errors, true)." and status code $apiStatus", $apiStatus);
            else throw new RuntimeException("API request \"$url\" failed with status code: $apiStatus", $apiStatus);
        }
        return $result;
    }

    /**
     * Gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): InternationalPublicHolidays
    {
        return static::$Instance ??= new static;
    }

    /**
     * Resets the singleton instance (phpunit only)
     */
    public static function resetInstance(): void
    {
        static::$Instance = null;
    }

    /**
     * Get country info for the given country
     */
    public function getCountryInfo(string $countryCode): array
    {
        $url = $this->apiUrl.'CountryInfo/'.$countryCode;
        return $this->makeApiRequest($url);
    }

    /**
     * Get available countries
     */
    public function getAvailableCountries(): array
    {
        $url = $this->apiUrl.'AvailableCountries';
        return $this->makeApiRequest($url);
    }
}
