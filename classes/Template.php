<?php
/**
 * -= Rapid Template Engine (RTE) =-
 * Template.class.php
 * Rapid Template Engine (RTE) ist eine Template-Engine für PHP. Genauer gesagt erlaubt es die einfache Trennung von
 * Applications-Logik und Design/Ausgabe. Dies ist vor allem wünschenswert, wenn der Applikationsentwickler nicht dieselbe
 * Person ist wie der Designer. Nehmen wir zum Beispiel eine Webseite, die Zeitungsartikel ausgibt.
 * Der Titel, die Einführung, der Author und der Inhalt selbst enthalten keine Informationen, darüber wie sie dargestellt
 * werden sollen. Also werden sie von der Applikation an RTE übergeben, damit der Designer in den Templates mit einer
 * Kombination von HTML- und Template-Tags die Ausgabe (Tabellen, Hintergrundfarben, Schriftgrößen, Stylesheets, etc.)
 * gestalten kann. Falls nun die Applikation eines Tages angepasst werden muss, ist dies für den Designer nicht von
 * Belang, da die Inhalte immer noch genau gleich übergeben werden. Genauso kann der Designer die Ausgabe der Daten beliebig
 * verändern, ohne dass eine Änderung der Applikation vorgenommen werden muss. Somit kann der Programmierer die
 * Applikations-Logik und der Designer die Ausgabe frei anpassen, ohne sich dabei in die Quere zu kommen.
 * Features:
 * Schnelligkeit
 * dynamische Blöcke
 * beliebige Template-Quellen
 * ermöglicht die direkte Einbettung von PHP-Code (Obwohl es weder benötigt noch empfohlen wird, da die Engine einfach erweiterbar ist).
 * @date $Date: 2007/03/13 08:52:50 $
 *
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * @since 2003-07-12
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\Core\PoolObject;
use pool\classes\Core\Weblication;
use pool\classes\Exception\FileNotFoundException;
use pool\classes\translator\Translator;

const TEMP_VAR_START = '{';
const TEMP_VAR_END = '}';
const TEMP_BLOCK_IDENT = 'BLOCK';
const TEMP_INCLUDE_IDENT = 'INCLUDE';
const TEMP_INCLUDESCRIPT_IDENT = 'INCLUDESCRIPT';
const TEMP_TRANSL_IDENT = 'TRANSL';
const TEMP_SESSION_IDENT = 'SESSION';

/**
 * TempHandle ist die abstrakte Basisklasse für alle eingebetteten Template Elemente (oder auch Platzhalter).
 * Die Klasse bewahrt den Namen des Handles, Typ und Inhalt auf.
 *
 * @package rte
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 */
class TempHandle extends PoolObject
{
    protected string $parsedContent = '';

    /**
     * TempHandle ist das Grundgeruest aller Handles der Rapid Template Engine. Ein Handle wird in der Template Engine Sprache wie folgt definiert:
     * <!-- ANWEISUNG HANDLE -->INHALT<!-- END HANDLE -->
     * Um den Handle besser zu identifizieren verwendet man eigene Prefixe, z.B. BLOCK_HANDLE, FILE_HANDLE, SCRIPT_HANDLE...
     * Die Basisklasse TempHandle haelt Informationen zum Handle Typ, Content und vervollstaendigten Inhalt bereit!
     *
     * @param string $type type of handle (BLOCK, FILE)
     * @param string $handle unique name for handle
     */
    public function __construct(
        protected readonly string $handle,
        protected readonly string $type,
        protected string $content = '',
    ) {}

    /**
     * Liefert Handle
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * Liefert den Handle Typ
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function getPlaceholder(): string
    {
        return "[$this->handle]";
    }

    /**
     * Speichert Inhalt im Handle ab.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Liefert den gespeicherten Inhalt eines Handles.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Funktion dient zum Zwischenspeichern von vervollstaendigten Inhalt.
     */
    public function setParsedContent(string $content): void
    {
        $this->parsedContent = $content;
    }

    /**
     * Gibt den gespeicherten geparsten, vervollstaendigten Inhalt zurueck.
     */
    public function getParsedContent(): string
    {
        return $this->parsedContent;
    }

    /**
     * Leert den gespeicherten geparsten Inhalt.
     */
    public function clearParsedContent(): void
    {
        $this->parsedContent = '';
    }

