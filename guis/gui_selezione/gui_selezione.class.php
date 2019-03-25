<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * Das GUI_Selezione stellt zwei Listen dar, in denen man die Einträge hin- und her schieben kann.
 *
 * @version $Id: gui_selezione.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2009-07-15
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Selezione
 *
 * @package pool
 * @author manhart
 * @access public
 **/
class GUI_Selezione extends GUI_Module
{
    var $numRows = 0;
    var $selectionColumns = array();
    var $sessionKeyColumns = '';
    var $selectionRows = array();
    var $sessionKeyRows = '';
    var $PK = array();
    var $sessionKeyPK = '';
    var $BlockList = null;
    var $BlockJsList = null;
    var $list = array();
    var $callback_formatSelectionList = null;

    /**
     * Constructor
     *
     * @param Object $Owner Klasse vom Typ Component
     * @param boolean $autoLoadFiles
     * @param array $params
     * @see Component
     **/
    function __construct(&$Owner, $autoLoadFiles, $params)
    {
        parent::__construct($Owner, $autoLoadFiles, $params);
    }

    /**
     * Initialisiert Standardwerte
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar('id', $this->getName());
        $this->Defaults->addVar('name', $this->getName());

        $this->Defaults->addVar('title_left', 'Liste');
        $this->Defaults->addVar('title_right', 'Auswahl');

        $this->Defaults->addVar('PK', '');

        $this->Defaults->addVar('jsLib', 'prototype');

        // todo's:
        $this->Defaults->addVar('list_and_selection_unique', false);

        parent::init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $jsLib = $this->Input->getVar('jsLib');

        $file = $this->Weblication->findTemplate('tpl_selezione.html', strtolower($this->getClassName()), true);
        $this->Template->setFilePath('stdout', $file);

        if(!isAjax()) {
            $Headerdata = &$this->Weblication->findComponent('Headerdata');
            if($Headerdata) {
                switch ($jsLib) {
                    case 'prototype':
                        // prototype.js benoetigt
                        $jsfile = $this->Weblication->findJavaScript('prototype.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);

                        $jsfile = $this->Weblication->findJavaScript('ajax.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);

                        $jsfile = $this->Weblication->findJavaScript('url.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);

                        $jsfile = $this->Weblication->findJavaScript('selezione.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);
                        break;

                    case 'jquery':
                        // jquery wird im moment von der software aufgrund der version selbst eingebunden (TODO)
                        $jsfile = $this->Weblication->findJavaScript('jquery.ajax.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);

                        $jsfile = $this->Weblication->findJavaScript('url.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);

                        $jsfile = $this->Weblication->findJavaScript('jquery.selezione.js', $this->getClassName(), true);
                        $Headerdata->addJavaScript($jsfile);
                        break;
                }
            }

            if(!is_array($PK = $this->getVar('PK'))) {
                if(empty($PK)) die('You must define the parameter PK for '.$this->Name);
                $this->setVar('PK', explode(';', $PK));
            }
        }

        $this->initSelezione();
    }

    /**
     * Falls loadFiles nicht erzwungen wird, kann das Modul von aussen direkt ueber initSelezione initialisiert werden.
     *
     */
    function initSelezione()
    {
        $this->sessionKeyRows = POOL.'.'.$this->Name.'.selectionRows';
        $this->sessionKeyColumns = POOL.'.'.$this->Name.'.selectionColumns';
        $this->sessionKeyPK = POOL.'.'.$this->Name.'selectionPK';
        $selectionRows = ($this->Session->getVar($this->sessionKeyRows));
//			if(!isAjax()) echo session_id().'<br>';
//		if(!isAjax()) echo $selectionRows;
        if(is_array($selectionRows)) {
            $this->selectionRows = $selectionRows;
        }

        $selectionColumns = $this->Session->getVar($this->sessionKeyColumns);
        #echo pray($this->Session->Vars);
        #echo 'hier: |'.$selectionColumns.'|';
        if(is_array($selectionColumns)) {
            $this->selectionColumns = $selectionColumns;
        }

        $PK = $this->Session->getVar($this->sessionKeyPK);
        if(is_array($PK)) {
            $this->PK = $PK;
        }
        else {
            $this->setPK($this->getVar('PK'));
        }

        $this->BlockList = &$this->Template->newBlock('bList');
        $this->BlockJsList = &$this->Template->newBlock('bJsList');
    }

