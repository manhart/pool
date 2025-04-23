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

declare(strict_types = 1);

namespace pool\classes\Core;

use Exception;
use JetBrains\PhpStorm\Pure;
use Locale;
use pool\classes\Cache\Memory;
use pool\classes\Core\Input\Cookie;
use pool\classes\Core\Input\Input;
use pool\classes\Core\Input\Session;
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\ModulNotFoundException;
use pool\classes\Exception\RuntimeException;
use pool\classes\Exception\SessionDisabledException;
use pool\classes\Exception\TemplateNotFoundException;
use pool\classes\GUI\Builtin\GUI_CustomFrame;
use pool\classes\GUI\GUI_Module;
use pool\classes\Language;
use pool\classes\translator\TranslationProviderFactory;
use pool\classes\translator\TranslationProviderFactory_nop;
use pool\classes\translator\TranslationProviderFactory_ResourceFile;
use pool\classes\translator\Translator;
use pool\guis\GUI_HeadData\GUI_HeadData;
use Template;

use function addEndingSlash;
use function array_merge;
use function assert;
use function basename;
use function buildDirPath;
use function buildFilePath;
use function count;
use function date_default_timezone_get;
use function defined;
use function explode;
use function extension_loaded;
use function file_exists;
use function hasHtmlContentType;
use function header;
use function ini_get;
use function ini_set;
use function is_dir;
use function is_null;
use function is_subclass_of;
use function isAjax;
use function json_encode;
use function makeRelativePathsFrom;
use function microtime;
use function readDirs;
use function register_shutdown_function;
use function reset;
use function session_status;
use function sprintf;
use function strrpos;
use function substr;
use function trim;

use const PHP_SESSION_ACTIVE;
use const PHP_SESSION_DISABLED;
use const PHP_SESSION_NONE;
use const pool\PWD_TILL_GUIS;
use const pool\PWD_TILL_JS;
use const pool\PWD_TILL_SKINS;