    /**
     * Leert auch den geladenen Content inkl. geparsten Content
     */
    public function clear(): void
    {
        $this->content = '';
        $this->parsedContent = '';
    }
}

/* --------------------- */
##### TempCoreHandle ######
/* --------------------- */

/**
 * TempCoreHandle
 * TempCoreHandle ist der Kern aller Template Elemente. Er kuemmert sich um die Erstellung neuer Objekte und kann Inhalt parsen.
 * Im Container merkt er sich alle erstellten Objekte. Neue TempX Elemente werden von TempCoreHandle abgeleitet (siehe TempFile, TempBlock)!
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempCoreHandle extends TempHandle
{
    /**
     * @var array container for placeholders (key, value)
     */
    protected array $varList = [];

    /**
     * @var array<TempBlock> container for blocks (key, content of block)
     */
    protected array $blockList = [];

    /**
     * @var array<TempFile|TempSimple> container for files (key, content of file)
     */
    protected array $fileList = [];

    private string $varStart = TEMP_VAR_START;

    private string $varEnd = TEMP_VAR_END;

    private ?TempCoreHandle $inheritFrom = null;

    /**
     * Konstruktor: benoetigt Handle-Typ und Verzeichnis zum Template.
     * Array fuer Variablen Container und Block Container werden angelegt.
     *
     * @param string $type Handle-Typ set of (BLOCK, FILE)
     * @param string $directory Directory to the template
     */
    public function __construct(string $handle, string $type, protected string $directory, protected string $charset, protected readonly array $hooks = [])
    {
        parent::__construct($handle, $type);
    }

    /**
     * Die Funktion createBlock legt einen neuen Block (Objekt vom Typ TempBlock) an.
     *
     * @access public
     * @param string $handle Name des Handles
     * @param string $dir Verzeichnis zum Template
     * @param string $content Inhalt des Blocks
     * @return TempBlock TempBlock
     * @see TempBlock
     */
    public function createBlock(string $handle, string $dir, string $content, string $charset, array $hooks): TempBlock
    {
        $obj = new TempBlock($handle, $dir, $content, $charset, $hooks);
        $this->blockList[$handle] = $obj;
        return $obj;
    }

    /**
     * Die Funktion createFile legt eine neue Datei (Objekt vom Typ TempFile) an.
     *
     * @access public
     * @param string $handle Name des Handles
     * @param string $dir Verzeichnis zum Template
     * @param string $filename Dateiname (des Templates)
     * @see TempFile
     */
    public function createFile(string $handle, string $dir, string $filename, string $charset): TempFile
    {
        $obj = new TempFile($handle, $dir, $filename, $charset);
        $this->fileList[$handle] = $obj;
        return $obj;
    }

    /**
     * Legt ein neues Script (Objekt vom Typ TempScript) an.
     * Hinweis: TempScript arbeitet ebenfalls mit dem Handle-Typ FILE
     *
     * @access public
     * @param string $handle Name des Handles
     * @param string $dir Verzeichnis zum Template
     * @param string $filename Dateiname (des Scripts)
     * @see TempScript
     */
    public function createScript(string $handle, string $dir, string $filename, string $charset): TempScript
    {
        $obj = new TempScript($handle, $dir, $filename, $charset);
        $this->fileList[$handle] = $obj;
        return $obj;
    }

    /**
     * Convert special characters to HTML Entities
     *
     * @param int $convert convert method
     */
    private function convertToHTML(mixed $value, int $convert): string
    {
        return match ($convert) {
            Template::CONVERT_HTMLSPECIALCHARS => htmlspecialchars((string)$value, ENT_QUOTES, $this->charset),
            Template::CONVERT_HTMLENTITIES => htmlentities((string)$value, ENT_QUOTES, $this->charset),
            default => (string)$value,
        };
    }

    /**
     * Fills placeholder with value
     *
     * @note should no longer be used for arrays, instead use @see Template::setVars
     * @param string|array $name name of the variable (placeholder in the template)
     * @param mixed $value (zuzuweisender) Wert der Variable
     */
    public function setVar(string|array $name, mixed $value = '', int $convert = Template::CONVERT_NONE): static
    {
        if (is_array($name)) {
            // backward compatibility
            return $this->setVars($name, $convert);
        }
        $this->varList[$name] = $this->convertToHTML($value, $convert);
        return $this;
    }

    /**
     * Fills multiple placeholders with values
     */
    public function setVars(array $vars, int $convert = Template::CONVERT_NONE): static
    {
        foreach ($vars as $key => $value) {
            $this->setVar($key, $value, $convert);
        }
        return $this;
    }

    public function getVars(): array
    {
        return $this->varList;
    }

    /**
     * Beim Setzen des Inhalts fuer einen Handle, wird dieser Inhalt automatisch nach Bloecken, Includes (Files) untersucht.
     */
    public function setContent(string $content, bool $scanForPattern = true): static
    {
        parent::setContent($content);

        if ($scanForPattern) {
            $this->findPattern($this->content);
        }
        return $this;
    }

    /**
     * Liefert das Verzeichnis.
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }

    /**
     * Gibt das Block Objekt mit dem uebergebenen Namen des Handles $handle zurueck.
     * Falls er ueberhaupt existiert! Ansonsten wird NULL zurueck gegeben.
     *
     * @param string $handle Name des Handles
     * @return TempBlock|null Instanz von TempBlock oder NULL
     */
    public function getTempBlock(string $handle): ?TempBlock
    {
        if (isset($this->blockList[$handle])) {
            return $this->blockList[$handle];
        }

        foreach ($this->blockList as $block) {
            if ($nestedBlock = $block->getTempBlock($handle)) {
                return $nestedBlock;
            }
        }
        return null;
    }

    /**
     * Eine der Hauptfunktionen in der Rapid Template Engine!
     * Objekt Elemente haben im Template das Muster <!-- FUNKTION HANDLENAME>INHALT<!-- END HANDLENAME --> .
     * Ein Block kann in diesem Fall auch ein Include eines weiteren Templates oder Scripts sein
     * (Dies gilt aber nur fuer die Blockdefinition innerhalb des Templates).
     * Findet er einen neuen Block, wird entsprechend ein neues Objekt mit dem Inhalt des gefundenen Blocks angelegt.
     *
     * @param string $templateContent Inhalt, welcher nach Template Elementen abgesucht werden soll
     * @return int Number of replaced patterns
     */
    protected function findPattern(string $templateContent): int
    {
        // Matches Blocks like <!-- XXX handle -->freeform-text<!-- END handle -->
        $pattern = '/<!-- ([A-Z]{2,}) ([^>]+) -->(.*?)<!-- END \2 -->/s';
        $count = 0;
        $content = preg_replace_callback($pattern, function (array $match) use (&$count) {
            //the entire Comment Block
            $fullMatchText = $match[0];
            //the XXX Tag part
            $kind = $match[1];
            //the handle part
            $handle = $match[2];
            //the freeform-text part
            $tagContent = $match[3];

            $value = match ($kind) {
                TEMP_BLOCK_IDENT => $this->createBlock($handle, $this->getDirectory(), $tagContent, $this->charset, $this->hooks)->getPlaceholder(),
                TEMP_INCLUDE_IDENT => $this->createFile($handle, $this->getDirectory(), $tagContent, $this->charset)->getPlaceholder(),
                TEMP_INCLUDESCRIPT_IDENT => $this->createScript($handle, $this->getDirectory(), $tagContent, $this->charset)->getPlaceholder(),
                TEMP_SESSION_IDENT => $_SESSION[$tagContent] ?? '',
                TEMP_TRANSL_IDENT => Template::getTranslator()?->translateTag($handle, $tagContent) ?? $tagContent,
                default => IS_DEVELOP ? "Unknown block $kind" : '',
            };

            if ($value !== $fullMatchText) {
                $count++;
                return $value;
            }
            return $fullMatchText;
        }, $templateContent);
        checkRegExOutcome($pattern, $templateContent);
        $content ??= $templateContent;
        $this->content = $content;
        return $count;
    }

    public function getBlockList(): array
    {
        return $this->blockList;
    }

    /**
     * Ersetzt alle Bloecke und Variablen mit den fertigen Inhalten.
     *
     * @param boolean $returnContent Bei true wird der geparste Inhalt an den Aufrufer zurueck gegeben, bei false gespeichert.
     * @return string Geparster Inhalt
     */
    public function parse(bool $returnContent = false, bool $clearParsedContent = true): string
    {
        $content = $this->content;

        $varList = $this->varList;
        if ($this->inheritFrom) {
            $varList = array_merge($this->inheritFrom->getVars(), $varList);
        }
        if (count($varList)) {
            $varPairs = [];
            foreach ($varList as $key => $val) {
                $varPairs["$this->varStart$key$this->varEnd"] = $val;
            }

            do {
                $oldContent = $content;
                $content = strtr($content, $varPairs);
            } while ($oldContent !== $content);
        }

        $replace_pairs = [];
        if (str_contains($content, 'TRANSL')) Template::getTranslator()?->translateWithRegEx($content, Translator::CURLY_TAG_REGEX, $replace_pairs);
        foreach ($this->blockList as $block) {
            if ($block->allowParse()) {
                $block->parse();
                $block->setAllowParse(false);
            }
            $replace_pairs[$block->getPlaceholder()] = $block->getParsedContent();
            if ($clearParsedContent) $block->clearParsedContent();
        }

        foreach ($this->fileList as $file) {
            $file->parse();
            $replace_pairs[$file->getPlaceholder()] = $file->getParsedContent();
            if ($clearParsedContent) $file->clearParsedContent();
        }

        if ($replace_pairs) $content = strtr($content, $replace_pairs);

        if (!$returnContent) {
            $this->parsedContent = $content;
            return '';
        }
        return $content;
    }

    /**
     * set parentheses for the variable placeholders
     */
    public function setParentheses(string $start, string $end): void
    {
        $this->varStart = $start;
        $this->varEnd = $end;
    }

    /**
     * Sucht nach einem File mit dem uebergebenen Namen des Handles $handle.
     *
     * @param string $handle Name des Handles
     * @return TempFile|null Instanz von TempFile oder NULL
     * @see TempFile
     */
    public function findFile(string $handle): ?TempFile
    {
        foreach ($this->fileList as $fileHandle => $file) {
            if ($fileHandle === $handle && $file->getType() === 'FILE') {
                return $file;
            }

            if ($file = $file->findFile($handle)) {
                return $file;
            }
        }
        return null;
    }

    public function inheritVariablesFrom(TempCoreHandle|null $coreHandle): void
    {
        $this->inheritFrom = $coreHandle;
    }
}

