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

class Weblication
{
    /**
     * All registered modules
     *
     * @type {[]}
     */
    #modules = [];

    /**
     * Class mapping
     * @type {{}}
     */
    static classMapping = {};

    /**
     * Singleton
     */
    constructor()
    {
        Weblication._instance = this;
    }

    /**
     * return instance of Weblication (Singleton)
     *
     * @returns {Weblication}
     */
    static getInstance()
    {
        if(!Weblication._instance) {
            Weblication._instance = new Weblication();
        }
        return Weblication._instance;
    }

    /**
     * Register class
     *
     * @param Class
     * @returns {Weblication}
     */
    static registerClass(Class)
    {
        Weblication.classMapping[Class.name] = Class;
        return this;
    }

    /**
     * Register module
     *
     * @param {GUI_Module} Module
     * @returns {Weblication}
     */
    registerModule(Module)
    {
        let moduleName = Module.getName();
        if((moduleName in this.#modules)) {
            throw new PoolError('Module with Name ' + moduleName + ' already exists. Registration not possible!');
        }
        this.#modules[moduleName] = Module;
        // console.debug('Weblication has Module "' + moduleName + '" registered');
        return this;
    }

    /**
     * Unregister module
     *
     * @param {GUI_Module} Module
     * @returns {Weblication}
     */
    unregisterModule(Module)
    {
        delete this.#modules[Module.getName()];
        return this;
    }

    /**
     * Returns module
     *
     * @param moduleName
     * @returns {GUI_Module}
     */
    getModule(moduleName)
    {
        if(!this.module_exists(moduleName)) {
            throw new PoolError('Module with Name ' + moduleName + ' was not found!');
        }
        return this.#modules[moduleName];
    }

    /**
     * Destroys the specified module.
     *
     * @param {string} moduleName - The name of the module to be destroyed.
     * @return {Weblication}
     */
    destroyModule(moduleName)
    {
        this.getModule(moduleName).destroy();
        window['$' + moduleName] = undefined;
        return this;
    }

    /**
     * Checks if module exists
     *
     * @param {string} moduleName
     * @return {boolean}
     */
    module_exists(moduleName)
    {
        return (moduleName in this.#modules);
    }

    /**
     * returns an array of all modules
     * @return {*[]}
     */
    getModules()
    {
        return this.#modules;
    }

    /**
     * Create all JavaScript modules with the options passed by the server
     */
    run()
    {
        const clientDataElement = document.head.querySelector('meta[name=client-data]');
        if(!clientDataElement) {
            console.debug('no client-data tag');
            return;
        }

        let clientData = b64DecodeUnicode(clientDataElement.content);
        if(!isJsonString(clientData)) {
            console.debug('client-data content is not compatible with json');
            return;
        }

        clientData = JSON.parse(clientData);
        console.debug('client-data', clientData);

        for(const moduleName in clientData) {
            const className = clientData[moduleName].className;
            /** fully qualified class name of the php module - is required by Ajax Calls */
            const fullyQualifiedClassName = clientData[moduleName].fullyQualifiedClassName;
            const parentModuleName = clientData[moduleName].parentModuleName;
            const initOptions = clientData[moduleName].initOptions ?? {};

            try {
                window['$' + moduleName] = GUI_Module.createGUIModule(className, moduleName, fullyQualifiedClassName, parentModuleName, initOptions);
            }
            catch(e) {
                console.error(e.toString());
            }
            // console.debug('GUI_Module.createGUIModule ' + moduleName + ' created');

            if(!this.module_exists(moduleName)) {
                console.error('Created module ' + moduleName + ' does not exist in Weblication');
            }
        }
    }
}

const $Weblication = Weblication.getInstance();
console.debug('Weblication.js loaded');

// Must be removed if not necessary anymore
// @deprecated
var MODULE_FUNCTIONS={}
// MODULE_FUNCTIONS.lang = document.documentElement.lang;