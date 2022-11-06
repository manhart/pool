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
     * @var string $FileIdent
     * @access private
     */
    var $FileIdent;

    /**
     * Kompletter Inhalt (geparster Content)
     *
     * @var string $FinalContent
     * @access private
     */
    var $FinalContent = '';

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
     *
     * @param boolean $search True sucht nach weiteren GUIs
     **/
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
     * @return string
     */
    function getTemplatePath($lookInside = false, $without_frame = true)
    {
        $Parent = $this->Parent;
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
            // AM, 15.07.2009
            // eval was slower: eval ("\$GUI = & new $GUIClassName(\$Owner);");
            // AM, 22.07.2020
            $Params = new Input(I_EMPTY);
            $Params->setParams($params);
            $GUI = new $GUIClassName($Owner, $autoLoadFiles, $Params->getData());
            /* @var $GUI GUI_Module */
            if ($ParentGUI instanceof Module) {
                $GUI->setParent($ParentGUI);
            }
            //            if(!$GUI->importParamsDone) $GUI->importParams($params); // Downward compatibility with older GUIs
            $GUI->autoLoadFiles(true);
            return $GUI;
        }
        else {
            return null;
        }
    }

    /**
     * Sucht in allen vorgeladenen Html Templates nach fest eingetragenen GUIs.
     * Ruft die Funktion GUI_Module::searchGUIs() auf.
     **/
    protected function searchGUIsInPreloadedContent()
    {
        $TemplateFiles = $this->Template->getFiles();
        for ($f = 0, $sizeOfTemplateFiles = sizeof($TemplateFiles); $f < $sizeOfTemplateFiles; $f++) {
            $TemplateFiles[$f]->setContent($this->searchGUIs($TemplateFiles[$f]->getContent()), false);
        }
    }

    /**
     * Durchsucht den Inhalt nach GUIs.
     *
     * @param string $content Zu durchsuchender Inhalt
     * @return string Neuer Inhalt (gefundene GUIs wurden im Html Code ersetzt)
     *
     * @throws ReflectionException
     */
    public function searchGUIs(string $content): string
    {
        $reg = '/\[(GUI_.*)(\((.*)\)|)\]/mU';
        $bResult = preg_match_all($reg, $content, $matches, PREG_SET_ORDER);
        if ($bResult) {
            for ($i = 0, $numMatches = count($matches); $i < $numMatches; $i++) {
                $pattern = $matches[$i][0];
                $guiname = $matches[$i][1];
                $params = isset($matches[$i][3]) ? $matches[$i][3] : '';
                $new_GUI = $this->createGUIModule($guiname, $this->getOwner(), $this, $params);

                if (is_null($new_GUI)) {
                    $message = 'Fehler beim Erzeugen der Klasse "{guiname}"';
                    $E = new Xception($message, 0, magicInfo(__FILE__, __LINE__, __FUNCTION__, __CLASS__,
                        compact('guiname')), null);
                    $this->throwException($E);
                }
                else {
                    $replacement = '{' . strtoupper($new_GUI->getName()) . '}';
                    $new_GUI->setMarker($replacement);
                    $content = preg_replace('/' . preg_quote($pattern, '/') . '/mU', $replacement, $content, 1);
                    $this->insertModule($new_GUI);

                }
                unset($new_GUI);
            }
        }
        return $content;
    }

    /**
     * Wiederbeleben der Child GUIs (meist, falls Autoload auf false gesetzt wurde).
     * Die Funktion muss verwendet werden, wenn zur Laufzeit neue GUI Module gesetzt werden!
     *
     * @access public
     * @param string $content Content / Inhalt
     * @return string Content / Inhalt aller Childs
     *
     * @throws ReflectionException
     */
    public function reviveChildGUIs(string $content): string
    {
        //$content = $this -> FindGUIsByContent($content);
        $content = $this->searchGUIs($content);
        $this->prepareChilds();
        $this->finalizeChilds();
        return $this->pasteChilds($content);
    }

    /**
     * Setzt sich Merker, auf welchem FileHandle sitze ich. Welches Muster (Ident) habe ich innerhalb des Templates.
     *
     * @param string $ident Identifikation innerhalb des Templates
     */
    private function setMarker($ident)
    {
        $this->FileIdent = $ident;
    }

    /**
     * Gibt einen Merker (Ident) zurueck
     *
     * @return string Ident/Pattern/Muster
     **/
    private function getMarkerIdent()
    {
        return $this->FileIdent;
    }

    /**
     * Aktiviert eine Box. Erwartet als Parameter eine HTML Vorlage mit der Box.
     * In der Vorlage muss der Platzhalter {CONTENT} stehen. Bei Bedarf kann noch {TITLE} gesetzt werden.
     *
     * @param string $title Titel
     * @param string $template HTML Vorlage (nur Dateiname ohne Pfad; Standard "tpl_box.html"); sucht immer im Projektverzeichnis nach der Vorlage.
     * @access public
     **/
    function enableBox($title = '', $template = 'tpl_box.html')
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
     *
     * @access public
     **/
    function disableBox()
    {
        $this->enabledBox = false;
    }


    /*
    * Laedt Templates (virtuelle Methode sollte ueberschrieben werden, falls im Konstruktor AutoLoad auf true gesetzt wird).
    *
    * @access protected
    */
    public function loadFiles()
    {
        if(!$this->getWeblication()) return $this;

        foreach($this->templates as $handle => $file) {
            $template = $this->Weblication->findTemplate($file, $this->getClassName());
            $this->Template->setFilePath($handle, $template);
        }

        if(!$this->getWeblication()->hasFrame()) return $this;
        $Frame = $this->getWeblication()->getFrame();

        foreach($this->cssFiles as $cssFile) {
            $cssFile = $this->getWeblication()->findStyleSheet($cssFile, $this->getClassName());
            $Frame->getHeaderdata()->addStyleSheet($cssFile);
        }

        foreach($this->jsFiles as $jsFile) {
            $jsFile = $this->getWeblication()->findJavaScript($jsFile, $this->getClassName());
            $Frame->getHeaderdata()->addJavaScript($jsFile);
        }
    }

    /**
     * load, create and register JavaScript GUI
     *
     * @param bool $global makes Module global in window scope
     */
    public function loadJavaScriptFiles(bool $global = true): bool
    {
        if(!$this->Weblication->hasFrame()) {
            return false;
        }

        $className = '';
        $myself_already_loaded = false;
        $something_loaded = false;
        $Header = $this->Weblication->getFrame()->getHeaderdata();
        foreach($this->getJavaScriptFiles() as $jsFile) {
            $jsFile = $this->Weblication->findJavaScript($jsFile[0], $className = $jsFile[1], $jsFile[2] ?? false, false);
            $myself_already_loaded = ($className == $this->getClassName());
            if($jsFile) {
                $Header->addJavaScript($jsFile);
                $something_loaded = true;
            }
        }

        if(!$myself_already_loaded) { // look for js file
            $jsFile = $this->Weblication->findJavaScript($this->getClassName().'.js', $this->getClassName(), $this->isBasicLibrary(), false);
            if(!$jsFile) {
                return false;
            }
            $className = $this->getClassName();
            $Header->addJavaScript($jsFile);
            $something_loaded = true;
        }

        if($something_loaded) {
            $this->js_createGUIModule($className, $global);
        }
        return true;
    }

    /**
     * @param string $className
     * @param bool $global
     * @return void
     */
    protected function js_createGUIModule(string $className = '', bool $global = true): void
    {
        if(!$this->js_createGUIModule) {
            return;
        }
        if(!$this->Weblication->hasFrame()) {
            return;
        }
        $className = $className ?: $this->getClassName();
        $windowCode = '';
        if($global) {
            $windowCode = 'window[\'$'.$this->getName().'\'] = ';
        }
        $this->Weblication->getFrame()->getHeaderdata()->addScriptCode($this->getName(),
            $windowCode.'GUI_Module.createGUIModule('.$className.', \''.$this->getName().'\');');

    }

    protected function getJavaScriptFiles(): array
    {
        return [];
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
        // todo before_provision or before_parents, after_provision or after_parents ?
//        $this->provision(); // 23.12.21, AM, moved to Weblication::prepareContent!!

//        echo 'prepare: '.$this->getName().'<br>';
        if ($this->isMyXMLHttpRequest and $this->XMLHttpRequestMethod) {
            return true;
        }

        $this->prepare();
        $this->prepareChilds();
    }

    /**
     * provision something
     */
    public function provision()
    {
        // echo $this->getName().'<br>';
        $max = count($this->Modules);
        for ($m = 0; $m < $max; $m++) {
            $this->Modules[$m]->provision();
        }
    }

    /**
     * frontend control: run/execute the main logic and fill templates.
     */
    protected function prepare() {}

    /**
     * Bereitet alle Html Templates aller Childs auf.
     **/
    private function prepareChilds()
    {
        $max = count($this->Modules);
        for ($m = 0; $m < $max; $m++) {
            $gui = $this->Modules[$m];
            $gui->importHandoff($this->Handoff);
            $gui->prepareContent();
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
        $this->finalizeChilds();
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
                $content = $this->FinalContent;
            }


            # render Box
            if ($this->enabledBox == true) {
                $this->TemplateBox->setVar('CONTENT', $content);
                $this->TemplateBox->parse('stdout');
                $content = $this->TemplateBox->getContent('stdout');
                $this->TemplateBox->clear();
            }

            if (!$this->takeMeAlone) {
                $content = $this->pasteChilds($content);
            }
        }
        return $content;
    }

    /**
     * Fertigt alle Html Templates der Childs an.
     **/
    private function finalizeChilds()
    {
        $count = count($this->Modules);
        for ($m = 0; $m < $count; $m++) {
            $gui = $this->Modules[$m];
            /*echo $gui->getClassName().' '.bool2string($gui->enabled).'<br>';*/
            if ($gui->enabled) {
                $gui->FinalContent = $gui->finalizeContent();
                $this->takeMeAlone = $gui->takeMeAlone;
                if ($this->takeMeAlone) {
                    $this->FinalContent = $gui->FinalContent;
                    break; // nachfolgende Module wuerden Fehler verursachen, da takeMeAlone reseted w�rde
                }
            }
        }
    }

    /**
     * adopts the content of the children
     *
     * @param string $content Eigener Content
     * @return string Eigener Content samt dem Content der Child GUIs
     **/
    private function pasteChilds(string $content): string
    {
        $count = count($this->Modules);
        for ($m = 0; $m < $count; $m++) {
            $gui = $this->Modules[$m];
            $content = str_replace($gui->getMarkerIdent(), $gui->FinalContent, $content);
        }
        return $content;
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