<?php
/*
 * POOL
 *
 * gui_table.class.php created at 08.04.21, 13:16
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_Table extends GUI_Module implements JsonConfig
{
    protected array $defaultOptions = [
        'url' => [
            'attribute' => 'data-url',
            'type' => 'string',
            'value' => null // undefined
        ],
        'classes' => [
            'attribute' => 'data-classes',
            'type' => 'string',
            'value' => 'table table-bordered table-hover'
        ],
        'clickToSelect' => [
            'attribute' => 'data-click-to-select',
            'type' => 'boolean',
            'value' => false
        ],
        'columns' => [
            'attribute' => '',
            'type' => 'array',
            'value' => []
        ],
        'customSort' => [
            'attribute' => 'data-custom-sort',
            'type' => 'function',
            'value' => null // undefined
        ],
        'pagination' => [
            'attribute' => 'data-pagination',
            'type' => 'boolean',
            'value' => false
        ],
        'resizable' => [
            'attribute' => 'data-resizable',
            'type' => 'boolean',
            'value' => false
        ],
        'search' => [
            'attribute' => 'data-search',
            'type' => 'boolean',
            'value' => false
        ],
        'showColumns' => [
            'attribute' => 'data-show-columns',
            'type' => 'boolean',
            'value' => false
        ],
        'showExport' => [
            'attribute' => 'data-show-export',
            'type' => 'boolean',
            'value' => false,
        ],
        'exportDataType' => [
            'attribute' => 'data-export-data-type',
            'type' => 'string',
            'value' => 'basic',
        ],
        'showFullscreen' => [
            'attribute' => 'data-show-fullscreen',
            'type' => 'boolean',
            'value' => false,
        ],
        'showRefresh' => [
            'attribute' => 'data-show-refresh',
            'type' => 'boolean',
            'value' => false,
        ],
        'showPrint' => [
            'attribute' => 'data-show-print',
            'type' => 'boolean',
            'value' => false
        ],
        'showToggle' => [
            'attribute' => 'data-show-toggle',
            'type' => 'boolean',
            'value' => false,
        ],
        'sortable' => [
            'attribute' => 'data-sortable',
            'type' => 'boolean',
            'value' => true
        ]
    ];

    protected array $defaultColumnOptions = [
        'align' => [
            'attribute' => 'data-align',
            'type' => 'string',
            'value' => null
        ],
        'cardVisible' => [
            'attribute' => 'data-card-visible',
            'type' => 'boolean',
            'value' => true,
        ],
        'cellStyle' => [
            'attribute' => 'data-cell-style',
            'type' => 'function',
            'value' => null
        ],
        'checkbox' => [
            'attribute' => 'data-checkbox',
            'type' => 'boolean',
            'value' => false
        ],
        'checkboxEnabled' => [
            'attribute' => 'data-checkbox-enabled',
            'type' => 'boolean',
            'value' => true,
        ],
        'class' => [
            'attribute' => 'data-class',
            'type' => 'string',
            'value' => null,
        ],
        'clickToSelect' => [
            'attribute' => 'data-click-to-select',
            'type' => 'boolean',
            'value' => false,
        ],
        'colspan' => [
            'attribute' => 'data-colspan',
            'type' => 'number',
            'value' => null,
        ],
        'detailFormatter' => [
            'attribute' => 'data-detail-formatter',
            'type' => 'function',
            'value' => 'function(index, row, $element) { return \'\' }',
        ],
        'escape' => [
            'attribute' => 'data-escape',
            'type' => 'boolean',
            'value' => false,
        ],
        'falign' => [
            'attribute' => 'data-falign',
            'type' => 'string',
            'value' => null,
        ],
        'field' => [
            'attribute' => 'data-field',
            'type' => 'string',
            'value' => null
        ],
        'footerFormatter' => [
            'attribute' => 'data-footer-formatter',
            'type' => 'function',
            'value' => null,
        ],
        'formatter' => [
            'attribute' => 'data-formatter',
            'type' => 'function',
            'value' => null
        ],
        'halign' => [
            'attribute' => 'data-halign',
            'type' => 'string',
            'value' => null,
        ],
        'order' => [
            'attribute' => 'data-order',
            'type' => 'string',
            'value' => 'asc',
        ],
        'radio' => [
            'attribute' => 'data-radio',
            'type' => 'boolean',
            'value' => false
        ],
        'rowspan' => [
            'attribute' => 'data-rowspan',
            'type' => 'number',
            'value' => null,
        ],
        'searchable' => [
            'attribute' => 'data-searchable',
            'type' => 'boolean',
            'value' => true,
        ],
        'searchFormatter' => [
            'attribute' => 'data-search-formatter',
            'type' => 'boolean',
            'value' => true,
        ],
        'searchHighlightFormatter' => [
            'attribute' => 'data-search-highlight-formatter',
            'type' => 'boolean', // could also be |function
            'value' => true,
        ],
        'showSelectTitle' => [
            'attribute' => 'data-show-select-title',
            'type' => 'boolean',
            'value' => false
        ],
        'sortable' => [
            'attribute' => 'data-sortable',
            'type' => 'boolean',
            'value' => false
        ],
        'sorter' => [
            'attribute' => 'data-sorter',
            'type' => 'function',
            'value' => null
        ],
        'sortName' => [
            'attribute' => 'data-sort-name',
            'type' => 'string',
            'value' => null,
        ],
        'switchable' => [
            'attribute' => 'data-switchable',
            'type' => 'boolean',
            'value' => true,
        ],
        'title' => [
            'attribute' => 'data-title',
            'type' => 'string',
            'value' => null
        ],
        'titleTooltip' => [
            'attribute' => 'data-title-tooltip',
            'type' => 'string',
            'value' => null,
        ],
        'valign' => [
            'attribute' => 'data-valign',
            'type' => 'string',
            'value' => null,
        ],
        'visible' => [
            'attribute' => 'data-visible',
            'type' => 'boolean',
            'value' => true
        ],
        'width' => [
            'attribute' => 'data-with',
            'type' => 'number',
            'value' => null,
        ],
        'widthUnit' => [
            'attribute' => 'data-with-unit',
            'type' => 'string',
            'value' => 'px'
        ]
    ];

    protected array $options = [];
    protected array $columns = [];

    protected array $poolOptions = [];



    /**
     * @var array options for the table
     */
    protected array $attribute = [
  /*      'data-url' => null,
        'data-classes' => 'table table-bordered table-hover',
        'data-thead-classes' => '', // undefined
        'data-click-to-select' => false, // clickToSelect decamilize data-click-to-select
        'data-pagination' => false,
        'data-search-highlight' => false,
        'data-show-columns' => false,
        'data-show-fullscreen' => false,
        'data-show-refresh' => false,
        'data-show-toggle' => false,
        'data-sortable' => true,
        'data-search' => false,
        'data-side-pagination' => 'client',
        'data-buttons-align' => 'right',
        'data-buttons-class' => 'secondary',
        'data-buttons-order' => '[\'paginationSwitch\', \'refresh\', \'toggle\', \'fullscreen\', \'columns\']', // array
        'data-buttons-prefix' => 'btn',
        'data-buttons-toolbar' => '', // undefined
        'data-cache' => true,
        'data-card-view' => false,
        'data-checkbox-header' => true,
        'data-content-type' => 'application/json',
        'data-data' => '[]', // Array | Object
        'data-data-field' => 'rows',
        'data-data-type' => 'json',
        'data-detail-view' => false,
        'data-detail-view-align' => 'left',
        'data-detail-view-by-click' => false,
        'data-detail-view-icon' => true,
        'data-escape' => false,
        'data-filter-options' => '{ filterAlgorithm: \'and\' }',

        // functions
        'data-ajax' => '', // undefined
        'data-ajax-options' => '', // {}
        'data-buttons' => '', // {}
        'data-custom-search' => '', // undefined
        'data-custom-sort' => '', // undefined
        'data-detail-filter' => 'function(index, row) { return true }',
        'data-detail-formatter' => 'function(index, row, element) { return \'\' }',*/
    ];

    /**
     * @param const|int $superglobals
     */
    public function init($superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('framework', 'bs4');
        $this->Defaults->addVar('render', true);
        $this->Defaults->addVar('url', null);
        $this->Defaults->addVar('columns', null);
        parent::init($superglobals);

    }

    /**
     * Load files
     *
     * @throws ReflectionException|Exception
     */
    public function loadFiles()
    {
        $className = strtolower(__CLASS__);
        $fw = $this->getVar('framework');
        $tpl = $this->Weblication->findTemplate('tpl_table_'.$fw.'.html', $className, true);
        $this->Template->setFilePath('stdout', $tpl);

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->Headerdata->addJavaScript($this->Weblication->findJavaScript('table.js', $className, true));
            //$this->Weblication->getFrame()->Headerdata->addStyleSheet($this->Weblication->findStyleSheet('table_'.$fw.'.css', $className, true));
        }
    }

    public function setOptions(array $options): GUI_Table
    {
        foreach($options as $key => $value) {
            if($key == 'columns' and is_array($value)) {
                $this->setColumns($value);
                continue;
            }

            if($value === 'true' or $value === 'false') {
                $value = string2bool($value);
            }
            if(isset($this->defaultOptions[$key])) {
                if($this->defaultOptions[$key]['value'] != $value) {
                    $this->options[$key] = $value;
                }
            }
            else {
                $this->poolOptions[$key] = $value;
            }
        }

        return $this;
    }

    public function setColumns(array $columns): GUI_Table
    {
        foreach($columns as $z => $column) {
            $field = $column['field'] ?? $z;
            foreach($column as $key => $value)
                if(isset($this->defaultColumnOptions[$key])) {
                    $type = $this->defaultColumnOptions[$key]['type'] ?? '';
                    switch($type) {
                        case 'boolean':
                            if(is_string($value)) {
                                $value = string2bool($value);
                            }
                            break;
                    }

                    if($this->defaultColumnOptions[$key]['value'] != $value) {
//                        $this->defaultColumnOptions[$key]['type']
                        $this->columns[$z][$key] = $value;
                    }
                }
                else {
                    $this->poolOptions['poolColumnOptions'][$field][$key] = $value;
//                    if($key == 'dataType') {
//                        switch ($value) {
//                            case 'datetime':
//                            case 'date':
//                            case 'time':
//                                if(isset($column['formatter']) == false) {
//                                    $this->columns[$z]['formatter'] = '(value, row, index, field) => { return {modulename}.strftime(value, row, index, field)}';
//                                }
//                                break;
//                        }
//                    }
                }
        }

//        $this->columns = $columns;
        return $this;
    }

    public function loadConfig(string $json): bool
    {
        $result = false;
        $data = json_decode($json, JSON_OBJECT_AS_ARRAY);
        if(json_last_error() != JSON_ERROR_NONE) {
            return false;
        }
        if(isset($data['options'])) {
            $this->options = $data['options'];
            $result = true;
        }
        if(isset($this->options['columns'])) {
            $this->columns = $this->options['columns'];
            $result = true;
        }
        return $result;
    }

    public function getConfig(): string
    {
        $options = $this->options + ['columns' => $this->columns];
        $data = [
            'options' => $options,
        ];
        return json_encode($data);
    }

    /**
     * Provisioning data before preparing module and there children.
     */
    public function provision()
    {
        $data = $this->Input->getData();
        unset(
            $data['moduleName'],
            $data['ModuleName'],
            $data['modulename'],
            $data['framework'],
            $data['render']
        );

        $this->setOptions($data);

        // default time formats
        $this->poolOptions['time.strftime'] = $this->poolOptions['time.strftime'] ?? $this->Weblication->getDefaultFormat('time.strftime');
        $this->poolOptions['date.strftime'] = $this->poolOptions['date.strftime'] ?? $this->Weblication->getDefaultFormat('date.strftime');
        $this->poolOptions['datetime.strftime'] = $this->poolOptions['datetime.strftime'] ?? $this->Weblication->getDefaultFormat('datetime.strftime');
        $this->poolOptions['number'] = $this->poolOptions['number'] ?? $this->Weblication->getDefaultFormat('number');

        if($this->Input->getVar('columns') != null) {
            $columns = $this->Input->getVar('columns');
            switch (gettype($columns)) {
                case 'string':
                    if(isJSON($columns)) {
                        $columns = json_decode($columns, true);
                    }
                    else {
                        $columnsArray = explode(';', $columns);

                        $columns = [];
                        foreach($columnsArray as $column) {
                            $columnAttr = explode('|', $column);
                            $column = [];
                            $i = 0;
                            foreach($columnAttr as $attr) {
                                $attrValue = trim($attr);
                                if(strpos($attrValue, '=') !== false) {
                                    $attr = explode('=', $attrValue);
                                    $key = $attr[0];
                                    $val = $attr[1];
                                    if($key == 'field') $field = $val;
                                    $column[$key] = $val;
                                }
                                elseif($i == 0) {
                                    $field = trim($columnAttr[$i]) ?? '';
                                    if($field == '') continue;
                                    $column['field'] = $field;
                                    if(count($columnAttr) == 1) { // no title given
                                        $title = $columnAttr[$i] ?? $field;
                                        $column['title'] = $title;
                                    }
                                }
                                elseif($i == 1 and isset($field)) {
                                    $title = $columnAttr[$i] ?? $field;
                                    $column['title'] = $title;
                                }

                                $i++;
                            }

                            $columns[] = $column;
                        }
                    }
                    $this->setColumns($columns);
            }
        }
    }

    /**
     * prepare content
     */
    public function prepare()
    {
        $this->poolOptions['moduleName'] = $this->getName();

        $this->Template->setVar([
            'moduleName' => $this->getName(),
            'className' => $this->getClassName(),
            'options' => json_encode($this->poolOptions, JSON_PRETTY_PRINT)
        ]);


        $this->Template->newBlock('tableAttributes');
        foreach($this->options as $optName => $attrValue) {
//            $inpValue = $this->Input->getVar($attrName);
//            $attrValue = $inpValue ?? $attrValue;
            $attrName = $this->defaultOptions[$optName]['attribute'];
            $attrType = $this->defaultOptions[$optName]['type'];

            if(is_bool($attrValue)) {
                $attrValue = bool2string($attrValue);
            }

            if($attrValue == '') {
                continue;
            }

            $TableAttributeBlock = $this->Template->newBlock('tableAttribute');
            $TableAttributeBlock->setVar([
                    'data-name' => $attrName,
                    'data-value' => $attrValue
                ]
            );
        }
        unset($optName);

//        $this->Template->newBlock('row');
//        foreach($this->columns as $column) {
//            $ColumnBlock = $this->Template->newBlock('column');
//            $ColumnBlock->setVar('caption', $column['caption'] ?? '');
//            unset($column['caption']);
//            foreach($column as $attrName => $attrValue) {
//                $ColumnAttributeBlock = $this->Template->newBlock('columnAttribute');
//                $ColumnAttributeBlock->setVar([
//                    'data-name' => $attrName,
//                    'data-value' => $attrValue
//                ]);
//            }
//        }

        $this->Template->newBlock('js_row');
        foreach($this->columns as $column) {
            $ColumnBlock = $this->Template->newBlock('js_column');
            foreach($column as $optName => $attrValue) {
                $type = '';
                if(isset($this->defaultColumnOptions[$optName])) {
                    $type = $this->defaultColumnOptions[$optName]['type'];
                }

                switch($type) {
                    case 'boolean':
                        $attrValue = bool2string($attrValue);
                        break;

                    case 'function':
                        break;

                    default:
                        $attrValue = '\''.$attrValue.'\'';
                }
                $ColumnAttributeBlock = $this->Template->newBlock('js_columnOption');
                $ColumnAttributeBlock->setVar([
                    'key' => $optName,
                    'value' => str_replace('{modulename}', $this->getName(), $attrValue)
                ]);
            }
        }

        $this->Template->leaveBlock();
        $js_render = '';
        if($this->getVar('render')) {
            $js_render = '.render()';
        }
        $this->Template->setVar('render', $js_render);
        parent::prepare();
    }

    /**
     * Creates data format for the bootstrap table
     */
    static function getRowSetAsArray(Resultset $ResultSet, int $total): array
    {
        $return = [];
        $return['total'] = $total;
        //            $return['totalNotFiltered'] = $total;
        $return['rows'] = $ResultSet->getRowset();
        return $return;
    }

    /**
     * render content
     *
     * @return string
     */
    public function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}