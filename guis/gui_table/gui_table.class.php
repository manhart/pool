<?php
/*
 * POOL
 *
 * gui_table.class.php created at 08.04.21, 13:16
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_Table extends GUI_Module
{
    use Configurable;

    private array $inspectorProperties = [
        'url' => [
            'attribute' => 'data-url',
            'type' => 'string',
            'value' => null, // undefined
            'element' => 'input',
            'inputType' => 'text',
            'caption' => 'Data Url'
        ],
        'buttons' => [
            'attribute' => 'data-buttons',
            'type' => 'function', // todo ModuleConfigurator functions - here buttons
            'value' => '{}',
            'element' => 'input',
            'inputType' => 'text',
            'caption' => 'Buttons'
        ],
        'classes' => [
            'attribute' => 'data-classes',
            'type' => 'string',
            'value' => 'table table-bordered table-hover'
        ],
        'clickToSelect' => [
            'attribute' => 'data-click-to-select',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Click to Select'
        ],
        'columns' => [
            'attribute' => '',
            'type' => 'array',
            'value' => [],
            'element' => 'tableEditor',
//            'options' => [ // defaultColumnOptions
//                'align' => [
//                    'attribute' => 'data-align',
//                    'type' => 'string',
//                    'value' => null,
//                    'element' => 'select', //
//                    'options' => ['', 'left', 'right', 'center']
//                ],
//                'cardVisible' => [
//                    'attribute' => 'data-card-visible',
//                    'type' => 'boolean',
//                    'value' => true,
//                ],
//                'cellStyle' => [
//                    'attribute' => 'data-cell-style',
//                    'type' => 'function',
//                    'value' => null
//                ],
//                'checkbox' => [
//                    'attribute' => 'data-checkbox',
//                    'type' => 'boolean',
//                    'value' => false,
//                    'element' => 'input',
//                    'inputType' => 'checkbox',
//                ],
//                'checkboxEnabled' => [
//                    'attribute' => 'data-checkbox-enabled',
//                    'type' => 'boolean',
//                    'value' => true,
//                ],
//                'class' => [
//                    'attribute' => 'data-class',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'clickToSelect' => [
//                    'attribute' => 'data-click-to-select',
//                    'type' => 'boolean',
//                    'value' => false,
//                ],
//                'colspan' => [
//                    'attribute' => 'data-colspan',
//                    'type' => 'number',
//                    'value' => null,
//                ],
//                'detailFormatter' => [
//                    'attribute' => 'data-detail-formatter',
//                    'type' => 'function',
//                    'value' => 'function(index, row, $element) { return \'\' }',
//                ],
//                'escape' => [
//                    'attribute' => 'data-escape',
//                    'type' => 'boolean',
//                    'value' => false,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'checkbox', // tableEditor
//                ],
//                'falign' => [
//                    'attribute' => 'data-falign',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'field' => [
//                    'attribute' => 'data-field',
//                    'type' => 'string',
//                    'value' => null,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'text', // tableEditor
//                    'unique' => true, // tableEditor
//                    'required' => true, // tableEditor
//                    'showColumn' => 0 // tableEditor order
//                ],
//                'footerFormatter' => [
//                    'attribute' => 'data-footer-formatter',
//                    'type' => 'function',
//                    'value' => null,
//                ],
//                'formatter' => [
//                    'attribute' => 'data-formatter',
//                    'type' => 'function',
//                    'value' => null,
//                    'element' => 'textarea',
//                ],
//                'halign' => [
//                    'attribute' => 'data-halign',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'order' => [
//                    'attribute' => 'data-order',
//                    'type' => 'string',
//                    'value' => 'asc',
//                    'element' => 'select',
//                    'options' => ['asc', 'desc']
//                ],
//                'poolFormat' => [
//                    'attribute' => 'data-pool-format',
//                    'type' => 'string',
//                    'element' => 'input',
//                    'inputType' => 'text',
//                    'value' => '',
//                    'pool' => true,
//                ],
//                'poolType' => [
//                    'attribute' => 'data-pool-type',
//                    'type' =>  'string',
//                    'element' => 'select',
//                    'value' => '',
//                    'options' => ['', 'date', 'time', 'date.time', 'number'],
//                    'pool' => true,
//                ],
//                'radio' => [
//                    'attribute' => 'data-radio',
//                    'type' => 'boolean',
//                    'value' => false
//                ],
//                'rowspan' => [
//                    'attribute' => 'data-rowspan',
//                    'type' => 'number',
//                    'value' => null,
//                ],
//                'searchable' => [
//                    'attribute' => 'data-searchable',
//                    'type' => 'boolean',
//                    'value' => true,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'checkbox',
//                    'showColumn' => 3
//                ],
//                'searchFormatter' => [
//                    'attribute' => 'data-search-formatter',
//                    'type' => 'boolean',
//                    'value' => true,
//                ],
//                'searchHighlightFormatter' => [
//                    'attribute' => 'data-search-highlight-formatter',
//                    'type' => 'boolean', // could also be |function
//                    'value' => true,
//                ],
//                'showSelectTitle' => [
//                    'attribute' => 'data-show-select-title',
//                    'type' => 'boolean',
//                    'value' => false
//                ],
//                'sortable' => [
//                    'attribute' => 'data-sortable',
//                    'type' => 'boolean',
//                    'value' => false,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'checkbox',
//                ],
//                'sorter' => [
//                    'attribute' => 'data-sorter',
//                    'type' => 'function',
//                    'value' => null
//                ],
//                'sortName' => [
//                    'attribute' => 'data-sort-name',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'switchable' => [
//                    'attribute' => 'data-switchable',
//                    'type' => 'boolean',
//                    'value' => true,
//                ],
//                'title' => [
//                    'attribute' => 'data-title',
//                    'type' => 'string',
//                    'value' => null,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'text', // tableEditor
//                    'showColumn' => 1, // tableEditor
//                    'required' => true, // tableEditor mandatory field
//                ],
//                'titleTooltip' => [
//                    'attribute' => 'data-title-tooltip',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'valign' => [
//                    'attribute' => 'data-valign',
//                    'type' => 'string',
//                    'value' => null,
//                ],
//                'visible' => [
//                    'attribute' => 'data-visible',
//                    'type' => 'boolean',
//                    'value' => true,
//                    'element' => 'input', // tableEditor
//                    'inputType' => 'checkbox', // tableEditor
//                    'showColumn' => 2 // tableEditor
//                ],
//                'width' => [
//                    'attribute' => 'data-with',
//                    'type' => 'number',
//                    'value' => null,
//                ],
//                'widthUnit' => [
//                    'attribute' => 'data-with-unit',
//                    'type' => 'string',
//                    'value' => 'px'
//                ]
//            ],
            'properties' => [ // columnsProperties
                'align' => [
                    'attribute' => 'data-align',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'select', //
                    'options' => ['', 'left', 'right', 'center']
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
                    'value' => false,
                    'element' => 'input',
                    'inputType' => 'checkbox',
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
                    'value' => true,
                ],
                'colspan' => [
                    'attribute' => 'data-colspan',
                    'type' => 'number',
                    'value' => null,
                ],
                'dbColumn' => [
                    'attribute' => '',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'pool' => true,
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
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox', // tableEditor
                ],
                'events' => [
                    'attribute' => 'data-events',
                    'type' => 'function',
                    'value' => null,
                    'element' => 'textarea'
                ],
                'falign' => [
                    'attribute' => 'data-falign',
                    'type' => 'string',
                    'value' => null,
                ],
                'field' => [
                    'attribute' => 'data-field',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'unique' => true, // tableEditor
                    'required' => true, // tableEditor
                    'showColumn' => 0 // tableEditor order
                ],
                'filterControl' => [
                    'attribute' => 'data-filter-control',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'select',
                    'options' => [null, 'input', 'select', 'datepicker']
                ],
                'filterControlPlaceholder' => [
                    'attribute' => 'data-filter-control-placeholder',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                ],
                'filterDatepickerOptions' => [
                    'attribute' => 'data-filter-datepicker-options',
                    'type' => 'json', // should be object json todo json editor
                    'value' => null, // overridden in @see GUI_Table::init
                    'element' => 'input',
                    'inputType' => 'text',
                ],
                'footerFormatter' => [
                    'attribute' => 'data-footer-formatter',
                    'type' => 'function',
                    'value' => null,
                ],
                'formatter' => [
                    'attribute' => 'data-formatter',
                    'type' => 'function',
                    'value' => null,
                    'element' => 'textarea',
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
                    'element' => 'select',
                    'options' => ['asc', 'desc']
                ],
                'poolFormat' => [
                    'attribute' => 'data-pool-format',
                    'type' => 'auto',
                    'element' => 'input',
                    'inputType' => 'text',
                    'value' => '',
                    'pool' => true,
                ],
                'poolType' => [
                    'attribute' => 'data-pool-type',
                    'type' =>  'string',
                    'element' => 'select',
                    'value' => '',
                    'options' => ['', 'date', 'time', 'date.time', 'number'],
                    'pool' => true,
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
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox',
                    'showColumn' => 3
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
                    'element' => 'input',
                    'inputType' => 'checkbox'
                ],
                'showSelectTitle' => [
                    'attribute' => 'data-show-select-title',
                    'type' => 'boolean',
                    'value' => false
                ],
                'sortable' => [
                    'attribute' => 'data-sortable',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox',
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
                    'value' => null,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'showColumn' => 1, // tableEditor
                    'required' => true, // tableEditor mandatory field
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
                    'value' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox', // tableEditor
                    'showColumn' => 2 // tableEditor
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
            ]
        ],
        'customSort' => [
            'attribute' => 'data-custom-sort',
            'type' => 'function',
            'value' => null // undefined
        ],
        'method' => [
            'attribute' => 'data-method',
            'type' => 'string',
            'value' => 'get',
            'element' => 'select',
            'options' => ['get', 'post'],
            'caption' => 'Method',
        ],
        'filterControl' => [
            'attribute' => 'data-filter-control',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Filter Control'
        ],
        'maintainMetaData' => [
            'attribute' => 'data-maintain-meta-data',
            'type' => 'boolean',
            'value' => false,
            'caption' => 'Maintain Metadata',
            'element' => 'input',
            'inputType' => 'checkbox'
        ],
        'poolClearControls' => [
            'attribute' => 'data-pool-clear-controls',
            'type' => 'boolean',
            'value' => false,
            'caption' => 'Clear Controls',
            'element' => 'input',
            'inputType' => 'checkbox',
            'pool' => true,
        ],
        'poolClearControlsSelector' => [
            'attribute' => 'data-pool-clear-controls-selector',
            'type' => 'string',
            'value' => '',
            'caption' => 'Clear Controls Selector',
            'element' => 'input',
            'inputType' => 'text',
            'pool' => true,
        ],
        'poolFillControls' => [
            'attribute' => 'data-pool-fill-controls',
            'type' => 'boolean',
            'value' => false,
            'caption' => 'Fill Controls',
            'element' => 'input',
            'inputType' => 'checkbox',
            'pool' => true,
        ],
        'poolFillControlsContainer' => [
            'attribute' => 'data-pool-fill-controls-container',
            'type' => 'string',
            'value' => '',
            'caption' => 'Fill Controls Container',
            'element' => 'input',
            'inputType' => 'text',
            'pool' => true,
        ],
        'poolFillControlsSelector' => [
            'attribute' => 'data-pool-fill-controls-selector',
            'type' => 'string',
            'value' => '',
            'caption' => 'Fill Controls Selector',
            'element' => 'input',
            'inputType' => 'text',
            'pool' => true,
        ],
        'onCheck' => [
            'attribute' => 'data-on-check',
            'type' => 'function',
            'value' => null,
            'caption' => 'onCheck',
            'poolEvent' => true,
            'element' => 'textarea'
        ],
        'onClickRow' => [
            'attribute' => 'data-on-click-row',
            'type' => 'function',
            'value' => null,
            'caption' => 'onClickRow',
            'poolEvent' => true,
            'element' => 'textarea'
        ],
        'onUncheck' => [
            'attribute' => 'data-on-uncheck',
            'type' => 'function',
            'value' => null,
            'caption' => 'onUncheck',
            'poolEvent' => true,
            'element' => 'textarea'
        ],
        'pagination' => [
            'attribute' => 'data-pagination',
            'type' => 'boolean',
            'value' => false,
            'caption' => 'Pagination',
            'element' => 'input',
            'inputType' => 'checkbox'
        ],
        'paginationLoop' => [
            'attribute' => 'data-pagination-loop',
            'type' => 'boolean',
            'value' => true,
            'caption' => 'Pagination Loop',
            'element' => 'input',
            'inputType' => 'checkbox'
        ],
        'uniqueId' => [
            'attribute' => 'data-unique-id',
            'type'  => 'string',
            'element'   => 'input',
            'inputType' => 'text',
            'value' => null // undefined
        ],
        'resizable' => [
            'attribute' => 'data-resizable',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'search' => [
            'attribute' => 'data-search',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'searchAlign' => [
            'attribute' => 'data-search-align',
            'type' => 'string',
            'value' => 'right',
            'element' => 'select',
            'options' => ['left', 'right'],
            'caption' => 'Search Align'
        ],
        'searchHighlight' => [
            'attribute' => 'data-search-highlight',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Search Highlight'
        ],
        'smartDisplay' => [
            'attribute' => 'data-smart-display',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Smart Display'
        ],
        'showColumns' => [
            'attribute' => 'data-show-columns',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Show Columns'
        ],
        'showExport' => [
            'attribute' => 'data-show-export',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Export'
        ],
        'showFilterControlSwitch' => [
            'attribute' => 'data-show-filter-control-switch',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Show Filter Control Switch'
        ],
        'exportDataType' => [
            'attribute' => 'data-export-data-type',
            'type' => 'string',
            'value' => 'basic',
            'element' => 'select',
            'options' => ['basic', 'all', 'selected']
        ],
        'exportTypes' => [
            'attribute' => 'data-export-types',
            'type' => 'array', // todo module configurator
            'value' => ['json', 'xml', 'csv', 'txt', 'sql', 'excel'],
        ],
        'showFullscreen' => [
            'attribute' => 'data-show-fullscreen',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Show FullScreen'
        ],
        'showRefresh' => [
            'attribute' => 'data-show-refresh',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Show Refresh'
        ],
        'showPrint' => [
            'attribute' => 'data-show-print',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Print',
        ],
        'showToggle' => [
            'attribute' => 'data-show-toggle',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Show Cardview'
        ],
        'singleSelect' => [
            'attribute' => 'data-single-select',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Single Select'
        ],
        'sortable' => [
            'attribute' => 'data-sortable',
            'type' => 'boolean',
            'value' => true,
            'caption' => 'Sortable',
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'sidePagination' => [
            'attribute' => 'data-side-pagination',
            'type' => 'string',
            'value' => 'client',
            'element' => 'select',
            'options' => ['client', 'server'],
            'caption' => 'Side Pagination'
        ],
        'toolbar' => [
            'attribute' => 'data-toolbar',
            'type' => 'string',
            'value' => null,
            'element' => 'input',
            'inputType' => 'text'
        ]
    ];


    protected array $options = [];
    protected array $columns = [];

    protected array $poolOptions = [];

    const RENDER_NONE = 0;
    const RENDER_IMMEDIATELY = 1;
    const RENDER_ONDOMLOADED = 2;

    /**
     * @param const|int $superglobals
     */
    public function init($superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('framework', 'bs4');
        $this->Defaults->addVar('render', self::RENDER_ONDOMLOADED);
        $this->Defaults->addVar('url', null);
        $this->Defaults->addVar('columns', null);

        // 09.12.21, AM, override default filterDatepickerOptions
        // @used-by table.js
        $this->inspectorProperties['columns']['properties']['filterDatepickerOptions']['value'] =
            '{"autoclose":true, "clearBtn":true, "todayHighlight":true, "language":"'.$this->Weblication->getLanguage().'"}';

        parent::init($superglobals);

//        $this->defaultOptions['moduleName']['value'] = $this->getName();

        // default time formats
        $this->poolOptions['time.strftime'] = $this->poolOptions['time.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.time');
        $this->poolOptions['date.strftime'] = $this->poolOptions['date.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.date');
        $this->poolOptions['date.time.strftime'] = $this->poolOptions['date.time.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.date.time');
        $this->poolOptions['number'] = $this->poolOptions['number'] ?? $this->Weblication->getDefaultFormat('number');

        if($this->Input->getVar('columns') != null) {
            $columns = $this->Input->getVar('columns');
            switch (gettype($columns)) {
                case 'string':
                    $this->Input->setVar('columns', $this->parseColumns($columns));
                //                    $this->setColumns($columns);
            }
        }
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

        $this->loadJavaScriptGUI();
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
            if(isset($this->getInspectorProperties()[$key])) {
                if($this->getInspectorProperties()[$key]['value'] != $value) {
                    $this->options[$key] = $value;
                }
            }
            else {
                $this->poolOptions[$key] = $value;
            }
        }

        return $this;
    }

