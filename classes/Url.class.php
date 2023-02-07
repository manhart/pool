<?php
/**
 * 		-==|| Rapid Module Library (RML) ||==-
 *
 * Url.class.php
 *
 * Klasse kuemmert sich um die Url Verwaltung.
 *
 * @version $Id: Url.class.php,v 1.10 2007/05/31 14:39:34 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @package pool
 * @since 2003-08-04
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

const URL_TARGET_BLANK = '_blank';    # um den Verweis in einem neuen Fenster zu öffnen
const URL_TARGET_SELF = '_self';        # um den Verweis im aktuellen Fenster zu öffnen
const URL_TARGET_PARENT = '_parent';    # um bei verschachtelten Framesets das aktuelle Frameset zu sprengen
const URL_TARGET_TOP = '_top';        # um bei verschachtelten Framesets alle Framesets zu sprengen

const URL_DEFAULT_SCHEME = 'http';
const URL_DEFAULT_PORT = 80;

/**
 * Url
 *
 * URI Verwaltung (liest alle $_GET Variablen ein).
 *
 * @package rml
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: Url.class.php,v 1.10 2007/05/31 14:39:34 manhart Exp $
 **/
class Url extends PoolObject
{
    /**
     * @var InputGet
     */
    private InputGet $InputGet;

    //@var string Scriptname
    private string $ScriptName = '';

    //@var string Scriptpfad (relativer Pfad)
    private string $ScriptPath = '';

    /**
     * @var int Port der Anfrage (Standard 80)
     * @access private
     */
    var $port = URL_DEFAULT_PORT;

    //@var string Host (Servername, oder Adresse)
    //@access private
    var $Host = '';

    /**
     * @var string Scheme (Standard http)
     */
    var $scheme = URL_DEFAULT_SCHEME;

    /**
     * @var string Pfad (ohne Scriptname!)
     */
    var $path = '';

    /**
     * @var string Skript (Name des Skripts)
     */
    var $script = '';


    //@var string Anker (#)
    //@access private
    var $Anchor = '';

    /**
     * Trennzeichen UND-Zeichen
     *
     * @var string
     */
    var $entityAmpersand = '&';

    /**
     * @var bool leider wird in setScript in path eine komplette Uri rein gesetzt!?!? Schoener Mist!
     */
    var $mieserWorkaround = false;

    /**
     * @var string hostname
     */
    private $hostname = ''; // hostname

    /**
     * @var string Top-Level-Domain
     */
    private $topLevelDomain = '';

    /**
     * @var string Second-Level-Domain
     */
    private $secondLevelDomain = '';

    /**
     * @var string Third-Level-Domain
     */
    private $thirdLevelDomain = ''; // = subdomain = host

    /**
     * Sets up the object.
     *
     * @access public
     * @param int $superglobal Superglobals (siehe Input.class.php)
     * @see Input::__construct
     **/
    function __construct(int $superglobal= Input::INPUT_GET)
    {
        $this->InputGet = new InputGet($superglobal);
        $this->Host = $_SERVER['SERVER_NAME'];
        $this->ScriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $this->ScriptName = basename($_SERVER['SCRIPT_NAME']);

        // 23.01.2017, AM, Fix scheme
        if(isset($_SERVER['REQUEST_SCHEME']) and $_SERVER['REQUEST_SCHEME'] != '') {
            $this->scheme = $_SERVER['REQUEST_SCHEME'];
        }

        // 03.05.2016, AM, path und script sind neue Variablen aufgrund missmatch in getUrl()!! todo getUrl neu entwickeln
        $this->path = $this->ScriptPath;
        $this->script = $this->ScriptName;

        if(isset($_SERVER['SERVER_PORT'])) {
            $this->port = intval($_SERVER['SERVER_PORT']);
        }
    }

