<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * GUI_Module.class.php
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
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

const REQUEST_PARAM_MODULENAME = 'requestModule';
const REQUEST_PARAM_MODULE = 'module';
const REQUEST_PARAM_METHOD = 'method';
// const FIXED_PARAM_CONFIG = 'config';

/**
 * GUI_Module
 *
 * Basisklasse fuer alle graphischen Steuerelemente.
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: GUI_Module.class.php,v 1.7 2006/11/02 12:04:54 manhart Exp $
 **/
class GUI_Module extends Module
{
    /**
     * Rapid Template Engine
     *
     * @var Template $Template
     * @access public
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
    var $takeMeAlone = false;

    /**
     * Reiche Ergebnis als JSON durch
     */
    protected bool $plainJSON = false;

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
     * Konstruktor
     *
     * @param Component $Owner Besitzer vom Typ Component
     * @param boolean $autoLoadFiles Laedt automatisch Templates und sucht darin GUIs
     * @param array $params additional parameters
     * @throws ReflectionException
     */
    function __construct(?Component $Owner, bool $autoLoadFiles = true, array $params = [])
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
     * @return Input
     */
//    public function getDefaults(): Input
//    {
//        // set default moduleName
//        $this->inspectorProperties['moduleName']['value'] = $this->getName();
//
//        foreach($this->getInspectorProperties() as $key => $property) {
//            $this->Defaults->setVar($key, $property['value']);
//        }
//        return $this->Defaults;
//    }

    /**
     * provides all properties for the Inspector module
     *
     * @return array|array[]
     */
//    public function getInspectorProperties(): array
//    {
//        return $this->inspectorProperties;
//    }

    /**
     * @param array $configuration
     * @param array $properties
     * @return array
     */
//    public function optimizeConfiguration(array $configuration, array $properties): array
//    {
//        $config = [];
//        foreach($configuration as $key => $value) {
//            if(isset($properties[$key])) {
//                $property = $properties[$key];
//                $type = $property['type'] ?? '';
//                switch($type) {
//                    case 'boolean':
//                        if(is_string($value)) {
//                            $value = string2bool($value);
//                        }
//                        break;
//                }
//
//                //                $isPoolOption = $this->getInspectorProperties()[$key]['pool'] ?? false; // serverside only
//                $defaultValue = $property['value'] ?? '';
//                if($defaultValue != $value) {
//                    $config[$key] = $value;
//                }
//
//                if(isset($property['properties']) and is_array($property['properties']) and is_array($configuration[$key])) {
//                    foreach($configuration[$key] as $z => $sub_configuration) {
//                        $config[$key][$z] = $this->optimizeConfiguration($sub_configuration, $property['properties']);
//                    }
//                }
//
//            }
//            //            else {
//            //                $this->poolOptions[$key] = $value;
//            //            }
//        }
//        return $config;
//    }

//    /**
//     * set configuration for module (it only takes different values)
//     *
//     * @param array $configuration
//     */
//    public function setConfiguration(array $configuration)
//    {
//        $this->configuration = $this->optimizeConfiguration($configuration, $this->getInspectorProperties());
//
//        if(isset($this->configuration['moduleName'])) {
//            $this->setName($this->configuration['moduleName']);
//        }
//
//        $this->Input->setVar($this->configuration);
//    }
//
//    /**
//     * returns the configuration
//     *
//     * @return array
//     */
//    public function getConfiguration(): array
//    {
//        return $this->configuration;
//    }
//
//    /**
//     * returns the configuration as json
//     *
//     * @return string
//     */
//    public function getConfigurationAsJSON(): string
//    {
//        return json_encode($this->getConfiguration());
//    }
//
//    /**
//     * returns the configuration as yaml
//     *
//     * @return string
//     */
//    public function getConfigurationAsYAML(): string
//    {
//        return yaml_emit($this->getConfiguration());
//    }

