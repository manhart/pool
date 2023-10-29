<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\utils;

final class Network
{
    /**
     * Ping a host by using the system ping command
     *
     * @param string $host Hostname or IP address
     * @param int $count Number of pings
     * @param bool $detailed If true, the output and result will be returned as array
     * @return array|bool Returns an array with detailed information or a boolean value
     */
    public static function ping(string $host, int $count = 4, bool $detailed = false): array|bool
    {
        // remove port from host
        $parsedUrl = \parse_url($host);
        if(isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];
        }
        elseif(isset($parsedUrl['path'])) {
            $host = $parsedUrl['path'];
        }

        // check if host is IPv6
        $isIPv6 = str_contains($host, ':');
        $ipv6Option = $isIPv6 ? '-6' : '';

        // shell secure arguments
        $host = \escapeshellarg($host);

        // Ausführen des Befehls
        $output = [];
        $result = null;
        \exec("ping $ipv6Option -c $count $host", $output, $result);

        if($detailed) {
            return [
                'output' => $output,
                'result' => $result,
            ];
        }

        return $result === 0;
    }

    /**
     * Ping a host by using ICMP packets. Attention: needs usually root privileges.
     */
    public static function pingUsingICMP(string $host, int $timeoutMillis = 1000): float|false
    {
        $package = "\x08\x00\x19\x2f\x00\x00\x00\x00\x70\x69\x6e\x67";

        /* create the socket, the last '1' denotes ICMP */
        $socket = \socket_create(\AF_INET, \SOCK_RAW, \getprotobyname('ICMP'));
        if(!\is_resource($socket)) {
            return false;
        }

        /* set socket receive timeout to 1 second */
        $sec = \floor($timeoutMillis / 1000);
        \socket_set_option($socket, \SOL_SOCKET, \SO_RCVTIMEO, ['sec' => $sec, 'usec' => $timeoutMillis - ($sec * 1000) * 1000]);

        /* connect to socket */
        if(!@\socket_connect($socket, $host)) {
            @\socket_close($socket);
            return false;
        }

        $result = false;
        /* record start time */
        $start_time = \getMicrotime();
        \socket_send($socket, $package, strlen($package), 0);
        if(@\socket_read($socket, 255)) {
            $end_time = \getMicrotime();
            $total_time = $end_time - $start_time;
            $result = $total_time;
        }
        \socket_close($socket);

        return $result;
    }

    /**
     * Check if a port is open
     *
     * @param string $host Hostname or IP address
     * @param int $port Port number
     * @param int $timeout Timeout in seconds
     * @return bool
     */
    public static function isPortReachable(string $host, int $port, int $timeout = 1): bool
    {
        $parsedUrl = \parse_url($host);
        if(isset($parsedUrl['host'])) {
            $host = $parsedUrl['host'];
        }
        elseif(isset($parsedUrl['path'])) {
            $host = $parsedUrl['path'];
        }

        $fp = @\fsockopen($host, $port, $errno, $error, $timeout);
        if(!$fp) {
            return false;
        }
        \fclose($fp);
        return true;
    }
}