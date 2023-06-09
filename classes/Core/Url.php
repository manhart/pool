<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace pool\classes\Core;

use Input;
use JetBrains\PhpStorm\NoReturn;
use JsonSerializable;
use pool\classes\Exception\InvalidArgumentException;
use SensitiveParameter;
use Stringable;

/**
 * Class Url
 * @package pool\classes\Core
 * @since 2003-08-04
 */
class Url extends PoolObject implements Stringable, JsonSerializable
{
    /**
     * @var array the valid schemes
     */
    const VALID_SCHEMES = [
        'http',
        'https',
        'ftp',
        'ftps',
        'sftp',
        'ssh',
        'tel',
        'mailto'
    ];

    /**
     * @var array the default ports for the schemes
     */
    const PORTS = [
        'http' => 80,
        'https' => 443,
        'ftp' => 21,
        'ftps' => 990,
        'sftp' => 22,
        'ssh' => 22,
        'tel' => 0,
        'mailto' => 0
    ];

    /**
     * @var string the scheme or protocol
     */
    protected string $scheme = '';

    /**
     * @var string the host
     */
    protected string $host = '';

    /**
     * @var int the port
     */
    protected int $port = 0;

    /**
     * @var string the user
     */
    protected string $user = '';

    /**
     * @var string the pass
     */
    protected string $password = '';

    /**
     * @var string the path
     */
    protected string $path = '';

    /**
     * @var array the query params
     */
    protected array $query = [];

    /**
     * @var string the fragment
     */
    protected string $fragment = '';

    /**
     * @var bool absolute url
     */
    protected bool $absolute = false;

    /**
     * @var bool is set to true if the scheme is "mailto" or "tel"
     */
    private bool $withoutHost = false;

    /**
     * @param bool|int $withQuery if true, the query params will be initialized with the current query params
     */
    public function __construct(bool | int $withQuery = true)
    {
        // initialize with current url
        $this->scheme = $_SERVER['REQUEST_SCHEME'] ?? 'https';
        $this->host = $_SERVER['SERVER_NAME'];
        $this->port = (int)$_SERVER['SERVER_PORT'] ?? 0;
        $this->path = $_SERVER['SCRIPT_NAME'] ?? '';
        if($withQuery) {
            $this->withQuery($_GET);
        }
    }

    /**
     * Make Url from string
     *
     * @param string $url
     * @return Url
     */
    public static function fromString(string $url): Url
    {
        $parts = @parse_url($url);
        if ($parts === false) {
            throw new InvalidArgumentException("Malformed or unsupported URI '$url'");
        }

        $Url = new static(false);
        $Url->withScheme(strtolower($parts['scheme'] ?? $_SERVER['REQUEST_SCHEME'] ?? ''));
        $Url->withHost(rawurldecode($parts['host'] ?? ''));
        $Url->withPort($parts['port'] ?? 0);
        $Url->withUserInfo(rawurldecode($parts['user'] ?? ''), rawurldecode($parts['pass'] ?? ''));
        $Url->withPath($parts['path'] ?? '');
        $Url->withQuery($parts['query'] ?? '');
        $Url->withFragment(rawurldecode($parts['fragment'] ?? ''));
        return $Url;
    }

    /**
     * Make Url from current url
     *
     * @return Url
     */
    public static function fromCurrent(): Url
    {
        return new self(true);
    }

    /**
     * Make Url from Input object with its data
     *
     * @param Input $Input
     * @return Url
     */
    public static function fromInput(Input $Input): Url
    {
        $Url = new static(false);
        $Url->withQuery($Input->getData());
        return $Url;
    }