    /**
     * Setzt nur den Pfad zu einem Script.
     *
     * @param string $scriptPath Pfad eines Scripts
     */
    public function setScriptPath(string $scriptPath)
    {
        $this->ScriptPath = $scriptPath;
        if($this->mieserWorkaround) {
            return;
        }
        $this->path = $scriptPath;
    }

    /**
     * Setzt nur den Dateinamen des Scripts.
     *
     * @param string $scriptName Dateiname eines Scripts.
     **/
    public function setScriptName(string $scriptName)
    {
        if ((strlen($scriptName) > 0) and ($scriptName[0] == '/')) {
            $scriptName = substr($scriptName, 1, strlen($scriptName)-1);
        }
        $this->ScriptName = $scriptName;
        if($this->mieserWorkaround) {
            return;
        }
        $this->script = $scriptName;
    }

    /**
     * Uebergabe des Scripts.
     *
     * @param string $script Script (Pfad+Dateiname)
     **/
    public function setScript(string $script)
    {
        $parsed_url = parse_url($script);

        // so wäre es korrekt, aber.... funktioniert mit dieser Url Version leider nicht
        $this->scheme = $parsed_url['scheme'] ?? URL_DEFAULT_SCHEME;
        $this->Host = $parsed_url['host'] ?? $_SERVER['SERVER_NAME'];
        $this->port = $parsed_url['port'] ?? '';
        $this->path = isset($parsed_url['path']) ? dirname($parsed_url['path']) : ''; // 02.05.2016, AM ergaenzt
        $this->script = isset($parsed_url['path']) ? basename($parsed_url['path']) : ''; // 03.05.2016, AM ergaenzt
        $this->ScriptPath = $this->path;
        $this->ScriptName = $this->script;

        // todo user, pass

        $bool = false;
        $path = $parsed_url['path'] ?? '';
        if(strlen($path)>0 and $path[strlen($path)-1] != '/') {
            $path = dirname($path);
            $bool = true;
        }

        if(isset($parsed_url['scheme']) and $parsed_url['scheme'] != '') {
            $this->mieserWorkaround = true;
            $path = $parsed_url['scheme'] . '://' . $this->Host . ($this->port ? ':' . $this->port : '') . $path;
        }

        if ($bool) {
            $this->setScriptPath($path);
            $this->setScriptName(basename(($parsed_url['path'] ?? '')));
        }
        else {
            $this->setScriptPath($path);
            $this->setScriptName('');
        }

        $this->mieserWorkaround = false;

        $query = $parsed_url['query'] ?? '';
        $this->InputGet->setParams($query);
    }

    /**
     * Erstellt die URI und gibt sie zurück
     *
     * @param string $scriptName Anderweitiges Script
     * @param boolean $withSid Session ID anfuegen.
     * @return string URI
     **/
    public function getUrl(string $scriptName='', bool $withSid=true): string
    {
        if ($scriptName == '') {
            $scriptName = ($this->ScriptName != '') ? (addEndingSlash($this->ScriptPath).$this->ScriptName) : $this->ScriptPath;
        }
        $q = $this->InputGet->getQuery('', $this->entityAmpersand);

        // todo pruefen ob es reicht auf $_GET[session_id] zu pruefen. Und nur wenn gesetzt, fuehren wir die ID in der URL weiter
        $use_only_cookies = ini_get('session.use_only_cookies');
        $sid = '';
        $isSID = false;
        if(!$use_only_cookies and $withSid) { // 01.03.2017, AM, Fix withSid
            $sid = session_id(); // 19.05.2015, AM, Konstante SID loest in PHP 5.4.39 memory leak aus!
            if($sid) {
                $sid = session_name().'='.$sid;
                $isSID = true;
            }
        }

        $url = $scriptName;
        if(str_contains($scriptName, '?')) {
            $url .= $this->entityAmpersand;
        }
        elseif($q != '' or $isSID) {
            $url .= '?';
        }

        if($isSID) {
            $url .= $sid;

            if($q != '') {
                $url .= $this->entityAmpersand;
            }
        }

        $url .= $q.$this->Anchor;
        return $url;
    }

