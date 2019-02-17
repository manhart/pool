<?php
/**
 * Class GUI_Radiobutton
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */
class GUI_Radiobutton extends GUI_InputElement
{
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('type', 'radio');

        $this -> Defaults -> addVar('label', '');

        parent::init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_radiobutton.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    function prepare ()
    {
        $Template = & $this -> Template;
        $Input = & $this -> Input;

        if ($Input -> getVar('label') != '') {
            $Template -> newBlock('Label');
            $Template -> setVar('label', $Input -> getVar('label'));
            $Template -> setVar('id', $Input -> getVar('id'));
            $Template -> leaveBlock();
        }

        parent :: prepare();
    }

    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}