/* --------------------- */
######## TempBlock ########
/* --------------------- */

/**
 * TempBlock ein Template Element konzipiert fuer dynamische Inhalte. Im Template definiert man einen Block wie folgt:
 * <!-- BEGIN BLOCK_MYNAME --> ...Inhalt... <!-- END BLOCK_MYNAME -->
 * Ob der Block im Endresultat erscheint oder wie oft wiederholt wird, bestimmt der Programmierer.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempBlock extends TempCoreHandle
{
    /**
     * @var bool content parseable? Am I allowed to parse the content?
     */
    private bool $allowParse = false;

    /**
     * @param string $handle Name des Handles
     * @param string $directory Verzeichnis zum Template
     * @param string $content Inhalt des Blocks
     */
    function __construct(string $handle, string $directory, string $content, string $charset, array $hooks = [])
    {
        parent::__construct($handle, 'BLOCK', $directory, $charset, $hooks);
        $this->setContent($content);
    }

    /**
     * Determines whether parsing is allowed.
     */
    public function allowParse(): bool
    {
        return $this->allowParse;
    }

    /**
     * Setzt den Status, ob geparst werden darf.
     */
    public function setAllowParse(bool $bool): void
    {
        $this->allowParse = $bool;
    }

    /**
     * Parses the content and optionally returns it.
     *
     * @param bool $returnContent Determines whether to return the parsed content or not.
     * @param bool $clearParsedContent Indicates if previously parsed content should be cleared before parsing.
     * @return string Returns the parsed content if $returnContent is true, otherwise an empty string.
     */
    public function parse(bool $returnContent = false, bool $clearParsedContent = true): string
    {
        $content = parent::parse($this->allowParse, $clearParsedContent);
        // run hooks
        $hooks = $this->hooks[$this->type] ?? [];
        foreach ($hooks as $hook) {
            $content = $hook($this, $content);
        }
        if ($returnContent) {
            return $content;
        }
        $this->parsedContent .= $content;
        return '';
    }
}

