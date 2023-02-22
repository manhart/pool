<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

use pool\classes\Core\Component;
use pool\classes\Core\Module;
use pool\classes\Core\PoolObject;
use pool\classes\Exception\ModulNotFoundException;

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
     * This will call the loadFiles method.
     *
     * @var bool $autoLoadFiles
     */
    protected bool $autoLoadFiles = true;

    /**
     * @var Template $TemplateBox Rapid Template Engine rendert eine Box (nur wenn diese ueber enableBox aktiviert wird)
     */
    private Template $TemplateBox;

    /**
     * This is how you put a HML template around this GUI. E.g. to create a frame around this template
     *
     * @var bool $enabledBox
     */
    private bool $enabledBox = false;

    /**
     * Is this module the Target of an Ajax-Call
     *
     * @var boolean
     */
    private bool $isAjax;

    /**
     * Ajax Request auf eine bestimmte Methode in einem GUI; f�hrt kein prepare aus
     *
     * @var string
     */
    private string $ajaxMethod;

    /**
     * Pass result unchanged as JSON string through
     */
    protected bool $plainJSON = false;

    /**
     * @var bool automatically create GUI_Module in JS
     */
    protected bool $js_createGUIModule = true;

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
     * @param array $params additional parameters
     *
     * @throws Exception
     */
    public function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner, $params);

        $this->ajaxMethod = $_REQUEST[REQUEST_PARAM_METHOD] ?? '';
        $this->isAjax = isAjax() && $_REQUEST[REQUEST_PARAM_MODULE] && $this->getClassName() == $_REQUEST[REQUEST_PARAM_MODULE] && $this->ajaxMethod;

        $this->Template = new Template();
    }

    /**
     * Is this module the Target of an Ajax-Call
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return $this->isAjax;
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

            // PSR-4 style
            $filename = $GUIRootDir . $GUIClassName . '/' . $GUIClassName . '.php';
            if(file_exists($filename)) {
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
     * @param bool $autoLoadFiles parameter of GUI-constructor
     * @param bool $search Do search for GUIs in preloaded Content
     * @return GUI_Module Neues GUI_Module
     * @throws ModulNotFoundException
     * @see GUI_Module::searchGUIsInPreloadedContent()
     */
    public static function createGUIModule(string $GUIClassName, ?Component $Owner, ?Module $ParentGUI, string $params = '',
                                           bool   $autoLoadFiles = true, bool $search = true): GUI_Module
    {
        $class_exists = class_exists($GUIClassName, false);

        if (!$class_exists) {
            GUI_Module::autoloadGUIModule($GUIClassName, $ParentGUI);

            // retest
            $class_exists = class_exists($GUIClassName, false);
        }

        if ($class_exists) {
            $Params = new Input(Input::INPUT_EMPTY);
            $Params->setParams($params);

            /* @var $GUI GUI_Module */
            $GUI = new $GUIClassName($Owner, $Params->getData());
            //TODO check authorisation
            //$GUI->disable();
            if ($ParentGUI instanceof Module) {
                $GUI->setParent($ParentGUI);
            }
            if ($autoLoadFiles && $GUI->autoLoadFiles) {
                $GUI->loadFiles();
                if ($search)
                    $GUI->searchGUIsInPreloadedContent();
            }
            return $GUI;
        } else {//Class not found
            throw new ModulNotFoundException("Fehler beim Erzeugen der Klasse '$GUIClassName'");
        }
    }

    /**
     * Sucht in allen vorgeladenen Html Templates nach fest eingetragenen GUIs.<br>
     * Automatically creates them and adds them to the children of this Modul
     *
     * @param bool $recurse Execute this while creating the GUIs found in the preloaded content
     * @param bool $autoLoadFiles Preload the GUIs found
     * @return void
     * @throws ModulNotFoundException
     * @see GUI_Module::createGUIModule()
     */
    public function searchGUIsInPreloadedContent(bool $recurse = true, bool $autoLoadFiles = true): void
    {
        $TemplateFiles = $this->Template->getFiles();
        foreach ($TemplateFiles as $TemplateFile) {
            //pump content through searchGUIs
            $content = $TemplateFile->getContent();
            $newContent = $this->searchGUIs($content, $recurse, $autoLoadFiles);
            $TemplateFile->setContent($newContent, false);
        }
    }

    /**
     * Durchsucht den Inhalt nach GUIs.
     *
     * @param string $content Zu durchsuchender Inhalt
     * @return string Neuer Inhalt (gefundene GUIs wurden im Html Code ersetzt)
     * @throws ModulNotFoundException
     */
    protected function searchGUIs(string $content, bool $recurse = true, bool $autoLoadFiles = true): string
    {
        $reg = '/\[(GUI_.*)(\((.*)\)|)]/mU';
        $bResult = preg_match_all($reg, $content, $matches, PREG_SET_ORDER);

        if (!$bResult)//no GUIs
            return $content;
        else {//GUIs found
            $newContent = [];
            $caret = 0;
            foreach ($matches as $match) {
                $pattern = $match[0];
                $guiName = $match[1];
                $params = $match[3] ?? '';
                //try building the GUI found
                $new_GUI = $this->createGUIModule($guiName, $this->getOwner(), $this, $params, $autoLoadFiles, $recurse);
                //get unique identifier
                $guiIdentifier = "[{$new_GUI->getName()}]";
                //store reference for later insertion in pasteChildren()
                $new_GUI->setMarker($guiIdentifier);
                //add GUI to child-list
                $this->insertModule($new_GUI);
                unset($new_GUI);

                //find the beginning of this Match
                $beginningOfMatch = strpos($content, $pattern, $caret);
                //save content between Matches
                $newContent[] = substr($content, $caret, $beginningOfMatch - $caret);
                //insert identifier
                $newContent[] = $guiIdentifier;
                //move caret to end of this match
                $caret = $beginningOfMatch + strlen($pattern);
            }//end foreach
            //add remainder
            $newContent[] = substr($content, $caret);
            return implode($newContent);
        }
    }

    /**
     * Wiederbeleben der Child GUIs (meist, falls Autoload auf false gesetzt wurde).
     * Die Funktion muss verwendet werden, wenn zur Laufzeit neue GUI Module gesetzt werden!
     *
     * @access public
     * @param string $content Content / Inhalt
     * @return string Content / Inhalt aller Childs
     *
     * @throws ModulNotFoundException
     * @throws Exception
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
    protected function setMarker(string $marker)
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
     **/
    public function enableBox(string $title = '', string $template = 'tpl_box.html')
    {
        $file = $this->Weblication->findTemplate($template, $this->getClassName());
        if ($file) {
            $this->TemplateBox = new Template();
            $this->TemplateBox->setFilePath('stdout', $file);
            $this->TemplateBox->setVar('TITLE', $title);
            $this->enabledBox = true;
        } else {
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

    /**
     * autoload templates, css- and js-files
     * @return $this
     */
    public function loadFiles()
    {
        if (!$this->getWeblication()) return $this;

        $className = $this->getClassName();

        foreach ($this->templates as $handle => $file) {
            $template = $this->Weblication->findTemplate($file, $className);
            $this->Template->setFilePath($handle, $template);
        }

        $hasFrame = $this->getWeblication()->hasFrame();
        if ($hasFrame) {
            $Frame = $this->getWeblication()->getFrame();
        } else {
            $hasFrame = $this instanceof GUI_CustomFrame;
            if (!$hasFrame) {
                return $this;
            }
            $Frame = $this;
        }

        foreach ($this->cssFiles as $cssFile) {
            $cssFile = $this->getWeblication()->findStyleSheet($cssFile, $className);
            $Frame->getHeadData()->addStyleSheet($cssFile);
        }

        foreach ($this->jsFiles as $jsFile) {
            $jsFile = $this->getWeblication()->findJavaScript($jsFile, $className);
            $Frame->getHeadData()->addJavaScript($jsFile);
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
     *
     * @return bool
     */
    protected function js_createGUIModule(string $className = '', bool $includeJS = true): bool
    {
        if (!$this->js_createGUIModule) {
            return false;
        }
        if (!$this->Weblication->hasFrame()) {
            return false;
        }

        $className = $className ?: $this->getClassName();

        if ($includeJS) {
            $js = $this->Weblication->findJavaScript($className . '.js', $className, $this->isPOOL(), false);
            if (!$js) {
                return false;
            }

            $this->Weblication->getFrame()->getHeadData()->addJavaScript($js);
        }
        return true;
    }

    /**
     * Adds a closed method (Closure) as an Ajax call. Only Ajax methods are callable by the client.
     * @see GUI_Module::registerAjaxCalls()
     * @param string $alias name of the method
     * @param Closure $method class for anonymous function
     * @param bool $noFormat
     * @param mixed ...$meta
     * @return GUI_Module
     */
    protected function registerAjaxMethod(string $alias, Closure $method, bool $noFormat = false, ...$meta): self
    {
        $meta['alias'] = $alias;
        $meta['method'] = $method;
        $meta['noFormat'] = $noFormat;
        $this->ajaxMethods[$alias] = $meta;
        return $this;
    }

    /**
     * frontend control: Prepare data for building the content or responding to an ajax-call<br>
     * Called once all modules and files have been loaded
     */
    public function provision(): void
    {
    }

    /**
     * Runs provision on all modules
     *
     * @return void
     */
    public function provisionContent(): void
    {
        $this->provision();
        foreach ($this->childModules as $modul) {
            if ($modul instanceof GUI_Module)
                $modul->provisionContent();
        }
    }

    /**
     * frontend control: run/execute the main logic and fill templates.
     */
    protected function prepare()
    {
    }

    /**
     * Runs prepare on all modules
     * Preparing modules and their children.
     **/
    public function prepareContent()
    {
        $this->prepare();
        $this->prepareChildren();

        if ($this->js_createGUIModule($this->getClassName()) && $Head = $this->Weblication->getHead()) {
            $Head->setClientData($this, $this->getClientVars());
        }
    }

    /**
     * Bereitet alle Html Templates aller Children auf.
     **/
    private function prepareChildren()
    {
        foreach ($this->childModules as $Module) {
            $Module->importHandoff($this->handoff);
            $Module->prepareContent();
        }
    }

    /**
     * Please override this method to register ajax calls
     * @return void
     * @see GUI_Module::registerAjaxMethod()
     */
    protected function registerAjaxCalls(): void
    {
    }

    /**
     * returns json encoded data of a method call of this object (intended use: ajax)
     *
     * @param string $requestedMethod
     * @return string
     * @throws ReflectionException
     * @throws Exception
     */
    private function invokeAjaxMethod(string $requestedMethod): string
    {
        // avoids unreadable error messages on the client side.
        ini_set('html_errors', 0);

        $result = '';
        if (!$this->enabled()) {
            $this->respondToAjaxCall(null, 'GUI '.self::class.' is not enabled', __METHOD__, 'access-denied', 403);
        }
        $this->registerAjaxCalls();
        $ajaxMethod = $this->ajaxMethods[$requestedMethod] ?? null;
        $Closure = $ajaxMethod['method'] ?? null;

        // 03.11.2022 @todo remove is_callable and the ReflectionMethod that depends on it
        if ($Closure) {
            $this->plainJSON = $ajaxMethod['noFormat'];
        } elseif (!is_callable([$this, $requestedMethod])) {
            return $this->respondToAjaxCall(null,
                "The method '$requestedMethod' in the class {$this->getClassName()} is not callable",
                __METHOD__, 'not-callable');
        }

        // @todo validate parameters?

        try {
            $ReflectionMethod = $Closure ? new ReflectionFunction($Closure) : new ReflectionMethod($this, $requestedMethod);
            $numberOfParameters = $ReflectionMethod->getNumberOfParameters();
        } catch (ReflectionException) {
            return $this->respondToAjaxCall(null, "Error calling method {$requestedMethod} on {$this->getClassName()}",
                __METHOD__, 'reflection');
        }

        // collect succeeding ajax calls that are not closures
        if (!$Closure) {
            Log::info("The method {$this->getClassName()}:{$requestedMethod} is not used as Closure ", ['className' => $this->getClassName(),
                'method' => $requestedMethod], 'ajaxCallLog');
        }

        error_clear_last();

        ob_start();


        $args = [];
        if ($numberOfParameters) {
            $parameters = $ReflectionMethod->getParameters();
            foreach ($parameters as $Parameter) {
                $value = $this->Input->getVar($Parameter->getName(), ($Parameter->isOptional() ? $Parameter->getDefaultValue() : ''));
                if (is_string($value)) {
                    if ($Parameter->getType()) {
                        switch ($Parameter->getType()->getName()) {
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
                    } else {
                        if ($value === 'true' or $value === 'false') {
                            $value = (bool)$value;
                        }
                    }
                }

                $args[] = $value;
            }
        }
        $callingClassName = $this->getClassName();
        try {
            if ($Closure) {
                //TODO check Authorisation
                //if (!$accessGranted)
                //    return $this->respondToAjaxCall(null, $reason,__METHOD__, 'access-denied',405);
                //setup Handler for aborting/crashing Ajax methods?? register_shutdown_function();

                // alternate: $result = $Closure->call($this, ...$args); // bind to another object possible
                /** @var mixed $result */
                $result = $Closure(...$args);
            } else {
                $result = $ReflectionMethod->invokeArgs($this, $args);
                $callingClassName = $ReflectionMethod->getDeclaringClass()->getName();
            }
        } catch (Throwable $e) {
            echo $e->getMessage();
        }

        $undefinedContent = ob_get_contents();
        ob_end_clean();
        return $this->respondToAjaxCall($result, $undefinedContent,
            "{$callingClassName}:{$requestedMethod}", 'user');
    }

    /**
     * checks if module is configurable (uses trait Configurable.trait.php; other solution would be via Reflections)
     *
     * @return bool
     */
    public function isConfigurable(): bool
    {
        return false;
    }

    /**
     * Creates a response to an Ajax call handling client format and encoding
     *
     * @param mixed $clientData
     * @param mixed $error
     * @param string $callingMethod optional; use __METHOD__
     * @param string $errorType
     * @param int $statusCode
     * @return string
     */
    protected function respondToAjaxCall(mixed $clientData, mixed $error, string $callingMethod = '', string $errorType='', int $statusCode = 200): string
    {
        header('Content-type: application/json', true, $statusCode);
        if (!$this->plainJSON) $clientData = [//standard client data-format
            'data' => $clientData,
            'success' => !$error,
            'error' => ($error ? ['message' => $error, 'type'=>$errorType] : null)
        ];
        //encode data
        $json = json_encode($clientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        if (json_last_error() == JSON_ERROR_NONE) return $json;
        //encoding error handling
        $error = json_last_error_msg() . ' in ' . $callingMethod . ': ' . print_r($clientData, true);
        $clientData = ['data' => [], 'success' => false, 'error' => ['message' => $error, 'type' => 'syntax']];
        return json_encode($clientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * enable plain JSON return (without change by the POOL)
     * @deprecated Set this in the Metadata when registering your method.
     * @see GUI_Module::registerAjaxMethod()
     * @param bool $activate
     * @return GUI_Module
     */
    public function respondAsPlainJSON(bool $activate = true): GUI_Module
    {
        $this->plainJSON = $activate;
        return $this;
    }

    /**
     * Stellt den Inhalt der Html Templates fertig und sorgt dafuer, dass auch alle Children fertiggestellt werden.
     * Das ganze geht von Innen nach Aussen!!! (umgekehrt zu CreateGUI, Init, PrepareContent)
     *
     * @return string Content / Inhalt
     * @throws Exception
     */
    public function finalizeContent(): string
    {
        if ($this->enabled()) {
            $this->finalizeChildren();
            if ($this->isAjax) {//GUI is target of the Ajax-Call
                //Start the Ajax Method -> returns JSON
                $content = $this->invokeAjaxMethod($this->ajaxMethod);
            } else {
                //Parse Templates or get the finished Content from a specific implementation
                $content = $this->finalize();
                //Wrap a GUI_Box around the content
                if ($this->enabledBox) {
                    $this->TemplateBox->setVar('CONTENT', $content);
                    $this->TemplateBox->parse('stdout');
                    $content = $this->TemplateBox->getContent('stdout');
                    $this->TemplateBox->clear();
                }
            }
            return $this->pasteChildren($content);
        } else {
            return "";
        }
    }

    /**
     * Fertigt alle Html Templates der Childs an.
     *
     * @throws Exception
     */
    private function finalizeChildren()
    {
        /** @var GUI_Module $GUI */
        foreach ($this->childModules as $GUI) {
            if (!$GUI->enabled()) continue;
            $GUI->finalContent = $GUI->finalizeContent();
        }
    }

    /**
     * adopts the content of the children
     *
     * @param string $content Eigener Content
     * @return string Eigener Content samt dem Content der Child GUIs
     */
    private function pasteChildren(string $content): string
    {
        $replace_pairs = [];
        /** @var GUI_Module $GUI */
        foreach ($this->childModules as $GUI) {
            $replace_pairs[$GUI->getMarker()] = $GUI->finalContent;
        }
        return strtr($content, $replace_pairs);
    }

    /**
     * returns the contents of the module
     */
    protected function finalize(): string
    {
        $content = '';
        foreach ($this->templates as $handle => $tplFile)
            $content .= $this->Template->parse($handle)->getContent($handle);
        return $content;
    }
}