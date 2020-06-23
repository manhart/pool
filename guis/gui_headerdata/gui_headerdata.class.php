<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * Headerdata.class.php
 *
 * Kopfdaten fuer ein Html Dokument.
 *
 * @version $Id: gui_headerdata.class.php,v 1.6 2007/08/09 10:23:06 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

 define('ROBOTS_NOINDEX', 'noindex');	# verbieten Sie einem Suchprogramm, Inhalte aus der HTML-Datei an seine Suchdatenbank zu uebermitteln.
 define('ROBOTS_INDEX', 'index');		# Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (index = Indizierung).
 define('ROBOTS_NOFOLLOW', 'nofollow');	# Damit erlauben Sie einem Suchprogramm, Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (nofollow = nicht folgen). Sie verbieten dem Suchprogramm jedoch, untergeordnete Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen.
 define('ROBOTS_FOLLOW', 'follow');		# Damit erlauben Sie einem Suchprogramm ausdruecklich, Inhalte aus der aktuellen HTML-Datei und aus untergeordneten Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen und an seine Suchdatenbank zu uebermitteln (follow = folgen).

 /**
  * Class GUI_Headerdata
  *
  * Objekt fuer die Html Kopfdaten (<head>kopfdaten</head>).
  *
  * @package pool
  * @author Alexander Manhart <alexander.manhart@freenet.de>
  * @version $Id: gui_headerdata.class.php,v 1.6 2007/08/09 10:23:06 manhart Exp $
  * @access public
  **/
 class GUI_Headerdata extends GUI_Module
 {
    // @var integer Datei von Originaladresse laden; z.B. 12 Stunden = 43200; (vertraegt auch String siehe Selfhtml)
    // @access private
    var $Expires = 0;

    // @var string Sprache des Dateiinhalts (HTTP 1.0 und RFC1766)
    // @access private
    var $ContentLanguage = 'de';

    // @var boolean Anweisung an den Browser: keinen Cache benutzen, sondern von Originalseite laden
    // @access private
    var $BrowserNoCache = true;

    // @var boolean An Proxy-Agenten: Datei bitte nicht auf Proxy-Server speichern!
    // @access private
    var $ProxyNoCache = true;

    // @var array [seconds] [url] Automatische Weiterleitung zu anderer Adresse (Forwarding)
    // @access private
    var $MetaRefresh = array();

    // @var string Seiten-Titel
    // @access private
    var $Title = 'Title not set';

    // @var string Beschreibung des Html Dokuments (Seite)
    // @access private
    var $Description = '';

    // @var string Suchmaschinenen-Robot Anweisungen
    // @access private
    var $Robots = ROBOTS_NOFOLLOW;

    /**
     * StyleSheet Files
     *
     * @var array
     */
    var $StyleSheets = array();

    /**
     * Media for StyleSheets
     *
     * @var array
     */
    var $StyleSheetsMedia = array();

     /**
      * @var array javascript files with properties
      */
    private array $javaScripts = array();

     /**
      * @var array javascript file names to prevent double inclusion
      */
    private array $javaScriptFiles = array();

    //@var string Base Target
    //@access private
    var $Base_Target = '_top';

    /**
     * X-UA-Compatible Meta Tag
     *
     * @var string
     */
    var $xuaCompatible = '';

    /**
     * Zeichensatz im Header einer HTML Datei
     *
     * @var string $charset Zeichensatz
     */
    var $charset = 'ISO-8859-1';

    /**
     * GUI_Headerdata::GUI_Headerdata()
     *
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer vom Typ Component
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);

        $php_default_charset = ini_get('default_charset');
        if(strlen($php_default_charset) > 0) {
            $this->charset = strtoupper($php_default_charset);
        }
    }

    /**
     * GUI_Headerdata::LoadFiles()
     *
     * Laedt das headerdata.html Template (Html Kopfdaten)
     *
     * @access private
     **/
    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_headerdata.html', 'gui_headerdata', true);
        $this -> Template -> setFilePath('headerdata', $file);
    }

    /**
     * GUI_Headerdata::setExpires()
     *
     * Setzt die Sekunden, wann der Browser die Datei von der Originaldatei laden soll (und nicht aus dem Cache).
     * z.B. 12 Stunden = 43200; (vertraegt auch String siehe Selfhtml)
     *
     * @access public
     * @param integer $expire Anzahl in Sekunden. 0 bedeutet der Browser muss immer von der Originaldatei laden
     **/
    function setExpires($expire)
    {
        $this -> Expires = $expire;
    }

    /**
     * GUI_Headerdata::setBrowserNoCache()
     *
     * Teilt dem Browser mit, dass er keinen Cache verwenden soll (je nach Browserinterpretation gleich zu expire=0)
     *
     * @access public
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     **/
    function setBrowserNoCache($bValue)
    {
        $this -> BrowserNoCache = $bValue;
    }

    /**
     * GUI_Headerdata::setProxyNoCache()
     *
     * Teilt einem Proxy mit, dass er keinen Cache verwenden soll (pragma)
     *
     * @access public
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     **/
    function setProxyNoCache($bValue)
    {
        $this -> ProxyNoCache = $bValue;
    }

    /**
     * GUI_Headerdata::setTitle()
     *
     * Setzt den Seitentitel und MetaTags!
     *
     * @access public
     * @param string $sTitle Titel (darf nicht leer sein; Titel muss vorhanden sein)
     **/
    function setTitle($sTitle)
    {
        if (!empty($sTitle)) {
            $this -> Title = $sTitle;
        }
    }

    /**
     * Setzt Content-Charset
     *
     * @param string $charset Zeichensatz
     */
    function setCharset($charset='ISO-8859-1')
    {
        $this -> charset = $charset;
    }

    /**
     * GUI_Headerdata::getTitle()
     *
     * Gibt den gesetzten Seitentitel wieder zurueck
     *
     * @access public
     * @return string Titel der Seite
     **/
    function getTitle()
    {
        return $this -> Title;
    }

    /**
     * GUI_Headerdata::setDescription()
     *
     * Setzt einen Beschreibungstext fuer Suchmaschinen
     *
     * @access public
     * @param string $sDescription
     **/
    function setDescription($sDescription)
    {
        $this -> Description = $sDescription;
    }

    /**
     * GUI_Headerdata::setRobots()
     *
     * Gibt Suchmaschinen Robots Anweisungen, was er auf dieser Seite tun soll. Siehe Headerdata.class.php Konstanten im oberen Bereich!!
     * z.b. Indexierung oder keine Indexierung, Follow etc.
     *
     * @access public
     * @param string $sRobots Uebergabe von ROBOT_ Konstanten
     **/
    function setRobots($sRobots)
    {
        $this -> Robots = $sRobots;
    }

    /**
     * GUI_Headerdata::setLanguage()
     *
     * Setzt die Sprache fuer die Seite
     *
     * @access public
     * @param string $lang
     **/
    function setLanguage($lang)
    {
        $this -> ContentLanguage = $lang;
    }

    /**
     * Diese Funktion setzt einen MetaRefresh auf die Seite.
     *
     * @access public
     * @param integer $seconds Sekunden in den ein Refresh gemacht werden soll
     * @param string $url Auf welche Url weitergeleitet werden soll
     **/
    function setMetaRefresh($seconds, $url)
    {
        $this -> MetaRefresh['seconds'] = $seconds;
        $this -> MetaRefresh['url'] = $url;
    }

    /**
     * Fuegt der Seite eine StyleSheet Datei (.css) hinzu.
     *
     * @access public
     * @param string $filename
     **/
    function addStyleSheet($filename, $media=null)
    {
        if (!in_array($filename, $this->StyleSheets)) {
            $this->StyleSheets[] = $filename;
            $this->StyleSheetsMedia[count($this->StyleSheets)-1] = $media;
        }
    }

    /**
     * Add a javascript file to the page
     *
     * @access public
     * @param string $file file
     * @param string $type (optional) type
     * @param array $dataset
     **/
    function addJavaScript($file, $type='', $dataset=[])
    {
        if (!in_array($file, $this->javaScriptFiles)) {
            $js = array(
                'file' => $file
            );
            if($type != '') {
                $js['type'] = $type;
            }
            if($dataset) {
                $js['dataset'] = $dataset;
            }
            $this->javaScripts[] = $js;
            $this->javaScriptFiles[] = $file;
        }
    }

    function setBaseTarget($target='_top')
    {
        $this -> Base_Target = $target;
    }

    /**
     * Setzt den X-UA-Compatbile Meta Tag, um den Browser den Standard-Rendermode vorzugeben.
     *
     * @param string $xuaCompatible
     */
    function setXuaCompatible($xuaCompatible)
    {
        $this->xuaCompatible = $xuaCompatible;
    }

    /**
     * GUI_Headerdata::prepare()
     *
     * Bereitet die Html Kopfdaten vor.
     *
     * @access public
     **/
    function prepare()
    {
        $Url = new Url(I_EMPTY);
        $this->Template->setVar(
            array(
                'EXPIRES' => $this -> Expires,
                'LANGUAGE' => $this -> ContentLanguage,
                'TITLE' => $this -> Title,
                'DESCRIPTION' => $this -> Description,
                'ROBOTS' => $this -> Robots,
                'BASE_TARGET' => $this -> Base_Target,
                'CHARSET' => $this -> charset,
                'SCRIPT' => $Url->getUrl()
            )
        );

        if($this->xuaCompatible != '') {
            if($this->Template->newBlock('XUACOMPATIBLE')) {
                $this->Template->setVar('XUACOMPATIBLE_VALUE', $this->xuaCompatible);
            }
            $this->Template->leaveBlock();
        }

        if ($this -> BrowserNoCache) {
            $this -> Template -> newBlock('BROWSERNOCACHE');
        }

        if ($this -> ProxyNoCache) {
            $this -> Template -> newBlock('PROXYNOCACHE');
        }

        if (count($this -> MetaRefresh) > 0) {
            $this -> Template -> newBlock('METAREFRESH');
            $this -> setVar('REFRESH', $this -> MetaRefresh['seconds']);
            $this -> setVar('URL', $this -> MetaRefresh['url']);
        }

        if (count($this->StyleSheets) > 0) {
            $z = 0;
            foreach($this->StyleSheets as $css) {
                $this->Template->newBlock('STYLESHEET');
                $this->Template->setVar('FILENAME', $css);
                if(!is_null($this->StyleSheetsMedia[$z])) { // Media
                    $this->Template->setVar('MEDIA', ' media="'.$this->StyleSheetsMedia[$z].'"');
                }
                else {
                    $this->Template->setVar('MEDIA', '');
                }
                $z++;
            }
        }

        if (count($this->javaScripts) > 0) {
            foreach($this->javaScripts as $js) {
                $this->Template->newBlock('JAVASCRIPT');
                $this->Template->setVar('FILENAME', $js['file']);
                $type = '';
                if(isset($js['type'])) {
                    $type = ' type="'.$js['type'].'"';
                }
                $this->Template->setVar('TYPE', $type);
            }
        }

        if(file_exists('favicon.ico')) $this->Template->newBlock('favicon');
    }

    /**
     * GUI_Headerdata::finalize()
     *
     * Gibt die fertigen Html Kopfdaten zurueck.
     *
     * @access public
     * @return string Content (Kopfdaten)
     **/
    function finalize()
    {
        $this -> Template -> parse();
        return $this -> Template -> getContent();
    }
 }