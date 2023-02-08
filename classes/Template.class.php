<?php
/**
 * -= Rapid Template Engine (RTE) =-
 *
 * Template.class.php
 *
 * Rapid Template Engine (RTE) ist eine Template-Engine für PHP. Genauer gesagt erlaubt es die einfache Trennung von
 * Applikations-Logik und Design/Ausgabe. Dies ist vor allem wuenschenswert, wenn der Applikationsentwickler nicht die
 * selbe Person ist wie der Designer. Nehmen wir zum Beispiel eine Webseite die Zeitungsartikel ausgibt.
 * Der Titel, die Einfuehrung, der Author und der Inhalt selbst enthalten keine Informationen darueber wie sie dargestellt
 * werden sollen. Also werden sie von der Applikation an RTE uebergeben, damit der Designer in den Templates mit einer
 * Kombination von HTML- und Template-Tags die Ausgabe (Tabellen, Hintergrundfarben, Schriftgroessen, Stylesheets, etc.)
 * gestalten kann. Falls nun die Applikation eines Tages angepasst werden muss, ist dies fuer den Designer nicht von
 * Belang, da die Inhalte immer noch genau gleich uebergeben werden. Genauso kann der Designer die Ausgabe der Daten beliebig
 * veraendern, ohne dass eine Aenderung der Applikation vorgenommen werden muss. Somit koennen der Programmierer die
 * Applikations-Logik und der Designer die Ausgabe frei anpassen, ohne sich dabei in die Quere zu kommen.
 *
 * Features:
 * Schnelligkeit
 * Dynamische Bloecke
 * Beliebige Template-Quellen
 * Ermoeglicht die direkte Einbettung von PHP-Code (Obwohl es weder benoetigt noch empfohlen wird, da die Engine einfach erweiterbar ist).
 *
 *
 * @date $Date: 2007/03/13 08:52:50 $
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-12
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\translator\Translator;

const TEMP_VAR_START = '{';
const TEMP_VAR_END = '}';

// Template Objekt Elemente Identifizierung
const TEMP_BLOCK_IDENT = 'BLOCK';
const TEMP_INCLUDE_IDENT = 'INCLUDE';
const TEMP_INCLUDESCRIPT_IDENT = 'INCLUDESCRIPT';
const TEMP_TRANSL_IDENT = 'TRANSL';
const TEMP_SESSION_IDENT = '';

/**
 * TempHandle
 *
 * TempHandle ist die abstrakte Basisklasse fuer alle eingebetteten Template Elemente (oder auch Platzhalter).
 * Die Klasse bewahrt den Name des Handles, Typ und Inhalt auf.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempHandle extends PoolObject
{
    //@var string Typ des Handles
    //@access public
    var $Type;

    //@var string Name des Handles (muss unique sein)
    //@access public
    var $Handle;

    //@var string Inhalt des Handles
    //@access public
    protected string $content = '';

    //@var string Geparster, vervollstaendigter Inhalt
    //@access public
    var $ParsedContent = '';

    /**
     * Konstruktor der Klasse TempHandle (lang TemplateHandle)
     *
     * TempHandle ist das Grundgeruest aller Handles der Rapid Template Engine.
     * Ein Handle wird in der Template Engine Sprache wie folgt definiert:
     * <!-- ANWEISUNG HANDLE -->INHALT<!-- END HANDLE -->
     * Um den Handle besser zu identifizieren verwendet man eigene Prefixe, z.B. BLOCK_HANDLE, FILE_HANDLE, SCRIPT_HANDLE...
     *
     * Die Basisklasse TempHandle haelt Informationen zum Handle Typ, Content und vervollstaendigten Inhalt bereit!
     *
     * @param string $type Typ set of (FILE, BLOCK)
     */
    function __construct(string $type)
    {
        $this->setType($type);
    }

    /**
     * Setzt eindeutigen Handle.
     *
     * @param string $handle Eindeutiger Name fuer Handle
     */
    public function setHandle(string $handle)
    {
        $this->Handle = $handle;
    }

    /**
     * Liefert Handle
     *
     * @return string Handle
     */
    public function getHandle(): string
    {
        return $this->Handle;
    }

    /**
     * Setzt Handle Typ. Anhand des Typs unterscheidet man derzeit zwischen Block- und File Handles.
     *
     * @param string $type Typ set of (BLOCK, FILE)
     */
    public function setType(string $type)
    {
        $this->Type = $type;
    }

    /**
     * Liefert den Handle Typ
     *
     * @return string Typ set of (BLOCK, FILE)
     */
    public function getType(): string
    {
        return $this->Type;
    }

    /**
     * Speichert Inhalt im Handle ab.
     *
     * @param string $content Inhalt (z.B. eines BLOCK's, FILE's)
     */
    public function setContent(string $content)
    {
        $this->content = $content;
    }

    /**
     * Liefert den gespeicherten Inhalt eines Handles.
     *
     * @return string Inhalt dieses Handles
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Funktion dient zum Zwischenspeichern von vervollstaendigten Inhalt.
     *
     * @param string $content Geparster, vervollstaendigter Inhalt
     */
    public function setParsedContent(string $content)
    {
        $this->ParsedContent = $content;
    }

    /**
     * Gibt den gespeicherten geparsten, vervollstaendigten Inhalt zurueck.
     *
     * @return string Geparster Inhalt
     */
    public function getParsedContent(): string
    {
        return $this->ParsedContent;
    }

    /**
     * Leert den gespeicherten geparsten Inhalt.
     */
    public function clearParsedContent()
    {
        $this->ParsedContent = '';
    }

    /**
     * Leert auch den geladenen Content inkl. geparsten Content
     */
    public function clear()
    {
        $this->content = '';
        $this->ParsedContent = '';
    }
}

