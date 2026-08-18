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

namespace pool\classes;
use Log;
use pool\classes\Core\PoolObject;
use pool\classes\Exception\FileOperationException;
use pool\classes\Exception\RuntimeException;
use Socket;

class LogJournald extends PoolObject
{
    public final const int SYSLOG_LEVEL_DEBUG = 7;
    public final const int SYSLOG_LEVEL_INFO = 6;
    public final const int SYSLOG_LEVEL_NOTICE = 5;
    public final const int SYSLOG_LEVEL_WARNING = 4;
    public final const int SYSLOG_LEVEL_ERROR = 3;
    public final const int SYSLOG_LEVEL_CRITICAL = 2;
    public final const int SYSLOG_LEVEL_ALERT = 1;
    public final const int SYSLOG_LEVEL_EMERGENCY = 0;

    /** @var array<int, int> $syslogPriorityMap https://en.wikipedia.org/wiki/Syslog#Severity_level */
    private array $syslogPriorityMap = [
        Log::LEVEL_DEBUG => self::SYSLOG_LEVEL_DEBUG,
        Log::LEVEL_INFO => self::SYSLOG_LEVEL_INFO,
        Log::LEVEL_WARN => self::SYSLOG_LEVEL_WARNING,
        Log::LEVEL_ERROR => self::SYSLOG_LEVEL_ERROR,
        Log::LEVEL_FATAL => self::SYSLOG_LEVEL_ALERT,
    ];
    /** sending socket for transferring log data */
    private readonly Socket $sendSocket;

    public function __construct(private readonly string $socketPath = '/run/systemd/journal/socket')
    {
        $this->sendSocket = socket_create(AF_UNIX, SOCK_DGRAM, 0) ?: throw new RuntimeException("Journal Log unable to create outbound socket");
    }

    public function __destruct() {
        socket_close($this->sendSocket);
    }

    /**
     * Send the log data to Journald socket configured during instantiation.
     * @param string|null $message message as to be displayed in journalctl. To avoid data-loss a given message will be added the message regardless of any message already declared in $extra. It's advisable to avoid passing messages in both places at once as something like ```journalctl -o verbose --output-fields=MESSAGE,PRIORITY``` will then be needed to read the earlier messages from \$extra. <br> Backed by field 'MESSAGE'
     * @param int $level POOL log level, will be ignored if $extra already declares a priority. Can be used as a filter for minimum severity like `journalctl -p [syslog level]`. <br> Backed by field 'PRIORITY'
     * @param array<string, mixed>|array<int, array{0:string, 1:mixed}> $extra structured log data, as KV Tuple list ([[k,v],...]) or KV array ([k=>v,...]). <br> For typical values and standard keys see https://www.freedesktop.org/software/systemd/man/latest/systemd.journal-fields.html
     * @param string|null $tag tag for quickly finding the logs using a tag filter like `journalctl -t [tag]`, as more than one tag is possible this will add the tag regardless of any tags already declared in $extra. <br> Backed by field 'SYSLOG_IDENTIFIER'
     * @see self::$syslogPriorityMap for how POOL log levels are converted to syslog severity levels
     * @see self::sendLogRaw() for a static sending interface without additional field mapping, which is backing this function.
     * @throws FileOperationException
     * @throws RuntimeException
     */
    public function sendLog(?string $message, int $level, array $extra = [], ?string $tag = null): void {
        $syslogPriorityList = range(self::SYSLOG_LEVEL_EMERGENCY, self::SYSLOG_LEVEL_DEBUG);
        $syslogPriorityList = array_merge($syslogPriorityList, array_map(strval(...), $syslogPriorityList));
        $dataHasDefinedPriority = 0;
        $data = [];
        foreach ($extra as $key => $value){
            if (is_int($key) && is_array($value)){//tuple structure
                if (count($value) !== 2) continue;
                $fieldName = $value[0];
                $fieldValue = $value[1];
            } elseif (is_string($key)){//key-value structure
                $fieldName = $key;
                $fieldValue = $value;
            } else {//nonsense input
                continue;
            }
            unset($key, $value);
            $isDefiningPriority = $fieldName === 'PRIORITY';
            $hasValidPriority = in_array($fieldValue, $syslogPriorityList, true);
            if ($isDefiningPriority){
                if (!$hasValidPriority) continue;//ignore bad value
                $dataHasDefinedPriority += 1;
            }
            $data[] = [$fieldName, $fieldValue];
        }
        $priority = $this->syslogPriorityMap[$level] ?? null;
        if ($priority !== null && !$dataHasDefinedPriority) $data[] = ['PRIORITY', $priority];
        if (is_string($message) && $message !== '') $data[] = ['MESSAGE', $message];
        if (is_string($tag) && $tag !== '') $data[] = ['SYSLOG_IDENTIFIER', $tag];
        self::sendLogRaw($this->sendSocket, $data, $this->socketPath);
    }

