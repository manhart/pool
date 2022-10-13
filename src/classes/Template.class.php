<?php
/**
 * -= Rapid Template Engine (RTE) =-
 *
 * Template.class.php
 *
 * Rapid Template Engine (RTE) ist eine Template-Engine f�r PHP. Genauer gesagt erlaubt es die einfache Trennung von
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
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

if(!defined('RAPID_TEMPLATE_ENGINE')) {
    // Variablen Identifizierung
    define('TEMP_VAR_START', '{');
    define('TEMP_VAR_END', '}');

    // Template Objekt Elemente Identifizierung
    define('TEMP_DYNBLOCK_IDENT', 'BLOCK');
    define('TEMP_INCLUDE_IDENT', 'INCLUDE');
    define('TEMP_INCLUDESCRIPT_IDENT', 'INCLUDESCRIPT');
    define('TEMP_SESSION_IDENT', 'SESSION');

    // Prevent multiple loading
    define('RAPID_TEMPLATE_ENGINE', 1);

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
        var $Content = '';

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
         * @access public
         * @param string $type Typ set of (FILE, BLOCK)
         */
        function __construct($type)
        {
            parent::__construct();

            $this->setType($type);
        }

        /**
         * Setzt eindeutigen Handle.
         *
         * @param string $handle Eindeutiger Name fuer Handle
         */
        function setHandle($handle)
        {
            $this->Handle = $handle;
        }

        /**
         * TempHandle::getHandle()
         *
         * Liefert Handle
         *
         * @access public
         * @return string Handle
         */
        function getHandle()
        {
            return $this->Handle;
        }

        /**
         * TempHandle::setType()
         *
         * Setzt Handle Typ. Anhand des Typs unterscheidet man derzeit zwischen Block- und File Handles.
         *
         * @access public
         * @param string $type Typ set of (BLOCK, FILE)
         */
        function setType($type)
        {
            $this->Type = $type;
        }

        /**
         * TempHandle::getType()
         *
         * Liefert den Handle Typ
         *
         * @access public
         * @return string Typ set of (BLOCK, FILE)
         */
        function getType()
        {
            return $this->Type;
        }

        /**
         * TempHandle::setContent()
         *
         * Speichert Inhalt im Handle ab.
         *
         * @access public
         * @param string $content Inhalt (z.B. eines BLOCK's, FILE's)
         */
        function setContent($content)
        {
            $this->Content = $content;
        }

        /**
         * Liefert den gespeicherten Inhalt eines Handles.
         *
         * @access public
         * @return string Inhalt dieses Handles
         */
        function getContent()
        {
            return $this->Content;
        }

        /**
         * Funktion dient zum Zwischenspeichern von vervollstaendigten Inhalt.
         *
         * @access public
         * @param string $content Geparster, vervollstaendigter Inhalt
         */
        function setParsedContent($content)
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
         *
         * @access public
         */
        function clearParsedContent()
        {
            $this->ParsedContent = '';
        }

        /**
         * Leert auch den geladenen Content inkl. geparsten Content
         *
         */
        function clear()
        {
            $this->Content = '';
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
        //@var array Variablen Container
        //@access private
        var $VarList;

        //@var array Block Container
        //@access private
        var $BlockList;

        //@var array File Container
        //@access private
        var $FileList;

        //@var string Verzeichnis zum Template
        //@access public
        var $Directory;

        var $varStart = TEMP_VAR_START;

        var $varEnd = TEMP_VAR_END;

        public $charset = 'UTF-8';

        /**
         * Konstruktor: benoetigt Handle-Typ und Verzeichnis zum Template.
         * Array fuer Variablen Container und Block Container werden angelegt.
         *
         * @access public
         * @param string $type Handle-Typ set of (BLOCK, FILE)
         * @param string $directory Verzeichnis zum Template
         */
        function __construct($type, $directory, $charset = 'UTF-8')
        {
            $this->charset = $charset;
            parent::__construct($type);

            $this->setDirectory($directory);
            $this->BlockList = array();
            $this->FileList = array();
            $this->VarList = array();
        }

        /**
         * Die Funktion createBlock legt einen neuen Block (Objekt vom Typ TempBlock) an.
         *
         * @access public
         * @param string $handle Name des Handles
         * @param string $dir Verzeichnis zum Template
         * @param string $content Inhalt des Blocks
         * @return object TempBlock
         * @see TempBlock
         */
        function createBlock($handle, $dir, $content, $charset = 'UTF-8')
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
         * @return object TempFile
         * @see TempFile
         */
        function createFile($handle, $dir, $filename, $charset = 'UTF-8')
        {
            $obj = new TempFile($handle, $dir, $filename, $charset);
            $this->FileList[$handle] = $obj;
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
         * @return object TempScript
         * @see TempScript
         */
        function createScript($handle, $dir, $filename, $charset = 'UTF-8')
        {
            $obj = new TempScript($handle, $dir, $filename, $charset);
            $this->FileList[$handle] = $obj;
            return $obj;
        }

        /**
         * Diese Prozedur fuegt eine Variable zum Variablen Container hinzu.
         *
         * @param string|array $name name of the variable (placeholder in the template)
         * @param mixed $value (zuzuweisender) Wert der Variable
         */
        function setVar($name, $value = '', int $encode = Template::ENCODE_NONE)
        {
            $encode = function($value) use ($encode) {
                if($encode == Template::ENCODE_NONE) {
                    return $value;
                }
                if($encode == Template::ENCODE_HTMLSPECIALCHARS) {
                    return htmlspecialchars($value, ENT_QUOTES, $this->charset);
                }
                if($encode == Template::ENCODE_HTMLENTITIES) {
                    return htmlentities($value, ENT_QUOTES, $this->charset);
                }
            };

            if(!is_array($name)) {
                $this->VarList[$name] = $encode($value);
            }
            else {
                if((array)$value !== $value) {
                    foreach($name as $key => $value) {
                        switch(gettype($value)) {
                            case 'array':
                                $this->VarList[$key] = 'array';
                                break;

                            case 'object':
                                $this->VarList[$key] = 'object';
                                break;

                            default:
                                $this->VarList[$key] = $encode($value);
                                break;
                        }
                    }
                }
                else {
                    foreach($name as $key => $value) {
                        $this->VarList[$key] = $encode($value);
                    }
                }
            }
        }

        /**
         * Beim Setzen des Inhalts fuer einen Handle, wird dieser Inhalt automatisch nach Bloecken, Includes (Files) untersucht.
         *
         * @param string $content Inhalt/Content
         */
        function setContent($content, $scan = true)
        {
            parent::setContent($content);

            if($scan) {
                $this->findPattern($this->Content);
            }
        }

        /**
         * Weist der Eigenschaft Directory ein Verzeichnis zu.
         * Das Verzeichnis muss durch alle Handles gereicht werden,
         * damit in jedem Handle die File Includes auf die richtigen Quellen zeigen.
         *
         * @access public
         * @param string $dir Verzeichnis
         */
        function setDirectory($dir)
        {
            $this->Directory = $dir;
        }

        /**
         * Liefert das Verzeichnis.
         *
         * @access public
         * @return string Verzeichnis zum Template
         */
        function getDirectory()
        {
            return $this->Directory;
        }

        /**
         * Gibt das Block Objekt mit dem uebergebenen Namen des Handles $handle zurueck.
         * Falls er ueberhaupt existiert! Ansonsten wird NULL zurueck gegeben.
         *
         * @access public
         * @param string $handle Name des Handles
         * @return TempBlock|null Instanz von TempBlock oder NULL
         */
        function getTempBlock(string $handle): ?TempBlock
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
         * Findet er einen neuen Block, wird entsprechend ein neues Objekt mit dem Inhalt des gefunden Blocks angelegt.
         *
         * @access public
         * @param string Inhalt, welcher nach Template Elementen abgesucht werden soll
         */
        function findPattern($content)
        {
            $elemsymbols = '(' . TEMP_DYNBLOCK_IDENT . '|' . TEMP_INCLUDE_IDENT . '|' . TEMP_INCLUDESCRIPT_IDENT . '|' .
                TEMP_SESSION_IDENT . ')'; // TODO REUSE
            // AM, 24.09.20, without modificator /s,
            // by default . doesn't match new lines - [\s\S] is a hack around that problem
            $reg = "/\<\!\-\- $elemsymbols (.+) \-\-\>([\s\S]*)\<\!\-\- END \\2 \-\-\>/U";
            $bResult = preg_match_all($reg, $content, $matches, PREG_SET_ORDER);
            if($bResult) {
                $numMatches = SizeOf($matches);
                for($i = 0; $i < $numMatches; $i++) {

                    $kind = $matches[$i][1];
                    $handle = ($matches[$i][2]);
                    $content = ($matches[$i][3]);

                    $reg = "/\<\!\-\- $elemsymbols $handle \-\-\>([\s\S]*)\<\!\-\- END $handle \-\-\>/U";
                    $this->Content = preg_replace($reg, '{' . $handle . '}', $this->Content);

                    switch($kind) {
                        case TEMP_DYNBLOCK_IDENT:
                            $this->createBlock($handle, $this->getDirectory(), $content, $this->charset);
                            break;

                        case TEMP_INCLUDE_IDENT:
                            $this->createFile($handle, $this->getDirectory(), $content, $this->charset);
                            break;

                        case TEMP_INCLUDESCRIPT_IDENT:
                            /*
                            if($content[0] == '/') {
                                $directory = addEndingSlash(dirname($content));
                                $content = basename($content);
                            } else $directory = $this -> Directory;
                            */
                            $this->createScript($handle, $this->getDirectory(), $content, $this->charset);
                            break;

                        case TEMP_SESSION_IDENT:
                            $value = isset($_SESSION[$content]) ? urldecode($_SESSION[$content]) : '';
                            $this->setVar($handle, $value);
                            break;
                    }
                }
            }
        }

        /**
         * Ersetzt alle Bloecke und Variablen mit den fertigen Inhalten.
         *
         * @param boolean $returnContent Bei true wird der geparste Inhalt an den Aufrufer zurueck gegeben, bei false gespeichert.
         * @param bool $clearParsedContent
         * @return string Geparster Inhalt
         */
        public function parse(bool $returnContent = false, bool $clearParsedContent = true)
        {
            $varStart = $this->varStart;
            $varEnd = $this->varEnd;

            $content = $this->Content;
            ### TODO Pool 5, bei setVar {} adden oder read write properties...
            #$content = str_replace(array_keys($this -> VarList), array_values($this -> VarList), $content);
            foreach($this->VarList as $Key => $val) {
                $content = str_replace($varStart . $Key . $varEnd, $val ?? '', $content);
            }

            $search = array();
            $replace = array();
            /**
             * @var string $Handle
             * @var TempBlock $TempBlock
             */
            foreach($this->BlockList as $Handle => $TempBlock) {
                $search[] = '{' . $Handle . '}';

                if($TempBlock->allowParse()) {
                    $TempBlock->parse();
                    $replace[] = $TempBlock->getParsedContent();
                    if($clearParsedContent) $TempBlock->clearParsedContent();
                    $TempBlock->setAllowParse(false);
                }
                else {
                    //		                $content = str_replace('{'.$Handle.'}', '', $content);
                    $replace[] = $TempBlock->getParsedContent();
                }

                unset($TempBlock);
            }

            foreach($this->FileList as $Handle => $TempFile) {
                $TempFile->parse();
                $search[] = '{' . $Handle . '}';
                $replace[] = $TempFile->getParsedContent();
                if($clearParsedContent) $TempFile->clearParsedContent();
                unset($TempFile);
            }

            $content = str_replace($search, $replace, $content);

            if(!$returnContent) {
                $this->ParsedContent = $content;
                return true;
            }
            else {
                return $content;
            }
        }

        /**
         * Sucht nach einem File mit dem uebergebenen Namen des Handles $handle.
         *
         * @access public
         * @param string $handle Name des Handles
         * @return object Instanz von TempFile oder false
         * @see TempFile
         */
        function &findFile($handle)
        {
            $keys = array_keys($this->FileList);
            for($i = 0; $i < SizeOf($keys); $i++) {
                $TempHandle = &$this->FileList[$keys[$i]];
                if($keys[$i] == $handle && $TempHandle->GetType() == 'FILE') {
                    return $TempHandle;
                }
                else {
                    $obj = &$TempHandle->findFile($handle);
                    if($obj) {
                        return $obj;
                    }
                }
            }
            $bResult = false;
            return $bResult;
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
         * TempBlock()
         *
         * Konstruktor
         *
         * @access public
         * @param string $handle Name des Handles
         * @param string $directory Verzeichnis zum Template
         * @param string $content Inhalt des Blocks
         */
        function __construct($handle, $directory, $content, $charset = 'UTF-8')
        {
            parent::__construct('BLOCK', $directory, $charset);

            $this->SetHandle($handle);
            $this->allowParse = false;
            $this->SetContent($content);
        }

        /**
         * TempBlock::allowParse()
         *
         * Die Funktion fraegt ab, ob geparst werden darf.
         * Beim ersten Aufruf der Funktion NewBlock darf noch nicht geparst werden,
         * da noch keine Variablen zugewiesen wurden. Erst bei weiteren Aufrufen (z.B. Schleife) wird geparst,
         * damit neuer Content entsteht.
         * Hinweis: wird in Verwendung mit parse($returncontent) verwendet. Um bei Schleifen den Content aneinander zu haengen.
         *
         * @access public
         * @return boolean Bei true darf geparst werden, bei false nicht.
         */
        function allowParse(): bool
        {
            return $this->allowParse;
        }

        /**
         * TempBlock::setAllowParse()
         *
         * Setzt den Status, ob geparst werden darf.
         *
         * @access public
         * @param boolean $bool True f�r ja, False f�r nein.
         */
        function setAllowParse(bool $bool)
        {
            $this->allowParse = $bool;
        }

        /**
         * Parst den Block Inhalt und fuegt das Ergebnis an den bisherigen geparsten Inhalt hinzu.
         *
         * @access public
         */
        function parse(bool $returnContent = false, bool $clearParsedContent = true)
        {
            $content = parent::parse($this->allowParse, $clearParsedContent);
            if($returnContent) {
                return $content;
            }
            else {
                $this->addParsedContent($content);
            }
        }

        /**
         * Fuegt Inhalt an.
         *
         * @access private
         * @param string $content Inhalt der angefuegt werden soll
         */
        function addParsedContent($content)
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
        //@var string Dateiname
        //@access private
        var $Filename;

        /**
         * TempFile
         *
         * Konstruktor
         *
         * Ruft die Funktion zum Laden der Datei auf.
         *
         * @access public
         * @param string $handle Name des Handles
         * @param string $directory Verzeichnis zur Datei
         * @param string $filename Dateiname
         */
        function __construct($handle, $directory, $filename, $charset = 'UTF-8')
        {
            parent::__construct('FILE', $directory, $charset);

            $this->setHandle($handle);
            $this->Filename = $filename;
            $this->loadFile();
        }

        /**
         * TempFile::loadFile()
         *
         * Laedt eine Datei (verwendet die Eigenschaft "filename") und setzt den Inhalt in die Eigenschaft "content"
         *
         * @access private
         */
        function loadFile()
        {
            $content = '';
            $fp = fopen($this->getDirectory() . $this->Filename, 'r');
            if(!$fp) {
                $this->raiseError(__FILE__, __LINE__, sprintf('Cannot load template %s (@LoadFile)',
                    $this->getDirectory() . $this->Filename));
                return;
            }
            $size = filesize($this->Directory . $this->Filename);
            if($size > 0) {
                $content = fread($fp, $size);
            }
            fclose($fp);

            $this->setContent($content);
        }

        /**
         * Sucht nach allen inkludierten TempFiles und gibt die Instanzen in einem Array zurueck (Rekursion).
         *
         * @access public
         * @return array Liste aus TempFile
         * @see TempFile
         */
        function &getFiles()
        {
            $files = array();

            $keys = array_keys($this->FileList);
            for($i = 0; $i < SizeOf($keys); $i++) {
                $TempFile = &$this->FileList[$keys[$i]];
                $files[] = &$TempFile;
                $more_files = &$TempFile->getFiles();
                if(count($more_files) > 0) {
                    $files = array_merge($files, $more_files);
                }
                unset($TempFile, $more_files);
            }

            return $files;
        }
    }

    /* --------------------- */
    ######## TempSimple #######
    /* --------------------- */

    class TempSimple extends TempCoreHandle
    {
        function __construct($handle, $content, $charset = 'UTF-8')
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
         * TempScript()
         *
         * Konstruktor
         *
         * @access public
         * @param string $handle Name des Handles (unique)
         * @param string $directory Verzeichnis zur Datei
         * @param string $filename Dateiname
         */
        function __construct($handle, $directory, $filename, $charset = 'UTF-8')
        {
            parent::__construct($handle, $directory, $filename, $charset);
        }

        /**
         * TempScript::parse()
         *
         * Parst das Script. Dabei wird enthaltener PHP Code ausgefuehrt.
         */
        public function parse(bool $returnContent = false, bool $clearParsedContent = true)
        {
            parent::parse($returnContent, $clearParsedContent);

            ob_start();
            eval('?>' . $this->ParsedContent . '<?php ');
            $this->ParsedContent = ob_get_contents();
            ob_end_clean();
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
        //@var string Verzeichnis zu den Templates
        //@access private
        var $dir;

        //@var string Aktiv verwendeter Handle (wohin gehen die Variablenzuweisungen)
        //@access private
        var $ActiveHandle = '';

        /**
         * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
         *
         * @var TempFile
         * @access private
         */
        var $ActiveFile;

        /**
         * Aktive Instanz TempBlock (hat eine hoehere Prioritaet als TempFile). Ist ein Block gesetzt, folgt darin die Variablenzuweisung
         *
         * @var TempBlock
         */
        var $ActiveBlock;

        //@var array Template Container (hier sind nur die Files enthalten, die �ber das Template Objekt gesetzt werden)
        //@access private
        var $FileList;

        var $varStart = TEMP_VAR_START;

        var $varEnd = TEMP_VAR_END;

        const ENCODE_NONE = 0;
        const ENCODE_HTMLSPECIALCHARS = 1;
        const ENCODE_HTMLENTITIES = 2;

        /**
         * @var string
         */
        private string $charset = 'UTF-8';

        /**
         * Konstruktor
         *
         * @param string $dir Verzeichnis zu den Templates (Standardwert ./)
         */
        function __construct($dir = './')
        {
            $this->FileList = array();
            $this->setDirectory($dir);
        }

        /**
         * @param string $charset
         */
        public function setCharset(string $charset = 'UTF-8')
        {
            $this->charset = $charset;
        }

        /**
         * aendert das Verzeichnis worin die Templates liegen.
         *
         * @param string $dir Verzeichnis zu den Templates
         */
        function setDirectory($dir)
        {
            if(strlen($dir) > 0) {
                $dir = addEndingSlash($dir);
                if(is_dir($dir)) {
                    $this->dir = $dir;
                }
                else {
                    $this->raiseError(__FILE__, __LINE__, sprintf('Directory \'%s\' not found!', $dir));
                }
            }
        }

        /**
         * Liefert das Verzeichnis zu den Templates zurueck
         *
         * @access public
         * @return string Verzeichnis zu den Templates
         */
        function getDirectory()
        {
            return $this->dir;
        }

        /**
         * Setzt die Templates. Der Handle-Name dient der Identifikation der Datei.
         *
         * @param string $handle Handle-Name; es kann auch ein Array mit Schluesselname und Wert (= Dateinamen) uebergeben werden.
         * @param string $filename Dateiname
         */
        function setFile($handle, $filename = '')
        {
            if(!is_array($handle)) {
                $this->addFile($handle, $filename);
            }
            else {
                foreach($handle as $filehandle => $filename) {
                    $this->addFile($filehandle, $filename);
                }
            }
        }

        /**
         * Setzt die Template Dateien samt Pfad. Der Pfad wird automatisch extrahiert. Der Handle-Name dient der Identifikation der Datei.
         *
         * @access public
         * @param string $handle Handle-Name (array erlaubt)
         * @param string $filename Pfad und Dateiname (Template)
         **/
        function setFilePath($handle, $filename = '')
        {
            if(!is_array($handle)) {
                $this->setDirectory(dirname($filename));
                $this->addFile($handle, basename($filename));
            }
            else {
                foreach($handle as $filehandle => $filename) {
                    $this->setDirectory(dirname($filename));
                    $this->addFile($filehandle, basename($filename));
                }
            }
        }

        function setContent($handle, $content)
        {
            $TempSimple = new TempSimple($handle, $content, $this->charset);
            $this->FileList[$handle] = $TempSimple;

            if($this->ActiveHandle == '') {
                $this->useFile($handle);
            }
        }

        function setBrackets($varStart, $varEnd)
        {
            $this->varStart = $varStart;
            $this->varEnd = $varEnd;

            if($this->ActiveFile) {
                $this->ActiveFile->varStart = $varStart;
                $this->ActiveFile->varEnd = $varEnd;
            }
        }

        /**
         * Fuegt ein Template zum File Container hinzu.
         *
         * @access private
         * @param string $handle Handle-Name
         * @param string $file Datei
         */
        function addFile($handle, $file)
        {
            $TempFile = new TempFile($handle, $this->getDirectory(), $file, $this->charset);
            $this->FileList[$handle] = $TempFile;

            if($TempFile) {
                // added 02.05.2006 Alex M.
                $TempFile->varStart = $this->varStart;
                $TempFile->varEnd = $this->varEnd;
            }

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
                    $TempFile = &$this->FileList[$handle];

                    $this->ActiveHandle = $handle;
                    $this->ActiveFile = &$TempFile;
                    // Referenz aufheben
                    unset($this->ActiveBlock);
                }
            }
            else {
                $found = false;
                $keys = array_keys($this->FileList);
                for($i = 0; $i < SizeOf($keys); $i++) {
                    $TempFile = &$this->FileList[$keys[$i]];

                    $obj = &$TempFile->findFile($handle);
                    if(is_object($obj)) {
                        $this->ActiveHandle = $handle;
                        $this->ActiveFile = &$obj;
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
         * @access public
         * @return array Liste aus TempFile Objekten
         */
        function &getFiles()
        {
            $files = array();

            $keys = array_keys($this->FileList);
            $numFiles = sizeof($keys);
            for($i = 0; $i < $numFiles; $i++) {
                $TempFile = &$this->FileList[$keys[$i]];
                if($TempFile instanceof TempSimple) {
                    continue;
                }
                $files[] = &$TempFile;

                // Suche innerhalb des TempFile's nach weiteren TemplateFiles
                $more_files = &$TempFile->getFiles();
                if(count($more_files) > 0) {
                    $files = array_merge($files, $more_files);
                }
                unset($more_files);
            }

            return $files;
        }

        /**
         * Anweisung, dass ein neuer Block folgt, dem die n�chsten Variablen zugewiesen werden.
         *
         * @param string $handle Handle-Name
         * @return TempBlock
         */
        function &newBlock(string $handle)
        {
            if($this->ActiveFile instanceof TempFile) {
                if((!isset($this->ActiveBlock)) or ($this->ActiveBlock->getHandle() != $handle)) {
                    $this->ActiveBlock = $this->ActiveFile->getTempBlock($handle);
                }

                if($this->ActiveBlock) {
                    // added 2.5.06 Alex M.
                    $this->ActiveBlock->varStart = $this->varStart;
                    $this->ActiveBlock->varEnd = $this->varEnd;

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
            $false = false; // PHP Notice, only variables should be returned by reference
            return $false;
        }

        /**
         * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::backToFile($filehandle)).
         * Verwendet Template::leaveBlock() und Template::useFile().
         *
         * @access public
         */
        function backToFile($filehandle = '')
        {
            $this->leaveBlock();
            if(!is_null($filehandle)) {
                $this->useFile($filehandle);
            }
        }

        /**
         * Verlaesst einen Block (einleitend mit der Funktion Template::newBlock(), anschliessend Template::leaveBlock())
         **/
        public function leaveBlock()
        {
            // Referenz zum aktiven Block aufheben
            unset($this->ActiveBlock);
        }

        /**
         * Weist die Template Engine an, als nächstes einen anderen BLOCK zu verwenden.
         *
         * @param string $blockHandle name of block
         * @return bool|TempBlock
         */
        function useBlock(string $blockHandle)
        {
            if($this->ActiveFile) {
                $ActiveBlock = $this->ActiveFile->getTempBlock($blockHandle);
                if($ActiveBlock) {
                    $this->ActiveBlock = $ActiveBlock;
                    return $this->ActiveBlock;
                }
            }
            return false;
        }

        /**
         * Weist Variablen zu. Standard Variablen werden im Template mit { } markiert.
         *
         * @access public
         * @param string $varname Name der Variable (= Name im Template); oder Array mit Schluesselnamen und deren Werte.
         * @param string $value Wert der Variable
         */
        function assignVar($varname, $value = '')
        {
            $this->setVar($varname, $value);
        }

        /**
         * Synonym auf die Template Funktion Template::assignVar().
         *
         * @param string|array $name name of placeholder
         * @param mixed $value value for placeholder
         */
        function setVar($name, $value = '', int $encoding = Template::ENCODE_NONE)
        {
            if(isset($this->ActiveBlock)) {
                $ActiveBlock = $this->ActiveBlock;
                $ActiveBlock->setVar($name, $value, $encoding);
            }
            elseif(isset($this->ActiveFile)) {
                $ActiveFile = $this->ActiveFile;
                $ActiveFile->setVar($name, $value, $encoding);
            }
            else {
                $this->raiseError(__FILE__, __LINE__, 'Class Template: Cannot assign Variable \'' . $name . '\'. There is no file or block associated.');
            }
        }

        /**
         * Erstellt automatisch dynamische Bloecke mit den uebergebenen Datensaetzen.
         * Das Array muss wie folgt aufgebaut werden: $array[$laufender_record_index][$varname] = $value !
         *
         * @param string $blockHandle Handle-Name des Blocks
         * @param array $recordset
         * @return Template
         */
        public function assignRecordset(string $blockHandle, array $recordset = []): Template
        {
            $count = count($recordset);
            if($count == 0) {
                return $this;
            }

            for($i = 0; $i < $count; $i++) {
                $record = $recordset[$i];

                if($this->newBlock($blockHandle)) {
                    $this->assignVar($record);
                }
            }
            $this->leaveBlock();

            return $this;
        }

        /**
         * Synonym auf die Template Funktion Template::assignRecordset()
         *
         * @access public
         * @param string $blockhandle Handle-Name des Blocks
         * @param array $redordset Datensaetze (z.B. aus einem MySQL Ergebnis)
         * @see Template::AssignRecordset()
         */
        function setRecordset($blockhandle, $recordset = array())
        {
            $this->assignRecordset($blockhandle, $recordset);
        }

        /**
         * Die Prozedur veranlasst, dass alle Dateien (Template Elemente) rekursiv geparst werden.
         *
         * @access public
         * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet)
         */
        function parse($handle = '')
        {
            if($handle != '') {
                $this->useFile($handle);
            }
            if($this->ActiveFile) {
                $this->ActiveFile->parse();
            }
            return $this;
        }

        /**
         * Liefert den Inhalt aller geparsten Dateien, Includes, Variablen und Bloecken!
         *
         * @access public
         * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet!)
         * @return string Inhalt
         */
        function getContent(string $handle = ''): string
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
         * @access public
         * @param string $handle Handle-Name eines Files (bei Nicht-Angabe wird das Default File verwendet!)
         **/
        function clear($handle = '')
        {
            if($handle != '') {
                $this->useFile($handle);
            }

            if(@is_a($this->ActiveFile, 'TempFile')) {
                $TempFile = &$this->ActiveFile;
                $TempFile->clearParsedContent();
            }
        }

        /**
         * Setzt alle Werte zurueck (Loescht alle Files aus dem Buffer).
         *
         * @return
         **/
        function reset()
        {
            $this->ActiveHandle = '';
            unset($this->ActiveFile);
            unset($this->ActiveBlock);
            $this->FileList = array();
        }
    }
}