    /**
     * @return array|array[]
     */
//    public function getInspectorValues(): array
//    {
//        $result = $this->getInspectorProperties();
//        foreach($this->options as $key => $value) {
//            $result[$key]['value'] = $value;
//        }
//        return $result;
//    }

    /**
     * Das Template Objekt laedt HTML Vorlagen.
     *
     * @access public
     * @param boolean $search True sucht nach weiteren GUIs
     **/
    function autoLoadFiles(bool $search = true)
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
     * @method static
     * @access public
     * @param string $GUIClassName Name der GUI Klasse
     * @param Component|null $Owner Besitzer dieses Objekts
     * @param Module|null $ParentGUI parent module
     * @param string $params Parameter in der Form key1=value1&key2=value2=&
     * @return Module|null Neues GUI_Module
     */
    public static function createGUIModule(string $GUIClassName, ?Component $Owner, ?Module $ParentGUI, string $params = ''): ?Module
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
            $GUI = new $GUIClassName($Owner, true, $Params->getData());
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
     *
     * @access public
     **/
    function searchGUIsInPreloadedContent()
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
     * @access private
     * @param string $filehandle Handle-Name eines Templates
     * @param string $ident Identifikation innerhalb des Templates
     **/
    function setMarker($ident)
    {
        $this->FileIdent = $ident;
    }

    /**
     * GUI_Module::getMarkerIdent()
     *
     * Gibt einen Merker (Ident) zurueck
     *
     * @access private
     * @return string Ident/Pattern/Muster
     **/
    function getMarkerIdent()
    {
        return $this->FileIdent;
    }

    /**
     * GUI_Module::enableBox()
     *
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
    protected function loadFiles() {}

    /**
     * load, create and register JavaScript GUI
     *
     * @param bool $global makes Module global in window scope
     */
    public function loadJavaScriptGUI(bool $global = true): bool
    {
        if(!$this->Weblication->hasFrame()) {
            return false;
        }

        $className = $this->getClassName();
        $Header = $this->Weblication->getFrame()->getHeaderdata();
        $jsFile = $this->Weblication->findJavaScript($className.'.js', strtolower($className), $this->isBasicLibrary(), false);
        if(!$jsFile) {
            return false;
        }
        $Header->addJavaScript($jsFile);

        $windowCode = '';
        if($global) {
            $windowCode = 'window[\'$'.$this->getName().'\'] = ';
        }
        $Header->addScriptCode($this->getName(),
            $windowCode.'GUI_Module.createGUIModule('.$className.', \''.$this->getName().'\');');
        return true;
    }

    /**
     * Provisioning data before preparing module and there children.
     **/
    public function prepareContent()
    {
        // todo before_provision or before_parents, after_provision or after_parents ?
        $this->provision(); // provision children

        if ($this->isMyXMLHttpRequest and $this->XMLHttpRequestMethod) return true;

        $this->prepare();
        $this->prepareChilds();
    }

    /**
     * provision something
     */
    protected function provision()
    {
        $max = count($this->Modules);
        for ($m = 0; $m < $max; $m++) {
            $this->Modules[$m]->provision();
        }
    }

