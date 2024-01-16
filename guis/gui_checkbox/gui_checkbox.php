<?php

use pool\classes\Core\Input\Input;

/**
 * Class GUI_Checkbox
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */
class GUI_Checkbox extends GUI_InputElement
{
    function init(?int $superglobals= Input::EMPTY)
    {
        $this -> Defaults -> addVar('type', 'checkbox');

        $this -> Defaults -> addVar('array', 0);
        $this -> Defaults -> addVar('label');

        parent::init(Input::GET | Input::POST);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_checkbox.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    function prepare (): void
    {
        $Template = & $this -> Template;
        $Input = & $this -> Input;

        if($Input -> getVar('array') == 1) {
            $Input -> setVar('name', $Input -> getVar('name') . '[]');
        }

        if ($Input -> getVar('label') != '') {
            $Template -> newBlock('Label');
            $Template -> setVar('label', $Input -> getVar('label'));
            $Template -> setVar('ID', $Input -> getVar('id'));
            $Template -> leaveBlock();
        }

        $this -> id = $Input -> getVar('id');
        $this -> prepareName();

        if($Input -> getVar('value') == $Input -> getVar($Input -> getVar('name'))) {
            $Input -> setVar('checked', 1);
        }

        parent :: prepare();
    }

    function finalize(): string
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}