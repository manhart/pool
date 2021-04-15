<?php declare(strict_types=1);
/**
 *        -==|| Rapid Module Library (RML) ||==-
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
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

use pool\classes\Translator;

/**
 * Weblication
 *
 * Klasse fuer eine Webanwendung.
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Weblication.class.php,v 1.16 2007/05/31 14:36:23 manhart Exp $
 * @access public
 **/
class Weblication extends Component
{
    /**
     * name of the project
     *
     * @var string $project
     */
    private string $project = 'unknown';

    /**
     * Titel der Weblication
     *
     * @var string
     */
    private string $title = '';

    /**
     * Enthaelt das erste geladene GUI_Module (wird in Weblication::run() eingeleitet)
     *
     * @var GUI_Frame $Frame
     * @access private
     */
    var $Frame = null;

    /**
     * Enthaelt das erste geladene GUI_Module (wird in Weblication::run() eingeleitet)
     *
     * @var GUI_Module $Main
     * @access private
     */
    var $Main = null;

    /**
     * PHP Session gekapselt in ISession
     *
     * @var ISession $Session
     * @access public
     */
    var $Session = null;

    /**
     * @var Weblication|null
     */
    private static ?Weblication $Instance = null;

    /**
     * Benutzer Klasse (nicht realisiert)
     *
     * @var User
     * @access private
     */
    var $User = null;

    /**
     * Relativer Pfad zur Hauptbibliothek
     *
     * @var string
     * @access private
     */
    private string $relativePathBaselib = '';

    /**
     * Skin / Theme (Designvorlage bzw. Bilderordner)
     *
     * @var string
     */
    private $skin = 'default';

    /**
     * @var array|null
     */
    private ?array $skins = null;

    /**
     * Schema / Layout (index ist das Standard-Schema)
     *
     * @var string
     * @access private
     */
    private $schema = 'index';

    /**
     * Bewahrt alle Schnittstellen Instanzen der unterschiedlichsten Speichermedien als Liste auf
     *
     * @var array
     * @access private
     */
    var $Interfaces = array();

    /**
     * Zeichensatz
     *
     * @var string
     */
    private $charset = '';

    /**
     * Programm ID
     *
     * @var int
     */
    var $progId = null;

    /**
     * @var string
     */
    private $cssFolder = 'css';

    /**
     * @var Input App Settings
     */
    private $Settings = null;

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
    private $language = 'de';

    /**
     * @var string
     */
    private string $subdirTranslated = '';

    /**
     * @var string version of the application
     */
    private string $version = '';

