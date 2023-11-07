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

        $ch = \curl_init($url);
        \curl_setopt($ch, \CURLOPT_NOBODY, true); // Check headers only, no body
        \curl_setopt($ch, \CURLOPT_FAILONERROR, true); // Returns false at 4xx status
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true); // No direct output
        \curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);

        \curl_exec($ch);
        $isValid = \curl_getinfo($ch, \CURLINFO_HTTP_CODE) === 200;

        \curl_close($ch);

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
        $ch = \curl_init($url);
        $fp = \fopen($destination, 'wb+');

        if ($fp === false) {
            throw new \RuntimeException("Cannot open file \"$destination\" for writing.");
        }

        \curl_setopt($ch, \CURLOPT_FILE, $fp);
        \curl_setopt($ch, \CURLOPT_TIMEOUT, $timeout);
        \curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, $followLocation); // Bei Redirects folgen

        $result = \curl_exec($ch);

        if (\curl_errno($ch)) {
            $error_msg = \curl_error($ch);
        }

        \fclose($fp);
        \curl_close($ch);

        if (isset($error_msg)) {
            throw new \RuntimeException("Error while downloading file: $error_msg");
        }

        return $result;
    }
}