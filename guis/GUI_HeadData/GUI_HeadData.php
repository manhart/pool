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

namespace pool\guis\GUI_HeadData;

use pool\classes\Core\Component;
use pool\classes\Core\Http\Request;
use pool\classes\Core\Module;
use pool\classes\Core\Url;
use pool\classes\Core\Weblication;
use pool\classes\Exception\FileNotFoundException;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\GUI\GUI_Module;

use const pool\NAMESPACE_SEPARATOR;

/**
 * Class GUI_HeaderData
 *
 * @package pool\guis\GUI_HeaderData
 * @since 2003-07-10
 */
class GUI_HeadData extends GUI_Module
{
    public const ROBOTS_NOINDEX = 'noindex';    # verbieten Sie einem Suchprogramm, Inhalte aus der HTML-Datei an seine Suchdatenbank zu uebermitteln.
    public const ROBOTS_INDEX = 'index';        # Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (index = Indizierung).
    public const ROBOTS_NOFOLLOW = 'nofollow';    # Damit erlauben Sie einem Suchprogramm, Inhalte aus der aktuellen HTML-Datei an seine Suchdatenbank zu uebermitteln (nofollow = nicht folgen). Sie verbieten dem Suchprogramm jedoch, untergeordnete Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen.
    public const ROBOTS_FOLLOW = 'follow';        # Damit erlauben Sie einem Suchprogramm ausdruecklich, Inhalte aus der aktuellen HTML-Datei und aus untergeordneten Dateien Ihres Projekts, zu denen Verweise fuehren, zu besuchen und an seine Suchdatenbank zu uebermitteln (follow = folgen).
    // @var integer Datei von Originaladresse laden; z.B. 12 Stunden = 43200; (vertraegt auch String siehe Selfhtml)
    // @access private
    var $Expires = 0;

    // @var boolean Anweisung an den Browser: keinen Cache benutzen, sondern von Originalseite laden
    // @access private
    var $BrowserNoCache = true;

    /**
     * Control the proxy cache
     *
     * @var bool
     */
    private bool $proxyNoCache = true;

    /**
     * meta refresh
     *
     * @var array
     */
    private array $metaRefresh = [];

    /**
     * @var string
     */
    private string $title = 'Unknown page title';

    // @var string Beschreibung des Html Dokuments (Seite)
    private string $description = '';

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
     *
     * @var string
     */
    private string $baseTarget = '_self';

    /**
     * base href
     *
     * @var string
     */
    private string $baseHref;

    /**
     * X-UA-Compatible Meta Tag
     *
     * @var string
     */
    private string $xuaCompatible = '';

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
     * data for the client
     *
     * @var array
     */
    private array $clientData = [];

