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

    /**
     * @param string name of module
     */
    constructor(name)
    {
        this.name = name;
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
     * creates a new unique GUI_Module. Makes the module globally known with $ in front of the name
     *
     * @param function GUIClassName
     * @param string name
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

        let Inst = new myClass(name);
        return Inst;
    }
}

console.debug('GUI_Module.class.js loaded');