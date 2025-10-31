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

namespace pool\classes\GUI;

use Closure;
use Exception;
use JetBrains\PhpStorm\Pure;
use Log;
use pool\classes\Autoloader;
use pool\classes\Core\Component;
use pool\classes\Core\Http\Request;
use pool\classes\Core\Input\Filter\DataType;
use pool\classes\Core\Input\Input;
use pool\classes\Core\Module;
use pool\classes\Core\Weblication;
use pool\classes\Database\DataInterface;
use pool\classes\Exception\MissingArgumentException;
use pool\classes\Exception\ModulNotFoundException;
use pool\classes\GUI\Builtin\GUI_CustomFrame;
use pool\guis\GUI_Schema\GUI_Schema;
use pool\utils\StringHelper;
use ReflectionException;
use ReflectionFunction;
use TempBlock;
use Template;
use Throwable;

use const pool\NAMESPACE_SEPARATOR;
use const pool\PWD_TILL_GUIS;

// const FIXED_PARAM_CONFIG = 'config';

/**
 * Class GUI_Module
 * Base class for all graphical frontend controls.
 * They have the template engine on board. GUIs defined in HTML templates with the syntax [GUI_xyz(param1=value&param2=value] are read in automatically,
 * instantiated, and filled with parameters.
 *
 * @package pool
 * @since 2003-07-10
 */
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
     * This will call the loadFiles method.
     *
     * @var bool $autoLoadFiles
     */
    protected bool $autoLoadFiles = true;

    /**
     * Pass result unchanged as JSON string through
     */
    protected bool $plainJSON = false;

    /**
     * @var bool automatically create GUI_Module in JS
     */
    protected bool $js_createGUIModule = true;

    /**
     * @var array<string, string> $templates files (templates) to be loaded, usually used with $this->Template->setVar(...) in the prepare function. Defined as an associated array
     *     [handle => tplFile].
     */
    protected array $templates = [];

    /**
     * @var array<int, string> $jsFiles JavaScript files to be loaded, defined as an indexed array
     */
    protected array $jsFiles = [];

    /**
     * @var array|string[] $cssFiles CSS files to be loaded, defined as an indexed array
     */
    protected array $cssFiles = [];

    /**
     * @var array<string, string> $ajaxMethods
     */
    protected array $ajaxMethods = [];

    /**
     * Merkt sich mit dieser Variable das eigene Muster im Template (dient der Identifikation)
     */
    private string $marker;

    /**
     * Holds the final content of this GUI_Module
     */
    private string $finalContent = '';

    /**
     * Is this module the Target of an Ajax-Call
     */
    private bool $isAjax;

    /**
     * Ajax Request auf eine bestimmte Methode in einem GUI; f�hrt kein prepare aus
     *
     * @var string
     */
    private string $ajaxMethod;

    private static array $guiCache = [];


    /**
     * Checks if we are in an ajax call and creates a template object
     *
     * @param Component|null $Owner Besitzer vom Typ Component
     * @param array $params additional parameters
     */
    public function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner, $params);

        $this->ajaxMethod = $_REQUEST[Weblication::REQUEST_PARAM_METHOD] ?? '';
        $this->isAjax = Request::isAjax() && $_REQUEST[Weblication::REQUEST_PARAM_MODULE] && $this->ajaxMethod &&
            ($_REQUEST[Weblication::REQUEST_PARAM_MODULE] === static::class || $_REQUEST[Weblication::REQUEST_PARAM_MODULE] === $this->getClassName());

        // set the module name (if it is necessary for ajax calls)
        if ($this->isAjax) {
            $moduleName = $_REQUEST['moduleName'] ?? null;
            if ($moduleName) {
                $filter = DataType::getFilter(DataType::ALPHANUMERIC);
                $moduleName = $filter($moduleName);
                $this->setName($moduleName);
            }
        }
        $this->Template = new Template();
        $this->Template->addGlobalHook($this->searchGUIsInParsedBlockContent(...));// adds hook for parsed block content
    }

    /**
     * Creates a new GUI module based on the class name.
     * The owner of the GUI's in an application is the class Weblication. It can also be another owner of type Component (@param string $GUIClassName Name der GUI Klasse
     *
     * @param Component|null $Owner Besitzer dieses Objekts
     * @param Module|null $ParentGUI parent module
     * @param string $params Parameter in der Form key1=value1&key2=value2=&
     * @param bool $autoLoadFiles parameter of GUI-constructor
     * @param bool $search Do search for GUIs in preloaded Content
     * @return GUI_Module Neues GUI_Module
     * @throws ModulNotFoundException
     * @todo rethink).
     * @see GUI_Module::searchGUIsInPreloadedContent()
     */
    public static function createGUIModule(
        string $GUIClassName,
        ?Component $Owner = null,
        ?Module $ParentGUI = null,
        string $params = '',
        bool $autoLoadFiles = true,
        bool $search = true,
    ): GUI_Module {
        $GUIFQClassName = self::$guiCache[$GUIClassName] ??= self::findGUIModule($GUIClassName, $ParentGUI);
        $paramsInput = new Input(Input::EMPTY);
        $paramsInput->setParams($params);

        /* @var $GUI GUI_Module */
        $GUI = new $GUIFQClassName($Owner, $paramsInput->getData());
        //TODO check authorisation
        //$GUI->disable();
        if ($ParentGUI instanceof Module) {
            $GUI->setParent($ParentGUI);
        }
        if ($autoLoadFiles && $GUI->autoLoadFiles) {
            $GUI->loadFiles();
            if ($search) {
                $GUI->searchGUIsInPreloadedContent();
            }
        }
        return $GUI;
    }

    /**
     * Autoloader for GUIModules
     */
    public static function autoloadGUIModule(string $GUIClassName, ?Module $ParentGUI = null): string|false
    {
        $GUIRootDirs = [];
        if (Autoloader::hasNamespace($GUIClassName)) {
            /** @noinspection PhpUndefinedConstantInspection */
            $baseNameSpacePath = defined('BASE_NAMESPACE_PATH') ? BASE_NAMESPACE_PATH : DIR_DOCUMENT_ROOT;
            $GUIRootDirs[] = $baseNameSpacePath;
            $GUIClassName = str_replace(NAMESPACE_SEPARATOR, '/', $GUIClassName);
        } else {
            $GUIRootDirs[] = DIR_APP_ROOT.'/'.PWD_TILL_GUIS;
            if (defined('DIR_POOL_ROOT')) $GUIRootDirs[] = DIR_POOL_ROOT.'/'.PWD_TILL_GUIS;
            if (defined('DIR_COMMON_ROOT')) $GUIRootDirs[] = DIR_COMMON_ROOT.'/'.PWD_TILL_GUIS;
            $GUIClassName = "$GUIClassName/$GUIClassName";
        }

        // try to load class
        foreach ($GUIRootDirs as $GUIRootDir) {
            $GUIRootDir = addEndingSlash($GUIRootDir);

            // PSR-4 style
            $filename = "$GUIRootDir$GUIClassName.php";
            if (Autoloader::requireFile($filename)) {
                return $filename;
            }

            if ($ParentGUI instanceof Module) {
                // verschachtelte GUI's
                $parent_directory = '';
                $parent_directory_without_frame = '';
                do {
                    if ($ParentGUI instanceof GUI_Schema) { // GUI_Schema is not nestable
                        $ParentGUI = $ParentGUI->getParent();
                        continue;
                    }
                    if (!$ParentGUI instanceof GUI_CustomFrame) {
                        $parent_directory_without_frame = "{$ParentGUI->getClassName()}/$parent_directory_without_frame";
                    }
                    $parent_directory = "{$ParentGUI->getClassName()}/$parent_directory";
                    $ParentGUI = $ParentGUI->getParent();
                } while ($ParentGUI !== null);

                $filename = "$GUIRootDir$parent_directory$GUIClassName.php";
                if (Autoloader::requireFile($filename)) {
                    return $filename;
                }

                $filename = "$GUIRootDir$parent_directory_without_frame$GUIClassName.php";
                if (Autoloader::requireFile($filename)) {
                    return $filename;
                }
            }
        }
        return false;
    }

    /**
     * Autoload templates, css- and js-files
     */
    public function loadFiles(): static
    {
        if (!$this->getWeblication()) {
            return $this;
        }

        $guiClassFolder = $this->getGUIClassFolder();

        $templates = $this->getTemplates();
        foreach ($templates as $handle => $file) {
            assert(is_string($handle), 'invalid handle in template list of '.static::class.' got templates '.var_export($templates, true));
            $template = $this->getWeblication()->findTemplate($file, $guiClassFolder, $this->isPOOL());
            $this->Template->setFilePath($handle, $template);
        }

        if ($this->getWeblication()->hasFrame()) {
            $Frame = $this->getWeblication()->getFrame();
        } elseif ($this instanceof GUI_CustomFrame) {
            $Frame = $this;
        } else {
            return $this;
        }

        $headData = $Frame->getHeadData();
        $files = ['css' => $this->getCssFiles(), 'js' => $this->getJsFiles()];
        foreach ($files as $extension => $fileList) {
            foreach ($fileList as $file) {
                if (str_ends_with($file, ".$extension")) {
                    $file = substr($file, 0, -strlen(".$extension"));
                }
                $headData->addClientWebAsset($extension, $file, $guiClassFolder, $this->isPOOL());
            }
        }

        /**
         * Including of the JavaScript class and Stylesheet with the same class/file name is done in the prepareContent() method.
         *
         * @see GUI_Module::prepareContent()
         */
        return $this;
    }

    /**
     * Returns the parts of the path that is after the guis directory (supports stackable GUI's).
     */
    public function getGUIClassFolder(string $marker = PWD_TILL_GUIS.DIRECTORY_SEPARATOR): string
    {
        return StringHelper::sliceAfter($this->getClassDirectory(), $marker);
    }

    /**
     * @return array<string, string> Returns the templates that should be loaded.
     */
    protected function getTemplates(): array
    {
        return $this->templates;
    }

    /**
     * Sucht in allen vorgeladenen Html Templates nach fest eingetragenen GUIs.<br>
     * Automatically creates them and adds them to the children of this Modul
     *
     * @param bool $recurse Execute this while creating the GUIs found in the preloaded content
     * @param bool $autoLoadFiles Preload the GUIs found
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
     * Searches for GUIs in the parsed block content
     */
    protected function searchGUIsInParsedBlockContent(TempBlock $block, string $content): string
    {
        return $this->searchGUIs($content);
    }

    /**
     * Durchsucht den Inhalt nach GUIs.
     *
     * @param string $content content to be searched
     * @return string Neuer Inhalt (gefundene GUIs wurden im Html Code ersetzt)
     * @throws ModulNotFoundException
     */
    protected function searchGUIs(string $content, bool $recurse = true, bool $autoLoadFiles = true): string
    {
        // search for GUIs like [\namespace\GUI_ClassName(key=val)] or [GUI_ClassName] in the content of the template
        $reg = '/\[([\w\x5c]*GUI_\w+)(\([^()]*\))?]/mU';
        $bResult = preg_match_all($reg, $content, $matches, PREG_SET_ORDER);

        if (!$bResult) {//no GUIs
            return $content;
        }

        //GUIs found
        $newContent = [];
        $caret = 0;
        foreach ($matches as $match) {
            [$pattern, $guiName, $params] = $match + [2 => '']; /* 2: params; php doesn't allow null coalescing when destructuring arrays  */
            $params = trim($params, '()');
            //try building the GUI found
            $new_GUI = self::createGUIModule($guiName, $this->getOwner(), $this, $params, $autoLoadFiles, $recurse);
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

    /**
     * Setzt sich Merker, auf welchem FileHandle sitze ich. Welches Muster (Ident) habe ich innerhalb des Templates.
     *
     * @param string $marker Identifikation innerhalb des Templates
     */
    protected function setMarker(string $marker): void
    {
        $this->marker = $marker;
    }

    /**
     * Attempts to find the GUI class in the file system.
     */
    private static function findGUIModule(string $GUIClassName, ?Module $ParentGUI): string
    {
        if (class_exists($GUIClassName, false)) return $GUIClassName;
        // attempt autoload
        if (!$fileName = self::autoloadGUIModule($GUIClassName, $ParentGUI))
            throw new ModulNotFoundException("Error while creating the class '$GUIClassName'");

        if (class_exists($GUIClassName, false)) return $GUIClassName;
        // construct namespace according to PSR-4 standards
        $docRoot = addEndingSlash(DIR_DOCUMENT_ROOT);
        /** @noinspection PhpUndefinedConstantInspection */
        $baseNameSpace = defined('BASE_NAMESPACE_PATH') ? BASE_NAMESPACE_PATH : '';
        $nameSpaceClassName = str_replace([$docRoot, '/'], [$baseNameSpace, NAMESPACE_SEPARATOR], remove_extension($fileName));
        if (class_exists($nameSpaceClassName, false)) return $nameSpaceClassName;
        throw new ModulNotFoundException("Your namespace for '$GUIClassName' doesn't match PSR-4 standards. Expected '$nameSpaceClassName'");
    }

    /**
     * Is this module the Target of an Ajax-Call
     */
    public function isAjax(): bool
    {
        return $this->isAjax;
    }

    /**
     * Wiederbeleben der Child GUIs (meist, falls Autoload auf false gesetzt wurde).
     * Die Funktion muss verwendet werden, wenn zur Laufzeit neue GUI Module gesetzt werden!
     *
     * @param string $content Content
     * @return string Content from the template with the child GUIs
     * @throws ModulNotFoundException
     * @throws Exception
     */
    public function reviveChildGUIs(string $content): string
    {
        $content = $this->searchGUIs($content);
        $this->prepareChildren();
        $this->finalizeChildren();
        return $this->pasteChildren($content);
    }

    /**
     * Bereitet alle Html Templates aller Children auf.
     */
    private function prepareChildren(): void
    {
        $this->finalizePendingBlocks();
        foreach ($this->childModules as $Module) {
            $Module->importHandoff($this->handoff);
            if ($Module instanceof self) {
                $Module->prepareContent();
            }
        }
    }

    /**
     * Main logic of the front controller. Compile main content.
     */
    public function prepareContent(): void
    {
        if (!$this->enabled()) return;
        $this->prepare();
        $this->prepareChildren();
        if (!$this->enabled()) return;
        $includedClientCode = $this->js_createGUIModule($this->getClassName());
        if (!$includedClientCode) return;
        $this->Weblication->getHead()->setClientData($this, $this->getClientVars());
    }

    /**
     * frontend control: run/execute the main logic and fill templates.
     */
    protected function prepare(): void {}

    /**
     * Parses all unfinished blocks in the templates.
     * This method triggers the hook system during block parsing,
     * which might create and insert new GUI's as children
     */
    private function finalizePendingBlocks(): void
    {
        $templates = $this->getTemplates();
        foreach ($templates as $handle => $_) {
            $this->Template->parsePendingBlocks($handle);
        }
    }

    /**
     * Automatically includes the appropriate JavaScript class, instantiates it, and adds it to JS Weblication. It also includes the CSS file.
     */
    protected function js_createGUIModule(string $className = '', bool $includeJS = true, bool $includeCSS = true): bool
    {
        if (!$this->js_createGUIModule) return false;
        $Frame = $this->getWeblication()?->getFrame();
        $Frame ??= $this;
        if (!$Frame instanceof GUI_CustomFrame) return false;
        $className = $className ?: $this->getClassName();
        $guiClassFolder = $this->getGUIClassFolder();
        //associated Stylesheet
        if ($includeCSS && ($css = $this->Weblication->findStyleSheet("$className.css", $guiClassFolder, $this->isPOOL(), false))) {
            $Frame->getHeadData()->addStyleSheet($css);
        }
        //associated Script
        if ($includeJS && ($jsFile = $this->Weblication->findJavaScript("$className.js", $guiClassFolder, $this->isPOOL(), false))) {
            $Frame->getHeadData()->addJavaScript($jsFile);
        }
        return (bool)($jsFile ?? true);//result of JS-lookup or true
    }

    /**
     * Prepares all html templates of the children.
     */
    private function finalizeChildren(): void
    {
        foreach ($this->childModules as $currentChild) {
            if (!$currentChild->enabled()) continue;
            if (!$currentChild instanceof self) continue;
            $currentChild->finalContent = $currentChild->finalizeContent();
        }
    }

    /**
     * Return finished HTML content
     * Walks from the inside out! (vice versa to createGUIModule, init, prepareContent)
     *
     * @return string Content / Inhalt
     */
    public function finalizeContent(): string
    {
        if (!$this->enabled()) return '';
        if ($this->isAjax) {
            //GUI is target of the Ajax-Call
            $content = $this->invokeAjaxMethod($this->ajaxMethod);
        } else {
            $this->finalizeChildren();
            $content = $this->finalize();
        }
        return $this->pasteChildren($content);
    }

    /**
     * Processes transactions
     * This method is used to start/begin, commit, or rollback a TRANSACTION for the given database interfaces.
     *
     * @throws Exception
     */
    private function processTransactions(array $dbInterfaces, string $action): void
    {
        foreach ($dbInterfaces as $dbInterface) {
            // Validate the interface is registered
            DataInterface::getInterfaceForResource($dbInterface);
            // Perform the specified action
            match ($action) {
                'start' => DataInterface::beginTransaction($dbInterface),
                'commit' => DataInterface::commit($dbInterface),
                'rollback' => DataInterface::rollback($dbInterface),
            };
        }
    }

    /**
     * Returns json encoded data of a method call of this object (intended use: ajax)
     */
    private function invokeAjaxMethod(string $requestedMethod): string
    {
        // avoids unreadable error messages on the client side.
        ini_set('html_errors', 0);

        $result = '';
        if (!$this->enabled()) {
            return $this->respondToAjaxCall(null, "GUI {$this->getClassName()} is not enabled", __METHOD__, 'access-denied', 403);
        }
        $this->registerAjaxCalls();
        $ajaxMethod = $this->ajaxMethods[$requestedMethod] ?? null;
        $Closure = $ajaxMethod['method'] ?? null;
        $this->plainJSON = $ajaxMethod['noFormat'] ?? false;
        $dbInterfaces = $ajaxMethod['dbInterfaces'] ?? [];
        $logConfigurationName = $ajaxMethod['logConfigurationName'] ?? null;

        if (!$Closure instanceof Closure) {
            if (is_callable([$this, $requestedMethod]))// 03.11.2022 @todo remove is_callable and the ReflectionMethod that depends on it
                return $this->respondToAjaxCall(
                    null,
                    "Method $requestedMethod is not registered for GUI {$this->getClassName()}",
                    __METHOD__,
                    'access-denied',
                    403,
                    logConfigurationName: $logConfigurationName
                );
            return $this->respondToAjaxCall(
                null,
                "The method '$requestedMethod' in the class {$this->getClassName()} is not a callable",
                __METHOD__,
                'not-callable',
                405,
                logConfigurationName: $logConfigurationName,
                logLevel: Log::LEVEL_WARN
            );
        }

        // @todo validate parameters?
        try {
            $ReflectionMethod = new ReflectionFunction($Closure);
        } catch (ReflectionException $e) {
            return $this->respondToAjaxCall(null, $e->getMessage(), __METHOD__, 'reflection', 500,
                logConfigurationName: $logConfigurationName, logLevel: Log::LEVEL_ERROR);
        }

        error_clear_last();
        ob_start();

        $callingClassName = $this->getClassName();
        try {
            $arguments = clone $this->Input;
            $args = $this->prepareMethodArguments($ReflectionMethod, $arguments);
            try {
                $this->processTransactions($dbInterfaces, 'start');
                //TODO check Authorisation
                //if (!$accessGranted)
                //    return $this->respondToAjaxCall(null, $reason,__METHOD__, 'access-denied',405);

                // alternate: $result = $Closure->call($this, ...$args); // bind to another object possible

                $result = $Closure(...$args);

                $rollbackTransaction = $result['poolRollbackTransaction'] ?? false;
                $statusCode = $result['poolStatusCode'] ?? null;
                $this->processTransactions($dbInterfaces, $rollbackTransaction ? 'rollback' : 'commit');
            } catch (Throwable $e) {
                $this->processTransactions($dbInterfaces, 'rollback');
                $this->plainJSON = false;
                $statusCode = 418;
            }
        } catch (Throwable $e) {
            $this->plainJSON = false;
            $statusCode = 400;
        }

        $errorText = trim(ob_get_clean());

        if (isset($e)) {//ups
            $result = IS_DEVELOP ? $e->getTraceAsString() : '';
            $errorText = $e->getMessage();
            $potentialErrorType = $e::class;
        } else {
            $potentialErrorType = 'undefined (Spurious output by invoked method)';
        }
        return $this->respondToAjaxCall(
            $result,
            $errorText,
            "$callingClassName:$requestedMethod",
            $potentialErrorType,
            $statusCode ?? 200,
            $ajaxMethod['flags'] ?? 0,
            $logConfigurationName,
            $errorText ? Log::LEVEL_ERROR : Log::LEVEL_INFO
        );
    }

    /**
     * Creates a response to an Ajax call handling client format and encoding
     *
     * @param string $callingMethod optional; use __METHOD__
     */
    protected function respondToAjaxCall(mixed $clientData, mixed $error, string $callingMethod = '', string $errorType = '', int $statusCode = 200, int $flags = 0,
        ?string $logConfigurationName = null, int $logLevel = Log::LEVEL_INFO): string
    {
        Log::message(
            $error,
            $logLevel,
            ['className' => $this->getClassName(), 'method' => $this->ajaxMethod, 'errorType' => $errorType, 'status' => $statusCode],
            $logConfigurationName ?? 'ajaxCallLog',
        );
        header('Content-Type: application/json');
        if (!$this->plainJSON || $statusCode != 200) {//report failed functions in standard format
            $clientData = [//standard client data-format
                'data' => $clientData,
                'success' => !$error,
                'error' => ($error ? ['message' => $error, 'type' => $errorType] : null),
            ];
            http_response_code($statusCode);
        }
        //encode data
        $json = json_encode($clientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | $flags);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }
        //encoding error handling
        $error = json_last_error_msg().' in '.$callingMethod.': '.print_r($clientData, true);
        $clientData = ['data' => [], 'success' => false, 'error' => ['message' => $error, 'type' => 'syntax']];
        return json_encode($clientData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Please override this method to register ajax calls
     *
     * @see GUI_Module::registerAjaxMethod()
     */
    protected function registerAjaxCalls(): void {}

    /** @return string[] */
    protected function getJsFiles(): array
    {
        return $this->jsFiles;
    }

    protected function getCssFiles(): array
    {
        return $this->cssFiles;
    }

    #[Pure]
    /** sets the success and message of a result and returns the result */
    protected static function succeed(array $result = [], string $message = ''): array
    {
        $result['success'] = true;
        $result['message'] = $message;
        return $result;
    }

    #[Pure]
    /** sets the success and message of a result and returns the result */
    protected static function fail(array $result = [], string $message = ''): array
    {
        $result['success'] = false;
        $result['message'] = $message;
        return $result;
    }

    #[Pure]
    /** sets the success, rollback flag, and message of a result and returns the result */
    protected static function abort(array $result = [], string $message = '', int $statusCode = 422): array
    {
        $result['success'] = false;
        $result['message'] = $message;
        $result['poolRollbackTransaction'] = true;
        $result['poolStatusCode'] = $statusCode;
        return $result;
    }

    /**
     * Returns the contents of the module<br>
     * Parse Templates or get the finished Content from a specific implementation
     */
    protected function finalize(): string
    {
        $content = '';
        $templates = $this->getTemplates();
        if (!array_key_exists('stdout', $templates) && in_array('stdout', $this->Template->getFiles(false))) {
            $templates['stdout'] = null;//patch for existing projects using loadFiles()
        }
        foreach ($templates as $handle => $_) {
            $content .= $this->Template->parse($handle)->getContent($handle);
        }
        return $content;
    }

    /**
     * Adopts the content of the children
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
     * Returns an Identifier/Pattern for this GUI
     */
    private function getMarker(): string
    {
        return $this->marker;
    }

    /**
     * Runs provision on all modules
     */
    public function provisionContent(): void
    {
        $this->provision();
        foreach ($this->childModules as $modul) {
            if ($modul instanceof self) {
                $modul->provisionContent();
            }
        }
    }

    /**
     * frontend control: Prepare data for building the content or responding to an ajax-call<br>
     * Called once all modules and files have been loaded
     */
    protected function provision(): void {}

    /**
     * Checks if module is configurable (uses trait Configurable.trait.php; other solution would be via Reflections)
     */
    public function isConfigurable(): bool
    {
        return false;
    }

    /**
     * Creates a new Template object based on the given file and handle.
     *
     * @param string $file The path to the template file.
     * @param string $handle The handle to be used for the template file. Default is 'stdout'.
     * @return Template The created Template object.
     */
    protected function createTemplate(string $file, string $handle = 'stdout'): Template
    {
        $template = new Template();
        $template->setFilePath($handle, $file);
        return $template;
    }

    /**
     * Adds a closed method (Closure) as an Ajax call. Only Ajax methods are callable by the client.
     *
     * @param string $alias name of the method
     * @param Closure $method class for anonymous function
     * @param array $dbInterfaces list of database interfaces, for which transactions should be started, committed, or rolled back
     * @see GUI_Module::registerAjaxCalls()
     */
    protected function registerAjaxMethod(
        string $alias,
        Closure $method,
        bool $noFormat = false,
        array $dbInterfaces = [],
        ?string $logConfigurationName = null,
        ...$meta
    ): self {
        $meta['alias'] = $alias;
        $meta['method'] = $method;
        $meta['noFormat'] = $noFormat;
        $meta['dbInterfaces'] = $dbInterfaces;
        $meta['logConfigurationName'] = $logConfigurationName;
        $this->ajaxMethods[$alias] = $meta;
        return $this;
    }

    /**
     * Enable plain JSON return (without a change by the POOL)
     *
     * @deprecated Set this in the Metadata when registering your method.
     * @see GUI_Module::registerAjaxMethod()
     */
    protected function respondAsPlainJSON(bool $activate = true): GUI_Module
    {
        $this->plainJSON = $activate;
        return $this;
    }

    /**
     * Prepares and returns an array of method arguments based on the given reflection method and input arguments.
     *
     * @param ReflectionFunction $ReflectionMethod The reflection of the method whose arguments are being prepared.
     * @param Input $arguments The input containing the argument values.
     * @return array Prepared arguments.
     * @throws MissingArgumentException|ReflectionException If a required argument is missing.
     */
    private function prepareMethodArguments(ReflectionFunction $ReflectionMethod, Input $arguments): array
    {
        $args = [];

        $numberOfParameters = $ReflectionMethod->getNumberOfParameters();
        if ($numberOfParameters) {
            $parameters = $ReflectionMethod->getParameters();
            $isVariadicParameter = $parameters[$numberOfParameters - 1]->isVariadic();
            if ($isVariadicParameter) array_pop($parameters);
            foreach ($parameters as $Parameter) {
                $parameterName = $Parameter->getName();
                $value = $arguments->getVar($parameterName) ?? ($Parameter->isOptional() ? $Parameter->getDefaultValue()
                    : throw new MissingArgumentException("Missing parameter $parameterName"));
                $arguments->delVar($parameterName);
                if (is_string($value)) {
                    if ($Parameter->hasType() && $Parameter->getType()->getName() !== 'mixed') {
                        $value = match ($Parameter->getType()->getName()) {
                            'float' => (float)$value,
                            'int' => (int)$value,
                            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
                            default => $value,
                        };
                    } elseif ($value === 'true' || $value === 'false') {
                        $value = $value === 'true';
                    }
                }
                $args[] = $value;
            }
        }
        if ($numberOfParameters && $isVariadicParameter) {
            $arguments->delVars($this->Weblication::getCoreRequestParameters());
            $args = [...$args, ...$arguments->getData()];
        }
        return $args;
    }

    /**
     * Render the content of this module and its children. Return finished HTML content
     */
    public function render(): string
    {
        $this->provisionContent();
        if(!$this->isAjax) $this->prepareContent();
        return $this->finalizeContent();
    }
}
