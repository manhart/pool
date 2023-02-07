<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class InputCookie extends Input
{
    /**
     * initialize the InputCookie object with the superglobal $_COOKIE
     * @param int $superglobals
     */
    public function __construct(int $superglobals = Input::INPUT_COOKIE)
    {
        parent::__construct($superglobals);
    }

    /**
     * Setzt ein fluechtiges Cookie, dass nur solange wie die Session existiert (d.h. verfaellt nach Schliessen des Browsers).
     * Hinweis: der Wertebereich des Cookies wird automatisch URL-konform codiert (urlencoded) und beim Lesen automatisch URL-konform decodiert.
     *
     * @param string $cookiename Name des Cookies
     * @param string $value Wert des Cookies
     * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
     * @param string $domain Die Domain, der das Cookie zur Verf�gung steht
     * @param integer $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
     * @return boolean Erfolgsstatus
     */
    public function setTransientCookie($cookiename, $value = '', $path = '/', $domain = '', $secure = 0): bool
    {
        // verf�llt nach Schlie�en des Browsers
        $this->setVar($cookiename, $value);
        return setcookie($cookiename, $value, null, $path, $domain, $secure);
    }

    /**
     * Setzt ein langlebiges Cookie, dass solange, bis die gesetze Zeit abgelaufen ist, existiert.
     * Hinweis: der Wertebereich des Cookies automatisch URL-konform codiert (urlencoded) und beim Lesen automatisch URL-konform decodiert.
     *
     * @param string $cookiename Name des Cookies
     * @param string $value Wert des Cookies
     * @param integer $expire Lebenszeit des Cookies in Sekunden
     * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
     * @param string $domain Die Domain, der das Cookie zur Verf�gung steht
     * @param integer $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
     * @return boolean Erfolgsstatus
     */
    public function setPersistentCookie($cookiename, $value, $expire, $path = '/', $domain = '', $secure = 0): bool
    {
        $this->setVar($cookiename, $value);
        return setcookie($cookiename, $value, time() + $expire, $path, $domain, $secure);
    }

    /**
     * Loescht ein Cookie.
     * Hinweis: Cookies m�ssen mit den selben Parametern geloescht werden, mit denen sie gesetzt wurden.
     *
     * @access public
     * @param string $cookieName Name des Cookies
     * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
     * @param string $domain Die Domain, der das Cookie zur Verfuegung steht
     * @param bool $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
     * @return boolean Erfolgsstatus
     */
    public function delCookie(string $cookieName, string $path = '/', string $domain = '', bool $secure = false): bool
    {
        if(isset($this->vars[$cookieName])) {
            $this->delVar($cookieName);
        }
        return setcookie($cookieName, '', time() - 3600, $path, $domain, $secure);
    }
}