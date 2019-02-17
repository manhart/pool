<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * GUI_DBGrid.class.php
 *
 * $Log$
 *
 * @version $Id Exp $
 * @version $Revision$
 * @version
 *
 * @since 2006/12/05
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBGrid
 *
 * Siehe Datei fuer ausfuehrliche Beschreibung!.
 *
 * @package auftragserfassung
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @version $Id: GUI_Liste.class.php,v 1.4 2006/11/27 11:45:19 manhart Exp $
 * @access public
 **/
class GUI_DBGrid extends GUI_Module
{
    /**
     * OnSetCell - Ereignis tritt vor dem F�llen der Zelle ein
     *
     * @var string
     */
    var $onSetCell=null;

    /**
     * OnSetRow - Ereignis tritt vor dem F�llen der Zeile ein
     *
     * @var string
     */
    var $onSetRow=null;

    /**
     * Count - Anzahl Datens�tze
     *
     * @var int
     */
    var $numRecords=0;

    /**
     * Erster Datensatz der Liste
     *
     * @var array
     */
    var $firstRecord=array();

    /**
     * Letzter Datensatz der Liste
     *
     * @var array
     */
    var $lastRecord=array();

    /**
     * Aktuell bearbeiteter Datensatz der Liste
     *
     * @var array
     */
    var $activeRecord=array();

    /**
     * Resultset
     *
     * @var Resultset
     */
    var $Resultset = null;

    /**
     * Style-Class fuer eine Tabellenzelle; kann in setCell ueberschrieben werden
     *
     * @var string
     */
    var $colClass = '';

    /**
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer vom Typ Component
     * @see Component
     **/
    function GUI_DBGrid(& $Owner)
    {
        parent::GUI_Module($Owner, true);
    }

    /**
     * Initialisierung der Standard Werte und Superglobals.
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar('DynToolTipFiltersEnabled', 1);
        $this->Defaults->addVar('submittedVerteiltermin', 0); # abgesendete Suche
        $this->Defaults->addVar('maxRecordsPerPage', 20);
        $this->Defaults->addVar('disableLimit', 0);

        $this -> Defaults -> addVar('name', 'list');

        $this -> Defaults -> addVar('colNames', '');
        $this->Defaults->addVar('colSortNames', '');
        $this -> Defaults -> addVar('colTitles', '');
        $this -> Defaults -> addVar('colWidths', '15;;;90');
        $this->Defaults->addVar('colClasses', '');
        $this -> Defaults -> addVar('colTitleAligns', '');
        $this -> Defaults -> addVar('colAligns', '');
        $this -> Defaults -> addVar('colTypes', '');
        $this -> Defaults -> addVar('colFormats', '');
        /*$this -> Defaults -> addVar('datasource', '');*/
        $this -> Defaults -> addVar('shorten', '');
        $this -> Defaults -> addVar('tabledefine', '');
        $this -> Defaults -> addVar('filter_rules', null);
        $this -> Defaults -> addVar('selectedIdentValue', '');
        $this -> Defaults -> addVar('multipleFunction', 'getMultiple');
        $this -> Defaults -> addVar('countFunction', 'getCount');
        $this -> Defaults -> addVar('template', 'tpl_dbgrid.html');
        $this -> Defaults -> addVar('rowHeight', 16);
        $this -> Defaults -> addVar('colSeparator', ';');
        $this -> Defaults -> addVar('paramSeparator', ':');
        $this -> Defaults -> addVar('sortSuffix', '');
        $this -> Defaults -> addVar('sortOrder', '');
        $this -> Defaults -> addVar('sortField', '');
        $this -> Defaults -> addVar('lastSortOrder');
        $this -> Defaults -> addVar('lastSortField');
        $this -> Defaults -> addVar('classActiveSortLink');
        $this -> Defaults -> addVar('classInactiveSortLink');
        $this -> Defaults -> addVar('primaryKey', array());
        $this -> Defaults -> addVar('resultset', null);