    /**
     * @var array all possible default formats
     */
    private array $formats = [
        'time' => 'H:i',
        'date' => 'd.m.Y',
        'datetime' => 'd.m.Y H:i',
        'time.strftime' => '%H:%M',
        'date.strftime' => '%d.%m.%Y',
        'datetime.strftime' => '%d.%m.%Y %H:%M',
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
        $Nil = new Nil();
        parent::__construct($Nil);

        $this->Settings = new Input();
        return $this;
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): Weblication
    {
        if (static::$Instance === null) {
            static::$Instance = new static();
        }

        return static::$Instance;
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
     * Aendert den Ordner fuer die Designvorlagen (Html Templates) und Bilder.
     *
     * @access public
     * @param string $skin Ordner fuer die Designvorlagen (Html Templates) und Bilder. (Standardwert: default)
     * @return Weblication
     */
    function setSkin($skin = 'default')
    {
        $this->skin = $skin;
        return $this;
    }

    /**
     * Liefert den Ordner (Namen) der aktuellen Designvorlagen und Bilder zurueck.
     * (wird derzeit fuer die Bilder und Html Templates missbraucht)
     *
     * @access public
     * @return string Name des Designs (Skin)
     **/
    function getSkin()
    {
        return $this->skin;
    }

    /**
     * Sets the language for the Page. It's used for html templates and images
     *
     * @param string $lang Country Code
     * @param string $resourceDir Directory with translations e.g. de.php, en.php
     * @param string $subdirTranslated Subdirectory for generated static translated templates during the deployment process
     * @return Weblication
     * @throws Exception
     */
    function setLanguage(string $lang = 'de', string $resourceDir = '', string $subdirTranslated = '')
    {
        $this->language = $lang;

        if ($resourceDir) {
            Translator::getInstance()->setResourceDir($resourceDir)->setDefaultLanguage($lang);
        }

        $this->subdirTranslated = $subdirTranslated;
        return $this;
    }

    /**
     * Liefert die Sprache der Seite.
     *
     * @access public
     * @return string Sprache der Webseite
     **/
    function getLanguage()
    {
        return $this->language; // $this->language;
    }

    /**
     * Get translator
     *
     * @param string|null $language overrides default language
     * @return Translator
     */
    public function getTranslator(?string $language = null): Translator
    {
        $Translator = Translator::getInstance();
        if ($language) {
            $Translator->changeLanguage($language);
        }
        return $Translator;
    }

    /**
     * Liefert den Zeichensatz der Webanwendung zurueck
     *
     * @return string
     */
    function getCharset()
    {
        return $this->charset;
    }

    /**
     * Set charset for the Web Application
     *
     * @param string $charset
     * @return Weblication
     */
    function setCharset(string $charset): Weblication
    {
        header('content-type: text/html; charset=' . $charset);
        $this->charset = strtoupper($charset);
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
     * @return string|array|null
     */
    public function getDefaultFormat(string $key)
    {
        return $this->formats[$key] ?? '';
    }

    /**
     * Setzt die Programm ID
     *
     * @param int $progId
     */
    function setProgId($progId)
    {
        $this->progId = $progId;
    }

    /**
     * Liefert die Programm ID
     *
     * @return int|null
     */
    function getProgId()
    {
        return $this->progId;
    }

    /**
     * Setzt das Standard Schema, welches geladen wird, wenn kein Schema uebergeben wurde.
     *
     * @param string $default Standard Schema
     **@deprecated
     * @access public
     */
    function setSchema($default = 'index')
    {
        $this->schema = trim($default);
    }

    /**
     * set default schema/layout, if none is loaded by request
     *
     * @param string $default
     * @return Weblication
     */
    public function setDefaultSchema($default = 'index')
    {
        $this->schema = trim($default);
        return $this;
    }

    /**
     * returns the default scheme
     *
     * @access public
     * @return string default schema
     **/
    public function getDefaultSchema()
    {
        return $this->schema;
    }

    /**
     * determines current schema/layout
     *
     * @return string
     */
    public function getSchema()
    {
        return (isset($_REQUEST['schema']) and $_REQUEST['schema'] != '') ? $_REQUEST['schema'] : $this->getDefaultSchema();
    }

    /**
     * @param string $version application version
     * @return Weblication
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @return string returns application version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Setzt das Haupt-GUI.
     *
     * @param GUI_Module $GUI_Module
     */
    function setMain(&$GUI_Module)
    {
        $this->Main = &$GUI_Module;
    }

    /**
     * Liefert das Haupt-GUI (meistens erstes GUI, das im Startscript �bergeben wurde).
     *
     * @return GUI_Module
     */
    function &getMain()
    {
        return $this->Main;
    }

    /**
     * returns the main frame
     *
     * @return GUI_CustomFrame
     * @throws Exception
     **/
    function &getFrame()
    {
        if ($this->Main instanceof GUI_CustomFrame) {
            return $this->Main;
        }
        throw new Exception('No Frame there');
    }

    /**
     * Is there a frame?
     *
     * @return bool has GUI_CustomFrame
     */
    function hasFrame()
    {
        return ($this->Main instanceof GUI_CustomFrame);
    }

    /**
     * Sets a common skin folder
     *
     * @param string $skinName
     * @return Weblication
     */
    public function setCommonSkinFolder(string $skinName)
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
     * Liefert den Pfad zu den Templates (abh�ngig vom Skin-Ordner und der gew�hlten Sprache).
     *
     * @access public
     * @param string $additionalDir Ordner werden an ermittelten Template Pfad geh�ngt
     * @return string Pfad
     */
    function getTemplatePath($additionalDir = ''/*, $baselib=false*/)
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
        if (is_dir($folder_language)) { // Language Ordner
            $path = $folder_language;
        }
        else if (is_dir($folder_dir)) { // Template Ordner
            $path = $folder_dir;
        }
        /*}*/

        if ($additionalDir) {
            $path = addEndingSlash($path) . $additionalDir;
        }

        if (!is_dir($path)) {
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
    function skin_exists($skin = '')
    {
        $skin = addEndingSlash(($skin ? $skin : $this->skin));
        $pathSkin = addEndingSlash(getcwd()) . addEndingSlash(PWD_TILL_SKINS) . $skin;
        return file_exists($pathSkin);
    }

    /**
     * Liefert einen Pfad zum Skin-Verzeichnis zurück. Wenn der Parameter $additionalDir gef�llt wird, wird er an das Skin-Verzeichnis dran geh�ngt.
     *
     * @param string $additionalDir Unterverzeichnis vom Skin-Verzeichnis
     * @return string
     */
    function getSkinPath($additionalDir = '', $absolute = true)
    {
        $path = '';
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);

        # Ordner Skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
        if ($absolute) {
            $folder_skins = addEndingSlash(getcwd()) . $folder_skins;
        }
        $folder_language = $folder_skins . $language;
        if ($additionalDir != '') {
            $folder_skin_dir = addEndingSlash($folder_skins) . $additionalDir;
            $folder_language_dir = addEndingSlash($folder_language) . $additionalDir;
        }
        else {
            $folder_skin_dir = $folder_skins;
            $folder_language_dir = $folder_language;
        }

        if (is_dir($folder_language_dir)) {
            $path = $folder_language_dir;
        }
        elseif (is_dir($folder_skin_dir)) {
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
            for ($i = 0; $i < $numDirs; $i++) {
                $skinName = basename($skinDirs[$i]);
                if ($skinName != $this->getCommonSkinFolder()) {
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
    function findImage($filename)
    {
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);
        $images = 'images/';

        # Ordner skins
        $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
        $folder_images = $folder_skins . $images;
        $folder_language = $folder_skins . addEndingSlash($language) . $images;

        if (is_dir($folder_language)) { // Language Ordner
            if (file_exists($folder_language . $filename)) {
                return $folder_language . $filename;
            }
        }
        if (is_dir($folder_images)) { // Images Ordner
            if (file_exists($folder_images . $filename)) {
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
        if (is_null($this->hasCommonSkinFolder)) {
            $this->hasCommonSkinFolder = [];
            $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->commonSkinFolder);
        }
        if ($subfolder != null and $this->hasCommonSkinFolder[$this->commonSkinFolder]['__exists']) {
            if (!isset($this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder])) $this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder] = null;
            if (is_null($this->hasCommonSkinFolder[$this->commonSkinFolder][$subfolder])) {
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
        if (!isset($this->hasSkinFolder[$this->skin])) {
            $this->hasSkinFolder[$this->skin] = [];
            $this->hasSkinFolder[$this->skin]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin);
        }
        if ($subfolder != null and $this->hasSkinFolder[$this->skin]['__exists']) {
            if (!isset($this->hasSkinFolder[$this->skin][$subfolder])) {
                $this->hasSkinFolder[$this->skin][$subfolder] = [];
                $this->hasSkinFolder[$this->skin][$subfolder]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin . '/' . $subfolder);
            }
            if (is_null($language) and is_null($translated)) {
                return $this->hasSkinFolder[$this->skin][$subfolder]['__exists'];
            }
            else {
                if ($this->hasSkinFolder[$this->skin][$subfolder]['__exists']) {
                    if (!isset($this->hasSkinFolder[$this->skin][$subfolder][$language])) {
                        $this->hasSkinFolder[$this->skin][$subfolder][$language] = [];
                        $this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists'] = is_dir(PWD_TILL_SKINS . '/' . $this->skin . '/' . $subfolder . '/' . $language);
                    }
                    if(is_null($translated)) {
                        return $this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists'];
                    }
                    else {
                        if ($this->hasSkinFolder[$this->skin][$subfolder][$language]['__exists']) {
                            if (!isset($this->hasSkinFolder[$this->skin][$subfolder][$language][$translated])) {
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
     * wird abschliessend noch in der baselib gesucht.
     *
     * @param string $filename Template Dateiname
     * @param string $classfolder Unterordner (guis/*) zur Klasse
     * @param boolean $baselib Schau auch in die baselib
     * @return string Bei Erfolg Pfad und Dateiname des gefunden Templates. Im Fehlerfall ''.
     **/
    function findTemplate(string $filename, string $classfolder = '', bool $baselib = false)
    {
        $skin = $this->skin;
        $language = $this->language;
        $templates = 'templates';

        # Ordner skins
        $skinFolder = PWD_TILL_SKINS . '/' . $skin;

        // static translation templates have priority
        if($this->subdirTranslated) {
            $translatedFolder = $skinFolder . '/' . $templates . '/' . $language . '/' . $this->subdirTranslated;
            if($this->hasSkinFolder($templates, $language, $this->subdirTranslated)) {
                if (file_exists($translatedFolder . '/' . $filename)) {
                    return $translatedFolder . '/' . $filename;
                }
            }
        }

        # folder: common
        if ($this->hasCommonSkinFolder($templates)) {
            $folder_common_templates = PWD_TILL_SKINS . '/' . $this->commonSkinFolder . '/' . $templates;
            if (file_exists($folder_common_templates . '/' . $filename)) {
                return $folder_common_templates . '/' . $filename;
            }
        }

        # folder: templates
        $languageFolder = $skinFolder . '/' . $templates . '/' . $language;
        if ($this->hasSkinFolder($templates, $language)) { // with language, more specific
            if (file_exists($languageFolder . '/' . $filename)) {
                return $languageFolder . '/' . $filename;
            }
        }

        if ($this->hasSkinFolder($templates, null)) { // without language
            $templatesFolder = $skinFolder . '/' . $templates;
            if (file_exists($templatesFolder . '/' . $filename)) {
                return $templatesFolder . '/' . $filename;
            }
        }

        # Ordner Projekt guis
        $gui_directories = [];
        if($classfolder) {
            $folder_guis = PWD_TILL_GUIS . '/' . $classfolder;
            $gui_directories[] = $folder_guis;

            # Ordner Commons guis
            if (defined('DIR_COMMON_ROOT')) {
                $gui_directories[] = addEndingSlash(DIR_COMMON_ROOT) . $folder_guis;
            }
        }

        foreach ($gui_directories as $folder_guis) {
            $folder_skins = $folder_guis . '/'.$skin;
            $folder_language = $folder_skins . '/' . $language;
            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language . '/' . $filename)) {
                    return $folder_language . '/' . $filename;
                }
            }
            if (is_dir($folder_skins)) { // Skin Ordner
                if (file_exists($folder_skins . '/' . $filename)) {
                    return $folder_skins . '/' . $filename;
                }
            }
            if (is_dir($folder_guis)) { // GUI Ordner
                if (file_exists($folder_guis . '/'.$filename)) {
                    return $folder_guis . '/'.$filename;
                }
            }
        }


        # Ordner baselib
        if ($baselib) {
            $folder = __DIR__.'/../'.PWD_TILL_GUIS.'/' . addEndingSlash($classfolder);
            if (is_dir($folder)) {
                if (file_exists($folder . $filename)) {
                    return $folder . $filename;
                }
            }
        }

        // Lowercase Workaround @deprecated
        if (preg_match('/[A-Z]/', $filename . $classfolder)) {
            // try lower case
            // todo log buggy code
            if(defined('IS_DEVELOP') and IS_DEVELOP) {
                $this->raiseError(__FILE__, __LINE__, 'Please use strtolower in your project to find '.$filename.' in '.$classfolder);
            }
            return $this->findTemplate(strtolower($filename), strtolower($classfolder), $baselib);
        }

        $this->raiseError(__FILE__, __LINE__, sprintf('Template \'%s\' not found (@Weblication->findTemplate)!', $filename));
        return '';
    }

    /**
     * Sucht das uebergebene StyleSheet in einer fest vorgegebenen Verzeichnisstruktur.
     * Zuerst im Ordner skins, als naechstes im guis Ordner.
     *
     * @param string $filename StyleSheet Dateiname
     * @param string $classfolder Unterordner (guis/*) zur Klasse
     * @param boolean $baselib Schau auch in die baselib
     * @return string Bei Erfolg Pfad und Dateiname des gefunden StyleSheets. Im Fehlerfall ''.
     **/
    function findStyleSheet(string $filename, string $classfolder = '', bool $baselib = false)
    {
        $skin = addEndingSlash($this->skin);
        $language = addEndingSlash($this->language);

        # Ordner skins
        $skinFolder = PWD_TILL_SKINS . '/' . $skin;

        # folder: common
        if ($this->hasCommonSkinFolder($this->cssFolder)) {
            $folder_common_styles = PWD_TILL_SKINS . '/' . $this->commonSkinFolder . '/' . $this->cssFolder;
            if (file_exists($folder_common_styles . '/' . $filename)) {
                return $folder_common_styles . '/' . $filename;
            }
        }

        // folder: skins
        $languageFolder = $skinFolder . '/' . $this->cssFolder . '/' . $language;
        if ($this->hasSkinFolder($this->cssFolder, $language)) { // with language, more specific
            if (file_exists($languageFolder . '/' . $filename)) {
                return $languageFolder . '/' . $filename;
            }
        }

        if ($this->hasSkinFolder($this->cssFolder, null)) { // without language
            $stylesheetsFolder = $skinFolder . '/' . $this->cssFolder;
            if (file_exists($stylesheetsFolder . '/' . $filename)) {
                return $stylesheetsFolder . '/' . $filename;
            }
        }


        $gui_directories = [];
        if($classfolder) {
            $folder_guis = PWD_TILL_GUIS . '/' . $classfolder;
            $gui_directories[] = $folder_guis;

            # Ordner Commons guis
            if (defined('DIR_COMMON_ROOT_REL')) { // addEndingSlash(DIR_COMMON_ROOT_REL)
                $gui_directories[] = addEndingSlash(DIR_COMMON_ROOT_REL) . $folder_guis;
            }
        }

        foreach ($gui_directories as $folder_guis) {
            # Projekt folder: guis
            $folder_skin = $folder_guis . '/' . $skin;
            $folder_language = $folder_skin . '/'. $language;
            if (is_dir($folder_language)) { // guis - classname - skin - language folder
                if (file_exists($folder_language . '/' . $filename)) {
                    return $folder_language . '/' . $filename;
                }
            }
            if (is_dir($folder_skin)) { // guis - classname - skin folder
                if (file_exists($folder_skin . '/' . $filename)) {
                    return $folder_skin . '/' . $filename;
                }
            }
            if (is_dir($folder_guis)) { // guis - classname folder
                if (file_exists($folder_guis . '/' . $filename)) {
                    return $folder_guis . '/' . $filename;
                }
            }
        }

        # Ordner baselib
        if ($baselib) {
            $folder = $this->getRelativePathBaselib(PWD_TILL_GUIS.'/'.$classfolder);
            if (is_dir($folder)) {
                $file = $folder . '/'. $filename;
                if (file_exists($file)) {
                    return $file;
                }
            }
        }

        // Lowercase Workaround:
        if (preg_match('/[A-Z]/', $filename . $classfolder)) {
            // try lower case
            // todo log buggy code
            if(defined('IS_DEVELOP') and IS_DEVELOP) {
                $this->raiseError(__FILE__, __LINE__, 'Please use strtolower in your project to find '.$filename.' in '.$classfolder);
            }
            return $this->findStyleSheet(strtolower($filename), strtolower($classfolder), $baselib);
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('StyleSheet \'%s\' not found (@Weblication->findStyleSheet)!', $filename));
        }
        return '';
    }

    /**
     * Sucht das uebergebene JavaScript in einer fest vorgegebenen Verzeichnisstruktur.
     * Sucht im Ordner /javascripts! Der 2. Parameter wird momentan nicht beruecksichtigt.
     * Wird der dritte Parameter benutzt, werden die JavaScript Dateien in der Hauptbibliothek gesucht.
     *
     * JavaScripts aus der Hauptbibliothek koennen nicht ueberschrieben werden (macht auch keinen Sinn).
     *
     * @param string $filename JavaScript Dateiname
     * @param string $classfolder Unterordner (guis/*) zur Klasse
     * @return string Bei Erfolg Pfad und Dateiname des gefunden JavaScripts. Im Fehlerfall ''.
     **/
    function findJavaScript(string $filename, string $classfolder = '', bool $baselib = false)
    {
        $javascripts = addEndingSlash(PWD_TILL_JAVASCRIPTS);

        # Ordner skins
        $folder_javascripts = $javascripts;

        # Ordner baselib
        if ($baselib) {
            $folder = $this->getRelativePathBaselib($javascripts);
            if (file_exists($folder . '/' . $filename)) {
                return $folder . '/'. $filename;
            }

            $folder_guis = $this->getRelativePathBaselib(PWD_TILL_GUIS).'/'.$classfolder;
            if (file_exists($folder_guis.'/'.$filename)) {
                return $folder_guis . '/'.$filename;
            }
        }
        else {
            $folder_guis = addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);

            if (file_exists($folder_javascripts . $filename)) {
                return $folder_javascripts . $filename;
            }

            if (file_exists($folder_guis . $filename)) {
                return $folder_guis . $filename;
            }

            if (defined('DIR_COMMON_ROOT_REL')) {
                $folder_common = addEndingSlash(DIR_COMMON_ROOT_REL) . addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
                if (file_exists($folder_common . $filename)) {
                    return $folder_common . $filename;
                }
            }
        }

        // Lowercase Workaround:
        if (preg_match('/[A-Z]/', $filename . $classfolder)) {
            // try lower case
            // todo log buggy code
            if(defined('IS_DEVELOP') and IS_DEVELOP) {
                $this->raiseError(__FILE__, __LINE__, 'Please use strtolower in your project to find '.$filename.' in '.$classfolder);
            }
            return $this->findJavaScript(strtolower($filename), strtolower($classfolder), $baselib);
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s\' not found (@findJavaScript)!', $filename));
        }
        return '';
    }

    /**
     * @param string $path
     */
    function setRelativePathBaselib(string $path)
    {
        $this->relativePathBaselib = $path;
    }

    /**
     * Relativer Pfad zum Rootverzeichnis der Baselib
     *
     * @access public
     * @param string $subdir
     * @return string path from project to library pool
     */
    function getRelativePathBaselib(string $subdir = '')
    {
        return $this->relativePathBaselib . '/' . $subdir;
    }

    /**
     * Erzeugt das MySQL Datenbank Objekt
     *
     * @param string $host Hostname des Datenbankservers
     * @param string $dbname Standard Datenbankname
     * @param string $name_of_auth_array Name des Authentifizierungsarrays
     * @param boolean $persistent
     * @return object MySQL_db
     **@deprecated
     * @access public
     */
    function &createMySQL($host, $dbname, $name_of_auth_array = 'mysql_auth', $persistent = false)
    {
        $Packet = array(
            'host' => $host,
            'database' => $dbname,
            'auth' => $name_of_auth_array,
            'persistency' => $persistent
        );
        $MySQLInterface = &DataInterface::createDataInterface(DATAINTERFACE_MYSQL, $Packet);

        $DI = &$this->addDataInterface($MySQLInterface);
        return $DI;
    }

    /**
     * Erzeugt das CISAM Client Objekt (not yet implemented)
     *
     * @param string $host Hostname des Java Servers
     * @param string $class_path Java Klassenpfad
     * @access public
     **@deprecated
     */
    function & createCISAM($host, $class_path)
    {
        $Packet = array(
            'host' => $host,
            'class_path' => $class_path
        );
        $CISAMInterface = &DataInterface::createDataInterface(DATAINTERFACE_CISAM, $Packet);

        return $this->addDataInterface($CISAMInterface);
    }

    /**
     * DataInterface in die Anwendung einfuegen. Somit ist es ueberall bekannt und kann
     * fuer die DAO Geschichte verwendet werden.
     *
     * @access public
     * @param object $DataInterface Einzufuegendes DataInterface
     * @return object Eingefuegtes DataInterface
     **/
    function &addDataInterface(&$DataInterface)
    {
        $interfaces = $this->Interfaces[$DataInterface->getInterfaceType()] = &$DataInterface;
        return $interfaces;
    }

    /**
     * Liefert ein Interface Objekt.
     *
     * @access public
     * @param string $interface_name
     * @return DataInterface Interface Objekt
     **/
    function &getInterface($interface_name)
    {
        return $this->Interfaces[$interface_name];
    }

    /**
     * Liefert alle Interface Objekte.
     *
     * @access public
     * @return array Interface Objekte
     * @see DAO::createDAO()
     **/
    public function getInterfaces()
    {
        return $this->Interfaces;
    }

    /**
     * starts any session derived from the class ISession
     *
     * @param array $settings configuration parameters:
     *                        sessionClassName - overrides default session class
     * @return Weblication
     */
    public function setup(array $settings)
    {
        $this->Settings->setVar($settings);
        return $this;
    }

    /**
     * Starts a PHP Session via session_start()!
     * We use the standard php sessions.
     *
     * @access public|static
     * @param string $session_name Name der Session (Default: sid)
     * @param integer $use_trans_sid Transparente Session ID (Default: 0)
     * @param integer $use_cookies Verwende Cookies (Default: 1)
     * @param integer $use_only_cookies Verwende nur Cookies (Default: 0)
     * @param boolean $autoClose session will not be kept open during runtime. Each write opens and closes the session. Session is not locked in parallel execution.
     * @return ISession
     **/
    public function startPHPSession($session_name = 'PHPSESSID', $use_trans_sid = 0, $use_cookies = 1, $use_only_cookies = 0, $autoClose = true)
    {
        $sessionConfig = array(
            'name' => $session_name,
            'use_trans_sid' => $use_trans_sid,
            'use_cookies' => $use_cookies,
            'use_only_cookies' => $use_only_cookies
        );
        foreach ($sessionConfig as $param => $value) {
            ini_set('session.' . $param, (string)$value);
        }

        $isStatic = !(isset($this)); // TODO static calls or static AppSettings
        if ($isStatic) {
            return new ISession($autoClose);
        }
        $className = $this->Settings->getVar('sessionClassName', 'ISession');
        $this->Session = new $className($autoClose);
        return $this->Session;
    }

    /**
     * Erzeugt eine Instanz vom eigenen Session Handler. Ansprechbar ueber Weblication::Session.
     *
     * @access public
     * @param string $tabledefine DAO Tabellendefinition.
     **/
    function createSessionHandler($tabledefine)
    {
        $this->SessionHandler = new SessionHandler($this->Interfaces, $tabledefine);
        $this->Session = new ISession();
    }

    /**
     * Komprimiert Html Ausgaben (Entfernt Kommentare, Leerzeichen, Zeilenvorschuebe)
     *
     * @access public
     * @param string $html Content
     * @return string Komprimierter Content
     **/
    private function minify($html)
    {
        //$html = ereg_replace("<!--.*-->", "", $html);
        //$html = str_replace("\n\r", '', $html);
        $html = str_replace(array("\n", "\r"), '', $html);
        $space_str = '   ';
        $html = str_replace($space_str, '', $html);
        return $html;
    }

    /**
     * Seitentitel setzen
     *
     * @param string $title
     * @return Weblication
     */
    public function setTitle(string $title)
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
     * @param string|null
     * @return false|string
     */
    public function setLocale(int $category = LC_ALL, ?string $locale = null)
    {
        if(is_null($locale)) {
            $locale = Translator::detectLocale();
        }
        $this->locale = $locale;
        return setlocale($category, $locale.($this->charset ? '.'.$this->charset : ''));
    }

    /**
     * Erzeugt das erste GUI_Module in der Kette (Momentan wird hier der Seitentitel mit dem Projektnamen gefuellt).
     *
     * @access public
     * @param string $className GUI_Module (Standard-Wert: GUI_CustomFrame)
     * @return Weblication
     *
     * @throws Exception
     */
    public function run($className = 'GUI_CustomFrame')
    {
        // TODO Get Parameter frame
        // TODO Subcode :: createSubCode()
        $params = $_REQUEST['params'] ?? '';
        if ($params != '' and isAjax()) {
            $params = base64url_decode($params);
        }

        $Nil = new Nil();
        $GUI = GUI_Module::createGUIModule($className, $this, $Nil, $params);
        if (isNil($GUI)) {
            throw new Exception('The class name '.$className.' was not found or does not exist');
        }
        else {
            /** Hinweis: erstes GUI registriert sich selbst �ber setMain als
             * Haupt-GUI im GUI_Module Konstruktor **/

            if ($this->Main instanceof GUI_CustomFrame) {
                # Seitentitel (= Project)
                $Header = &$this->Main->getHeaderdata();
                if ($Header) {
                    $title = $this->title;
                    $Header->setTitle($title);
                    $Header->setLanguage($this->language);
                    if ($this->charset) $Header->setCharset($this->charset);
                }
            }
        }
        return $this;
    }

    /**
     * Einleitung zur Aufbereitung des Contents (der Inhalte) der Webseite.
     *
     * @access public
     **/
    public function prepareContent()
    {
        if ($this->Main instanceof GUI_Module) {
            $this->Main->prepareContent();
        }
        else {
            $this->raiseError(__FILE__, __LINE__, 'Main ist nicht vom Typ GUI_Module oder nicht gesetzt (@PrepareContent).');
        }
    }

    /**
     * Passt die Verzeichnisse der Bilder auf den Skin und/oder die Sprache an.
     *
     * @param string $content Inhalt eines Templates
     * @return string
     */
    function adjustImageDir($content)
    {
        $folderImages = 'skins/' . $this->skin;
        if (is_dir($folderImages . '/' . $this->language)) {
            $folderImages .= '/' . $this->language;
        }
        $folderImages .= '/images/';

        $content = str_replace($folderImages, 'images/', $content);
        $content = str_replace('images/', $folderImages, $content);

        return $content;
    }

    /**
     * Fertigen Content (Inhalt) ausgeben.
     *
     * @access public
     * @param boolean $print True gibt den Inhalt sofort auf den Bildschirm aus. False liefert den Inhalt zurueck
     * @param bool $minify simple minifier
     * @return string Inhalt der Webseite
     **/
    public function finalizeContent($print = true, $minify = false)
    {
        if ($this->Main instanceof GUI_Module) {
            $content = $this->Main->finalizeContent();

            $content = $this->adjustImageDir($content);

            if ($minify) {
                $content = $this->minify($content);
            }

            if ($print) {
                print $content;
            }
            else {
                return $content;
            }
        }
        else {
            $this->raiseError(__FILE__, __LINE__, 'Main ist nicht vom Typ GUI_Module oder nicht gesetzt (@CreateContent).');
        }
    }

    /**
     * Schliesst alle Verbindungen und loescht die Interface Objekte.
     * Bitte bei der Erstellung von Interface Objekten sicherheitshalber immer abschliessend mit destroy() alle Verbindungen trennen!
     *
     * @access public
     **/
    function destroy()
    {
        if (defined('DATAINTERFACE_MYSQL')) {
            if (isset($this->Interfaces[DATAINTERFACE_MYSQL]) and is_a($this->Interfaces[DATAINTERFACE_MYSQL], 'MySQL_Interface')) {
                $this->Interfaces[DATAINTERFACE_MYSQL]->close();
                unset($this->Interfaces[DATAINTERFACE_MYSQL]);
            }
        }

        if (defined('DATAINTERFACE_MYSQLI')) {
            if (isset($this->Interfaces[DATAINTERFACE_MYSQLI]) and is_a($this->Interfaces[DATAINTERFACE_MYSQLI], 'MySQLi_Interface')) {
                $this->Interfaces[DATAINTERFACE_MYSQLI]->close();
                unset($this->Interfaces[DATAINTERFACE_MYSQLI]);
            }
        }
        if (defined('DATAINTERFACE_C16')) {
            if (isset($this->Interfaces[DATAINTERFACE_C16]) and is_a($this->Interfaces[DATAINTERFACE_C16], 'C16_Interface')) {
                $this->Interfaces[DATAINTERFACE_C16]->close();
                unset($this->Interfaces[DATAINTERFACE_C16]);
            }
        }
        parent::destroy();
    }
}