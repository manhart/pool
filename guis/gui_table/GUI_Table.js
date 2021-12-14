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
class GUI_Table extends GUI_Module {
    /* > ES7
    static const STYLE_DEFAULT = 'toast';
    static const STYLE_ERROR = 'error';
    static const STYLE_INFO = 'info';
    static const STYLE_SUCCESS = 'success';
    static const STYLE_WARNING = 'warning';
    */
    url = '';

    table = null;

    // name = 'GUI_Table';

    formats = [];

    options = {};

    columns = [];

    selections = [];

    rendered = false;
    inside_render = false;

    refreshOptions = false;

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
     *
     * @param settings
     * @returns {GUI_Table}
     */
    setConfiguration(settings)
    {
        if(('moduleName' in settings)) {
            delete settings['moduleName'];
        }


        // automation
        // if('poolColumnOptions' in settings) {
        //     this.poolColumnOptions = settings['poolColumnOptions'];
        //     delete settings['poolColumnOptions'];
        // }

        this.formats['time'] = settings['time.strftime'];
        this.formats['date'] = settings['date.strftime'];
        this.formats['date.time'] = settings['date.time.strftime'];
        this.formats['number'] = settings['number'];
        return this;
    }

    setOptions(options = {})
    {
        this.options = options;
        return this;
    }

    setColumns(columns = [])
    {
        columns.forEach((column, z) => {
            let field = ('field' in column) ? column['field'] : z;
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
        this.refreshOptions = true;

        let result = false;
        if(isEmpty(options)) return result;

        // console.debug('setColumnOptions', field, options);
        for(let c = 0; c<this.columns.length; c++) {
            // console.debug(c);
            if (this.columns[c].field == field) {
                // console.debug('treffer');
                this.columns[c] = Object.assign({}, this.columns[c], options);
                result = true;
                break;
            }
        }
        console.debug('Result of setColumnOptions', this.columns);
        return result;
    }

    getTable()
    {
        if(!this.table) {
            // warning if the developer uses a wrong order
            if(!this.inside_render && !this.rendered) {
                console.warn(this.getName()+'.getTable() is called before '+this.getName()+'.render()! Not all table options ' +
                    'were passed. Please check the order of the method calls.');
            }
            this.table = $('#' + this.getName()).on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', this.onCheckUncheckRows);
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

        console.debug(this.getName()+'.render', this.options, options, window['mod_ManageUser'] ? mod_ManageUser : '');

        if(!this.rendered) {
            this.getTable().bootstrapTable(
                this.options
            );
        }
        else {
            console.info(this.getName()+'.render has already been called once.')
            this.refresh(options);
        }
        this.inside_render = false;
        this.rendered = true;
        return this;
    }

    refresh(options = {})
    {
        if(!isEmpty(options) || this.refreshOptions) {
            console.debug(this.getName()+'.refreshOptions', options);
            this.options = Object.assign({}, this.options, options);
            this.getTable().bootstrapTable('refreshOptions', this.options);
        }
        else {
            console.debug(this.getName()+'.refresh', options);
            this.getTable().bootstrapTable('refresh');
        }
        return this;
    }

    onCheckUncheckRows = (evt, rowsAfter, rowsBefore) =>
    {
        let rows = rowsAfter;

        if(evt.type === 'uncheck-all') {
            rows = rowsBefore;
        }

        let ids = $.map(!$.isArray(rows) ? [rows] : rows, function(row) {
            return row.idUser;
        })

        console.debug(evt.type, rows, ids);

        if(this.getTable().bootstrapTable('getOptions').singleSelect) {
            this.selections = [];
        }

        let fnString = ['check', 'check-all'].indexOf(evt.type) > -1 ? 'array_union' : 'array_difference'
        let fn = window[fnString];
        this.selections = fn(this.selections, ids);
        // this.selections = ids;
    }

    responseHandler = (res) =>
    {
        console.debug(this.getName()+'.responseHandler', res);

        res.forEach(row => {
            row.state = this.selections.indexOf(row.idUser) !== -1
        })
        return res;
    }

    sortDateTime(a, b)
    {
        if (new Date(a) > new Date(b)) return 1;
        if (new Date(a) < new Date(b)) return -1;
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


/**
 * Example e.g. for testing:
 *
 * */
// ready(function () {
// });

console.debug('table.js loaded');