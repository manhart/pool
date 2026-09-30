<?php
declare(strict_types=1);

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Cache\Memory;
use pool\classes\Core\Weblication;
use ReflectionProperty;

if (!class_exists(Weblication::class, false)) {
    require_once __DIR__.'/bootstrap.php';
}

final class WeblicationCacheTest extends TestCase
{
    private Weblication $app;

    private ?Memory $previousMemory;

    private int $previousCacheTypes;

    protected function setUp(): void
    {
        $this->app = Weblication::getInstance();
        $this->previousMemory = new ReflectionProperty(Weblication::class, 'memory')->getValue($this->app);
        $this->previousCacheTypes = new ReflectionProperty(Weblication::class, 'cacheTypes')->getValue();

        $values = [];
        $memory = $this->createStub(Memory::class);
        $memory->method('isAvailable')->willReturn(true);
        $memory->method('setValue')->willReturnCallback(static function (string $key, mixed $value) use (&$values): bool {
            $values[$key] = $value;
            return true;
        });
        $memory->method('get')->willReturnCallback(static function (string $key) use (&$values): mixed {
            return $values[$key] ?? false;
        });
        new ReflectionProperty(Weblication::class, 'memory')->setValue($this->app, $memory);
        Weblication::caching();
    }

    protected function tearDown(): void
    {
        new ReflectionProperty(Weblication::class, 'memory')->setValue($this->app, $this->previousMemory);
        new ReflectionProperty(Weblication::class, 'cacheTypes')->setValue(null, $this->previousCacheTypes);
    }

    public function testSelectiveDisablingPreservesModuleParametersAndExistingValues(): void
    {
        $topics = [Weblication::CACHE_FILE_ACCESS, Weblication::CACHE_FILE, Weblication::CACHE_ITEM];
        foreach ($topics as $topic) {
            self::assertTrue($this->app->cacheItem('same-key', "value-$topic", $topic));
        }
        $params = ['ownOrders' => '1'];
        self::assertTrue($this->app->cacheItem('same-key', $params, Weblication::CACHE_MODULE_PARAMS));

        Weblication::caching(false, Weblication::CACHE_FILE_ACCESS | Weblication::CACHE_FILE | Weblication::CACHE_ITEM);

        foreach ($topics as $topic) {
            self::assertFalse($this->app->getCachedItem('same-key', $topic));
            self::assertFalse($this->app->cacheItem('same-key', 'replacement', $topic));
        }
        self::assertSame($params, $this->app->getCachedItem('same-key', Weblication::CACHE_MODULE_PARAMS));

        Weblication::caching(true, Weblication::CACHE_FILE);
        self::assertSame('value-'.Weblication::CACHE_FILE, $this->app->getCachedItem('same-key', Weblication::CACHE_FILE));
        self::assertFalse($this->app->getCachedItem('same-key'));

        Weblication::caching(true, Weblication::CACHE_FILE_ACCESS | Weblication::CACHE_ITEM);
        foreach ($topics as $topic) {
            self::assertSame("value-$topic", $this->app->getCachedItem('same-key', $topic));
        }
    }

    public function testGlobalSwitchAndSelectiveEnableApplyToReadsAndWrites(): void
    {
        Weblication::caching(false);
        foreach ([Weblication::CACHE_FILE_ACCESS, Weblication::CACHE_FILE, Weblication::CACHE_ITEM, Weblication::CACHE_MODULE_PARAMS] as $topic) {
            self::assertFalse($this->app->cacheItem('key', 'value', $topic));
            self::assertFalse($this->app->getCachedItem('key', $topic));
        }

        Weblication::caching(true, Weblication::CACHE_MODULE_PARAMS);
        self::assertTrue($this->app->cacheItem('key', 'params', Weblication::CACHE_MODULE_PARAMS));
        self::assertSame('params', $this->app->getCachedItem('key', Weblication::CACHE_MODULE_PARAMS));
        self::assertFalse($this->app->cacheItem('key', 'item'));

        Weblication::caching();
        self::assertTrue($this->app->cacheItem('key', 'item'));
        self::assertSame('item', $this->app->getCachedItem('key'));
        self::assertSame('params', $this->app->getCachedItem('key', Weblication::CACHE_MODULE_PARAMS));
    }

    public function testMissingMemoryKeepsCachingUnavailable(): void
    {
        new ReflectionProperty(Weblication::class, 'memory')->setValue($this->app, null);

        self::assertFalse($this->app->cacheItem('key', 'params', Weblication::CACHE_MODULE_PARAMS));
        self::assertFalse($this->app->getCachedItem('key', Weblication::CACHE_MODULE_PARAMS));
    }
}
