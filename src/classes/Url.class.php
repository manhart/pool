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

if(!defined('CLASS_URL')) {

    define('CLASS_URL', 1); 	// Prevent multiple loading

    define('URL_TARGET_BLANK', '_blank');	# um den Verweis in einem neuen Fenster zu öffnen
    define('URL_TARGET_SELF', '_self');		# um den Verweis im aktuellen Fenster zu öffnen
    define('URL_TARGET_PARENT', '_parent');	# um bei verschachtelten Framesets das aktuelle Frameset zu sprengen
    define('URL_TARGET_TOP', '_top');		# um bei verschachtelten Framesets alle Framesets zu sprengen

    define('URL_DEFAULT_SCHEME', 'http');
    define('URL_DEFAULT_PORT', 80);

    /**
     * Url
     *
     * URI Verwaltung (liest alle $_GET Variablen ein).
     *
     * @package rml
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: Url.class.php,v 1.10 2007/05/31 14:39:34 manhart Exp $
     * @access public
     **/
    class Url extends PoolObject
    {
        /**
         * Objekt IGet
         *
         * @access private
         * @var IGet
         */
        var $InpGet = null;

        //@var string Schluesselparameter (-wort)
        //@access private
        var $KeyWord = '';

        //@var string Scriptname
        //@access private
        var $ScriptName = '';

        //@var string Scriptpfad (relativer Pfad)
        //@access private
        var $ScriptPath = '';

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
         * @param mixed $superglobal Superglobals (siehe Input.class.php)
         * @param string $KeyWord not yet implemented
         * @see Input.class.php
         **/
        function __construct($superglobal=I_GET, $KeyWord='')
        {
            $this->InpGet = new IGet($superglobal);
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

            parent::__construct();
        }

        /**
         * Setzt nur den Pfad zu einem Script.
         *
         * @access public
         * @param string $scriptpath Pfad eines Scripts
         **/
        function setScriptPath($scriptpath)
        {
            $this->ScriptPath = $scriptpath;
            if($this->mieserWorkaround) {
                return;
            }
            $this->path = $scriptpath;
        }

        /**
         * Setzt nur den Dateinamen des Scripts.
         *
         * @access public
         * @param string $scriptname Dateiname eines Scripts.
         **/
        function setScriptName($scriptname)
        {
            if ((strlen($scriptname) > 0) and ($scriptname[0] == '/')) {
                $scriptname = substr($scriptname, 1, strlen($scriptname)-1);
            }
            $this->ScriptName = $scriptname;
            if($this->mieserWorkaround) {
                return;
            }
            $this->script = $scriptname;
        }

        /**
         * Uebergabe des Scripts.
         *
         * @param string $script Script (Pfad+Dateiname)
         **/
        function setScript($script)
        {
            $parsed_url = parse_url($script);

            // so wäre es korrekt, aber.... funktioniert mit dieser Url Version leider nicht
            $this->scheme = isset($parsed_url['scheme']) ? $parsed_url['scheme'] : URL_DEFAULT_SCHEME;
            $this->Host = isset($parsed_url['host']) ? $parsed_url['host'] : $_SERVER['SERVER_NAME'];
            $this->port = isset($parsed_url['port']) ? $parsed_url['port'] : '';
            $this->path = isset($parsed_url['path']) ? dirname($parsed_url['path']) : ''; // 02.05.2016, AM ergaenzt
            $this->script = isset($parsed_url['path']) ? basename($parsed_url['path']) : ''; // 03.05.2016, AM ergaenzt
            $this->ScriptPath = $this->path;
            $this->ScriptName = $this->script;

            // todo user, pass

            $bool = false;
            $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
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
                $this->setScriptName(basename((isset($parsed_url['path']) ? $parsed_url['path'] : '')));
            }
            else {
                $this->setScriptPath($path);
                $this->setScriptName('');
            }

            $this->mieserWorkaround = false;

            $query = isset($parsed_url['query']) ? $parsed_url['query'] : '';
            $this->InpGet->setParams($query);
        }

        /**
         * Erstellt die URI und gibt sie zurück
         *
         * @access public
         * @param string $scriptName Anderweitiges Script
         * @param boolean $withSid Session ID anfuegen.
         * @return string URI
         **/
        function getUrl($scriptName='', $withSid=true)
        {
            if ($scriptName == '') {
                $scriptName = ($this->ScriptName != '') ? (addEndingSlash($this->ScriptPath).$this->ScriptName) : $this->ScriptPath;
            }
            $q = $this->InpGet->getQuery('', $this->entityAmpersand);

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
            if(strpos($scriptName, '?') !== false) {
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
         * @access public
         * @param boolean $withSid Session ID
         * @return string URI
         **/
        function getAbsoluteUrl($withSid=false)
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
            $query = $this->InpGet->getQuery('', $this->entityAmpersand);

            if($isSID and $withSid) {
                if($query != '') $query .= $this->entityAmpersand;
                $query .= $sid;
            }


            $url = $scheme.'://'.$this->Host.$port.$path.(($query != '') ? '?' : '').$query.$this->Anchor;
            return $url;
        }

        /**
         * Get Domain
         *
         * @return string
         */
        public function getDomain()
        {
            return $this->Host;
        }

        /**
         * Get hostname
         *
         * @return string
         */
        public function getHostname()
        {
            $this->resolveDomainname();
            return $this->hostname;
        }

        /**
         * Get Top-Level Domain
         * @return string
         */
        public function getTopLevelDomain()
        {
            $this->resolveDomainname();
            return $this->topLevelDomain;
        }

        /**
         * Get Second-Level Domain
         * @return string
         */
        public function getSecondLevelDomain()
        {
            $this->resolveDomainname();
            return $this->secondLevelDomain;
        }

        /**
         * Get Third-Level Domain
         * @return string
         */
        public function getThirdLevelDomain()
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
         * @access public
         * @param string $caption aussagekraeftiger Verweistext
         * @param mixed $target Zielfenster fuer den Verweis bestimmen (Konstanten siehe Url.class.php)
         * @param array $attr Weitere Attribute fuer das <a> (Anchor = Anker)
         * @return string
         **/
        function getHref($caption='Link', $target=URL_TARGET_SELF, $attr=array())
        {
            $str_attr = '';
            if (is_array($attr)) {
                foreach($attr as $key => $value) {
                    $str_attr .= $key.'="'.$value.'" ';
                }
            }
            $href = '<a href="'.$this->getUrl().'" target="'.$target.'" '.$str_attr.'>'.$caption.'</a>';
            return $href;
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
         * @access public
         * @param boolean $with_sid Session Id uebernehmen
         **/
        function reloadUrl($with_sid=true, $replace=true, $http_response_code=302)
        {
            header('Location: '.$this->getUrl('', $with_sid), $replace, $http_response_code);
            exit;
        }

        /**
         * Laedt das Dokument neu (header redirect)
         *
         * @access public
         * @param boolean $with_sid Session Id uebernehmen
         **/
        function restartUrl($with_sid=true)
        {
            $this->reloadUrl($with_sid);
        }

        /**
         * Geht zu einem neuen Dokument (header redirect). Session via URL wird nicht mitgenommen. Siehe dazu reloadUrl()!
         *
         * @access public
         * @param boolean $absolute verwende absolutee URI
         * @param boolean $replace Der optionale Parameter replace gibt an, ob der Header einen vorhergehenden gleichartigen Header ersetzten soll, oder ob ein zweiter Header des selben Typs hinzugefuegt werden soll.
         * @param integer $http_response_code Forciert einen HTTP-Response-Code des angegebenen Wertes
         **/
        function gotoUrl($absolute=false, $replace=true, $http_response_code=302)
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
         * Loescht alle Url Parameter
         *
         * @access public
         **/
        function clear()
        {
            $this->InpGet->clear();
        }

        /**
         * Aendert einen Parameter des Queries im URI.
         * Der Wert NULL loescht einen Parameter.
         *
         * @access public
         * @param string $key Schluessel / Parameterbezeichnung
         * @param string $value Wert des Parameters
         * @deprecated
         **/
        function modifyQuery($key, $value='')
        {
            $this->InpGet->setVar($key, $value);
        }

        /**
         * Synonym auf Url::modifyQuery()
         *
         * @access public
         * @param string $key Schluessel / Parameterbezeichnung
         * @param string $value Wert des Parameters
         **/
        function setParam($key, $value='')
        {
            $this->InpGet->setVar($key, $value);
        }

        /**
         * Synonym auf Url::modifyQuery()
         *
         * @access public
         * @param string $key Schluessel / Parameterbezeichnung
         * @param string $value Wert des Parameters
         **/
        function modifyParam($key, $value='')
        {
            $this->InpGet->setVar($key, $value);
        }

        /**
         * Setzt einen Anker (Anchor) fuer die aktuelle Url.
         *
         * @access public
         * @param string $value Name des Ankers / Fragments
         **/
        function setAnchor($value = '#')
        {
            if (strlen($value) > 0) {
                if ($value[0] != '#') {
                    $value = '#' . $value;
                }
                $this -> Anchor = $value;
            }
        }
    }
}