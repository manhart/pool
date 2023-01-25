/*
 * POOL
 *
 * Weblication.js created at 10.12.21, 12:00
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class Weblication
{
    #modules = [];

    static classesMapping = {};

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
     * register class
     *
     * @param Class
     * @returns {Weblication}
     */
    static registerClass(Class)
    {
        Weblication.classesMapping[Class.name] = Class;
        return this;
    }

    /**
     * register module
     *
     * @param GUI_Module Module
     * @returns {Weblication}
     */
    registerModule(Module)
    {
        let moduleName = Module.getName();
        if((moduleName in this.#modules)) {
            throw new Error('Module with Name ' + moduleName + ' already exists. Registration not possible!');
        }
        this.#modules[moduleName] = Module;
        // console.debug('Weblication has Module "' + moduleName + '" registered');
        return this;
    }

    /**
     * unregister module
     *
     * @param GUI_Module Module
     * @returns {Weblication}
     */
    unregisterModule(Module)
    {
        let moduleName = Module.getName();
        delete this.#modules[moduleName];
        return this;
    }

    /**
     * returns module
     *
     * @param moduleName
     * @returns {GUI_Module}
     */
    getModule(moduleName)
    {
        if(!this.module_exists(moduleName)) {
            throw new Error('Module with Name ' + moduleName + ' was not found!');
        }
        return this.#modules[moduleName];
    }

    module_exists(moduleName)
    {
        return (moduleName in this.#modules);
    }

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

        let clientData = window.atob(clientDataElement.content);
        if(!isJsonString(clientData)) {
            console.debug('client-data content is not compatible with json');
            return;
        }

        clientData = JSON.parse(clientData);
        console.debug('client-data', clientData);

        for(const moduleName in clientData) {
            const className = clientData[moduleName].className;
            try {
                window['$' + moduleName] = GUI_Module.createGUIModule(className, moduleName);
            }
            catch(e) {
                console.error(e.toString());
            }
            // console.debug('GUI_Module.createGUIModule ' + moduleName + ' created');

            if(!this.module_exists(moduleName)) continue;
            const $Module = this.getModule(moduleName);

            const initOptions = clientData[moduleName].initOptions ?? [];
            ready(() => $Module.init(initOptions));
        }
    }
}

const $Weblication = Weblication.getInstance();
console.debug('Weblication.class.js loaded');

// Must be removed if not necessary anymore
// @deprecated
var MODULE_FUNCTIONS={}
MODULE_FUNCTIONS.lang = document.documentElement.lang;
