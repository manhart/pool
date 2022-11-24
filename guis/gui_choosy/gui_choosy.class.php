<?php
/**
 * Auswahlliste
 *
 * Class GUI_Choosy ist eine Auswahllisten - Komponente. Man kann sie auf einer HTML Seite einbinden
 * oder auch als POPUP verwenden. Als POPUP hat man zwei Moeglichkeiten. Entweder man ruft das
 * GUI_Choosy mit Parameter ueber URL z.B. window.open('run.php?module=GUI_Choosy&transfer=transfer_by_db')
 * auf oder man bettet GUI_Choosy in ein vorbereitetes eigenes GUI (z.B: GUI_Auswahlliste_Lager) ein.
 * Die zweite Moeglichkeit erlaubt eine bessere Steuerung der Auswahlliste GUI_Choosy. Denn man hat
 * ueber das eigene GUI direkten Kontakt zur eingebetten Auswahlliste und muss nicht alles umstaendlich
 * ueber URL Parameter uebergeben.
 *
 * Wichtigster Parameter ist:
 *
 * - transfer: Bestimmt die Datenuebertragung ueber Input und Output (per Datei, Datenkbank, etc.)
 *
 * Fuer "transfer" gibt es folgende Konstanten:
 *
 * - CHOOSY_TRANSFER_FILE : Uebertragung findet per Datei statt
 * - CHOOSY_TRANSFER_DBCHOOSY : Uebertragung findet komplett ueber eine Datenbank statt (wird ueber eine eigene Tabelle abgehandelt, Spalten: input, output; siehe dazu auch DAO's Intranet\tbl_choosy.class.php)
 * - CHOOSY_TRANSFER_DB : Keine Input - Uebertragung. GUI_Choosy nimmt die Daten selbst aus der Datenbank und legt das Auswahlergebnis in einer temporaeren Datei ab.
 *
 * Alle weiteren Parameter werden in der Funktion "init" beschrieben.
 *
 * Verwendung:
 *
 * <code>
 * // DAO fuer eine beliebige Tabelle:
 * $DAO = DAO::createDAO($interfaces, 'xxxx');
 *
 * //
 * $DAO->setColumns('id', 'lagername');
 * $Set = $DAO->getMultiple(null, null, array(array('id', 'equal', $id)));
 * $ChoosySet = &$DAO_Choosy->insertRowset($name_set, $Set->getRowset(), '');
 *
 * $idChoosy = $ChoosySet->getValue('idChoosy');
 *
 * $Url = new Url();
 * $Url->modifyParam(
 *   array(
 *     'module' => 'GUI_Choosy',
 *     'transfer' => constant('CHOOSY_TRANSFER_DBCHOOSY'),
 *     'tabledefine' => 'xxxChoosyTable',
 *     'tableid' => $idchoosy,
 *     'primarykeys' => 'id',
 *
 *     'displayfields' => 'id;lagername',
 *     'shortenlength' => '0;25',
 *
 *     'enableSearchbar' => 1,
 *     'searchfields' => 'lagername',
 *
 *     'reloadOpenerBeforeClose' => 0,
 *
 *     'quickselect' => 1
 *   )
 * );
 * $Template->setVar('URL_AUSWAHLLISTE_2', $Url->getUrl());
 * </code>
 *
 *
 * @version $Id: gui_choosy.class.php,v 1.15 2006/12/29 12:22:03 aziz Exp $
 * @version $Revision: 1.15 $
 *
 * @since 2004-09-20
 * @package pool
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 * @see GUI_Choosy::init()
 */

define('CHOOSY_TRANSFER_FILE', 'transfer_by_file');
define('CHOOSY_TRANSFER_DBCHOOSY', 'transfer_by_db_choosy');
define('CHOOSY_TRANSFER_DB', 'transfer_by_db');

