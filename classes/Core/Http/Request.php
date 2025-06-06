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

namespace pool\classes\Core\Http;

use pool\classes\Core\Input\Input;
use pool\classes\Core\Url;

final class Request
{
    private static ?Url $url = null;

    private static ?string $body = null;

    private static ?Input $input = null;

    private static ?bool $isAjax = null;

    private static ?Input $inputServer = null;

    private static ?HttpMethod $method = null;

    private static function inputServer(): Input
    {
        return self::$inputServer ??= new Input(Input::SERVER);
    }

    public static function method(): ?HttpMethod
    {
        return self::$method ??= HttpMethod::fromGlobals();
    }

    public static function isGet(): bool
    {
        return self::method() === HttpMethod::GET;
    }

    public static function isPost(): bool
    {
        return self::method() === HttpMethod::POST;
    }

    public static function isPut(): bool
    {
        return self::method() === HttpMethod::PUT;
    }

    public static function isDelete(): bool
    {
        return self::method() === HttpMethod::DELETE;
    }

    public static function isPatch(): bool
    {
        return self::method() === HttpMethod::PATCH;
    }

    public static function isHead(): bool
    {
        return self::method() === HttpMethod::HEAD;
    }

    public static function isOptions(): bool
    {
        return self::method() === HttpMethod::OPTIONS;
    }

    public static function header(string $name): ?string
    {
        $key = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
        return self::inputServer()->getVar($key);
    }

    public static function contentType(): ?string
    {
        return self::inputServer()->getVar('CONTENT_TYPE');
    }

    public static function accepts(string $mime): bool
    {
        $accept = self::inputServer()->getAsString('HTTP_ACCEPT');
        return str_contains($accept, $mime);
    }

    public static function body(): string
    {
        return self::$body ??= file_get_contents('php://input') ?: '';
    }

    public static function json(): ?array
    {
        $data = json_decode(self::body(), true);
        return is_array($data) ? $data : null;
    }

    public static function url(): Url
    {
        return self::$url ??= new Url();
    }

    public static function input(): Input
    {
        return self::$input ??= new Input(Input::POST | Input::GET);
    }

    public static function resetInput(Input $Input): void
    {
        self::$input = $Input;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::input()->getVar($key, $default);
    }

    public static function has(string $key): bool
    {
        return self::input()->has($key);
    }

    public static function all(): array
    {
        return self::input()->getData();
    }

    public static function scheme(): string
    {
        return self::inputServer()->getVar('REQUEST_SCHEME', self::inputServer()->getAsString('HTTPS') !== '' ? 'https' : 'http');
    }

    public static function host(): ?string
    {
        return self::inputServer()->getVar('HTTP_HOST', self::inputServer()->getVar('SERVER_NAME'));
    }

    public static function port(): int
    {
        return self::inputServer()->getAsInt('SERVER_PORT') ?: (self::scheme() === 'https' ? 443 : 80);
    }

    public static function scriptName(): string
    {
        return self::inputServer()->getAsString('SCRIPT_NAME');
    }

    public static function queryRaw(): string
    {
        return self::inputServer()->getAsString('QUERY_STRING');
    }

    public static function uri(): string
    {
        return self::inputServer()->getAsString('REQUEST_URI', self::scriptName());
    }

    public static function pathInfo(): ?string
    {
        return self::inputServer()->getVar('PATH_INFO');
    }

    public static function phpSelf(): ?string
    {
        return self::inputServer()->getVar('PHP_SELF');
    }

    public static function realRemoteAddr(): ?string
    {
        return self::inputServer()->getVar('REMOTE_ADDR');
    }

    public static function clientIp(): ?string
    {
        foreach (
            [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',// RFC 7239
                'REMOTE_ADDR',
            ] as $key
        ) {
            if (self::inputServer()->emptyVar($key)) {
                continue;
            }

            $raw = self::inputServer()->getAsString($key);

            // Check if RFC 7239 Forwarded header is present
            if ($key === 'HTTP_FORWARDED') {
                // Forwarded: for=192.0.2.43, for=198.51.100.17 or for=2001:db8::1 or for="[2001:db8::1]:4711"
                $ip = self::parseForwardedHeader($raw);
                if ($ip !== null) {
                    return $ip;
                }
            } else {
                foreach (explode(',', $raw) as $entry) {
                    $ip = trim($entry);
                    if (str_contains($ip, ':')) {
                        $ip = explode(':', $ip)[0]; // strip port
                    }
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return null;
    }

    public static function isAjax(): bool
    {
        return self::$isAjax ??= self::header('X-Requested-With') === 'XMLHttpRequest';
    }

    public static function isSecure(): bool
    {
        return self::scheme() === 'https';
    }

    public static function netDomain(): ?string
    {
        return self::header('X-NET-DOMAIN');
    }

    public static function referer(): ?string
    {
        return self::inputServer()->getVar('HTTP_REFERER');
    }

    public static function userAgent(): ?string
    {
        return self::inputServer()->getVar('HTTP_USER_AGENT');
    }

    public static function fingerprint(bool $withClientIP = true): string
    {
        $parts = [];

        if ($withClientIP) {
            $parts[] = self::clientIp() ?? '';
        }

        // classic header
        $parts[] = self::userAgent() ?? '';
        $parts[] = self::inputServer()->getAsString('HTTP_ACCEPT');
        $parts[] = self::inputServer()->getAsString('HTTP_ACCEPT_CHARSET');
        $parts[] = self::inputServer()->getAsString('HTTP_ACCEPT_ENCODING');
        $parts[] = self::inputServer()->getAsString('HTTP_ACCEPT_LANGUAGE');

        // modern client Hints (not always set, but helpful)
        $parts[] = self::inputServer()->getAsString('HTTP_SEC_CH_UA');
        $parts[] = self::inputServer()->getAsString('HTTP_SEC_CH_UA_MOBILE');
        $parts[] = self::inputServer()->getAsString('HTTP_SEC_CH_UA_PLATFORM');
        $parts[] = self::inputServer()->getAsString('HTTP_SEC_CH_UA_PLATFORM_VERSION');

        return hash('sha256', implode('|', $parts));
    }

    private static function parseForwardedHeader(string $header): ?string
    {
        foreach (explode(',', $header) as $segment) {
            $segment = trim($segment);
            foreach (explode(';', $segment) as $kv) {
                [$key, $val] = array_map(trim(...), explode('=', $kv, 2) + [1 => '']);
                if (strtolower($key) === 'for') {
                    $val = trim($val, '"');

                    // IPv6 with brackets + port → e.g. [2001:db8::1]:4711
                    if (str_starts_with($val, '[')) {
                        $closing = strpos($val, ']');
                        if ($closing !== false) {
                            $ip = substr($val, 1, $closing - 1);
                        } else {
                            $ip = $val; // fallback
                        }
                    } else {
                        // remove optional port from IPv4 or unbracketed IPv6
                        $colon = strpos($val, ':');
                        $ip = $colon !== false ? substr($val, 0, $colon) : $val;
                    }

                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }
        return null;
    }
}
