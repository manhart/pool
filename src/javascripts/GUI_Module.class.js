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
     * @param string name of module
     */
    constructor(name)
    {
        this.name = name;
        this.className = what(this);

        // 10.02.2022, AM, sometimes the edge has an undefined className (especially when we put new versions live)
        if(this.className == undefined) {
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