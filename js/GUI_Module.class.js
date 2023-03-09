/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class GUI_Module
{
    name = '';
    className = this.constructor.name;

    /**
     * @param {string} name of module
     */
    constructor(name) {
        this.name = name;
        // 10.02.2022, AM, sometimes the edge has an undefined className (especially when we put new versions live)
        if (typeof this.className == 'undefined') {
            if (!window['pool_GUI_Module_unknown_className']) {
                alert('An unknown error has occurred in your browser. Please try to clear the browser cache. Key combination is: ' +
                    'Ctrl+Shift+Del. ' + String.fromCharCode(10) + 'If this does not help, contact our IT (software developers).');
            }
            window['pool_GUI_Module_unknown_className'] = 1;
        }

        Weblication.getInstance().registerModule(this);
    }

    /**
     * @abstract
     */
    init(options = {}) {
        // console.debug(this.getName()+'.init called');
    }

    /**
     * returns the name of the module
     *
     * @returns {string}
     */
    getName() {
        return this.name;
    }

    /**
     * returns the className of the module
     *
     * @returns {string}
     */
    getClassName() {
        return this.className;
    }

    async parseJSON(response) {
        let json;
        let text = await response.text();
        try {
            json = JSON.parse(text);
        } catch (e) {
            throw new PoolAjaxResponseError('Invalid server response: \n' + text, e);
        }
        return json;
    }

    /**
     * promise-based ajax request to server-side GUI-Modul<br>
     * Formats the Parameters and calls the fetch-API
     * @see https://developer.mozilla.org/en-US/docs/Web/API/fetch
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch
     * @see https://blog.openreplay.com/ajax-battle-xmlhttprequest-vs-the-fetch-api
     * @param {string} ajaxMethod Alias name of the method to call
     * @param data Object containing parameters passed to server method
     * @param {object} options Request options e.g. {method:'POST'}
     * @return {Promise<*>} Resolves to the value returned by the method or rejects with an error thrown by the method
     */
    request(ajaxMethod, data, options = {}) {
        // the data attribute is a simplification for parameter passing. POST => body = data. GET => query = data.
        let key = 'query';
        if (options.method) {
            switch (options.method) {
                case 'GET':
                    break;
                case 'POST':
                    key = 'body';
                    break;
                default:
                    throw new Error('Unrecognized request Method ' + options.method);
            }
        }
        options[key] = data;

        const {
            headers,
            query = null,
            method = 'GET',
            module = this.getClassName(),
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
        if (body) {
            const types = [FormData, Blob, ArrayBuffer, URLSearchParams, DataView];
            if (typeof body == 'object' && !(types.includes(body.constructor))) {
                reqOptions.body = JSON.stringify(body);
            } else {
                // @see https://muffinman.io/uploading-files-using-fetch-multipart-form-data/
                // FormData, Blob, ArrayBuffer, TypedArray, URLSearchParams, DataView
                reqOptions.body = body;
                defaultContentType = '';
            }
        }

        if (defaultContentType && !reqOptions.headers['Content-Type']) {
            reqOptions.headers['Content-Type'] = defaultContentType;
        }

        let queryString = '';
        if (query) {
            let QueryURL = new URLSearchParams();

            for (const [key, value] of Object.entries(query)) {
                if (Array.isArray(value)) {
                    value.forEach(innerValue => QueryURL.append(key, innerValue));
                }
                //doesn't work with empty Objects
                else if (typeof value === 'object') {
                    for (const [innerKey, innerValue] of Object.entries(value)) {
                        QueryURL.append(key + '[' + innerKey + ']', String(innerValue));
                    }
                } else {
                    QueryURL.append(key, value.toString());
                }
            }

            // Convert to encoded string and prepend with ?
            queryString = QueryURL.toString();
            queryString = queryString && `?${queryString}`;
        }

        const {
            origin, pathname
        } = window.location;

        let Endpoint = new URL(pathname + queryString, origin);
        Endpoint.searchParams.set('module', module);
        Endpoint.searchParams.set('method', ajaxMethod);

        // console.debug('fetch', Endpoint.toString(), reqOptions);
        let promise = fetch(Endpoint, reqOptions).then(this.parseAjaxResponse.bind(this), this.onFetchNetworkError);
        //add a default handler
        promise.then = this.getThenMod(this.onAjax_UnhandledException).bind(promise);
        return promise;
    }

    onFetchNetworkError = () => alert('Netzwerkfehler');
    onAjax_UnhandledException = e => {
        if (e instanceof PoolAjaxResponseError) {
            console.warn('Caught unhandled Ajax Error of Server-type: ' + e.serverSideType);
            if (e.cause) console.warn(e.cause)
        } else
            console.warn('Caught Unhandled Error during handling an ajax-request')
        console.warn(e);
    }

    getThenMod = (handler) => {
        const thenMod = function (onFullfilled, onRejected) {
            console.debug('modded')
            onRejected ??= e => {//create delegating handler
                //handle rejection
                if (newPromise.hasNext)//closure magic!
                    throw e//pass on to the next promise in the chain
                else//end of chain -> default Handler
                    handler(e);
            }
            //Execute default and inject delegating handler
            const newPromise = Object.getPrototypeOf(this).then.apply(this, [onFullfilled, onRejected]);
            this.hasNext = true;
            //Pass the modification on to the next Promise in the chain
            newPromise.then = thenMod.bind(newPromise);//more closure magic
            console.debug('mod complete')
            return newPromise;
        };
        return thenMod;
    }

    /**
     * @param {Response} response
     * @return {Promise<*>}
     */
    async parseAjaxResponse(response) {
        const status = response.status;
        if (500 <= status && status < 600)//Server error
            throw new PoolAjaxResponseError(await response.text(), null, 'internal');
        switch (status) {
            case 200: {
                // if a body response exists, parse and extract the possible properties
                const {data, error, success} = await this.parseJSON(response);
                if (!success) // trigger a new exception to capture later on request call site
                    // notice: the pool responds with an error.type and error.message
                    throw new PoolAjaxResponseError(error.message, data, error.type);
                else // Otherwise, simply resolve the received data
                    return data;
            }
            case 204://No-Content Header
                return undefined;
            case 401://(Re)authorization required
                return await this.onAjax_ReAuthorizationRequired(response);
            case 404:
                return await this.onAjax_404(response);
            case 403://Access denied (Modul)
                return await this.onAjax_ModuleAccessDenied(response);
            case 405://Access denied (Method e.g. save)
                return await this.onAjax_MethodAccessDenied(response);
        }
    };

    onAjax_ReAuthorizationRequired = async response => alert('Session expired');
    onAjax_404 = async response => console.error(`Ajax-Method at ${response.url} not found`);
    /**should be rare as this module was fetched beforehand to be able to make this call*/
    onAjax_ModuleAccessDenied = async response => alert('Access to Module denied');
    onAjax_MethodAccessDenied = async response => alert('Access to Method denied');

    /**
     * should be used (overwritten) to redraw the corresponding html element (necessary for module configurator)
     * @param options
     */
    redraw(options = {}) {
    }

    /**
     * creates a new unique GUI_Module. Makes the module globally known with $ in front of the name
     *
     * @param {string} GUIClassName
     * @param {string} name
     * @returns {GUI_Module}
     */
    static createGUIModule(GUIClassName, name) {
        if (Weblication.getInstance().module_exists(name)) {
            return Weblication.getInstance().getModule(name);
        }

        let myClass;
        if (typeof GUIClassName == 'function') {
            myClass = GUIClassName;
        } else {
            if (!Weblication.classMapping[GUIClassName]) {
                throw new Error('Class ' + GUIClassName + ' is not registered. Please make sure to register your Module.');
            }
            myClass = Weblication.classMapping[GUIClassName];
        }

        return new myClass(name);
    }
}

console.debug('GUI_Module.class.js loaded');