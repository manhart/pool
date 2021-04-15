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

    dateTimeFormat = '%Y-%m-%d %H:%M';

    options = [];

    /**
     * Defaults
     *
     * @constructor
     */
    constructor(options = {})
    {
        this.moduleName = options['moduleName'];
        delete options['moduleName'];
        this.options = options;
        // this.columns = options['columns'];

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

    setColumns(columns = [])
    {
        this.columns = columns;
        this.options['columns'] = this.columns;
        return this;
    }

    render()
    {
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

    formatDateTime(value, row, index, field)
    {
        let format = '%Y-%m-%d %H:%M';
        // console.debug('format: '+format);
        // console.debug(this);
        // console.debug('dateTimeFOrmat:',this.dateTimeFormat);
        return new Date(value).strftime(format);
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