<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use pool\classes\Core\Weblication;
use pool\classes\Database\DAO;
use pool\classes\Database\DataInterface;

/**
 * Class Log
 * @package pool\classes\Utils
 * @since 2022-01-18
 */
class Log
{
    const OUTPUT_SCREEN = 'screen';
    const OUTPUT_SYSTEM = 'system';
    const OUTPUT_FILE = 'file';
    const OUTPUT_DAO = 'dao';
    const OUTPUT_MAIL = 'mail';
    const LEVEL_NONE = 0;
    const LEVEL_FATAL = 1;
    const LEVEL_ERROR = 2;
    const LEVEL_WARN = 4;
    const LEVEL_INFO = 8;
    const LEVEL_DEBUG = 16;
    const LEVEL_UNTIL_ERROR = 3;
    const LEVEL_UNTIL_WARN = 7;
    const LEVEL_UNTIL_INFO = 15;
    const LEVEL_ALL = 31;

    private static array $TEXT_LEVEL = [
        1 => 'fatal',
        2 => 'error',
        4 => 'warn',
        8 => 'info',
        16 => 'debug'
    ];

    private static bool $dao_strip_tags = true;

    const EXIT_LEVEL = 'exit';
    const COMMON = 'common';
    const SQL_LOG_NAME = 'ResultSetSql';