/* --------------------- */
######## TempFile #########
/* --------------------- */

/**
 * TempFile ein Template Element konzipiert fuer weitere Template Dateien. Im Template definiert man eine neues Template wie folgt:
 * <!-- INCLUDE MYNAME --> Pfad + Dateiname des Html Templates <!-- END MYNAME -->
 * Inkludierte Dateien werden genauso behandelt wie andere Html Templates. Man kann darin Variablen, Bloecke und weitere Html Templates definieren.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempFile extends TempCoreHandle
{
    private string $filename;

    /**
     * Calls the function to load the file.
     */
    public function __construct(string $handle, string $directory, string $filename, string $charset, array $hooks = [])
    {
        parent::__construct($handle, 'FILE', $directory, $charset, $hooks);
        $this->loadFile($filename);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * loads the template file
     *
     * @throws UnexpectedValueException|FileNotFoundException|RuntimeException
     */
    private function loadFile(string $filename): void
    {
        if (!$filename) throw new \pool\classes\Exception\UnexpectedValueException('No template file given.');
        $this->filename = $filename;
        $filePath = buildFilePath($this->getDirectory(), $filename);
        $weblication = Weblication::getInstance();
        $fileExists = file_exists($filePath);

        if (!$fileExists) {
            //trying parent directory to compensate for translated templates including templates until Template engine gets fixed
            $filePath = buildFilePath($this->getDirectory(), '..', $filename);
            $fileExists = file_exists($filePath);
            if ($fileExists && Template::isCacheTranslations())
                //Create Translated file and put it in the language folder
                $filePath = Template::attemptFileTranslation($filePath, $weblication->getLanguage());
        }

        $fileTime = $fileExists ? @filemtime($filePath) : false;
        if ($fileExists && $fileTime !== false) {
            $content = $weblication->getCachedItem("$fileTime:$filePath", Weblication::CACHE_FILE);
            if ($content) {
                $this->setContent($content);
                return;
            }
        }

        // fopen is faster than file_get_contents
        if (!$fileExists || false === ($fh = @fopen($filePath, 'rb'))) {
            throw new FileNotFoundException("Template file $filePath not found.");
        }
        $content = stream_get_contents($fh);
        fclose($fh);
        if ($content === false) {
            throw new \pool\classes\Exception\RuntimeException("Template file $filePath could not be read.");
        }
        $this->setContent($content);
        /** @noinspection PhpUndefinedVariableInspection */
        if ($fileTime !== false) {
            $weblication->cacheItem("$fileTime:$filePath", $content, Weblication::CACHE_FILE);
        }
    }

    /**
     * Sucht nach allen inkludierten TempFiles und gibt die Instanzen in einem Array zurueck (Rekursion).
     *
     * @return array<TempFile> list of TempFile
     * @see TempFile
     */
    public function getFiles(): array
    {
        $files = [];
        foreach ($this->fileList as $tempFile) {
            $files[] = $tempFile;
            $files = array_merge($files, $tempFile->getFiles());
        }
        return $files;
    }
}