    /**
     * run/execute the main logic and fill templates.
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
     * Vollende mit dem Aufruf einer Methode (Verwendungszweck Ajax)
     */
    private function finalizeMethod($method)
    {
        header('Content-type: application/json');

        $Result = false;

//        $charset = '';
//        if ($this->Owner instanceof Weblication) {
//            $charset = $this->Owner->getCharset();
//        }

        ob_start();


        // 09.12.21, AM, reworked
        if(!is_callable([$this, $method])) {
            $Xception = new Xception('The method "' . $method . '" in the class ' . $this->getClassName().' is not callable', 0, array(), POOL_ERROR_DISPLAY);
            $Xception->raiseError();
            return;
        }

        // if (method_exists($this, $method)) {
        // eval('$Result = $this->' . $method . '();');

        // todo validate parameters

        try {
            $ReflectionMethod = new ReflectionMethod($this, $method);
            $numberOfParameters = $ReflectionMethod->getNumberOfParameters();
        }
        catch(\ReflectionException $e) {
            $Xception = new Xception('Error calling method '.$method.' on '.$this->getClassName(), 0, [], POOL_ERROR_DISPLAY);
            $Xception->raiseError();
            return;
        }

        $args = [];
        if($numberOfParameters) {
            $parameters = $ReflectionMethod->getParameters();
            foreach($parameters as $Parameter) {
                if($this->Input->exists($Parameter->getName())) {
                    $value = $this->Input->getVar($Parameter->getName());
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
                    $args[] = $value;
                }
            }
        }
        try {
            $Result = $ReflectionMethod->invokeArgs($this, $args);
        }
        catch(\ReflectionException $e) {
            echo $e->getMessage();
        }


        $clientData = [];

        // if ($charset == 'UTF-8') {
        if ($this->plainJSON) {
            $clientData = $Result;
        }
        else {
            $clientData['Result'] = $Result;
            $output = ob_get_contents();
            $clientData['Error'] = (strlen($output) > 0) ? $output : '';
        }
        $json = json_encode($clientData);

        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            $json_last_error = json_last_error();
            if ($json_last_error > 0) {
                if (version_compare(PHP_VERSION, '5.5.0') >= 0) {
                    $json_last_error_msg = json_last_error_msg();
                }
                else {
                    $json_last_error_msg = 'Unbekannter Fehler';
                    switch ($json_last_error) {
                        case JSON_ERROR_DEPTH:
                            $json_last_error_msg = 'Maximale Stacktiefe überschritten';
                            break;

                        case JSON_ERROR_STATE_MISMATCH:
                            $json_last_error_msg = 'Unterlauf oder Nichtübereinstimmung der Modi';
                            break;

                        case JSON_ERROR_CTRL_CHAR:
                            $json_last_error_msg = 'Unerwartetes Steuerzeichen gefunden';
                            break;

                        case JSON_ERROR_SYNTAX:
                            $json_last_error_msg = 'Syntaxfehler, ungültiges JSON';
                            break;

                        case JSON_ERROR_UTF8:
                            $json_last_error_msg = 'Missgestaltete UTF-8 Zeichen, möglicherweise fehlerhaft kodiert';
                            break;
                    }
                }

                return $json_last_error_msg . ' in ' . $this->getClassName() . '->' . $method . '(): ' . print_r($clientData, true);
            }
        }
        /*
        }
        else { // fuer ISO-8859-X und windows-125X
            if ($this->plainJSON) {
                $clientData = $Result;
            }
            else {
                $clientData['Result'] = arrayEncodeToRFC1738($Result);
                $output = ob_get_contents();
                $clientData['Error'] = arrayEncodeToRFC1738((strlen($output) > 0) ? $output : '');
            }
            $json = array2json($clientData);
        }
        */
        ob_end_clean();

        return $json;
    }

    /**
     * Rueckgabe von Ajax Requests 1:1 (ohne Veraenderungen durch den POOL)
     */
    function enablePlainJSON()
    {
        $this->plainJSON = true;
    }

    /**
     * Rueckgabe von Ajax Requests werden vom POOL als Array[Result,Error] umformatiert
     */
    function disablePlainJSON()
    {
        $this->plainJSON = false;
    }

    /**
     * Stellt den Inhalt der Html Templates fertig und sorgt dafuer, dass auch alle Childs fertig gestellt werden.
     * Das ganze geht von Innen nach Aussen!!! (umgekehrt zu CreateGUI, Init, PrepareContent)
     *
     * @access public
     * @return string Content / Inhalt
     */
    function finalizeContent()
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
     * Vollendet die Generierung der Templates (diese virtuelle Methode muss ueberschrieben werden).
     *
     * @access protected
     **/
    protected function finalize()
    {
        return '<font color="red">Error in GUI Module \'' . $this->getClassName() . '\' (@Finalize)! Please override this protected function.</font>';
    }
}