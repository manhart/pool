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

use PHPUnit\Framework\TestCase;
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