/* --------------------- */
######## TempSimple #######
/* --------------------- */

class TempSimple extends TempCoreHandle
{
    public function __construct(string $handle, string $content, string $charset)
    {
        parent::__construct($handle, 'FILE', '', $charset);
        $this->setContent($content);
    }
}

/* --------------------- */
####### TempScript ########
/* --------------------- */

/**
 * TempScript
 * TempScript ein Template Element konzipiert fuer weitere Php Html Template Dateien. Im Template definiert man eine neues Script wie folgt:
 * <!-- INCLUDESCRIPT MYNAME --> Pfad + Dateiname des pHtml Templates <!-- END MYNAME -->
 * Achtung: PHP Inhalt wird ausgefuehrt! Dadurch kann die Sicherheit gefaehrdet werden.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempScript extends TempFile
{
    /**
     * Parst das Script. Dabei wird enthaltener PHP Code ausgefuehrt.
     */
    public function parse(bool $returnContent = false, bool $clearParsedContent = true): string
    {
        $content = parent::parse($returnContent, $clearParsedContent);
        if ($returnContent) {
            return $content;
        }

        $this->parsedContent = $this->executeIsolatedPHP($this->parsedContent);
        return '';
    }

    protected function executeIsolatedPHP(string $code): string
    {
        ob_start();
        try {
            (static function () use ($code) {
                eval("?>$code<?php ");
            })();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error in template script: {$e->getMessage()}", 0, $e);
        }
        return ob_get_clean();
    }
}

/* --------------------- */
######## Template #########
# Rapid Template Engine   #
# ... arise ...  :)       #
/* --------------------- */

