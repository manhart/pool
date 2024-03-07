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

use pool\classes\Exception\RuntimeException;
use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;

final class Curl
{
    /**
     * Check if a given URL is valid by ensuring it is a well-formed URL and that it returns a 200 HTTP response code.
     *
     * @param string $url The URL to validate.
     * @param int $timeout The timeout value for the HTTP request in seconds. Default is 5 seconds.
     * @return bool Returns true if the URL is valid, false otherwise.
     */
    public static function isValidUrl(string $url, int $timeout = 5): bool
    {
        if(\filter_var($url, \FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $ch = curl_init($url);
        curl_setopt($ch, \CURLOPT_NOBODY, true); // Check headers only, no body
        curl_setopt($ch, \CURLOPT_FAILONERROR, true); // Returns false at 4xx status
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true); // No direct output
        curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);

        curl_exec($ch);
        $isValid = \curl_getinfo($ch, \CURLINFO_HTTP_CODE) === 200;
        curl_close($ch);

        return $isValid;
    }

    /**
     * Downloads a file from a given URL and saves it to a specified destination.
     *
     * @param string $url The URL of the file to be downloaded.
     * @param string $destination The path to save the downloaded file.
     * @param int $timeout The maximum time in seconds allowed for the download to complete. Default is 50.
     * @param bool $followLocation Whether to follow redirects. Default is true.
     * @return bool True if the file was downloaded successfully, false otherwise.
     * @throws \RuntimeException If there is an error opening the file for writing or if there is an error during the download.
     */
    public static function downloadFile(string $url, string $destination, int $timeout = 50, bool $followLocation = true): bool
    {
        $ch = curl_init($url);
        $fp = \fopen($destination, 'wb+');

        if ($fp === false) {
            throw new RuntimeException("Cannot open file \"$destination\" for writing.");
        }

        curl_setopt($ch, \CURLOPT_FILE, $fp);
        curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, $followLocation); // Bei Redirects folgen

        $result = curl_exec($ch);
        $error_msg = curl_errno($ch) ? curl_error($ch). ' (Error code: '.curl_errno($ch).')' : null;
        curl_close($ch);
        \fclose($fp);

        return !$error_msg ? $result : throw new RuntimeException("Error while downloading file: $error_msg");
    }

    /**
     * Post Request
     */
    public static function post(string $url, array $data, array $options = []): string
    {
        $query = \http_build_query($data);
        $curl = curl_init($url);

        $options[\CURLOPT_POST] = true;
        $options[\CURLOPT_POSTFIELDS] = $query;
        $options[\CURLOPT_RETURNTRANSFER] = true;
        $options[\CURLOPT_HTTPHEADER] ??= [
            'Content-Type: application/x-www-form-urlencoded',
            'Content-Length: '.\strlen($query)
        ];
        $options[\CURLOPT_SSL_VERIFYPEER] ??= true;
        $options[\CURLOPT_SSL_VERIFYHOST] ??= 2;
        curl_setopt_array($curl, $options);
        $response = curl_exec($curl);
        $error_msg = curl_errno($curl) ? curl_error($curl). ' (Error code: '.curl_errno($curl).')' : null;
        curl_close($curl);

        return !$error_msg ? $response : throw new RuntimeException("Error while posting data: $error_msg");
    }
}