/* --------------------- */
##### TempCoreHandle ######
/* --------------------- */

/**
 * TempCoreHandle
 *
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
    protected array $VarList = [];

    /**
     * @var array(TempBlock) container for blocks (key, content of block)
     */
    protected array $BlockList = [];

    /**
     * @var array(TempCoreHandle) container for files (key, content of file)
     */
    protected array $fileList = [];

    /**
     * @var string directory to the templates
     */
    protected string $Directory;

    private string $varStart = TEMP_VAR_START;

    private string $varEnd = TEMP_VAR_END;

    protected string $charset = 'UTF-8';

    /**
     * Konstruktor: benoetigt Handle-Typ und Verzeichnis zum Template.
     * Array fuer Variablen Container und Block Container werden angelegt.
     *
     * @param string $type Handle-Typ set of (BLOCK, FILE)
     * @param string $directory Verzeichnis zum Template
     */
    function __construct(string $type, string $directory, string $charset = 'UTF-8')
    {
        $this->charset = $charset;
        parent::__construct($type);

        $this->setDirectory($directory);
        $this->BlockList = [];
        $this->fileList = [];
        $this->VarList = [];
    }

    /**
     * Die Funktion createBlock legt einen neuen Block (Objekt vom Typ TempBlock) an.
     *
     * @access public
     * @param string $handle Name des Handles
     * @param string $dir Verzeichnis zum Template
     * @param string $content Inhalt des Blocks
     * @param string $charset
     * @return TempBlock TempBlock
     * @see TempBlock
     */
    function createBlock(string $handle, string $dir, string $content, string $charset): TempBlock
    {
        $obj = new TempBlock($handle, $dir, $content, $charset);
        $this->BlockList[$handle] = $obj;
        return $obj;
    }

    /**
     * Die Funktion createFile legt eine neue Datei (Objekt vom Typ TempFile) an.
     *
     * @access public
     * @param string $handle Name des Handles
     * @param string $dir Verzeichnis zum Template
     * @param string $filename Dateiname (des Templates)
     * @param string $charset
     * @return TempFile
     * @see TempFile
     */
    function createFile(string $handle, string $dir, string $filename, string $charset): TempFile
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
     * @param string $charset
     * @return TempScript
     * @see TempScript
     */
    function createScript(string $handle, string $dir, string $filename, string $charset): TempScript
    {
        $obj = new TempScript($handle, $dir, $filename, $charset);
        $this->fileList[$handle] = $obj;
        return $obj;
    }

    /**
     * Convert special characters to HTML Entities
     *
     * @param $value
     * @param int $convert converting method
     * @return string|void
     */
    private function convertToHTML($value, int $convert)
    {
        if($convert == Template::CONVERT_NONE) {
            return $value;
        }
        if($convert == Template::CONVERT_HTMLSPECIALCHARS) {
            return htmlspecialchars($value, ENT_QUOTES, $this->charset);
        }
        if($convert == Template::CONVERT_HTMLENTITIES) {
            return htmlentities($value, ENT_QUOTES, $this->charset);
        }
    }

    /**
     * Fills placeholder with value
     *
     * @note should no longer be used for arrays, instead use @see Template::setVars
     * @param string|array $name name of the variable (placeholder in the template)
     * @param mixed $value (zuzuweisender) Wert der Variable
     */
    public function setVar($name, $value = '', int $convert = Template::CONVERT_NONE): static
    {
        if(!is_array($name)) {
            $value = $value ?? '';
            $this->VarList[$name] = $this->convertToHTML($value, $convert);
        }
        else {
            // backward compatibility
            $this->setVars($name, $convert);
        }
        return $this;
    }

    /**
     * Fills multiple placeholders with values
     *
     * @param array $vars
     * @param int $convert
     * @return TempCoreHandle
     */
    public function setVars(array $vars, int $convert = Template::CONVERT_NONE): static
    {
        foreach($vars as $key => $value) {
            $this->setVar($key, $value, $convert);
        }
        return $this;
    }

    /**
     * Beim Setzen des Inhalts fuer einen Handle, wird dieser Inhalt automatisch nach Bloecken, Includes (Files) untersucht.
     *
     * @param string $content Inhalt/Content
     */
    public function setContent(string $content, bool $scanForPattern = true)
    {
        parent::setContent($content);

        if($scanForPattern) {
            $this->findPattern($this->content);
        }
    }

    /**
     * Weist der Eigenschaft Directory ein Verzeichnis zu.
     * Das Verzeichnis muss durch alle Handles gereicht werden,
     * damit in jedem Handle die File Includes auf die richtigen Quellen zeigen.
     *
     * @param string $dir Verzeichnis
     */
    public function setDirectory(string $dir)
    {
        $this->Directory = $dir;
    }

    /**
     * Liefert das Verzeichnis.
     *
     * @return string Verzeichnis zum Template
     */
    public function getDirectory(): string
    {
        return $this->Directory;
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
        if(array_key_exists($handle, $this->BlockList)) {
            return $this->BlockList[$handle];
        }
        else {
            $keys = array_keys($this->BlockList);
            foreach($keys as $key) {
                $Obj = $this->BlockList[$key]->getTempBlock($handle);
                if($Obj instanceof TempBlock) {
                    return $Obj;
                }
            }
        }
        return null;
    }

    /**
     * Eine der Hauptfunktionen in der Rapid Template Engine!
     * Objekt Elemente haben im Template das Muster <!-- FUNKTION HANDLENAME>INHALT<!-- END HANDLENAME --> .
     * Ein Block kann in diesem Fall auch ein Include eines weiteren Templates oder Scripts sein
     * (Dies gilt aber nur fuer die Blockdefinition innerhalb des Templates).
     *
     * Findet er einen neuen Block, wird entsprechend ein neues Objekt mit dem Inhalt des gefundenen Blocks angelegt.
     *
     * @param string $templateContent Inhalt, welcher nach Template Elementen abgesucht werden soll
     * @return int Number of replaced patterns
     */
    protected function findPattern(string $templateContent):int
    {
        // Matches Blocks like <!-- XXX handle -->freeform-text<!-- END handle -->
        $reg = '/<!-- ([A-Z]{2,}) ([^>]+) -->(.*?)<!-- END \2 -->/s';
        preg_match_all($reg, $templateContent, $matches, PREG_SET_ORDER);
        checkRegExOutcome($reg, $templateContent);
        $changes = [];
        foreach ($matches as $match) {
            //the entire Comment Block
            $fullMatchText = $match[0];
            //the XXX Tag part
            $kind = $match[1];
            //the handle part
            $handle = ($match[2]);
           //the freeform-text part
            $tagContent = ($match[3]);

            switch($kind) {
                case TEMP_BLOCK_IDENT:
                    $value = "[$handle]";
                    $this->createBlock($handle, $this->getDirectory(), $tagContent, $this->charset);
                    break;

                case TEMP_INCLUDE_IDENT:
                    $value = "[$handle]";
                    $this->createFile($handle, $this->getDirectory(), $tagContent, $this->charset);
                    break;

                case TEMP_INCLUDESCRIPT_IDENT:
                    $value = "[$handle]";
                    $this->createScript($handle, $this->getDirectory(), $tagContent, $this->charset);
                    break;

                case TEMP_SESSION_IDENT:
                    $value = isset($_SESSION[$tagContent]) ? urldecode($_SESSION[$tagContent]) : '';
                    break;

                case TEMP_TRANSL_IDENT:
                    if ($translator = Template::getTranslator())
                        $value = $translator->translateTag($handle, $tagContent);
                    else
                        $value  = $tagContent;
                    break;

                default:
                    $value = IS_DEVELOP ? "Unknown block $kind" : '';
            }
            if ($value !== $fullMatchText)
                //made a change
                $changes[$fullMatchText] = $value;
        }
        $this->content = strtr($this->content, $changes);
        $countChanges = count($changes);
        unset($changes);
        return $countChanges;
    }

    /**
     * Ersetzt alle Bloecke und Variablen mit den fertigen Inhalten.
     *
     * @param boolean $returnContent Bei true wird der geparste Inhalt an den Aufrufer zurueck gegeben, bei false gespeichert.
     * @param bool $clearParsedContent
     * @return string Geparster Inhalt
     */
    public function parse(bool $returnContent = false, bool $clearParsedContent = true): string
    {
        $varStart = $this->varStart;
        $varEnd = $this->varEnd;

        $content = $this->content;

        $search = [];
        $replace = [];

        foreach($this->VarList as $key => $val) {
            $search[] = "$varStart$key$varEnd";
            $replace[] = $val;
        }

        $sizeOfVarList = count($this->VarList);
        $iterations = 0;
        $count = 1;
        while($count and $iterations < $sizeOfVarList) {
            $content = str_replace($search, $replace, $content, $count);
            $iterations++;
        }
        $replace_pairs = [];
        if ($translator = Template::getTranslator())
            $translator->translateWithRegEx(
                $content, Translator::CURLY_TAG_REGEX, $replace_pairs);
        foreach($this->BlockList as $Handle => $TempBlock) {
            if($TempBlock->allowParse()) {
                $TempBlock->parse();
                $parsedContent = $TempBlock->getParsedContent();
                if($clearParsedContent) $TempBlock->clearParsedContent();
                $TempBlock->setAllowParse(false);
            }
            else {
                $parsedContent = $TempBlock->getParsedContent();
            }
            $replace_pairs["[$Handle]"] = $parsedContent;
            unset($TempBlock);
        }

        foreach($this->fileList as $Handle => $TempFile) {
            /**@var TempCoreHandle $TempFile*/
            $TempFile->parse();
            $parsedContent = $TempFile->getParsedContent();
            if($clearParsedContent) $TempFile->clearParsedContent();
            unset($TempFile);
            $replace_pairs["[$Handle]"] = $parsedContent;
        }

        $content = strtr($content, $replace_pairs);
        unset($replace_pairs);


        if(!$returnContent) {
            $this->ParsedContent = $content;
            return '';
        }
        else {
            return $content;
        }
    }

    /**
     * set parentheses for the variable placeholders
     *
     * @param string $start
     * @param string $end
     * @return void
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
     * @return TempFile|null Instanz von TempFile oder false
     * @see TempFile
     */
    public function findFile(string $handle): ?TempFile
    {
        $keys = array_keys($this->fileList);
        $numFiles = count($keys);
        for($i = 0; $i < $numFiles; $i++) {
            /** @var TempCoreHandle $TempHandle */
            $TempHandle = $this->fileList[$keys[$i]];
            if($keys[$i] == $handle && $TempHandle->getType() == 'FILE') {
                return $TempHandle;
            }
            else {
                $obj = $TempHandle->findFile($handle);
                if($obj) {
                    return $obj;
                }
            }
        }
        return null;
    }
}

