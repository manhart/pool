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

class Url
{
    /**
     * Creates a new Url instance and parses path, query parameters, and fragment.
     *
     * @class
     * @param {string|null} [url=''] The URL to parse. If `null` is passed, the current browser location (`window.location.href`) will be used.
     *
     * @example
     * const url = new Url('?page=1&sort=asc#top');
     * const current = new Url(null); // uses current window location
     */
    constructor(url = '')
    {
        this.params = {};
        this.fragment = '';
        this.path = '';

        if(url === null) {
            url = window.location.href;
        }

        if(url) {
            this.init(url);
        }
    }

    setScript(script)
    {
        return this.init(script);
    }

    /**
     * Initializes the Url instance from a given URL string.
     *
     * @param {string} url A URL string to parse.
     * @returns {this}
     */
    init(url)
    {
        if(typeof url === 'object') url = url.toString();

        let basePath = url;
        let queryString = '';
        let fragment = '';

        const hashSplit = url.split('#');
        if(hashSplit.length > 1) {
            fragment = hashSplit[1];
            url = hashSplit[0];
        }

        const querySplit = url.split('?');
        if(querySplit.length > 1) {
            basePath = querySplit[0];
            queryString = querySplit[1];
        }
        else {
            basePath = url;
        }

        this.path = basePath;
        this.fragment = fragment;

        if(queryString) {
            // Try native URLSearchParams if available
            const parser = typeof URLSearchParams !== 'undefined'
                ? new URLSearchParams(queryString)
                : null;

            if(parser) {
                for(const [key, val] of parser.entries()) {
                    this._addRawParam(key, val);
                }
            }
            else {
                const pairs = queryString.split('&');
                for(const pair of pairs) {
                    const [rawKey, rawVal = ''] = pair.split('=');
                    const key = decodeURIComponent(rawKey);
                    const val = decodeURIComponent(rawVal);
                    this._addRawParam(key, val);
                }
            }
        }

        return this;
    }

    _addRawParam(key, val)
    {
        if(!this.params[key]) {
            this.params[key] = [];
        }
        this.params[key].push(val);
    }

    /**
     * Gets the value of a parameter.
     * If the parameter has multiple values, an array is returned.
     *
     * @param {string} key The parameter name.
     * @returns {string|string[]|undefined} The parameter value, array of values, or undefined if not set.
     */
    getParam(key)
    {
        const val = this.params[key];
        if(!val) return undefined;
        return val.length === 1 ? val[0] : val;
    }

    /**
     * Checks if a parameter with the given key exists.
     *
     * @param {string} key The parameter name.
     * @returns {boolean} True if the parameter exists, false otherwise.
     */
    hasParam(key)
    {
        return Array.isArray(this.params[key]) && this.params[key].length > 0;
    }

    /**
     * Sets a parameter value. Overwrites any existing values.
     *
     * @param {string|Object} key The parameter name or an object of key-value pairs.
     * @param {string|number|null} [val] The parameter value or array of values (if key is string).
     * @returns {this}
     *
     * @example
     * url.setParam('page', '2');
     * url.setParam({ sort: 'asc', filter: 'active' });
     */
    setParam(key, val)
    {
        if(typeof key === 'object') {
            for(const [k, v] of Object.entries(key)) {
                this.setParam(k, v);
            }
        }
        else {
            this.params[key] = Array.isArray(val) ? val : [val];
        }
        return this;
    }

    /**
     * Adds a parameter value. Does not remove existing values.
     *
     * @param {string} key The parameter name.
     * @param {string} val The parameter value to add.
     * @returns {this}
     *
     * @example
     * url.addParam('tag', 'php').addParam('tag', 'js');
     */
    addParam(key, val)
    {
        if(!this.params[key]) {
            this.params[key] = [];
        }
        this.params[key].push(val);
        return this;
    }