        parent :: init(I_GET|I_POST);
    }

    /**
     * Laden der Html Templates.
     *
     * @access public
     **/
    function loadFiles()
    {
        $Input = &$this -> Input;

        $template = $Input -> getVar('template');
        if(!file_exists($template)) {
            $file = $this -> Weblication -> findTemplate($template, $this -> getClassName(), true);
        }
        else {
            $file = $template;
        }
        $this -> Template -> setFilePath('frame', $file);
    }

    /**
     * Aufbereiten der Html Templates.
     *
     * @access public
     **/
    function prepare()
    {
        $interfaces = & $this -> Weblication -> getInterfaces();

        $Weblication = & $this -> Weblication;
        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        //
        // DBSession
        $progSID = $Input -> getVar('progSID');
        $DBSession = new DBSession($interfaces, 'Intranet_tbl_Session', $progSID);

        //
        // Konfiguration
        $name = $this->getName();
        $splitterPosName = $name . 'SplitterPos';
        $tabledefine = $Input -> getVar('tabledefine');
        $multipleFunction = $Input -> getVar('multipleFunction');
        $countFunction = $Input -> getVar('countFunction');
        $splitterPos = (int)$Input -> getVar($splitterPosName);
        $maxRecordsPerPage = $Input -> getVar('maxRecordsPerPage');
        $colNames = $Input -> getVar('colNames');
        $colSortNames = $this->getVar('colSortNames');
        $colTitles = $Input -> getVar('colTitles');
        $colWidths = $Input -> getVar('colWidths');
        $colClasses = $this->getVar('colClasses');
        $colTitleAligns = $Input -> getVar('colTitleAligns');
        $colAligns = $Input -> getVar('colAligns');
        $colTypes = $Input -> getVar('colTypes');
        $colFormats = $Input -> getVar('colFormats');
        $shorten = $Input -> getVar('shorten');
        $selectedIdentValue = $Input -> getVar('selectedIdentValue');
        $filter_rules = $Input -> getVar('filter_rules');
        $rowHeight = $Input -> getVar('rowHeight');
        $colSeparator = $Input -> getVar('colSeparator');
        $paramSeparator = $Input -> getVar('paramSeparator');
        $this -> sortSuffix = $Input -> getVar('sortSuffix');
        $this -> sortOrder = $Input -> getVar('sortOrder' . $this -> sortSuffix);
        $this -> sortField = $Input -> getVar('sortField' . $this -> sortSuffix);
        $lastSortOrder = $Input -> getVar('lastSortOrder');
        $lastSortField = $Input -> getVar('lastSortField');
        $defaultSortField = $Input -> getVar('defaultSortField');
        $defaultSortOrder = $Input -> getVar('defaultSortOrder');
        $pk = $Input -> getVar('primaryKey');
        $Resultset = $Input -> getVar('resultset');

        if($this -> sortField == '') $this -> sortField = $defaultSortField;
        if($this -> sortOrder == '') $this -> sortOrder = $defaultSortOrder;


        if($colNames and !is_array($colNames)) {
            $colNames = explode($colSeparator, $colNames);
        }

        if($colSortNames and !is_array($colSortNames)) {
            $colSortNames = explode($colSeparator, $colSortNames);
        }

        if($colTitles and !is_array($colTitles)) {
            $colTitles = explode($colSeparator, $colTitles);
        }

        if($colWidths and !is_array($colWidths)) {
            $colWidths = explode($colSeparator, $colWidths);
        }

        if($colClasses and !is_array($colClasses)) {
            $colClasses = explode($colSeparator, $colClasses);
        }

        if($colTitleAligns and !is_array($colTitleAligns)) {
            $colTitleAligns = explode($colSeparator, $colTitleAligns);
        }

        if($colAligns and !is_array($colAligns)) {
            $colAligns = explode($colSeparator, $colAligns);
        }

        if($colTypes and !is_array($colTypes)) {
            $colTypes = explode($colSeparator, $colTypes);
        }

        if($colFormats and !is_array($colFormats)) {
            $colFormats = explode($colSeparator, $colFormats);
        }

        if($pk and !is_array($pk)) {
            $pk = explode($colSeparator, $pk);
        }

        if($shorten and !is_array($shorten)) {
            $shorten = explode($colSeparator, $shorten);
        }

        if($Resultset==null) {
            $Dao = DAO::createDAO($interfaces, $tabledefine, true);
            if(count($pk) == 0) {
                $pk = $Dao -> getPrimaryKey();
            }
        }

        $identName = '';
        for($i=0; $i<count($pk); $i++) {
            if($identName != '') $identName .= ';';
            $identName .= $pk[$i];
        }
        // echo $identName;


        $sorting = array();
        if($this -> sortField) {
            $this -> sortOrder = ($this -> sortOrder != '') ? $this -> sortOrder : 'ASC';
            $sorting[$this -> sortField] = $this -> sortOrder;
        }

        // Limit
        $limit = array();
        if((int)$this->getVar('disableLimit') == 0) {
            if($maxRecordsPerPage) {
                $limit = array($maxRecordsPerPage);
                if($splitterPos) {
                    array_unshift($limit, $splitterPos);
                }
            }
        }

        if($Resultset == null) {
            $Set = $Dao->$multipleFunction(null, null, $filter_rules, $sorting, $limit);
            if($Set->getLastError()) {
                // Callback Funktion
            }

            /* @var $Set Resultset */
            $CountSet = $Dao->$countFunction(null, null, $filter_rules);
            $this->numRecords = (int)$CountSet->getValue('count');

            $numVisibleRecords = $Set->count();
        }
        else {
            $Set=$Resultset; /* @var $Set Resultset */
            $this->numRecords=$Resultset->count();
            $Set->seek($splitterPos);
            //$numVisibleRecords = $Set->count()-$splitterPos;
            // TODO disableLimit
            $numVisibleRecords = $maxRecordsPerPage;
            if($Set->count() < $numVisibleRecords) $numVisibleRecords = $Set->count();
            //echo 'numVisibleRecords: ' . $numVisibleRecords;
        }
        $this->Resultset = &$Set;

        $columns = $Set->getColumns();
        if(!$colNames) $colNames = $columns;

        // Header - Titel - �berschrift
        $c=-1;
        foreach ($colNames as $colName) {
            $c++;
            $colTitle = isset($colTitles[$c]) ? $colTitles[$c] : null;
            $colWidth = isset($colWidths[$c]) ? $colWidths[$c] : '';
            $colSortName = isset($colSortNames[$c]) ? $colSortNames[$c] : '';
            $colTitleAlign = isset($colTitleAligns[$c]) ? $colTitleAligns[$c] : '';
            if(!$colTitle) {
                $colTitle = ucfirst($colName);
            }
            $Template->newBlock('fixedCol');
            $Template->ActiveBlock->setVar(array(
                'colTitle' => $colTitle,
                'colWidth' => $colWidth,
                'colTitleAlign' => $colTitleAlign
            ));

            $this->generateSortLink($colSortName ? $colSortName : $colName);
        }

        // Optimierungen, 21.4.09, AM
        $DAO_cache = array();

        #spuckZeitaus();

        $rowNr = 1;
        if($numVisibleRecords) {
            $row = $Set->getRow();
            do {
                $this->activeRecord = $row; // Ablage (Puffer)

                // Prim�r Schl�ssel
                $identValue = '';
                foreach ($pk as $pkName) {
                    $pkValue = isset($row[$pkName]) ? $row[$pkName] : 0;
                    if($identValue != '') $identValue .= ';';
                    $identValue .= $pkValue;
                }

                $selected = bool2int($identValue == $selectedIdentValue);

                $Template->newBlock('row');
                $Template->ActiveBlock->setVar(array(
                    'rowNr' => $rowNr,
                    'rowHeight' => $rowHeight,
                    'modulus' => ($rowNr%2),
                    'identValue' => $identValue
                ));

                if(is_array($this->onSetRow)) {
                    eval("\$cellValue=\$this->onSetRow[0]->".$this->onSetRow[1]."(\$this, $rowNr, $selected);");
                }

                $c=-1;
                foreach ($colNames as $fieldName) {
                    ++$c;

                    $cellValue = isset($row[$fieldName]) ? $row[$fieldName] : '';
                    $cellValueOriginal = $cellValue;

                    $cellType = isset($colTypes[$c]) ? $colTypes[$c] : '';
                    $cellFormat = isset($colFormats[$c]) ? $colFormats[$c] : '';
                    $colAlign = isset($colAligns[$c]) ? $colAligns[$c] : '';
                    $colWidth = isset($colWidths[$c]) ? $colWidths[$c] : '';
                    $this->colClass = isset($colClass[$c]) ? $colClass[$c] : '';
                    $shortenValue = isset($shorten[$c]) ? $shorten[$c] : '';

                    switch($cellType) {
                        case 'string':
                        case 'float':
                        case 'double':
                            settype($cellValue, $cellType);
                            break;

                        case 'number':
                            if(!isset($cellFormat[0])) $cellFormat[0] = 2;
                            if(!isset($cellFormat[1])) $cellFormat[1] = ',';
                            if(!isset($cellFormat[2])) $cellFormat[2] = '.';

                            $cellValue = number_format(floatval($cellValue), $cellFormat[0], $cellFormat[1], $cellFormat[2]);
                            break;

                        case 'boolean':
                            settype($cellValue, $cellType);
                            $cellFormat = explode($paramSeparator, $cellFormat);
                            if(count($cellFormat) > 0) {
                                if(!$cellValue and isset($cellFormat[0])) $cellValue=$cellFormat[0];
                                else if($cellValue and isset($cellFormat[1])) $cellValue=$cellFormat[1];
                            }
                            else {
                                $cellValue = bool2string($cellValue);
                            }
                            break;

                        case 'int':
                        case 'integer': // number w�re besser gewesen fuer die formatierte ausgabe, AM, 16.6.09
                            settype($cellValue, $cellType);
                            $cellValue = number_format($cellValue, null, null, '.');
                            break;

                        case 'unix_timestamp':
                            if($cellFormat and ($cellValue > 0)) {
                                $cellValue = date($cellFormat, $cellValue);
                            }
                            break;

                        case 'lookup':
                            $cellFormat = explode($paramSeparator, $cellFormat);
                            if($cellValue != '') {
                                if(count($cellFormat) == 2) {
                                    if(!array_key_exists($cellFormat[0], $DAO_cache)) {
                                        $TestDao = DAO::createDAO($interfaces, $cellFormat[0], true);
                                        $DAO_cache[$cellFormat[0]] = &$TestDao;
                                    }
                                    else {
                                        $TestDao = &$DAO_cache[$cellFormat[0]];
                                    }
                                    $lookupFields = explode('+', $cellFormat[1]);
                                    $TestDao->setColumnsAsArray($lookupFields);
                                    $TestSet = $TestDao->get($cellValue);
                                    $cellValue = '';
                                    for($i=0; $i<count($lookupFields); $i++) {
                                        if($cellValue != '') $cellValue .= '&nbsp;';
                                        $cellValue .= $TestSet -> getValue($lookupFields[$i]);
                                    }
                                }
                            }
                            break;

                        case 'custom':
                            if(is_array($this->onSetCell)) {
                                eval("\$cellValue=\$this->onSetCell[0]->".$this->onSetCell[1]."(\$this, $rowNr, '" . addSlashes($fieldName) . "', '" . addSlashes($cellValue) . "', $selected);");
                                //$cellValue = $this->onSetCell[0]->$this->onSetCell[1]($rowNr, $fieldName, $cellValue);
                            }
                            break;
                    }


                    if($shortenValue > 0) {
                        $cellValue = shorten($cellValue, $shortenValue, 1, false);
                    }


                    $Template->newBlock('col');
                    $Template->ActiveBlock->setVar(array(
                        'cellValue' => $cellValue,
                        'cellValueOriginal' => $cellValueOriginal,
                        'identValue' => $identValue,
                        'colAlign' => $colAlign,
                        'colWidth' => $colWidth,
                        'colClass' => $this->colClass,
                        'colName' => $fieldName
                    ));
                    $this->activeRecord[$fieldName] = $cellValue;
                }
                if($rowNr == 1) $this->firstRecord = $this->activeRecord;
                $rowNr++;
                $this->lastRecord = $this->activeRecord;
            } while($row = $Set->next() and $numVisibleRecords >= $rowNr);
        }
        #spuckZeitaus();

        // Leerzeilen auff�llen
        $rowNr++;
        while($rowNr <= ($maxRecordsPerPage+1)) {
            $rowNr++;
            $ActiveBlock = &$this->Template->newBlock('emptyRow');
            if($ActiveBlock) {
                $ActiveBlock->setVar(array(
                    'rowHeight' => $rowHeight,
                    'modulus' => ($rowNr%2),
                    'colLength' => count($colNames)
                ));
            }
        }
        $this->Template->leaveBlock();

        if($this->getVar('disableLimit') == 0) {
            //
            // Splitter
            $this->Template->ActiveFile->setVar(array(
                'TREFFER' => $this->numRecords,
                'VON' => ($this->numRecords > 0) ? $splitterPos + 1 : 0,
                'BIS' => $splitterPos + $numVisibleRecords
            ));
        }

        $this->addHandoffVar('page_urlParam', $splitterPosName);
        $this->addHandOffVar('numRecords', $this->numRecords);
        $this->addHandOffVar('maxRecordsPerPage', $maxRecordsPerPage);
    }

    /**
     * Liefert die Anzahl gefundener Datens�tze
     *
     * @return int
     */
    function getNumRecords()
    {
        return (int)$this->numRecords;
    }

    /**
     * Liefert den ersten Datensatz der Liste
     *
     * @return array
     */
    function getFirstRecord()
    {
        return $this->firstRecord;
    }

    /**
     * Liefert den letzten Datensatz der Liste
     *
     * @return array
     */
    function getLastRecord()
    {
        return $this->lastRecord;
    }

    /**
     * Liefert den aktuellen Datensatz der Liste (der gerade aufbereitet wird)
     *
     * @return array
     */
    function getActiveRecord()
    {
        return $this->activeRecord;
    }

    /**
     * Liefert das erstellte Resultset
     *
     * @return Resultset
     */
    function &getResultset()
    {
        return $this->Resultset;
    }

    /**
     * Erzeugt Link zum Sortieren in der Titelzeile der Trefferliste
     *
     * @param unknown_type $fieldname
     */
    function generateSortLink($fieldname)
    {
        $Template = &$this -> Template;
        $Input = &$this -> Input;

        $sortSuffix = $this -> sortSuffix; //$Input -> getVar('sortSuffix');
        $sortField = $this -> sortField; //$Input -> getVar('sortField' . $sortSuffix);
        $sortOrder = $this -> sortOrder; //$Input -> getVar('sortOrder' . $sortSuffix);

        $classActiveSortLink = $Input->getVar('classActiveSortLink');
        $classInactiveSortLink = $Input->getVar('classInactiveSortLink');

        $Url = new Url();
        $Url -> modifyParam('sortField'.$sortSuffix, addslashes($fieldname));
        $Url -> modifyParam('sortOrder'.$sortSuffix, ($sortField == $fieldname and $sortOrder == 'ASC') ? 'DESC' : 'ASC');
        $Template->setVar('URL_Sort', $Url -> getUrl());
        $css = ($fieldname == $sortField) ? $classActiveSortLink : $classInactiveSortLink;
        $Template->setVar('classSortLink', $css);
    }

    /**
     * Html Templates uebersetzen. Dabei werden Bloecke und Variablen im Html Template zugewiesen.
     * Der fertige Inhalt wird zurueck gegeben.
     *
     * @return string fertiger Content der Html Templates
     **/
    function finalize()
    {
        $this -> Template -> parse('frame');
        $content = $this -> Template -> getContent('frame');
        return $content;
    }
}