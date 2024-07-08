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
 * Class GUI_Radiobutton
 *
 * @package pool\guis\GUI_Radiobutton
 * @since 2004/07/07
 */
class GUI_Radiobutton extends GUI_InputElement
{
    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVar('type', 'radio');
        $this->Defaults->addVar('label');
        parent::init(Input::GET | Input::POST);
    }

    public function loadFiles(): void
    {
        $file = $this->Weblication->findTemplate('tpl_radiobutton.html', $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);
    }

    protected function prepare(): void
    {
        $Template = &$this->Template;
        if($this->Input->getVar('label') != '') {
            $Template->newBlock('Label');
            $Template->setVar('label', $this->Input->getVar('label'));
            $Template->setVar('id', $this->Input->getVar('id'));
            $Template->leaveBlock();
        }
        parent::prepare();
    }
}