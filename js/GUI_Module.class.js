/*
 * POOL
 *
 * Module.class.js created at 10.12.21, 12:29
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_Module
{
    name = '';
    className = this.constructor.name;

    /**
     * @param {string} name of module
     */
    constructor(name)
    {
        this.name = name;

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
     * @abstract
     */
    init(options = {})
    {
        // console.debug(this.getName()+'.init called');
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
        // if a body response exists, parse and extract the possible properties
        let json;
        let text = await response.text();
        try {
            json = JSON.parse(text);
        }
        catch(e) {
            if(e instanceof SyntaxError) {
                // ? should the framework handle this error
            }
            throw e
        }
        const { data, error, success } = response.status !== 204 ? json : { success: true };

        // trigger a new exception to capture later on request call site
        if (!success) {
            // notice: the pool responses with an error.type and error.message
            throw new PoolAjaxResponseError(error.message, null, error.type);
        }
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
    request(ajaxMethod, data, options = {})
    {
        // the data attribute is a simplification for parameter passing. POST => body = data. GET => query = data.
        let key = 'query';
        if(options.method && options.method == 'POST')
            key = 'body';
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
        if(body) {
            const types = [FormData, Blob, ArrayBuffer, URLSearchParams, DataView];
            if(typeof body == 'object' && !(types.includes(body.constructor))) {
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
            let QueryURL = new URLSearchParams();

            for(const [key, value] of Object.entries(query)) {
                if(Array.isArray(value)) {
                    value.forEach(innerValue => QueryURL.append(key, innerValue));
                }
                else if(typeof value === 'object') {
                    for(const [innerKey, innerValue] of Object.entries(value)) {
                        QueryURL.append(key + '[' + innerKey + ']', innerValue.toString());
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
            origin, pathname
        } = window.location;

        let Endpoint = new URL(pathname + queryString, origin);
        Endpoint.searchParams.set('module', module);
        Endpoint.searchParams.set('method', ajaxMethod);

        // console.debug('fetch', Endpoint.toString(), reqOptions);
        return fetch(Endpoint, reqOptions).then(this.parseAjaxResponse);
    }

    /**
     * should be used (overwritten) to redraw the corresponding html element (necessary for module configurator)
     * @param options
     */
    redraw(options = {}) {}

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
            if(!Weblication.classesMapping[GUIClassName]) {
                throw new Error('Class ' + GUIClassName + ' is not registered.');
            }
            myClass = Weblication.classesMapping[GUIClassName];
        }

        return new myClass(name);
    }
}

console.debug('GUI_Module.class.js loaded');