<?php declare(strict_types=1);
/**
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * Das GUI_Module ist die Basisklasse fuer alle graphischen Steuerelemente.
 * Sie generieren standardmaessig ein Template.
 * Fest definierte GUIs in Html Templates werden automatisch eingelesen, instanziert und mit Parametern gefuellt.
 * Werden GUIs erst zur Laufzeit des Programms beigemischt, schaltet man den Automatismus ab (im Konstruktor) und
 * verwendet am Ende (meist finalize) die Funktion GUI_Module::reviveChildGUIs().
 *
 * @version $Id: GUI_Module.class.php,v 1.7 2006/11/02 12:04:54 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\ModulNotFoundExeption;

const REQUEST_PARAM_MODULENAME = 'requestModule';
const REQUEST_PARAM_MODULE = 'module';
const REQUEST_PARAM_METHOD = 'method';
// const FIXED_PARAM_CONFIG = 'config';

class GUI_Module extends Module
{
    /**
     * Rapid Template Engine
     *
     * @var Template $Template
     * @see Template
     */
    public Template $Template;

    /**
     * Merkt sich mit dieser Variable das eigene Muster im Template (dient der Identifikation)
     *
     * @var string $marker
     */
    private string $marker;

    /**
     * Kompletter Inhalt (geparster Content)
     *
     * @var string $finalContent
     */
    private string $finalContent = '';

    /**
     * HTML-Vorlage automatisch vorladen
     *
     * @var bool $AutoLoadFiles
     * @access private
     */
    var $AutoLoadFiles;

    /**
     * @var Template $TemplateBox Rapid Template Engine rendert eine Box (nur wenn diese ueber enableBox aktiviert wird)
     * @access public
     */
    var $TemplateBox = null;

    /**
     * Status der Rahmenbox (um das GUI): TRUE Box ist eingeschaltet und FALSE Box ist deaktiviert
     *
     * @var bool $enabledBox
     * @access private
     */
    var $enabledBox = false;

    /**
     * Ajax Request / XMLHttpRequest
     *
     * @var boolean
     */
    var $isMyXMLHttpRequest = false;

    /**
     * Ajax Request auf eine bestimmte Methode in einem GUI; f�hrt kein prepare aus
     *
     * @var string
     */
    var $XMLHttpRequestMethod = '';

    /**
     * Nimmt nur den Content eines Moduls u. vernachl�ssigt alle anderen GUI's in der Hierarchie
     *
     * @var boolean
     */
    private bool $takeMeAlone = false;

    /**
     * Pass result unchanged as JSON string through
     */
    protected bool $plainJSON = false;

    /**
     * @var bool automatically create GUI_Module in JS
     */
    protected bool $js_createGUIModule = true;

    /**
     * Options for the module-inspector
     *
     * @var array|array[]
     */
