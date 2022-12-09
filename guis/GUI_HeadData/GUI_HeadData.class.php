<?php
/*
 * g7system.local
 *
 * GUI_HeaderData.class.php created at 24.11.22, 23:43
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

/**
 * -= Rapid Module Library (RML) =-
 *
 * Header.class.php
 *
 * Kopfdaten fuer ein Html Dokument (<head>kopfdaten</head>)..
 *
 * @version $Id: GUI_HeaderData.class.php,v 1.6 2007/08/09 10:23:06 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

class GUI_HeadData extends GUI_Module
{
    const ROBOTS_NOINDEX = 'noindex';    # verbieten Sie einem Suchprogramm, Inhalte aus der HTML-Datei an seine Suchdatenbank zu uebermitteln.
    const ROBOTS_INDEX = 'index';        # Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (index = Indizierung).
    const ROBOTS_NOFOLLOW = 'nofollow';    # Damit erlauben Sie einem Suchprogramm, Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (nofollow = nicht folgen). Sie verbieten dem Suchprogramm jedoch, untergeordnete Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen.
    const ROBOTS_FOLLOW = 'follow';        # Damit erlauben Sie einem Suchprogramm ausdruecklich, Inhalte aus der aktuellen HTML-Datei und aus untergeordneten Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen und an seine Suchdatenbank zu uebermitteln (follow = folgen).

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

    /**
     * meta refresh
     * @var array
     */
    private array $metaRefresh = [];

    /**
     * @var string
     */
    private string $title = 'Unknown page title';

    // @var string Beschreibung des Html Dokuments (Seite)
    // @access private
    var $Description = '';

    // @var string Suchmaschinenen-Robot Anweisungen
    private string $robots = self::ROBOTS_NOFOLLOW;

    /**
     * StyleSheet Files
     *
     * @var array
     */
    private array $styleSheetFiles = [];

    /**
     * Media for StyleSheets
     *
     * @var array
     */
    private array $styleSheetsMedia = [];

    /**
     * @var array javascript file names to prevent double inclusion
     */
    private array $javaScriptFiles = [];

    //@var string Base Target

    /**
     * base target: _blank, _self (browser default), _parent, _top
     * @var string
     */
    private string $baseTarget = '_self';

    /**
     * base href
     * @var string
     */
    private string $baseHref;

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
    private string $charset = 'UTF-8';

    /**
     * @var array JavaScript Code
     */
    private array $scriptCode = [];

    /**
     * @var array|callable|null
     */
    private $addFileFct = null;

    /**
     * Konstruktor
     *
     * @param object $Owner Besitzer vom Typ Component
     * @param array $params
     */
    function __construct($Owner, array $params = [])
    {
        parent::__construct($Owner, $params);

        $this->baseHref = $_SERVER['PHP_SELF'];

        $php_default_charset = ini_get('default_charset');
        if($php_default_charset) {
            $this->charset = strtoupper($php_default_charset);
        }
    }

    /**
     * loads the template (Html Kopfdaten)
     */
    public function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_headData.html', __CLASS__, true);
        $this->Template->setFilePath('head', $file);
    }

    /**
     * Setzt die Sekunden, wann der Browser die Datei von der Originaldatei laden soll (und nicht aus dem Cache).
     * z.B. 12 Stunden = 43200; (vertraegt auch String siehe Selfhtml)
     *
     * @access public
     * @param integer $expire Anzahl in Sekunden. 0 bedeutet der Browser muss immer von der Originaldatei laden
     **/
    public function setExpires($expire)
    {
        $this->Expires = $expire;
    }

    /**
     * Teilt dem Browser mit, dass er keinen Cache verwenden soll (je nach Browserinterpretation gleich zu expire=0)
     *
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     **/
    function setBrowserNoCache($bValue)
    {
        $this->BrowserNoCache = $bValue;
    }

    /**
     * Teilt einem Proxy mit, dass er keinen Cache verwenden soll (pragma)
     *
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     **/
    function setProxyNoCache($bValue)
    {
        $this->ProxyNoCache = $bValue;
    }

    /**
     * Setzt den Seitentitel und MetaTags!
     *
     * @param string $sTitle Titel (darf nicht leer sein; Titel muss vorhanden sein)
     **/
    function setTitle(string $sTitle)
    {
        $this->title = $sTitle;
    }

    /**
     * Setzt Content-Charset
     *
     * @param string $charset Zeichensatz
     */
    function setCharset(string $charset)
    {
        $this->charset = $charset;
    }

    /**
     * Gibt den gesetzten Seitentitel wieder zurueck
     *
     * @return string Titel der Seite
     **/
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Setzt einen Beschreibungstext fuer Suchmaschinen
     *
     * @param string $sDescription
     **/
    function setDescription(string $sDescription)
    {
        $this->Description = $sDescription;
    }

    /**
     * Gibt Suchmaschinen Robots Anweisungen, was er auf dieser Seite tun soll. Siehe head.class.php Konstanten im oberen Bereich!!
     * z.b. Indexierung oder keine Indexierung, Follow etc.
     *
     * @param string $sRobots Uebergabe von ROBOT_ Konstanten
     **/
    function setRobots($sRobots)
    {
        $this->robots = $sRobots;
    }

    /**
     * Setzt die Sprache fuer die Seite
     *
     * @param string $lang
     **/
    function setLanguage(string $lang)
    {
        $this->ContentLanguage = $lang;
    }

    /**
     * Diese Funktion setzt einen MetaRefresh auf die Seite.
     *
     * @param integer $seconds Sekunden in den ein Refresh gemacht werden soll
     * @param string $url Auf welche Url weitergeleitet werden soll
     **/
    function setMetaRefresh($seconds, $url)
    {
        $this->metaRefresh['seconds'] = $seconds;
        $this->metaRefresh['url'] = $url;
    }

    /**
     * Add stylesheet file to the page
     *
     * @param string $file
     * @param null $media
     * @return GUI_HeadData
     */
    public function addStyleSheet(string $file, $media = null): self
    {
        if($file == '') return $this;
        if($this->addFileFct) $file = call_user_func($this->addFileFct, $file);
        if(in_array($file, $this->styleSheetFiles)) return $this;
        $this->styleSheetFiles[] = $file;
        $this->styleSheetsMedia[count($this->styleSheetFiles) - 1] = $media;
        return $this;
    }

    /**
     * Add a javascript file to the page
     *
     * @param string $file file
     * @param array $attributes (optional)
     * @return GUI_HeadData
     */
    public function addJavaScript(string $file, array $attributes = []): self
    {
        if($file == '') {
            return $this;
        }
        if(isset($this->javaScriptFiles[$file])) {
            return $this;
        }

        $originalFile = $file;
        // $fileName = basename(strtok($file, '?'));

        if($this->addFileFct) {
            $file = call_user_func($this->addFileFct, $file);
        }

        $js = [
            'file' => $file,
            'originalFile' => $originalFile
        ];

        if($attributes) {
            $js['attributes'] = $attributes;
        }
        $this->javaScriptFiles[$originalFile] = $js;
        return $this;
    }

    /**
     * @param callable $fct
     * @return GUI_HeadData
     */
    public function onAddFile(callable $fct): self
    {
        $this->addFileFct = $fct;
        return $this;
    }

    /**
     * adds JavaScript to the head
     *
     * @param string $name with a unique name, it is possible to overwrite code
     * @param string $code javaScript source code
     */
    public function addScriptCode(string $name, string $code)
    {
        $this->scriptCode[$name] = $code;
    }

    /**
     * @param string $href
     * @param string $target
     * @return GUI_HeadData
     */
    public function setBaseTarget(string $href, string $target = '_top'): self
    {
        $this->baseHref = $href;
        $this->baseTarget = $target;
        return $this;
    }

    /**
     * Setzt den X-UA-Compatbile Meta Tag, um den Browser den Standard-Rendermode vorzugeben.
     *
     * @param string $xuaCompatible
     */
    function setXuaCompatible(string $xuaCompatible)
    {
        $this->xuaCompatible = $xuaCompatible;
    }

    /**
     * Gibt die fertigen Html Kopfdaten zurueck.
     *
     * @return string Content (Kopfdaten)
     */
    public function finalize(): string
    {
        $Url = new Url(I_EMPTY);

        $this->Template->setVars(
            array(
                'EXPIRES' => $this->Expires,
                'LANGUAGE' => $this->ContentLanguage,
                'TITLE' => $this->title,
                'DESCRIPTION' => $this->Description,
                'ROBOTS' => $this->robots,
                'BASE_HREF' => $this->baseHref,
                'BASE_TARGET' => $this->baseTarget,
                'CHARSET' => $this->charset,
                'SCRIPT' => $Url->getUrl()
            )
        );

        if($this->xuaCompatible != '') {
            if($this->Template->newBlock('XUACOMPATIBLE')) {
                $this->Template->setVar('XUACOMPATIBLE_VALUE', $this->xuaCompatible);
            }
            $this->Template->leaveBlock();
        }

        if($this->BrowserNoCache) {
            $this->Template->newBlock('BROWSERNOCACHE');
        }

        if($this->ProxyNoCache) {
            $this->Template->newBlock('PROXYNOCACHE');
        }

        if(count($this->metaRefresh) > 0) {
            $this->Template->newBlock('METAREFRESH');
            $this->setVar('REFRESH', $this->metaRefresh['seconds']);
            $this->setVar('URL', $this->metaRefresh['url']);
        }

        $z = 0;
        foreach($this->styleSheetFiles as $css) {
            $this->Template->newBlock('STYLESHEET');
            $this->Template->setVar('FILENAME', $css);
            if(!is_null($this->styleSheetsMedia[$z])) { // Media
                $this->Template->setVar('MEDIA', ' media="' . $this->styleSheetsMedia[$z] . '"');
            }
            else {
                $this->Template->setVar('MEDIA', '');
            }
            $z++;
        }

        foreach($this->javaScriptFiles as $js) {
            $this->Template->newBlock('JAVASCRIPT');
            $this->Template->setVar('FILENAME', $js['file']);
            $attributes = '';
            if(isset($js['attributes'])) {
                $attributes = htmlAttributes($js['attributes']);
            }
            $this->Template->setVar('attributes', $attributes);
        }

        if(count($this->scriptCode) > 0) {
            foreach($this->scriptCode as $name => $code) {
                $ScriptBlock = $this->Template->newBlock('SCRIPT_CODE');
                if($ScriptBlock) {
                    $ScriptBlock->setVar('NAME', $name);
                    $ScriptBlock->setVar('CODE', $code);
                }
                else {
                    if($this->Weblication->isXdebugEnabled()) {
                        xdebug_print_function_stack('SCRIPT_CODE is missing in tpl_head.html');
                    }
                }
            }
        }

        if(file_exists('favicon.ico')) {
            $this->Template->newBlock('favicon');
        }

        $this->Template->parse();
        return $this->Template->getContent();
    }
}