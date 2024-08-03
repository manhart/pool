<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Input\Input;

/**
 * Class GUI_Toast
 * @package pool\guis\GUI_Toast
 * @since 2020-11-18, 19:12
 */
class GUI_Toast extends GUI_Module
{
    /**
     * @param int|null $superglobals
     * @throws Exception
     */
    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVar('framework', 'bs4');
        parent::init($superglobals);
    }

    /**
     * load files
     */
    public function loadFiles()
    {
        parent::loadFiles();
        $fw = $this->getVar('framework');
        $tpl = $this->Weblication->findTemplate('tpl_toast_'.$fw.'.html', __CLASS__, true);
        $this->Template->setFilePath('stdout', $tpl);

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->getHeadData()->addJavaScript($this->Weblication->findJavaScript('Toast.js', __CLASS__, true));
            $this->Weblication->getFrame()->getHeadData()->addStyleSheet($this->Weblication->findStyleSheet('toast_'.$fw.'.css', __CLASS__, true));
        }
    }

    /**
     * prepare content
     */
    protected function prepare(): void
    {
        $this->Template->setVar('moduleName', $this->getName());
    }
}