/**
 * GUI_Choosy
 *
 * Class GUI_Choosy ist eine Auswahllisten - Komponente. Man kann sie auf einer HTML Seite einbinden
 * oder auch als POPUP verwenden. Als POPUP hat man zwei Moeglichkeiten. Entweder man ruft das
 * GUI_Choosy mit Parameter ueber URL z.B. window.open('run.php?module=GUI_Choosy&transfer=transfer_by_db')
 * auf oder man bettet GUI_Choosy in ein vorbereitetes eigenes GUI (z.B: GUI_Auswahlliste_Lager) ein.
 * Die zweite Moeglichkeit erlaubt eine bessere Steuerung der Auswahlliste GUI_Choosy. Denn man hat
 * ueber das eigene GUI direkten Kontakt zur eingebetten Auswahlliste und muss nicht alles umstaendlich
 * ueber URL Parameter uebergeben.
 *
 * Wichtigster Parameter ist:
 *
 * - transfer: Bestimmt die Datenuebertragung von Input und Output (per Datei, Datenkbank, etc.)
 *
 * Fuer "transfer" gibt es folgende Konstanten:
 *
 * - CHOOSY_TRANSFER_FILE : Uebertragung findet per Datei statt
 * - CHOOSY_TRANSFER_DBCHOOSY : Uebertragung findet per Datenbank statt (wird ueber eine eigene Tabelle abgehandelt)
 * - CHOOSY_TRANSFER_DB : Keine Input - Uebertragung. GUI_Choosy nimmt die Daten selbst aus der Datenbank und legt das Auswahlergebnis in einer temporaeren Datei ab.
 *
 * Alle weiteren Parameter werden in der Funktion "init" beschrieben.
 *
 * Beispiel in der Dateibeschreibung (Methode: Url Uebergabe). Weitere Beispiele im "examples" Verzeichnis.
 *
 * @package pool
 * @author manhart
 * @copyright Copyright (c) 2004
 * @version $Id: gui_choosy.class.php,v 1.15 2006/12/29 12:22:03 aziz Exp $
 * @access public
 * @see GUI_Choosy::init()
 **/
class GUI_Choosy extends GUI_Module
{
    /**
     * @var bool
     */
    protected bool $autoLoadFiles = false;

    //@var boolean Merker, ob das GUI_DHtmlHint verwendet werden kann (ist abhaengig vom POOL)
    //@access private
    var $enable_GUI_DHtmlHint = false;

    public function __construct($Owner, array $params = [])
    {
        parent::__construct($Owner, $params);
    }

