<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Input;

/**
 * Class GUI_Button
 * @package pool\guis\GUI_Button
 * @since 2004/07/07
 */
class GUI_Button extends GUI_Universal
{
    /**
     * @param int|null $superglobals Superglobals I_GET, I_POST, I_REQUEST....
     */
    public function init(?int $superglobals = Input::EMPTY)
    {
        $this->Defaults->addVars([
                'name' => $this->getName(),
                'type' => 'button', // button, reset, submit
                'value' => null,
                'content' => '', // = Caption oder Image
            ]
        );

        parent::init($superglobals);
    }

    public function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_button.html', __CLASS__, true);
        $this->Template->setFilePath('stdout', $file);
    }

    /**
     * @return
     */
    function prepare()
    {
        parent::prepare();

        $id = $this->id;
        $name = $this->Input->getVar('name');
        $content = $this->Input->getVar('content');
        $type = $this->Input->getVar('type');

        // id mit name (sowie umgekehrt) abgleichen
        if($name != $this->Defaults->getVar('name') and $id == $this->getName()) {
            $id = $name;
        }
        if($id != $this->Defaults->getVar('name') and $name == $this->getName()) {
            $name = $id;
        }
        $valueByName = $this->Input->getVar($name);

        #### Events
        $events = $this->events;

        #### leere Attribute
        $emptyattributes = '';
        if($disabled = $this->Input->getVar('autofocus')) {
            $emptyattributes .= 'autofocus';
        }
        if($disabled = $this->Input->getVar('disabled')) {
            if($emptyattributes != '') $emptyattributes .= ' ';
            $emptyattributes .= 'disabled';
        }
        if($formnovalidate = $this->Input->getVar('formnovalidate')) {
            if($emptyattributes != '') $emptyattributes .= ' ';
            $emptyattributes .= 'formnovalidate';
        }

        #### Attribute
        $attributes = $this->attributes;
        if($form = $this->Input->getVar('form')) {
            $attributes .= ' ';
            $attributes .= 'form="' . $form . '"';
        }
        if($formaction = $this->Input->getVar('formaction')) {
            $attributes .= ' ';
            $attributes .= 'formaction="' . $formaction . '"';
        }
        if($formenctype = $this->Input->getVar('formenctype')) {
            $attributes .= ' ';
            $attributes .= 'formenctype="' . $formenctype . '"';
        }
        if($formmethod = $this->Input->getVar('formmethod')) {
            $attributes .= ' ';
            $attributes .= 'formmethod="' . $formmethod . '"';
        }
        if($formtarget = $this->Input->getVar('formtarget')) {
            $attributes .= ' ';
            $attributes .= 'formtarget="' . $formtarget . '"';
        }

        #### Set Template wildcards
        $this->Template->setVars(
            [
                'id' => $id,
                'NAME' => $name,
                'TYPE' => $type,
                'ATTRIBUTES' => ltrim($attributes),
                'EMPTYATTRIBUTES' => $emptyattributes,
                'CONTENT' => $content,
                'EVENTS' => $events
            ]
        );
    }

    public function finalize(): string
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}