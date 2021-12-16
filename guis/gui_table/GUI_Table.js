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
        console.debug(this.getName() + '.setConfiguration', options['poolOptions']);
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
        if('date.time' in poolOptions) {
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

        // check supported events
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
        if(!this.options.poolOnUnCheck) {
            if(this._getTable().dataset.poolOnUnCheck) {
                options.poolOnUnCheck = this._getTable().dataset.poolOnUnCheck;
            }
        }

        if(!this.options.poolFillControls) {
            if(this._getTable().dataset.poolFillControls) {
                options.poolFillControls = this._getTable().dataset.poolFillControls;
            }
        }
        if(!this.options.poolFillControlsContainer) {
            if(this._getTable().dataset.poolFillControlsContainer) {
                options.poolFillControlsContainer = this._getTable().dataset.poolFillControlsContainer;
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
        console.debug(this.getName() + '.setOptions', options);

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
            // console.debug(field, poolType, format);

            // if('formatter' in column) {
            //     column['formatter'] =
            // }
            switch(poolType) {
                case 'date.time':
                case 'date':
                case 'time':

                    if(!('formatter' in column)) {
                        column['formatter'] = (value, row, index, field) => this.strftime(value, row, index, field, format);
                    }
                    break;

                case 'number':

                    if(!('formatter' in column)) {
                        column['formatter'] = (value, row, index, field) => this.number_format(value, row, index, field, format);
                    }
                    break;
            }
        });
        this.columns = columns;
        return this;
    }

    getColumns()
    {
        return this.columns;
    }

    setColumnOptions(field, options = [])
    {
        let result = false;
        if(isEmpty(options)) return result;

        // console.debug('setColumnOptions', field, options);
        if(field in this.columnNames) {
            this.columns[this.columnNames[field]] = Object.assign({}, this.columns[this.columnNames[field]], options);
            this.forceRefreshOptions = true;
            result = true;
        }
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
        return result;
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
            .on('refresh-options.bs.table', this.onRefreshOptions)
            .on('click-row.bs.table', this.onClickRow)
            .on('check.bs.table', this.onCheck)
            .on('uncheck.bs.table', this.onUnCheck)
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

        console.debug(this.getName() + '.render', this.options, options, window['mod_ManageUser'] ? mod_ManageUser : '');

        if(!this.rendered) {
            this.getTable().bootstrapTable(
                this.options
            );
        }
        else {
            console.info(this.getName() + '.render has already been called once.')
            this.refresh(options);
        }
        this.inside_render = false;
        this.rendered = true;
        console.debug('getUniqueId', this.getUniqueId());
        return this;
    }

    refresh(options = {})
    {
        // todo stelle Seite wieder her
        if(!isEmpty(options) || this.forceRefreshOptions) {
            console.debug(this.getName() + '.refreshOptions', options);
            this.options = Object.assign({}, this.options, options);
            this.getTable().bootstrapTable('refreshOptions', this.options);
        }
        else {
            console.debug(this.getName() + '.refresh', options);
            this.getTable().bootstrapTable('refresh');
        }
        return this;
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

        if(this.options.poolOnClickRow) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.options.poolOnClickRow, [evt, row, $element, field], null)
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
    onCheck = (evt, row, $element) => {
        if(this.getOption('poolFillControls') && this.getOption('poolFillControlsContainer')) {
            fillControls(this.getOption('poolFillControlsContainer'), row);
        }

        if(this.getOption('poolOnCheck')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnCheck'), [evt, row, $element], null)
        }
    }

    /**
     * onUncheck event
     *
     * @param evt
     * @param row
     * @param $element
     */
    onUnCheck = (evt, row, $element) => {
        if(this.getOption('poolClearControls') && this.getOption('poolClearControlsContainer')) {
            clearControls(this.getOption('poolClearControlsContainer'));
        }

        if(this.getOption('poolOnUnCheck')) {
            jQuery().bootstrapTable.utils.calculateObjectValue(this.getTable(), this.getOption('poolOnUnCheck'), [evt, row, $element], null)
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
     * save selections
     */
    onCheckUncheckRows = () => {
        let uniqueId = this.getUniqueId()
        if(!uniqueId) {
            return;
        }

        let ids = this.getSelections().map(function(row) {
            return row[uniqueId];
        });

        this.selections = array_difference(this.selections, this.pageIds);
        this.selections = array_union(this.selections, ids);

        // console.debug(this.getName()+'.onCheckUncheckRows', ids, this.pageIds);

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

    number_format(value, row, index, field, format)
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

    strftime(value, row, index, field, format)
    {
        // 09.12.21, AM, fallback: handle empty english database format (should be handled server-side!!)
        if(value == '0000-00-00 00:00:00') {
            value = '';
        }

        if(format && value) {
            return new Date(value).strftime(format);
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