    /**
     * Initialisiert Standardwerte. Uebernimmt Daten kommend von INPUT_REQUEST (GET, POST, COOKIE).
     *
     * Steuerparameter:
     *
     * - transfer: Bestimmt die Datenuebertragung von Input und Output (per Datei, Datenkbank, etc.)
     * - input: Quelldatei mit Liste im CSV Format (nur bei transfer: CHOOSY_TRANSFER_FILE)
     * - output: Zieldatei mit gespeicherter Auswahl im CSV Format (enthaelt nur Primaerschluessel, nur bei transfer: CHOOSY_TRANSFER_FILE und CHOOSY_TRANSFER_DB)
     * - tabledefine: Tabellendefinition gibt die zu lesende Tabelle an (nur bei Transfer CHOOSY_TRANSFER_DB und CHOOSY_TRANSFER_DBCHOOSY). Siehe database.inc.php fuer Tabellendefinitionen!
     * - tableid: Datensatz-ID (nur bei CHOOSY_TRANSFER_DBCHOOSY).
     * - primarykeys: Primaerschluessel bei allen "transfer" Modis notwendig!
     * - separator: Trenner, Default ist ; (Semikolon). Dient zur Trennung der Felder z.B. Parameter displayfields, shortenlength, rowClass und weiteren Parametern. Typ: string[1].
     *
     * - maxRecordsPerPage: Maximale Anzahl Datensaetze pro Seite (in Verbindung mit Seitennavigation bzw. Splitterbar relevant), Default ist 10. Typ: integer.
     * - splitterPos: Position an der zu Lesen begonnen werden soll, Default ist 0 (d.h. von Anfang an). Typ: integer.
     * - defaultSortfield: Bestimmt wonach standardmaessig sortiert werden soll (derzeit nur bei Transfer CHOOSY_TRANSFER_DB).
     * - defaultSortorder: Bestimmt die Sortierreihenfolge, Default ist ASC. Moegliche Werte: ASC und DESC. Typ: enum.
     *
     * - multiple: Bestimmt Einfach- oder Mehrfachauswahl. Default ist 0 (Einfachauswahl). Typ: boolean.
     * - selmode: Bestimmt die Art der Auswahl. Moegliche Werte: checkbox und radiobutton. Typ: enum.
     * - filter: Filter fuer Datenbankanfrage. Parameter kann nicht per Url uebergeben werden, da von Typ array!! (nur bei Transfer CHOOSY_TRANSFER_DB). Typ: array
     *
     * - displayfields: Angezeigte Felder. Default ist *. Typ: text. (Trenner siehe Parameter separator)
     * - shortenlength: Kuerzt Felder, sobald sie die uebergebene Laenge ueberschreiten. Typ: text. (Trenner siehe Parameter separator)
     * - shortenmore: Bei gekuerzten Feldern ein Sufix anzeigen. Default ist ... .
     * - colalign: Richtet Spalten aus (muss im Template als {align} angegeben werden). Default ist left. Moegliche Werte: left, right, justify, char, center. Typ enum.
     * - quickselect: Formular nach Einfachauswahl absenden. Aktiviert man zusaetzlich den Parameter closeWindow, schliesst sich das Fenster. Default ist 0. Typ: boolean.
     *
     * - enableSearchbar: Suchleiste aktivieren. Default ist 0. Typ: boolean.
     * - customSearchFuntion: benutzerdefinierte Suchfunktion
     * - searchfields: Suchfelder. Typ: text. (Trenner siehe Parameter separator)
     * - submitsearch: Flag, ob Suchanfrage abgeschickt wurde. Default ist 0. Typ: boolean.
     * - suchbegriff: Suchbegriff. Typ: text.
     * - enableSplitterbar: Seitennavigation bzw. Splitter aktivieren. Default ist 1 (aktiviert). Typ: boolean.
     * - enableScrollbox: Aktiviert Scrollbox. Default ist 0 (deaktiviert). Typ: boolean.
     * - scrollBoxwidth: Setzt die Breite der Scrollbox in Pixel (Prozentangaben moeglich)
     * - scrollBoxheight: Setzt die Hoehe der Scrollbox in Pixel (kein %!; da die Hoehe per Javascript zum Scrollen berechnet wird)
     * - frameTitle: Seitentitel (nur moeglich, wenn das GUI innerhalb eines GUI_CustomFrame eingepflanzt wurde). Typ: text.
     * - fileTemplateHTML: Zu ladendes HTML Template. Default ist tpl_choosy.html.
     *
     * - reloadOpenerBeforeClose: Laedt Opener (per JavaScript) neu, bevor das Fenster geschlossen wird. Default ist 0. Typ: boolean.
     * - closeWindow: Schliesst das Fenster nachdem Abschicken der Daten (d.h. beim Ok Klick und falls Quickselect aktiviert wurde). Default ist 1. Typ: boolean.
     * - rowClass: Bestimmt die Stylesheet Klasse fuer eine Zeile. Kann Abwechselnd bestimmt werden. Default ist leer. Typ: string oder array (string wird getrennt, siehe Parameter "separator").
     * - watchFieldValue: beruecksichtigt die Wertaenderung eines Feldes und verwendet entsprechend eine Stylesheet Klasse.
     * - fieldNameStyleAhead: Spalte mit Style Angaben zu einer Textspalte (vorne einzufuegender Style)
     * - fieldNameStyleRear: Spalte mit Style Angaben zu einer Textspalte (hinten einzufuegender Style)
     * - user_function: Bei CHOOSY_TRANSFER_DB kann eine benutzerdefinierte DAO Funktion uebergeben werden, statt getMultiple! (Parameter user_function_count NOTWENDIG!)
     * - user_function_count: Bei CHOOSY_TRANSFER_DB kann eine benutzerdefinierte DAO Funktion uebergeben werden, statt getCount!
     *
     * @access public
     **/
    function init(?int $superglobals = I_EMPTY)
    {
        $this->Defaults->addVar(
            array(
                'transfer' => '',
                'input' => '',
                'output' => '',            // + Zwischenspeicher fuer Auswahl
                'tabledefine' => '',
                'tableid' => null,
                'primarykeys' => '',
                'separator' => ';',

                'maxRecordsPerPage' => 10,
                'splitterPos' => 0,
                'defaultSortfield' => '',
                'defaultSortorder' => 'ASC',
                'multiple' => 0,
                'selmode' => 'checkbox',
                'filter' => array(),

                'displayfields' => '*',
                'shortenlength' => '',
                'shortenmore' => '...',
                'colalign' => '',
                'quickselect' => 0,

                'enableSearchbar' => 0,
                'customSearchFunction' => array($this, 'custom_array_filter'),
                'searchfields' => '',
                'submitsearch' => 0,
                'suchbegriff' => '',

                'enableSplitterbar' => 1,

                'enableScrollbox' => 0,
                'scrollBoxheight' => '100',
                'scrollBoxwidth' => '100%',

                'frameTitle' => '',
                'fileTemplateHTML' => 'tpl_choosy.html',

                'reloadOpenerBeforeClose' => 0,
                'closeWindow' => 1,

                'rowClass' => array(),
                'watchFieldValue' => false,
                'fieldNameTextClass' => 'textClass_',

                'user_function' => false,
                'user_function_count' => false,

                /* private */
                'submitFChoosy' => 0
            )
        );
        parent:: init(I_REQUEST);
    }