/* --------------------- */
######## TempBlock ########
/* --------------------- */

/**
 * TempBlock
 *
 * TempBlock ein Template Element konzipiert fuer dynamische Inhalte. Im Template definiert man einen Block wie folgt:
 * <!-- BEGIN BLOCK_MYNAME --> ...Inhalt... <!-- END BLOCK_MYNAME -->
 *
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
    private bool $allowParse;

    /**
     * @param string $handle Name des Handles
     * @param string $directory Verzeichnis zum Template
     * @param string $content Inhalt des Blocks
     */
    function __construct(string $handle, string $directory, string $content, string $charset)
    {
        parent::__construct('BLOCK', $directory, $charset);

        $this->setHandle($handle);
        $this->allowParse = false;
        $this->setContent($content);
    }

    /**
     *f Die Funktion fraegt ab, ob geparst werden darf.
     * Beim ersten Aufruf der Funktion NewBlock darf noch nicht geparst werden,
     * da noch keine Variablen zugewiesen wurden. Erst bei weiteren Aufrufen (z.B. Schleife) wird geparst,
     * damit neuer Content entsteht.
     * Hinweis: wird in Verwendung mit parse($returncontent) verwendet. Um bei Schleifen den Content aneinander zu haengen.
     *
     * @return boolean Bei true darf geparst werden, bei false nicht.
     */
    public function allowParse(): bool
    {
        return $this->allowParse;
    }

    /**
     * Setzt den Status, ob geparst werden darf.
     *
     * @param boolean $bool True f�r ja, False f�r nein.
     */
    public function setAllowParse(bool $bool)
    {
        $this->allowParse = $bool;
    }

    /**
     * Parst den Block Inhalt und fuegt das Ergebnis an den bisherigen geparsten Inhalt hinzu.
     */
    public function parse(bool $returnContent = false, bool $clearParsedContent = true): string
    {
        $content = parent::parse($this->allowParse, $clearParsedContent);
        if($returnContent) {
            return $content;
        }
        else {
            $this->addParsedContent($content);
        }
        return '';
    }

    /**
     * Fuegt Inhalt an.
     *
     * @param string $content Inhalt der angefuegt werden soll
     */
    private function addParsedContent(string $content)
    {
        $this->ParsedContent .= $content;
    }
}

