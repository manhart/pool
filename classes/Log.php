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

use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use pool\classes\Core\Input\Input;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO;
use pool\classes\Database\DataInterface;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\LogJournald;

/**
 * Class Log
 *
 * @package pool\classes\Utils
 * @since 2022-01-18
 */
class Log
{
    const string OUTPUT_SCREEN = 'screen';
    const string OUTPUT_SYSTEM = 'system';
    const string OUTPUT_FILE = 'file';
    /**
     * Journald Native Protocol via UNIX Datagramm socket <br> Options:
     * - socketPath: File path of the journald socket, defaults to `/run/systemd/journal/socket`
     * - tag: 'syslog identifier' for use with `journalctl -t [tag]` defaults to the first available value of: <br> constant JOB_NAME, <br>Name of the Weblication, <br>POOL_LOG_$configurationName
     * @see LogJournald::sendLog() provides underlying functionality
     * @see self::writeJournald()
     */
    const string OUTPUT_JOURNALD = 'journald';
    const string OUTPUT_DAO = 'dao';
    const string OUTPUT_MAIL = 'mail';
    const int LEVEL_NONE = 0;
    const int LEVEL_FATAL = 1;
    const int LEVEL_ERROR = 2;
    const int LEVEL_WARN = 4;
    const int LEVEL_INFO = 8;
    const int LEVEL_NOTICE = 16;
    const int LEVEL_DEBUG = 32;
    const int LEVEL_UNTIL_ERROR = 3;
    const int LEVEL_UNTIL_WARN = 7;
    const int LEVEL_UNTIL_INFO = 15;
    const int LEVEL_UNTIL_NOTICE = 31;
    const int LEVEL_ALL = 63;

    private static array $TEXT_LEVEL = [
        1 => 'fatal',
        2 => 'error',
        4 => 'warn',
        8 => 'info',
        16 => 'notice',
        32 => 'debug',
    ];

    private static bool $dao_strip_tags = true;

    const string EXIT_LEVEL = 'exit';
    const string COMMON = 'common';
    const string SQL_LOG_NAME = 'ResultSetSql';
    const string AJAX_CALL_LOG = 'ajaxCallLog';

    /**
     * @var array facilities
     */
    private static array $facilities = [];