    /**
     * Erstellt eine absolute URI und gibt sie zurück
     *
     * @param boolean $withSid Session ID
     * @return string URI
     **/
    public function getAbsoluteUrl(bool $withSid=false): string
    {
        $use_only_cookies = ini_get('session.use_only_cookies');
        $sid = '';
        $isSID = false;
        if(!$use_only_cookies) {
            $sid = session_id(); // 19.05.2015, AM, Konstante SID loest in PHP 5.4.39 memory leak aus!
            if($sid) {
                $sid = session_name().'='.$sid;
                $isSID = true;
            }
        }

        // 03.05.2016, AM, nur wenn ein Port existiert, ergaenzen
        $scheme = $this->scheme;
        $port = $this->port ? ($this->port != URL_DEFAULT_PORT ? ':'.$this->port : '') : '';
        $path = addEndingSlash($this->path).$this->script;
        if(strlen($path) > 0 and $path[0] != '/') {
            $path = '/'.$path;
        }
        $query = $this->InputGet->getQuery('', $this->entityAmpersand);

        if($isSID and $withSid) {
            if($query != '') $query .= $this->entityAmpersand;
            $query .= $sid;
        }

        return $scheme.'://'.$this->Host.$port.$path.(($query != '') ? '?' : '').$query.$this->Anchor;
    }

    /**
     * Get Domain
     *
     * @return string
     */
    public function getDomain(): string
    {
        return $this->Host;
    }

    /**
     * Get hostname
     *
     * @return string
     */
    public function getHostname(): string
    {
        $this->resolveDomainname();
        return $this->hostname;
    }

    /**
     * Get Top-Level Domain
     * @return string
     */
    public function getTopLevelDomain(): string
    {
        $this->resolveDomainname();
        return $this->topLevelDomain;
    }

    /**
     * Get Second-Level Domain
     * @return string
     */
    public function getSecondLevelDomain(): string
    {
        $this->resolveDomainname();
        return $this->secondLevelDomain;
    }

    /**
     * Get Third-Level Domain
     * @return string
     */
    public function getThirdLevelDomain(): string
    {
        $this->resolveDomainname();
        return $this->thirdLevelDomain;
    }

    /**
     * doesn't work with tlds like .co.uk!
     */
    private function resolveDomainname()
    {
        if($this->hostname != '') return;
        if(filter_var($this->Host, FILTER_VALIDATE_IP)) {
            $this->hostname = $this->Host;
        }

        $host = explode('.', $this->Host);
        $numHostParts = count($host);
        if($numHostParts == 1) { // mydomain, develop01
            $this->hostname = array_pop($host);
        }
        elseif($numHostParts == 2) { // mydomain.de, mydomain.com
            $this->topLevelDomain = array_pop($host);
            $this->secondLevelDomain = array_pop($host);
            $this->hostname = $this->secondLevelDomain;
        }
        elseif($numHostParts >= 3) { // www.mydomain.de, xyz.www.mydomain.com
            $this->topLevelDomain = array_pop($host);
            $this->secondLevelDomain = array_pop($host);
            $this->thirdLevelDomain = implode('.', $host);
            $this->hostname = $this->thirdLevelDomain;
        }
    }

    /**
     * Erstellt eine HTML <a href=""> Zeichenkette und gibt den HTML Anchor zurück
     *
     * @param string $caption aussagekraeftiger Verweistext
     * @param string $target Zielfenster fuer den Verweis bestimmen (Konstanten siehe Url.class.php)
     * @param array $attr Weitere Attribute fuer das <a> (Anchor = Anker)
     * @return string
     **/
    public function getHref(string $caption='Link', string $target=URL_TARGET_SELF, array $attr=array()): string
    {
        $str_attr = '';
        if (is_array($attr)) {
            foreach($attr as $key => $value) {
                $str_attr .= $key.'="'.$value.'" ';
            }
        }
        return '<a href="'.$this->getUrl().'" target="'.$target.'" '.$str_attr.'>'.$caption.'</a>';
    }

