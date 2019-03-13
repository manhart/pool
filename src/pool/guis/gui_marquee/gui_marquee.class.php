<?php

/**
 * Class GUI_Marquee
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */
class GUI_Marquee extends GUI_Module
{
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
    }

    function init($superglobals=I_EMPTY)
    {
        parent::init($superglobals);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_marquee.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('marquee', $file);

    }

    function prepare ()
    {
        $Input = & $this -> Input;

        if ($this -> Weblication -> Main) {
            $this -> Weblication -> Main -> addBodyLoad('marqueePopulate()');
        }
    }

    function finalize()
    {
        $this -> Template -> parse('marquee');
        return $this -> Template -> getContent('marquee');
    }
}