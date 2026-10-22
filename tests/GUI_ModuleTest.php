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
use pool\classes\Exception\DAOException;
use pool\classes\GUI\GUI_Module;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use RuntimeException;

final class GUI_ModuleTest extends TestCase
{
    private Weblication $app;

    private string $recordRoot = '';

    private array $logFacilities;

    #[\Override]
    protected function setUp(): void
    {
        $this->logFacilities = $this->useSilentLogConfigurations();
        $this->app = Weblication::getInstance();
        $this->app->setAjaxPayloadRecordDir('');
        $this->recordRoot = buildDirPath(sys_get_temp_dir(), 'gui-module-test-'.bin2hex(random_bytes(4)));
        GUI_ModulePayloadRecordingTestModule::resetRecordingState();
        $this->setRequestBody('{"payload":"test"}');
    }

    #[\Override]
    protected function tearDown(): void
    {
        try {
            $this->app->runDeferredAfterResponseCallbacks();
            $this->app->setAjaxPayloadRecordDir('');
            deleteDir($this->recordRoot);
            $this->setRequestBody(null);
        } finally {
            $this->restoreLogConfigurations($this->logFacilities);
        }
    }

    #[Test]
    public function ajaxInvocationConvertsNumericStringsForNumericUnions(): void
    {
        $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
        $gui->setVar('value', '42');
        $response = $this->invokeAjaxMethod($gui, 'numericUnion');
        $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        self::assertTrue($payload['success']);
        self::assertSame(42, $payload['data']);
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

    #[Test]
    public function ajaxExceptionLogsFullChainAndJournalContextWithoutChangingResponse(): void
    {
        mkdir($this->recordRoot);
        $logFile = $this->recordRoot.'/exceptions.log';
        $ajaxLogFile = $this->recordRoot.'/ajax.log';
        $socketPath = $this->recordRoot.'/journal.sock';
        $server = socket_create(AF_UNIX, SOCK_DGRAM, 0);
        self::assertInstanceOf(\Socket::class, $server);
        self::assertTrue(socket_bind($server, $socketPath));
        self::assertTrue(socket_set_nonblock($server));

        try {
            \Log::setup([
                \Log::OUTPUT_SCREEN => ['level' => \Log::LEVEL_ERROR, 'stream' => fopen('php://output', 'w')],
                \Log::OUTPUT_FILE => ['level' => \Log::LEVEL_ERROR, 'file' => $logFile],
                \Log::OUTPUT_JOURNALD => ['level' => \Log::LEVEL_ERROR, 'socketPath' => $socketPath],
            ]);
            \Log::setup([
                \Log::OUTPUT_FILE => ['level' => \Log::LEVEL_ALL, 'file' => $ajaxLogFile],
            ], \Log::AJAX_CALL_LOG);
            $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
            $cause = new RuntimeException('execute command denied: '.str_repeat('database details ', 200));
            $exception = new RuntimeException('Unable to save', previous: new DAOException('Query failed', previous: $cause));
            $gui->exception = $exception;
            $className = $gui->getClassName();

            $response = $this->invokeAjaxMethod($gui, 'recordedException');
            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            self::assertFalse($payload['success']);
            self::assertSame(['message' => 'Unable to save', 'type' => RuntimeException::class], $payload['error']);
            self::assertSame(IS_DEVELOP ? $exception->getTraceAsString() : '', $payload['data']);
            self::assertSame(418, http_response_code());
            self::assertStringNotContainsString($cause->getMessage(), $response);

            $log = file_get_contents($logFile);
            self::assertStringContainsString("$className:recordedException\n$exception", $log);
            self::assertSame(1, substr_count($log, "$className:recordedException\n"));
            $ajaxLog = file_get_contents($ajaxLogFile);
            self::assertStringContainsString('Unable to save', $ajaxLog);
            self::assertStringNotContainsString('execute command denied', $ajaxLog);

            $journal = '';
            self::assertGreaterThan(0, socket_recv($server, $journal, 65535, 0));
            self::assertStringContainsString("EVENT=exception\n", $journal);
            self::assertStringContainsString("EXCEPTION_TYPE=RuntimeException\n", $journal);
            self::assertStringContainsString("CLASS_NAME=$className\n", $journal);
            self::assertStringContainsString("METHOD=recordedException\n", $journal);
            self::assertStringContainsString("PRIORITY=3\n", $journal);
            self::assertStringContainsString((string)$exception, $journal);

            $success = json_decode($this->invokeAjaxMethod($gui, 'recordedSuccess'), true, 512, JSON_THROW_ON_ERROR);
            self::assertTrue($success['success']);
            self::assertSame(200, http_response_code());
            self::assertSame($log, file_get_contents($logFile));
            self::assertFalse(socket_recv($server, $journal, 65535, 0));
            self::assertSame(SOCKET_EAGAIN, socket_last_error($server));
        } finally {
            \Log::setup([]);
            \Log::setup([], \Log::AJAX_CALL_LOG);
            socket_close($server);
            unlink($socketPath);
        }
    }

    #[Test]
    public function unavailableJournalPreservesAjaxErrorAndLogsToPhp(): void
    {
        mkdir($this->recordRoot);
        $logFile = $this->recordRoot.'/php-error.log';
        $previousLogFile = ini_set('error_log', $logFile);
        try {
            \Log::setup([
                \Log::OUTPUT_JOURNALD => [
                    'level' => \Log::LEVEL_ERROR,
                    'socketPath' => $this->recordRoot.'/missing.sock',
                ],
            ]);
            $gui = new GUI_ModulePayloadRecordingTestModule($this->app);
            $gui->exception = new RuntimeException('Unable to save', previous: new DAOException('execute command denied'));

            $response = $this->invokeAjaxMethod($gui, 'recordedException');
            $payload = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
            self::assertFalse($payload['success']);
            self::assertSame(['message' => 'Unable to save', 'type' => RuntimeException::class], $payload['error']);
            self::assertSame(418, http_response_code());
            self::assertStringNotContainsString('execute command denied', $response);
            self::assertStringContainsString((string)$gui->exception, file_get_contents($logFile));
        } finally {
            \Log::setup([]);
            ini_set('error_log', $previousLogFile);
        }
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

    private function useSilentLogConfigurations(): array
    {
        $reflectionProperty = new ReflectionProperty(\Log::class, 'facilities');
        $logFacilities = $reflectionProperty->getValue();
        $silentLogFacilities = $logFacilities;
        $silentLogFacilities[\Log::COMMON] = [];
        $silentLogFacilities[\Log::AJAX_CALL_LOG] = [];
        $reflectionProperty->setValue(null, $silentLogFacilities);

        return $logFacilities;
    }

    private function restoreLogConfigurations(array $logFacilities): void
    {
        $reflectionProperty = new ReflectionProperty(\Log::class, 'facilities');
        $reflectionProperty->setValue(null, $logFacilities);
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

    public ?\Throwable $exception = null;

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
        $this->registerAjaxMethod('numericUnion', static fn(int|float $value): int|float => $value);
    }

    public function recordedSuccess(): array
    {
        [&$result] = Weblication::makeResultArray(success: true, message: 'recordedSuccess');
        return $result;
    }

    public function recordedException(): never
    {
        throw $this->exception ?? new RuntimeException('boom');
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