    /**
     * Deletes a parameter and all its values.
     *
     * @param {string} key The parameter name to remove.
     * @returns {this}
     */
    delParam(key)
    {
        delete this.params[key];
        return this;
    }

    removeParam(key)
    {
        return this.delParam(key);
    }

    /**
     * Sets the fragment (hash) part of the URL.
     *
     * @param {string} fragment The fragment (without the #).
     * @returns {this}
     */
    setFragment(fragment)
    {
        this.fragment = fragment;
        return this;
    }

    /**
     * Builds the full URL string from path, query parameters, and fragment.
     *
     * @param {Object} [options] Optional configuration
     * @param {'csv'|'brackets'} [options.arrayFormat='csv']
     *        Defines how array values are serialized:
     *        - 'csv' → key=1,2,3
     *        - 'brackets' → key[]=1&key[]=2&key[]=3
     * @returns {string}
     */
    getUrl(options = {})
    {
        const arrayFormat = options.arrayFormat || 'csv';

        const query = Object.entries(this.params).flatMap(([key, values]) => {
            const encodedKey = encodeURIComponent(key);
            if(values.length <= 1) {
                return [`${encodedKey}=${encodeURIComponent(values[0]??'')}`];
            }
            switch(arrayFormat) {
                case 'brackets':
                    return values.map(val => `${encodedKey}[]=${encodeURIComponent(val)}`);
                case 'csv':
                default:
                    return [`${encodedKey}=${encodeURIComponent(values.join(','))}`];
            }
        }).join('&');

        let url = this.path;
        if(query) url += '?' + query;
        if(this.fragment) url += '#' + encodeURIComponent(this.fragment);
        return url;
    }

    /**
     * Sanitizes the URL parameters by applying filters such as whitelisting,
     * maximum length checks, and pattern detection to remove suspicious values.
     *
     * @param {Object} [options] Optional filtering rules.
     * @param {string[]} [options.whitelist] Array of allowed parameter names. All others will be removed.
     * @param {number} [options.maxValueLength=512] Maximum allowed length for each parameter value.
     * @param {RegExp} [options.pattern] Regex to detect suspicious content (e.g., XSS or SQL injection payloads).
     * @returns {this}
     *
     * @example
     * url.sanitize({
     *   whitelist: ['page', 'lang'],
     *   maxValueLength: 100
     * });
     */
    sanitize(options = {})
    {
        const whitelist = options.whitelist || null; // Array of allowed keys
        const maxValueLength = options.maxValueLength || 512;
        const suspiciousPattern = /<script|javascript:|on\w+=|union\s+select|--/i;

        for(const key in this.params) {
            if(whitelist && !whitelist.includes(key)) {
                delete this.params[key];
                continue;
            }

            let values = this.params[key];
            if(!Array.isArray(values)) values = [values];

            values = values.filter(v => {
                if(typeof v !== 'string') return false;
                if(v.length > maxValueLength) return false;
                return !suspiciousPattern.test(v);
            });

            if(values.length === 0) {
                delete this.params[key];
            }
            else {
                this.params[key] = values;
            }
        }

        return this;
    }

    /**
     * Reloads the page using the constructed URL.
     *
     * @returns {void}
     */
    restartUrl()
    {
        location.href = this.getUrl();
    }

    toString()
    {
        return this.getUrl();
    }

    toJSON()
    {
        return {
            path: this.path,
            params: this.params,
            fragment: this.fragment
        };
    }
}

/**
 * SCRIPT_SCHEMA contains the current schema. If the schema parameter is not set, we use no schema.
 * @type {string|string}
 */
const SCRIPT_SCHEMA = new URLSearchParams(location.search).get('schema') || '';
/**
 * SCRIPT_NAME contains the current script name with protocol, host and path. It is used to redirect to the start page. The query parameters are not included, except the schema
 * parameter.
 */
const SCRIPT_NAME = location.protocol + '//' + location.host + location.pathname + (SCRIPT_SCHEMA ? '?schema=' + SCRIPT_SCHEMA : '');