    private static bool $shutdownFunctionRegistered = false;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('Log is a static class. You cannot instantiate it.');
    }

    /**
     * Replaces all facilities for the named configuration. Facilities omitted from
     * the passed array are disabled, and an omitted exit level is reset to LEVEL_NONE.
     * Configurations with other names remain unchanged.
     *
     * Facility entities/properties for OUTPUT_SCREEN:
     * -level - defines the level (LEVEL_DEBUG, LEVEL_INFO, LEVEL_NOTICE, LEVEL_WARN, LEVEL_ERROR, LEVEL_FATAL) at which the message should be displayed
     * -withDate - shows the date with every line
     * -withLineBreak - make a line break after each message
     * -showLevelNameAtTheBeginning - prints the caption of the level (debug, info, notice, warn, error, fatal) at the beginning of the message
     * -stream - optionally routes all CLI screen output to a stream resource
     *
     * @param array $facilities complete set of facilities for this configuration
     * @param string $configurationName name of the configuration. Default is "common". You can have more configurations for different purposes.
     * @throws InvalidArgumentException if a facility configuration is invalid
     * @throws Exception if a configured facility cannot be initialized
     * @throws TypeError if a nested option has an unsupported type
     */
    public static function setup(array $facilities, string $configurationName = Log::COMMON): void
    {
        $configuration = [
            self::OUTPUT_SCREEN => self::normalizeFacilityConfiguration(self::OUTPUT_SCREEN, $facilities[self::OUTPUT_SCREEN] ?? null),
            self::OUTPUT_SYSTEM => self::normalizeFacilityConfiguration(self::OUTPUT_SYSTEM, $facilities[self::OUTPUT_SYSTEM] ?? null),
            self::OUTPUT_FILE => self::normalizeFacilityConfiguration(self::OUTPUT_FILE, $facilities[self::OUTPUT_FILE] ?? null),
            self::OUTPUT_JOURNALD => self::normalizeFacilityConfiguration(self::OUTPUT_JOURNALD, $facilities[self::OUTPUT_JOURNALD] ?? null),
            self::OUTPUT_MAIL => self::normalizeFacilityConfiguration(self::OUTPUT_MAIL, $facilities[self::OUTPUT_MAIL] ?? null),
            self::OUTPUT_DAO => self::normalizeFacilityConfiguration(self::OUTPUT_DAO, $facilities[self::OUTPUT_DAO] ?? null),
            self::EXIT_LEVEL => (int)($facilities[self::EXIT_LEVEL] ?? self::LEVEL_NONE),
        ];

        if (isset($facilities[self::OUTPUT_FILE])) {
            $logFile = new LogFile($configuration[self::OUTPUT_FILE]['file']);
            $logFile->setSeparator(' ');
            $configuration[self::OUTPUT_FILE]['LogFile'] = $logFile;
        }

        if (isset($facilities[self::OUTPUT_JOURNALD])) {
            $socketPath = $configuration[self::OUTPUT_JOURNALD]['socketPath'] ?? null;
            $configuration[self::OUTPUT_JOURNALD]['LogJournald'] = $socketPath === null
                ? new LogJournald()
                : new LogJournald($socketPath);
        }

        if (isset($facilities[self::OUTPUT_MAIL])) {
            $from = $configuration[self::OUTPUT_MAIL]['from'] ?? G7SYSTEM_DEFAULT_MAIL_ADDRESS;
            $to = $configuration[self::OUTPUT_MAIL]['to'];
            $subject = $configuration[self::OUTPUT_MAIL]['subject']
                ?? \pool\classes\Core\Http\Request::host().' '.Weblication::getInstance()->getName().' reports';

            $Mailer = new SendmailMailer();
            $MailMsg = new Message();
            $MailMsg->setFrom($from)->addTo($to)->setSubject($subject);

            $configuration[self::OUTPUT_MAIL]['Mailer'] = $Mailer;
            $configuration[self::OUTPUT_MAIL]['MailMsg'] = $MailMsg;
        }

        if (isset($facilities[self::OUTPUT_DAO])) {
            $DAO = $configuration[self::OUTPUT_DAO]['DAO'] ?? null;
            $tableDefine = $configuration[self::OUTPUT_DAO]['tableDefine'] ?? null;
            $host = $configuration[self::OUTPUT_DAO]['host'] ?? MYSQL_HOST;
            $charset = $configuration[self::OUTPUT_DAO]['charset'] ?? 'utf8';

            if ($tableDefine) {
                $databaseName = $tableDefine[0];
                try {
                    DataInterface::getInterfaceForResource($databaseName);
                } catch (InvalidArgumentException) {
                    DataInterface::createDataInterface([
                        'host' => $host,
                        'database' => $databaseName,
                        'charset' => $charset,
                    ]);
                }
                /** @var DAO\MySQL_DAO|string $table */
                $table = $tableDefine[1];
                $DAO = $table instanceof DAO\MySQL_DAO
                    ? $table::create(databaseName: $databaseName)
                    : DAO\MySQL_DAO::create($table, $databaseName);

                $DAO->fetchColumns();
            }

            $configuration[self::OUTPUT_DAO]['DAO'] = $DAO;
        }

        $previousConfiguration = self::$facilities[$configurationName] ?? null;
        self::$facilities[$configurationName] = $configuration;
        self::closeFileFacility($previousConfiguration);

        if (!self::$shutdownFunctionRegistered) {
            register_shutdown_function(static fn() => Log::close());
            self::$shutdownFunctionRegistered = true;
        }
    }

    /**
     * @param array<string, mixed>|bool|float|int|string|null $facilityConfiguration
     * @return array<string, mixed>
     */
    private static function normalizeFacilityConfiguration(string $output, array|bool|float|int|string|null $facilityConfiguration): array
    {
        if ($facilityConfiguration === null) return ['level' => self::LEVEL_NONE];
        if (!is_array($facilityConfiguration)) {
            if (!in_array($output, [self::OUTPUT_SCREEN, self::OUTPUT_SYSTEM, self::OUTPUT_JOURNALD], true)) {
                throw new InvalidArgumentException("Log facility '$output' requires an array configuration.");
            }
            return ['level' => (int)$facilityConfiguration];
        }
        if ($output === self::OUTPUT_FILE && (!is_string($facilityConfiguration['file'] ?? null) || $facilityConfiguration['file'] === '')) {
            throw new InvalidArgumentException("Log facility '$output' requires a non-empty string option 'file'.");
        }
        if ($output === self::OUTPUT_MAIL && (!is_string($facilityConfiguration['to'] ?? null) || $facilityConfiguration['to'] === '')) {
            throw new InvalidArgumentException("Log facility '$output' requires a non-empty string option 'to'.");
        }
        if ($output === self::OUTPUT_DAO) {
            $dao = $facilityConfiguration['DAO'] ?? null;
            $tableDefine = $facilityConfiguration['tableDefine'] ?? null;
            $validTableDefine = is_array($tableDefine) && isset($tableDefine[0], $tableDefine[1])
                && is_string($tableDefine[0]) && (is_string($tableDefine[1]) || $tableDefine[1] instanceof DAO\MySQL_DAO);
            if (!$dao instanceof DAO\MySQL_DAO && !$validTableDefine) {
                throw new InvalidArgumentException("Log facility '$output' requires a MySQL_DAO option 'DAO' or a valid 'tableDefine'.");
            }
        }
        foreach (['withDate', 'withLineBreak', 'showLevelNameAtTheBeginning'] as $option) {
            if (isset($facilityConfiguration[$option]) && is_scalar($facilityConfiguration[$option])) {
                $facilityConfiguration[$option] = (bool)$facilityConfiguration[$option];
            }
        }
        if (isset($facilityConfiguration['withExtra']) && is_scalar($facilityConfiguration['withExtra'])) {
            $facilityConfiguration['withExtra'] = (bool)$facilityConfiguration['withExtra'];
        }
        $facilityConfiguration['level'] = (int)($facilityConfiguration['level'] ?? self::LEVEL_NONE);
        return $facilityConfiguration;
    }

    /**
     * Closes the file handle owned by a replaced configuration.
     *
     * DAO data interfaces may be shared and remain open until Log::close().
     * Journald sockets are closed by LogJournald::__destruct().
     */
    private static function closeFileFacility(?array $configuration): void
    {
        $logFile = $configuration[self::OUTPUT_FILE]['LogFile'] ?? null;
        $logFile?->close();
    }

    /**
     * Returns the level of the corresponding output
     */
    private static function getLevel(string $configurationName, string $output): int
    {
        return self::$facilities[$configurationName][$output]['level'] ?? 0;
    }

    /**
     * Returns the exit level
     */
    private static function getExitLevel(string $configurationName): int
    {
        return self::$facilities[$configurationName][self::EXIT_LEVEL] ?? self::LEVEL_NONE;
    }

    /**
     * Returns whether the output with timestamp is requested
     */
    private static function screenWithDate(string $configurationName): bool
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['withDate'] ?? true;
    }

    /**
     * Returns whether the output with line breaks is requested
     */
    private static function screenWithLineBreak(string $configurationName): bool
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['withLineBreak'] ?? true;
    }

    private static function screenWithExtra(string $configurationName): array|bool
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['withExtra'] ?? false;
    }

    /**
     * @return resource|null
     */
    private static function screenStream(string $configurationName): mixed
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['stream'] ?? null;
    }

    private static function outputWithExtra(string $configurationName, string $output): array|bool
    {
        return self::$facilities[$configurationName][$output]['withExtra'] ?? false;
    }

    /**
     * Returns whether the output with the name of the level is requested
     */
    private static function showLevelNameAtTheBeginning(string $configurationName, string $output): bool
    {
        return self::$facilities[$configurationName][$output]['showLevelNameAtTheBeginning'] ?? true;
    }

    /**
     * Writes a debug message
     */
    public static function debug(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_DEBUG, $extra, $configurationName);
    }

    /**
     * Writes a notice message
     */
    public static function notice(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_NOTICE, $extra, $configurationName);
    }

    /**
     * Writes an info message
     */
    public static function info(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_INFO, $extra, $configurationName);
    }

    /**
     * Writes a warning message
     */
    public static function warn(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_WARN, $extra, $configurationName);
    }

    /**
     * Writes an error message
     */
    public static function error(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_ERROR, $extra, $configurationName);
    }

    /**
     * Writes a fatal error message
     */
    public static function fatal(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_FATAL, $extra, $configurationName);
    }

    /**
     * Write a message
     */
    public static function message(string $text, int $level = self::LEVEL_INFO, array $extra = [], string $configurationName = Log::COMMON): void
    {
        if (!self::isConfigurationAvailable($configurationName)) {
            $error_level = match ($level) {
                self::LEVEL_INFO, self::LEVEL_NOTICE => E_USER_NOTICE,
                self::LEVEL_WARN, self::LEVEL_ERROR, self::LEVEL_FATAL => E_USER_WARNING,
                default => null
            };
            if ($text === '' || !$error_level) return;
            if (IS_TESTSERVER) trigger_error($text, $error_level);
            return;
        }
        if (self::getLevel($configurationName, self::OUTPUT_SCREEN) & $level) {
            self::writeScreen($text, $level, $extra, $configurationName);
        }

        if (self::getLevel($configurationName, self::OUTPUT_FILE) & $level) {
            self::writeFile($text, $level, $extra, $configurationName);
        }

        if (self::getLevel($configurationName, self::OUTPUT_JOURNALD) & $level) {
            self::writeJournald($text, $level, $extra, $configurationName);
        }

        if (self::getLevel($configurationName, self::OUTPUT_MAIL) & $level) {
            self::writeMail($text, $level, $extra, $configurationName);
        }

        if (self::getLevel($configurationName, self::OUTPUT_DAO) & $level) {
            self::writeDAO($text, $level, $extra, $configurationName);
        }

        if ($level === self::getExitLevel($configurationName)) {
            exit(1);
        }
    }

    public static function writeScreen(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        // todo format
        $isHTML = isHTML($text);

        $message = $text;

        $withDate = self::screenWithDate($configurationName);
        $withLineBreak = self::screenWithLineBreak($configurationName);

        if (\pool\IS_CLI) {
            if ($isHTML) {
                // no html
                $message = str_replace(['&nbsp;', '<br>', '<hr>'], [' ', \pool\LINE_BREAK, str_repeat('-', 25)], $message);
                $message = strip_tags($message);
            }

            $isEmptyString = isEmptyString($message);
            if (!$isEmptyString) {
                if (self::showLevelNameAtTheBeginning($configurationName, Log::OUTPUT_SCREEN)) {
                    $message = ucfirst(self::$TEXT_LEVEL[$level]).': '.$message;
                }
            }

            [$message, $remainingExtra] = self::processExtra($configurationName, Log::OUTPUT_SCREEN, $extra, $message);
            $message = self::appendExtraToText($message, $remainingExtra);
            $message = ($withDate ? date('Y-m-d H:i:s').' | ' : '').$message;
            $message .= $withLineBreak ? \pool\LINE_BREAK : '';

            $stream = self::screenStream($configurationName) ?? ((self::LEVEL_ERROR & $level or self::LEVEL_FATAL & $level) ? \STDERR : \STDOUT);
            fwrite($stream, $message);
        } else {
            [$message, $remainingExtra] = self::processExtra($configurationName, Log::OUTPUT_SCREEN, $extra, $message);
            if ($remainingExtra) {
                $message .= '<pre>'.htmlspecialchars(self::formatExtra($remainingExtra, true), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
            }

            if ($isHTML) {
                $foundHeadline = preg_match_all('/<\/(h[1-6]+|p)>$/m', $message);
                if (!$foundHeadline) {
                    $message .= ($withLineBreak ? \pool\LINE_BREAK : '');
                }
                // todo insert displayLevelScreen? or not
            } else {
                $message .= ($withLineBreak ? \pool\LINE_BREAK : '');
            }
            $message = ($withDate ? date('Y-m-d H:i:s').' | ' : '').$message;

            echo $message;
        }
    }

    public static function writeFile(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = ucfirst(self::$TEXT_LEVEL[$level]).' '.$text;
        if (!empty($extra)) {
            $message .= ' '.json_encode($extra);
        }
        self::$facilities[$configurationName][self::OUTPUT_FILE]['LogFile']->addLine($message);
    }

    public static function writeJournald(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $facility = self::$facilities[$configurationName][self::OUTPUT_JOURNALD];
        /** @var LogJournald $journaldLogger */
        $journaldLogger = $facility['LogJournald'];
        $weblication = Weblication::hasInstance() ? Weblication::getInstance() : null;
        $jobname = defined('JOB_NAME') ? JOB_NAME : null;
        $jobname = is_string($jobname) ? $jobname : null;
        $tag = $facility['tag'] ?? $jobname ?? $weblication?->getName() ?? "POOL_LOG_$configurationName";
        $journaldLogger->sendLog($text, $level, $extra, $tag);
    }

    public static function writeMail(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = $text;
        [$message, $remainingExtra] = self::processExtra($configurationName, Log::OUTPUT_MAIL, $extra, $message);
        if ($remainingExtra) {
            $message .= "\n\nExtra:\n".self::formatExtra($remainingExtra, true);
        }
        /** @var Nette\Mail\Message $MailMsg */
        $MailMsg = self::$facilities[$configurationName][self::OUTPUT_MAIL]['MailMsg'];
        $MailMsg->setSubject(str_replace('{LogLevel}', ucfirst(self::$TEXT_LEVEL[$level]), $MailMsg->getSubject()));
        $MailMsg->setBody($message);
        self::$facilities[$configurationName][self::OUTPUT_MAIL]['Mailer']->send($MailMsg);
    }

    public static function writeDAO(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = $text;
        /** @var DAO\MySQL_DAO $DAO */
        $DAO = self::$facilities[$configurationName][self::OUTPUT_DAO]['DAO'];
        if (self::$dao_strip_tags && isHTML($message)) {
            // no html
            $message = trim(str_replace(['&nbsp;', '<br>', '<hr>'], [' ', chr(10), ''], $message));
            $message = strip_tags($message);
        }
        $Data = new Input();
        $Data->setData(
            [
                'message' => substr($message, 0, 2048),
                'level' => self::$TEXT_LEVEL[$level],
            ] + $extra,
        );
        $Data = $Data->filter($DAO->getColumns());
        $DAO->insert($Data->getData());
    }

    /**
     * close resource / file handles
     */
    public static function close(): void
    {
        foreach (self::$facilities as $facility) {
            if (isset($facility[self::OUTPUT_FILE]['LogFile']))
                $facility[self::OUTPUT_FILE]['LogFile']->close();
            if (isset($facility[self::OUTPUT_DAO]['DAO']))
                $facility[self::OUTPUT_DAO]['DAO']->getDataInterface()->close();
        }
    }

    /**
     * Creates a file, by default in the systems temp directory and writes the passed details into the File
     *
     * @param string $details the Details to save
     * @param null|string $directory optional directory to save the File in
     * @return string the path to the File created
     * @throws Exception
     */
    public static function makeDetailsFile(string $details, ?string $directory = null): string
    {
        $directory ??= buildDirPath(sys_get_temp_dir(), 'error-details');
        do {
            $file = buildFilePath(
                $directory,
                base64_encode(random_bytes(8)),
            );
        } while (file_exists($file));
        file_put_contents($file, $details);
        return realpath($file);
    }

    private static function processExtra(string $configurationName, string $output, array $extra, string $message): array
    {
        $withExtra = self::outputWithExtra($configurationName, $output);
        if (!$withExtra && $output !== Log::OUTPUT_SCREEN) {
            $withExtra = self::screenWithExtra($configurationName);// backward compatibility: non-screen outputs historically reused OUTPUT_SCREEN.withExtra.
        }
        if (!$withExtra || !$extra) {
            return [$message, []];
        }

        $placeholders = [];
        $remainingExtra = $extra;
        foreach ($extra as $key => $value) {
            $value = self::formatPlaceholderValue($value);
            $foundPlaceholder = false;
            // {{key}} also matches the legacy {key} pattern; strtr() applies the longest matching key first.
            foreach (['{{'.$key.'}}', '{'.$key.'}'] as $placeholder) {
                if (!str_contains($message, $placeholder)) continue;

                $placeholders[$placeholder] = $value;
                $foundPlaceholder = true;
            }
            if (!$foundPlaceholder) continue;

            unset($remainingExtra[$key]);
        }

        if ($placeholders) {
            $message = strtr($message, $placeholders);
        }

        return [$message, $remainingExtra];
    }

    private static function appendExtraToText(string $message, array $extra): string
    {
        if (!$extra) return $message;

        $formattedExtra = self::formatExtra($extra);
        if (isEmptyString($message)) return "extra: $formattedExtra";

        return "$message | extra: $formattedExtra";
    }

    private static function formatPlaceholderValue(mixed $value): string
    {
        if ($value === null) return '';
        if (is_bool($value)) return $value ? '1' : '';
        if (is_scalar($value)) return (string)$value;

        return self::formatExtra($value);
    }

    private static function formatExtra(mixed $extra, bool $pretty = false): string
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR;
        if ($pretty) $flags |= JSON_PRETTY_PRINT;

        $formatted = json_encode($extra, $flags);
        return $formatted === false ? '[unencodable extra]' : $formatted;
    }

    private static function isConfigurationAvailable(string $configurationName): bool
    {
        return isset(self::$facilities[$configurationName]);
    }
}
