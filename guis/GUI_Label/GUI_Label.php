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

/**
 * -= PHP Object Oriented Library (POOL) =-
 * Das GUI_Label ist lediglich ein Anzeigefeld fuer Text.
 *
 * @version $Id: gui_label.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 * @since 2004-02-18
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

namespace pool\guis\GUI_Label;

use GUI_HTMLElement;
use pool\classes\Core\Input\Input;

/**
 * Das GUI_Label ist lediglich ein Anzeigefeld fuer Text.
 *
 * @package pool\guis\GUI_Label
 * @since 2004/02/18
 */
class GUI_Label extends GUI_HTMLElement
{
    protected int $superglobals = Input::EMPTY;

    /*
     * protected array $inputFilter = [
     * 'caption' => [DataType::ALPHANUMERIC, ''],
     * 'for' => [DataType::ALPHANUMERIC, ''],
     * ];
     */

    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVars([
            'content' => '',
            'for' => '',
        ]);
        parent::init($superglobals);
    }

    protected function finalize(): string
    {
        $name = $this->Input->getVar('name');
        if($name) $name = "name=\"$name\"";
        return "<label id=\"$this->id\" $name for=\"{$this->getVar('for')}\" {$this->getVar('attributes')}>{$this->getVar('caption')}</label>";
    }
}