/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

class GUI_Module
{
    /** @var {string} name contains the unique name of the module */
    name = '';
    /** @var {string} moduleSelector contains the ID selector for the HTML Element of the module */
    moduleSelector = '';
    className = this.constructor.name;
    /** @var {string} fullyQualifiedClassName of the php module - is required by Ajax Calls */
    fullyQualifiedClassName = this.constructor.name;
    #parent = null;
    /** property contains the html element of the module */
    #rootElement = null;

    /**
     * @param {string} name unique name of the module
     */
    constructor(name)
    {
        this.name = name;
        this.moduleSelector = `#${name}`;
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
     * Destroys the current module by unregistering it from the Weblication instance.
     *
     * @return {void}
     */
    destroy()
    {
        Weblication.getInstance().unregisterModule(this);
    }

    /**
     * Marks the root element as a pool module
     */
    markAsPoolModule()
    {
        if(!this.getRootElement()) {
            return;
        }
        this.#rootElement.poolModule = this;
        this.#rootElement.classList.add('pool-module');
        this.#rootElement.setAttribute('data-pool-class-name', this.constructor.name);
    }

    /**
     * Initializes the module
     *
     * @param {object} options - options passed to the module
     */
    init(options = {})
    {
    }

    /**
     * Returns the name of the module
     *
     * @returns {string}
     */
    getName()
    {
        return this.name;
    }

    /**
     * Returns the className of the module
     *
     * @returns {string}
     */
    getClassName()
    {
        return this.className;
    }

    /**
     * Sets the fully qualified className of the php module
     *
     * @param {string} fullyQualifiedClassName
     */
    setFullyQualifiedClassName(fullyQualifiedClassName)
    {
        this.fullyQualifiedClassName = fullyQualifiedClassName;
        return this;
    }

    /**
     * Returns the fully qualified className of the php module
     * @see https://www.php.net/manual/en/language.namespaces.rules.php
     * @returns {string}
     */
    getFullyQualifiedClassName()
    {
        return this.fullyQualifiedClassName;
    }

    /**
     * parses the response as JSON
     * @param {Response} response
     * @returns {Promise<*>}
     */
    async parseJSON(response)
    {
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
     * @param {object|FormData} data Object containing parameters passed to server method
     * @param {object} options Request options e.g. {method:'POST'}
     * @returns {Promise<*>} Resolves to the value returned by the method or rejects with an error thrown by the method
     */
    request(ajaxMethod, data = {}, options = {})
    {
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
            module = this.getFullyQualifiedClassName(),
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
                else if(value === null) {
                    QueryURL.append(key, '');
                }
                //doesn't work with empty Objects
                else if (typeof value === 'object') {
                    for (const [innerKey, innerValue] of Object.entries(value)) {
                        QueryURL.append(key + '[' + innerKey + ']', String(innerValue));
                    }
                }
                else {
                    QueryURL.append(key, value.toString());
                }
            }

            // Convert to encoded string and prepend with ?
            queryString = QueryURL.toString();
            queryString = queryString && `?${queryString}`;
        }

        const {
            origin, pathname, search
        } = window.location;

        let Endpoint = new URL(pathname + queryString, origin);
        Endpoint.searchParams.get('schema') || Endpoint.searchParams.set('schema', (new URLSearchParams(search).get('schema') || ''));
        Endpoint.searchParams.set('module', module);
        Endpoint.searchParams.set('method', ajaxMethod);

        // console.debug('fetch', Endpoint.toString(), reqOptions);
        let promise = fetch(Endpoint, reqOptions).then(this.parseAjaxResponse.bind(this), this.onFetchNetworkError);
        //add a default handler
        promise.then = this.getThenMod(this.onAjax_UnhandledException).bind(promise);
        return promise;
    }

    onFetchNetworkError = () => alert('Netzwerkfehler');
    onAjax_UnhandledException = e =>
    {
        if (e instanceof PoolAjaxResponseError) {
            console.warn('Caught unhandled Ajax Error of Server-type: ' + e.serverSideType);
            if (e.cause) console.warn(e.cause)
        } else
            console.warn('Caught Unhandled Error during handling an ajax-request')
        console.warn(e);
    }

    getThenMod = (handler) =>
    {
        const thenMod = function (onFulfilled, onRejected) {
            console.debug('modded')
            onRejected ??= e => {//create delegating handler
                //handle rejection
                if (newPromise.hasNext)//closure magic!
                    throw e//pass on to the next promise in the chain
                else//end of chain -> default Handler
                    handler(e);
            }
            //Execute default and inject delegating handler
            const newPromise = Object.getPrototypeOf(this).then.apply(this, [onFulfilled, onRejected]);
            this.hasNext = true;
            //Pass the modification on to the next Promise in the chain
            newPromise.then = thenMod.bind(newPromise);//more closure magic
            console.debug('mod complete')
            return newPromise;
        };
        return thenMod;
    }

