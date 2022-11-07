<?php

/**
 * Class GUI_Marquee
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */
class GUI_Marquee extends GUI_Module
{
    function init(?int $superglobals=I_EMPTY)
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
        if ($this->Weblication->getFrame()) {
            $this->Weblication->getFrame()->addBodyLoad('marqueePopulate()');
        }
    }

    function finalize(): string
    {
        $this -> Template -> parse('marquee');
        return $this -> Template -> getContent('marquee');
    }
}