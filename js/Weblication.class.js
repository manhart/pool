/*
 * POOL
 *
 * Weblication.js created at 10.12.21, 12:00
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

'use strict';

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
     * @param className
     * @param Class
     * @returns {Weblication}
     */
    static registerClass(className, Class)
    {
        Weblication.classesMapping[className] = Class;
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
            throw new Error('Module '+ moduleName + ' is not registered!');
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

    run()
    {
        const clientData = JSON.parse(window.atob(document.head.querySelector('meta[name=client-data]').content));

        for(const moduleName in clientData) {
            const className = clientData[moduleName].className;
            delete clientData[moduleName].className;
            const options = clientData[moduleName];
            window['$'+moduleName] = GUI_Module.createGUIModule(className, moduleName);

            if(!this.module_exists(moduleName)) continue;

            const $Module = this.getModule(moduleName);
            if(typeof $Module.init === 'function') {
                $Module.init(options);
            }
        }
    }
}
const $Weblication = Weblication.getInstance();

console.debug('Weblication.class.js loaded');