    /**
     * Parses the response and analyzes the status code.
     *
     * @param {Response} response
     * @returns {Promise<*>}
     */
    async parseAjaxResponse(response)
    {
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
            default:
                let data, error;
                try {
                    ({error, data} = await this.parseJSON(response));
                } catch (e){}
                throw new PoolAjaxResponseError(error?.message ?? `Status ${status}`, data, error?.type ?? "unknown");
        }
    }

    onAjax_ReAuthorizationRequired = async response => alert('Session expired');
    onAjax_404 = async response => console.error(`Ajax-Method at ${response.url} not found`);
    /**should be rare as this module was fetched beforehand to be able to make this call*/
    onAjax_ModuleAccessDenied = async response => alert('Access to Module denied');
    onAjax_MethodAccessDenied = async response => alert('Access to Method denied');

    /**
     * should be used (overwritten) to redraw the corresponding html element (necessary for module configurator)
     * @param options
     */
    redraw(options = {})
    {
    }

    /**
     * Returns predefined moduleSelector
     *
     * @returns {string}
     */
    getModuleSelector()
    {
        return this.moduleSelector;
    }

    /**
     * Returns the element property
     * @return {HTMLElement|null}
     */
    getRootElement()
    {
        this.#rootElement ??= this.element();
        return this.#rootElement;
    }

    /**
     * Returns selected element within the root / module element. if no selector is given, it should return self (=the top root / module element)
     * @see document.querySelector
     * @param {string} selector
     * @returns {HTMLElement|null}
     */
    element(selector = '')
    {
        return document.querySelector(`${this.moduleSelector}${(selector) ? ' ' + selector : ''}`);
    }

    /**
     * Search html element by id within the root / module element
     *
     * @param {string} id
     * @returns {HTMLElement}
     * @see moduleSelector
     */
    elementById(id = '')
    {
        return document.getElementById(this.name + id);
    }

    /**
     * Search a html element by name within the root / module element
     *
     * @param {string} name
     * @returns {HTMLElement}
     * @see moduleSelector
     */
    elementByName(name)
    {
        return this.element(`[name='${name}']`);
    }

    /**
     * Search html elements by name within the root / module element
     *
     * @param {string} name
     * @returns {NodeListOf<Element>}
     * @see moduleSelector
     */
    elementsByName(name)
    {
        return this.elements(`[name='${name}']`);
    }

    /**
     * Search html elements by selector within the root / module element
     *
     * @param selectors
     * @returns {NodeListOf<Element>}
     * @see moduleSelector
     */
    elements(...selectors)
    {
        const selector = selectors.map(s => this.moduleSelector + ' ' + s).join(', ');
        return document.querySelectorAll(selector);
    }

    /**
     * Sets the parent module
     * @param {GUI_Module|null} parent
     * @return {GUI_Module}
     */
    setParent(parent)
    {
        if(parent instanceof GUI_Module) {
            this.#parent = parent;
        }
        return this;
    }

    /**
     * Returns the parent module
     * @return {null|GUI_Module}
     */
    getParent()
    {
        return this.#parent;
    }


    /**
     * creates a new unique GUI_Module. Makes the module globally known with $ in front of the name
     *
     * @param {string} GUIClassName
     * @param {string} name
     * @param {string} fullyQualifiedClassName - currently only used for Ajax calls
     * @param {string|null} parentModuleName
     * @param {object} initOptions
     * @returns {GUI_Module}
     */
    static createGUIModule(GUIClassName, name, fullyQualifiedClassName = '', parentModuleName = null, initOptions = {}) {
        const app = Weblication.getInstance();
        if (app.module_exists(name)) {
            return app.getModule(name);
        }

        let myClass;
        if (typeof GUIClassName == 'function') {
            myClass = GUIClassName;
        } else {
            if (!Weblication.classMapping[GUIClassName]) {
                throw new Error(`Class ${GUIClassName} is not registered. Please make sure to register your Module.`);
            }
            myClass = Weblication.classMapping[GUIClassName];
        }

        /** @type {GUI_Module} module */
        const module = new myClass(name);
        module.setFullyQualifiedClassName(fullyQualifiedClassName);
        ready(() => {
            module.markAsPoolModule();
            if(app.module_exists(parentModuleName)) {
                module.setParent(app.getModule(parentModuleName));
            }
            module.init(initOptions);
        });
        return module;
    }
}
console.debug('GUI_Module.js loaded');