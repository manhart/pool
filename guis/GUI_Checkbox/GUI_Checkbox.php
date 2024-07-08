<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

use pool\classes\Core\Input\Input;

/**
 * Class GUI_Checkbox
 *
 * @package pool\guis\GUI_Checkbox
 * @since 2004/07/07
 */
class GUI_Checkbox extends GUI_InputElement
{
    function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVar('type', 'checkbox');
        $this->Defaults->addVar('array', 0);
        $this->Defaults->addVar('label');
        parent::init(Input::GET | Input::POST);
    }

    public function loadFiles(): void
    {
        $file = $this->Weblication->findTemplate('tpl_checkbox.html', $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);
    }

    protected function prepare(): void
    {
        if($this->Input->getVar('array') == 1) {
            $this->Input->setVar('name', $this->Input->getVar('name').'[]');
        }
        if($this->Input->getVar('label') != '') {
            $this->Template->newBlock('Label');
            $this->Template->setVar('label', $this->Input->getVar('label'));
            $this->Template->setVar('ID', $this->Input->getVar('id'));
            $this->Template->leaveBlock();
        }

        $this->prepareName();

        if($this->Input->getVar('value') == $this->Input->getVar($this->Input->getVar('name'))) {
            $this->Input->setVar('checked', 1);
        }

        parent::prepare();
    }
}