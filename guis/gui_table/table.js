/*
 * POOL
 *
 * table.js created at 08.04.21, 13:17
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

'use strict';

// $.BootstrapTable = class extends $.BootstrapTable {}
class GUI_Table {
    /* > ES7
    static const STYLE_DEFAULT = 'toast';
    static const STYLE_ERROR = 'error';
    static const STYLE_INFO = 'info';
    static const STYLE_SUCCESS = 'success';
    static const STYLE_WARNING = 'warning';
    */
    url = '';

    table = null;

    moduleName = 'GUI_Table';

    formats = [];

    options = {};
    columns = [];

    poolColumnOptions = {}; // poolOptions

    /**
     * Defaults
     *
     * @constructor
     */
    constructor(settings = {})
    {
        if(!('moduleName' in settings)) {
            console.error('Missing moduleName in settings of GUI_Table');
        }
        else {
            this.moduleName = settings['moduleName'];
            delete settings['moduleName'];
        }

        if('poolColumnOptions' in settings) {
            this.poolColumnOptions = settings['poolColumnOptions'];
            delete settings['poolColumnOptions'];
        }

        this.formats['time'] = settings['time.strftime'];
        this.formats['date'] = settings['date.strftime'];
        this.formats['datetime'] = settings['datetime.strftime'];

        this.table = $('#'+this.moduleName)

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
            if(!(field in this.poolColumnOptions)) {
                return;
            }

            // automation for special dataType's
            let dataType = '';
            if('dataType' in this.poolColumnOptions[field]) {
                dataType = this.poolColumnOptions[field]['dataType'];
            }
            let dataFormat = '';
            if('dataFormat' in this.poolColumnOptions[field]) {
                dataFormat = this.poolColumnOptions[field]['dataFormat']
            }

            switch(dataType) {
                case 'datetime':
                case 'date':
                case 'time':
                    let format = dataFormat ? dataFormat : this.formats[dataType];
                    if(!('formatter' in column)) {
                        column['formatter'] = (value, row, index, field) => this.strftime(value, row, index, field, format);
                    }
                    break;
            }
        })
        this.columns = columns;
        return this;
    }

    render()
    {
        this.options['columns'] = this.columns;
        this.table.bootstrapTable(
            this.options
        );
        return this;
    }

    sortDateTime(a, b)
    {
        if (new Date(a) > new Date(b)) return 1;
        if (new Date(a) < new Date(b)) return -1;
        return 0;
    }

    strftime(value, row, index, field, format)
    {
        if(format) {
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