    /**
     * Set the default values
     */
    public function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner, $params);
        $this->baseHref = Request::phpSelf();
    }

    /**
     * loads the template (Html Kopfdaten)
     */
    public function loadFiles(): static
    {
        $file = $this->Weblication->findTemplate('tpl_headData.html', 'GUI_HeadData', true);
        $this->Template->setFilePath('head', $file);
        return $this;
    }

    /**
     * Setzt den X-UA-Compatbile Meta Tag, um den Browser den Standard-Rendermode vorzugeben.
     *
     * @param string $xuaCompatible
     */
    public function setXuaCompatible(string $xuaCompatible)
    {
        $this->xuaCompatible = $xuaCompatible;
    }

    /**
     * Setzt die Sekunden, wann der Browser die Datei von der Originaldatei laden soll (und nicht aus dem Cache).
     * z.B. 12 Stunden = 43200; (vertraegt auch String siehe Selfhtml)
     *
     * @access public
     * @param integer $expire Anzahl in Sekunden. 0 bedeutet der Browser muss immer von der Originaldatei laden
     */
    public function setExpires($expire)
    {
        $this->Expires = $expire;
    }

    /**
     * Teilt dem Browser mit, dass er keinen Cache verwenden soll (je nach Browserinterpretation gleich zu expire=0)
     *
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     */
    function setBrowserNoCache($bValue)
    {
        $this->BrowserNoCache = $bValue;
    }

    /**
     * Teilt einem Proxy mit, dass er keinen Cache verwenden soll (pragma)
     *
     * @param boolean $bValue Wahr NoCache, Falsch mit Cache
     */
    public function setProxyNoCache(bool $bValue): static
    {
        $this->proxyNoCache = $bValue;
        return $this;
    }

    /**
     * Setzt den Seitentitel und MetaTags!
     *
     * @param string $title Titel (darf nicht leer sein; Titel muss vorhanden sein)
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @param string $description
     * @return void
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Setzt Content-Charset
     *
     * @param string $charset Zeichensatz
     */
    public function setCharset(string $charset): static
    {
        $this->charset = $charset;
        return $this;
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
     * Gibt Suchmaschinen Robots Anweisungen, was er auf dieser Seite tun soll. Siehe head.class.php Konstanten im oberen Bereich!!
     * z.b. Indexierung oder keine Indexierung, Follow etc.
     *
     * @param string $sRobots Uebergabe von ROBOT_ Konstanten
     **/
    function setRobots(string $sRobots)
    {
        $this->robots = $sRobots;
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

    public function addClientWebAsset(string $extension, string $file, ?string $classFolder = null, bool $baseLib = false, bool $isOptional = false): ?static {
        $weblication = Weblication::getInstance();
        $classFolder ??= $file;
        [$file] = array_reverse(explode(NAMESPACE_SEPARATOR, $file));
        [$classFolder] = array_reverse(explode(NAMESPACE_SEPARATOR, $classFolder));
        /* @noinspection PhpDeprecationInspection */
        $filePath = match ($extension) {
            'css' => $weblication->findStyleSheet("$file.$extension", $classFolder, $baseLib, false),
            'js' => $weblication->findJavaScript("$file.$extension", $classFolder, $baseLib, false),
            default => throw new InvalidArgumentException("Unhandled web-asset extension '$extension'"),
        };
        if (!$filePath && !$isOptional) throw new FileNotFoundException("Could not find the web-asset $file.$extension");
        elseif (!$filePath) return $this;
        match ($extension) {
            'css' => $this->addStyleSheet($filePath),
            'js' => $this->addJavaScript($filePath),
            default => throw new InvalidArgumentException("Unhandled web-asset extension '$extension'"),
        };
        return $this;
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
        if ($file == '') return $this;
        if ($this->addFileFct) $file = call_user_func($this->addFileFct, $file);
        if (in_array($file, $this->styleSheetFiles)) return $this;
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
        if ($file === '') {
            return $this;
        }
        if (isset($this->javaScriptFiles[$file])) {
            return $this;
        }

        $originalFile = $file;
        // $fileName = basename(strtok($file, '?'));

        if ($this->addFileFct) {
            $file = call_user_func($this->addFileFct, $file);
        }

        $js = [
            'file' => $file,
            'originalFile' => $originalFile,
        ];

        if ($attributes) {
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
     * @param Module $Module
     * @param array|null $initOptions
     * @param string|null $jsClassName if null, the Module's class name is used
     * @return GUI_HeadData
     */
    public function setClientData(Module $Module, ?array $initOptions = null, ?string $jsClassName = null): self
    {
        $clientData =& $this->clientData[$Module->getName()];
        $clientData ??= [
            'className' => $jsClassName ?? $Module->getClassName(),
            'fullyQualifiedClassName' => $Module::class,
            'parentModuleName' => $Module->getParent()?->getName(),
        ];
        if (!$initOptions) return $this;
        $existingInitOptions =& $clientData['initOptions'];
        $existingInitOptions ??= [];
        $existingInitOptions += $initOptions;
        return $this;
    }

    /**
     * Gibt die fertigen Html Kopfdaten zurueck.
     *
     * @return string Content (Kopfdaten)
     */
    public function finalize(): string
    {
        $Url = new Url(false);

        $this->Template->setVars([
            'EXPIRES' => $this->Expires,
            'LANGUAGE' => $this->Weblication->getLanguage(),
            'TITLE' => $this->title,
            'DESCRIPTION' => $this->description,
            'ROBOTS' => $this->robots,
            'BASE_HREF' => $this->baseHref,
            'BASE_TARGET' => $this->baseTarget,
            'CHARSET' => $this->charset,
            'KEYWORDS' => '',
            'AUTHOR' => '',
            'CLIENT-DATA' => base64_encode(
                json_encode(
                    $this->clientData,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
                ),
            ),
            'SCRIPT' => $Url->getUrl(),
        ],
        );

        if ($this->xuaCompatible !== '') {
            if ($this->Template->newBlock('XUACOMPATIBLE')) {
                $this->Template->setVar('XUACOMPATIBLE_VALUE', $this->xuaCompatible);
            }
            $this->Template->leaveBlock();
        }

        if ($this->BrowserNoCache) {
            $this->Template->newBlock('BROWSERNOCACHE');
        }

        if ($this->proxyNoCache) {
            $this->Template->newBlock('PROXYNOCACHE');
        }

        if ($this->metaRefresh) {
            $this->Template->newBlock('METAREFRESH');
            $this->setVar('REFRESH', $this->metaRefresh['seconds']);
            $this->setVar('URL', $this->metaRefresh['url']);
        }

        $z = 0;
        foreach ($this->styleSheetFiles as $css) {
            $this->Template->newBlock('STYLESHEET');
            $this->Template->setVar('FILENAME', $css);
            if (!is_null($this->styleSheetsMedia[$z])) { // Media
                $this->Template->setVar('MEDIA', ' media="'.$this->styleSheetsMedia[$z].'"');
            } else {
                $this->Template->setVar('MEDIA');
            }
            $z++;
        }

        foreach ($this->javaScriptFiles as $js) {
            $this->Template->newBlock('JAVASCRIPT');
            $this->Template->setVar('FILENAME', $js['file']);
            $attributes = '';
            if (isset($js['attributes'])) {
                $attributes = buildHtmlAttributes($js['attributes']);
            }
            $this->Template->setVar('attributes', $attributes);
        }

        if (count($this->scriptCode) > 0) {
            $this->Template->newBlock('INLINE_SCRIPT_CODE');
            foreach ($this->scriptCode as $name => $code) {
                $ScriptBlock = $this->Template->newBlock('SCRIPT_CODE');
                if ($ScriptBlock) {
                    $ScriptBlock->setVar('NAME', $name);
                    $ScriptBlock->setVar('CODE', $code);
                } elseif ($this->Weblication->isXdebugEnabled()) {
                    /** @noinspection ForgottenDebugOutputInspection */
                    xdebug_print_function_stack('SCRIPT_CODE is missing in tpl_head.html');
                }
            }
        }

        if (file_exists('favicon.ico')) {
            $this->Template->newBlock('favicon');
        }

        $this->Template->parse();
        return rtrim($this->Template->getContent());
    }
}
