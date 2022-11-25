<?php
/**
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

define('CHOOSY_TRANSFER_FILE', 'transfer_by_file');
define('CHOOSY_TRANSFER_DBCHOOSY', 'transfer_by_db_choosy');
define('CHOOSY_TRANSFER_DB', 'transfer_by_db');

class GUI_Selectionlist extends GUI_Module
{
    /**
     * @var bool
     */
    protected bool $autoLoadFiles = false;

    function init(?int $superglobals = I_EMPTY)
    {
        $this->Defaults->addVar(
            array(
                'inputfile' => '',
                'selectionfile' => '',
                'tabledefine' => '',
                'outputfile' => '',
                'primarykeys' => '',
                'separator' => ';',
                'transfer' => '',

                'maxRecordsPerPage' => 10,
                'splitterPos' => 0,
                'defaultSortfield' => '',
                'defaultSortorder' => 'ASC',
                'multiple' => 0,
                'seltype' => 'checkbox',
                'filter' => array(),

                'showfields' => '',
                'searchfields' => '',
                'shortenfields' => '',
                'shortenmore' => '...',

                'submitsearch' => 0,
                'suchbegriff' => '',

                'class' => array('style_row_1', 'style_row_2')
            )
        );
        parent:: init(I_REQUEST);
    }

    function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_selectionlist.html', $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);

        $jsfile = $this->Weblication->findJavaScript('dhtmlhint.js', '', true);
        $cssfile = @$this->Weblication->findStyleSheet($this->getClassName() . '.css', $this->getClassName());

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->getHeadData()->addJavaScript($jsfile);
            if($cssfile) {
                $this->Weblication->getFrame()->getHeadData()->addStyleSheet($cssfile);
            }
        }
    }

    function prepare()
    {
        $this->loadFiles();

        $Template = $this->Template;
        $Input = $this->Input;
        $interfaces = $this->Weblication->getInterfaces();
        $Frame = $this->Weblication->getFrame();
        $Frame->getHeadData()->setTitle('Auswahlliste Zwangskombinationen');

        if($Input->getVar('primarykeys') == '') {
            $this->raiseError(__FILE__, __LINE__, 'Der Parameter "primarykeys" (Primärschlässel) wurde nicht übergeben! ' .
                'Der Primärschlüssel bestimmt die Rückgabewerte der Auswahlliste.');
        }


        $GUI_Shorten = new GUI_Shorten($this->getOwner());
        $GUI_Shorten->loadFiles();

        $inputfile = $Input->getVar('inputfile');
        $selectionfile = $Input->getVar('selectionfile');
        $tabledefine = $Input->getVar('tabledefine');
        //        $outputfile = $Input -> getVar('outputfile');
        $separator = $Input->getVar('separator');

        #### suche
        if($Input->getVar('submitsearch') == 1) {
            $Url = new Url();
            $Url->setParam('splitterPos', 0);
            $Url->setParam('suchbegriff', $Input->getVar('suchbegriff'));
            $Url->restartUrl();
        }

        #### gespeicherte auswahl (selectionfile)
        if(file_exists($selectionfile)) {
            $selectionlines = file($selectionfile);
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
        // echo '<br>anfang: ' . pray($selectionlines);


        if($tabledefine) {
            #### input from daos
            $filter = $Input->getVar('filter');
            if($Input->getVar('suchbegriff')) {
                $suchbegriff = $Input->getVar('suchbegriff');
                $searchfields = explode(';', $Input->getVar('searchfields'));
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
                    array_push($filter, array($fieldname, 'like', str_replace('*', '%', $suchbegriff) . '%'));
                }
                if($merker) {
                    array_push($filter, ')');
                }
            }
            $sorting = ($Input->getVar('defaultSortfield')) ? array($Input->getVar('defaultSortfield') => $Input->getVar('defaultSortorder')) : array();
            $limit = array($Input->getVar('splitterPos'), $Input->getVar('maxRecordsPerPage'));

            $DAO = DAO::createDAO($interfaces, $tabledefine);
            //$DAO -> setColumnsAsString($Input -> getVar('searchfields'), ';');
            $Resultset = $DAO->getMultiple(null, null, $filter, $sorting, $limit);
            $list = $Resultset->getRowset();

            $Resultset_count = $DAO->getCount(null, null, $filter);
            $numRecords = $Resultset_count->getValue('count');
        }
        else {
            #### input from inputfile
            $lines = file($inputfile);
            $columns = array_shift($lines);
        }

        /* wichtig variable: list */

        #### ok - Auswahlliste speichern
        if($Input->getVar('submitselectionlist') == 1) {
            $auswahl = $Input->getVar('auswahl');

            //if (is_array($auswahl) and count($auswahl)) {
            $saved_list = explode("\n", trim($Input->getVar('saved_list')));
            #### header schreiben
            if(count($selectionlines) == 0) {
                $selectionlines[] = $Input->getVar('primarykeys');
            }
            else {
                // echo '<br>vor unshift' . pray($selectionlines);
                array_unshift($selectionlines, $key_line);
                // echo '<br>nach unshift' . pray($selectionlines);
            }
            foreach($saved_list as $pkstring) {
                //$pkstring = $this -> getPKString($row);
                $pkstring = trim($pkstring);

                if(empty($pkstring)) {
                    continue;
                }

                #### INSERT
                if(is_array($auswahl) and in_array($pkstring, $auswahl)) {
                    if(!in_array($pkstring, $selectionlines)) {
                        $selectionlines[] = $pkstring;
                    }
                }
                #### DELETE
                else {
                    if($key = array_search($pkstring, $selectionlines)) {
                        // echo 'DELETE';
                        unset($selectionlines[$key]);
                    }
                }
            }
            $selectionlines = array_values($selectionlines);

            $fhandle = fopen($selectionfile, 'w');
            if($fhandle) {
                // echo 'gespeichert wird: ' . pray ($selectionlines);
                fwrite($fhandle, trim(implode("\n", $selectionlines)));
            }
            @fclose($fhandle);

            //// echo pray(file($selectionfile));
            //}

            $Url = new Url();
            $Url->setParam('splitterPos', $Input->getVar('splitterPos'));
            $Url->setParam('saved', $Input->getVar('submitok'));
            $Url->restartUrl();
        }

        $showfields = explode(';', $Input->getVar('showfields'));
        $count_showfields = count($showfields);
        $shortenfields = explode(';', $Input->getVar('shortenfields'));

        #### Zaehler
        $z = 0;
        $class = $Input->getVar('class');

        //// // echo pray(file($selectionfile));

        #### Auswahlliste erzeugen:
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
                            //								// echo "$row[$key] == $selvalues[$k] <br>";
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
                $Template->setVar('class', $class[$z % 2]);

                for($i = 0; $i < $count_showfields; $i++) {

                    $Template->newBlock('columns');
                    if($shortenfields[$i] > 0) {
                        $GUI_Shorten->Input->setVar(
                            array(
                                'text' => $row[$showfields[$i]],
                                'len' => $shortenfields[$i],
                                'more' => $Input->getVar('shortenmore')
                            )
                        );
                        $GUI_Shorten->prepareContent();
                        $text = $GUI_Shorten->finalizeContent();
                    }
                    else {
                        if($selected) {
                            $text = $row[$showfields[$i]];
                        }
                        else {
                            $text = $row[$showfields[$i]];
                        }
                    }
                    $Template->setVar('text', $text);
                }

                #### seltype (checkbox, radiobutton, ...)
                $value = $this->getPKString($row);
                $Template->newBlock($Input->getVar('seltype'));
                $Template->setVar(
                    array(
                        'checked' => ($selected) ? '1' : '0',
                        'value' => $value
                    )
                );
                $saved_list[] = $value;

                $z++;
            }
        }
        $Template->leaveBlock();

        $Template->setVar('saved_list', implode("\n", $saved_list));

        #### GUI_Splitter Parameter:
        $this->addHandOffVar(
            array(
                'maxRecordsPerPage' => $Input->getVar('maxRecordsPerPage'),
                'numRecords' => $numRecords
            )
        );
        /*
        $GUI_ListView = new GUI_CustomListView($this -> Owner, true);
        $GUI_ListView -> Input -> setVar(
            array(
                'list' => $list,
                'columns' => $Input -> getVar('showfields'),
                'coltitles' => $Input -> getVar('titles'),
                'enablesort' => 1
            )
        );
        $GUI_ListView -> autoLoadFiles();
        $GUI_ListView -> prepareContent();

        $Template -> setVar('listview', $GUI_ListView -> finalizeContent());
        */
    }

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

    function finalize(): string
    {
        $this->Template->parse('stdout');
        return $this->reviveChildGUIs($this->Template->getContent('stdout'));
    }
}