//    public function getInspectorProperties(): array
//    {
//        return $this->inspectorProperties + parent::getInspectorProperties();
//    }

    /**
     * @return array
     */
    public function getInspectorProperties(): array
    {
        return $this->inspectorProperties + $this->getDefaultInspectorProperties();
    }

    public function getColumnsProperties(): array
    {
        return $this->getInspectorProperties()['columns']['properties'];
    }

    /**
     * @param array $columns
     * @return $this
     */
    public function setColumns(array $columns): GUI_Table
    {
        $defaultColumnOptions = $this->getColumnsProperties();

        foreach($columns as $z => $column) {
            $field = $column['field'] ?? $z;
            foreach($column as $key => $value)

                if(isset($defaultColumnOptions[$key])) {
                    $type = $defaultColumnOptions[$key]['type'] ?? '';
                    switch($type) {
                        case 'boolean':
                            if(is_string($value)) {
                                $value = string2bool($value);
                            }
                            break;
                    }

                    if($defaultColumnOptions[$key]['value'] != $value) {
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

//    public function loadConfig(string $json): bool
//    {
//        $result = false;
//        $data = json_decode($json, JSON_OBJECT_AS_ARRAY);
//        if(json_last_error() != JSON_ERROR_NONE) {
//            return false;
//        }
//        if(isset($data['options'])) {
//            $this->options = $data['options'];
//            $result = true;
//        }
//        if(isset($this->options['columns'])) {
//            $this->columns = $this->options['columns'];
//            $result = true;
//        }
//        if(isset($this->options['moduleName'])) {
//            $this->setName($this->options['moduleName']);
//        }
//        return $result;
//    }


    /**
     * Provisioning data before preparing module and there children.
     */
//    public function provision()
//    {
//        parent::provision();
//        $data = $this->Input->getData();
//        unset(
//            $data['moduleName'],
//            $data['ModuleName'],
//            $data['modulename'],
//            $data['framework'],
//            $data['render']
//        );
//
//        $this->setOptions($data);

//    }

    /**
     * prepare content
     */
    public function prepare()
    {
        $this->poolOptions['moduleName'] = $this->getName();

        $this->Template->setVar([
            'moduleName' => $this->getName(),
            'className' => $this->getClassName(),
            'poolOptions' => json_encode($this->poolOptions, JSON_PRETTY_PRINT)
        ]);


        $this->Template->newBlock('tableAttributes');
        foreach($this->getInspectorProperties() as $name => $property) {
            $value = $this->Input->getVar($name);
            if($value === null) continue;

            if($value === $property['value']) {
                continue; // no modification
            }

            $attrName = $property['attribute'] ?? null;
            if($attrName == null) continue; // no html data-attribute

            if(is_bool($value)) {
                $value = bool2string($value);
            }

            $type = $property['type'] ?? null;
            if($type == 'array') {
                $value = htmlspecialchars(json_encode($value, JSON_OBJECT_AS_ARRAY), ENT_COMPAT);
            }
//            echo $name.'='.$value.'<br>';

            $TableAttributeBlock = $this->Template->newBlock('tableAttribute');
            $TableAttributeBlock->setVar([
                    'data-name' => $attrName,
                    'data-value' => $value
                ]
            );
        }

        if($columns = $this->getVar('columns')) {
            $this->Template->newBlock('js_row');
            foreach ($columns as $column) {
                $ColumnBlock = $this->Template->newBlock('js_column');
                foreach ($column as $optName => $attrValue) {
                    $type = '';
                    if (isset($this->getColumnsProperties()[$optName])) {
                        $type = $this->getColumnsProperties()[$optName]['type'];
                    }

                    // translate title
                    if($optName == 'title') {
                        if(strpos($attrValue, '.') !== false) {
                            $attrValue = $this->Weblication->getTranslator()->get($attrValue) ?: $attrValue;
                        }
                    }

                    switch ($type) {
                        case 'boolean':
                            $attrValue = bool2string($attrValue);
                            break;

                        case 'function':
                        case 'json':
                            break;

                        case 'auto':
                            if(is_array($attrValue)) {
                                $attrValue = json_encode($attrValue, JSON_OBJECT_AS_ARRAY);
                                break;
                            }

                        default:
                            $attrValue = '\'' . $attrValue . '\'';
                    }


                    $ColumnAttributeBlock = $this->Template->newBlock('js_columnOption');
                    $ColumnAttributeBlock->setVar([
                        'key' => $optName,
                        'value' => str_replace('{modulename}', $this->getName(), $attrValue)
                    ]);
                }
            }
        }

//        foreach($this->configuration as $optName => $attrValue) {
////            $inpValue = $this->Input->getVar($attrName);
////            $attrValue = $inpValue ?? $attrValue;
//            $attrName = $this->getInspectorProperties()[$optName]['attribute'] ?? null;
//            if($attrName == null) continue; // no data-attribute
//            // $attrType = $this->getDefaultOptions()[$optName]['type'];
//
//            if(is_bool($attrValue)) {
//                $attrValue = bool2string($attrValue);
//            }
//
//            if($attrValue == '') {
//                continue;
//            }
//
//            $TableAttributeBlock = $this->Template->newBlock('tableAttribute');
//            $TableAttributeBlock->setVar([
//                    'data-name' => $attrName,
//                    'data-value' => $attrValue
//                ]
//            );
//        }
//        unset($optName);

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

        $this->Template->leaveBlock();

        // render table
        $render_immediately = $render_ondomloaded = '';
        if($this->getVar('render') == self::RENDER_ONDOMLOADED) {
            $render_ondomloaded = 'ready(() => $Weblication.getModule(\''.$this->getName().'\').render());';
        }
        elseif($this->getVar('render') == self::RENDER_IMMEDIATELY) {
            $render_immediately = '.render()';
        }
        $this->Template->setVar([
            'RENDER_IMMEDIATELY' => $render_immediately,
            'RENDER_ONDOMLOADED' => $render_ondomloaded
        ]);
        parent::prepare();
    }

    /**
     * parse columns
     *
     * @param string $columns
     * @return array
     */
    public function parseColumns(string $columns): array
    {
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
        return $columns;
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