//    private array $inspectorProperties = [
//        'moduleName' => [ // pool
//            'pool' => true,
//            'caption' => 'ModuleName',
//            'type' => 'string',
//            'value' => '',
//            'element' => 'input',
//            'inputType' => 'text'
//        ]
//    ];
//
//    protected array $configuration = [];

    /**
     * @var array<string, string> $templates files (templates) to be loaded, usually used with $this->Template->setVar(...) in the prepare function. Defined as an associated array [handle => tplFile].
     */
    protected array $templates = [];

    /**
     * @var array<int, string> $jsFiles javascript files to be loaded, defined as indexed array
     */
    protected array $jsFiles = [];

    /**
     * @var array|string[] $cssFiles css files to be loaded, defined as indexed array
     */
    protected array $cssFiles = [];

    /**
     * @var array<string, string> $ajaxMethods
     */
    protected array $ajaxMethods = [];

    /**
     * Konstruktor
     *
     * @param Component|null $Owner Besitzer vom Typ Component
     * @param boolean $autoLoadFiles Laedt automatisch Templates und sucht darin GUIs
     * @param array $params additional parameters
     * @throws ReflectionException
     */
    public function __construct(?Component $Owner, bool $autoLoadFiles = true, array $params = [])
    {
        parent::__construct($Owner, $params);

        if (isAjax()) {
            if (isset($_REQUEST[REQUEST_PARAM_MODULE]) and $this->getClassName() == $_REQUEST[REQUEST_PARAM_MODULE]) {
                $this->isMyXMLHttpRequest = true;

                // eventl. genauer definiert, welches Modul, falls es mehrere des gleichen Typs/Klasse gibt
                if (isset($_REQUEST[REQUEST_PARAM_MODULENAME])) {
                    if ($this->Name == $_REQUEST[REQUEST_PARAM_MODULENAME]) {
                        $this->isMyXMLHttpRequest = true;
                    }
                    else {
                        $this->isMyXMLHttpRequest = false;
                    }
                }
            }
            elseif (isset($_REQUEST[REQUEST_PARAM_MODULENAME])) {
                if ($this->Name == $_REQUEST[REQUEST_PARAM_MODULENAME]) {
                    $this->isMyXMLHttpRequest = true;
                }
            }

            if ($this->isMyXMLHttpRequest and isset($_REQUEST[REQUEST_PARAM_METHOD])) {
                $this->XMLHttpRequestMethod = $_REQUEST[REQUEST_PARAM_METHOD];
            }
        }

        if ($Owner instanceof Weblication) {
            if (is_null($Owner->getMain())) {
                $Owner->setMain($this);
            }
        }

        $this->Template = new Template();
        $this->AutoLoadFiles = $autoLoadFiles;
    }

    /**
     * Das Template Objekt laedt HTML Vorlagen.
     * @param boolean $search True sucht nach weiteren GUIs
     *
     * @throws ModulNotFoundExeption
     */
    public function autoLoadFiles(bool $search = true)
    {
        if ($this->AutoLoadFiles) {
            // Lade Templates
            $this->loadFiles();
            if ($search) {
                // Suche nach weiteren Modulen (in den Html Templates)
                $this->searchGUIsInPreloadedContent();
            }
        }
    }

    /**
     * Liefert den Pfad des GUI's fuer die Template Engine
     *
     * @param bool $lookInside Wenn es sich um ein verschachteltes GUI handelt, dann sollte dies auf true stehen
     * @param bool $without_frame
     * @return string
     */
    function getTemplatePath(bool $lookInside = false, bool $without_frame = true): string
    {
        $Parent = $this->getParent();
        $parent_directory = '';
        if ($lookInside and $Parent != null) {
            do {
                if ($Parent instanceof GUI_Schema) {
                    $Parent = $Parent->getParent();
                    continue;
                }
                if ($without_frame and $Parent instanceof GUI_CustomFrame) {
                    $Parent = $Parent->getParent();
                    continue;
                }
                $parent_directory = $Parent->getClassName() . '/' . $parent_directory;
                $Parent = $Parent->getParent();
            } while ($Parent != null);
        }
        return $parent_directory . $this->getClassName();
    }

    /**
     * Autoloader for GUIModules
     *
     * @param string $GUIClassName
     * @param Module|null $ParentGUI
     * @return bool
     */
    public static function autoloadGUIModule(string $GUIClassName, ?Module $ParentGUI = null): bool
    {
        $GUIRootDirs = array(
            getcwd()
        );
        if (defined('DIR_POOL_ROOT')) {
            $GUIRootDirs[] = DIR_POOL_ROOT;
        }
        if (defined('DIR_COMMON_ROOT')) {
            $GUIRootDirs[] = DIR_COMMON_ROOT;
        }

        // try to load class
        foreach ($GUIRootDirs as $GUIRootDir) {
            $GUIRootDir = addEndingSlash($GUIRootDir) . addEndingSlash(PWD_TILL_GUIS);

            $filename = $GUIRootDir . strtolower($GUIClassName . '/' . $GUIClassName) . PoolObject::CLASS_EXTENSION;
            if (file_exists($filename)) {
                require_once $filename;
                return true;
            }

            $filename = $GUIRootDir . $GUIClassName . '/' . $GUIClassName . PoolObject::CLASS_EXTENSION;
            if (file_exists($filename)) {
                require_once $filename;
                return true;
            }

            if ($ParentGUI instanceof Module) {
                // verschachtelte GUI's
                $parent_directory = '';
                $parent_directory_without_frame = '';
                do {
                    if ($ParentGUI instanceof GUI_Schema) { // GUI_Schema ist nicht schachtelbar
                        $ParentGUI = $ParentGUI->getParent();
                        continue;
                    }
                    if (!$ParentGUI instanceof GUI_CustomFrame) {
                        $parent_directory_without_frame = $ParentGUI->getClassName() . '/' . $parent_directory_without_frame;
                    }
                    $parent_directory = $ParentGUI->getClassName() . '/' . $parent_directory;
                    $ParentGUI = $ParentGUI->getParent();
                } while ($ParentGUI != null);

                $filename = $GUIRootDir . $parent_directory . strtolower($GUIClassName . '/' . $GUIClassName) . PoolObject::CLASS_EXTENSION;
                if (file_exists($filename)) {
                    require_once $filename;
                    return true;
                }

                $filename = $GUIRootDir . strtolower($parent_directory_without_frame . $GUIClassName . '/' . $GUIClassName) . PoolObject::CLASS_EXTENSION;
                if (file_exists($filename)) {
                    require_once $filename;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Erzeugt ein neues GUI Modul anhand des Klassennamens.
     * Faustregel fuer Owner: als Owner sollte die Klasse Weblication uebergeben werden
     * (damit ein Zugriff auf alle Unterobjekte gewaehrleistet werden kann).
     *
     * @param string $GUIClassName Name der GUI Klasse
     * @param Component|null $Owner Besitzer dieses Objekts
     * @param Module|null $ParentGUI parent module
     * @param string $params Parameter in der Form key1=value1&key2=value2=&
     * @return GUI_Module|null Neues GUI_Module
     * @throws ModulNotFoundExeption
     */
    public static function createGUIModule(string $GUIClassName, ?Component $Owner, ?Module $ParentGUI, string $params = '',
                                           $autoLoadFiles = true): ?GUI_Module
    {
        $class_exists = class_exists($GUIClassName, false);

        if (!$class_exists) {
            GUI_Module::autoloadGUIModule($GUIClassName, $ParentGUI);

            // retest
            $class_exists = class_exists($GUIClassName, false);
        }

        if ($class_exists) {
            $Params = new Input(I_EMPTY);
            $Params->setParams($params);
            //TODO check authorisation
            $GUI = new $GUIClassName($Owner, $autoLoadFiles, $Params->getData());
            /* @var $GUI GUI_Module */
            if ($ParentGUI instanceof Module) {
                $GUI->setParent($ParentGUI);
            }
            //            if(!$GUI->importParamsDone) $GUI->importParams($params); // Downward compatibility with older GUIs
            $GUI->autoLoadFiles(true);
            return $GUI;
        }
        else {//Class not found
            throw new ModulNotFoundExeption("Fehler beim Erzeugen der Klasse '$GUIClassName'");
        }
    }

    /**
     * Sucht in allen vorgeladenen Html Templates nach fest eingetragenen GUIs.
     * Ruft die Funktion GUI_Module::searchGUIs() auf.
     * @throws ModulNotFoundExeption
     */
    protected function searchGUIsInPreloadedContent()
    {
        $TemplateFiles = $this->Template->getFiles();
        foreach($TemplateFiles as $TemplateFile) {
            $TemplateFile->setContent($this->searchGUIs($TemplateFile->getContent()), false);
        }
    }

    /**
     * Durchsucht den Inhalt nach GUIs.
     *
     * @param string $content Zu durchsuchender Inhalt
     * @return string Neuer Inhalt (gefundene GUIs wurden im Html Code ersetzt)
     * @throws ModulNotFoundExeption
     */
    public function searchGUIs(string $content): string
    {
        $reg = '/\[(GUI_.*)(\((.*)\)|)\]/mU';
        $bResult = preg_match_all($reg, $content, $matches, PREG_SET_ORDER);

        if(!$bResult) return $content;

        //GUIs found
        $newContent = [];
        $caret = 0;
        foreach ($matches as $match) {
            $pattern = $match[0];
            $patternLength = strlen($pattern);
            $guiName = $match[1];
            $params = $match[3] ?? '';
            //try building the GUI found
            $new_GUI = $this->createGUIModule($guiName, $this->getOwner(), $this, $params);

            $guiIdentifier = "[{$new_GUI->getName()}]";
            //store reference for later insertion in pasteChildren()
            $new_GUI->setMarker($guiIdentifier);
            //add GUI to child-list
            $this->insertModule($new_GUI);
            //find the beginning of this Match
            $beginningOfMatch = strpos($content, $pattern, $caret);
            //save content between Matches
            $newContent[] = substr($content, $caret, $beginningOfMatch - $caret);
            //insert identifier
            $newContent[] = $guiIdentifier;
            //move caret to end of this match
            $caret = $beginningOfMatch + $patternLength;
            unset($new_GUI);
        }//end foreach
        //add remainder
        $newContent[] = substr($content, $caret);
        //replace content
        return implode($newContent);
    }

    /**
     * Wiederbeleben der Child GUIs (meist, falls Autoload auf false gesetzt wurde).
     * Die Funktion muss verwendet werden, wenn zur Laufzeit neue GUI Module gesetzt werden!
     *
     * @access public
     * @param string $content Content / Inhalt
     * @return string Content / Inhalt aller Childs
     *
     * @throws ModulNotFoundExeption
     */
    public function reviveChildGUIs(string $content): string
    {
        //$content = $this -> FindGUIsByContent($content);
        $content = $this->searchGUIs($content);
        $this->prepareChildren();
        $this->finalizeChildren();
        return $this->pasteChildren($content);
    }

    /**
     * Setzt sich Merker, auf welchem FileHandle sitze ich. Welches Muster (Ident) habe ich innerhalb des Templates.
     *
     * @param string $marker Identifikation innerhalb des Templates
     */
    private function setMarker(string $marker)
    {
        $this->marker = $marker;
    }

    /**
     * Gibt einen Merker (Ident) zurueck
     *
     * @return string Ident/Pattern/Muster
     **/
    private function getMarker(): string
    {
        return $this->marker;
    }

    /**
     * Aktiviert eine Box. Erwartet als Parameter eine HTML Vorlage mit der Box.
     * In der Vorlage muss der Platzhalter {CONTENT} stehen. Bei Bedarf kann noch {TITLE} gesetzt werden.
     *
     * @param string $title Titel
     * @param string $template HTML Vorlage (nur Dateiname ohne Pfad; Standard "tpl_box.html"); sucht immer im Projektverzeichnis nach der Vorlage.
     * @access public
     **/
    function enableBox(string $title = '', $template = 'tpl_box.html')
    {
        $file = $this->Weblication->findTemplate($template, $this->getClassName(), false);
        if ($file) {
            $this->TemplateBox = new Template();
            $this->TemplateBox->setFilePath('stdout', $file);
            $this->TemplateBox->setVar('TITLE', $title);
            $this->enabledBox = true;
        }
        else {
            $this->enabledBox = false;
        }
    }

    /**
     * Deaktiviert die Box.
     */
    public function disableBox()
    {
        $this->enabledBox = false;
    }

    /*
     * Laedt Templates (virtuelle Methode sollte ueberschrieben werden, falls im Konstruktor AutoLoad auf true gesetzt wird).
     *
     * @return GUI_Module
     */
    public function loadFiles()
    {
        if(!$this->getWeblication()) return $this;

        $className = $this->getClassName();

        foreach($this->templates as $handle => $file) {
            $template = $this->Weblication->findTemplate($file, $className);
            $this->Template->setFilePath($handle, $template);
        }

        if(!$this->getWeblication()->hasFrame()) return $this;
        $Frame = $this->getWeblication()->getFrame();

        foreach($this->cssFiles as $cssFile) {
            $cssFile = $this->getWeblication()->findStyleSheet($cssFile, $className);
            $Frame->getHeaderdata()->addStyleSheet($cssFile);
        }

        foreach($this->jsFiles as $jsFile) {
            $jsFile = $this->getWeblication()->findJavaScript($jsFile, $className);
            $Frame->getHeaderdata()->addJavaScript($jsFile);
        }

        // automatically includes the appropriate JavaScript class, instantiates it, and adds it to JS Weblication (if enabled).
        $this->js_createGUIModule($this->getClassName());

        return $this;
    }

    /**
     * Automatically includes the appropriate JavaScript class, instantiates it, and adds it to JS Weblication.
     *
     * @param string $className
     * @param bool $includeJS
     * @param bool $global
     * @return void
     */
    protected function js_createGUIModule(string $className = '', bool $includeJS = true, bool $global = true): void
    {
        if(!$this->js_createGUIModule) {
            return;
        }
        if(!$this->Weblication->hasFrame()) {
            return;
        }

        $className = $className ?: $this->getClassName();

        if($includeJS) {
            $js = $this->Weblication->findJavaScript($className . '.js', $className, false, false);
            if(!$js) {
                return;
            }

            $this->Weblication->getFrame()->getHeaderdata()->addJavaScript($js);
        }

        $windowCode = '';
        if($global) {
            $windowCode = 'window[\'$' . $this->getName() . '\'] = ';
        }
        $this->Weblication->getFrame()->getHeaderdata()->addScriptCode($this->getName(),
            $windowCode . 'GUI_Module.createGUIModule(' . $className . ', \'' . $this->getName() . '\');');
    }

    /**
     * Adds a closed method (Closure) as an Ajax call. Only Ajax methods are callable by the client.
     *
     * @param string $alias name of the method
     * @param Closure $closure class for anonymous function
     * @return GUI_Module
     */
    protected function addAjaxMethod(string $alias, Closure $closure): self
    {
        $this->ajaxMethods[$alias] = $closure;
        return $this;
    }

    /**
     * Provisioning data before preparing module and there children.
     **/
    public function prepareContent()
    {
        if ($this->isMyXMLHttpRequest and $this->XMLHttpRequestMethod) {
            return;
        }

        $this->prepare();
        $this->prepareChildren();
    }

    /**
     * provision something
     */
    public function provision(): void
    {
        foreach($this->modules as $Module) {
            $Module->provision();
        }
    }

    /**
     * frontend control: run/execute the main logic and fill templates.
     */
    protected function prepare() {}

    /**
     * Bereitet alle Html Templates aller Childs auf.
     **/
    private function prepareChildren()
    {
        foreach($this->modules as $Module) {
            $Module->importHandoff($this->Handoff);
            $Module->prepareContent();
        }
    }

    /**
     * returns json encoded data of a method call of this object (intended use: ajax)
     *
     * @param string $method
     * @return string
     * @throws ReflectionException
     * @throws Exception
     */
    private function finalizeMethod(string $method): string
    {
        $result = '';

        $Closure = $this->ajaxMethods[$method] ?? null;

        // 03.11.2022 @todo remove is_callable and the ReflectionMethod that depends on it
        if(!($Closure || is_callable([$this, $method]))) {
            $Xception = new Xception('The method "' . $method . '" in the class ' . $this->getClassName().' is not callable', 0, array(),
                POOL_ERROR_DISPLAY);
            $Xception->raiseError();
            return '';
        }

        // @todo validate parameters?

        try {
            $ReflectionMethod = $Closure ? new ReflectionFunction($Closure) : new ReflectionMethod($this, $method);
            $numberOfParameters = $ReflectionMethod->getNumberOfParameters();
        }
        catch(\ReflectionException $e) {
            $Xception = new Xception('Error calling method '.$method.' on '.$this->getClassName(), 0, [], POOL_ERROR_DISPLAY);
            $Xception->raiseError();
            return '';
        }

        // collect every ajax calls that are not closures
        if(!$Closure) {
            Log::info('The method '.$this->getClassName().':'.$method.' is not used as Closure ', ['className' => $this->getClassName(),
                'method' => $method], 'ajaxCallLog');
        }

        error_clear_last();

        ob_start();

        $callingClassName = $this->getClassName();

        $args = [];
        if($numberOfParameters) {
            $parameters = $ReflectionMethod->getParameters();
            foreach($parameters as $Parameter) {
                $value = $this->Input->getVar($Parameter->getName(), ($Parameter->isOptional() ? $Parameter->getDefaultValue() : ''));
                if(is_string($value)) {
                    if($Parameter->getType()) {
                        switch($Parameter->getType()->getName()) {
                            case 'float':
                                $value = (float)$value;
                                break;

                            case 'int':
                                $value = (int)$value;
                                break;

                            case 'bool':
                                $value = string2bool($value);
                                break;
                        }
                    }
                    else {
                        if($value === 'true' or $value === 'false') {
                            $value = string2bool($value);
                        }
                    }
                }

                $args[] = $value;
            }
        }

        if($Closure) {
            //TODO check Authorisation

            // alternate: $result = $Closure->call($this, ...$args); // bind to another object possible
            $result = $Closure(...$args);
        }
        else {
            try {
                $result = $ReflectionMethod->invokeArgs($this, $args);
                $callingClassName = $ReflectionMethod->getDeclaringClass()->getName();
            }
            catch(\ReflectionException $e) {

                echo $e->getMessage();
            }
        }

        $undefinedContent = ob_get_contents();
        ob_end_clean();

        return $this->respondToAjaxCall($result, $undefinedContent, $callingClassName.':'.$method);
    }

    /**
     * responds to an Ajax call
     *
     * @param mixed $result
     * @param mixed $error
     * @param string $callingMethod optional; use __METHOD__
     * @return string
     * @throws Exception
     */
    protected function respondToAjaxCall(mixed $result, mixed $error, string $callingMethod = ''): string
    {
        header('Content-type: application/json');

        $clientData = [];

        if ($this->plainJSON) {
            $clientData = $result;

            // strange behavior with xdebug; xdebug overrides error_get_last
            $last_error = $this->Weblication->isXdebugEnabled() ? null : error_get_last();
            if($last_error != null) {
                if(IS_DEVELOP) { // only for developers, to have a notice
                    $message = $last_error['message'] . ' in '.$callingMethod.' in file ' . $last_error['file'] . ' on line ' . $last_error['line'];
                    throw new Exception($message, $last_error['type']);
                }
                // error_log($message);
                // syslog(LOG_WARNING, $message);

                error_clear_last();
            }
        }
        else {
            $clientData['Result'] = $result;
            $clientData['Error'] = $error;
        }

        $json = json_encode($clientData);

        $json_last_error = json_last_error();
        if($json_last_error == JSON_ERROR_NONE) {
            return $json;
        }

        if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
            $json_last_error_msg = json_last_error_msg();
        }
        else {
            $json_last_error_msg = 'Unknown Error';
            switch ($json_last_error) {
                case JSON_ERROR_DEPTH:
                    // $json_last_error_msg = 'Maximale Stacktiefe überschritten';
                    $json_last_error_msg = 'The maximum stack depth has been exceeded';
                    break;

                case JSON_ERROR_STATE_MISMATCH:
                    // $json_last_error_msg = 'Unterlauf oder Nichtübereinstimmung der Modi';
                    $json_last_error_msg = 'Invalid or malformed JSON';
                    break;

                case JSON_ERROR_CTRL_CHAR:
                    // $json_last_error_msg = 'Unerwartetes Steuerzeichen gefunden';
                    $json_last_error_msg = 'Control character error, possibly incorrectly encoded';
                    break;

                case JSON_ERROR_SYNTAX:
                    //                            $json_last_error_msg = 'Syntaxfehler, ungültiges JSON';
                    $json_last_error_msg = 'Syntax error';
                    break;

                case JSON_ERROR_UTF8:
                    //                            $json_last_error_msg = 'Missgestaltete UTF-8 Zeichen, möglicherweise fehlerhaft kodiert';
                    $json_last_error_msg = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;

                case JSON_ERROR_RECURSION:
                    $json_last_error_msg = 'One or more recursive references in the value to be encoded';
                    break;

                case JSON_ERROR_INF_OR_NAN:
                    $json_last_error_msg = 'One or more NAN or INF values in the value to be encoded';
                    break;

                case JSON_ERROR_UNSUPPORTED_TYPE:
                    $json_last_error_msg = 'A value of a type that cannot be encoded was given';
                    break;

                case JSON_ERROR_INVALID_PROPERTY_NAME:
                    $json_last_error_msg = 'A property name that cannot be encoded was given';
                    break;
            }
        }

        return $json_last_error_msg . ' in ' . $callingMethod . ': ' . print_r($clientData, true);
    }

    /**
     * enable plain JSON return (without change by the POOL)
     *
     * @param bool $activate
     * @return GUI_Module
     */
    public function respondAsPlainJSON(bool $activate = true): GUI_Module
    {
        $this->plainJSON = $activate;
        return $this;
    }

    /**
     * Stellt den Inhalt der Html Templates fertig und sorgt dafuer, dass auch alle Childs fertig gestellt werden.
     * Das ganze geht von Innen nach Aussen!!! (umgekehrt zu CreateGUI, Init, PrepareContent)
     *
     * @return string Content / Inhalt
     * @throws Exception
     */
    public function finalizeContent(): string
    {
        $content = '';
        $this->finalizeChildren();
        if ($this->enabled) {
            if ($this->isMyXMLHttpRequest && isset($_REQUEST[REQUEST_PARAM_METHOD])) {
                // dispatch Ajax Call only for ONE GUI -> returns JSON
                $content = $this->finalizeMethod($_REQUEST[REQUEST_PARAM_METHOD]);
                // hier wird abgebrochen, pool wurde bis zu dieser instanz durchlaufen
                if (isset($_REQUEST[REQUEST_PARAM_MODULENAME])) {
                    $this->takeMeAlone = true; // dieses GUI wirft ganz alleine den Inhalt von finalizeMethod zur�ck
                    /*						print $content;
                                        exit(0);*/
                }
            }
            elseif (!$this->takeMeAlone) {
                $content = $this->finalize();
            }
            else {
                $content = $this->finalContent;
            }


            # render Box
            if ($this->enabledBox) {
                $this->TemplateBox->setVar('CONTENT', $content);
                $this->TemplateBox->parse('stdout');
                $content = $this->TemplateBox->getContent('stdout');
                $this->TemplateBox->clear();
            }

            if (!$this->takeMeAlone) {
                $content = $this->pasteChildren($content);
            }
        }
        return $content;
    }

    /**
     * Fertigt alle Html Templates der Childs an.
     *
     * @throws Exception
     */
    private function finalizeChildren()
    {
        /** @var GUI_Module $GUI */
        foreach($this->modules as $GUI) {
            if(!$GUI->enabled) continue;
            $GUI->finalContent = $GUI->finalizeContent();
            $this->takeMeAlone = $GUI->takeMeAlone;
            if ($this->takeMeAlone) {
                $this->finalContent = $GUI->finalContent;
                break; // nachfolgende Module wuerden Fehler verursachen, da takeMeAlone reseted w�rde
            }
        }
    }

    /**
     * adopts the content of the children
     *
     * @param string $content Eigener Content
     * @return string Eigener Content samt dem Content der Child GUIs
     **/
    private function pasteChildren(string $content): string
    {
        $replace_pairs = [];
        foreach($this->modules as $GUI) {
            $replace_pairs[$GUI->getMarker()] = $GUI->finalContent;
        }
        return strtr($content, $replace_pairs);
    }

    /**
     * returns the contents of the module
     **/
    protected function finalize(): string
    {
        $content = '';
        foreach($this->templates as $handle => $tplFile) {
            $this->Template->parse($handle);
            $content .= $this->Template->getContent($handle);
        }
        return $content;
    }
}