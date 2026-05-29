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

if (!class_exists(\pool\classes\GUI\GUI_Module::class, false)) {
    require_once __DIR__.'/bootstrap.php';
}

use JsonException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use pool\classes\Core\AjaxPayloadRecordMode;
use pool\classes\Core\Http\Request;
use pool\classes\Core\Weblication;
use pool\classes\GUI\GUI_Module;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

final class GUI_ModuleTest extends TestCase
{
    private Weblication $app;

    private string $recordRoot = '';

    #[\Override]
    protected function setUp(): void
    {
        $this->app = Weblication::getInstance();
        $this->app->setAjaxPayloadRecordDir('');
        $this->recordRoot = buildDirPath(sys_get_temp_dir(), 'gui-module-test-'.bin2hex(random_bytes(4)));
        GUI_ModulePayloadRecordingTestModule::resetRecordingState();
        $this->setRequestBody('{"payload":"test"}');
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->app->runDeferredAfterResponseCallbacks();
        $this->app->setAjaxPayloadRecordDir('');
        deleteDir($this->recordRoot);
        $this->setRequestBody(null);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function payloadRecorderIsDeferredByDefaultWithFinalResponseOnSuccess(): void
    {
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'recordedSuccess');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->assertSame('recordedSuccess', $payload['data']['message']);
        $this->assertSame([], GUI_ModulePayloadRecordingTestModule::$recordings);

        $this->app->runDeferredAfterResponseCallbacks();

        $this->assertCount(1, GUI_ModulePayloadRecordingTestModule::$recordings);
        $this->assertSame('recordedSuccess', GUI_ModulePayloadRecordingTestModule::$recordings[0]['method']);
        $this->assertSame('{"payload":"test"}', GUI_ModulePayloadRecordingTestModule::$recordings[0]['requestBody']);
        $this->assertSame($response, GUI_ModulePayloadRecordingTestModule::$recordings[0]['response']);
        $this->assertSame(200, GUI_ModulePayloadRecordingTestModule::$recordings[0]['statusCode']);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function payloadRecorderIsDeferredByDefaultWithFinalResponseWhenAjaxMethodThrows(): void
    {
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'recordedException');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertFalse($payload['success']);
        $this->assertSame('boom', $payload['error']['message']);
        $this->assertSame([], GUI_ModulePayloadRecordingTestModule::$recordings);

        $this->app->runDeferredAfterResponseCallbacks();

        $this->assertCount(1, GUI_ModulePayloadRecordingTestModule::$recordings);
        $this->assertSame('recordedException', GUI_ModulePayloadRecordingTestModule::$recordings[0]['method']);
        $this->assertSame('{"payload":"test"}', GUI_ModulePayloadRecordingTestModule::$recordings[0]['requestBody']);
        $this->assertSame($response, GUI_ModulePayloadRecordingTestModule::$recordings[0]['response']);
        $this->assertSame(418, GUI_ModulePayloadRecordingTestModule::$recordings[0]['statusCode']);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function payloadRecorderExceptionDoesNotChangeAjaxResponse(): void
    {
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'recordingThrows');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->assertSame('recordingThrows', $payload['data']['message']);

        $this->app->runDeferredAfterResponseCallbacks();

        $this->assertSame([], GUI_ModulePayloadRecordingTestModule::$recordings);
    }