    /**
     * @var array facilities
     */
    private static array $facilities = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('Log is a static class. You cannot instantiate it.');
    }

    /**
     * Facility entities/properties for OUTPUT_SCREEN:
     * -level - defines the level (LEVEL_DEBUG, LEVEL_INFO, LEVEL_WARN, LEVEL_ERROR, LEVEL_FATAL) at which the message should be displayed
     * -withDate - shows the date with every line
     * -withLineBreak - make a line break after each message
     * -showLevelNameAtTheBeginning - prints the caption of the level (debug, info, warn, error, fatal) at the beginning of the message
     *
     * @param string $configurationName name of the configuration. Default is "common". You can have more configurations for different purposes.
     * @param array $facilities
     */
    public static function setup(array $facilities, string $configurationName = Log::COMMON): void
    {
        if(!defined('IS_CONSOLE')) define('IS_CONSOLE', php_sapi_name() == 'cli');
        if(!defined('LINE_BREAK')) define('LINE_BREAK', (IS_CONSOLE) ? chr(10) : '<br>');

        $level = self::$facilities[$configurationName][self::OUTPUT_SCREEN]['level'] ?? 0;
        if(isset($facilities[self::OUTPUT_SCREEN])) {
            $facility = $facilities[self::OUTPUT_SCREEN];
            if(is_array($facility)) {
                $level = $facility['level'] ?? 0;
            }
            else {
                $level = $facility;
                $facilities[self::OUTPUT_SCREEN] = [];
            }
        }
        $facilities[self::OUTPUT_SCREEN]['level'] = (int)$level;

        $level = 0;
        if(isset($facilities[self::OUTPUT_SYSTEM])) {
            $facility = $facilities[self::OUTPUT_SYSTEM];
            if(is_array($facility)) {
                $level = $facility['level'] ?? 0;
            }
            else {
                $level = $facility;
            }
        }
        $facilities[self::OUTPUT_SYSTEM]['level'] = (int)$level;

        $level = self::$facilities[$configurationName][self::OUTPUT_FILE]['level'] ?? 0;
        if(isset($facilities[self::OUTPUT_FILE])) {
            $facility = $facilities[self::OUTPUT_FILE];
            if(is_array($facility)) {
                $level = $facility['level'] ?? 0;
                $file = $facility['file'] ?? '';

                $LogFile = new LogFile();
                $LogFile->setSeparator(' ');
                $LogFile->open($file);

                $facilities[self::OUTPUT_FILE]['LogFile'] = $LogFile;
            }
            else {
                $level = $facility;
            }
        }
        $facilities[self::OUTPUT_FILE]['level'] = (int)$level;


        $level = self::$facilities[$configurationName][self::OUTPUT_MAIL]['level'] ?? 0;
        if(isset($facilities[self::OUTPUT_MAIL])) {
            $facility = $facilities[self::OUTPUT_MAIL];
            if(is_array($facility)) {
                $level = $facility['level'] ?? 0;
                $from = $facility['from'] ?? G7SYSTEM_DEFAULT_MAIL_ADDRESS;
                $to = $facility['to'] ?? '';
                $subject = $facility['subject'] ?? $_SERVER['SERVER_NAME'] . ' ' . Weblication::getInstance()->getName() . ' reports';

                $Mailer = new SendmailMailer();
                $MailMsg = new Message();
                $MailMsg->setFrom($from)->addTo($to)->setSubject($subject);

                $facilities[self::OUTPUT_MAIL]['Mailer'] = $Mailer;
                $facilities[self::OUTPUT_MAIL]['MailMsg'] = $MailMsg;
            }
            else {
                $level = $facility;
            }
        }
        $facilities[self::OUTPUT_MAIL]['level'] = (int)$level;

        $level = self::$facilities[$configurationName][self::OUTPUT_DAO]['level'] ?? 0;
        if(isset($facilities[self::OUTPUT_DAO])) {
            $facility = $facilities[self::OUTPUT_DAO];
            if(is_array($facility)) {
                $level = $facility['level'] ?? 0;
                $DAO = $facility['DAO'] ?? null;
                $tableDefine = $facility['tableDefine'] ?? '';
                $host = $facility['host'] ?? MYSQL_HOST;
                $charset = $facility['charset'] ?? 'utf8';

                if($tableDefine) {
                    $interfaceType = $dbname = $table = '';
                    DAO::extractTabledefine($tableDefine, $interfaceType, $dbname, $table);

                    $Packet = array(
                        'host' => $host,
                        'database' => $dbname,
                        'charset' => $charset
                    );
                    $Db = DataInterface::createDataInterface($Packet, $interfaceType);
                    $DAO = DAO::createDAO($tableDefine, $Db);
                    $DAO->fetchColumns();
                }

                $facilities[self::OUTPUT_DAO]['DAO'] = $DAO;
            }
            else {
                $level = $facility;
            }
        }
        $facilities[self::OUTPUT_DAO]['level'] = (int)$level;

        if(!isset($facilities[self::EXIT_LEVEL])) {
            $facilities[self::EXIT_LEVEL] = self::$facilities[$configurationName][self::EXIT_LEVEL] ?? self::LEVEL_NONE;
        }

        self::$facilities[$configurationName] = $facilities;
    }

    /**
     * returns the level of the corresponding output
     *
     * @param string $configurationName
     * @param string $output
     * @return int
     */
    private static function getLevel(string $configurationName, string $output): int
    {
        return self::$facilities[$configurationName][$output]['level'] ?? 0;
    }

    /**
     * returns the exit level
     *
     * @param string $configurationName
     * @return int
     */
    private static function getExitLevel(string $configurationName): int
    {
        return self::$facilities[$configurationName][self::EXIT_LEVEL] ?? self::LEVEL_NONE;
    }

    /**
     * returns whether the output with timestamp is requested
     *
     * @param string $configurationName
     * @return bool
     */
    private static function screenWithDate(string $configurationName): bool
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['withDate'] ?? true;
    }

    /**
     * returns whether the output with line breaks is requested
     *
     * @param string $configurationName
     * @return bool
     */
    private static function screenWithLineBreak(string $configurationName): bool
    {
        return self::$facilities[$configurationName][Log::OUTPUT_SCREEN]['withLineBreak'] ?? true;
    }

    /**
     * returns whether the output with the name of the level is requested
     *
     * @param string $configurationName
     * @param string $output
     * @return bool
     */
    private static function showLevelNameAtTheBeginning(string $configurationName, string $output): bool
    {
        return self::$facilities[$configurationName][$output]['showLevelNameAtTheBeginning'] ?? true;
    }

    /**
     * writes debug message
     *
     * @param string $text
     * @param array $extra
     * @param string $configurationName
     * @return void
     */
    public static function debug(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_DEBUG, $extra, $configurationName);
    }

    /**
     * writes info message
     *
     * @param string $text
     * @param array $extra
     * @param string $configurationName
     * @return void
     */
    public static function info(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_INFO, $extra, $configurationName);
    }

    /**
     * writes warning message
     *
     * @param string $text
     * @param array $extra
     * @param string $configurationName
     * @return void
     */
    public static function warn(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_WARN, $extra, $configurationName);
    }

    /**
     * writes an error message
     *
     * @param string $text
     * @param array $extra
     * @param string $configurationName
     * @return void
     */
    public static function error(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_ERROR, $extra, $configurationName);
    }

    /**
     * writes a fatal error message
     *
     * @param string $text
     * @param array $extra
     * @param string $configurationName
     * @return void
     */
    public static function fatal(string $text, array $extra = [], string $configurationName = Log::COMMON): void
    {
        self::message($text, self::LEVEL_FATAL, $extra, $configurationName);
    }

    /**
     * write message
     *
     * @param string $text
     * @param int $level
     * @param array $extra
     * @param string $configurationName
     */
    public static function message(string $text, int $level = self::LEVEL_INFO, array $extra = [], string $configurationName = Log::COMMON): void
    {
        if(self::getLevel($configurationName, self::OUTPUT_SCREEN) & $level) {
            self::writeScreen($text, $level, $extra, $configurationName);
        }

        if(self::getLevel($configurationName, self::OUTPUT_FILE) & $level) {
            self::writeFile($text, $level, $extra, $configurationName);
        }

        if(self::getLevel($configurationName, self::OUTPUT_MAIL) & $level) {
            self::writeMail($text, $level, $extra, $configurationName);
        }

        if(self::getLevel($configurationName, self::OUTPUT_DAO) & $level) {
            self::writeDAO($text, $level, $extra, $configurationName);
        }

        if($level == self::getExitLevel($configurationName)) {
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

        if(IS_CONSOLE) {

            if($isHTML) {
                // no html
                $message = str_replace(['&nbsp;', '<br>', '<hr>'], [' ', LINE_BREAK, str_repeat('-', 25)], $message);
                $message = strip_tags($message);
            }

            $isEmptyString = isEmptyString($message);
            if(!$isEmptyString) {
                if(self::showLevelNameAtTheBeginning($configurationName, Log::OUTPUT_SCREEN)) {
                    $message = ucfirst(self::$TEXT_LEVEL[$level]) . ': ' . $message;
                }
            }

            $message = ($withDate ? date('Y-m-d H:i:s') . ' | ' : '') . $message;
            $message .= $withLineBreak ? LINE_BREAK : '';

            if(self::LEVEL_ERROR & $level or self::LEVEL_FATAL & $level) {
                $stderr = fopen('php://stderr', 'w');
                fwrite($stderr, $message);
                fclose($stderr);
            }
            else {
                $stdout = fopen('php://stdout', 'w');
                fwrite($stdout, $message);
                fclose($stdout);
            }
        }
        else {

            if($isHTML) {
                $foundHeadline = preg_match_all('/<\/(h[1-6]+|p)>$/m', $message);
                if(!$foundHeadline) {
                    $message .= ($withLineBreak ? LINE_BREAK : '');
                }
                // todo insert displayLevelScreen? or not
            }
            else {
                $message .= ($withLineBreak ? LINE_BREAK : '');
            }
            $message = ($withDate ? date('Y-m-d H:i:s') . ' | ' : '') . $message;

            echo $message;
        }
    }

    public static function writeFile(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = $text;
        self::$facilities[$configurationName][self::OUTPUT_FILE]['LogFile']->addLine($message);
    }

    public static function writeMail(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = $text;
        $MailMsg = self::$facilities[$configurationName][self::OUTPUT_MAIL]['MailMsg'];
        $MailMsg->setBody($message);
        self::$facilities[$configurationName][self::OUTPUT_MAIL]['Mailer']->send($MailMsg);
    }

    public static function writeDAO(string $text, int $level, array $extra = [], string $configurationName = Log::COMMON): void
    {
        $message = $text;
        $DAO = self::$facilities[$configurationName][self::OUTPUT_DAO]['DAO'];
        if(self::$dao_strip_tags and isHTML($message)) {
            // no html
            $message = trim(str_replace(['&nbsp;', '<br>', '<hr>'], [' ', chr(10), ''], $message));
            $message = strip_tags($message);
        }
        $Data = new Input();
        $Data->setData([
                'message' => substr($message, 0, 2048),
                'level' => self::$TEXT_LEVEL[$level]
            ] + $extra);
        $Data = $Data->filter($DAO->getFieldlist());
        $DAO->insert($Data->getData());
    }

    /**
     * close resource / file handles
     *
     * @return void
     */
    public static function close(): void
    {
        foreach(self::$facilities as $facility) {
            if(isset($facility[self::OUTPUT_FILE]['LogFile']))
                $facility[self::OUTPUT_FILE]['LogFile']->close();
            if(isset($facility[self::OUTPUT_DAO]['DAO']))
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
            $file = buildFilePath($directory,
                base64_encode(random_bytes(8)));
        } while(file_exists($file));
        file_put_contents($file, $details);
        return realpath($file);
    }
}