/* --------------------- */
######## TempFile #########
/* --------------------- */

/**
 * TempFile
 *
 * TempFile ein Template Element konzipiert fuer weitere Template Dateien. Im Template definiert man eine neues Template wie folgt:
 * <!-- INCLUDE MYNAME --> Pfad + Dateiname des Html Templates <!-- END MYNAME -->
 *
 * Inkludierte Dateien werden genauso behandelt wie andere Html Templates. Man kann darin Variablen, Bloecke und weitere Html Templates definieren.
 *
 * @package rte
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Template.class.php,v 1.12 2007/03/13 08:52:50 manhart Exp $
 * @access private
 **/
class TempFile extends TempCoreHandle
{
    /**
     * @var string filename
     */
    private string $filename;

    /**
     * Ruft die Funktion zum Laden der Datei auf.
     *
     * @param string $handle Name des Handles
     * @param string $directory Verzeichnis zur Datei
     * @param string $filename Dateiname
     */
    function __construct(string $handle, string $directory, string $filename, string $charset)
    {
        parent::__construct('FILE', $directory, $charset);

        $this->setHandle($handle);
        $this->filename = $filename;
        $this->loadFile();
    }

    /**
     * @return string filename
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * loads the template file
     */
    private function loadFile()
    {
        $content = '';
        $fp = fopen($this->getDirectory() . $this->filename, 'r');
        if(!$fp) {
            $this->raiseError(__FILE__, __LINE__, sprintf('Cannot load template %s (@LoadFile)',//TODO Exeption
                $this->getDirectory() . $this->filename));
            return;
        }
        $size = filesize($this->Directory . $this->filename);
        if($size > 0) {
            $content = fread($fp, $size);
        }
        fclose($fp);

        $this->setContent($content);
    }