    /**
     * Transfers a data payload to journald using its native protocol. Transport happens via a Datagramm UNIX socket.
     * @param array<int, array{0:string, 1:mixed}> $data a list of [key, value] tuples, wrong size tuples or those with non string keys will be ignored, values are casted to string where possible otherwise print_r is used
     * @see socket_set_option() socket_set_option($socket, SOL_SOCKET, SO_SNDBUF, $size) to control kernel send buffer size
     * @see socket_set_nonblock() to error instead of stalling in case the kernel buffer fills up
     * @see socket_create() socket_create(AF_UNIX, SOCK_DGRAM, 0)
     * @throws FileOperationException
     * @throws RuntimeException
     */
    static function sendLogRaw(Socket $socket, array $data, string $socketPath = '/run/systemd/journal/socket'): void {
        $payload = '';
        //https://systemd.io/JOURNAL_NATIVE_PROTOCOL/
        foreach ($data as $datum) {
            if (!is_array($datum) || count($datum) !== 2) continue;
            [$logField, $rawLogDatum] = $datum;
            if (!is_string($logField)) continue;
            $isLogDatumStringable = is_string($rawLogDatum) || $rawLogDatum instanceof \Stringable;
            $logDatum = $isLogDatumStringable ? (string)$rawLogDatum : print_r($rawLogDatum, true);
            if (str_contains($logDatum, "\n")) {
                $len = strlen($logDatum);
                $lenBin = pack('Vxxxx', $len);
                $payload .= "$logField\n$lenBin$logDatum\n";
            } else {
                $payload .= "$logField=$logDatum\n";
            }
        }
        //https://gitlab.gnome.org/GNOME/glib/-/blob/f1457694acfe831478ed3c35f263b0662962225c/glib/gmessages.c#L2481
        $socketData = [
            "name" => ["path" => $socketPath],
            'iov' => [$payload],
        ];
        $res = @socket_sendmsg($socket, $socketData);
        if ($res === false) {
            $isPayloadTooLarge = socket_last_error($socket) === 90;
            if ($isPayloadTooLarge) {//use file descriptor fallback
                $socketData['iov'] = [];
                $fd = tmpfile() ?: throw new FileOperationException("Journal log was unable to open temp file.");
                @unlink(stream_get_meta_data($fd)['uri']);//remove useless file, we just need an open file descriptor and php has no method for requesting a memfd from the kernel
                @fwrite($fd, $payload) ?: throw new FileOperationException("Journal log was unable to write temp file.");
                $filePass = [[
                    'level' => SOL_SOCKET,
                    'type' => SCM_RIGHTS,
                    'data' => [$fd],
                ]];
                $socketData['control'] = $filePass;
                $res = @socket_sendmsg($socket, $socketData);
            }
        }
        if ($res === false) {
            $errorCode = socket_last_error($socket);
            $errorText = socket_strerror($errorCode);
            throw new RuntimeException("Journal log was unable to transmit payload via socket '$socketPath': $errorText ($errorCode)");
        }

    }
}