/**
 * Class Weblication represents the main class of a web application
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class Weblication extends Component
{
    public const string REQUEST_PARAM_MODULE = 'module';
    public const string REQUEST_PARAM_METHOD = 'method';
    public const string REQUEST_PARAM_SCHEMA = 'schema';

    /**
     * Is this request an ajax call
     */
    static public bool $isAjax = false;

    /**
     * Titel der Weblication
     *
     * @var string
     */
    private string $title = '';

    /**
     * @var string class name of the module that is started as main module
     */
    protected string $launchModule = GUI_CustomFrame::class;

    /**
     * Contains the first loaded GUI_Module
     *
     * @var GUI_Module $Main
     * @see Weblication::run()
     */
    private GUI_Module $Main;

    private ?GUI_CustomFrame $Frame = null;

    /**
     * Session object
     *
     * @var Session|null $Session
     */
    public ?Session $Session = null;

    /**
     * @var static|null
     */
    private static ?Weblication $Instance = null;

    public Input $Input;

    /**
     * client-side path to the pool
     *
     * @var string
     */
    private string $poolClientSideRelativePath = 'pool';

    /**
     * server-side path to the pool
     *
     * @var string
     */
    private string $poolServerSideRelativePath = 'pool';

    /**
     * Skin / Theme (Designvorlage bzw. Bilderordner)
     *
     * @var string
     */
    private string $skin = 'default';

    private ?array $skins = null;

    /**
     * Schema / Layout (index ist das Standard-Schema)
     *
     * @var string
     */
    protected string $schema = 'index';

    /**
     * @var string Default charset
     */
    private string $charset = 'utf-8';

    /**
     * @var int Default charset
     */
    private int $progId = 0;

    private string $cssFolder = 'css';

    /**
     * @var Input central data container for runtime data exchange between classes/modules.
     */
    protected Input $context;

    /**
     * @var bool|null xdebug enabled
     */
    private ?bool $xdebug = null;

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
    private array $hasSkinFolder = [];

    /**
     * @var string language code
     */
    private string $language = '';

    /**
     * @var string an identifier used to get language, culture, or regionally-specific behavior
     */
    private string $locale = '';

    /**
     * @var string used as fallback
     */
    private string $defaultLocale = 'en_US';

    /**
     * @var string version of the application
     */
    private string $version = '';

    /**
     * Cookie for the application
     *
     * @var Cookie|null
     */
    private ?Cookie $Cookie = null;

    /**
     * locale unchanged / as is.
     */
    public const int LOCALE_UNCHANGED = 0;
    /**
     * ISO-3166 Country Code. If locale does not have a region, the best fitting one is taken.
     */
    public const int LOCALE_FORCE_REGION = 1;
    /**
     * The application's charset is appended if no charset is attached to the locale.
     */
    public const int LOCALE_FORCE_CHARSET = 2;
    /**
     * Removes possible charsets.
     */
    public const int LOCALE_WITHOUT_CHARSET = 4;

    /**
     * @var array all possible default formats
     */
    private array $formats = [
        'php.time' => 'H:i',
        'php.date' => 'd.m.Y',
        'php.sec' => 's',
        'php.date.time' => 'd.m.Y H:i',
        'php.date.time.sec' => 'd.m.Y H:i:s',
        'php.date.time.us6' => 'd.m.Y H:i:s.u',
        'strftime.time' => '%H:%M', // needed in js
        'strftime.date' => '%d.%m.%Y', // needed in js
        'strftime.date.time' => '%d.%m.%Y %H:%M', // needed in js
        'strftime.date.time.short' => '%d.%m.%y %H:%M', // needed in js
        'strftime.date.time.sec' => '%d.%m.%Y %H:%M:%S', // needed in js
        'moment.date' => 'DD.MM.YYYY', // needed for moment js
        'moment.date.time' => 'DD.MM.YYYY HH:mm', // needed for moment js
        'moment.date.time.sec' => 'DD.MM.YYYY HH:mm:ss', // needed for moment js
        'mysql.date_format.date' => '%d.%m%.%Y',
        'mysql.date_format.date.time' => '%d.%m%.%Y %H:%i',
        'mysql.date_format.date.time.sec' => '%d.%m%.%Y %T',
        'number' => [
            'decimals' => 2,
            'decimal_separator' => ',',
            'thousands_separator' => '.',
        ],
    ];

    protected Translator $translator;

    /**
     * Set to true after initialization of the application settings
     *
     * @var true
     */
    private bool $isInitialized = false;

    /**
     * @var Memory Cache (Memcached)
     */
    private readonly Memory $memory;

    /**
     * @var int Cache time to live
     */
    private const int CACHE_TTL = 86400;
    /**
     * Types of caching
     */
    public const string CACHE_FILE_ACCESS = 'fileAccess';
    public const string CACHE_FILE = 'file';
    public const string CACHE_ITEM = 'item';

    /**
     * Enable or disable caching of different types
     */
    private static array $cacheItem = [
        self::CACHE_ITEM => true,
        self::CACHE_FILE => true,
        self::CACHE_FILE_ACCESS => true,
    ];

    /**
     * @var string working path
     */
    private static string $workingDirectory;

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    final private function __construct()
    {
        parent::__construct(null);
        self::$isAjax = isAjax();
        self::$workingDirectory = getcwd();
        $this->context = new Input();
        //handles POST requests containing JSON data
        Input::processJsonPostRequest();
    }

    /**
     * Gets the instance via lazy initialization (created on first usage)
     *
     * @throws RuntimeException When the running app is not compatible with the requested Weblication type
     */
    public static function getInstance(): static
    {
        self::$Instance ??= new static;
        if (!(self::$Instance instanceof static))
            throw new RuntimeException("Incompatible app types can't use ".static::class.' already initialized a '.self::$Instance::class);
        return self::$Instance;
    }

    public static function hasInstance(): bool
    {
        return static::$Instance !== null;
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone() {}

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup() {}

    /**
     * Changes the folder for the design templates (Html templates) and images.
     *
     * @param string $skin folder for frontend design (css, templates and images).
     */
    public function setSkin(string $skin): static
    {
        $this->skin = $skin;
        return $this;
    }

    /**
     * Liefert den Ordner (Namen) der aktuellen Designvorlagen und Bilder zurueck.
     * (wird derzeit fuer die Bilder und Html Templates missbraucht)
     *
     * @return string Name des Designs (Skin)
     */
    public function getSkin(): string
    {
        return $this->skin;
    }

    /**
     * Gets a value from the context container
     */
    public function getContextValue(string $key, mixed $default = null): mixed
    {
        return $this->context->getVar($key, $default);
    }

    /**
     * Sets a value in the context container
     */
    public function setContextValue(string $key, mixed $value): static
    {
        $this->context->setVar($key, $value);
        return $this;
    }

    /**
     * Get translator
     */
    public function getTranslator(): Translator
    {
        return $this->translator;
    }

    /**
     * Set the translator instance
     */
    public function setTranslator(Translator $translator): static
    {
        $this->translator = $translator;
        return $this;
    }

    /**
     * Check if a translator is set
     */
    public function hasTranslator(): bool
    {
        return isset($this->translator);
    }

    /**
     * Get the character set
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Set charset for the Web Application
     *
     */
    public function setCharset(string $charset): static
    {
        header("Content-Type: text/html; charset=$charset");
        $this->charset = $charset;
        return $this;
    }

    /**
     * All kinds of formats.There are predefined ones: datetime, date and time
     */
    public function setDefaultFormats(array $formats): static
    {
        $this->formats = array_merge($this->formats, $formats);
        return $this;
    }

    /**
     * Reads the saved format
     */
    public function getDefaultFormat(string $key): array|string
    {
        return $this->formats[$key] ?? '';
    }

    /**
     * Set an application id
     */
    public function setProgId(int $progId): static
    {
        $this->progId = $progId;
        return $this;
    }

    /**
     * Get an application id (if set)
     */
    public function getProgId(): ?int
    {
        return $this->progId;
    }

    /**
     * Is this request an ajax call
     */
    public static function isAjax(): bool
    {
        return self::$isAjax;
    }

    /**
     * Set default schema/layout, if none is loaded by request
     */
    public function setDefaultSchema(string $default = 'index'): static
    {
        $this->schema = $default;
        return $this;
    }

    /**
     * Returns the default scheme
     */
    public function getDefaultSchema(): string
    {
        return $this->schema;
    }

    /**
     * @param string $version application version
     */
    public function setVersion(string $version): static
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
     * Sets the main gui.
     */
    public function setMain(GUI_Module $GUI_Module): static
    {
        $this->Main = $GUI_Module;
        return $this;
    }

    /**
     * Liefert das Haupt-GUI (meistens erstes GUI, das im Startscript uebergeben wurde).
     */
    public function getMain(): GUI_Module
    {
        return $this->Main;
    }

    public function getHead(): GUI_HeadData
    {
        return $this->getFrame()?->getHeadData() ?? new GUI_HeadData($this);
    }

    /**
     * Returns the main frame
     */
    public function getFrame(): ?GUI_CustomFrame
    {
        if (!$this->Frame && $this->hasFrame()) {
            assert($this->Main instanceof GUI_CustomFrame);
            $this->Frame = $this->Main;
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
        return isset($this->Main) && $this->Main instanceof GUI_CustomFrame;
    }

    /**
     * Sets a common skin folder
     */
    public function setCommonSkinFolder(string $skinName): static
    {
        $this->commonSkinFolder = $skinName;
        return $this;
    }

    public function getCommonSkinFolder(): string
    {
        return $this->commonSkinFolder;
    }

    public function getCommonSkinPath(string $additionalDir = '', bool $absolute = true): string
    {
        $path = '';

        # Ordner Skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS).$this->getCommonSkinFolder();
        if ($absolute) {
            $folder_skins = addEndingSlash(self::$workingDirectory).$folder_skins;
        }
        $folder_language = $folder_skins.addEndingSlash($this->language);
        if ($additionalDir !== '') {
            $folder_skin_dir = addEndingSlash($folder_skins).$additionalDir;
            $folder_language_dir = addEndingSlash($folder_language).$additionalDir;
        } else {
            $folder_skin_dir = $folder_skins;
            $folder_language_dir = $folder_language;
        }

        if (is_dir($folder_language_dir)) {
            $path = $folder_language_dir;
        } elseif (is_dir($folder_skin_dir)) {
            $path = $folder_skin_dir;
        } else {
            $this->raiseError(
                __FILE__,
                __LINE__,
                sprintf(
                    'Path \'%s\' and \'%s\' not found (@getCommonSkinPath)!',
                    $folder_skin_dir,
                    $folder_language_dir,
                ),
            );
        }

        return $path;
    }

    /**
     * Checks if skin exists
     */
    public function skin_exists(string $skin = ''): bool
    {
        $skin = addEndingSlash(($skin ?: $this->skin));
        $pathSkin = addEndingSlash(self::$workingDirectory).addEndingSlash(PWD_TILL_SKINS).$skin;
        return file_exists($pathSkin);
    }

    /**
     * Liefert einen Pfad zum Skin-Verzeichnis zurück. Wenn der Parameter $additionalDir gef�llt wird, wird er an das Skin-Verzeichnis dran geh�ngt.
     *
     * @param string $additionalDir Unterverzeichnis vom Skin-Verzeichnis
     */
    public function getSkinPath(string $additionalDir = '', bool $absolute = true): string
    {
        $path = '';
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);

        # Ordner Skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS).$skin;
        if ($absolute) {
            $folder_skins = addEndingSlash(self::$workingDirectory).$folder_skins;
        }
        $folder_language = $folder_skins.$language;
        if ($additionalDir !== '') {
            $folder_skin_dir = addEndingSlash($folder_skins).$additionalDir;
            $folder_language_dir = addEndingSlash($folder_language).$additionalDir;
        } else {
            $folder_skin_dir = $folder_skins;
            $folder_language_dir = $folder_language;
        }

        if (is_dir($folder_language_dir)) {
            $path = $folder_language_dir;
        } elseif (is_dir($folder_skin_dir)) {
            $path = $folder_skin_dir;
        } else {
            $this->raiseError(
                __FILE__,
                __LINE__,
                sprintf(
                    'Path \'%s\' and \'%s\' not found (@getSkinPath)!',
                    $folder_skin_dir,
                    $folder_language_dir,
                ),
            );
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
        if (!($this->skins)) {
            $skinPath = self::$workingDirectory.'/'.PWD_TILL_SKINS;
            $skinDirs = readDirs($skinPath);
            $skins = [];
            foreach ($skinDirs as $iValue) {
                $skinName = basename($iValue);
                if ($skinName !== $this->getCommonSkinFolder()) {
                    $skins[] = $skinName;
                }
            }
            $this->skins = $skins;
        }
        return $this->skins;
    }

    /**
     * Searches the given image in a fixed directory skins folder structure.
     *
     * @param string $filename wanted image
     * @return string Filename or empty string in case of failure
     */
    public function findImage(string $filename, ?string $skin = null): string
    {
        $skinFolder = addEndingSlash($skin ?? $this->skin);
        $language = addEndingSlash($this->language);
        $images = 'images/';

        # Ordner skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS).$skinFolder;
        $folder_images = $folder_skins.$images;
        $folder_language = $folder_skins.addEndingSlash($language).$images;

        // Language Ordner
        if (is_dir($folder_language) && file_exists($folder_language.$filename)) {
            return $folder_language.$filename;
        }
        // Images Ordner
        if (is_dir($folder_images) && file_exists($folder_images.$filename)) {
            return $folder_images.$filename;
        }

        $this->raiseError(__FILE__, __LINE__, sprintf('Image \'%s\' not found (@Weblication->findImage)!', $folder_images.$filename));
        return '';
    }

    /**
     * Does the project have a common skin folder?
     */
    public function hasCommonSkinFolder(?string $subFolder = null): bool
    {
        if (is_null($this->hasCommonSkinFolder)) {
            $this->hasCommonSkinFolder = [];
            $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists'] = is_dir(PWD_TILL_SKINS.'/'.$this->commonSkinFolder);
        }
        if ($subFolder !== null && $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists']) {
            if (!isset($this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder])) $this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder] =
                null;
            if (is_null($this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder])) {
                $this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder] = [];
                $this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder]['__exists'] =
                    is_dir(PWD_TILL_SKINS.'/'.$this->commonSkinFolder.'/'.$subFolder);
            }
            return $this->hasCommonSkinFolder[$this->commonSkinFolder][$subFolder]['__exists'];
        }
        return $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists'];
    }

    /**
     * Does the project have the skin?
     */
    public function hasSkinFolder(?string $subFolder = null, ?string $language = null, ?string $translated = null): bool
    {
        $skinFolder = PWD_TILL_SKINS;
        $skinName = $this->skin;
        if (!isset($this->hasSkinFolder[$skinName])) {
            $this->hasSkinFolder[$skinName] = [];
            $this->hasSkinFolder[$skinName]['__exists'] = is_dir("$skinFolder/$skinName");
        }
        if ($subFolder !== null && $this->hasSkinFolder[$skinName]['__exists']) {
            if (!isset($this->hasSkinFolder[$skinName][$subFolder])) {
                $this->hasSkinFolder[$skinName][$subFolder] = [];
                $this->hasSkinFolder[$skinName][$subFolder]['__exists'] = is_dir("$skinFolder/$skinName/$subFolder");
            }
            if (is_null($language) && is_null($translated)) {
                return $this->hasSkinFolder[$skinName][$subFolder]['__exists'];
            }

            if ($this->hasSkinFolder[$skinName][$subFolder]['__exists']) {
                if (!isset($this->hasSkinFolder[$skinName][$subFolder][$language])) {
                    $this->hasSkinFolder[$skinName][$subFolder][$language] = [];
                    $this->hasSkinFolder[$skinName][$subFolder][$language]['__exists'] = is_dir("$skinFolder/$skinName/$subFolder/$language");
                }
                if (is_null($translated)) {
                    return $this->hasSkinFolder[$skinName][$subFolder][$language]['__exists'];
                }

                if ($this->hasSkinFolder[$skinName][$subFolder][$language]['__exists']) {
                    if (!isset($this->hasSkinFolder[$skinName][$subFolder][$language][$translated])) {
                        $this->hasSkinFolder[$skinName][$subFolder][$language][$translated] = [];
                        $this->hasSkinFolder[$skinName][$subFolder][$language][$translated]['__exists'] =
                            is_dir("$skinFolder/$skinName/$subFolder/$language/$translated");
                    }
                    return $this->hasSkinFolder[$skinName][$subFolder][$language][$translated]['__exists'];
                }
            }
        }

        return $this->hasSkinFolder[$skinName]['__exists'];
    }

    /**
     * Sucht das uebergebene Template in einer fest vorgegebenen Verzeichnisstruktur.
     * Zuerst im Ordner skins, als naechstes im guis Ordner. Wird der Parameter baseLib auf true gesetzt,
     * wird abschliessend noch in der baseLib gesucht.<br>
     * Reihenfolge: <s>skin-translated+subdirTranslated</s> (<b>common-skin-translated</b> common-skin skin-translated skin) GUIs-Projekt+ <i>(..?skins of
     * Projekt Common?..)</i> GUIs-Common+ ((..???..) GUIs-Baselib)
     *
     * @param string $filename Template Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param boolean $baseLib Schau auch in die baseLib
     * @return string Bei Erfolg Pfad und Dateiname des gefundenen Templates. Im Fehlerfall ''.
     */
    public function findTemplate(string $filename, string $classFolder = '', bool $baseLib = false): string
    {
        $language = $this->language;
        $elementSubFolder = 'templates';
        $translate = (bool)Template::getTranslator();
        $memKey = "findTemplate.$this->skin.$language.$classFolder.$filename.$baseLib";
        if (($template = $this->getCachedItem($memKey, static::CACHE_FILE_ACCESS)) !== false) {
            return $template;
        }
        $template = $this->findBestElement($elementSubFolder, $filename, $language, $classFolder, $baseLib, false, $translate);
        if ($template) {
            $this->cacheItem($memKey, $template, static::CACHE_FILE_ACCESS);
            return $template;
        }

        $msg = "Template $filename in ".__METHOD__." not found!";
        if ($baseLib && !$this->getPoolServerSideRelativePath()) {
            // if nothing was found, we give a hint to uninformed useres that the path has not been set.
            $msg .= ' You need to set the path to the pool with Weblication->setPoolRelativePath().';
        }
        throw new TemplateNotFoundException($msg);
    }

    /**
     * Sucht das uebergebene StyleSheet in einer fest vorgegebenen Verzeichnisstruktur.
     * Zuerst im Ordner skins, als naechstes im guis Ordner.<br>
     * Reihenfolge: common-skin+ skin+ GUIs-Projekt+ (..? skins ?..) GUIs-Common+ (BaseLib xor Common-common-skin)
     *
     * @param string $filename StyleSheet Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param boolean $baseLib Schau auch in die baseLib
     * @return string Bei Erfolg Pfad und Dateiname des gefunden StyleSheets. Im Fehlerfall ''.
     **@see Weblication::findTemplate()
     */
    public function findStyleSheet(string $filename, string $classFolder = '', bool $baseLib = false, bool $raiseError = true): string
    {
        $elementSubFolder = $this->cssFolder;
        $language = $this->language;
        $memKey = "findStyleSheet.$this->skin.$language.$classFolder.$elementSubFolder.$filename.$baseLib";
        if (($stylesheet = $this->getCachedItem($memKey, static::CACHE_FILE_ACCESS)) !== false) {
            return $stylesheet;
        }
        $stylesheet = $this->findBestElement($elementSubFolder, $filename, $language, $classFolder, $baseLib, true);
        if ($stylesheet) {
            if ($baseLib) {
                $stylesheet = strtr($stylesheet, [$this->getPoolServerSideRelativePath() => $this->getPoolClientSideRelativePath()]);
            }
            $this->cacheItem($memKey, $stylesheet, static::CACHE_FILE_ACCESS);
            return $stylesheet;
        }

        if ($raiseError)
            $this->raiseError(__FILE__, __LINE__, sprintf('StyleSheet \'%s\' not found (@Weblication->findStyleSheet)!', $filename));
        else {
            $this->cacheItem($memKey, '', static::CACHE_FILE_ACCESS);
        }
        return '';
    }

    public function findBestElement(
        string $elementSubFolder,
        string $filename,
        string $language,
        string $classFolder,
        bool $baseLib,
        bool $all,
        bool $translate = false,
    ): string {
        $places = [];
        //Getting list of Places to search
        if ($this->hasCommonSkinFolder($elementSubFolder)) //Project? -> Special common-skin
            $places[] = buildDirPath(PWD_TILL_SKINS, $this->commonSkinFolder, $elementSubFolder);
        if ($this->hasSkinFolder($elementSubFolder)) //Project? -> Skin
            $places[] = buildDirPath(PWD_TILL_SKINS, $this->skin, $elementSubFolder);
        $places[] = buildDirPath($elementSubFolder);
        if ($classFolder) {//Projects -> GUI
            //Path from Project root to the specific GUI folder
            $guiDirectory = buildDirPath(PWD_TILL_GUIS, $classFolder);
            //current Project
            $places[] = $guiDirectory;
            //common Project
            if (defined('DIR_COMMON_ROOT_REL')) {
                $places[] = $commonGuiDirectory = buildDirPath(DIR_COMMON_ROOT_REL, $guiDirectory);
                $places[] = buildDirPath($commonGuiDirectory, PWD_TILL_SKINS, $this->skin);
            }
            //POOL Library Project
            if ($baseLib)
                $places[] = buildDirPath($this->getPoolServerSideRelativePath(), $guiDirectory);
        }
        //Common-common-skin
        if (!$baseLib && defined('DIR_COMMON_ROOT_REL'))
            $places[] = buildDirPath(DIR_COMMON_ROOT_REL, PWD_TILL_SKINS, $this->commonSkinFolder, $elementSubFolder);
        $finds = [];
        //Searching
        foreach ($places as $place) {
            $file = buildFilePath($place, $filename);
            if (file_exists($file)) {
                $translatedFile = buildFilePath($place, $language, $filename);
                if (Template::isCacheTranslations() && file_exists($translatedFile)) {
                    // Language specific Ordner
                    $finds[] = $translatedFile;
                } elseif ($translate && Template::isCacheTranslations()) {
                    //Create Translated file and put it in the language folder
                    $finds[] = Template::attemptFileTranslation($file, $language);
                } else {// generic Ordner
                    $finds[] = $file;
                }//end decision which file to pick
                if (!$all) break;//stop searching after first match
            }
        }
        //grab first element for now
        return reset($finds) ?: "";
    }

    /**
     * Sucht das uebergebene JavaScript in einer fest vorgegebenen Verzeichnisstruktur.
     * Sucht im Ordner ./javascripts/ anschliessend in ./guis/$classFolder/ und ggf. noch in /poolcommons/guis/$classFolder/.
     * Wird der dritte Parameter benutzt, wird ausgehend von /pool/ anstelle des aktuellen Arbeitsverzeichnisses gesucht.
     * JavaScripts aus der Hauptbibliothek koennen nicht ueberschrieben werden (macht auch keinen Sinn).
     *
     * @param string $filename JavaScript Dateiname
     * @param string $classFolder Unterordner (guis/*) zur Klasse
     * @param bool $clientSideRelativePath If true, the path to the JavaScript is relative to the client side. If false, the path is relative to the
     *     server side.
     * @return string If successful, the path and filename of the JavaScript found are returned. In case of error an empty string.
     */
    public function findJavaScript(
        string $filename,
        string $classFolder = '',
        bool $baseLib = false,
        bool $raiseError = true,
        bool $clientSideRelativePath = true,
    ): string {
        //memcache
        $memKey = "findJavaScript.$classFolder.$filename.$baseLib.$clientSideRelativePath";
        if (($javaScriptFile = $this->getCachedItem($memKey, static::CACHE_FILE_ACCESS)) !== false) return $javaScriptFile;
        //cache-miss
        $relativeProjectPaths = [];
        //Ordner BaseLib -> look in POOL instead
        $relativeProjectPaths[] = $baseLib ? [$this->poolServerSideRelativePath, $this->poolClientSideRelativePath] : ['', ''];
        if (defined('DIR_COMMON_ROOT_REL')) {
            $relativeProjectPaths[] = [DIR_COMMON_ROOT_REL, DIR_COMMON_ROOT_REL];
        }
        $subDirs = [PWD_TILL_JS, buildDirPath(PWD_TILL_GUIS, $classFolder)];
        foreach ($relativeProjectPaths as [$project, $projectClientSide]) {
            foreach ($subDirs as $subDir) {
                $javaScriptFile = buildFilePath($project, $subDir, $filename);
                $javaScriptFileClientSide = buildFilePath($projectClientSide, $subDir, $filename);
                if (!file_exists($javaScriptFile)) continue;
                $javaScriptFile = $clientSideRelativePath ? $javaScriptFileClientSide : $javaScriptFile;
                $this->cacheItem($memKey, $javaScriptFile, static::CACHE_FILE_ACCESS);
                return $javaScriptFile;
            }
        }
        //premium error handling @todo replace
        if ($raiseError)
            $this->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s\' not found (@findJavaScript)!', $filename));
        else {
            $this->cacheItem($memKey, '', static::CACHE_FILE_ACCESS);
        }
        return '';
    }

    public function setPoolRelativePath(string $clientSidePath, string $serverSidePath): static
    {
        $this->poolClientSideRelativePath = $clientSidePath;
        $this->poolServerSideRelativePath = $serverSidePath;
        return $this;
    }

    /**
     * Client-side relative path to the pool root directory
     */
    public function getPoolClientSideRelativePath(string $subDir = ''): string
    {
        return $this->poolClientSideRelativePath.($subDir ? '/' : '').$subDir;
    }

    /**
     * Server-side relative path to the pool root directory
     */
    public function getPoolServerSideRelativePath(string $subDir = ''): string
    {
        return $this->poolServerSideRelativePath.($subDir ? '/' : '').$subDir;
    }

    /**
     * Transforms the PATH_INFO into an Input object.
     */
    protected function transformPathInfo(): Input
    {
        $Input = new Input();
        if (isset($_SERVER['PATH_INFO'])) {
            $pathInfo = trim($_SERVER['PATH_INFO'], '/');
            $segments = explode('/', $pathInfo);
            $count = count($segments);

            for ($i = 0; $i < $count; $i += 2) {
                $name = $segments[$i];
                $value = $segments[$i + 1] ?? null;
                $Input->setVar($name, $value);
            }
        }
        return $Input;
    }

    /**
     * Redirect to schema
     */
    public function redirect(string $schema, bool $withQuery = false, string $path = ''): never
    {
        $Url = new Url($withQuery);
        $Url->setParam(self::REQUEST_PARAM_SCHEMA, $schema);
        if ($path) $Url->setScriptPath($path);
        $Url->redirect();
    }

    /**
     * Initializes the settings for the application and sets up the translators.
     *
     * @param array $settings configuration parameters:
     *   application.name
     *   application.title
     *   application.charset
     *   application.locale
     *   application.version
     *   application.launchModule - sets the main module that is launched
     *   application.session.className - overrides default session class
     *   application.languages - array of languages
     *   application.translator Instance of Translator
     *   application.translatorResource Instance of TranslationProviderFactory
     *   application.translatorResourceDir Directory where translation files are stored
     * @throws Exception
     */
    public function setup(array $settings = []): static
    {
        $this->initializeSettings($settings);
        $this->setupTranslator($settings);
        return $this;
    }

    /**
     * Initializes the settings of the application.
     */
    protected function initializeSettings(array $settings): void
    {
        if ($this->isInitialized) return;

        $this->setupMemory($settings['memcached.servers'] ?? '', $settings['memcached.ttl'] ?? self::CACHE_TTL);
        // determine the relative client und server path from the application to the pool
        if (!\pool\IS_CLI) {
            $poolRelativePath = $this->getCachedItem('poolRelativePath') ?: makeRelativePathsFrom(null, DIR_POOL_ROOT); // try to find the pool
            $poolRelativePath['clientside'] = defined('DIR_RELATIVE_DOCUMENT_ROOT') ? DIR_RELATIVE_DOCUMENT_ROOT.'/'.basename(DIR_POOL_ROOT) : $poolRelativePath['clientside'];
            $this->setPoolRelativePath($poolRelativePath['clientside'], $poolRelativePath['serverside']);
            $this->cacheItem('poolRelativePath', $poolRelativePath);
        }

        // set well known setting
        $this->setName($settings['application.name'] ?? $this->getName());
        $this->setTitle($settings['application.title'] ?? $this->getTitle());
        $this->setCharset($settings['application.charset'] ?? $this->getCharset());
        $this->setLaunchModule($settings['application.launchModule'] ?? $this->getLaunchModule());
        $this->setVersion($settings['application.version'] ?? $this->getVersion());
        if ($this->getCachedItem('workingDirectory') !== self::$workingDirectory || $this->getCachedItem("version") !== $this->getVersion()) {
            // clear fs cache
            $this->clearCache(self::CACHE_FILE_ACCESS);
            $this->cacheItem('version', $this->getVersion());
            $this->cacheItem('workingDirectory', self::$workingDirectory);
        }

        $this->isInitialized = true;
    }

    /**
     * Starts a PHP Session via session_start()!
     * We use the standard php sessions.
     *
     * @param string $session_name Name der Session (Default: sid)
     * @param bool $useTransSID Transparente Session ID (Default: 0)
     * @param bool $useCookies Verwende Cookies (Default: 1)
     * @param bool $useOnlyCookies Verwende nur Cookies (Default: 0)
     * @param boolean $autoClose session will not be kept open during runtime. Each write opens and closes the session. Session is not locked in parallel execution.
     * @param string $sessionClassName default is session class
     * @throws SessionDisabledException
     * @throws RuntimeException
     */
    public function startPHPSession(
        string $session_name = 'WebAppSID',
        bool $useTransSID = false,
        bool $useCookies = true,
        bool $useOnlyCookies = false,
        bool $autoClose = true,
        string $sessionClassName = Session::class,
    ): static {
        switch ($sessionStatus = session_status()) {
            case PHP_SESSION_DISABLED:
                throw new SessionDisabledException();

            case PHP_SESSION_NONE:
                // setting ini is only possible, if the session is not started yet
                $sessionConfig = [
                    'session.name' => $session_name,
                    'session.use_trans_sid' => $useTransSID,
                    'session.use_cookies' => $useCookies,
                    'session.use_only_cookies' => $useOnlyCookies,
                ];
                foreach ($sessionConfig as $option => $value) {
                    if (ini_get($option) !== $value) ini_set($option, $value);
                }
                break;

            case PHP_SESSION_ACTIVE:
                // session is already started
                break;

            default:
                throw new RuntimeException("Got undocumented session status $sessionStatus");
        }

        // Check if session class is valid
        if ($sessionClassName !== Session::class && !is_subclass_of($sessionClassName, Session::class)) {
            throw new InvalidArgumentException('Session class must be instance of '.Session::class);
        }
        $this->Session ??= new $sessionClassName($autoClose);
        return $this;
    }

    /**
     * Set page title
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns page title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Returns requested ajax module
     */
    public static function getRequestedAjaxModule(): string
    {
        return $_REQUEST[self::REQUEST_PARAM_MODULE] ?? '';
    }

    /**
     * Return requested ajax method
     */
    public static function getRequestedAjaxMethod(): string
    {
        return $_REQUEST[self::REQUEST_PARAM_METHOD] ?? '';
    }

    /**
     * Set locale (the POOL is independent of the system locale, e.g. php's setlocale).
     */
    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string returns the fallback locale
     */
    protected function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    /**
     * Returns the determined locale (of the browser) if it has not been overwritten. Format: [language[_region][.charset][@modifier]]
     *
     * @see Weblication::setLocale()
     * @see Translator::getPrimaryLocale()
     */
    public function getLocale(int $type = self::LOCALE_UNCHANGED): string
    {
        if (!$this->locale) {
            $this->setLocale($this->getTranslator()->getPrimaryLocale());
        }

        if ($type === self::LOCALE_UNCHANGED) {
            return $this->locale;
        }

        $locale = $this->locale;

        // with region
        if ($type & self::LOCALE_FORCE_REGION && !(str_contains($locale, '_') || str_contains($locale, '-'))) {
            $locale = Language::getBestLocale($locale, $this->getDefaultLocale());
        }
        // with charset
        if ($type & self::LOCALE_FORCE_CHARSET && $this->charset && !str_contains($locale, '.')) {
            $locale = "$locale.$this->charset";
        }
        // without charset
        if ($type & self::LOCALE_WITHOUT_CHARSET && $pos = strrpos($locale, '.')) {
            $locale = substr($locale, 0, $pos);
        }
        return $locale;
    }

    /**
     * Sets the language for the Page. It's used for html templates and images
     *
     * @param string $lang Country Code
     */
    public function setLanguage(string $lang): static
    {
        $this->language = $lang;
        return $this;
    }

    /**
     * Returns the primary language based on the set locale
     *
     * @return string language code
     */
    public function getLanguage(): string
    {
        if (!$this->language) {
            $this->setLanguage(Locale::getPrimaryLanguage($this->getLocale(self::LOCALE_FORCE_REGION)));
        }
        return $this->language;
    }

    /**
     * Returns cookie for this application
     */
    public function getCookie(): Cookie
    {
        if (!$this->Cookie) {
            $this->Cookie = new Cookie();
        }
        return $this->Cookie;
    }

    public function setLaunchModule(string $launchModule): static
    {
        $this->launchModule = $launchModule;
        return $this;
    }

    /**
     * Returns module that should be launched
     */
    public function getLaunchModule(): string
    {
        return $_GET[self::REQUEST_PARAM_MODULE] ?? $this->launchModule;
    }

    /**
     * Render application
     *
     * @throws ModulNotFoundException
     * @throws Exception
     */
    public function render(): static
    {
        if ($this->run($this->getLaunchModule())) {
            echo $this->Main->render();
        }

        $measurePageSpeed = IS_DEVELOP || ((int)($_REQUEST['measurePageSpeed'] ?? 0));
        if ($measurePageSpeed && defined('POOL_START')) {
            $this->measurePageSpeed();
        }
        return $this;
    }

    /**
     * Creates the first GUI_Module in the chain (the page title is filled with the project name).
     *
     * @param string $className GUI_Module (Standard-Wert: GUI_CustomFrame)
     * @throws ModulNotFoundException|Exception
     */
    public function run(string $className = GUI_CustomFrame::class): static
    {
        // An application name is required. For example, the application name is used for creating directories in the data folder.
        if ($this->getName() === '') {
            throw new InvalidArgumentException('The application name must be defined.');
        }

        $mainGUI = GUI_Module::createGUIModule($className, $this, search: false);
        //maybe an Ajax Call could run here and return its result
        $this->setMain($mainGUI);

        $mainGUI->searchGUIsInPreloadedContent();

        if ($this->hasFrame()) {
            //Seitentitel (= Project)
            $Header = $this->getFrame()->getHeadData();

            $Header->setTitle($this->title);
            if ($this->charset) $Header->setCharset($this->charset);
        }
        return $this;
    }

    /**
     * Main logic of the front controller. compile main content.
     * @deprecated
     */
    protected function prepareContent(): void
    {
        $this->Main->provisionContent();
        if (!$this->Main->isAjax()) {
            $this->Main->prepareContent();
        }
    }

    /**
     * Return finished HTML content
     * Error handling wrapper around finalizeContent of the Main-GUI
     *
     * @return string website content
     */
    protected function finalizeContent(): string
    {
        return $this->Main->finalizeContent();
    }

    /**
     * Creates an array with given default values / structure for ajax results
     *
     * @return mixed
     * @deprecated
     */
    public static function makeAjaxArray(&...$result): array
    {
        foreach ($result as $key => &$value) {
            $value ??= match ($key) {
                'success' => false,
                'message' => '',
                'row', 'rows', 'data' => [],
                'count' => 0,
                default => null
            };
        }
        return $result;
    }

    #[Pure]
    /**
     * Creates an array with references to the variadic default values for ajax results
     *
     * @return mixed
     */
    public static function &makeResultArray(...$defaults): array
    {
        $references = [&$result];
        foreach ($defaults as $key => $value) {
            $result[$key] ??= $value;
            $references[] = &$result[$key];
        }
        return $references;
    }

    /**
     * Returns the current timezone
     */
    public function getTimezone(): string
    {
        return date_default_timezone_get();
    }

    public function isXdebugEnabled(): bool
    {
        if ($this->xdebug === null) {
            $this->xdebug = extension_loaded('xdebug');
        }
        return $this->xdebug;
    }

    /**
     * Measure page speed and print it in the footer
     *
     * @todo ajax requests?
     */
    public function measurePageSpeed(): void
    {
        register_shutdown_function(static function () {
            // print only when html content type is set
            if (!hasHtmlContentType()) {
                return;
            }

            $timeSpent = microtime(true) - POOL_START;
            $htmlStartTags = $htmlCloseTags = '';
            if (\pool\IS_CLI) {
                $what = 'Script';
            } else {
                $what = 'Page';
                $color = $timeSpent > 0.2 ? 'danger' : 'success';
                $htmlStartTags = "<footer class=\"container-fluid text-center\"><p class=\"fw-bold text-$color\">";
                $htmlCloseTags = '</p></footer>';
            }
            echo "$htmlStartTags$what was generated in $timeSpent sec.$htmlCloseTags";
        });
    }

    /**
     * Terminates Ajax requests with a caller-defined error
     */
    public function denyAJAX_Request($messageKey, $defaultMessage, $response_code, $errorType): void
    {
        if (self::isAjax()) {
            header('Content-Type: application/json', true, $response_code);
            $message = $this->getTranslator()->getTranslation($messageKey, $defaultMessage);
            $return = [
                'success' => false,
                'error' => [
                    'type' => $errorType,
                    'message' => $message,
                ],
            ];
            die(json_encode($return));
        }
    }

    public function logout(): void
    {
        // reset the session
        $this->Session->destroy();
        //header('Location: /logout', true, 302);
        //exit;
    }

    /**
     * Setup AppTranslator and TemplateTranslator
     *
     * @throws Exception
     */
    public function setupTranslator(array $settings): static
    {
        // setup AppTranslator
        $defaultLocale = $settings['application.locale'] ?? $this->defaultLocale;
        $AppTranslator = $settings['application.translator'] ?? null;
        $TranslatorResource = $settings['application.translatorResource'] ?? null;
        $translatorResourceDir = $settings['application.translatorResourceDir'] ?? '';
        if (!$AppTranslator instanceof Translator)
            $AppTranslator = new Translator();
        if (!$TranslatorResource instanceof TranslationProviderFactory) {
            if ($translatorResourceDir)// make a ressource from a given file
                $TranslatorResource = TranslationProviderFactory_ResourceFile::create($translatorResourceDir);
            elseif (count($AppTranslator->getTranslationResources()) > 0)// Translator is already loaded
                $TranslatorResource = null;
            else  // add Fallback or throw
                $TranslatorResource = TranslationProviderFactory_nop::create();
        }
        if ($TranslatorResource !== null)
            $AppTranslator->addTranslationResource($TranslatorResource);
        // Setup Languages (for Application)
        $AppLanguages = $settings['application.languages'] ?? null;
        // Get defaults from browser
        $AppLanguages ??= $AppTranslator->parseLangHeader(false, $defaultLocale);
        // Try to load the required languages
        $AppTranslator->swapLangList($AppLanguages, true);
        $this->setTranslator($AppTranslator);

        // setup TemplateTranslator
        $translatorStaticResourceDir = $settings['application.translatorStaticResourceDir'] ?? '';
        if ($translatorStaticResourceDir) {
            $staticResource = TranslationProviderFactory_ResourceFile::create($translatorStaticResourceDir);
            $TemplateTranslator = new Translator($staticResource);
            // Try to load the required languages
            $TemplateTranslator->swapLangList($AppLanguages, true);
            Template::setTranslator($TemplateTranslator);
        }
        return $this;
    }

    /**
     * Generate a cache key
     */
    private function generateCacheKey(string $key, string $topic): string
    {
        return "$topic:{$this->getName()}.$key";
    }

    /**
     * Check if memory is available
     */
    private function isMemoryAvailable(): bool
    {
        return (bool)($this->memory ?? false);
    }

    /**
     * @param string $servers memcached servers e.g. localhost:11211
     * @param int $expiration cache TTL in seconds
     */
    private function setupMemory(string $servers, int $expiration): void
    {
        if (!extension_loaded('memcached')) {
            if ($servers) throw new RuntimeException('Memcached extension is not loaded. Please install the memcached extension.');
            return;
        }

        $this->memory ??= Memory::getInstance($servers);
        $this->memory->setDefaultExpiration($expiration);
    }

    /**
     * Cache an item if memory is available
     */
    public function cacheItem(string $key, mixed $item, string $topic = self::CACHE_ITEM): bool
    {
        if (!$this->isMemoryAvailable()) return false;
        if (!self::$cacheItem[$topic]) return false;
        $memKey = $this->generateCacheKey($key, $topic);
        return $this->memory->setValue($memKey, $item);
    }

    /**
     * Returns the cached item or false if the item was not found or memory is not available
     */
    public function getCachedItem(string $key, string $topic = self::CACHE_ITEM): mixed
    {
        if (!$this->isMemoryAvailable()) return false;
        if (!self::$cacheItem[$topic]) return false;
        $memKey = $this->generateCacheKey($key, $topic);
        return $this->memory->get($memKey);
    }

    /**
     * Clear the cache for file system access (prefix "fs:")
     */
    private function clearCache(string $topic = self::CACHE_ITEM): void
    {
        if (!$this->isMemoryAvailable()) return;
        if (!self::$cacheItem[$topic]) return;
        $allKeys = $this->memory->getAllKeys();
        $keys = [];
        foreach ($allKeys as $key) {
            if (str_starts_with($key, $topic)) {
                $keys[] = $key;
            }
        }
        $this->memory->deleteMulti($keys);
    }

    public static function getCoreRequestParameters(): array
    {
        return [
            Weblication::REQUEST_PARAM_SCHEMA,
            Weblication::REQUEST_PARAM_MODULE,
            Weblication::REQUEST_PARAM_METHOD,
        ];
    }

    /**
     * En- disables Caching
     *
     * @todo control individual caching topics
     */
    public static function caching(bool $enable = true): void
    {
        static::$cacheItem[static::CACHE_ITEM] = $enable;
        static::$cacheItem[static::CACHE_FILE] = $enable;
        static::$cacheItem[static::CACHE_FILE_ACCESS] = $enable;
    }
}