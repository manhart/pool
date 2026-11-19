<?php
declare(strict_types=1);

namespace pool\tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use pool\classes\Cache\Memory;
use pool\classes\Exception\RuntimeException;
use pool\utils\InternationalPublicHolidays;
use ReflectionProperty;

final class InternationalPublicHolidaysHttpTest extends TestCase
{
    /** @var resource|null */
    private $server;

    /** @var resource[] */
    private array $pipes = [];

    private ?Memory $previousMemory = null;
    private array $sharedCache = [];

    protected function setUp(): void
    {
        if (!extension_loaded('memcached')) return;
        $property = new ReflectionProperty(Memory::class, 'instance');
        $this->previousMemory = $property->getValue();
        $property->setValue(null, null);
    }

    protected function tearDown(): void
    {
        $this->stopServer();
        InternationalPublicHolidays::resetInstance();
        if (extension_loaded('memcached')) new ReflectionProperty(Memory::class, 'instance')->setValue(null, $this->previousMemory);
    }

    private function stopServer(): void
    {
        if ($this->server) {
            foreach ($this->pipes as $pipe) fclose($pipe);
            proc_terminate($this->server);
            proc_close($this->server);
            $this->server = null;
        }
    }

    private function respondWith(int $status, string $body, array $followingResponses = []): void
    {
        $this->stopServer();
        $responses = json_encode([[$status, $body], ...$followingResponses], JSON_THROW_ON_ERROR);
        $this->server = proc_open([PHP_BINARY, __DIR__.'/_data/holiday-http-server.php', $responses], [
            0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $this->pipes);
        self::assertIsResource($this->server);
        stream_set_timeout($this->pipes[1], 5);
        $address = fgets($this->pipes[1]);
        self::assertNotFalse($address);
        new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl')->setValue(
            InternationalPublicHolidays::getInstance(), 'http://'.rtrim($address).'/api/v3/',
        );
    }

    private function useSharedCache(bool $writable = true): void
    {
        $memory = $this->createStub(Memory::class);
        $memory->method('isAvailable')->willReturn(true);
        $memory->method('getValue')->willReturnCallback(fn(string $key) => $this->sharedCache[$key] ?? null);
        $memory->method('setValue')->willReturnCallback(function (string $key, mixed $value, ?int $expiration) use ($writable): bool {
            self::assertSame(0, $expiration, 'Keep the last successful response available after its refresh date.');
            if ($writable) $this->sharedCache[$key] = $value;
            return $writable;
        });
        new ReflectionProperty(Memory::class, 'instance')->setValue(null, $memory);
    }

    /** Simulate a new PHP request while retaining the shared cache and the test API endpoint. */
    private function resetClient(): InternationalPublicHolidays
    {
        $property = new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl');
        $url = $property->getValue(InternationalPublicHolidays::getInstance());
        InternationalPublicHolidays::resetInstance();
        $client = InternationalPublicHolidays::getInstance();
        $property->setValue($client, $url);
        return $client;
    }

