<?php
/* Class GUI_DynClock
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

class GUI_DynClock extends GUI_Module
{
    function GUI_DynClock(& $Owner)
    {
        parent::GUI_Module($Owner);
    }

    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar('cycleClock', 'true');
        $this->Defaults->addVar('showSec', 'true');
        $this->Defaults->addVar('interval', 1000);
        $this->Defaults->addVar('format', 1);

        parent::init($superglobals);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_dynclock.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('dynclock', $file);
    }

    function prepare ()
    {
        $Input = & $this -> Input;

        $this -> Template -> setVar('interval', $this -> Input -> getVar('interval'));
        $this -> Template -> setVar('showSec', $this -> Input -> getVar('showSec'));
        $this -> Template -> setVar('format', $this -> Input -> getVar('format'));

        if ($this -> Weblication -> Main) {
            $this -> Weblication -> Main -> addBodyLoad('startDynClock('.$this -> Input -> getVar('cycleClock').')');
        }
    }

    function finalize()
    {
        $this -> Template -> parse();
        return $this -> Template -> getContent();
    }
}