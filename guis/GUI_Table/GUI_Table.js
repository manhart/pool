/*
 * POOL
 *
 * table.js created at 08.04.21, 13:17
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

'use strict';

// 09.12.2021, AM, override default filterDatepickerOptions, because the default is undefined
jQuery().bootstrapTable.columnDefaults.filterDatepickerOptions = {
    'autclose': true,
    'clearBtn': true,
    'todayHighlight': true,
    'language': document.documentElement.lang
}

// $.BootstrapTable = class extends $.BootstrapTable {
// }
class GUI_Table extends GUI_Module
{
    /* > ES7
    static const STYLE_DEFAULT = 'toast';
    static const STYLE_ERROR = 'error';
    static const STYLE_INFO = 'info';
    static const STYLE_SUCCESS = 'success';
    static const STYLE_WARNING = 'warning';
    */
    url = '';

    $table = undefined;
    table = undefined;

    // name = 'GUI_Table';

    formats = [];

    options = {};

    columns = [];
    columnNames = [];

    /**
     * Accessed by bs-table filterControl extension
     *
     * @type {{}}
     */
    filterData = {};

    /**
     * unique ids of one page
     *
     * @private
     * @type {[]}
     */
    pageIds = [];

    /**
     * selections
     *
     * @private
     * @type {[]}
     */
    selections = [];

    rendered = false;
    inside_render = false;

    forceRefreshOptions = false;

    scrollPosition;



    // poolColumnOptions = {}; // poolOptions

    /**
     * Defaults
     *
     * @constructor
     */
    constructor(name)
    {
        super(name);

        this.options.responseHandler = this.responseHandler


        // let columns = {
        //     columns: [{
        //         field: 'idLoginProtocol',
        //         title: 'ID',
        //     }, {
        //         field: 'username',
        //         title: 'Benutzer',
        //     }, {
        //         field: 'loginDateTime',
        //         title: 'eingeloggt am',
        //         formatter: this.formatDateTime
        //     }]
        // }
        // this.table.bootstrapTable(columns);

        //this.table.bootstrapTable('refreshOptions', options);
        return this;
    }

    /**
     * @param options
     * @returns {GUI_Table}
     */
    setConfiguration(options)
    {
        // console.debug(this.getName() + '.setConfiguration', options['poolOptions']);
        let poolOptions = {};
        if('poolOptions' in options) {
            poolOptions = options['poolOptions'];
            delete options['poolOptions'];
        }

        this.formats['time'] = '%H:%M';
        if('time.strftime' in poolOptions) {
            this.formats['time'] = poolOptions['time.strftime'];
        }
        this.formats['date'] = '%Y-%m-%d';
        if('date.strftime' in poolOptions) {
            this.formats['date'] = poolOptions['date.strftime'];
        }
        this.formats['date.time'] = '%Y-%m-%d %H:%M';
        if('date.time.strftime' in poolOptions) {
            this.formats['date.time'] = poolOptions['date.time.strftime'];
        }
        this.formats['number'] = {
            decimal_separator: '.',
            decimals: 2,
            thousands_separator: ','
        }
        if('number' in poolOptions) {
            this.formats['number'] = poolOptions['number'];
        }

        // check unsupported data-attributes for events and parse the strings to a function
        if(!this.options.poolOnCheck) {
            if(this._getTable().dataset.poolOnCheck) {
                options.poolOnCheck = this._getTable().dataset.poolOnCheck;
            }
        }
        if(!this.options.poolOnClickRow) {
            if(this._getTable().dataset.poolOnClickRow) {
                options.poolOnClickRow = this._getTable().dataset.poolOnClickRow;
            }
        }
        if(!this.options.poolOnUncheck) {
            if(this._getTable().dataset.poolOnUncheck) {
                options.poolOnUncheck = this._getTable().dataset.poolOnUncheck;
            }
        }
        if(!this.options.poolOnUncheckAll) {
            if(this._getTable().dataset.poolOnUncheckAll) {
                options.poolOnUncheckAll = this._getTable().dataset.poolOnUncheckAll;
            }
        }

        if(!this.options.poolFillControls) {
            if(this._getTable().dataset.poolFillControls) {
                options.poolFillControls = this._getTable().dataset.poolFillControls;
            }
        }
        if(!this.options.poolFillControlsContainer) { // @deprecated
            if(this._getTable().dataset.poolFillControlsContainer) {
                options.poolFillControlsContainer = this._getTable().dataset.poolFillControlsContainer;
            }
        }
        if(!this.options.poolFillControlsSelector) {
            if(this._getTable().dataset.poolFillControlsSelector) {
                options.poolFillControlsSelector = this._getTable().dataset.poolFillControlsSelector;
            }
        }

        if(!this.options.poolClearControls) {
            if(this._getTable().dataset.poolFillControls) {
                options.poolClearControls = this._getTable().dataset.poolClearControls;
            }
        }
        if(!this.options.poolClearControlsSelector) {
            if(this._getTable().dataset.poolClearControlsSelector) {
                options.poolClearControlsSelector = this._getTable().dataset.poolClearControlsSelector;
            }
        }

        if(!isEmpty(options)) {
            this.setOptions(options);
        }

        // automation
        // if('poolColumnOptions' in poolOptions) {
        //     this.poolColumnOptions = poolOptions['poolColumnOptions'];
        //     delete poolOptions['poolColumnOptions'];
        // }

        return this;
    }

    setOptions(options = {})
    {
        // console.debug(this.getName() + '.setOptions', options);

        this.options = options;

        if(options.responseHandler) {
            // todo save responseHandler
        }
        this.options.responseHandler = this.responseHandler

        return this;
    }

    setColumns(columns = [])
    {
        columns.forEach((column, z) => {
            let field = ('field' in column) ? column['field'] : z;
            this.columnNames[field] = z;
            // if(!(field in this.poolColumnOptions)) {
            //     return;
            // }

            if(!('poolType' in column)) {
                return;
            }

            // automation for special poolType's
            // let poolType = '';
            // if('poolType' in this.poolColumnOptions[field]) {
            //     poolType = this.poolColumnOptions[field]['poolType'];
            // }
            // let poolFormat = '';
            // if('poolFormat' in this.poolColumnOptions[field]) {
            //     poolFormat = this.poolColumnOptions[field]['poolFormat']
            // }
            let poolType = column['poolType'];
            // if('poolType' in column) {
            //     poolType = column['poolType'];
            // }

            let poolFormat = '';
            if('poolFormat' in column) {
                poolFormat = column['poolFormat']
            }

            let format = poolFormat ? poolFormat : this.formats[poolType];

            let poolUseFormatted = false;
            if('poolUseFormatted' in column) {
                poolUseFormatted = column['poolUseFormatted'];
            }
            // console.debug(field, poolType, format);

            // if('formatter' in column) {
            //     column['formatter'] =
            // }
            //

            switch(poolType) {
                case 'date.time':
                case 'date':
                case 'time':

                    if(!('formatter' in column)) {
                        column['formatter'] = (value, row, index, field) => this.strftime(value, row, index, field, format, poolUseFormatted);
                    }
                    break;

                case 'number':

                    if(!('formatter' in column)) {
                        column['formatter'] = (value, row, index, field) => this.number_format(value, row, index, field, format, poolUseFormatted);
                    }
                    break;
            }
        });
        this.columns = columns;
        return this;
    }

    /**
     * return pool format
     *
     * @param poolType
     * @returns {*}
     */
    getFormat(poolType)
    {
        return this.formats[poolType];
    }

    /**
     *
     * @returns {*[]}
     */
    getColumns()
    {
        return this.columns;
    }

    /**
     * set column option
     *
     * @param field
     * @param options
     * @returns {GUI_Table}
     */
    setColumnOptions(field, options = [])
    {
        if(isEmpty(options)) {
            return this;
        }

        // console.debug('setColumnOptions', field, options);
        if(field in this.columnNames) {
            this.columns[this.columnNames[field]] = Object.assign({}, this.columns[this.columnNames[field]], options);
            this.forceRefreshOptions = true;
        }
        // console.debug('setColumnOptions', this.columns);
        // for(let c = 0; c<this.columns.length; c++) {
        //     // console.debug(c);
        //     if (this.columns[c].field == field) {
        //         // console.debug('treffer');
        //         this.columns[c] = Object.assign({}, this.columns[c], options);
        //         result = true;
        //         break;
        //     }
        // }
        // console.debug('Result of setColumnOptions', this.columns);
        return this;
    }

    /**
     * filterData is required by the filterControl extension of the bs table as soon as the bs-table is changed to server-side pagination.
     *
     * @param filterData
     */
    setFilterData(filterData)
    {
        // filterData is required by the filterControl extension of the bs table as soon as the bs-table is changed to server-side pagination.
        this.filterData = filterData;
        for(let column in this.filterData) {
            this.setColumnOptions(column, {
                filterData: 'obj:$'+this.getName()+'.filterData.' + column
            });
        }
    }

    getTable()
    {
        if(!this.$table) {
            // warning if the developer uses a wrong order
            if(!this.inside_render && !this.rendered) {
                console.warn(this.getName() + '.getTable() is called before ' + this.getName() + '.render()! Not all table options ' +
                    'were passed. Please check the order of the method calls.');
            }
            this.$table = $('#' + this.getName())
            .on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', this.onCheckUncheckRows)
            // .on('refresh-options.bs.table', this.onRefreshOptions)
            .on('click-row.bs.table', this.onClickRow)
            .on('check.bs.table', this.onCheck)
            .on('uncheck.bs.table', this.onUncheck)
            .on('uncheck-all.bs.table', this.onUncheckAll)
            ;
        }
        return this.$table;
    }

    _getTable()
    {
        if(!this.table) {
            this.table = document.getElementById(this.getName());
        }
        return this.table;
    }

    /**
     * renders bootstrap-table. should only be called once! use method refresh instead
     *
     * @param options
     * @returns {GUI_Table}
     */
    render(options = {})
    {
        this.inside_render = true;
        this.options['columns'] = this.columns;
        if(!isEmpty(options)) {
            this.options = Object.assign({}, this.options, options);
        }

        if(!this.rendered) {
            // console.debug(this.getName() + ' start rendering', this.options);
            this.getTable().bootstrapTable(
                this.options
            );
            // console.debug(this.getName() + '.rendered');
        }
        else {
            console.info(this.getName() + '.render has already been called once.')
            this.refresh(options);
        }
        this.inside_render = false;
        this.rendered = true;
        return this;
    }

    refresh(options = {}, silent = false)
    {
        // todo stelle Seite wieder her
        if(!isEmpty(options) || this.forceRefreshOptions) {
            this.options = Object.assign({}, this.options, options);
            console.debug(this.getName() + '.refreshOptions', this.options);
            this.scrollPosition = this.getScrollPosition();
            this.getTable().bootstrapTable('refreshOptions', this.options);
            this.forceRefreshOptions = false;
        }
        else {
            let params = {};
            if(silent) params.silent = true;
            this.getTable().bootstrapTable('refresh', params);
            console.debug(this.getName() + '.refreshed', params);
        }
        return this;
    }

    /**
     *
     * @returns {integer}
     */
    getScrollPosition()
    {
        return this.getTable().bootstrapTable('getScrollPosition');
    }

    /**
     * get unique id
     *
     * @returns string
     */
    getUniqueId()
    {
        return this.getOption('uniqueId');
    }

    /**
     * get bootstrap option
     *
     * @param option
     * @returns {*}
     */
    getOption(option)
    {
        return this.getTable().bootstrapTable('getOptions')[option];
    }


    onClickRow = (evt, row, $element, field) => {
        // console.debug(this.getName() + '.onClickRow', row, $element, field);

        if(this.getOption('poolOnClickRow')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnClickRow'), [row, $element, field], null)
        }
    }


    onRefreshOptions = (options) => {
    }

    /**
     * onCheck event
     *
     * @param evt
     * @param row
     * @param $element
     */
    onCheck = (evt, row, $element) =>
    {
        // console.debug('onCheck', row, $element);
        if(this.getOption('poolFillControls')) {
            if(this.getOption('poolFillControlsContainer')) {
                fillControls(this.getOption('poolFillControlsContainer'), row, true);
            }
            if(this.getOption('poolFillControlsSelector')) {
                fillControls(this.getOption('poolFillControlsSelector'), row, false);
            }
        }

        if(this.getOption('poolOnCheck')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnCheck'), [row, $element], null)
        }
    }

    /**
     * onUncheck event
     *
     * @param evt
     * @param row
     * @param $element
     */
    onUncheck = (evt, row, $element) => {
        // console.debug('onUncheck');
        if(this.getOption('poolClearControls') && this.getOption('poolClearControlsSelector')) {
            clearControls(this.getOption('poolClearControlsSelector'));
        }

        if(this.getOption('poolOnUncheck')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnUncheck'), [row, $element], null)
        }
    }

    /**
     * onUncheckAll event
     *
     * @param evt
     * @param rowsAfter
     * @param rowsBefore
     */
    onUncheckAll = (evt, rowsAfter, rowsBefore) =>
    {
        // console.debug('onUncheckAll');
        if(this.getOption('poolClearControls') && this.getOption('poolClearControlsSelector')) {
            clearControls(this.getOption('poolClearControlsSelector'));
        }

        if(this.getOption('poolOnUncheckAll')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnUncheckAll'), [rowsAfter, rowsBefore], null)
        }
    }

    /**
     * get selected rows
     *
     * @returns {*}
     */
    getSelections()
    {
        return this.getTable().bootstrapTable('getSelections');
    }

    /**
     * get selected unique ids
     */
    getSelectedUniqueIds()
    {
        let uniqueId = this.getUniqueId()
        if(!uniqueId) {
            return [];
        }

        return this.getSelections().map(function(row) {
            return row[uniqueId]
        })
    }

    /**
     * Check a row by array of values
     *
     * @param field name of the field used to find records (ID column)
     * @param values array of values for rows to check
     * @param onlyCurrentPage (default false): If true only the visible dataset will be checked. If pagination is used the other pages will be ignored.
     */
    checkBy(field, values, onlyCurrentPage=false)
    {
        this.getTable().bootstrapTable('checkBy', { field : field, values : values, onlyCurrentPage: onlyCurrentPage });
    }

    /**
     * Uncheck a row by array of values
     *
     * @param field name of the field used to find records.
     * @param values array of values for rows to uncheck.
     * @param onlyCurrentPage (default false): If true only the visible dataset will be unchecked. If pagination is used the other pages will be ignored.
     */
    uncheckBy(field, values, onlyCurrentPage=false)
    {
        this.getTable().bootstrapTable('uncheckBy', { field : field, values : values, onlyCurrentPage: onlyCurrentPage });
    }

    /**
     * check a row
     *
     * @param index
     */
    check(index = 0)
    {
        this.getTable().bootstrapTable('check', index);
    }

    /**
     * check all rows
     */
    checkAll()
    {
        if(this.getOption('pagination')) {
            this.getTable().bootstrapTable('togglePagination').bootstrapTable('checkAll').bootstrapTable('togglePagination');
        }
        else {
            this.getTable().bootstrapTable('checkAll');
        }
    }

    /**
     * uncheck all rows
     */
    uncheckAll()
    {
        if(this.getOption('pagination')) {
            this.getTable().bootstrapTable('togglePagination').bootstrapTable('uncheckAll').bootstrapTable('togglePagination');
        }
        else {
            this.getTable().bootstrapTable('uncheckAll');
        }
    }

    /**
     * insert row and check row
     *
     * @param index
     * @param row record
     * @param check (optional) calls check
     * @param paging (optional) calls selectPage
     */
    insertRow(index, row, check = true, paging = true)
    {
        // console.debug(this.getName()+'.insertRow', index, row, check, paging);
        this.getTable().bootstrapTable('insertRow', {
            index: index,
            row: row
        });

        let uniqueId = this.getUniqueId()
        if(uniqueId && row[uniqueId] != '') {
            this.pageIds.push(row[uniqueId]);

            // alternate
            // this.checkBy(uniqueId, [row[uniqueId]]);
        }
        // 29.03.22, AM, fix correct index
        index = this.getData().indexOf(row);

        if(paging) {
            let pageSize = this.getOption('pageSize');
            let pageNumber = Math.ceil((index+1) / pageSize);
            this.getTable().bootstrapTable('selectPage', pageNumber);
        }

        if(check) {
            this.check(index);
        }

        return index;
    }

    /**
     * Get the loaded data of table at the moment that this method is called
     *
     * @param useCurrentPage if set to true the method will return the data only in the current page
     * @param includeHiddenRows if set to false the method will exclude the hidden rows
     * @param unfiltered if set to false the method will exclude filtered data
     * @param formatted if set to true the method will return the formatted value
     * @returns {*}
     */
    getData(useCurrentPage = false, includeHiddenRows = true, unfiltered = false, formatted = false)
    {
        let params = {
            useCurrentPage: useCurrentPage,
            includeHiddenRows: includeHiddenRows,
            unfiltered: unfiltered,
            formatted: formatted
        }
        return this.getTable().bootstrapTable('getData', params);
    }

    /**
     * Get data from table, the row that contains the id passed by parameter.
     *
     * @param id
     * @returns {*}
     */
    getRowByUniqueId(id)
    {
        let params = {
            id: parseInt(id, 10)
        }
        return this.getTable().bootstrapTable('getRowByUniqueId', params);
    }

    /**
     * Update one cell, the params contain following properties:
     *
     * @param index
     * @param field
     * @param value
     * @returns {*}
     */
    updateCell(index, field, value)
    {
        let params = {
            index: index,
            field: field,
            value: value
        }
        return this.getTable().bootstrapTable('updateCell', params);
    }

    /**
     * update the specified row(s)
     *
     * @param id id where the id should be the uniqueId field assigned to the table
     * @param row the new row data
     * @param replace (optional) set to true, to replace the row instead of extending
     */
    updateByUniqueId(id, row, replace = false)
    {
        let params = {
            id: parseInt(id, 10),
            row: row,
            replace: replace
        }
        // console.debug('updateByUniqueId', params);
        this.getTable().bootstrapTable('updateByUniqueId', params);
    }

    /**
     * remove rows
     *
     * @param params
     * @returns {GUI_Table}
     */
    remove(params)
    {
        this.getTable().bootstrapTable('remove', params);
        return this;
    }

    /**
     * remove rows by unique id
     *
     * @param uniqueId
     * @returns {GUI_Table}
     */
    removeByUniqueId(uniqueId)
    {
        this.getTable().bootstrapTable('removeByUniqueId', uniqueId);
        return this;
    }

    removeAll()
    {
        this.pageIds = [];
        this.selections = [];
        this.getTable().bootstrapTable('removeAll');
    }

    /**
     * remove selected rows
     *
     * @returns {GUI_Table}
     */
    removeSelectedRows()
    {
        let uniqueId = this.getUniqueId();
        if(!uniqueId) {
            return this;
        }
        this.getTable().bootstrapTable('remove', {
            field: uniqueId,
            values: this.getSelectedUniqueIds()
        })
    }

    /**
     * save selections
     */
    onCheckUncheckRows = (evt) => {

        let ids = this.getSelectedUniqueIds();

        // let prev = this.selections;
        if(this.getOption('singleSelect')) {
            this.selections = ids;
        }
        else {
            this.selections = array_difference(this.selections, this.pageIds);
            this.selections = array_union(this.selections, ids);
        }

        // console.debug(this.getName()+'.onCheckUncheckRows', prev, this.pageIds, ids, this.selections);

        // let rows = rowsAfter;

        // if(evt.type === 'uncheck-all') {
        //     rows = rowsBefore;
        // }

        // let ids = $.map(!$.isArray(rows) ? [rows] : rows, function(row) {
        //     return row.idUser;
        // })

        // console.debug(evt.type, rows, ids);

        // if(this.getTable().bootstrapTable('getOptions').singleSelect) {
        //     this.selections = [];
        // }

        // let fnString = ['check', 'check-all'].indexOf(evt.type) > -1 ? 'array_union' : 'array_difference'
        // let fn = window[fnString];
        // this.selections = fn(this.selections, ids);
        // this.selections = ids;
    }

    /**
     * Ajax's response handler: restores selected records
     *
     * @param res
     * @returns {*}
     */
    responseHandler = (res) => {
        let uniqueId = this.getUniqueId();
        if(!uniqueId) {
            return res;
        }

        // console.debug(this.getName() + '.responseHandler', res);

        let rows = (res.rows) ? res.rows : res;

        this.pageIds = rows.map(function(row) {
            return row[uniqueId];
        });

        rows.forEach(row => {
            row.state = this.selections.indexOf(row[uniqueId]) !== -1
        });
        return res;
    }

    /**
     * Destroy the Bootstrap Table.
     */
    destroy()
    {
        this.pageIds = [];
        this.selections = [];
        this.rendered = false;
        this.getTable().bootstrapTable('destroy');
    }

    sortDateTime(a, b)
    {
        if(new Date(a) > new Date(b)) return 1;
        if(new Date(a) < new Date(b)) return -1;
        return 0;
    }

    money_format(value, row, index, field, format)
    {
        // todo
    }

    number_format(value, row, index, field, format, useFormatted)
    {
        return number_format(value, format['decimals'], format['decimal_separator'], format['thousands_separator'])
    }

    sprintf(value, row, index, field, format)
    {
        if(format) {
            return sprintf(format, value);
        }
        return value;
    }

    strftime(value, row, index, field, format, useFormatted)
    {
        // 09.12.21, AM, fallback: handle empty english database format (should be handled server-side!!)
        if(value == '0000-00-00 00:00:00' || value == '0000-00-00') {
            value = '';
        }

        if(format && value) {
            // console.debug(row);
            // 26.01.22, AM, save data in new invisible columns
            let col_pool_formatted = field + '_pool_formatted';

            let already_formatted = col_pool_formatted in row;

            // 28.01.22, AM, was_modified and reformat added, because updateByUniqueId modifies row at runtime.
            let was_modified = already_formatted ? (useFormatted && value != row[col_pool_formatted]) : false;
            let reformat = !already_formatted || was_modified;

            if(reformat) {
                row[col_pool_formatted] = new Date(value).strftime(format)
            }
            if(useFormatted && reformat) {
                // AM, hint: _pool_use_formatted used in fillControls!!
                row[field + '_pool_raw'] = value;
                row[field + '_pool_use_formatted'] = true;
                // row[field] = row[col_pool_formatted];
            }
            // console.debug('complete row', row);

            return row[col_pool_formatted];
        }

        return value;
    }
}

/*
$.extend($.fn.bootstrapTable.defaults.icons, {
    clearSearch: 'fa-undo'
});
*/

console.debug('GUI_Table.js loaded');