    /**
     * Set the URI-scheme
     *
     * @param string $scheme
     * @return $this
     */
    public function withScheme(string $scheme): static
    {
        if(!in_array($scheme, static::VALID_SCHEMES)) {
            throw new InvalidArgumentException('Invalid scheme: ' . $scheme);
        }
        if($this->scheme != $scheme && $scheme) {
            $this->absolute = true;
            // set withoutHost to true if scheme is "mailto" or "tel". This is needed for the getUrl() or __toString() method.
            $this->withoutHost = in_array($scheme, ['mailto', 'tel']);
        }
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Retrieve the scheme component of the URI
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     * @return string
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * Set the host
     *
     * @param string $host
     * @return $this
     */
    public function withHost(string $host): static
    {
        if($host && !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new InvalidArgumentException('Invalid host: ' . $host);
        }

        if($this->host != $host && $host) {
            $this->absolute = true;
        }

        $this->host = $host;
        return $this;
    }

    /**
     * Retrieve the host component of the URI
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * return the part of domain
     *
     * @param int $level
     * @return $this
     */
    public function getDomain(int $level = 2): string
    {
        $parts = ip2long($this->host) ? [$this->host] : explode('.', $this->host);
        $parts = $level >= 0 ? array_slice($parts, -$level) : array_slice($parts, 0, $level);
        return implode('.', $parts);
    }

    /**
     * Set the port
     *
     * @param int $port
     * @return $this
     */
    public function withPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * Retrieve the port component of the URI
     *
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * Set the user and password
     *
     * @param string $user
     * @param string $password
     * @return $this
     */
    public function withUserInfo(string $user, #[SensitiveParameter] string $password = ''): static
    {
        $this->user = $user;
        $this->password = $password;
        return $this;
    }

    /**
     * Retrieve the user component of the URI
     *
     * @return string
     */
    public function getUser(): string
    {
        return $this->user;
    }

    /**
     * Retrieve the password component of the URI
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Set the path
     *
     * @param string $path
     * @return $this
     */
    public function withPath(string $path): static
    {
        if($this->host && !str_starts_with($path, '/')) {
            $path = '/' . $path;
        }
        $this->path = $path;
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * return segments of path as array separated by /
     *
     * @return array
     */
    public function getSegments(): array
    {
        return explode('/', trim($this->path, '/'));
    }

    /**
     * set the query params
     *
     * @param array|string $query
     * @return $this
     */
    public function withQuery(array|string $query): static
    {
        if(is_string($query)) {
            $query_string = $query;
            $query = [];
            parse_str($query_string, $query);
        }
        $this->query = $query;
        return $this;
    }

    /**
     * clear the query params
     */
    public function clearQuery(): static
    {
        $this->query = [];
        return $this;
    }

    /**
     * set query value
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function setQueryValue(string $key, string $value): static
    {
        $this->query[$key] = $value;
        return $this;
    }

    /**
     * set a query param / value and overwrite existing
     *
     * @param string $key
     * @param string|null $value
     * @return $this
     */
    public function setParam(string $key, ?string $value): static
    {
        if(is_null($value)) {
            return $this->delParam($key);
        }
        $this->query[$key] = $value;
        return $this;
    }

    /**
     * set query params / values and overwrite existing
     */
    public function setParams(array $params): static
    {
        $this->query = $params + $this->query;
        return $this;
    }

    /**
     * get a query param
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getParam(string $key, ?string $default = null): ?string
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * delete a query param
     * @param string $key
     * @return $this
     */
    public function delParam(string $key): static
    {
        unset($this->query[$key]);
        return $this;
    }

    /**
     * get the query params
     *
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * set an anchor / fragment
     * @param string $fragment
     * @return $this
     */
    public function withFragment(string $fragment): static
    {
        $this->fragment = $fragment;
        return $this;
    }

    /**
     * get the anchor / fragment
     * @return string
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * set only the path of the url
     *
     * @param string $scriptPath
     * @return Url
     */
    public function setScriptPath(string $scriptPath): static
    {
        $script = pathinfo($this->path, PATHINFO_BASENAME);
        if(!empty($script) && str_contains($script, '.')) {
            $this->path = addEndingSlash($scriptPath) . $script;
        }
        else
            $this->path = $scriptPath;
        return $this;
    }

    /**
     * set or change the script / filename of the url
     *
     * @param string $scriptName
     * @return Url
     */
    public function setScriptName(string $scriptName): static
    {
        $path = pathinfo($this->path, PATHINFO_DIRNAME);
        $this->path = addEndingSlash($path) . $scriptName;
        return $this;
    }

    /**
     * return the port if it's not the default port of the scheme
     * @return string
     */
    private function _getPortInfo(): string
    {
        // don't add the port if it's the default port of the scheme
        if(!empty($this->port) &&
            !($this->port === self::PORTS[$this->scheme] ?? 0)) {
            return ':' . $this->port;
        }
        return '';
    }

    /**
     * Retrieve the authority component of the URI
     *
     * If no authority information is present, this method MUST return an empty string.
     *
     * The authority syntax of the URI is: [user-info@]host[:port]
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     * @return string The URI authority, in '[user-info@]host[:port]' format
     */
    public function getAuthority(): string
    {
        $authority = $this->host;

        if($userInfo = $this->getUserInfo()) {
            $authority = "$userInfo@$authority";
        }
        $authority .= $this->_getPortInfo();
        return $authority;
    }

    /**
     * Retrieve the user information component of the URI
     *
     * @return string The URI user information, in 'username[:password]' format.
     */
    public function getUserInfo(): string
    {
        $userInfo = rawurlencode($this->user);
        if($this->password) {
            $userInfo .= ':' . rawurlencode($this->password);
        }
        return $userInfo;
    }

    /**
     * return the url as string
     *
     * @param bool|null $absolute
     * @return string
     */
    public function getUrl(?bool $absolute = null): string
    {
        $url = '';
        $absolute ??= $this->absolute;
        if($absolute) {
            // "mailto" and "tel" have no authority. For an absolute URL, the scheme and host must be set.
            if($this->withoutHost || ($this->scheme && $this->host)) {
                $url = $this->scheme ? $this->scheme . ':' : '';
                if(!$this->withoutHost && $authority = $this->getAuthority()) {
                    $url .= "//$authority";
                }
            }
        }

        $url .= $this->path;
        if ($this->query) {
            $url .= '?' . http_build_query($this->query);
        }
        if ($this->fragment) {
            $url .= '#' . $this->fragment;
        }
        return $url;
    }

    /**
     * transform the url to a string
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUrl();
    }

    /**
     * @return never
     */
    #[NoReturn] public function reload(): never
    {
        $this->redirect();
    }

    /**
     * redirect to the current url
     *
     * @param bool $replace
     * @param int $http_response_code
     * @return never
     */
    #[NoReturn] public function redirect(bool $replace = true, int $http_response_code = 302): never
    {
        header('Location: ' . $this->getUrl(), $replace, $http_response_code);
        exit;
    }

    /**
     * @deprecated use redirect() instead
     * @return never
     */
    public function restartUrl(): never
    {
        $this->redirect();
    }
    /**
     * @deprecated use getUrl() instead
     * @return string
     */
    public function getAbsoluteUrl(): string
    {
        return $this->getUrl(true);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4
     */
    public function jsonSerialize(): string
    {
        return $this->getUrl();
    }
}

/*
$Url = new Url(false);
echo $Url->getUrl();
echo '<br>';

$Url = new Url();
echo $Url->getUrl();
echo '<br>';

$Url->setScriptName('run.php');
echo $Url->getUrl();
echo '<br>';

$Url->setParam('myParam', 'myValue');
echo $Url->getUrl();
echo '<br>';

$Url->setScriptPath('/foo/bar');
echo $Url->getUrl();
echo '<br>';

$Url = Url::fromString('https://www.example.com:80/foo/bar?test=1#fragment');
echo $Url->getUrl();
echo '<br>';

$Url = Url::fromString('mailto:alexander.manhart@gmx.de');
echo $Url->getUrl();
echo '<br>';

$Url = Url::fromString('https://www.example.com:80/foo/bar?test=1#fragment');
$Url->setParam('test', '2')->setParam('name', 'alex');
echo $Url->getUrl();
echo '<br>';

echo $Url->getDomain(3);
echo '<br>';

$Url->delParam('test');
echo $Url->getUrl();
echo '<br>';

$Url->withFragment('');
$Url->setParam('name', null);
echo $Url->getUrl();
echo '<br>';

$Url = Url::fromCurrent();
echo $Url->getUrl(true);
echo '<br>';

echo $Url;
echo '<br>';

$Url = Url::fromString('/g7portal/index.php?r=site%2Findex');
echo $Url->getUrl();
echo '<br>';

$Input = new Input();
$Input->setVar('animal', 'cat');
$Url = Url::fromInput($Input);
echo $Url->getUrl();
echo '<br>';

$Input->setVar('hans', 'wurst');
$Input->setVar('arr', null);
$Url = new Url();
$Url->setParams($Input->getData());
//$Url->delParam('arr');
echo $Url->getUrl();
echo '<br>';

$Url = Url::fromString('/images/logos/logo.png');
echo $Url->getUrl();
echo '<br>';
$Url->setScriptName('baby.png');
echo $Url->getUrl();
echo '<br>';
$Url->setScriptPath('/images/icons');
echo $Url->getUrl();
echo '<br>';
//$Url->withScheme('https')->withHost('www.example.com');
echo $Url->getUrl(true);
echo '<br>';

*/