    function loadFiles()
    {
        $file = $this->Weblication->findTemplate($this->Input->getVar('fileTemplateHTML'), $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);

        $jsfile = $this->Weblication->findJavaScript('dhtmlhint.js', '', true);
        $cssfile = @$this->Weblication->findStyleSheet($this->getClassName() . '.css', $this->getClassName());

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->getHead()->addJavaScript($jsfile);
            $this->enable_GUI_DHtmlHint = true;
            if($cssfile) {
                $this->Weblication->getFrame()->getHead()->addStyleSheet($cssfile);
            }
        }
        else {
            // add needed files manual
            if($cssfile) {
                $this->Template->newBlock('STYLESHEET');
                $this->Template->setVar('FILENAME', $cssfile);
            }
            $this->Template->leaveBlock();
        }
    }

    /**
     * Die eigentlich Arbeit wird verrichtet. Parameterauswertung, Platzhalter ersetzt...
     *
     * @access public
     */
    function prepare()
    {
        $this->loadFiles();

        $Template = &$this->Template;

        $Input = &$this->Input;
        $interfaces = $this->Weblication->getInterfaces();

        //echo pray($Input -> Vars);

        #### Frame Titel
        if($this->Weblication->hasFrame()) {
            $Frame = $this->Weblication->getFrame();
            $Frame->getHead()->setTitle($Input->getVar('frameTitle'));
        }

        #### Auswahl gespeichert = okClick - Url->reload() - windowClose()
        if($Input->getVar('closeWindow') == 1 and $Input->getVar('saved') == 1) {
            if($Input->getVar('reloadOpenerBeforeClose') == 1) $Template->newBlock('reloadOpener');
            $Template->newBlock('closeWindow');
            $Template->leaveBlock();
        }

        #### Instanziere GUI_Shorten (kuerzt Textausgaben)
        $GUI_Shorten = new GUI_Shorten($this->getOwner());
        $GUI_Shorten->loadFiles();

        #### Hauptargumente
        $transfer = $Input->getVar('transfer');
        $tabledefine = $Input->getVar('tabledefine');
        $primarykeys = $Input->getVar('primarykeys');
        $input = $Input->getVar('input');
        $output = $Input->getVar('output');
        $separator = $Input->getVar('separator');
        $multiple = (int)$Input->getVar('multiple');

        if(empty($primarykeys)) {
            $this->raiseError(__FILE__, __LINE__, 'Der Parameter "primarykeys" (Prim�rschl�ssel) wurde nicht �bergeben! ' .
                'Der Prim�rschl�ssel ist notwendig und bestimmt die R�ckgabewerte der Auswahlliste.');
        }


        #### Weitere Parameter:
        $splitterPos = $Input->getVar('splitterPos'); // private
        $maxRecordsPerPage = $Input->getVar('maxRecordsPerPage');

        $quickselect = (int)$Input->getVar('quickselect');

        $enableSearchbar = $Input->getVar('enableSearchbar');
        $searchfields = explode($separator, $Input->getVar('searchfields'));

        $enableSplitterbar = $Input->getVar('enableSplitterbar');

        $rowClass = $Input->getVar('rowClass');
        $watchFieldValue = $Input->getVar('watchFieldValue');

        $user_function = $Input->getVar('user_function');
        $user_function_count = $Input->getVar('user_function_count');

        if(!$Input->emptyVar('colalign') and (!is_array($Input->getVar('colalign')))) {
            $colalign = explode($separator, $Input->getVar('colalign'));
        }
        else {
            $colalign = array();
        }


        if(!is_array($rowClass)) $rowClass = explode($separator, $rowClass);

        #### Suche
        if($Input->getVar('submitsearch') == 1) {
            $Url = new Url();
            $Url->setParam('splitterPos', 0);
            $Url->setParam('suchbegriff', $Input->getVar('suchbegriff'));
            $Url->restartUrl();
        }

        #### enable Searchbar
        if($enableSearchbar) {
            $Template->newBlock('searchBar');
            $Template->leaveBlock();
        }

        #### enable Splitterbar
        if($enableSplitterbar) {
            $Template->newBlock('splitterBar');
            $Template->leaveBlock();
        }

        #### Daten einlesen:
        #### $selectionlines, $list, $numRecords
        switch($transfer) {
            case CHOOSY_TRANSFER_DB:
                if($tabledefine) {
                    #### input from daos
                    $filter = $Input->getVar('filter');
                    $this->getSQLSearchfilter($filter, $searchfields);
                    $sorting = ($Input->getVar('defaultSortfield')) ? array($Input->getVar('defaultSortfield') => $Input->getVar('defaultSortorder')) : array();
                    $limit = array($splitterPos, $maxRecordsPerPage);

                    $DAO = DAO::createDAO($interfaces, $tabledefine);
                    // $DAO -> enableDebugging();
                    // $DAO -> setColumnsAsString($Input -> getVar('searchfields'), ';');
                    if($user_function != false) {
                        $Resultset = &$DAO->$user_function(null, null, $filter, $sorting, $limit);
                        $Resultset_count = &$DAO->$user_function_count(null, null, $filter);
                    }
                    else {
                        $Resultset = $DAO->getMultiple(null, null, $filter, $sorting, $limit);
                        $Resultset_count = $DAO->getCount(null, null, $filter);
                    }
                    $list = $Resultset->getRowset();


                    $numRecords = $Resultset_count->getValue('count');

                    #### Output (gespeicherte Auswahl) laden
                    if(file_exists($output)) {
                        $selectionlines = file($output);
                        // echo '<br>ganz am anfang: ' . pray($selectionlines);
                        $key_line = trim(array_shift($selectionlines));

                        $keys = explode($separator, $key_line);
                        $selection = array();
                        foreach($selectionlines as $line) {
                            $line = trim($line);
                            if(empty($line)) {
                                continue;
                            }
                            array_push($selection, explode($separator, $line));
                            $new_selectionlines[] = trim($line);
                        }
                        $selectionlines = $new_selectionlines;
                    }
                    else {
                        $selectionlines = array();
                    }
                }
                break;

            case CHOOSY_TRANSFER_DBCHOOSY:
                $tableid = $Input->getVar('tableid');
                if($tableid == '') die('no tableid!!');
                $DAO_Choosy = DAO::createDAO($interfaces, $tabledefine);
                $Resultset_Choosy = $DAO_Choosy->get($tableid);

                $input_as_sql = $Resultset_Choosy->getValue('input_as_sql');
                if($input_as_sql == 1) {
                    $database = $Input->getVar('database');
                    $limit = array($splitterPos, $maxRecordsPerPage);
                    $sql = $Resultset_Choosy->getValue('input');

                    $MySQL_Resultset = new MySQL_Resultset($interfaces[constant('DATAINTERFACE_MYSQL')]);

                    $this->getSQLSearchstring($sql, $searchfields);
                    $MySQL_Resultset->execute($sql, $database);
                    $hole_list = $MySQL_Resultset->getRowset();

                    $sql .= ' LIMIT ' . implode(', ', $limit);
                    $MySQL_Resultset->execute($sql, $database);

                    $list = $MySQL_Resultset->getRowset();
                    $numRecords = sizeof($hole_list);
                }
                else {
                    $rowset = unserialize($Resultset_Choosy->getValue('input'));

                    $list = $rowset;
                    $this->filterList($list, $searchfields);
                    $numRecords = sizeof($list);

                    if($maxRecordsPerPage > 0) {
                        $list = array_slice($list, $splitterPos, $maxRecordsPerPage);
                    }
                    #else $list=$rowset;
                }

                $selectionlines = explode("\n", $Resultset_Choosy->getValue('output'));
                $key_line = trim(array_shift($selectionlines));

                $keys = explode($separator, $key_line);
                $selection = array();
                foreach($selectionlines as $line) {
                    $line = trim($line);
                    if(empty($line)) {
                        continue;
                    }
                    array_push($selection, explode($separator, $line));
                    $new_selectionlines[] = trim($line);
                }
                $selectionlines = $new_selectionlines;
                break;

            case CHOOSY_TRANSFER_FILE:
                #### Output (gespeicherte Auswahl) laden
                if(file_exists($output)) {
                    $selectionlines = file($output);
                    // echo '<br>ganz am anfang: ' . pray($selectionlines);
                    $key_line = trim(array_shift($selectionlines));

                    $keys = explode($separator, $key_line);
                    $selection = array();
                    foreach($selectionlines as $line) {
                        $line = trim($line);
                        if(empty($line)) {
                            continue;
                        }
                        array_push($selection, explode($separator, $line));
                        $new_selectionlines[] = trim($line);
                    }
                    $selectionlines = $new_selectionlines;
                }
                else {
                    $selectionlines = array();
                }

                #### input from input
                $lines = file($input);
                $columns = array_shift($lines);
                break;
        }

        /* wichtig variable: list */

        #### ok - Auswahlliste speichern
        if(!is_array($selectionlines)) $selectionlines = array();
        if($Input->getVar('submitFChoosy') == 1) {
            $auswahl = $Input->getVar('auswahl');

            //				echo 'auswahl: ' . pray($auswahl);
            #### Radiobutton; Auswahl nicht als Array
            if(!is_array($auswahl)) $auswahl = array($auswahl);

            //if (is_array($auswahl) and count($auswahl)) {
            $saved_list = explode("\n", trim($Input->getVar('saved_list')));

            foreach($saved_list as $pkstring) {
                $pkstring = trim($pkstring);

                if(empty($pkstring)) {
                    continue;
                }

                #### INSERT
                if(is_array($auswahl) and in_array($pkstring, $auswahl)) {
                    if(!$multiple) $selectionlines = array();
                    if(!in_array($pkstring, $selectionlines)) {
                        $selectionlines[] = $pkstring;
                    }
                }
                #### DELETE
                else {
                    $key = array_search($pkstring, $selectionlines, false);
                    if($key !== false) {
                        unset($selectionlines[$key]);
                    }
                }
            }

            #### Kopfzeile eintragen (Primaerschluessel)
            array_unshift($selectionlines, $primarykeys);
            $selectionlines = array_values($selectionlines);

            #### Output schreiben (Auswahl speichern)
            switch($transfer) {
                case CHOOSY_TRANSFER_DB:
                case CHOOSY_TRANSFER_FILE:
                    $fhandle = fopen($output, 'w');
                    if($fhandle) {
                        fwrite($fhandle, trim(implode("\n", $selectionlines)));
                    }
                    @fclose($fhandle);
                    break;

                case CHOOSY_TRANSFER_DBCHOOSY:
                    $tableid = $Input->getVar('tableid');
                    $DAO_Choosy = DAO::createDAO($interfaces, $tabledefine);
                    $DAO_Choosy->update(array('idtbl_choosy' => $tableid,
                        'output' => trim(implode("\n", $selectionlines)),
                        'ready' => 1));
                    break;
            }

            $Url = new Url();
            $Url->setParam('splitterPos', $Input->getVar('splitterPos'));
            $Url->setParam('saved', $Input->getVar('submitok'));
            $Url->restartUrl();
        }

        #### Anzuzeigende Felder (*=alle):
        $displayfields = $Input->getVar('displayfields');
        if($displayfields != '*') {
            $displayfields = explode($separator, $displayfields);
        }
        else {
            $displayfields = array_keys($list[0]);
        }
        $count_displayfields = count($displayfields);
        $shortenlength = explode($separator, $Input->getVar('shortenlength'));

        #### Zaehler
        $z = 0;
        $w = 0;


        #### Auswahlliste erzeugen:
        $old_value = '';
        if(is_array($list)) {
            #### Datensaetze
            $saved_list = array();
            $count_keys = count($keys);
            foreach($list as $row) {
                #### Datensaetz ausgewaehlt?
                $selected = false;
                if(is_array($selection)) {
                    foreach($selection as $selvalues) {
                        for($k = 0; $k < $count_keys; $k++) {
                            $key = trim($keys[$k]);
                            if($row[$key] == trim($selvalues[$k])) {
                                $selected = true;
                            }
                            else {
                                $selected = false;
                                break 1;
                            }
                        }
                        if($selected) {
                            break 1;
                        }
                    }
                }


                $Template->newBlock('list_element');

                #### Zeile: StyleSheets
                if($watchFieldValue !== false) {
                    if($old_value != $row[$watchFieldValue]) {
                        $Template->setVar('rowClass', (is_array($rowClass) and count($rowClass)) ? $rowClass[$w % count($rowClass)] : '');
                        $old_value = $row[$watchFieldValue];
                        $w++;
                    }
                }
                else {
                    $Template->setVar('rowClass', (is_array($rowClass) and count($rowClass)) ? $rowClass[$z % count($rowClass)] : '');
                }

                for($i = 0; $i < $count_displayfields; $i++) {

                    $text_r = '';
                    $Template->newBlock('columns');
                    if($shortenlength[$i] > 0) {
                        $GUI_Shorten->Input->setVar(
                            array(
                                'text' => $row[$displayfields[$i]],
                                'len' => $shortenlength[$i],
                                'more' => $Input->getVar('shortenmore'),
                                'hint' => $this->enable_GUI_DHtmlHint
                            )
                        );

                        $GUI_Shorten->prepareContent();
                        $text = $GUI_Shorten->finalizeContent();
                    }
                    else {
                        if($selected) {
                            $text = $row[$displayfields[$i]];
                        }
                        else {
                            $text = $row[$displayfields[$i]];
                        }
                    }
                    $text_r = $row[$displayfields[$i]];
                    #### Text: Stylesheet
                    $textClass = '';
                    $fieldNameTextClass = $Input->getVar('fieldNameTextClass') . $displayfields[$i];
                    if(key_exists($fieldNameTextClass, $row)) {
                        $textClass = $row[$fieldNameTextClass];
                    }
                    $Template->setVar(
                        array(
                            'text' => $text,
                            'index' => $z,
                            'align' => isset($colalign[$i]) ? $colalign[$i] : 'left',
                            'textClass' => $textClass
                        )
                    );
                }
                #### selmode (checkbox, radiobutton, ...)
                $value = $this->getPKString($row);
                $Template->newBlock($Input->getVar('selmode'));
                $Template->setVar(
                    array(
                        'text_r' => $text_r,
                        'checked' => ($selected) ? '1' : '0',
                        'value' => $value,
                        'index' => $z
                    )
                );
                $saved_list[] = $value;

                $z++;
            }
        }
        $Template->leaveBlock();

        $Template->setVar(
            array(
                'saved_list' => @implode("\n", $saved_list),
                'multiple' => $multiple,
                'quickselect' => $quickselect
            )
        );

        #### GUI_Splitter Parameter:
        $this->addHandOffVar(
            array(
                'maxRecordsPerPage' => $maxRecordsPerPage,
                'numRecords' => $numRecords
            )
        );
    }

    /**
     * GUI_Choosy::getSQLSearchfilter()
     *
     * Erstellt Filter fuer Suchanfrage. (nur CHOOSY_TRANSFER_DB)
     *
     * @access private
     * @param array $filter Filterung (Referenzwert)
     * @param string $searchfields Suchfelder
     **/
    function getSQLSearchfilter(&$filter, $searchfields)
    {
        $suchbegriff = $this->Input->getVar('suchbegriff');
        if(empty($suchbegriff)) return;

        $merker = false;
        $jump = false;
        if(count($filter)) {
            array_push($filter, 'and');
            array_push($filter, '(');
            $merker = true;
            $jump = true;
        }
        foreach($searchfields as $fieldname) {
            if(count($filter)) {
                if($jump) {
                    $jump = false;
                }
                else {
                    array_push($filter, 'or');
                }
            }
            array_push($filter, array($fieldname, 'like', '%' . str_replace('*', '%', $suchbegriff) . '%'));
        }
        if($merker) {
            array_push($filter, ')');
        }
    }

    /**
     * GUI_Choosy::getSQLSearchstring()
     *
     * Erstellt SQL Filter fuer Suchanfrage. (nur CHOOSY_TRANSFER_DBCHOOSY, falls SQL uebergeben wurde)
     *
     * @access private
     * @param string $string SQL Statement ohne "order by" (Referenzwert)
     * @param string $searchfields Suchfelder
     **/
    function getSQLSearchstring(&$string, $searchfields)
    {
        $suchbegriff = $this->Input->getVar('suchbegriff');
        if(empty($suchbegriff)) return;

        if(strpos($string, 'where') !== false) {
            $string .= ' and ';
            $string .= '(';
            $merker = true;
            $jump = true;
        }
        else {
            $string .= ' where ';
            $jump = true;
            $merker = false;
        }
        foreach($searchfields as $fieldname) {
            if(strlen($string) > 0) {
                if($jump) {
                    $jump = false;
                }
                else {
                    $string .= ' or ';
                }
            }
            $string .= $fieldname . ' like "' . '%' . str_replace('*', '%', $suchbegriff) . '%" ';
        }
        if($merker) {
            $string .= ')';
        }
    }

    /**
     * Erstellt Array-Filter bei Suchanfrage (nur CHOOSY_TRANSFER_DBCHOOSY, falls Arrayliste uebergeben wurde)
     *
     * @access private
     * @param array $list Liste
     * @param string $searchfields Suchfelder
     **/
    function filterList(&$list, $searchfields)
    {
        $suchbegriff = $this->Input->getVar('suchbegriff');
        $customSearchFunction = $this->Input->getVar('customSearchFunction');
        if($suchbegriff) {
            $this->temp_searchfields = $searchfields;
            $list = array_filter($list, $customSearchFunction);
            unset($this->temp_searchfields);
        }
    }

    /**
     * Hilfsfunktion fuer GUI_Choosy::filterList().
     *
     * @access private
     * @param array $row Datensatz
     * @return boolean Gefiltert? 1=ja, 0=nein.
     **/
    function custom_array_filter($row)
    {
        $bResult = 0;
        $suchbegriff = strtolower($this->Input->getVar('suchbegriff'));
        foreach($this->temp_searchfields as $fieldname) {
            $value = strtolower($row[$fieldname]);
            $bResult = (strpos($value, $suchbegriff) !== false);
        }
        return $bResult;
    }

    /**
     * GUI_Choosy::getPKString()
     *
     * Setzt Primaerschluessel zusammen. Uebergabe ist ein Datensatz. Rueckgabewert der Primaerschluessel getrennt als ganzer String.
     *
     * @param array $row Datensatz
     * @return string Primaerschluessel
     **/
    function getPKString($row)
    {
        $separator = $this->Input->getVar('separator');

        $primarykeys = explode($separator, $this->Input->getVar('primarykeys'));
        $value = '';
        foreach($primarykeys as $pk) {
            if($value != '') {
                $value .= $separator;
            }
            $value .= $row[$pk];
        }

        return $value;
    }

    /**
     * GUI_Choosy::finalize()
     *
     * Template parsen und fertigen Inhalt an das naechste GUI weiterreichen.
     *
     * @return string fertiger Content
     **/
    function finalize(): string
    {
        $this->Template->parse('stdout');
        $content = $this->reviveChildGUIs($this->Template->getContent('stdout'));

        $enableScrollbox = $this->Input->getVar('enableScrollbox');
        if($enableScrollbox == 1) {
            $Scrollbox = new GUI_Scrollbox($this->getOwner());
            $Scrollbox->Input->setVar(
                array(
                    'boxwidth' => $this->Input->getVar('scrollBoxwidth'),
                    'boxheight' => $this->Input->getVar('scrollBoxheight'),
                    'gapheight' => 0
                )
            );

            $Scrollbox->prepareContent();
            return $Scrollbox->finalize($content);
        }
        else {
            return $content;
        }
    }
}