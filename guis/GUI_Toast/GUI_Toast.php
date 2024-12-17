<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\guis\GUI_Toast;

use pool\classes\Core\Input\Input;
use pool\classes\GUI\GUI_Module;

/**
 * Class GUI_Toast
 *
 * @package pool\guis\GUI_Toast
 * @since 2020-11-18, 19:12
 */
class GUI_Toast extends GUI_Module
{
    protected int $superglobals = Input::EMPTY;

    public function loadFiles(): static
    {
        parent::loadFiles();
        $fw = $this->getVar('framework') ?? 'bs4';
        $tpl = $this->Weblication->findTemplate('tpl_toast_'.$fw.'.html', 'GUI_Toast', true);
        $this->Template->setFilePath('stdout', $tpl);

        $this->Weblication?->getFrame()?->getHeadData()
            ?->addClientWebAsset('js', 'Toast', __CLASS__, true)
            ?->addClientWebAsset('css', "toast_$fw", __CLASS__, true)
        ;

        return $this;
    }

    protected function prepare(): void
    {
        $this->Template->setVar('moduleName', $this->getName());
    }
}
