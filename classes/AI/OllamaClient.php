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

namespace pool\classes\AI;

use JsonException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;
use pool\utils\Curl;

use function array_replace;
use function filter_var;
use function is_string;
use function rtrim;
use function trim;

use const FILTER_VALIDATE_URL;

final readonly class OllamaClient
{
    private string $baseUrl;

    private string $model;

    private int $connectTimeout;

    private int $timeout;

    /**
     * @var array<string, mixed>
     */
    private array $defaultOptions;

    /**
     * @param array<string, mixed> $defaultOptions
     */
    public function __construct(string $baseUrl, string $model, int $connectTimeout, int $timeout, array $defaultOptions = [])
    {
        if ($baseUrl === '' || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Ollama base URL must be a valid URL.');
        }
        if ($model === '') {
            throw new InvalidArgumentException('Ollama model must not be empty.');
        }
        if ($connectTimeout < 1) {
            throw new InvalidArgumentException('Ollama connect timeout must be at least 1 second.');
        }
        if ($timeout < 1) {
            throw new InvalidArgumentException('Ollama timeout must be at least 1 second.');
        }

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
        $this->connectTimeout = $connectTimeout;
        $this->timeout = $timeout;
        $this->defaultOptions = self::sanitizeOptions($defaultOptions);
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options
     */
    public function chat(array $messages, array $options = []): string
    {
        $payload = array_replace(['model' => $this->model, 'messages' => $messages, 'stream' => false], $this->defaultOptions, self::sanitizeOptions($options));
        
        try {
            $response = Curl::json(
                'POST',
                $this->baseUrl.'/v1/chat/completions',
                $payload,
                [
                    \CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
                    \CURLOPT_TIMEOUT => $this->timeout,
                ],
            );
        } catch (JsonException $e) {
            throw new RuntimeException('Ollama request could not be encoded: '.$e->getMessage(), previous: $e);
        }

        if ($response['error'] || $response['statusCode'] < 200 || $response['statusCode'] >= 300) {
            throw new RuntimeException('Ollama request failed: '.($response['error'] ?: 'HTTP '.$response['statusCode']));
        }

        $content = $response['data']['choices'][0]['message']['content'] ?? '';
        $content = is_string($content) ? trim($content) : '';
        if ($content === '') {
            throw new RuntimeException('Ollama returned an empty answer.');
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function sanitizeOptions(array $options): array
    {
        if (($options['stream'] ?? false) === true) {
            throw new InvalidArgumentException('Streaming is not supported by OllamaClient::chat().');
        }

        unset($options['model'], $options['messages'], $options['stream']);
        return $options;
    }
}
