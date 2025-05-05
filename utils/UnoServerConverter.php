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

namespace pool\utils;

use pool\classes\Exception\FileOperationException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use pool\classes\Exception\ServiceUnavailableException;

/**
 * Note: This class needs the container libreoffice-unoserver to be running.
 * @link https://github.com/libreofficedocker/libreoffice-unoserver
 */
final class UnoServerConverter
{
    private string $serverUrl;

    private const SUPPORTED_OUTPUT_FORMATS = ['pdf', 'txt', 'csv'];

    /**
     * @param string $serverUrl The base URL of the unoserver instance, e.g., 'http://localhost:2004'.
     * @throws InvalidArgumentException If the URL is invalid.
     */
    public function __construct(string $serverUrl)
    {
        if (!filter_var($serverUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("Invalid unoserver URL: $serverUrl");
        }
        $this->serverUrl = rtrim($serverUrl, '/');

        if (!$this->isAlive()) {
            throw new ServiceUnavailableException("Unoserver is not alive at $serverUrl");
        }
    }

    /**
     * Checks if the unoserver is alive.
     */
    public function isAlive(): bool
    {
        return Curl::isServiceAlive($this->serverUrl);
    }

    /**
     * Converts a file to the specified format using unoserver.
     *
     * Curl example usage:
     * curl -s -v -X POST --url http://127.0.0.1:2004/request --header 'Content-Type: multipart/form-data' --form "file=@/path/file.doc" --form 'convert-to=pdf' --output '/path/file.pdf'
     *
     * @param string $filePath The path to the source file to be converted.
     * @param string $outputFormat The desired output format (e.g., 'pdf', 'txt', 'csv').
     * @return string The converted file content as a string.
     * @throws \JsonException|RuntimeException|InvalidArgumentException
     */
    public function convert(string $filePath, string $outputFormat): string
    {
        if(!in_array($outputFormat, self::SUPPORTED_OUTPUT_FORMATS)) {
            throw new InvalidArgumentException("Unsupported output format: $outputFormat");
        }

        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("File does not exist or is not readable: $filePath");
        }

        $url = "$this->serverUrl/request";
        $options = [CURLOPT_HTTPHEADER => ['Accept: */*']];
        $data = [
            'file' => new \CURLFile($filePath),
            'convert-to' => $outputFormat
        ];

        $response = Curl::post($url,$data, $options, 'multipart/form-data');
        $statusCode = $response['statusCode'];
        if ($statusCode !== 200) {
            throw new RuntimeException("Conversion failed with status code $statusCode and response: {$response['body']}");
        }

        return $response['body'];
    }

    /**
     * Converts a file and saves the result to a specified path.
     *
     * @param string $filePath The path to the source file to be converted.
     * @param string $outputPath The path where the converted file should be saved.
     * @param string $outputFormat The desired output format (e.g., 'pdf', 'txt', 'csv').
     * @throws \JsonException|FileOperationException|RuntimeException|InvalidArgumentException
     */
    public function convertToFile(string $filePath, string $outputPath, string $outputFormat): void
    {
        $convertedContent = $this->convert($filePath, $outputFormat);

        if (file_put_contents($outputPath, $convertedContent) === false) {
            throw new FileOperationException("Failed to write converted file to $outputPath");
        }
    }
}