/**
 * Template
 * Die Template Klasse! Sie liest die gewuenschten Templates ein, weist dynamische Bloecke und Variablen zu und stosst
 * letzendlich die Uebersetzung, den Parse Vorgang, an.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access public
 **/
class Template extends PoolObject
{
    private static bool $cacheTranslations = true;

    private static ?Translator $translator = null;

    //@var string Verzeichnis zu den Templates
    private string $dir;

    //@var string Aktiv verwendeter Handle (wohin gehen die Variablenzuweisungen)
    private string $activeHandle = '';

    /**
     * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
     *
     * @var null|TempFile|TempSimple
     */
    public null|TempFile|TempSimple $activeFile = null;

    /**
     * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
     *
     * @var null|TempBlock
     */
    public ?TempBlock $ActiveBlock = null;

    /**
     * @var array<TempFile|TempSimple> Template Container (hier sind nur die Files enthalten, die �ber das Template Objekt gesetzt werden)
     */
    protected array $FileList = [];

    private string $varStart = TEMP_VAR_START;

    private string $varEnd = TEMP_VAR_END;

    public const int CONVERT_NONE = 0;
    public const int CONVERT_HTMLSPECIALCHARS = 1;
    public const int CONVERT_HTMLENTITIES = 2;

    private string $charset = 'UTF-8';

    private array $globalHooks = ['BLOCK' => []];

    /**
     * @param string $dir Verzeichnis zu den Templates (Standardwert./)
     */
    public function __construct(string $dir = './')
    {
        $this->setDirectory($dir);
    }

    public static function getTranslator(): ?Translator
    {
        return static::$translator;
    }

    public static function setTranslator(Translator $translator): void
    {
        static::$translator = $translator;
    }

    public static function attemptFileTranslation(string $file, string $language): string
    {
        try {
            return self::getTranslator()->translateFile($file, $language);
        } catch (Exception) {
            return $file;
        }
    }

    public static function isCacheTranslations(): bool
    {
        return static::getTranslator() !== null ? static::$cacheTranslations : false;
    }

    public static function setCacheTranslations(bool $cacheTranslations): void
    {
        self::$cacheTranslations = $cacheTranslations;
    }

    public function setCharset(string $charset): void
    {
        $this->charset = $charset;
    }

    /**
     * Set the directory to the templates
     */
    public function setDirectory(string $dir): void
    {
        $dir === '' && $dir = './';
        $dir = addEndingSlash($dir);
        if (!is_dir($dir)) {
            throw new \pool\classes\Exception\UnexpectedValueException(__CLASS__." could not find the directory $dir.");
        }
        $this->dir = $dir;
    }

    /**
     * Liefert das Verzeichnis zu den Templates zurueck
     */
    public function getDirectory(): string
    {
        return $this->dir;
    }

    /**
     * Setzt die Templates. Der Handle-Name dient der Identifikation der Datei.
     *
     * @param string $handle Handle-Name; es kann auch ein Array mit Schluesselname und Wert (= Dateinamen) uebergeben werden.
     * @param string $filename Dateiname
     */
    public function setFile(string $handle, string $filename = ''): static
    {
        $this->addFile($handle, $filename);
        return $this;
    }

    /**
     * Setzt die Template Dateien samt Pfad. Der Pfad wird automatisch extrahiert. Der Handle-Name dient der Identifikation der Datei.
     *
     * @param string $handle Handle-Name (array erlaubt)
     * @param string $filename Pfad und Dateiname (Template)
     */
    public function setFilePath(string $handle, string $filename = ''): Template
    {
        $this->setDirectory(dirname($filename));
        $this->addFile($handle, basename($filename));
        return $this;
    }

    /**
     * Set content directly
     */
    public function setContent(string $handle, string $content): static
    {
        $TempSimple = new TempSimple($handle, $content, $this->charset);
        $this->FileList[$handle] = $TempSimple;

        if ($this->activeHandle === '') {
            $this->useFile($handle);
        }
        return $this;
    }

    /**
     * Set the parentheses for the placeholders
     */
    public function setParentheses(string $varStart, string $varEnd): void
    {
        $this->varStart = $varStart;
        $this->varEnd = $varEnd;

        $this->activeFile?->setParentheses($varStart, $varEnd);
    }