    #[Test]
    public function deferredCallbacksContinueAfterException(): void
    {
        $calls = [];

        $this->app->deferAfterResponse(static function (): void {
            throw new RuntimeException('deferred failed');
        });
        $this->app->deferAfterResponse(static function () use (&$calls): void {
            $calls[] = 'second';
        });

        $this->app->runDeferredAfterResponseCallbacks();

        $this->assertSame(['second'], $calls);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function payloadRecorderCanRunInline(): void
    {
        GUI_ModulePayloadRecordingTestModule::$recordMode = AjaxPayloadRecordMode::Inline;
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'recordedSuccess');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->assertCount(1, GUI_ModulePayloadRecordingTestModule::$recordings);
        $this->assertSame($response, GUI_ModulePayloadRecordingTestModule::$recordings[0]['response']);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function payloadRecorderIsNotInvokedWhenRecordPayloadIsFalse(): void
    {
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'notRecorded');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->app->runDeferredAfterResponseCallbacks();
        $this->assertSame([], GUI_ModulePayloadRecordingTestModule::$recordings);
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function defaultPayloadRecorderDoesNothingWhenRecordDirIsEmpty(): void
    {
        $gui = new GUI_ModuleDefaultPayloadRecordingTestModule($this->app);
        $response = $this->invokeAjaxMethod($gui, 'recordedDefaultSuccess');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $this->app->runDeferredAfterResponseCallbacks();
        $this->assertFalse(is_dir($this->recordRoot));
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    #[Test]
    public function defaultPayloadRecorderWritesRawRequestAndResponseFiles(): void
    {
        $method = 'recordedDefaultSuccess';
        $date = date('Y-m-d');
        $this->app->setAjaxPayloadRecordDir($this->recordRoot);
        $gui = new GUI_ModuleDefaultPayloadRecordingTestModule($this->app);

        $response = $this->invokeAjaxMethod($gui, $method);
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        $this->assertTrue($payload['success']);
        $recordDir = buildDirPath($this->recordRoot, sanitizeFilename($method), $date);
        $this->assertSame([], glob($recordDir.'*.request.json') ?: []);

        $this->app->runDeferredAfterResponseCallbacks();

        $requestFiles = glob($recordDir.'*.request.json') ?: [];
        $responseFiles = glob($recordDir.'*.response.json') ?: [];

        $this->assertCount(1, $requestFiles);
        $this->assertCount(1, $responseFiles);
        $this->assertSame(
            preg_replace('/\.(request|response)\.json$/', '', $requestFiles[0]),
            preg_replace('/\.(request|response)\.json$/', '', $responseFiles[0]),
        );
        $this->assertSame('{"payload":"test"}', file_get_contents($requestFiles[0]));
        $this->assertSame($response, file_get_contents($responseFiles[0]));
    }

    /**
     * @throws ReflectionException
     */
    private function invokeAjaxMethod(GUI_Module $gui, string $method): string
    {
        $reflectionClass = new ReflectionClass($gui);
        $invokeAjaxMethod = $reflectionClass->getMethod('invokeAjaxMethod');
        return $invokeAjaxMethod->invoke($gui, $method);
    }

    /**
     * @throws ReflectionException
     */
    private function setRequestBody(?string $body): void
    {
        $reflectionProperty = new ReflectionProperty(Request::class, 'body');
        $reflectionProperty->setValue(null, $body);
    }
}

class GUI_ModuleDefaultPayloadRecordingTestModule extends GUI_Module
{
    protected function registerAjaxCalls(): void
    {
        $this->registerAjaxMethod('recordedDefaultSuccess', $this->recordedDefaultSuccess(...), recordPayload: AjaxPayloadRecordMode::AfterResponse);
    }

    public function recordedDefaultSuccess(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'recordedDefaultSuccess');
        return $result;
    }
}

class GUI_ModulePayloadRecordingTestModule extends GUI_Module
{
    public static array $recordings = [];

    public static AjaxPayloadRecordMode $recordMode = AjaxPayloadRecordMode::AfterResponse;

    public static function resetRecordingState(): void
    {
        self::$recordings = [];
        self::$recordMode = AjaxPayloadRecordMode::AfterResponse;
    }

    protected function registerAjaxCalls(): void
    {
        $this->registerAjaxMethod('recordedSuccess', $this->recordedSuccess(...), recordPayload: self::$recordMode);
        $this->registerAjaxMethod('recordedException', $this->recordedException(...), recordPayload: self::$recordMode);
        $this->registerAjaxMethod('recordingThrows', $this->recordingThrows(...), recordPayload: self::$recordMode);
        $this->registerAjaxMethod('notRecorded', $this->notRecorded(...));
    }

    public function recordedSuccess(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'recordedSuccess');
        return $result;
    }

    public function recordedException(): never
    {
        throw new RuntimeException('boom');
    }

    public function recordingThrows(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'recordingThrows');
        return $result;
    }

    public function notRecorded(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'notRecorded');
        return $result;
    }

    #[\Override]
    protected function recordAjaxPayload(
        string $method,
        string $requestBody,
        string $response,
        int $statusCode,
        ?string $logConfigurationName,
    ): void {
        if ($method === 'recordingThrows') {
            throw new RuntimeException('recording failed');
        }

        self::$recordings[] = [
            'method' => $method,
            'requestBody' => $requestBody,
            'response' => $response,
            'statusCode' => $statusCode,
            'logConfigurationName' => $logConfigurationName,
        ];
    }
}