    /**
     * Template aufbereiten
     *
     */
    function prepare()
    {
        $this->Template->leaveBlock();

        $id = $this->Input->getVar('id');
        $name = $this->Input->getVar('name');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this->getName() and $id == $this->getName()) {
            $id = $name;
        }

        if ($id != $this->getName() and $name == $this->getName()) {
            $name = $id;
        }
        $this->Input->setVar(array('name' => $name, 'id' => $id));

        $this->Template->setVar('NAME', $this->Name);
        $this->Template->setVar('TITLE_LEFT', $this->getVar('title_left'));
        $this->Template->setVar('TITLE_RIGHT', $this->getVar('title_right'));
        $this->Template->setVar('SELECTIONLIST_COUNT', count($this->selectionRows));

        $this->showSelectionList();
    }

    /**
     * Zeigt die Selektion (rechte Liste) an
     *
     */
    function showSelectionList()
    {
        $this->_renderSelectionList();
    }

    /**
     * Setzt PK (Primary Key)
     *
     * @param array $PK
     */
    function setPK($PK)
    {
        $this->PK = $PK;
        $this->Session->setVar($this->sessionKeyPK, $PK);
    }

    /**
     * Setzt die Spalten, die auf die rechte Seite (Auswahl) landen
     *
     * @param array $columns Spalten/Feldnamen
     */
    function setSelectionColumns($columns)
    {
        $this->selectionColumns = $columns;
        $this->Session->setVar($this->sessionKeyColumns, $this->selectionColumns);
    }

    /**
     * Liefert die Spalten f�r die rechte Seite (Auswahl)
     *
     * @return unknown
     */
    function getSelectionColumns()
    {
        if(count($this->selectionColumns) > 0) {
            return $this->selectionColumns;
        }
        else if(isAjax()) {
            return array_keys($this->Input->getData());
        }
        else {
            die('Bitte ueber setSelectionColumns die Spalten der Auswahl definieren!');
        }
    }

    /**
     * AJAX Request: fuegt ein Element in der Selektionsliste hinzu
     *
     * Use it with JavaScript function: Selezione::add
     *
     * @return array Ergebnis
     */
    function add()
    {
        $row = array();
        $selectionColumns = $this->getSelectionColumns();
        foreach($selectionColumns as $column) {
            $row[$column] = utf8_decode($this->getVar($column));
        }
        $this->addSelection($row, $this->getVar('URL_SELEZIONE_KEY'));

        $BlockSelectionList = &$this->_renderSelectionList();
        $contentSelectionList = $this->Weblication->adjustImageDir($BlockSelectionList->parse(true));
        return array('SelectionList' => $contentSelectionList, 'action' => 'add', 'countSelectionList' =>
            count($this->selectionRows));
    }

    /**
     * AJAX Request: loescht ein Element aus der Selektionsliste
     *
     * Use it with JavaScript function: Selezione::remove
     *
     * @return array Ergebnis
     */
    function remove1()
    {
        $PK = utf8_decode($this->getVar('URL_SELEZIONE_KEY'));
        unset($this->selectionRows[$PK]);
        $this->storeSelection();

        $BlockSelectionList = &$this->_renderSelectionList();
        $contentSelectionList = $this->Weblication->adjustImageDir($BlockSelectionList->parse(true));
        return array('SelectionList' => $contentSelectionList, 'action' => 'remove', 'countSelectionList' =>
            count($this->selectionRows));
    }

    /**
     * Rendert die Selektionsliste (rechte Liste)
     *
     * @access private
     * @return TempBlock
     */
    function &_renderSelectionList()
    {
        $BlockSelectionList = &$this->Template->newBlock('bSelectionList'); /* @var BlockSelectionList TempBlock */

        foreach ($this->selectionRows as $row) {
            $BlockSelectionRow = &$this->Template->newBlock('bSelectionRow');
            if($callback = $this->callback_formatSelectionList) {
                $row = $callback[0]->$callback[1]($row);
            }

            #$BlockSelectionRow->setVar(utf8_decode(urldecode($row)));
            #$BlockSelectionRow->setVar(str_replace('%20', ' ', $row));

            $BlockSelectionRow->setVar(sonder2umlauts($row));

            $BlockSelectionRow->setVar('NAME', $this->Name);
        }
        return $BlockSelectionList;
    }

    function formatSelectionList($callback)
    {
        $this->callback_formatSelectionList = $callback;
    }

    /**
     * Erstellt PK aus der Datenzeile
     *
     * @param array $row
     * @return unknown
     */
    function _combinePK($row)
    {
        $ident = '';
        if (is_array($this->PK)) {
            foreach($this->PK as $key) {
                if(array_key_exists($key, $row)) {
                    $ident .= $row[$key];
                }
            }
        }
        return $ident;
    }

    /**
     * F�gt einen Eintrag in die (linke) Liste ein. Zaehlt automatisch die Anzahl mit (siehe setNumRows)
     *
     * @param array $row Datensatz
     * @access public
     */
    function addRow($row)
    {
        $PK = $this->_combinePK($row);

        $BlockRow = &$this->Template->newBlock('bRow');
        $BlockRow->setVar(
            array(
                'PK' => $PK,
                'NAME' => $this->Name
            )+$row
        );
        $BlockJSRow = &$this->Template->newBlock('jsRow');
        $BlockJSRow->setVar(
            array(
                'PK' => $PK,
                'JSONOBJ' => str_replace(array(chr(13), chr(10)), array('\\r', '\\n'), array2json($row)),
                'NAME' => (isAjax() ? $this->Name : 'this')
            )
        );
        //$this->list[] = $row;
        $this->Template->leaveBlock();
        return $this->numRows++;
    }

    function setNumRows($numRows)
    {
        $this->numRows = (int)$numRows;
    }

    function setListAsArray($list)
    {
        $this->list = $list;
    }

    function showList()
    {
        $this->renderList();
    }

    /**
     * AJAX Request: rendert die Liste
     *
     * Use it with the JavaScript function: Selezione::renderList
     *
     * @return array
     */
    function renderList()
    {
        $isAjax = isAjax();
        if($isAjax) {
            $componentName = $this->getVar('componentName');
            $custom_method = $this->getVar('customMethod');
            $Component = &$this->Weblication->findComponent($componentName);
            if(is_object($Component) and method_exists($Component, $custom_method)) {
                $result = $Component->$custom_method($this);
                if(is_array($result)) $this->setListAsArray($result); //
            }
            else {
                return array('ErrorMsg' => 'Component '.$componentName.' unknown or method '.$custom_method.' not found in '.__LINE__.':'.__FILE__.'!');
            }
        }

        $num = count($this->list);
        if($num) {
            foreach ($this->list as $row) {
                $this->addRow($row);
            }
        }

        if($isAjax) {
            $contentList = $this->Weblication->adjustImageDir($this->BlockList->parse(true));
            $contentJsList = $this->BlockJsList->parse(true);
            return array('List' => $contentList, 'JsList' => $contentJsList, 'countList' => $num);
        }
        return true;
    }

    function addSelection($row, $PK)
    {
        $row['PK'] = $PK;
        $this->selectionRows[$PK] = $row;
        // eventl. sort, TODO
        $this->storeSelection();
    }

    function clearSelection()
    {
        $this->selectionRows = array();
        $this->storeSelection();
    }

    function storeSelection()
    {
        $this->Session->setVar($this->sessionKeyRows, ($this->selectionRows));
    }

    function isInSelection($search_row)
    {
        // TODO Optimize, wenn er einen gefunden hat, kann er diesen auch aus selectionRows tempor�r entfernen
        $found = false;
        #$numCols = count($this->selectionColumns);
        foreach ($this->selectionRows as $row) {
            if($row['PK'] == $this->_combinePK($search_row)) {
                return true;
            }
/*				$c = 0;
            foreach($this->PK as $col) {
                if($row[$col] != $search_row[$col]) {
                    break;
                }
                $c++;
            }
            if($c > 0 and $c == $numCols) $found = true;*/
        }
        return $found;
    }

    /**
     * Liefert die gespeicherte Auswahl
     *
     * @access public
     * @return array
     */
    function getSelection()
    {
        return $this->selectionRows;
    }

    /**
     * Template abschlie�en
     *
     * @return string Templateinhalt
     */
    function finalize()
    {
        #echo '<br>'.$this->numRows;
        $this->Template->leaveBlock();
        $this->Template->setVar('NUMROWS', $this->numRows);
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}