    /**
     * Sucht nach allen inkludierten TempFiles und gibt die Instanzen in einem Array zurueck (Rekursion).
     *
     * @return array<int, TempFile> list of TempFile
     * @see TempFile
     */
    public function getFiles(): array
    {
        $files = [];

        $keys = array_keys($this->fileList);
        $sizeOf = count($keys);
        for($i = 0; $i < $sizeOf; $i++) {
            $TempFile = $this->fileList[$keys[$i]];
            $files[] = $TempFile;
            $more_files = $TempFile->getFiles();
            if($more_files) {
                $files = array_merge($files, $more_files);
            }
        }

        return $files;
    }
}

/* --------------------- */
######## TempSimple #######
/* --------------------- */

class TempSimple extends TempCoreHandle
{
    function __construct(string $handle, string $content, string $charset)
    {
        parent::__construct('FILE', '', $charset);
        $this->setHandle($handle);
        $this->setContent($content);
    }
}

/* --------------------- */
####### TempScript ########
/* --------------------- */

/**
 * TempScript
 *
 * TempScript ein Template Element konzipiert fuer weitere Php Html Template Dateien. Im Template definiert man eine neues Script wie folgt:
 * <!-- INCLUDESCRIPT MYNAME --> Pfad + Dateiname des pHtml Templates <!-- END MYNAME -->
 *
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
        if($returnContent) {
            return $content;
        }

        ob_start();
        eval('?>' . $this->ParsedContent . '<?php ');
        $this->ParsedContent = ob_get_contents();
        ob_end_clean();

        return '';
    }
}

/* --------------------- */
######## Template #########
# Rapid Template Engine   #
# ... arise ...  :)       #
/* --------------------- */

