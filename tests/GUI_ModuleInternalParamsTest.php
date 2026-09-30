<?php
declare(strict_types=1);

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Cache\Memory;
use pool\classes\Core\Http\Request;
use pool\classes\Core\Module;
use pool\classes\Core\Weblication;
use pool\classes\GUI\GUI_Module;
use ReflectionProperty;

if (!class_exists(GUI_Module::class, false)) {
    require_once __DIR__.'/bootstrap.php';
}
require_once __DIR__.'/_data/GUI_InternalParams.php';

final class GUI_ModuleInternalParamsTest extends TestCase
{
    private Weblication $app;

    private Memory $memory;

    private array $previousState;

    protected function setUp(): void
    {
        $this->previousState = [
            'app' => Weblication::getInstance(),
            'get' => $_GET,
            'post' => $_POST,
            'request' => $_REQUEST,
            'isAjax' => new ReflectionProperty(Request::class, 'isAjax')->getValue(),
            'appIsAjax' => Weblication::isAjax(),
            'workingDirectory' => new ReflectionProperty(Weblication::class, 'workingDirectory')->getValue(),
            'cacheTypes' => new ReflectionProperty(Weblication::class, 'cacheTypes')->getValue(),
        ];

        $values = [];
        $this->memory = $this->createStub(Memory::class);
        $this->memory->method('isAvailable')->willReturn(true);
        $this->memory->method('setValue')->willReturnCallback(static function (string $key, mixed $value) use (&$values): bool {
            $values[$key] = $value;
            return true;
        });
        $this->memory->method('get')->willReturnCallback(static function (string $key) use (&$values): mixed {
            return $values[$key] ?? false;
        });
        Weblication::caching();
    }

    protected function tearDown(): void
    {
        $_GET = $this->previousState['get'];
        $_POST = $this->previousState['post'];
        $_REQUEST = $this->previousState['request'];
        new ReflectionProperty(Request::class, 'isAjax')->setValue(null, $this->previousState['isAjax']);
        new ReflectionProperty(Weblication::class, 'Instance')->setValue(null, $this->previousState['app']);
        new ReflectionProperty(Weblication::class, 'isAjax')->setValue(null, $this->previousState['appIsAjax']);
        new ReflectionProperty(Weblication::class, 'workingDirectory')->setValue(null, $this->previousState['workingDirectory']);
        new ReflectionProperty(Weblication::class, 'cacheTypes')->setValue(null, $this->previousState['cacheTypes']);
    }

    public function testRestoresTemplateParametersBeforeInitWithoutEnablingContentCaches(): void
    {
        Weblication::caching(false, Weblication::CACHE_FILE_ACCESS | Weblication::CACHE_FILE | Weblication::CACHE_ITEM);
        $this->request(false);
        $params = 'moduleName=mod_order&mode=own&editable=1&caption=A%26B+%C3%84';
        $original = $this->loadTemplate($params);
        $key = $this->app->getModuleParamsCacheKey($original::class, $original->getName());
        self::assertSame($params, $this->app->getCachedItem($key, Weblication::CACHE_MODULE_PARAMS));

        $this->request(true);
        $_GET['mode'] = $_REQUEST['mode'] = 'external';
        $restored = $this->runModule();

        self::assertSame($original->getInternalParams(), $restored->paramsAtInit);
        self::assertSame('own', $restored->getVar('mode'));
        self::assertSame('A&B Ä', $restored->getInternalParam('caption'));
        self::assertSame('mod_order', $restored->getName());
        self::assertTrue($restored->isAjax());
    }

    public function testSeparatesSchemasClassesAndModuleNames(): void
    {
        $contexts = [
            ['orders', 'mod_order', GUI_InternalParams::class],
            ['own-orders', 'mod_order', GUI_InternalParams::class],
            ['orders', 'mod_other', GUI_InternalParams::class],
            ['orders', 'mod_order', GUI_Module::class],
        ];

        foreach ($contexts as $index => [$schema, $name, $class]) {
            $this->request(false, $schema);
            $this->loadTemplate("moduleName=$name&mode=$index", $class);
        }
        foreach ($contexts as $index => [$schema, $name, $class]) {
            $this->request(true, $schema, $name, $class);
            self::assertSame((string)$index, $this->runModule()->getInternalParam('mode'));
        }
    }

