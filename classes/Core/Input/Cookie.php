<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core\Input;

/**
 * Class Cookie
 *
 * @package pool\classes\Core\Input
 * @since 2003-07-10
 */
class Cookie extends Input
{
    /**
     * Initialize the Cookie with the default superglobal $_COOKIE
     *
     * @param int $superglobals
     */
    public function __construct(int $superglobals = Input::COOKIE)
    {
        parent::__construct($superglobals);
    }

    /**
     * Sets a session cookie, which will be available only to the current session. If the browser is closed, the cookie will be deleted.
     *
     * @param string $cookieName Name of the cookie
     * @param string $value Value of the cookie
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire
     *     domain. If set to '/foo/', the cookie will only be available within the /foo/ directory and all subdirectories such as /foo/bar/ of domain
     * @param string $domain The (sub)domain that the cookie is available to. Setting this to a subdomain (such as 'www.example.com') will make the cookie
     *     available to that subdomain and all other subdomains of it (i.e. w2.www.example.com). To make the cookie available to the whole domain
     *     (including all subdomains of it), simply set the value to the domain name ('example.com', in this case).
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to true, the
     *     cookie will only be set if a secure connection exists. On the server-side, it's on the programmer to send this kind of cookie only on secure
     *     connection (e.g. with respect to $_SERVER["HTTPS"]).
     * @noinspection SpellCheckingInspection
     * @return boolean If output exists prior to calling this function, setcookie() will fail and return false. If setcookie() successfully runs, it will
     *     return true. This does not indicate whether the user accepted the cookie.
     * @see http://php.net/manual/en/function.setcookie.php
     */
    public function setTransientCookie(string $cookieName, string $value = '', string $path = '/', string $domain = '', bool $secure = false): bool
    {
        $this->setVar($cookieName, $value);
        return setcookie($cookieName, $value, null, $path, $domain, $secure);
    }

    /**
     * Set a persistent cookie, which will be stored on the clients system for the duration of the $expire parameter. Like other headers, cookies must be
     * sent before any output from your script (this is a protocol restriction).
     *
     * @param string $cookieName Name of the cookie
     * @param string $value Value of the cookie
     * @param integer $expire Seconds until the cookie expires
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire
     *     domain. If set to '/foo/', the cookie will only be available within the /foo/ directory and all subdirectories such as /foo/bar/ of domain
     * @param string $domain The (sub)domain that the cookie is available to. Setting this to a subdomain (such as 'www.example.com') will make the cookie
     *     available to that subdomain and all other subdomains of it (i.e. w2.www.example.com). To make the cookie available to the whole domain
     *     (including all subdomains of it), simply set the value to the domain name ('example.com', in this case).
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to true, the
     *     cookie will only be set if a secure connection exists. On the server-side, it's on the programmer to send this kind of cookie only on secure
     *     connection (e.g. with respect to $_SERVER["HTTPS"]).
     * @param bool $httponly When true the cookie will be made accessible only through the HTTP protocol. This means that the cookie won't be accessible
     *     by scripting languages, such as JavaScript. It has been suggested that this setting can effectively help to reduce identity theft through XSS
     *     attacks (although it is not supported by all browsers), but that claim is often disputed.
     * @noinspection SpellCheckingInspection
     * @return boolean If output exists prior to calling this function, setcookie() will fail and return false. If setcookie() successfully runs, it will
     *     return true. This does not indicate whether the user accepted the cookie.
     * @see http://php.net/manual/en/function.setcookie.php
     */
    public function setPersistentCookie(string $cookieName, string $value, int $expire, string $path = '/', string $domain = '', bool $secure = false,
        bool $httponly = false): bool
    {
        $this->setVar($cookieName, $value);
        return setcookie($cookieName, $value, time() + $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Delete a value of a cookie
     *
     * @param string $cookieName Name of the cookie
     * @param string $path The path on the server in which the cookie will be available on. If set to '/', the cookie will be available within the entire
     *     domain. If set to '/foo/', the cookie will only be available within the /foo/ directory and all subdirectories such as /foo/bar/ of domain
     * @param string $domain The (sub)domain that the cookie is available to. Setting this to a subdomain (such as 'www.example.com') will make the cookie
     *     available to that subdomain and all other subdomains of it (i.e. w2.www.example.com). To make the cookie available to the whole domain
     *     (including all subdomains of it), simply set the value to the domain name ('example.com', in this case).
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client. When set to true, the
     *     cookie will only be set if a secure connection exists. On the server-side, it's on the programmer to send this kind of cookie only on secure
     *     connection (e.g. with respect to $_SERVER["HTTPS"]).
     * @noinspection SpellCheckingInspection
     * @return boolean If output exists prior to calling this function, setcookie() will fail and return false. If setcookie() successfully runs, it will
     *     return true. This does not indicate whether the user accepted the cookie.
     */
    public function delCookie(string $cookieName, string $path = '/', string $domain = '', bool $secure = false): bool
    {
        if(array_key_exists($cookieName, $this->vars)) {
            $this->delVar($cookieName);
        }
        return setcookie($cookieName, '', time() - 3600, $path, $domain, $secure);
    }
}