/**
 * Template
 *
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
    private string $ActiveHandle = '';

    /**
     * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
     *
     * @var null|TempFile|TempSimple
     */
    public null|TempFile|TempSimple $ActiveFile = null;

    /**
     * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
     *
     * @var null|TempBlock
     */
    public ?TempBlock $ActiveBlock = null;

    //@var array Template Container (hier sind nur die Files enthalten, die �ber das Template Objekt gesetzt werden)
    protected array $FileList = [];

    private string $varStart = TEMP_VAR_START;

    private string $varEnd = TEMP_VAR_END;

    const CONVERT_NONE = 0;
    const CONVERT_HTMLSPECIALCHARS = 1;
    const CONVERT_HTMLENTITIES = 2;

    /**
     * @var string
     */
    private string $charset = 'UTF-8';

    /**
     * Konstruktor
     *
     * @param string $dir Verzeichnis zu den Templates (Standardwert ./)
     */
    function __construct(string $dir = './')
    {
        $this->FileList = [];
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
            return Template::getTranslator()->translateFile($file, $language);
        } catch (Exception) {
            return $file;
        }
    }

    public static function isCacheTranslations(): bool
    {
        return static::getTranslator() != null ? static::$cacheTranslations : false;
    }

    /**
     * @param bool $cacheTranslations
     */
    public static function setCacheTranslations(bool $cacheTranslations): void
    {
        self::$cacheTranslations = $cacheTranslations;
    }

    /**
     * @param string $charset
     */
    public function setCharset(string $charset)
    {
        $this->charset = $charset;
    }

    /**
     * aendert das Verzeichnis worin die Templates liegen.
     *
     * @param string $dir Verzeichnis zu den Templates
     */
    function setDirectory(string $dir)
    {
        if(!$dir) {
            return;
        }

        $dir = addEndingSlash($dir);
        if(is_dir($dir)) {
            $this->dir = $dir;
        }
        else {
            $this->raiseError(__FILE__, __LINE__, sprintf('Directory \'%s\' not found!', $dir));
        }
    }

    /**
     * Liefert das Verzeichnis zu den Templates zurueck
     *
     * @return string Verzeichnis zu den Templates
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
    public function setFile(string $handle, string $filename = '')
    {
        $this->addFile($handle, $filename);
    }

    /**
     * Setzt die Template Dateien samt Pfad. Der Pfad wird automatisch extrahiert. Der Handle-Name dient der Identifikation der Datei.
     *
     * @param string $handle Handle-Name (array erlaubt)
     * @param string $filename Pfad und Dateiname (Template)
     */
    public function setFilePath(string $handle, string $filename = '')
    {
        $this->setDirectory(dirname($filename));
        $this->addFile($handle, basename($filename));
    }

    /**
     * Set content directly
     *
     * @param string $handle
     * @param string $content
     * @return void
     */
    public function setContent(string $handle, string $content): void
    {
        $TempSimple = new TempSimple($handle, $content, $this->charset);
        $this->FileList[$handle] = $TempSimple;

        if($this->ActiveHandle == '') {
            $this->useFile($handle);
        }
    }

    /**
     * Set the parentheses for the placeholders
     *
     * @param string $varStart
     * @param string $varEnd
     * @return void
     */
    public function setParentheses(string $varStart, string $varEnd): void
    {
        $this->varStart = $varStart;
        $this->varEnd = $varEnd;

        $this->ActiveFile?->setParentheses($varStart, $varEnd);
    }

    /**
     * Fuegt ein Template zum File Container hinzu.
     *
     * @param string $handle Handle-Name
     * @param string $file Datei
     */
    private function addFile(string $handle, string $file)
    {
        $TempFile = new TempFile($handle, $this->getDirectory(), $file, $this->charset);
        $this->FileList[$handle] = $TempFile;

        // added 02.05.2006 Alex M.
        $this->setParentheses($this->varStart, $this->varEnd);

        if($this->ActiveHandle == '') {
            $this->useFile($handle);
        }
    }

    /**
     * Die Funktion sagt der Template Engine, dass die nachfolgenden Variablenzuweisungen auf ein anderes Html Template fallen.
     *
     * @param string $handle Name des (File-) Handles
     */
    function useFile(string $handle)
    {
        if(array_key_exists($handle, $this->FileList)) {
            if($handle != $this->ActiveHandle) {
                $TempFile = $this->FileList[$handle];

                $this->ActiveHandle = $handle;
                $this->ActiveFile = $TempFile;
                // Referenz aufheben
                unset($this->ActiveBlock);
            }
        }
        else {
            $found = false;
            $keys = array_keys($this->FileList);
            $numKeys = count($keys);
            for($i = 0; $i < $numKeys; $i++) {
                $TempFile = &$this->FileList[$keys[$i]];

                $obj = $TempFile->findFile($handle);
                if($obj instanceof TempFile) {
                    $this->ActiveHandle = $handle;
                    $this->ActiveFile = $obj;
                    // Referenz aufheben
                    unset($this->ActiveBlock);
                    $found = true;
                    break;
                }
            }
            if(!$found) {
                // TODO Error
                $this->raiseError(__FILE__, __LINE__, sprintf('FileHandle %s not found', $handle));
                unset($this->ActiveFile);
                unset($this->ActiveBlock);
            }
        }
    }

    /**
     * Liefert ein Array mit allen TempFile Objekten (auch TempScript).
     *
     * @return array<int, TempFile> Liste aus TempFile Objekten
     */
    public function getFiles(): array
    {
        $files = [];

        $keys = array_keys($this->FileList);
        $numFiles = count($keys);
        for($i = 0; $i < $numFiles; $i++) {
            $TempFile = $this->FileList[$keys[$i]];
            if($TempFile instanceof TempSimple) {
                continue;
            }
            $files[] = $TempFile;

            // Suche innerhalb des TempFile's nach weiteren TemplateFiles
            $more_files = $TempFile->getFiles();
            if(count($more_files) > 0) {
                $files = array_merge($files, $more_files);
            }
            unset($more_files);
        }

        return $files;
    }

    /**
     * @return int
     */
    public function countFileList(): int
    {
        return count($this->FileList);
    }

    /**
     * Anweisung, dass ein neuer Block folgt, dem die n�chsten Variablen zugewiesen werden.
     *
     * @param string $handle Handle-Name
     * @return TempBlock|null
     */
    function newBlock(string $handle): ?TempBlock
    {
        if($this->ActiveFile instanceof TempFile) {
            if((!isset($this->ActiveBlock)) or ($this->ActiveBlock->getHandle() != $handle)) {
                $this->ActiveBlock = $this->ActiveFile->getTempBlock($handle);
            }

            if($this->ActiveBlock) {
                // added 2.5.06 Alex M.
                $this->ActiveBlock->setParentheses($this->varStart, $this->varEnd);

                if($this->ActiveBlock->allowParse()) {
                    $this->ActiveBlock->parse();
                }
                else {
                    $this->ActiveBlock->setAllowParse(true);
                }
                return $this->ActiveBlock;
            }
            else unset($this->ActiveBlock);
        }
        return null;
    }

    /**
     * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::backToFile($filehandle)).
     * Verwendet Template::leaveBlock() und Template::useFile().
     */
    public function backToFile(string $fileHandle = ''): static
    {
        $this->leaveBlock();
        if(!is_null($fileHandle)) {
            $this->useFile($fileHandle);
        }
        return $this;
    }

    /**
     * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::leaveBlock())
     *
     * @return Template
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
     * @return null|TempBlock
     */
    function useBlock(string $blockHandle): ?TempBlock
    {
        if($this->ActiveFile) {
            $ActiveBlock = $this->ActiveFile->getTempBlock($blockHandle);
            if($ActiveBlock) {
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
    public function setVar($name, $value = '', int $convert = Template::CONVERT_NONE): static
    {
        if(isset($this->ActiveBlock)) {
            $ActiveBlock = $this->ActiveBlock;
            $ActiveBlock->setVar($name, $value, $convert);
        }
        elseif(isset($this->ActiveFile)) {
            $ActiveFile = $this->ActiveFile;
            $ActiveFile->setVar($name, $value, $convert);
        }
        else {
            $this->raiseError(__FILE__, __LINE__, "Class Template: Cannot assign Variable $name. There is no file or block associated.");
        }
        return $this;
    }

    /**
     * Fill multiple placeholders with values
     *
     * @param array $vars
     * @param int $encoding
     * @return Template
     */
    public function setVars(array $vars, int $encoding = Template::CONVERT_NONE): static
    {
        if(isset($this->ActiveBlock)) {
            $ActiveBlock = $this->ActiveBlock;
            $ActiveBlock->setVars($vars, $encoding);
        }
        elseif(isset($this->ActiveFile)) {
            $ActiveFile = $this->ActiveFile;
            $ActiveFile->setVars($vars, $encoding);
        }
        else {
            $this->raiseError(__FILE__, __LINE__, 'Class Template: Cannot assign Variables. There is no file or block associated.');
        }
        return $this;
    }

    /**
     * Erstellt automatisch dynamische Bloecke mit den uebergebenen Datensaetzen.
     * Das Array muss wie folgt aufgebaut werden: $array[$laufender_record_index][$varname] = $value !
     *
     * @param string $blockHandle Handle-Name des Blocks
     * @param array $recordset
     * @return Template
     */
    public function assignRecordset(string $blockHandle, array $recordset = []): static
    {
        $count = count($recordset);
        if($count == 0) {
            return $this;
        }

        for($i = 0; $i < $count; $i++) {
            $record = $recordset[$i];

            if($this->newBlock($blockHandle)) {
                $this->setVar($record);
            }
        }
        $this->leaveBlock();

        return $this;
    }

    /**
     * Alias for Template::assignRecordset()
     *
     * @param string $blockHandle Handle-Name des Blocks
     * @param array $recordSet
     * @return Template
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
     * @return Template
     */
    public function parse(string $handle = ''): self
    {
        if($handle != '') {
            $this->useFile($handle);
        }
        $this->ActiveFile?->parse();
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
        if($handle != '') {
            $this->useFile($handle);
        }

        if($this->ActiveFile) {
            return $this->ActiveFile->getParsedContent();
        }
        return '';
    }

    /**
     * Leert den Buffer (ParsedContent)
     *
     * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet!)
     **/
    public function clear(string $handle = '')
    {
        if($handle != '') {
            $this->useFile($handle);
        }

        if($this->ActiveFile instanceof TempFile) {
            $TempFile = $this->ActiveFile;
            $TempFile->clearParsedContent();
        }
    }

    /**
     * Setzt alle Werte zurueck (Loescht alle Files aus dem Buffer).
     *
     * @return void
     */
    function reset(): void
    {
        $this->ActiveHandle = '';
        unset($this->ActiveFile);
        unset($this->ActiveBlock);
        $this->FileList = [];
    }
}