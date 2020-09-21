<?php
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

if (!defined('CLASS_WEBLICATION')) {

    define('CLASS_WEBLICATION', 1);    // Prevent multiple loading

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
         * Name des Projekts (wird im Konstruktor gesetzt)
         *
         * @var string $Project
         * @access private
         */
        var $Project = 'unknown';

        /**
         * Titel der Weblication
         *
         * @var string
         */
        private $Title = '';

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
         * Benutzer Klasse (nicht realisiert)
         *
         * @var User
         * @access private
         */
        var $User = null;

        /**
         * Root Verzeichnis der Hauptbibliothek
         *
         * @var string
         * @access private
         */
        var $PathBaselib = '';

        /**
         * Relativer Pfad zur Hauptbibliothek
         *
         * @var string
         * @access private
         */
        var $RelativePathBaselib = '';

        /**
         * Skin / Theme (Designvorlage bzw. Bilderordner)
         *
         * @var string
         */
        private $skin = 'default';

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
         * @var Translator
         */
        private Translator $Translator;

        /**
         * @var string
         */
        private string $subdirTranslated = '';

        /**
         * Der Konstruktor erwartet den Projektnamen, den absoluten und relativen Pfad zur Baselib.
         *
         * @access public
         * @param string $Project Name des Projekts.
         * @param string $PathBaselib absoluter Pfad zur Baselib
         * @param string $RelativePathBaselib relativer Pfad zur Baselib
         * @throws Exception
         **/
        function __construct($Project = '', $PathBaselib = '', $RelativePathBaselib = '')
        {
            if ($Project != '') {
                $this->Project = $Project;
            }
            $this->Settings = new Input();
            $this->PathBaselib = $PathBaselib;
            $this->RelativePathBaselib = $RelativePathBaselib;
            $this->Translator = new Translator();

            $Nil = new Nil();
            parent::__construct($Nil);
        }

        /**
         * Aendert den Ordner fuer die Designvorlagen (Html Templates) und Bilder.
         *
         * @access public
         * @param string $skin Ordner fuer die Designvorlagen (Html Templates) und Bilder. (Standardwert: default)
         **/
        function setSkin($skin = 'default')
        {
            $this->skin = $skin;
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
         * @throws Exception
         */
        function setLanguage(string $lang = 'de', string $resourceDir = '', string $subdirTranslated = '')
        {
            $this->language = $lang;

            if ($resourceDir) {
                $this->Translator->setResourceDir($resourceDir)->setDefaultLanguage($lang);
            }

            $this->subdirTranslated = $subdirTranslated;
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
            $Translator = $this->Translator;
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
         */
        function setCharset($charset)
        {
            header('content-type: text/html; charset=' . $charset);
            $this->charset = strtoupper($charset);
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
         */
        public function setDefaultSchema($default = 'index')
        {
            $this->schema = trim($default);
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
        function findTemplate($filename, $classfolder = '', $baselib = false)
        {
            $classfolder = strtolower($classfolder); // 29.05.2007, Workaround for PHP 5
            // PHP > 5.x/7 liefert mit get_class() pl�tzlich richtig geschriebene Klassennamen in Gro�buchstaben

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
            $folder_guis = PWD_TILL_GUIS . '/' . $classfolder . '/';
            $gui_directories = array(
                $folder_guis
            );

            # Ordner Commons guis
            if (defined('DIR_COMMON_ROOT')) {
                $gui_directories[] = addEndingSlash(DIR_COMMON_ROOT) . $folder_guis;
            }

            foreach ($gui_directories as $folder_guis) {
                $folder_skins = $folder_guis . $skin;
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
                    if (file_exists($folder_guis . $filename)) {
                        return $folder_guis . $filename;
                    }
                }
            }


            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this->PathBaselib) . addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
                if (is_dir($folder)) {
                    if (file_exists($folder . $filename)) {
                        return $folder . $filename;
                    }
                }
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
        function findStyleSheet($filename, $classfolder = '', $baselib = false)
        {
            $skin = addEndingSlash($this->skin);
            $language = addEndingSlash($this->language);
            $stylesheets = $this->cssFolder . '/';

            # folder: skins
            $folder_skins = addEndingSlash(PWD_TILL_SKINS);
            $folder_default = $folder_skins . $this->commonSkinFolder . '/'; // TODO AM, making it configurable
            $folder_skins_stylesheets = $folder_default . $stylesheets;

            if (is_dir($folder_skins_stylesheets)) { // skins - skinname - stylesheet folder
                if (file_exists($folder_skins_stylesheets . $filename)) {
                    return $folder_skins_stylesheets . $filename;
                }
            }

            $folder_skin = $folder_skins . $skin;
            $folder_skin_stylesheets = $folder_skin . $stylesheets;
            $folder_language = $folder_skin . $language . $stylesheets;

            if (is_dir($folder_language)) { // skins - skinname - language - stylesheet folder
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }

            if (is_dir($folder_skin_stylesheets)) { // skins - skinname - stylesheet folder
                if (file_exists($folder_skin_stylesheets . $filename)) {
                    return $folder_skin_stylesheets . $filename;
                }
            }

            # Projekt folder: guis
            $folder_guis = addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
            $folder_skin = $folder_guis . $skin;
            $folder_language = $folder_skin . $language;
            if (is_dir($folder_language)) { // guis - classname - skin - language folder
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }
            if (is_dir($folder_skin)) { // guis - classname - skin folder
                if (file_exists($folder_skin . $filename)) {
                    return $folder_skin . $filename;
                }
            }
            if (is_dir($folder_guis)) { // guis - classname folder
                if (file_exists($folder_guis . $filename)) {
                    return $folder_guis . $filename;
                }
            }

            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this->RelativePathBaselib) . addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
                if (is_dir($folder)) {
                    if (file_exists($folder . $filename)) {
                        return $folder . $filename;
                    }
                }
            }

            // PHP 4 Workaround:
            if (preg_match('/[A-Z]/', $filename . $classfolder)) {
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
        function findJavaScript($filename, $classfolder = '', $baselib = false)
        {
            $javascripts = addEndingSlash(PWD_TILL_JAVASCRIPTS);

            # Ordner skins
            $folder_javascripts = $javascripts;

            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this->RelativePathBaselib) . $javascripts;
                if (file_exists($folder . $filename)) {
                    return $folder . $filename;
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

            $this->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s\' not found (@findJavaScript)!', $filename));
            return '';
        }

        /**
         * Setzte Pfad zum POOL
         *
         * @param $path
         */
        function setPathBaselib($path)
        {
            $this->PathBaselib = $path;
        }

        /**
         * Pfad zum Rootverzeichnis der Baselib
         *
         * @access public
         * @return string Pfad zur Base-Lib
         **/
        function getPathBaselib($path = '')
        {
            return $this->PathBaselib . $path;
        }

        /**
         * @param $path
         */
        function setRelativePathBaselib($path)
        {
            $this->RelativePathBaselib = $path;
        }

        /**
         * Relativer Pfad zum Rootverzeichnis der Baselib
         *
         * @access public
         * @return string Relativer Pfad zur Baselib
         **/
        function getRelativePathBaselib($path = '')
        {
            return $this->RelativePathBaselib . $path;
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
        public function &getInterfaces()
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
        public function &setup(array $settings)
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
        public function &startPHPSession($session_name = 'PHPSESSID', $use_trans_sid = 0, $use_cookies = 1, $use_only_cookies = 0, $autoClose = true)
        {
            $sessionConfig = array(
                'name' => $session_name,
                'use_trans_sid' => $use_trans_sid,
                'use_cookies' => $use_cookies,
                'use_only_cookies' => $use_only_cookies
            );
            foreach ($sessionConfig as $param => $value) {
                ini_set('session.' . $param, $value);
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
         * Projektname auslesen
         *
         * @return string
         */
        function getProject()
        {
            return $this->Project;
        }

        /**
         * Definiert einen Projektnamen / App-Name
         *
         * @param string $project
         * @return boolean
         */
        public function setProject($project)
        {
            if ($this->Project == 'unknown') {
                $this->Project = $project;
                return true;
            }
            return false;
        }

        /**
         * Seitentitel setzen
         *
         * @param string $title
         */
        public function setTitle($title)
        {
            $this->Title = $title;
        }

        /**
         * Seitentitel auslesen
         *
         * @return string
         */
        public function getTitle()
        {
            return $this->Title;
        }

        /**
         * Erzeugt das erste GUI_Module in der Kette (Momentan wird hier der Seitentitel mit dem Projektnamen gefuellt).
         *
         * @access public
         * @param string $className GUI_Module (Standard-Wert: GUI_CustomFrame)
         * @return boolean Erfolgsstatus
         **/
        public function run($className = 'GUI_CustomFrame')
        {
            // TODO Get Parameter frame
            // TODO Subcode :: createSubCode()
            $params = $_REQUEST['params'] ?? '';
            if ($params != '' and isAjax()) {
                $params = base64url_decode($params);
            }

            $Nil = new Nil();
            $GUI = &GUI_Module::createGUIModule($className, $this, $Nil, $params);
            if (isNil($GUI)) {
                $this->raiseError(__FILE__, __LINE__, 'Klasse ' . $className .
                    ' wurde nicht gefunden oder existiert nicht. (@Weblication -> run).');
                // TODO Page Error
                return false;
            }
            else {
                /** Hinweis: erstes GUI registriert sich selbst �ber setMain als
                 * Haupt-GUI im GUI_Module Konstruktor **/

                if ($this->Main instanceof GUI_CustomFrame) {
                    # Seitentitel (= Project)
                    $Header = &$this->Main->getHeaderdata();
                    if ($Header) {
                        $title = $this->Title;
                        $Header->setTitle($title);
                        $Header->setLanguage($this->language);
                        if ($this->charset) $Header->setCharset($this->charset);
                    }
                }
                return true;
            }
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
}