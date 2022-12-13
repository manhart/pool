/*
 * POOL
 *
 * Module.class.js created at 10.12.21, 12:29
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

'use strict';

class GUI_Module
{
    name = '';
    className = what(this);

    /**
     * @param {string} name of module
     */
    constructor(name)
    {
        this.name = name;
        this.className = what(this);

        // 10.02.2022, AM, sometimes the edge has an undefined className (especially when we put new versions live)
        if(typeof this.className == 'undefined') {
            if(!window['pool_GUI_Module_unknown_className']) {
                alert('An unknown error has occurred in your browser. Please try to clear the browser cache. Key combination is: '+
                    'Ctrl+Shift+Del. ' + String.fromCharCode(10) + 'If this does not help, contact our IT (software developers).');
            }
            window['pool_GUI_Module_unknown_className'] = 1;
        }

        Weblication.getInstance().registerModule(this);
    }

    /**
     * returns the name of the module
     *
     * @returns {string}
     */
    getName()
    {
        return this.name;
    }

    /**
     * returns the className of the module
     *
     * @returns {string}
     */
    getClassName()
    {
        return this.className;
    }

    /**
     * @param {Response} response
     * @return {Promise<*>}
     */
    async parseAjaxResponse(response)
    {
        // if a body response exists, parse anx extract the possible properties
        const { data, error, success } = response.status !== 204 ? await response.json() : { success: true };

        // trigger a new exception to capture later on request call site
        if (!success) throw new Error(error.message);
        // Otherwise, simply resolve the received data
        return data;
    }

    /**
     * promise-based ajax request
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/fetch
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
     * @see https://blog.openreplay.com/ajax-battle-xmlhttprequest-vs-the-fetch-api
     * @param {string} ajaxMethod
     * @param {object} options
     * @return {Promise<*>}
     */
    request(ajaxMethod, options = {})
    {
        // the data attribute is a simplification for parameter passing. POST => body = data. GET => query = data.
        if(options.data) {
            if(options.method && options.method == 'POST')
                options.body = data;
            else
                options.query = data;

            delete options.data;
        }

        const {
            headers,
            query = null,
            method = 'GET',
            body,
            ...extraOpts
        } = options;


        const reqOptions = {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                ...headers
            },
            ...extraOpts
        };

        let defaultContentType = 'application/json';

        // if a body object is passed, automatically stringify it.
        if(body) {
            if(isStringJSON(body)) {
                reqOptions.body = JSON.stringify(body);
            }
            else {
                // @see https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
                // FormData, Blob, ArrayBuffer, TypedArray, URLSearchParams, DataView
                reqOptions.body = body;
                defaultContentType = '';
            }
        }

        if(defaultContentType && !reqOptions.headers['Content-Type']) {
            reqOptions.headers['Content-Type'] = defaultContentType;
        }

        let queryString = '';
        if (query) {
            // Convert to encoded string and prepend with ?
            queryString = new URLSearchParams(query).toString();
            queryString = queryString && `?${queryString}`;
        }

        const {
            origin, pathname
        } = window.location;

        let Endpoint = new URL(pathname + queryString, origin);
        Endpoint.searchParams.set('module', this.getClassName());
        Endpoint.searchParams.set('method', ajaxMethod);

        // console.debug(Endpoint.toString(), reqOptions);
        return fetch(Endpoint, reqOptions).then(this.parseAjaxResponse);
    }

    /**
     * creates a new unique GUI_Module. Makes the module globally known with $ in front of the name
     *
     * @param {string} GUIClassName
     * @param {string} name
     * @returns {GUI_Module}
     */
    static createGUIModule(GUIClassName, name)
    {
        if(Weblication.getInstance().module_exists(name)) {
            return Weblication.getInstance().getModule(name);
        }

        let myClass;
        if(typeof GUIClassName == 'function') {
            myClass = GUIClassName;
        }
        else {
            myClass = Weblication.classesMapping[GUIClassName];
        }

        return new myClass(name);
    }
}

console.debug('GUI_Module.class.js loaded');