    /**
     * Fuegt ein Template zum File Container hinzu.
     */
    private function addFile(string $handle, string $file): static
    {
        $TempFile = new TempFile($handle, $this->getDirectory(), $file, $this->charset, $this->globalHooks);
        $this->FileList[$handle] = $TempFile;

        // added 02.05.2006 Alex M.
        $this->setParentheses($this->varStart, $this->varEnd);

        if ($this->activeHandle === '') {
            $this->useFile($handle);
        }
        return $this;
    }

    /**
     * Die Funktion sagt der Template Engine, dass die nachfolgenden Variablenzuweisungen auf ein anderes Html Template fallen.
     *
     * @param string $handle Name des (File-) Handles
     */
    public function useFile(string $handle): static
    {
        if (array_key_exists($handle, $this->FileList)) {
            if ($handle !== $this->activeHandle) {
                $this->activeHandle = $handle;
                $this->activeFile = $this->FileList[$handle];
                unset($this->ActiveBlock);
            }
        } else {
            foreach ($this->FileList as $TempFile) {
                if ($obj = $TempFile->findFile($handle)) {
                    $this->activeHandle = $handle;
                    $this->activeFile = $obj;
                    unset($this->ActiveBlock);
                    break;
                }
            }
        }
        return $this;
    }

    /**
     * Liefert ein Array mit allen TempFile Objekten (auch TempScript).
     *
     * @return array<int, TempFile> Liste aus TempFile Objekten
     */
    public function getFiles(bool $recursive = true): array
    {
        $files = [];

        $keys = array_keys($this->FileList);
        if (!$recursive) return $keys;
        foreach ($keys as $handle) {
            $TempFile = $this->FileList[$handle];
            if ($TempFile instanceof TempSimple) {
                continue;
            }
            $files[] = $TempFile;

            // Suche innerhalb des TempFile's nach weiteren TemplateFiles
            $more_files = $TempFile->getFiles();
            if (count($more_files)) {
                $files = array_merge($files, $more_files);
            }
            unset($more_files);
        }

        return $files;
    }

    public function countFileList(): int
    {
        return count($this->FileList);
    }

    public function getFileList(): array
    {
        return $this->FileList;
    }

    /**
     * Add a global hook between parsing and returning the content (currently only for blocks)
     */
    public function addGlobalHook(callable $hook): void
    {
        $this->globalHooks['BLOCK'][] = $hook;
    }

    public function getGlobalHooks(): array
    {
        return $this->globalHooks;
    }

    /**
     * Anweisung, dass ein neuer Block folgt, dem die n�chsten Variablen zugewiesen werden.
     *
     * @param string $handle Handle-Name
     */
    public function newBlock(string $handle, bool $inheritVariables = false): ?TempBlock
    {
        if ($this->activeFile instanceof TempFile || $this->activeFile instanceof TempSimple) {
            if ((!isset($this->ActiveBlock)) || ($this->ActiveBlock->getHandle() !== $handle)) {
                $this->ActiveBlock = $this->activeFile->getTempBlock($handle);
            }

            if ($this->ActiveBlock) {
                // added 2.5.06 Alex M.
                $this->ActiveBlock->setParentheses($this->varStart, $this->varEnd);

                if ($inheritVariables) {
                    $this->ActiveBlock->inheritVariablesFrom($this->activeFile);
                }

                if ($this->ActiveBlock->allowParse()) {
                    $this->ActiveBlock->parse();
                } else {
                    $this->ActiveBlock->setAllowParse(true);
                }
                return $this->ActiveBlock;
            } else
                unset($this->ActiveBlock);
        }
        return null;
    }

    public function parsePendingBlocks(string $fileHandle = ''): static
    {
        if ($fileHandle && $this->activeHandle !== $fileHandle) {
            $this->useFile($fileHandle);
        }
        if ($this->activeFile instanceof TempFile) {
            foreach ($this->activeFile->getBlockList() as $block) {
                $block->setParentheses($this->varStart, $this->varEnd);
                if ($block->allowParse()) {
                    $block->parse();
                    $block->setAllowParse(false);
                }
            }
        }
        return $this;
    }

    /**
     * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::backToFile($filehandle)).
     * Verwendet Template::leaveBlock() und Template::useFile().
     */
    public function backToFile(string $fileHandle = ''): static
    {
        $this->leaveBlock();
        if ($fileHandle !== '') {
            $this->useFile($fileHandle);
        }
        return $this;
    }

