<?php
declare(strict_types=1);

/**
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * Weblication.class.php
 *
 * Die Hauptklasse aller Webanwendungen. Jedes neue Projekt beginnt mit Weblication und wird davon instanziert.
 *
 * @version $Id: Weblication.class.php,v 1.16 2007/05/31 14:36:23 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\ModulNotFoundException;
use pool\classes\translator\TranslationProviderFactory_ResourceFile;
use pool\classes\translator\Translator;

class Weblication extends Component
{
    /**
     * Titel der Weblication
     *
     * @var string
     */
    private string $title = '';

    /**
     * @var string class name of the module that is started as main module
     */
    protected string $launchModule = 'GUI_CustomFrame';

    /**
     * Enthaelt das erste geladene GUI_Module (wird in Weblication::run() eingeleitet)
     *
     * @var GUI_Module $Main
     */
    private GUI_Module $Main;

    /**
     * @var GUI_CustomFrame|null
     */
    private ?GUI_CustomFrame $Frame = null;

    /**
     * PHP Session gekapselt in ISession
     *
     * @var ISession|null $Session
     */
    public ?ISession $Session = null;

    /**
     * @var Weblication|null
     */
    private static ?Weblication $Instance = null;

    /**
     * @var int filter that defines which superglobals are passed to input->vars
     */
    private int $superglobals = I_EMPTY;

    /**
     * @var Input
     */
    public Input $Input;

    /**
     * Benutzer Klasse (nicht realisiert)
     *
     * @var User
     * @access private
     */
    // var $User = null;

    /**
     * Relativer Pfad zur Hauptbibliothek
     *
     * @var string
     */
    private string $relativePathBaselib = '';

    /**
     * Skin / Theme (Designvorlage bzw. Bilderordner)
     *
     * @var string
     */
    private string $skin = 'default';

    /**
     * @var array|null
     */
    private ?array $skins = null;

    /**
     * Schema / Layout (index ist das Standard-Schema)
     *
     * @var string
     */
    protected string $schema = 'index';

    /**
     * Bewahrt alle Schnittstellen Instanzen der unterschiedlichsten Speichermedien als Liste auf
     *
     * @var array
     */
    private array $interfaces = [];

    /**
     * Zeichensatz
     *
     * @var string
     */
    private string $charset = '';

    /**
     * Programm ID
     *
     * @var int
     */
    private int $progId = 0;

    /**
     * @var string
     */
    private string $cssFolder = 'css';

    /**
     * @var Input App Settings
     */
    protected Input $Settings;

    /**
     * @var bool|null xdebug enabled
     */
    private ?bool $xdebug = null;

    /**
     * @var string
     */
    private string $commonSkinFolder = 'common';

    /**
     * Stores file accesses temporarily. Prevents many file accesses
     *
     * @var array|null
     */
    private ?array $hasCommonSkinFolder = null;

    /**
     * Stores file accesses temporarily. Prevents many file accesses
     *
     * @var array
     */
    private array $hasSkinFolder = array();

    /**
     * @var string Country code
     */
    private string $language = 'de';

    /**
     * @var string
     */
    private string $locale = '';

    /**
     * @var string
     */
    private string $subdirTranslated = '';

    /**
     * @var string version of the application
     */
    private string $version = '';

    /**
     * Cookie for the application
     *
     * @var ICookie|null
     */
    private ?ICookie $Cookie = null;

    /**
     * @var array all possible default formats
     */
    private array $formats = [
        'php.time' => 'H:i',
        'php.date' => 'd.m.Y',
        'php.sec' => 's',
        'php.date.time' => 'd.m.Y H:i',
        'php.date.time.sec' => 'd.m.Y H:i:s',
        'strftime.time' => '%H:%M',
        'strftime.date' => '%d.%m.%Y',
        'strftime.date.time' => '%d.%m.%Y %H:%M',
        'strftime.date.time.sec' => '%d.%m.%Y %H:%M:%S',
        'moment.date' => 'DD.MM.YYYY',
        'moment.date.time' => 'DD.MM.YYYY HH:mm',
        'moment.date.time.sec' => 'DD.MM.YYYY HH:mm:ss',
        'mysql.date_format.date' => '%d.%m%.%Y',
        'mysql.date_format.date.time' => '%d.%m%.%Y %H:%i',
        'mysql.date_format.date.time.sec' => '%d.%m%.%Y %T',
        'number' => [
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => '.'
        ]
    ];

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        parent::__construct(null);

        $this->Settings = new Input();
        return $this;
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): Weblication
    {
        static::$Instance ??= new static();
        return static::$Instance;
    }

    /**
     * @return bool
     */
    public static function hasInstance(): bool
    {
        return (static::$Instance !== null);
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
    }

    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * Aendert den Ordner fuer die Designvorlagen (Html Templates) und Bilder.
     *
     * @param string $skin Ordner fuer die Designvorlagen (Html Templates) und Bilder. (Standardwert: default)
     * @return Weblication
     */
    public function setSkin(string $skin = 'default'): Weblication
    {
        $this->skin = $skin;
        return $this;
    }

    /**
     * Liefert den Ordner (Namen) der aktuellen Designvorlagen und Bilder zurueck.
     * (wird derzeit fuer die Bilder und Html Templates missbraucht)
     *
     * @return string Name des Designs (Skin)
     **/
    public function getSkin(): string
    {
        return $this->skin;
    }

    /**
     * Sets the main language for the Page. It's used for html templates and images
     *
     * @param string $lang Country Code
     * @return Weblication
     */
    public function setLanguage(string $lang = 'de'): self
    {
        $this->language = $lang;
        return $this;
    }

    /**
     * Liefert die Sprache der Seite.
     *
     * @return string Sprache der Webseite
     **/
    public function getLanguage(): string
    {
        return $this->language; // $this->language;
    }

    /**
     * Get translator
     *
     * @return Translator
     */
    public function getTranslator(): Translator
    {
        if(!isset($this->translator)) {
            $this->setTranslator(new Translator());
        }
        return $this->translator;
    }

    public function setTranslator(Translator $translator): static
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Liefert den Zeichensatz der Webanwendung zurueck
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set charset for the Web Application
     *
     * @param string $charset
     * @return Weblication
     */
    public function setCharset(string $charset): Weblication
    {
        header('content-type: text/html; charset=' . $charset);
        $this->charset = $charset;
        return $this;
    }

    /**
     * all kinds of formats.There are predefined ones: datetime, date and time
     *
     * @param array $formats
     * @return $this
     */
    public function setDefaultFormats(array $formats): Weblication
    {
        $this->formats = array_merge($this->formats, $formats);
        return $this;
    }

    /**
     * reads the saved format
     *
     * @param string $key
     * @return string|array
     */
    public function getDefaultFormat(string $key): array|string
    {
        return $this->formats[$key] ?? '';
    }

    /**
     * Setzt die Programm ID
     *
     * @param int $progId
     * @return Weblication
     */
    function setProgId(int $progId): Weblication
    {
        $this->progId = $progId;
        return $this;
    }

    /**
     * Liefert die Programm ID
     *
     * @return int|null
     */
    function getProgId(): ?int
    {
        return $this->progId;
    }

    /**
     * set default schema/layout, if none is loaded by request
     *
     * @param string $default
     * @return Weblication
     */
    public function setDefaultSchema(string $default = 'index'): Weblication
    {
        $this->schema = $default;
        return $this;
    }

    /**
     * returns the default scheme
     *
     * @return string default schema
     **/
    public function getDefaultSchema(): string
    {
        return $this->schema;
    }

    /**
     * determines current schema/layout
     *
     * @return string
     */
    public function getSchema(): string
    {
        return (isset($_REQUEST['schema']) and $_REQUEST['schema'] != '') ? $_REQUEST['schema'] : $this->getDefaultSchema();
    }

    /**
     * @param string $version application version
     * @return Weblication
     */
    public function setVersion(string $version): Weblication
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string returns application version
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Setzt das Haupt-GUI.
     *
     * @param GUI_Module $GUI_Module
     */
    public function setMain(GUI_Module $GUI_Module)
    {
        $this->Main = $GUI_Module;
    }

    /**
     * Liefert das Haupt-GUI (meistens erstes GUI, das im Startscript �bergeben wurde).
     *
     * @return GUI_Module
     */
    public function getMain(): GUI_Module
    {
        return $this->Main;
    }

    /**
     * @return GUI_HeadData|null
     */
    public function getHead(): ?GUI_HeadData
    {
        return $this->getFrame()?->getHeadData();
    }

    /**
     * returns the main frame
     *
     * @return GUI_CustomFrame|null
     **/
    public function getFrame(): ?GUI_CustomFrame
    {
        if(!$this->Frame) {
            if($this->hasFrame()) {
                $this->Frame = $this->Main;
            }
        }
        return $this->Frame;
    }

    /**
     * Is there a frame?
     *
     * @return bool has GUI_CustomFrame
     */
    public function hasFrame(): bool
    {
        return (isset($this->Main) && $this->Main instanceof GUI_CustomFrame);
    }

    /**
     * Sets a common skin folder
     *
     * @param string $skinName
     * @return Weblication
     */
    public function setCommonSkinFolder(string $skinName): Weblication
    {
        $this->commonSkinFolder = $skinName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommonSkinFolder(): string
    {
        return $this->commonSkinFolder;
    }

    /**
     * @param string $additionalDir
     * @param bool $absolute
     * @return string
     */
    public function getCommonSkinPath(string $additionalDir = '', bool $absolute = true): string
    {
        $path = '';

        # Ordner Skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $this->getCommonSkinFolder();
        if($absolute) {
            $folder_skins = addEndingSlash(getcwd()) . $folder_skins;
        }
        $folder_language = $folder_skins . addEndingSlash($this->language);;
        if($additionalDir != '') {
            $folder_skin_dir = addEndingSlash($folder_skins) . $additionalDir;
            $folder_language_dir = addEndingSlash($folder_language) . $additionalDir;
        }
        else {
            $folder_skin_dir = $folder_skins;
            $folder_language_dir = $folder_language;
        }

        if(is_dir($folder_language_dir)) {
            $path = $folder_language_dir;
        }
        elseif(is_dir($folder_skin_dir)) {
            $path = $folder_skin_dir;
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('Path \'%s\' and \'%s\' not found (@getCommonSkinPath)!',
                $folder_skin_dir, $folder_language_dir));
        }

        return $path;
    }

    /**
     * Liefert den Pfad zu den Templates (abh�ngig vom Skin-Ordner und der gew�hlten Sprache).
     *
     * @param string $additionalDir Ordner werden an ermittelten Template Pfad geh�ngt
     * @return string Pfad
     */
    public function getTemplatePath($additionalDir = ''/*, $baselib=false*/): string
    {
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);
        $dir = 'templates';

        # Ordner skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
        $folder_dir = $folder_skins . $dir;
        $folder_language = $folder_skins . $language . $dir;

        /*if ($baselib) {
            $path = addEndingSlash(PWD_TILL_GUIS) . $additionalDir;
        }
        else {*/
        if(is_dir($folder_language)) { // Language Ordner
            $path = $folder_language;
        }
        else if(is_dir($folder_dir)) { // Template Ordner
            $path = $folder_dir;
        }
        /*}*/

        if($additionalDir) {
            $path = addEndingSlash($path) . $additionalDir;
        }

        if(!is_dir($path)) {
            $this->raiseError(__FILE__, __LINE__, sprintf('Path \'%s\' not found (@getTemplatePath)!', $path));
        }

        return $path;
    }

    /**
     * Checks if skin exists
     *
     * @param string $skin
     * @return bool
     */
    function skin_exists(string $skin = ''): bool
    {
        $skin = addEndingSlash(($skin ? $skin : $this->skin));
        $pathSkin = addEndingSlash(getcwd()) . addEndingSlash(PWD_TILL_SKINS) . $skin;
        return file_exists($pathSkin);
    }

    /**
     * Liefert einen Pfad zum Skin-Verzeichnis zurück. Wenn der Parameter $additionalDir gef�llt wird, wird er an das Skin-Verzeichnis dran geh�ngt.
     *
     * @param string $additionalDir Unterverzeichnis vom Skin-Verzeichnis
     * @param bool $absolute
     * @return string
     */
    function getSkinPath(string $additionalDir = '', bool $absolute = true): string
    {
        $path = '';
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);

        # Ordner Skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
        if($absolute) {
            $folder_skins = addEndingSlash(getcwd()) . $folder_skins;
        }
        $folder_language = $folder_skins . $language;
        if($additionalDir != '') {
            $folder_skin_dir = addEndingSlash($folder_skins) . $additionalDir;
            $folder_language_dir = addEndingSlash($folder_language) . $additionalDir;
        }
        else {
            $folder_skin_dir = $folder_skins;
            $folder_language_dir = $folder_language;
        }

        if(is_dir($folder_language_dir)) {
            $path = $folder_language_dir;
        }
        elseif(is_dir($folder_skin_dir)) {
            $path = $folder_skin_dir;
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('Path \'%s\' and \'%s\' not found (@getSkinPath)!',
                $folder_skin_dir, $folder_language_dir));
        }

        return $path;
    }

    /**
     * list skins
     *
     * @return array skins
     */
    public function getSkins(): array
    {
        // detect skins
        if(!($this->skins)) {
            $skinPath = getcwd() . '/' . PWD_TILL_SKINS;
            $skinDirs = readDirs($skinPath);
            $numDirs = sizeof($skinDirs);
            $skins = [];
            for($i = 0; $i < $numDirs; $i++) {
                $skinName = basename($skinDirs[$i]);
                if($skinName != $this->getCommonSkinFolder()) {
                    $skins[] = $skinName;
                }
            }
            $this->skins = $skins;
        }
        return $this->skins;
    }

    /**
     * Sucht das uebergebene Image in einer fest vorgegebenen Verzeichnisstruktur. Nur im Ordner skins.
     *
     * @param string $filename Image Dateiname
     * @return string Bei Erfolg Pfad und Dateiname des gefunden Templates. Im Fehlerfall ''.
     **/
    function findImage(string $filename): string
    {
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);
        $images = 'images/';

        # Ordner skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
        $folder_images = $folder_skins . $images;
        $folder_language = $folder_skins . addEndingSlash($language) . $images;

        if(is_dir($folder_language)) { // Language Ordner
            if(file_exists($folder_language . $filename)) {
                return $folder_language . $filename;
            }
        }
        if(is_dir($folder_images)) { // Images Ordner
            if(file_exists($folder_images . $filename)) {
                return $folder_images . $filename;
            }
        }

        $this->raiseError(__FILE__, __LINE__, sprintf('Image \'%s\' not found (@Weblication->findImage)!', $folder_images . $filename));
        return '';
    }

    /**
     * Does the project have a common skin folder?
     *
     * @param string|null $subfolder
     * @return bool
     */
    public function hasCommonSkinFolder(?string $subfolder = null): bool
    {
        if(is_null($this->hasCommonSkinFolder)) {
            $this->hasCommonSkinFolder = [];
            $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->commonSkinFolder);
        }
        if($subfolder != null and $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists']) {
            if(!isset($this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder])) $this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder] = null;
            if(is_null($this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder])) {
                $this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder] = [];
                $this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->commonSkinFolder . '/' . $subfolder);
            }
            return $this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder]['__exists'];
        }
        return $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists'];
    }

    /**
     * Does the project have the skin?
     *
     * @param string|null $subfolder
     * @param string|null $language
     * @param string|null $translated
     * @return bool
     */
    public function hasSkinFolder(?string $subfolder = null, ?string $language = null, ?string $translated = null): bool
    {
        if(!isset($this->hasSkinFolder[$this->skin])) {
            $this->hasSkinFolder[$this->skin] = [];
            $this->hasSkinFolder[$this->skin]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin);
        }
        if($subfolder != null and $this->hasSkinFolder[$this->skin]['__exists']) {
            if(!isset($this->hasSkinFolder[$this->skin][$subfolder])) {
                $this->hasSkinFolder[$this->skin][$subfolder] = [];
                $this->hasSkinFolder[$this->skin][$subfolder]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin . '/' . $subfolder);
            }
            if(is_null($language) and is_null($translated)) {
                return $this->hasSkinFolder[$this->skin][$subfolder]['__exists'];
            }
            else {
                if($this->hasSkinFolder[$this->skin][$subfolder]['__exists']) {
                    if(!isset($this->hasSkinFolder[$this->skin][$subfolder][$language])) {
                        $this->hasSkinFolder[$this->skin][$subfolder][$language] = [];
                        $this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin . '/' . $subfolder . '/' . $language);
                    }
                    if(is_null($translated)) {
                        return $this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists'];
                    }
                    else {
                        if($this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists']) {
                            if(!isset($this->hasSkinFolder[$this->skin][$subfolder][$language][$translated])) {
                                $this->hasSkinFolder[$this->skin][$subfolder][$language][$translated] = [];
                                $this->hasSkinFolder[$this->skin][$subfolder][$language][$translated]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin . '/' . $subfolder . '/' . $language . '/' . $translated);
                            }
                            return $this->hasSkinFolder[$this->skin][$subfolder][$language][$translated]['__exists'];
                        }
                    }
                }
            }
        }

        return $this->hasSkinFolder[$this->skin]['__exists'];
    }

    /**
     * Sucht das uebergebene Template in einer fest vorgegebenen Verzeichnisstruktur.
     * Zuerst im Ordner skins, als naechstes im guis Ordner. Wird der Parameter baslib auf true gesetzt,
     * wird abschliessend noch in der baselib gesucht.<br>
     * Reihenfolge: skin-translated+subdirTranslated common-skin skin-translated skin GUIs-Projekt+ GUIs-Common+ (GUIs-Baselib)
     *
     * @param string $filename Template Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param boolean $baselib Schau auch in die baselib
     * @return string Bei Erfolg Pfad und Dateiname des gefundenen Templates. Im Fehlerfall ''.
     **/
    public function findTemplate(string $filename, string $classFolder = '', bool $baselib = false): string
    {
        $language = $this->language;
        $templates_subFolder = 'templates';
        $skinTemplateFolder = buildDirPath(PWD_TILL_SKINS, $this->skin, $templates_subFolder);
        //skin-translated+subdirTranslated
        //static translation templates have priority
        if($this->subdirTranslated) {
            if($this->hasSkinFolder($templates_subFolder, $language, $this->subdirTranslated)) {
                $translatedTemplate = buildFilePath($skinTemplateFolder, $language, $this->subdirTranslated, $filename);
                if(file_exists($translatedTemplate)) {
                    return $translatedTemplate;
                }
            }
        }

        $template = $this->findBestElement($templates_subFolder, $filename, $language, $classFolder, $baselib, true);
        if($template)
            return $template;

        // Lowercase Workaround @deprecated
        if(preg_match('/[A-Z]/', $filename . $classFolder)) {
            // try lower case
            // todo log buggy code
            $recursionResult = $this->findTemplate(strtolower($filename), strtolower($classFolder), $baselib);
            if((!empty($recursionResult)) && defined('IS_DEVELOP') && IS_DEVELOP) {
                $this->raiseError(__FILE__, __LINE__, 'Please use strtolower in your project to find ' . $filename . ' in ' . $classFolder);
            }
            return $recursionResult;
        }

        $this->raiseError(__FILE__, __LINE__, sprintf('Template \'%s\' not found (@Weblication->findTemplate)!', $filename));
        return '';
    }

    /**
     * Sucht das uebergebene StyleSheet in einer fest vorgegebenen Verzeichnisstruktur.
     * Zuerst im Ordner skins, als naechstes im guis Ordner.<br>
     * Reihenfolge: common-skin skin-translated skin GUIs-Projekt+ GUIs-Common+ (Baselib xor Common-common-skin)
     *
     * @param string $filename StyleSheet Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param boolean $baselib Schau auch in die baselib
     * @return string Bei Erfolg Pfad und Dateiname des gefunden StyleSheets. Im Fehlerfall ''.
     **/
    public function findStyleSheet(string $filename, string $classFolder = '', bool $baselib = false): string
    {
        $elementSubFolder = $this->cssFolder;

        $stylesheet = $this->findBestElement($elementSubFolder, $filename, $this->language, $classFolder, $baselib);
        if($stylesheet)
            return $stylesheet;

        if(!$baselib) {//Common-common-skin
            if(defined('DIR_COMMON_ROOT_REL')) {
                $stylesheet = buildFilePath(
                    DIR_COMMON_ROOT_REL, PWD_TILL_SKINS, $this->commonSkinFolder, $elementSubFolder, $filename);
                if(file_exists($stylesheet))
                    return $stylesheet;
            }
        }

        // Lowercase Workaround:
        if(preg_match('/[A-Z]/', $filename . $classFolder)) {
            // try lower case
            // todo log buggy code
            $recursionResult = $this->findStyleSheet(strtolower($filename), strtolower($classFolder), $baselib);
            if((!empty($recursionResult)) && defined('IS_DEVELOP') && IS_DEVELOP) {
                $this->raiseError(__FILE__, __LINE__, 'Please use strtolower in your project to find ' . $filename . ' in ' . $classFolder);
            }
            return $recursionResult;
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('StyleSheet \'%s\' not found (@Weblication->findStyleSheet)!', $filename));
        }
        return '';
    }

    /**
     * @param string $elementSubFolder
     * @param string $filename
     * @param string $language
     * @param string $classFolder
     * @param bool $baselib
     * @param bool $translate
     * @return string
     */
    public function findBestElement(string $elementSubFolder, string $filename, string $language, string $classFolder, bool $baselib, bool $translate = false): string
    {
        $skinElementFolder = buildDirPath(PWD_TILL_SKINS, $this->skin, $elementSubFolder);


        //common-skin
        if($this->hasCommonSkinFolder($elementSubFolder)) {
            $file = buildFilePath(PWD_TILL_SKINS, $this->commonSkinFolder, $elementSubFolder, $filename);
            if(file_exists($file))
                return $file;//Translated version?
        }

        if($this->hasSkinFolder($elementSubFolder)) {
            //skin-translated
            if($this->hasSkinFolder($elementSubFolder, $language)) { // with language, more specific
                $file = buildFilePath($skinElementFolder, $language, $filename);
                if(file_exists($file))
                    return $file;
            }
            //skin without language
            $file = buildFilePath($skinElementFolder, $filename);
            if(file_exists($file)) {
                if($translate)
                    try {
                        return Template::getTranslator()->translateFile($file, $language);
                    }
                    catch(Exception) {
                    }
                return $file;
            }
        }

        $gui_directories = [];
        if($classFolder) {
            $folder_guis = buildDirPath(PWD_TILL_GUIS, $classFolder);
            //Project-GUIs
            $gui_directories[] = $folder_guis;
            if(defined('DIR_COMMON_ROOT_REL')) {
                //Common-GUIs
                $gui_directories[] = buildDirPath(DIR_COMMON_ROOT_REL, $folder_guis);
            }
            if($baselib) {
                //Baselib-GUIs
                $gui_directories[] = buildDirPath($this->getRelativePathBaselib(PWD_TILL_GUIS), $classFolder);
            }
        }

        foreach($gui_directories as $folder_guis) {
            $file = $folder_guis . $filename;
            if(file_exists($file)) {
                $translatedFile = buildFilePath($folder_guis, $language, $filename);
                if(file_exists($translatedFile))
                    // Language Ordner
                    return $translatedFile;
                // GUI Ordner
                if($translate)
                    try {
                        return Template::getTranslator()->translateFile($file, $language);
                    }
                    catch(Exception) {
                    }
                return $file;
            }
        }
        return "";
    }

    /**
     * Sucht das uebergebene JavaScript in einer fest vorgegebenen Verzeichnisstruktur.
     * Sucht im Ordner ./javascripts/ anschliessend in ./guis/$classFolder/ und ggf. noch in /poolcommons/guis/$classFolder/.
     * Wird der dritte Parameter benutzt, wird ausgehend von /pool/ anstelle des aktuellen Arbeitsverzeichnisses gesucht.
     *
     * JavaScripts aus der Hauptbibliothek koennen nicht ueberschrieben werden (macht auch keinen Sinn).
     *
     * @param string $filename JavaScript Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param bool $baselib
     * @param bool $raiseError
     * @return string If successful, the path and filename of the JavaScript found are returned. In case of error an empty string.
     */
    function findJavaScript(string $filename, string $classFolder = '', bool $baselib = false, bool $raiseError = true): string
    {
        $folder_javascripts = addEndingSlash(PWD_TILL_JAVASCRIPTS);
        $folder_guis = addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classFolder);
        //Ordner baselib -> look in POOL instead
        if($baselib) {
            $folder_javascripts = addEndingSlash($this->getRelativePathBaselib($folder_javascripts));
            $folder_guis = addEndingSlash($this->getRelativePathBaselib($folder_guis));
        }
        $javaScriptFile = $folder_javascripts . $filename;
        if(file_exists($javaScriptFile))
            return $javaScriptFile;//found
        $javaScriptFile = $folder_guis . $filename;
        if(file_exists($javaScriptFile))
            return $javaScriptFile;//found
        if(defined('DIR_COMMON_ROOT_REL')) {
            $folder_common = buildDirPath(DIR_COMMON_ROOT_REL, PWD_TILL_GUIS, $classFolder);
            $javaScriptFile = $folder_common . $filename;
            if(file_exists($javaScriptFile))
                return $javaScriptFile;//found
        }
        if($raiseError)
            $this->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s\' not found (@findJavaScript)!', $filename));
        return '';
    }

    /**
     * @param string $path
     */
    public function setRelativePathBaselib(string $path)
    {
        $this->relativePathBaselib = $path;
    }

    /**
     * Relativer Pfad zum Rootverzeichnis der Baselib
     *
     * @param string $subDir
     * @return string path from project to library pool
     */
    public function getRelativePathBaselib(string $subDir = ''): string
    {
        return $this->relativePathBaselib . '/' . $subDir;
    }

    /**
     * DataInterface in die Anwendung einfuegen. Somit ist es ueberall bekannt und kann
     * fuer die DAO Geschichte verwendet werden.
     *
     * @param DataInterface $DataInterface Einzufuegendes DataInterface
     * @return DataInterface Eingefuegtes DataInterface
     **/
    public function addDataInterface(DataInterface $DataInterface): DataInterface
    {
        $this->interfaces[$DataInterface->getInterfaceType()] = $DataInterface;
        return $DataInterface;
    }

    /**
     * Liefert ein Interface Objekt.
     *
     * @param string $interface_name
     * @return DataInterface Interface Objekt
     **/
    public function getInterface(string $interface_name): DataInterface
    {
        return $this->interfaces[$interface_name];
    }

    /**
     * Liefert alle Interface Objekte.
     *
     * @return array Interface Objekte
     * @see DAO::createDAO()
     **/
    public function getInterfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * starts any session derived from the class ISession
     *
     * @param array $settings configuration parameters:
     *   application.launchModule - sets the main module that is launched
     *   application.sessionClassName - overrides default session class
     * @return Weblication
     * @throws Exception
     */
    public function setup(array $settings = []): self
    {
        $this->Settings->setVars($settings);

        $applicationName = $this->Settings->getVar('application.name', $this->getName());
        $this->setName($applicationName);

        $this->setTitle($this->Settings->getVar('application.title', $this->getTitle()));
        $this->setCharset($this->Settings->getVar('application.charset', $this->getCharset()));

        $this->setLaunchModule($this->Settings->getVar('application.launchModule', $this->getLaunchModule()));


        // setup Translator
        $translatorResourceDir = $this->Settings->getVar('application.translatorResourceDir');
        if($translatorResourceDir) {
            $AppTranslator = TranslationProviderFactory_ResourceFile::create($translatorResourceDir);
            $this->setTranslator($AppTranslator);
        }

        $TranslatorResource = $this->Settings->getVar('application.translatorResource');
        if($TranslatorResource instanceof TranslationProviderFactory_ResourceFile) {
            $AppTranslator = new Translator($TranslatorResource);
            $this->setTranslator($AppTranslator);
        }

        $Translator = $this->Settings->getVar('application.translator');
        if($Translator instanceof Translator) {
            $this->setTranslator($Translator);
        }

        $languages = $this->getTranslator()->parseLangHeader(false, $this->Settings->getVar('application.locale', 'en_US'));
        $this->getTranslator()->swapLangList($languages);

        // $this->Input = new Input($this->Settings->getVar('application.superglobals', $this->superglobals));
        return $this;
    }

    /**
     * Starts a PHP Session via session_start()!
     * We use the standard php sessions.
     *
     * @param string $session_name Name der Session (Default: sid)
     * @param integer $use_trans_sid Transparente Session ID (Default: 0)
     * @param integer $use_cookies Verwende Cookies (Default: 1)
     * @param integer $use_only_cookies Verwende nur Cookies (Default: 0)
     * @param boolean $autoClose session will not be kept open during runtime. Each write opens and closes the session. Session is not locked in parallel execution.
     * @return ISession|null
     * @throws Exception
     */
    public function startPHPSession(string $session_name = 'PHPSESSID', int $use_trans_sid = 0, int $use_cookies = 1,
        int $use_only_cookies = 0, bool $autoClose = true): ?ISession
    {
        switch(session_status()) {
            case PHP_SESSION_DISABLED:
                throw new Exception('PHP Session is  disabled.');

            case PHP_SESSION_NONE:
                // setting ini is only possible, if the session is not started yet
                $use_trans_sid = (string)$use_trans_sid;
                $use_cookies = (string)$use_cookies;
                $use_only_cookies = (string)$use_only_cookies;

                $sessionConfig = [
                    'session.name' => $session_name,
                    'session.use_trans_sid' => $use_trans_sid,
                    'session.use_cookies' => $use_cookies,
                    'session.use_only_cookies' => $use_only_cookies
                ];
                foreach($sessionConfig as $option => $value) {
                    if(ini_get($option) != $value) {
                        ini_set($option, $value);
                    }
                }
        }

        $isStatic = !(isset($this)); // TODO static calls or static AppSettings
        if($isStatic) {
            return new ISession($autoClose);
        }
        $className = $this->Settings->getVar('application.sessionClassName', 'ISession');
        $this->Session = new $className($autoClose);
        return $this->Session;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getSetting(string $key): mixed
    {
        return $this->Session->getVar($key);
    }

    /**
     * Erzeugt eine Instanz vom eigenen Session Handler. Ansprechbar ueber Weblication::Session.
     *
     * @param string $tabledefine DAO Tabellendefinition.
     */
    public function createSessionHandler($tabledefine)
    {
        $this->SessionHandler = new SessionHandler($this->interfaces, $tabledefine);
        $this->Session = new ISession();
    }

    /**
     * Seitentitel setzen
     *
     * @param string $title
     * @return Weblication
     */
    public function setTitle(string $title): Weblication
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Seitentitel auslesen
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * set locale
     *
     * @param int $category
     * @param string|null $locale
     * @return bool|string
     */
    public function setLocale(int $category = LC_ALL, ?string $locale = null): bool|string
    {
        if(is_null($locale)) {
            $locale = $this->getTranslator()->parseLangHeader();
        }
        $this->locale = $locale;
        return setlocale($category, $locale);
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * returns cookie for this application
     *
     * @return ICookie
     */
    public function getCookie(): ICookie
    {
        if(!$this->Cookie) {
            $this->Cookie = new ICookie();
        }
        return $this->Cookie;
    }

    /**
     * @param string $launchModule
     * @return $this
     */
    public function setLaunchModule(string $launchModule): self
    {
        $this->launchModule = $launchModule;
        return $this;
    }

    /**
     * returns module that should be launched
     *
     * @return string
     */
    public function getLaunchModule(): string
    {
        return $_GET[REQUEST_PARAM_MODULE] ?? $this->launchModule;
    }

    /**
     * render application
     *
     * @return void
     * @throws ModulNotFoundException
     * @throws Exception
     */
    public function render(): void
    {
        if($this->run($this->getLaunchModule())) {
            $this->prepareContent();
            echo $this->finalizeContent();
        }
    }

    /**
     * Erzeugt das erste GUI_Module in der Kette (Momentan wird hier der Seitentitel mit dem Projektnamen gefuellt).
     *
     * @param string $className GUI_Module (Standard-Wert: GUI_CustomFrame)
     * @return Weblication
     *
     * @throws ModulNotFoundException|Exception
     */
    public function run(string $className = 'GUI_CustomFrame'): self
    {
        // An application name is required. For example, the application name is used for creating directories in the data folder.
        if($this->getName() == '') {
            throw new Exception('The application name must be defined.');
        }

        // TODO Get Parameter frame
        $params = $_REQUEST['params'] ?? '';
        if(isNotEmpty($params) and isAjax()) {
            $params = base64url_decode($params) ?: "";
        }

        $mainGUI = GUI_Module::createGUIModule($className, $this, null, $params, true, false);
        $this->setMain($mainGUI);
        //
        $mainGUI->searchGUIsInPreloadedContent();

        if($this->hasFrame()) {
            //Seitentitel (= Project)
            $Header = $this->getFrame()->getHeadData();

            $Header->setTitle($this->title);
            $Header->setLanguage($this->language);
            if($this->charset) $Header->setCharset($this->charset);
        }
        return $this;
    }

    /**
     * main logic of the front controller. compile main content.
     */
    protected function prepareContent(): void
    {
        $this->Main->provisionContent();
        if(!$this->Main->isAjax()) {
            $this->Main->prepareContent();
        }
    }

    /**
     * return finished HTML content
     * Error handling wrapper around finalizeContent of the Main-GUI
     *
     * @return string website content
     *
     * @throws Exception
     */
    protected function finalizeContent(): string
    {
        $content = $this->Main->finalizeContent();

        // Odd, there were outputs written?
        if(headers_sent()) {
            $error = error_get_last();
            // error was triggered (old method)
            if($this->isXdebugEnabled()) {
                // we suppress the output of the application @todo redirect to an error page?
                if($error) return '';
            }
        }

        return $content;
    }

    /**
     * @return bool
     */
    public function isXdebugEnabled(): bool
    {
        if($this->xdebug === null) {
            $this->xdebug = extension_loaded('xdebug');
        }
        return $this->xdebug;
    }

    /**
     * @return void
     */
    public function logout(): void
    {
        // reset the session
        $this->Session->destroy();
    }

    /**
     * Schliesst alle Verbindungen und loescht die Interface Objekte.
     * Bitte bei der Erstellung von Interface Objekten sicherheitshalber immer abschliessend mit destroy() alle Verbindungen trennen!
     */
    public function destroy()
    {
        if(defined('DATAINTERFACE_MYSQL')) {
            if(isset($this->interfaces[DATAINTERFACE_MYSQL]) and is_a($this->interfaces[DATAINTERFACE_MYSQL], 'MySQL_Interface')) {
                $this->interfaces[DATAINTERFACE_MYSQL]->close();
                unset($this->interfaces[DATAINTERFACE_MYSQL]);
            }
        }

        if(defined('DATAINTERFACE_MYSQLI')) {
            if(isset($this->interfaces[DATAINTERFACE_MYSQLI]) and is_a($this->interfaces[DATAINTERFACE_MYSQLI], 'MySQLi_Interface')) {
                $this->interfaces[DATAINTERFACE_MYSQLI]->close();
                unset($this->interfaces[DATAINTERFACE_MYSQLI]);
            }
        }
        if(defined('DATAINTERFACE_C16')) {
            if(isset($this->interfaces[DATAINTERFACE_C16]) and is_a($this->interfaces[DATAINTERFACE_C16], 'C16_Interface')) {
                $this->interfaces[DATAINTERFACE_C16]->close();
                unset($this->interfaces[DATAINTERFACE_C16]);
            }
        }
        parent::destroy();
    }
}