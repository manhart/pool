<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\tests;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use pool\classes\Core\Weblication;
use ReflectionMethod;

if (!class_exists(\Log::class, false)) {
    require_once __DIR__.'/bootstrap.php';
}

class LogTest extends TestCase
{
    public function testProcessExtraReplacesPlaceholdersAndKeepsOnlyUnconsumedExtra(): void
    {
        $configurationName = $this->setupScreenLogWithExtra();

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_SCREEN,
            ['trace' => 'line 1', 'recordId' => 123],
            'Failure trace: {{trace}}',
        );

        self::assertSame('Failure trace: line 1', $message);
        self::assertSame(['recordId' => 123], $remainingExtra);
    }

    public function testProcessExtraDoesNothingWhenWithExtraIsDisabled(): void
    {
        $configurationName = __METHOD__;
        \Log::setup([
            \Log::OUTPUT_SCREEN => [
                'level' => \Log::LEVEL_INFO,
                'withExtra' => false,
            ],
        ], $configurationName);

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_SCREEN,
            ['trace' => 'line 1'],
            'Failure trace: {{trace}}',
        );

        self::assertSame('Failure trace: {{trace}}', $message);
        self::assertSame([], $remainingExtra);
    }

    public function testProcessExtraStillSupportsLegacySingleBracePlaceholders(): void
    {
        $configurationName = $this->setupScreenLogWithExtra();

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_SCREEN,
            ['reference' => 'REF-123', 'recordId' => 42],
            '{reference}: import failed',
        );

        self::assertSame('REF-123: import failed', $message);
        self::assertSame(['recordId' => 42], $remainingExtra);
    }

    public function testDoubleBracePlaceholderWinsOverLegacySingleBracePlaceholder(): void
    {
        $configurationName = $this->setupScreenLogWithExtra();

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_SCREEN,
            ['reference' => 'REF-123', 'recordId' => 42],
            '{{reference}}: import failed',
        );

        self::assertSame('REF-123: import failed', $message);
        self::assertSame(['recordId' => 42], $remainingExtra);
    }

    public function testMailOutputFallsBackToScreenWithExtraConfiguration(): void
    {
        $configurationName = $this->setupScreenLogWithExtra();

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_MAIL,
            ['trace' => 'line 1', 'errorCode' => 500],
            'Failure trace: {{trace}}',
        );

        self::assertSame('Failure trace: line 1', $message);
        self::assertSame(['errorCode' => 500], $remainingExtra);
    }

    public function testAppendExtraToTextUsesCompactJson(): void
    {
        $message = $this->invokePrivateStatic('appendExtraToText', [
            'Batch import finished.',
            ['total' => 2, 'success' => 1, 'failed' => 1],
        ]);

        self::assertSame('Batch import finished. | extra: {"total":2,"success":1,"failed":1}', $message);
    }

    public function testPlaceholderCanUseStructuredExtraValue(): void
    {
        $configurationName = $this->setupScreenLogWithExtra();

        [$message, $remainingExtra] = $this->invokeProcessExtra(
            $configurationName,
            \Log::OUTPUT_SCREEN,
            ['payload' => ['id' => 123, 'url' => 'https://example.test/a/b']],
            'Payload: {{payload}}',
        );

        self::assertSame('Payload: {"id":123,"url":"https://example.test/a/b"}', $message);
        self::assertSame([], $remainingExtra);
    }

    public function testWriteFileWritesLevelMessageAndExtra(): void
    {
        $logFile = $this->tempLogFile();
        $configurationName = $this->setupFileLog($logFile, \Log::LEVEL_INFO);

        \Log::writeFile('Batch import finished.', \Log::LEVEL_INFO, ['total' => 2], $configurationName);
        \Log::close();

        $contents = file_get_contents($logFile);
        self::assertIsString($contents);
        self::assertStringContainsString('Info Batch import finished. {"total":2}', $contents);
    }

    public function testMessageRoutesOnlyToEnabledFileLevel(): void
    {
        $logFile = $this->tempLogFile();
        $configurationName = $this->setupFileLog($logFile, \Log::LEVEL_ERROR);

        \Log::message('Skipped info.', \Log::LEVEL_INFO, configurationName: $configurationName);
        \Log::message('Written error.', \Log::LEVEL_ERROR, configurationName: $configurationName);
        \Log::close();

        $contents = file_get_contents($logFile);
        self::assertIsString($contents);
        self::assertStringNotContainsString('Skipped info.', $contents);
        self::assertStringContainsString('Error Written error.', $contents);
    }

    public function testNoticeRoutesToFileAndAllIncludesNewLevels(): void
    {
        self::assertSame(\Log::LEVEL_NOTICE, \Log::LEVEL_ALL & \Log::LEVEL_NOTICE);
        self::assertSame(\Log::LEVEL_DEBUG, \Log::LEVEL_ALL & \Log::LEVEL_DEBUG);

        $logFile = $this->tempLogFile();
        $configurationName = $this->setupFileLog($logFile, \Log::LEVEL_NOTICE);

        \Log::notice('Shipment requires attention.', configurationName: $configurationName);
        \Log::close();

        $contents = file_get_contents($logFile);
        self::assertIsString($contents);
        self::assertStringContainsString('Notice Shipment requires attention.', $contents);
    }

    public function testScreenStreamReturnsConfiguredCliStream(): void
    {
        $configurationName = uniqid('log-test-', true);
        \Log::setup([
            \Log::OUTPUT_SCREEN => [
                'level' => \Log::LEVEL_INFO,
                'stream' => \STDERR,
            ],
        ], $configurationName);

        self::assertSame(
            \STDERR,
            $this->invokePrivateStatic('screenStream', [$configurationName]),
        );
    }

    private function setupScreenLogWithExtra(): string
    {
        $configurationName = uniqid('log-test-', true);
        \Log::setup([
            \Log::OUTPUT_SCREEN => [
                'level' => \Log::LEVEL_INFO,
                'withDate' => false,
                'withLineBreak' => false,
                'withExtra' => true,
                'showLevelNameAtTheBeginning' => false,
            ],
        ], $configurationName);
        return $configurationName;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNoticeUsesJournaldPriority(): void
    {
        $socketPath = sys_get_temp_dir().'/pool-journald-'.bin2hex(random_bytes(8)).'.sock';
        $server = socket_create(AF_UNIX, SOCK_DGRAM, 0);

        self::assertInstanceOf(\Socket::class, $server);
        self::assertTrue(socket_bind($server, $socketPath));

        try {
            $configurationName = $this->setupJournaldLog($socketPath);
            \Log::notice('notice-message', configurationName: $configurationName);

            $payload = '';
            $bytesReceived = socket_recv($server, $payload, 65535, 0);
            self::assertNotFalse($bytesReceived, 'No Journald datagram received.');
            self::assertGreaterThan(0, $bytesReceived);
            self::assertSame(
                "PRIORITY=5\nMESSAGE=notice-message\nSYSLOG_IDENTIFIER=POOL_LOG_$configurationName",
                rtrim($payload, "\n"),
            );
        } finally {
            socket_close($server);
            @unlink($socketPath);
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testJournaldLog(): void
    {
        $socketPath = sys_get_temp_dir().'/pool-journald-'.bin2hex(random_bytes(8)).'.sock';
        $server = socket_create(AF_UNIX, SOCK_DGRAM, 0);

        self::assertInstanceOf(\Socket::class, $server);
        self::assertTrue(socket_bind($server, $socketPath));
        self::assertTrue(socket_set_nonblock($server));

        $receive = static function (bool $expectNoMessage = false) use ($server): string {
            $payload = '';
            $bytesReceived = socket_recv($server, $payload, 65535, 0);
            if (!$expectNoMessage){
                self::assertNotFalse($bytesReceived, 'No Journald datagram received.');
                self::assertGreaterThan(0, $bytesReceived);
                return rtrim($payload, "\n");
            } else {
                self::assertFalse($bytesReceived, 'Unexpected Journald datagram received.');
                $errorCode = socket_last_error($server);
                self::assertSame(SOCKET_EAGAIN, $errorCode);
                return '';
            }
        };

        try {
            //fallback tag, associative extras, malformed extras, string priority, duplicate message and tag.
            self::assertFalse(Weblication::hasInstance(), 'This test requires a fresh Weblication state.');
            $configName = $this->setupJournaldLog($socketPath);
            \Log::info('primary-message', [
                'PRIORITY' => '3',
                'MESSAGE' => 'extra-message',
                'SYSLOG_IDENTIFIER' => 'extra-tag',
                'ASSOC_FIELD' => 'associative',
                123 => 'ignored-non-string-key',
                'garbage',
            ], $configName);
            $received = $receive();
            $expected = <<< EOF
PRIORITY=3
MESSAGE=extra-message
SYSLOG_IDENTIFIER=extra-tag
ASSOC_FIELD=associative
MESSAGE=primary-message
SYSLOG_IDENTIFIER=POOL_LOG_$configName
EOF;
            self::assertSame($expected, $received);

            //Weblication tag, empty message, malformed extra tuples, key-transform, log::debug, no extra priority
            Weblication::getInstance()->setName('POOL_APP');
            $configName = $this->setupJournaldLog($socketPath);
            \Log::debug('', [
                [],
                [123, 'invalid-field-type-and-name'],
                ['123', 'invalid-field-name1'],
                ['Ж', 'invalid-field-name2'],
                ['ABC!', 'invalid-field-name3'],
                ['1A', 'invalid-field-name4'],
                ['_A', 'invalid-protected-field-name'],
                ['a123-camelCase_Field', 'fixable-field-name'],
                ['TOO', 'MANY', 'VALUES'],
                ['TOO_FEW_VALUES'],
                [],
            ], $configName);
            $received = $receive();
            $expected = <<< EOF
A123_CAMEL_CASE_FIELD=fixable-field-name
PRIORITY=7
SYSLOG_IDENTIFIER=POOL_APP
EOF;
            self::assertSame($expected, $received);

            //batch job tag, tuple extras, integer priority, malformed extras, 0 message, 0 tag
            define('JOB_NAME', 'MY_BATCH_JOB');
            $configName = $this->setupJournaldLog($socketPath);
            \Log::warn( '0', [
                    ['PRIORITY', 2],
                    ['TUPLE_FIELD', 'tuple'],
                    ['MESSAGE', 'extra-message'],
                    ['SYSLOG_IDENTIFIER', 0],
                    ['SYSLOG_IDENTIFIER', '0'],
                ], $configName);
            $received = $receive();
            $expected = <<< EOF
PRIORITY=2
TUPLE_FIELD=tuple
MESSAGE=extra-message
SYSLOG_IDENTIFIER=0
SYSLOG_IDENTIFIER=0
MESSAGE=0
SYSLOG_IDENTIFIER=MY_BATCH_JOB
EOF;
            self::assertSame($expected, $received);

            //Explicit configured tag, invalid extra priority, Log::warn
            $configName = $this->setupJournaldLog($socketPath, 'configured-tag');
            \Log::warn("multi\nline\rmessage\0!", [
                'PRIORITY' => '8',
                ['PRIORITY', 0, 'garbage'],
            ], $configName);
            $received = $receive();
            $encodedMessage = "\x14\0\0\0\0\0\0\0multi\nline\rmessage\0!";
            $expected = <<< EOF
PRIORITY=4
MESSAGE
$encodedMessage
SYSLOG_IDENTIFIER=configured-tag
EOF;
            self::assertSame($expected, $received);

            //test level filter
            $configName = $this->setupJournaldLog($socketPath, level: \Log::LEVEL_ERROR);
            \Log::warn('', configurationName: $configName);
            $receive(true);
        } finally {
            socket_close($server);
            @unlink($socketPath);
        }
    }

    private function setupFileLog(string $logFile, int $level): string
    {
        $configurationName = uniqid('log-test-', true);
        \Log::setup([
            \Log::OUTPUT_FILE => [
                'level' => $level,
                'file' => $logFile,
            ],
            \Log::EXIT_LEVEL => \Log::LEVEL_NONE,
        ], $configurationName);
        return $configurationName;
    }

    private function setupJournaldLog(string $socketPath, ?string $tag = null, int $level = \Log::LEVEL_ALL): string
    {
        $configurationName = uniqid('log-test-', true);
        $conf = [
            'level' => $level,
            'socketPath' => $socketPath,
        ];
        if ($tag) {
            $conf['tag'] = $tag;
        }
        \Log::setup([
            \Log::OUTPUT_JOURNALD => $conf,
            \Log::EXIT_LEVEL => \Log::LEVEL_NONE,
        ], $configurationName);
        return $configurationName;
    }

    private function tempLogFile(): string
    {
        $logFile = tempnam(sys_get_temp_dir(), 'pool-log-test-');
        self::assertIsString($logFile);
        return $logFile;
    }

    private function invokeProcessExtra(string $configurationName, string $output, array $extra, string $message): array
    {
        return $this->invokePrivateStatic('processExtra', [$configurationName, $output, $extra, $message]);
    }

    private function invokePrivateStatic(string $method, array $args): mixed
    {
        $reflectionMethod = new ReflectionMethod(\Log::class, $method);
        return $reflectionMethod->invokeArgs(null, $args);
    }
}
