<?php
declare(strict_types=1);

namespace pool\tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use pool\classes\Exception\RuntimeException;
use pool\utils\InternationalPublicHolidays;
use ReflectionProperty;

final class InternationalPublicHolidaysHttpTest extends TestCase
{
    /** @var resource|null */
    private $server;

    /** @var resource[] */
    private array $pipes = [];

    protected function tearDown(): void
    {
        $this->stopServer();
        InternationalPublicHolidays::resetInstance();
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

    private function respondWith(int $status, string $body): void
    {
        $this->stopServer();
        $this->server = proc_open([PHP_BINARY, __DIR__.'/_data/holiday-http-server.php', (string)$status, $body], [
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

    public static function emptyResponses(): array
    {
        return [[204, ''], [404, ''], [200, '{"status":404}'], [200, '[]']];
    }

    #[DataProvider('failedResponses')]
    public function testFailuresThrowAndAllowRetry(int $status, string $body, string $message): void
    {
        $this->respondWith($status, $body);
        $client = InternationalPublicHolidays::getInstance();
        try {
            $client->getPublicHolidays(2026, 'AT');
            self::fail('Expected an API exception.');
        } catch (RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
        }
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