    /**
     * Erzeugt einen JavaScript Link. Mit Type kann die Art bestimmt werden, z.B. 'open' macht einen windows.open(xyz).
     * Beim 'open' kann man weitere Parameter mitgeben: $name und $params. Der Name ist der Fenstername. Parameter werden 1:1 aus folgenden Moeglichkeiten zusammen gesetzt:
     *
     * dependent= yes|no  Wenn ja (yes), wird das Fenster geschlossen, wenn sein Elternfenster geschlossen wird. Wenn nein (no = Voreinstellung), bleibt das Fenster erhalten, wenn sein Elternfenster geschlossen wird.
     * height= [Pixel]  Hoehe des neuen Fensters in Pixeln, z.B. height=200.
     * hotkeys= yes|no  Wenn nein (no), werden Tastaturbefehle zum Steuern des Browsers in dem Fenster deaktiviert. Wenn ja (yes = Voreinstellung), bleiben Tastaturbefehle des Browsers in dem Fenster gueltig.
     * innerHeight= [Pixel]  Hoehe des Anzeigebereichs des neuen Fensters in Pixeln, z.B. innerHeight=200.
     * innerWidth= [Pixel]  Breite des Anzeigebereichs des neuen Fensters in Pixeln, z.B. innerWidth=200.
     * left= [Pixel]  Horizontalwert der linken oberen Ecke des neuen Fensters in Pixeln, z.B. left=100.
     * location= yes|no  Wenn ja (yes), erhaelt das Fenster eine eigene Adresszeile. Wenn nein (no), erhaelt es keine Adresszeile. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt. Netscape 6.1 interpretiert diese Angabe nicht.
     * menubar= yes|no  Wenn ja (yes), erhaelt das Fenster eine eigene Menueleiste mit Browser-Befehlen. Wenn nein (no), erhaelt es keine Menueleiste. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt.
     * resizable= yes|no  Wenn ja (yes), kann der Anwender das Fenster in der Groesse verändern. Wenn nein (no), kann er die Fenstergröße nicht aendern. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt.
     * screenX= [Pixel]  Horizontalwert der linken oberen Ecke des neuen Fensters in Pixeln, z.B. screenX=100.
     * screenY= [Pixel]  Vertikalwert der linken oberen Ecke des neuen Fensters in Pixeln, z.B. screenY=30.
     * scrollbars= yes|no  Wenn ja (yes), erhaelt das Fenster Scroll-Leisten. Wenn nein (no), kann der Anwender in dem Fenster nicht scrollen. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt.
     * status= yes|no  Wenn ja (yes), erhaelt das Fenster eine eigene Statuszeile. Wenn nein (no), erhaelt es keine Statuszeile. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt.
     * toolbar= yes|no  Wenn ja (yes), erhaelt das Fenster eine eigene Werkzeugleiste. Wenn nein (no), erhaelt es keine Werkzeugleiste. Voreinstellung ist no, beim Internet Explorer jedoch nur, wenn die Optionenzeichenkette mindestens eine Option enthaelt.
     * top= [Pixel]  Vertikalwert der linken oberen Ecke des neuen Fensters in Pixeln, z.B. top=100.
     * width= [Pixel]  Breite des neuen Fensters in Pixeln, z.B. width=400.
     *
     * @access public
     * @param string $type open|href|location|top
     * @param string $name Standardwert 'window'
     * @param array $params Parameter (siehe Funktionsbeschreibung), Array Aufbau: array('location' => 'yes')
     * @param boolean $focus Fokusiert per window.open geoeffnete Fenster
     * @param boolean $withSid Session ID ergaenzen
     * @return string JavaScript Code
     **/
    function getJavaScript($type, $name='window', $params=array(), $focus=true, $withSid=true)
    {
        $url = $this->getUrl('', $withSid);

        switch($type) {
            case 'open':	// window.open
                $param_list = '';
                if(is_array($params)) {
                    foreach($params as $key => $value) {
                        $param = $key.'='.$value;
                        if ($param_list != '') {
                            $param = ',' . $param;
                        }
                        $param_list .= $param;
                    }
                }

                if ($focus) {
                    $js = 'var new_win=window.open(\''.$url.'\', \''.$name.'\', \''.$param_list.'\'); if(typeof new_win == \'object\') new_win.focus();';
                }
                else {
                    $js = 'window.open(\''.$url.'\', \''.$name.'\', \''.$param_list.'\')';
                }
                break;

            case 'href':
                $js = 'document.location.href=\''.$url.'\';';
                break;

            case 'location':
                $js = 'document.location=\''.$url.'\';';
                break;

            case 'top':
                $js = 'top.document.location=\''.$url.'\';';
                break;

            case 'frame':
                $js = 'top.frames[\''.$name.'\'].location.href=\''.$url.'\';';

            default:
                $js = 'void(0);';
        }
        return $js;
    }