    public function testPageLoadsReplaceChangedAndRemovedParameters(): void
    {
        $this->request(false, '');
        $this->loadTemplate('mode=old');
        $this->request(false, '');
        $name = $this->loadTemplate('mode=new')->getName();

        $this->request(true, 'orders', $name);
        self::assertSame('new', $this->runModule()->getInternalParam('mode'));

        $this->request(false);
        $this->loadTemplate();
        $this->request(true, 'orders', $name);
        self::assertSame([], $this->runModule()->getInternalParams());
    }

    public function testFactoryUsesOnlyExplicitParametersAndDoesNotChangeTheCache(): void
    {
        $this->request(false);
        $this->loadTemplate('moduleName=mod_order&mode=own&editable=1');
        $this->request(false);
        GUI_Module::createGUIModule(GUI_InternalParams::class, $this->app, params: 'moduleName=mod_order&mode=direct', autoLoadFiles: false);

        $this->request(true);
        $direct = GUI_Module::createGUIModule(GUI_InternalParams::class, $this->app, params: 'mode=explicit', autoLoadFiles: false);
        self::assertSame(['mode' => 'explicit'], $direct->paramsAtInit);

        $this->request(true);
        $_POST['mode'] = $_REQUEST['mode'] = 'external';
        $restored = $this->runModule();
        self::assertSame('own', $restored->getInternalParam('mode'));
        self::assertSame('own', $restored->getVar('mode'));
        self::assertSame('1', $restored->getInternalParam('editable'));
    }

    public function testChildModulesKeepTheirTemplateParametersDuringAjax(): void
    {
        $this->request(false);
        $this->loadTemplate('moduleName=mod_order&mode=own&editable=1');
        $this->request(false);
        $this->loadTemplate('moduleName=mod_child&mode=cached-child');

        $this->request(true);
        $this->runModule();
        $child = $this->loadTemplate('moduleName=mod_child&mode=child');
        self::assertSame(['moduleName' => 'mod_child', 'mode' => 'child'], $child->paramsAtInit);

        $this->request(true, name: 'mod_child');
        self::assertSame('cached-child', $this->runModule()->getInternalParam('mode'));
    }

    public function testMissingOrDisabledCacheKeepsThePreviousRunBehavior(): void
    {
        $this->request(true);
        self::assertSame([], $this->runModule()->paramsAtInit);

        $this->request(false);
        $this->loadTemplate('moduleName=mod_order&mode=cached');
        $this->request(true);
        Weblication::caching(false, Weblication::CACHE_MODULE_PARAMS);
        self::assertSame([], $this->runModule()->paramsAtInit);

        Weblication::caching(true, Weblication::CACHE_MODULE_PARAMS);
        $this->request(true);
        new ReflectionProperty(Weblication::class, 'memory')->setValue($this->app, null);
        self::assertSame([], $this->runModule()->paramsAtInit);
    }

    private function request(
        bool $ajax,
        string $schema = 'orders',
        string $name = 'mod_order',
        string $class = GUI_InternalParams::class,
    ): void {
        $_GET = ['schema' => $schema];
        if ($ajax) {
            $_GET += ['module' => $class, 'moduleName' => $name, 'method' => 'load'];
        }
        $_POST = [];
        $_REQUEST = $_GET;
        new ReflectionProperty(Request::class, 'isAjax')->setValue(null, $ajax);
        new ReflectionProperty(Weblication::class, 'Instance')->setValue(null, null);
        $this->app = Weblication::getInstance();
        $this->app->setName('module-params-test');
        $this->app->setDefaultSchema('orders');
        new ReflectionProperty(Weblication::class, 'memory')->setValue($this->app, $this->memory);
    }

    private function loadTemplate(string $params = '', string $class = GUI_InternalParams::class): GUI_Module
    {
        $file = tempnam(sys_get_temp_dir(), 'pool-module-params-');
        try {
            file_put_contents($file, "[$class($params)]");
            $host = new GUI_Module($this->app);
            $host->Template->setFilePath('stdout', $file);
            $host->searchGUIsInPreloadedContent(autoLoadFiles: false);
            return new ReflectionProperty(Module::class, 'childModules')->getValue($host)[0];
        } finally {
            unlink($file);
        }
    }

    private function runModule(): GUI_Module
    {
        $this->app->run($this->app->getLaunchModule());
        return $this->app->getMain();
    }
}
