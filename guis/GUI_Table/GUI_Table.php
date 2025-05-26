<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace pool\guis\GUI_Table;

use Configurable;
use pool\classes\Core\Input\Input;
use pool\classes\Core\RecordSet;
use pool\classes\Database\DAO;
use pool\classes\Database\DAO\MySQL_DAO;
use pool\classes\GUI\GUI_Module;

/**
 * Class GUI_Table
 *
 * @package pool\guis\GUI_Table
 * @since 2021-04-08
 */
class GUI_Table extends GUI_Module
{
    use Configurable;

    /**
     * @var array caches dbColumns
     */
    private array $dbColumns = [
        'all' => [],
        'aliasNames' => [],
        'columnNames' => [], // could also be an expression
        'searchable' => [],
        'sortable' => [],
    ];

    private array $inspectorProperties = [
        'buttons' => [
            'attribute' => 'data-buttons',
            'type' => 'function', // todo ModuleConfigurator functions - here buttons
            'value' => '{}',
            'element' => 'input',
            'inputType' => 'text',
        ],
        'buttonsClass' => [
            'attribute' => 'data-buttons-class',
            'type' => 'string',
            'value' => 'secondary',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'cache' => [
            'attribute' => 'data-cache',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'checkOnInit' => [ // extension: mobile
            'attribute' => 'data-check-on-init',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'classes' => [
            'attribute' => 'data-classes',
            'type' => 'string',
            'value' => 'table table-bordered table-hover',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'clickToSelect' => [
            'attribute' => 'data-click-to-select',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'checkboxHeader' => [
            'attribute' => 'data-checkbox-header',
            'type' => 'boolean',
            'value' => true,
            'clientside' => true,
        ],
        'columns' => [
            'attribute' => '',
            'type' => 'json',
            'value' => [],
            'element' => 'tableEditor',
            'configurable' => true,
            'properties' => [ // columnsProperties
                'align' => [
                    'attribute' => 'data-align',
                    'type' => 'string',
                    'value' => null, // default value, see bootstrap-table documentation
                    'element' => 'select', // html element for module configurator
                    'options' => ['', 'left', 'right', 'center'], // html element options for module configurator
                    'clientside' => true, // variable is provided on the client side (js/html)
                ],
                'cardVisible' => [
                    'attribute' => 'data-card-visible',
                    'type' => 'boolean',
                    'value' => true,
                    'clientside' => true,
                ],
                'cellStyle' => [
                    'attribute' => 'data-cell-style',
                    'type' => 'function',
                    'value' => null,
                    'clientside' => true,
                ],
                'checkbox' => [
                    'attribute' => 'data-checkbox',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'clientside' => true,
                    'configurable' => true,
                ],
                'checkboxEnabled' => [
                    'attribute' => 'data-checkbox-enabled',
                    'type' => 'boolean',
                    'value' => true,
                    'clientside' => true,
                ],
                'class' => [
                    'attribute' => 'data-class',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                    'element' => 'input',
                    'inputType' => 'text',
                    'configurable' => true,
                ],
                'clickToSelect' => [
                    'attribute' => 'data-click-to-select',
                    'type' => 'boolean',
                    'value' => true,
                    'clientside' => true,
                    'configurable' => true,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                ],
                'colspan' => [
                    'attribute' => 'data-colspan',
                    'type' => 'number',
                    'value' => null,
                    'clientside' => true,
                ],
                'dbColumn' => [
                    'attribute' => '',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'pool' => true,
                    'clientside' => false,
                ],
                'detailFormatter' => [
                    'attribute' => 'data-detail-formatter',
                    'type' => 'function',
                    'value' => 'function(index, row, $element) { return \'\' }',
                    'clientside' => true,
                ],
                'escape' => [
                    'attribute' => 'data-escape',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox', // tableEditor
                    'clientside' => true,
                ],
                'events' => [
                    'attribute' => 'data-events',
                    'type' => 'function',
                    'value' => null,
                    'element' => 'textarea',
                    'clientside' => true,
                ],
                'falign' => [
                    'attribute' => 'data-falign',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                ],
                'field' => [
                    'attribute' => 'data-field',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'unique' => true, // tableEditor
                    'required' => true, // tableEditor
                    'showColumn' => 0, // tableEditor order
                    'clientside' => true,
                ],
                'filterByDbColumn' => [
                    'attribute' => '',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'pool' => true,
                    'clientside' => false,
                ],
                'filterControl' => [
                    'attribute' => 'data-filter-control',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'select',
                    'options' => [null, 'input', 'select', 'datepicker'],
                    'clientside' => true,
                ],
                'filterControlPlaceholder' => [
                    'attribute' => 'data-filter-control-placeholder',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'clientside' => true,
                ],
                'filterData' => [
                    'attribute' => 'data-filter-data',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'clientside' => true,
                ],
                'filterDefault' => [
                    'attribute' => 'data-filter-default',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input',
                    'inputType' => 'text',
                    'clientside' => true,
                ],
                'filterDatepickerOptions' => [
                    'attribute' => 'data-filter-datepicker-options',
                    'type' => 'json', // should be object json todo json editor
                    'value' => null, // overridden in @see GUI_Table::init
                    'element' => 'input',
                    'inputType' => 'text',
                    'clientside' => true,
                ],
                'footerFormatter' => [
                    'attribute' => 'data-footer-formatter',
                    'type' => 'function',
                    'value' => null,
                    'clientside' => true,
                ],
                'forceExport' => [
                    'attribute' => 'data-force-export',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'clientside' => true,
                ],
                'forceHide' => [
                    'attribute' => 'data-force-hide',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'clientside' => true,
                ],
                'formatter' => [
                    'attribute' => 'data-formatter',
                    'type' => 'function',
                    'value' => null,
                    'element' => 'textarea',
                    'clientside' => true,
                ],
                'halign' => [
                    'attribute' => 'data-halign',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                ],
                'order' => [
                    'attribute' => 'data-order',
                    'type' => 'string',
                    'value' => 'asc',
                    'element' => 'select',
                    'options' => ['asc', 'desc'],
                    'clientside' => true,
                ],
                'poolFormat' => [
                    'attribute' => 'data-pool-format',
                    'type' => 'auto',
                    'element' => 'input',
                    'inputType' => 'text',
                    'value' => '',
                    'pool' => true,
                    'clientside' => true,
                ],
                'poolType' => [
                    'attribute' => 'data-pool-type',
                    'type' => 'string',
                    'element' => 'select',
                    'value' => '',
                    'options' => ['', 'date', 'time', 'date.time', 'number'],
                    'pool' => true,
                    'clientside' => true,
                ],
                'poolUseFormatted' => [
                    'attribute' => 'data-pool-use-formatted',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'caption' => 'Use formatted Value',
                    'tooltip' => 'Uses formatted bs-table value of column in fillControls',
                    'pool' => true,
                    'clientside' => true,
                ],
                'radio' => [
                    'attribute' => 'data-radio',
                    'type' => 'boolean',
                    'value' => false,
                    'clientside' => true,
                ],
                'rowspan' => [
                    'attribute' => 'data-rowspan',
                    'type' => 'number',
                    'value' => null,
                    'clientside' => true,
                ],
                'searchable' => [
                    'attribute' => 'data-searchable',
                    'type' => 'boolean',
                    'value' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox',
                    'showColumn' => 3,
                    'clientside' => true,
                ],
                'searchFormatter' => [
                    'attribute' => 'data-search-formatter',
                    'type' => 'boolean',
                    'value' => true,
                    'clientside' => true,
                    'configurable' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox', // tableEditor
                ],
                'searchHighlightFormatter' => [
                    'attribute' => 'data-search-highlight-formatter',
                    'type' => 'boolean', // could also be |function
                    'value' => true,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'clientside' => true,
                ],
                'showSelectTitle' => [
                    'attribute' => 'data-show-select-title',
                    'type' => 'boolean',
                    'value' => false,
                    'clientside' => true,
                ],
                'sortable' => [
                    'attribute' => 'data-sortable',
                    'type' => 'boolean',
                    'value' => false,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox',
                    'clientside' => true,
                    'configurable' => true,
                ],
                'sorter' => [
                    'attribute' => 'data-sorter',
                    'type' => 'function',
                    'value' => null,
                    'clientside' => true,
                ],
                'sortName' => [
                    'attribute' => 'data-sort-name',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                ],
                'switchable' => [
                    'attribute' => 'data-switchable',
                    'type' => 'boolean',
                    'value' => true,
                    'clientside' => true,
                    'element' => 'input',
                    'inputType' => 'checkbox',
                    'configurable' => true,
                ],
                'title' => [
                    'attribute' => 'data-title',
                    'type' => 'string',
                    'value' => null,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'showColumn' => 1, // tableEditor
                    'required' => false, // tableEditor mandatory field
                    'clientside' => true,
                    'configurable' => true,
                ],
                'titleTooltip' => [
                    'attribute' => 'data-title-tooltip',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                ],
                'valign' => [
                    'attribute' => 'data-valign',
                    'type' => 'string',
                    'value' => null,
                    'clientside' => true,
                ],
                'visible' => [
                    'attribute' => 'data-visible',
                    'type' => 'boolean',
                    'value' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'checkbox', // tableEditor
                    'showColumn' => 2, // tableEditor
                    'clientside' => true,
                ],
                'width' => [
                    'attribute' => 'data-with',
                    'type' => 'number',
                    'value' => null,
                    'clientside' => true,
                    'configurable' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'showColumn' => 4, // tableEditor
                    'required' => false, // tableEditor mandatory field
                ],
                'widthUnit' => [
                    'attribute' => 'data-with-unit',
                    'type' => 'string',
                    'value' => 'px',
                    'clientside' => true,
                    'configurable' => true,
                    'element' => 'input', // tableEditor
                    'inputType' => 'text', // tableEditor
                    'required' => false, // tableEditor mandatory field
                ],
            ],
        ],
        'cookie' => [
            'attribute' => 'data-cookie',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Cookies',
            'configurable' => true,
        ],
        'cookieIdTable' => [
            'attribute' => 'data-cookie-id-table',
            'type' => 'string',
            'value' => '',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'customSearch' => [
            'attribute' => 'data-custom-search',
            'type' => 'function',
            'value' => null, // undefined
        ],
        'customSort' => [
            'attribute' => 'data-custom-sort',
            'type' => 'function',
            'value' => null, // undefined
        ],
        'detailFormatter' => [
            'attribute' => 'data-detail-formatter',
            'type' => 'function',
            'value' => 'function(index, row, element) { return \'\' }',
            'clientside' => true,
        ],
        'detailView' => [
            'attribute' => 'data-detail-view',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'detailViewAlign' => [
            'attribute' => 'data-detail-view-align',
            'type' => 'string',
            'value' => 'left',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'detailViewByClick' => [
            'attribute' => 'data-detail-view-by-click',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'detailViewIcon' => [
            'attribute' => 'data-detail-view-icon',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'exportDataType' => [
            'attribute' => 'data-export-data-type',
            'type' => 'string',
            'value' => 'basic',
            'element' => 'select',
            'options' => ['basic', 'all', 'selected'],
        ],
        'exportTypes' => [
            'attribute' => 'data-export-types',
            'type' => 'array', // todo module configurator
            'value' => ['json', 'xml', 'csv', 'txt', 'sql', 'excel'],
        ],
        'filterControl' => [ // extension: filter-control
            'attribute' => 'data-filter-control',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'caption' => 'Filter Control',
            'configurable' => true,
        ],
        'groupBy' => [
            'attribute' => 'data-group-by',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'groupByField' => [
            'attribute' => 'data-group-by-field',
            'type' => 'array',
            'value' => [],
            'element' => 'input',
            'inputType' => 'text',
        ],
        'groupByToggle' => [
            'attribute' => 'data-group-by-toggle',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'groupByShowToggleIcon' => [
            'attribute' => 'data-group-by-show-toggle-icon',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'groupByCollapsedGroups' => [
            'attribute' => 'data-group-by-collapsed-groups',
            'type' => 'array',
            'value' => [],
            'element' => 'input',
            'inputType' => 'text',
        ],
        'groupByFormatter' => [
            'attribute' => 'data-group-by-formatter',
            'type' => 'function',
            'value' => 'function(value, idx, data) { return \'\' }',
            'clientside' => true,
        ],
        'height' => [
            'attribute' => 'data-height',
            'type' => 'integer',
            'value' => null,
            'element' => 'input',
            'inputType' => 'number',
            'configurable' => true,
        ],
        'heightThreshold' => [ // extension: mobile
            'attribute' => 'data-height-threshold',
            'type' => 'integer',
            'value' => 100,
            'element' => 'input',
            'inputType' => 'number',
            'configurable' => true,
        ],
        'icons' => [
            'attribute' => 'data-icons',
            'type' => 'json',
            'value' => [
                'paginationSwitchDown' => 'fa-caret-square-down',
                'paginationSwitchUp' => 'fa-caret-square-up',
                'refresh' => 'fa-sync',
                'toggleOff' => 'fa-toggle-off',
                'toggleOn' => 'fa-toggle-on',
                'columns' => 'fa-th-list',
                'fullscreen' => 'fa-arrows-alt',
                'detailOpen' => 'fa-plus',
                'detailClose' => 'fa-minus',
                'sort' => 'fa-sort',
                'plus' => 'fa-plus',
                'minus' => 'fa-minus',
            ],
            'element' => 'textarea', // todo json editor
            //'inputType' => '',
            'configurable' => true,
        ],
        'iconSize' => [
            'attribute' => 'data-icon-size',
            'type' => 'string',
            'value' => null, // undefined
            'element' => 'select',
            'options' => [null, 'lg', 'sm'],
            'configurable' => true,
        ],
        'iconsPrefix' => [
            'attribute' => 'data-icons-prefix',
            'type' => 'string',
            'value' => 'fa',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'idField' => [
            'attribute' => 'data-icons-id-field',
            'type' => 'string',
            'value' => null,
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'maintainMetaData' => [
            'attribute' => 'data-maintain-meta-data',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'method' => [
            'attribute' => 'data-method',
            'type' => 'string',
            'value' => 'get',
            'element' => 'select',
            'options' => ['get', 'post'],
            'configurable' => true,
        ],
        'minHeight' => [ // extension: mobile
            'attribute' => 'data-min-height',
            'type' => 'integer',
            'value' => null,
            'element' => 'input',
            'inputType' => 'number',
        ],
        'minWidth' => [ // extension: mobile
            'attribute' => 'data-min-width',
            'type' => 'integer',
            'value' => 562,
            'element' => 'input',
            'inputType' => 'number',
        ],
        'mobileResponsive' => [ // extension: mobile
            'attribute' => 'data-mobile-responsive',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'multiSortStrictSort' => [ // ext: multiple sort
            'attribute' => 'data-multi-sort-strict-sort',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
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
        'poolOnCheck' => [
            'attribute' => 'data-pool-on-check',
            'type' => 'function',
            'value' => null,
            'caption' => 'onCheck',
            'poolEvent' => true,
            'element' => 'textarea',
        ],
        'poolOnClickRow' => [
            'attribute' => 'data-pool-on-click-row',
            'type' => 'function',
            'value' => null,
            'caption' => 'onClickRow',
            'poolEvent' => true,
            'element' => 'textarea',
        ],
        'poolOnUncheck' => [
            'attribute' => 'data-pool-on-uncheck',
            'type' => 'function',
            'value' => null,
            'caption' => 'onUncheck',
            'poolEvent' => true,
            'element' => 'textarea',
        ],
        'poolOnUncheckAll' => [
            'attribute' => 'data-pool-on-uncheck-all',
            'type' => 'function',
            'value' => null,
            'caption' => 'onUncheckAll',
            'poolEvent' => true,
            'element' => 'textarea',
        ],
        'pagination' => [
            'attribute' => 'data-pagination',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'paginationLoop' => [
            'attribute' => 'data-pagination-loop',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'reorderableRows' => [
            'attribute' => 'data-reorderable-rows',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
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
            'configurable' => true,
        ],
        'searchAccentNeutralise' => [
            'attribute' => 'data-search-accent-neutralise',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'searchAlign' => [
            'attribute' => 'data-search-align',
            'type' => 'string',
            'value' => 'right',
            'element' => 'select',
            'options' => ['left', 'right'],
            'configurable' => true,
        ],
        'searchHighlight' => [
            'attribute' => 'data-search-highlight',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'searchOnEnterKey' => [
            'attribute' => 'data-search-on-enter-key',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'searchTimeOut' => [
            'attribute' => 'data-search-time-out',
            'type' => 'integer',
            'value' => 500,
            'element' => 'input',
            'inputType' => 'number',
            'configurable' => true,
        ],
        'selectItemName' => [
            'attribute' => 'data-select-item-name',
            'type' => 'string',
            'value' => 'btSelectItem',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'showColumns' => [
            'attribute' => 'data-show-columns',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showExport' => [
            'attribute' => 'data-show-export',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showExtendedPagination' => [
            'attribute' => 'data-show-extended-pagination',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showFilterControlSwitch' => [
            'attribute' => 'data-show-filter-control-switch',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showFullscreen' => [
            'attribute' => 'data-show-fullscreen',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showMultiSort' => [
            'attribute' => 'data-show-multi-sort',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showMultiSortButton' => [
            'attribute' => 'data-show-multi-sort-button',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showRefresh' => [
            'attribute' => 'data-show-refresh',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showPrint' => [
            'attribute' => 'data-show-print',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'showSearchClearButton' => [
            'attribute' => 'data-show-search-clear-button',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'showToggle' => [
            'attribute' => 'data-show-toggle',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'sidePagination' => [
            'attribute' => 'data-side-pagination',
            'type' => 'string',
            'value' => 'client',
            'element' => 'select',
            'options' => ['client', 'server'],
            'configurable' => true,
        ],
        'singleSelect' => [
            'attribute' => 'data-single-select',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'smartDisplay' => [
            'attribute' => 'data-smart-display',
            'type' => 'boolean',
            'value' => true,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'sortName' => [
            'attribute' => 'data-sort-name',
            'type' => 'string',
            'value' => '',
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'sortOrder' => [
            'attribute' => 'data-sort-order',
            'type' => 'string',
            'value' => null,
            'element' => 'select',
            'options' => [null, 'asc', 'desc'],
            'configurable' => true,
        ],
        'sortPriority' => [ // ext: multiple sort
            'attribute' => 'data-sort-priority',
            'type' => 'array',
            'value' => null,
            'element' => 'input',
            'inputType' => 'text',
        ],
        'sortReset' => [
            'attribute' => 'data-sort-reset',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'sortable' => [
            'attribute' => 'data-sortable',
            'type' => 'boolean',
            'value' => true,
            'caption' => 'Sortable',
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
        'stickyHeader' => [
            'attribute' => 'data-sticky-header',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
        ],
        'stickyHeaderOffsetLeft' => [
            'attribute' => 'data-sticky-header-offset-right',
            'type' => 'integer',
            'value' => 0,
            'element' => 'input',
            'inputType' => 'number',
        ],
        'stickyHeaderOffsetY' => [
            'attribute' => 'data-sticky-header-offset-y',
            'type' => 'integer',
            'value' => 0,
            'element' => 'input',
            'inputType' => 'number',
        ],
        'theadClasses' => [
            'attribute' => 'data-thead-classes',
            'type' => 'string',
            'value' => '',
            'element' => 'input',
            'inputType' => 'text',
        ],
        'toolbar' => [
            'attribute' => 'data-toolbar',
            'type' => 'string',
            'value' => null,
            'element' => 'input',
            'inputType' => 'text',
            'configurable' => true,
        ],
        'uniqueId' => [
            'attribute' => 'data-unique-id',
            'type' => 'string',
            'element' => 'input',
            'inputType' => 'text',
            'value' => null, // undefined
            'configurable' => true,
        ],
        'paginationParts' => [
            'attribute' => 'data-pagination-parts',
            'type' => 'array',
            'element' => 'input',
            'inputType' => 'text',
            'value' => ['pageInfo', 'pageSize', 'pageList'],
        ],
        'pageSize' => [
            'attribute' => 'data-page-size',
            'type' => 'integer',
            'element' => 'input',
            'inputType' => 'number',
            'value' => 10,
        ],
        'pageList' => [
            'attribute' => 'data-page-list',
            'type' => 'array',
            'element' => 'input',
            'inputType' => 'text',
            'value' => [10, 25, 50, 100],
        ],
        'url' => [
            'attribute' => 'data-url',
            'type' => 'string',
            'value' => null, // undefined
            'element' => 'input',
            'inputType' => 'text',
            'caption' => 'Url',
            'configurable' => true,
        ],
        'virtualScrollItemHeight' => [
            'attribute' => 'data-virtual-scroll-item-height',
            'type' => 'integer',
            'value' => null,
            'element' => 'input',
            'inputType' => 'number',
        ],
        'visibleSearch' => [
            'attribute' => 'data-visible-search',
            'type' => 'boolean',
            'value' => false,
            'element' => 'input',
            'inputType' => 'checkbox',
            'configurable' => true,
        ],
    ];

    protected array $options = [];

    protected array $columns = [];

    protected array $poolOptions = [];

    public const int RENDER_NONE = 0;
    public const int RENDER_IMMEDIATELY = 1;
    public const int RENDER_ONDOMLOADED = 2;
    //    private string $version = '1.19.1';

    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVar('framework', 'bs4');
        $this->Defaults->addVar('render', self::RENDER_ONDOMLOADED);
        $this->Defaults->addVar('url', null);
        $this->Defaults->addVar('columns', null);

        // 09.12.21, AM, override default filterDatepickerOptions (language is unknown in property); version before <= 1.19.1
        // @used-by GUI_Table.js
        $this->inspectorProperties['columns']['properties']['filterDatepickerOptions']['value'] =
            '{"autoclose":true, "clearBtn":true, "todayHighlight":true, "language":"'.$this->Weblication->getLanguage().'"}';

        parent::init($superglobals);

        //        $this->defaultOptions['moduleName']['value'] = $this->getName();

        // default time formats
        $this->poolOptions['time.strftime'] = $this->poolOptions['time.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.time');
        $this->poolOptions['date.strftime'] = $this->poolOptions['date.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.date');
        $this->poolOptions['date.time.strftime'] = $this->poolOptions['date.time.strftime'] ?? $this->Weblication->getDefaultFormat('strftime.date.time');
        $this->poolOptions['number'] = $this->poolOptions['number'] ?? $this->Weblication->getDefaultFormat('number');

        if ($this->Input->getVar('columns') != null) {
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
     */
    public function loadFiles(): static
    {
        $fw = $this->getVar('framework');
        $tpl = $this->Weblication->findTemplate("tpl_table_$fw.html", 'GUI_Table', true);
        $this->Template->setFilePath('stdout', $tpl);
        $this->Weblication->getFrame()?->getHeadData()?->addClientWebAsset('js', self::class, baseLib: true);
        parent::loadFiles();
        return $this;
    }

    // public function getInspectorProperties(): array
    // {
    //     return $this->inspectorProperties + parent::getInspectorProperties();
    // }

    public function getInspectorProperties(): array
    {
        return $this->inspectorProperties + $this->getDefaultInspectorProperties();
    }

    public function getColumnsProperties(): array
    {
        return $this->getInspectorProperties()['columns']['properties'];
    }

    public function getColumnProperty(string $property): array
    {
        return $this->getColumnsProperties()[$property];
    }

    /**
     * @return $this
     */
    public function setColumns(array $columns): GUI_Table
    {
        $defaultColumnOptions = $this->getColumnsProperties();

        foreach ($columns as $z => $column) {
            $field = $column['field'] ?? $z;
            foreach ($column as $key => $value) {
                if (isset($defaultColumnOptions[$key])) {
                    $type = $defaultColumnOptions[$key]['type'] ?? '';
                    switch ($type) {
                        case 'boolean':
                            if (is_string($value)) {
                                $value = string2bool($value);
                            }
                            break;
                    }

                    if ($defaultColumnOptions[$key]['value'] != $value) {
                        //                        $this->defaultColumnOptions[$key]['type']
                        $this->columns[$z][$key] = $value;
                    }
                } else {
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
        }

        //        $this->columns = $columns;
        return $this;
    }

    /**
     * @return array all columns
     */
    public function getColumns(): array
    {
        return $this->Input->getVar('columns', []);
    }

    //    public function getVersion()
    //    {
    //        return $this->version;
    //    }

    /**
     * @param string $which possible keys: all, aliasNames, columnNames (assoc array), searchable (assoc array), (@todo filterable/filterSelect)
     * @param DAO|null $DAO if given, escape column names
     * @return array only columns for database and sql statement passing
     */
    public function getDBColumns(string $which = 'all', ?DAO $DAO = null): array
    {
        // todo if columns change / new configuration, reread with loop
        if ($this->dbColumns[$which]) {
            return $this->dbColumns[$which];
        }

        $this->dbColumns = [
            'all' => [],
            'aliasNames' => [],
            'columnNames' => [], // could also be an expression
            'searchable' => [],
            'sortable' => [],
        ];
        $columns = $this->Input->getVar('columns');
        foreach ($columns as $column) {
            if (!isset($column['field'])) continue; // necessary
            if (!isset($column['dbColumn'])) continue;
            $dbColumn = $column['dbColumn'];
            if ($dbColumn === '') continue;

            $dbColumn = $DAO ? $DAO->encloseColumnName($dbColumn) : $dbColumn;
            $this->dbColumns['all'][] = '('.$dbColumn.')`'.$column['field'].'`';
            $this->dbColumns['aliasNames'][] = $column['field'];

            $expr = $dbColumn;
            $isSubQuery = stripos($expr, 'select') === 0;
            if ($isSubQuery) {
                $expr = '('.$expr.')';
            }

            $this->dbColumns['columnNames'][$column['field']] = $expr;

            $filterControl = $column['filterControl'] ?? '';
            $filterByDbColumn = $column['filterByDbColumn'] ?? '';

            // 29.04.22, AM, workaround: bootstrap-table uses input datetime since v1.20.0. js/html always sends an english date!
            // to prevent the auto date_format in DAO::makeFilter we overwrite the filterByDbColumn if it is not set
            //            if($filterControl == 'datepicker' and $filterByDbColumn == '') {
            //                if(version_compare($this->getVersion(), '1.20.0', '>=')) {
            //                    $column['filterByDbColumn'] = $expr;
            //                }
            //            }

            $assoc = [
                'expr' => $expr, // select expression
                'alias' => $column['field'], // alias name
                'type' => $column['poolType'] ?? '', // data type
                'filterControl' => $filterControl, // filterControl
                'filterByDbColumn' => $filterByDbColumn, // column
            ];

            $searchable = $column['searchable'] ?? $this->getColumnProperty('searchable')['value'];
            if ($searchable) {
                $this->dbColumns['searchable'][] = $assoc;
            }
            $sortable = $column['sortable'] ?? $this->getColumnProperty('sortable')['value'];
            if ($sortable) {
                $this->dbColumns['sortable'][$column['field']] = $expr;
            }
        }

        return $this->dbColumns[$which];
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
    //    }
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
    protected function prepare(): void
    {
        $this->poolOptions['moduleName'] = $this->getName();

        $this->Template->setVars([
            'moduleName' => $this->getName(),
            'className' => $this->getClassName(),
            'poolOptions' => json_encode($this->poolOptions, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
        ]);
        $this->setClientVar('poolOptions', $this->poolOptions);


        $this->Template->newBlock('tableAttributes');
        foreach ($this->getInspectorProperties() as $name => $property) {
            $value = $this->Input->getVar($name);
            if ($value === null) continue;

            if ($value === $property['value']) {
                continue; // no modification
            }

            $attrName = $property['attribute'] ?? null;
            if ($attrName === null) continue; // no html data-attribute

            if (is_bool($value)) {
                $value = bool2string($value);
            }

            $type = $property['type'] ?? null;
            if ($type === 'json' || $type === 'array') {
                $value = htmlspecialchars(json_encode($value, JSON_THROW_ON_ERROR), ENT_COMPAT, 'UTF-8');
            }
            //            echo $name.'='.$value.'<br>';

            $TableAttributeBlock = $this->Template->newBlock('tableAttribute');
            $TableAttributeBlock->setVars([
                'data-name' => $attrName,
                'data-value' => $value,
            ],
            );
        }

        if ($columns = $this->getVar('columns')) {
            $clientColumns = [];
            $columnProperties = $this->getColumnsProperties();
            // $this->Template->newBlock('js_row');
            $c = 0;
            foreach ($columns as $column) {
                // $ColumnBlock = $this->Template->newBlock('js_column');
                foreach ($column as $optName => $attrValue) {
                    $type = '';
                    $clientside = false;
                    $defaultValue = null;
                    if (isset($columnProperties[$optName])) {
                        $type = $columnProperties[$optName]['type'];
                        $clientside = $columnProperties[$optName]['clientside'] ?? false;
                        $defaultValue = $columnProperties[$optName]['value'];
                    }

                    if (!$clientside) {
                        continue;
                    }

                    // translate title
                    if ($optName == 'title') {
                        if (str_contains($attrValue, '.')) {
                            $attrValue = $this->Weblication->getTranslator()->getTranslation($attrValue, $attrValue);
                        }
                    }

                    $attrValue = match ($type) {
                        'string' => $attrValue !== '' ? strtr($attrValue, ['{modulename}' => $this->getName()]) : (($defaultValue === null) ? null : $attrValue),
                        'number' => $attrValue !== '' ? (int)$attrValue : null,
                        default => $attrValue !== '' ? $attrValue : null,
                    };


                    if ($defaultValue !== $attrValue) {
                        $clientColumns[$c][$optName] = $attrValue;
                    }

                    //                    switch ($type) {
                    //                        case 'boolean':
                    //                            $attrValue = bool2string($attrValue);
                    //                            break;
                    //
                    //                        case 'function':
                    //                        case 'json':
                    //                            break;
                    //
                    //                        case 'auto':
                    //                            if(is_array($attrValue)) {
                    //                                $attrValue = json_encode($attrValue, JSON_OBJECT_AS_ARRAY);
                    //                                break;
                    //                            }
                    //
                    //                        default:
                    //                            $attrValue = '\'' . $attrValue . '\'';
                    //                    }


                    //                    $ColumnAttributeBlock = $this->Template->newBlock('js_columnOption');
                    //                    $ColumnAttributeBlock?->setVar([
                    //                        'key' => $optName,
                    //                        'value' => $attrValue
                    //                    ]);
                }
                $c++;
            }
            $this->setClientVar('columns', $clientColumns);
        }

        $this->Template->leaveBlock();
        $this->setClientVar('render', (int)$this->getVar('render'));
    }

    /**
     * parse columns
     */
    public function parseColumns(string $columns): array
    {
        if (isValidJSON($columns)) {
            $columns = json_decode($columns, true);
        } else {
            $columnsArray = explode(';', $columns);

            $columns = [];
            foreach ($columnsArray as $column) {
                $columnAttr = explode('|', $column);
                $column = [];
                $i = 0;
                foreach ($columnAttr as $attr) {
                    $attrValue = trim($attr);
                    if (str_contains($attrValue, '=')) {
                        $attr = explode('=', $attrValue);
                        $key = $attr[0];
                        $val = $attr[1];
                        if ($key == 'field') $field = $val;
                        $column[$key] = $val;
                    } elseif ($i == 0) {
                        $field = trim($columnAttr[$i]) ?? '';
                        if ($field == '') continue;
                        $column['field'] = $field;
                        if (count($columnAttr) == 1) { // no title given
                            $title = $columnAttr[$i] ?? $field;
                            $column['title'] = $title;
                        }
                    } elseif ($i == 1 and isset($field)) {
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
     * Creates data format for the bootstrap table (clientside transfer format)
     */
    static function getRowSetAsArray(RecordSet $ResultSet, int $total): array
    {
        $return = [];
        $return['total'] = $total;
        //            $return['totalNotFiltered'] = $total;
        $return['rows'] = $ResultSet->getRaw();
        return $return;
    }

    /**
     * Creates server-side filter rules of a bootstrap table for database queries (DAO's)
     *
     * @param MySQL_DAO $DAO DAO to use
     * @param string $search search terms (e.g. from client side request)
     * @param string $filter filter (e.g. from client side request)
     * @param array $mandatoryFilter it involves strict and mandatory constraints.
     * @param array $searchFilter it involves additional search patterns
     * @throws JsonException
     * @see MySQL_DAO::buildFilter()
     */
    public function buildFilter(MySQL_DAO $DAO, string $search, string $filter, array $mandatoryFilter = [], array $searchFilter = []): array
    {
        $filterRules = [];
        if (isValidJSON($filter)) {
            $filterRules = json_decode($filter, JSON_OBJECT_AS_ARRAY, 512, JSON_THROW_ON_ERROR);
        }

        // auto filter
        $filterRules = array_merge($DAO->makeFilter($this->getDBColumns('searchable'), $search, $filterRules), $searchFilter);
        if ($filterRules && $mandatoryFilter) {
            array_unshift($filterRules, '(');
            $filterRules[] = ')';
            $filterRules[] = 'AND';
        }
        return array_merge($filterRules, $mandatoryFilter);
    }

    /**
     * Creates serverside sorting rules for a Bootstrap Table
     *
     * @param string $sort sort column (e.g. from client side request)
     * @param string $order sort order (e.g. from client side request)
     * @param array|null $multiSort optional: multiple sort columns from client side request
     * @return array
     * @see MySQL_DAO::buildSorting()
     */
    public function buildSorting(string $sort, string $order, ?array $multiSort = null): array
    {
        $columns = $this->getDBColumns('sortable');
        // SORTING
        $sorting = [];
        if ($sort and $order) {
            $sorting = [$columns[$sort] => $order];
        } // MULTIPLE SORTING
        elseif ($multiSort) {
            foreach ($multiSort as $x => $item) {
                $sort = $item['sortName'];
                $order = $item['sortOrder'];
                $sorting[$columns[$sort]] = $order;
            }
        }
        return $sorting;
    }

    /**
     * Creates serverside limit rules for a Bootstrap Table
     *
     * @param int $offset offset (e.g. from client side request)
     * @param int|null $limit limit (e.g. from client side request)
     * @param int|null $total optional: total number of rows
     * @return array
     * @see MySQL_DAO::buildLimit()
     */
    public function buildLimit(int $offset = 0, ?int $limit = null, ?int $total = null): array
    {
        if (is_null($limit)) {
            return [];
        }
        $offset = !is_null($total) && $total <= $limit ? 0 : $offset;
        return [$offset, $limit];
    }
}