    #[RequiresPhpExtension('memcached')]
    public function testSharedCacheSurvivesNewRequestsAndSupportsDifferentHolidayFilters(): void
    {
        $this->useSharedCache();
        $holidays = [
            ['date' => '2026-01-01', 'types' => ['Public'], 'global' => true],
            ['date' => '2026-03-19', 'types' => ['Public'], 'global' => false, 'counties' => ['AT-7']],
        ];
        $this->respondWith(200, json_encode($holidays, JSON_THROW_ON_ERROR));
        $before = time();
        self::assertSame($holidays, InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
        $this->stopServer();
        $this->resetClient();
        self::assertSame(['2026-01-01' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT'));
        self::assertSame(['2026-01-01' => true, '2026-03-19' => true], InternationalPublicHolidays::getHolidayDates(2026, 'AT', 'AT-7'));
        self::assertCount(1, $this->sharedCache);
        $entry = current($this->sharedCache);
        self::assertSame($holidays, $entry['holidays']);
        self::assertGreaterThanOrEqual($before + 30 * 86400, $entry['refreshAfter']);
        self::assertLessThanOrEqual(time() + 30 * 86400, $entry['refreshAfter']);
    }

    #[RequiresPhpExtension('memcached')]
    public function testSharedCacheSeparatesYearsCountriesAndApiEndpoints(): void
    {
        $this->useSharedCache();
        $this->respondWith(200, '[]', [[200, '[]'], [200, '[]']]);
        $client = InternationalPublicHolidays::getInstance();
        foreach ([[2026, 'AT'], [2027, 'AT'], [2026, 'CH']] as [$year, $country]) {
            self::assertSame([], $client->getPublicHolidays($year, $country));
            self::assertSame("GET /api/v3/PublicHolidays/$year/$country HTTP/1.1\r\n", fgets($this->pipes[1]));
        }
        $this->resetClient();
        $this->respondWith(200, '[]');
        self::assertSame([], InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
        self::assertSame("GET /api/v3/PublicHolidays/2026/AT HTTP/1.1\r\n", fgets($this->pipes[1]));
        self::assertCount(4, $this->sharedCache);
    }

    #[RequiresPhpExtension('memcached')]
    public function testExpiredCacheIsRefreshed(): void
    {
        $this->useSharedCache();
        $holidays = [['date' => '2026-01-01', 'types' => ['Public'], 'global' => true]];
        $this->respondWith(200, '[]', [[200, json_encode($holidays, JSON_THROW_ON_ERROR)]]);
        InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT');
        $key = array_key_first($this->sharedCache);
        $this->sharedCache[$key]['refreshAfter'] = time() - 1;
        $before = time();
        self::assertSame($holidays, $this->resetClient()->getPublicHolidays(2026, 'AT'));
        self::assertSame($holidays, $this->sharedCache[$key]['holidays']);
        self::assertGreaterThanOrEqual($before + 30 * 86400, $this->sharedCache[$key]['refreshAfter']);
    }

    #[RequiresPhpExtension('memcached')]
    public function testFailedRefreshKeepsLastResponseAndDefersRetry(): void
    {
        $this->useSharedCache();
        $holidays = [['date' => '2026-01-01', 'types' => ['Public'], 'global' => true]];
        $this->respondWith(200, json_encode($holidays, JSON_THROW_ON_ERROR), [[500, 'Unavailable']]);
        InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT');
        $key = array_key_first($this->sharedCache);
        $this->sharedCache[$key]['refreshAfter'] = time() - 1;
        $before = time();
        // Exercise Log's warning fallback even when the application bootstrap configured a logger.
        $logFacilities = new ReflectionProperty(\Log::class, 'facilities');
        $previousFacilities = $logFacilities->getValue();
        $logFacilities->setValue(null, array_diff_key($previousFacilities, [\Log::COMMON => true]));
        $warnings = [];
        set_error_handler(static function (int $level, string $message) use (&$warnings): bool {
            $warnings[] = $message;
            return true;
        }, E_USER_WARNING);
        try {
            self::assertSame($holidays, $this->resetClient()->getPublicHolidays(2026, 'AT'));
        } finally {
            restore_error_handler();
            $logFacilities->setValue(null, $previousFacilities);
        }
        self::assertCount(1, $warnings);
        self::assertStringContainsString('Holiday API request failed for AT/2026', $warnings[0]);
        self::assertSame($holidays, $this->sharedCache[$key]['holidays']);
        self::assertGreaterThanOrEqual($before + 3600, $this->sharedCache[$key]['refreshAfter']);
        self::assertLessThanOrEqual(time() + 3600, $this->sharedCache[$key]['refreshAfter']);
        $this->stopServer();
        self::assertSame($holidays, $this->resetClient()->getPublicHolidays(2026, 'AT'));
    }

    #[RequiresPhpExtension('memcached')]
    public function testCacheWriteFailureDoesNotDiscardSuccessfulApiResponse(): void
    {
        $this->useSharedCache(writable: false);
        $this->respondWith(200, '[]');
        self::assertSame([], InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
        self::assertSame([], $this->sharedCache);
        $this->stopServer();
        self::assertSame([], InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
    }

    #[RequiresPhpExtension('memcached')]
    public function testUnavailableCacheIsNotReadOrWritten(): void
    {
        $memory = $this->createMock(Memory::class);
        $memory->expects(self::once())->method('isAvailable')->willReturn(false);
        $memory->expects(self::never())->method('getValue');
        $memory->expects(self::never())->method('setValue');
        new ReflectionProperty(Memory::class, 'instance')->setValue(null, $memory);
        $holidays = [['date' => '2026-01-01', 'types' => ['Public'], 'global' => true]];
        $this->respondWith(200, json_encode($holidays, JSON_THROW_ON_ERROR));
        self::assertSame($holidays, InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
        $this->stopServer();
        self::assertSame($holidays, InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'AT'));
    }

    public function testHolidayResponseIsCachedAfterTheServerCloses(): void
    {
        $holidays = [['date' => '2026-01-01', 'types' => ['Public'], 'global' => true]];
        $this->respondWith(200, json_encode($holidays, JSON_THROW_ON_ERROR));
        $client = InternationalPublicHolidays::getInstance();
        self::assertSame($holidays, $client->getPublicHolidays(2026, 'AT'));
        self::assertSame("GET /api/v3/PublicHolidays/2026/AT HTTP/1.1\r\n", fgets($this->pipes[1]));
        $this->stopServer();
        self::assertSame($holidays, $client->getPublicHolidays(2026, 'AT'));
    }

    #[DataProvider('countryRequests')]
    public function testCountryMetadataRequests(string $method, array $arguments, string $path, array $result): void
    {
        $this->respondWith(200, json_encode($result, JSON_THROW_ON_ERROR));
        self::assertSame($result, InternationalPublicHolidays::getInstance()->$method(...$arguments));
        self::assertSame("GET /api/v3/$path HTTP/1.1\r\n", fgets($this->pipes[1]));
    }

    public static function countryRequests(): array
    {
        return [
            ['getCountryInfo', ['AT'], 'CountryInfo/AT', ['countryCode' => 'AT', 'commonName' => 'Austria']],
            ['getAvailableCountries', [], 'AvailableCountries', [['countryCode' => 'AT', 'name' => 'Austria']]],
        ];
    }

    #[DataProvider('emptyResponses')]
    public function testEmptyResultsAreCached(int $status, string $body): void
    {
        $this->respondWith($status, $body);
        $client = InternationalPublicHolidays::getInstance();
        self::assertSame([], $client->getPublicHolidays(2026, 'XX'));
        $this->stopServer();
        self::assertSame([], $client->getPublicHolidays(2026, 'XX'));
    }

    #[RequiresPhpExtension('memcached')]
    #[DataProvider('emptyResponses')]
    public function testEmptyResultsAreSharedAcrossRequests(int $status, string $body): void
    {
        $this->useSharedCache();
        $this->respondWith($status, $body);
        self::assertSame([], InternationalPublicHolidays::getInstance()->getPublicHolidays(2026, 'XX'));
        $this->stopServer();
        self::assertSame([], $this->resetClient()->getPublicHolidays(2026, 'XX'));
    }

    public static function emptyResponses(): array
    {
        return [[204, ''], [404, ''], [200, '{"status":404}'], [200, '[]']];
    }

    #[DataProvider('failedResponses')]
    public function testFailuresThrowAndAllowRetry(int $status, string $body, string $message): void
    {
        if (extension_loaded('memcached')) $this->useSharedCache();
        $this->respondWith($status, $body);
        $client = InternationalPublicHolidays::getInstance();
        try {
            $client->getPublicHolidays(2026, 'AT');
            self::fail('Expected an API exception.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
        self::assertSame([], $this->sharedCache, 'Failed requests must not be cached as empty holiday lists.');
        $this->respondWith(200, '[]');
        self::assertSame([], $client->getPublicHolidays(2026, 'AT'));
        self::assertSame("GET /api/v3/PublicHolidays/2026/AT HTTP/1.1\r\n", fgets($this->pipes[1]));
    }

    public static function failedResponses(): array
    {
        return [
            [500, '<html>Unavailable</html>', 'status code: 500'],
            [400, '{"status":400}', 'status code: 400'],
            [200, '{', 'Invalid JSON'],
            [200, 'null', 'Invalid JSON'],
            [200, '{"status":400,"errors":{"year":["Invalid year"]}}', 'Invalid year'],
            [200, '{"status":500}', 'status code: 500'],
        ];
    }

    public function testConnectionFailureThrowsAndAllowsRetry(): void
    {
        // Reserve a port without listening so the connection is refused deterministically.
        $socket = stream_socket_server('tcp://127.0.0.1:0', flags: STREAM_SERVER_BIND);
        self::assertIsResource($socket);
        $client = InternationalPublicHolidays::getInstance();
        new ReflectionProperty(InternationalPublicHolidays::class, 'apiUrl')->setValue(
            $client, 'http://'.stream_socket_get_name($socket, false).'/',
        );
        try {
            $client->getPublicHolidays(2026, 'AT');
            self::fail('Expected a connection exception.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString('failed:', $exception->getMessage());
        } finally {
            fclose($socket);
        }
        $this->respondWith(200, '[]');
        self::assertSame([], $client->getPublicHolidays(2026, 'AT'));
    }
}
