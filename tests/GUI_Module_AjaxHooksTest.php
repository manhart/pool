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

namespace pool\tests;

// TODO: bootstrap.php is not loaded in my environment.. some kind of setting must be missing
if (!class_exists(\pool\classes\GUI\GUI_Module::class, false)) {
    require_once __DIR__.'/bootstrap.php';
}

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use pool\classes\Core\Weblication;
use pool\classes\GUI\GUI_Module;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

final class GUI_Module_AjaxHooksTest extends TestCase
{
    private Weblication $app;

    protected function setUp(): void
    {
        $this->app = Weblication::getInstance();
        GUI_TestModule::resetHookState();
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksSuccessAndFinallyAreCalledOnSuccessfulResult(): void
    {
        $gui = new GUI_TestModule($this->app);
        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethod');

        $this->assertTrue($payload['success']);
        $this->assertSame('testAjaxMethod', $payload['data']['message']);
        $this->assertSame([
            ['hook' => 'success', 'requestedMethod' => 'testAjaxMethod'],
            ['hook' => 'finally', 'requestedMethod' => 'testAjaxMethod'],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksFailureAndFinallyAreCalledWhenAjaxMethodReturnsSuccessFalse(): void
    {
        $gui = new GUI_TestModule($this->app);
        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodFailure');

        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['success']);
        $this->assertSame('testAjaxMethodFailure', $payload['data']['message']);
        $this->assertSame([
            ['hook' => 'failure', 'requestedMethod' => 'testAjaxMethodFailure'],
            ['hook' => 'finally', 'requestedMethod' => 'testAjaxMethodFailure'],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksAreNotDispatchedWhenAjaxMethodThrows(): void
    {
        $gui = new GUI_TestModule($this->app);
        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodThrows');

        $this->assertFalse($payload['success']);
        $this->assertSame('boom', $payload['error']['message']);
        $this->assertSame(RuntimeException::class, $payload['error']['type']);
        $this->assertIsString($payload['data']);
        $this->assertSame([], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksReceiveExpectedArgumentsInHookState(): void
    {
        $gui = new GUI_TestModule($this->app);
        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodWithArgumentCapture');

        $this->assertTrue($payload['success']);
        $this->assertSame('testAjaxMethodWithArgumentCapture', $payload['data']['message']);
        $this->assertSame([
            [
                'hook' => 'capture',
                'moduleClass' => GUI_TestModule::class,
                'requestedMethod' => 'testAjaxMethodWithArgumentCapture',
            ],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksDoNotFailWhenSuccessHooksAreMissing(): void
    {
        $gui = new GUI_TestModule($this->app);

        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodWithoutSuccessHooks');

        $this->assertTrue($payload['success']);
        $this->assertSame('testAjaxMethodWithoutSuccessHooks', $payload['data']['message']);
        $this->assertSame([
            ['hook' => 'finally', 'requestedMethod' => 'testAjaxMethodWithoutSuccessHooks'],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksDoNotFailWhenFailureHooksAreMissing(): void
    {
        $gui = new GUI_TestModule($this->app);

        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodWithoutFailureHooks');

        $this->assertTrue($payload['success']);
        $this->assertFalse($payload['data']['success']);
        $this->assertSame('testAjaxMethodWithoutFailureHooks', $payload['data']['message']);
        $this->assertSame([
            ['hook' => 'finally', 'requestedMethod' => 'testAjaxMethodWithoutFailureHooks'],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function ajaxHooksDoNotFailWhenFinallyHooksAreMissing(): void
    {
        $gui = new GUI_TestModule($this->app);

        $payload = $this->invokeAjaxMethod($gui, 'testAjaxMethodWithoutFinallyHooks');

        $this->assertTrue($payload['success']);
        $this->assertSame('testAjaxMethodWithoutFinallyHooks', $payload['data']['message']);
        $this->assertSame([
            ['hook' => 'success', 'requestedMethod' => 'testAjaxMethodWithoutFinallyHooks'],
        ], GUI_TestModule::$hookCalls);
    }

    /**
     * @throws ReflectionException
     * @throws JsonException
     */
    private function invokeAjaxMethod(GUI_TestModule $gui, string $method): array
    {
        $reflectionClass = new ReflectionClass($gui);
        $invokeAjaxMethod = $reflectionClass->getMethod('invokeAjaxMethod');
        $response = $invokeAjaxMethod->invoke($gui, $method);

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}

class GUI_TestModule extends GUI_Module
{
    public static array $hookCalls = [];

    public static function resetHookState(): void
    {
        self::$hookCalls = [];
    }

    protected function registerAjaxCalls(): void
    {
        $allHooks = [
            'success' => [self::testSuccess(...)],
            'failure' => [self::testFailure(...)],
            'finally' => [self::testFinally(...)],
        ];

        $withoutSuccessHooks = [
            'failure' => [self::testFailure(...)],
            'finally' => [self::testFinally(...)],
        ];

        $withoutFailureHooks = [
            'success' => [self::testSuccess(...)],
            'finally' => [self::testFinally(...)],
        ];

        $withoutFinallyHooks = [
            'success' => [self::testSuccess(...)],
            'failure' => [self::testFailure(...)],
        ];

        $this->registerAjaxMethod('testAjaxMethod', $this->testAjaxMethod(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodFailure', $this->testAjaxMethodFailure(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodThrows', $this->testAjaxMethodThrows(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodEmptyArray', $this->testAjaxMethodEmptyArray(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodNull', $this->testAjaxMethodNull(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodWithoutSuccessKey', $this->testAjaxMethodWithoutSuccessKey(...), hooks: $allHooks);
        $this->registerAjaxMethod('testAjaxMethodWithArgumentCapture', $this->testAjaxMethodWithArgumentCapture(...), hooks: [
            'success' => [self::captureArguments(...)],
        ]);
        $this->registerAjaxMethod('testAjaxMethodWithoutSuccessHooks', $this->testAjaxMethodWithoutSuccessHooks(...), hooks: $withoutSuccessHooks);
        $this->registerAjaxMethod('testAjaxMethodWithoutFailureHooks', $this->testAjaxMethodWithoutFailureHooks(...), hooks: $withoutFailureHooks);
        $this->registerAjaxMethod('testAjaxMethodWithoutFinallyHooks', $this->testAjaxMethodWithoutFinallyHooks(...), hooks: $withoutFinallyHooks);
    }

    public function testAjaxMethod(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'testAjaxMethod');
        return $result;
    }

    public function testAjaxMethodFailure(): array
    {
        [&$result] = Weblication::makeResultArray(success: false, message: 'testAjaxMethodFailure');
        return $result;
    }

    public function testAjaxMethodThrows(): never
    {
        throw new RuntimeException('boom');
    }

    public function testAjaxMethodEmptyArray(): array
    {
        return [];
    }

    public function testAjaxMethodNull(): null
    {
        return null;
    }

    public function testAjaxMethodWithoutSuccessKey(): array
    {
        return ['message' => 'missing-success-key'];
    }

    public function testAjaxMethodWithArgumentCapture(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'testAjaxMethodWithArgumentCapture');
        return $result;
    }

    public function testAjaxMethodWithoutSuccessHooks(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'testAjaxMethodWithoutSuccessHooks');
        return $result;
    }

    public function testAjaxMethodWithoutFailureHooks(): array
    {
        [&$result] = Weblication::makeResultArray(success: false, message: 'testAjaxMethodWithoutFailureHooks');
        return $result;
    }

    public function testAjaxMethodWithoutFinallyHooks(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'testAjaxMethodWithoutFinallyHooks');
        return $result;
    }

    public static function testSuccess($module, string $requestedMethod, mixed &$result = null): void
    {
        self::$hookCalls[] = ['hook' => 'success', 'requestedMethod' => $requestedMethod];
        $result['hook']['success'][__METHOD__] = true;
    }

    public static function testFailure($module, string $requestedMethod, mixed &$result = null): void
    {
        self::$hookCalls[] = ['hook' => 'failure', 'requestedMethod' => $requestedMethod];

        if (!is_array($result)) {
            $result = [];
        }

        $result['hook']['failure'][__METHOD__] = true;
    }

    public static function testFinally($module, string $requestedMethod, mixed &$result = null): void
    {
        self::$hookCalls[] = ['hook' => 'finally', 'requestedMethod' => $requestedMethod];

        if (!is_array($result)) {
            $result = [];
        }

        $result['hook']['finally'][__METHOD__] = true;
    }

    public static function captureArguments($module, string $requestedMethod, mixed &$result = null): void
    {
        self::$hookCalls[] = [
            'hook' => 'capture',
            'moduleClass' => $module::class,
            'requestedMethod' => $requestedMethod,
        ];
    }
}
