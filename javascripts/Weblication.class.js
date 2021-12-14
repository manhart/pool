/*
 * g7system.local
 *
 * Weblication.class.js created at 13.12.21, 15:51
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2021, GROUP7 AG
 */

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
    modules = [];

    static classesMapping = {};

    constructor()
    {
        Weblication._instance = this;
    }

    /**
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

    static registerClass(className, Class)
    {
        Weblication.classesMapping[className] = Class;
    }

    /**
     *
     * @param GUI_Module Module
     */
    registerModule(Module)
    {
        let moduleName = Module.getName();
        if((moduleName in this.modules)) {
            throw new Error('Module with Name ' + moduleName + ' already exists. Registration not possible!');
        }
        this.modules[moduleName] = Module;
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
        return this.modules[moduleName];
    }

    module_exists(moduleName)
    {
        return (moduleName in this.modules);
    }
}
const $Weblication = Weblication.getInstance();

console.debug('Weblication.class.js loaded');