    /**
     * Laedt das Dokument neu (header redirect). Behaelt Session ID in der URL.
     *
     * @param boolean $with_sid Session Id uebernehmen
     */
    public function reloadUrl(bool $with_sid=true, bool $replace=true, int $http_response_code=302): never
    {
        header('Location: '.$this->getUrl('', $with_sid), $replace, $http_response_code);
        exit;
    }

    /**
     * Geht zu einem neuen Dokument (header redirect). Session via URL wird nicht mitgenommen. Siehe dazu reloadUrl()!
     *
     * @param boolean $absolute verwende absolutee URI
     * @param boolean $replace Der optionale Parameter replace gibt an, ob der Header einen vorhergehenden gleichartigen Header ersetzten soll, oder ob ein zweiter Header des selben Typs hinzugefuegt werden soll.
     * @param integer $http_response_code Forciert einen HTTP-Response-Code des angegebenen Wertes
     **/
    public function gotoUrl(bool $absolute=false, bool $replace=true, int $http_response_code=302): never
    {
        if ($absolute) {
            header('Location: '.$this->getAbsoluteUrl(), $replace, $http_response_code);
        }
        else {
            header('Location: '.$this->getUrl(), $replace, $http_response_code);
        }
        exit;
    }

    /**
     * Laedt das Dokument neu (header redirect)
     *
     * @param boolean $with_sid Session Id uebernehmen
     */
    public function restartUrl(bool $with_sid=true): never
    {
        $this->reloadUrl($with_sid);
    }

    /**
     * Loescht alle Url Parameter
     **/
    public function clear()
    {
        $this->InputGet->clear();
    }

    /**
     * Synonym auf Url::modifyQuery()
     *
     * @param string $key Schluessel / Parameterbezeichnung
     * @param string|null $value Wert des Parameters
     * @return Url
     */
    public function setParam(string $key, ?string $value=''): Url
    {
        $this->InputGet->setVar($key, $value);
        return $this;
    }

    /**
     * Setzt einen Anker (Anchor) fuer die aktuelle Url.
     *
     * @param string $value Name des Ankers / Fragments
     **/
    public function setAnchor(string $value = '#')
    {
        if (strlen($value) > 0) {
            if ($value[0] != '#') {
                $value = '#' . $value;
            }
            $this -> Anchor = $value;
        }
    }

    /**
     * set scheme (~ protocol) - type of URIs
     *
     * @param string $scheme
     * @return Url
     */
    public function setScheme(string $scheme): Url
    {
        $this->scheme =  $scheme;
        return $this;
    }

    /**
     * set host (~ authority without port)
     *
     * @param string $host www.domain.com
     * @return Url
     */
    public function setDomain(string $host): Url
    {
        $this->Host = $host;
        return $this;
    }
}