    /**
     * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::leaveBlock())
     */
    public function leaveBlock(): static
    {
        // Referenz zum aktiven Block aufheben
        unset($this->ActiveBlock);
        return $this;
    }

    /**
     * Weist die Template Engine an, als nächstes einen anderen BLOCK zu verwenden.
     *
     * @param string $blockHandle name of block
     */
    public function useBlock(string $blockHandle): ?TempBlock
    {
        if ($this->activeFile) {
            $ActiveBlock = $this->activeFile->getTempBlock($blockHandle);
            if ($ActiveBlock) {
                $this->ActiveBlock = $ActiveBlock;
                return $this->ActiveBlock;
            }
        }
        return null;
    }

    /**
     * Fill placeholder with value
     *
     * @param string|array $name name of placeholder
     * @param mixed $value value for placeholder
     */
    public function setVar(string|array $name, mixed $value = '', int $convert = Template::CONVERT_NONE): static
    {
        if (isset($this->ActiveBlock)) {
            $this->ActiveBlock->setVar($name, $value, $convert);
        } elseif (isset($this->activeFile)) {
            $this->activeFile->setVar($name, $value, $convert);
        } else {
            throw new \pool\classes\Exception\RuntimeException("Cannot assign Variable $name. There is no file or block associated.");
        }
        return $this;
    }

    /**
     * Fill multiple placeholders with values
     */
    public function setVars(array $vars, int $encoding = Template::CONVERT_NONE): static
    {
        if (isset($this->ActiveBlock)) {
            $ActiveBlock = $this->ActiveBlock;
            $ActiveBlock->setVars($vars, $encoding);
        } elseif (isset($this->activeFile)) {
            $ActiveFile = $this->activeFile;
            $ActiveFile->setVars($vars, $encoding);
        } else {
            throw new \pool\classes\Exception\RuntimeException("Cannot assign Variables. There is no file or block associated.");
        }
        return $this;
    }

    /**
     * Erstellt automatisch dynamische Bloecke mit den uebergebenen Datensaetzen.
     * Das Array muss wie folgt aufgebaut werden: $array[$laufender_record_index][$varname] = $value !
     *
     * @param string $blockHandle Handle-Name des Blocks
     */
    public function assignRecordset(string $blockHandle, array $recordset = []): static
    {
        foreach ($recordset as $record) {
            $this->newBlock($blockHandle)?->setVars($record);
        }
        $this->leaveBlock();
        return $this;
    }

    /**
     * Alias for Template::assignRecordset()
     *
     * @param string $blockHandle Handle-Name des Blocks
     * @see Template::assignRecordset()
     */
    public function setRecordset(string $blockHandle, array $recordSet = []): static
    {
        return $this->assignRecordset($blockHandle, $recordSet);
    }

    /**
     * Die Prozedur veranlasst, dass alle Dateien (Template Elemente) rekursiv geparst werden.
     *
     * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet)
     */
    public function parse(string $handle = ''): self
    {
        if ($handle !== '') {
            $this->useFile($handle);
        }
        $this->activeFile?->parse();
        return $this;
    }

    /**
     * Liefert den Inhalt aller geparsten Dateien, Includes, Variablen und Bloecken!
     *
     * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet!)
     * @return string Inhalt
     */
    public function getContent(string $handle = ''): string
    {
        if ($handle)
            $this->useFile($handle);
        if (!$this->activeFile)
            return '';
        $parsedContent = $this->activeFile->getParsedContent();
        if ($this->activeFile instanceof TempFile && constant("IS_DEVELOP")) {
            $name = $this->activeFile->getFilename();
            if (str_ends_with($name, '.html')) {
                $parsedContent = "<!-- begin $name -->$parsedContent<!-- end $name -->";
            }
        }
        return $parsedContent;
    }

    /**
     * Leert den Buffer (ParsedContent)
     *
     * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet!)
     **/
    public function clear(string $handle = ''): void
    {
        if ($handle !== '') {
            $this->useFile($handle);
        }

        if ($this->activeFile instanceof TempFile) {
            $this->activeFile->clearParsedContent();
        }
    }

    /**
     * Setzt alle Werte zurueck (Loescht alle Files aus dem Buffer).
     */
    public function reset(): void
    {
        $this->activeHandle = '';
        unset($this->activeFile, $this->ActiveBlock);
        $this->FileList = [];
    }
}
