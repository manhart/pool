<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * GUI_InputElement.class.php
 *
 * @version $Id: GUI_InputElement.class.php,v 1.8 2007/07/12 12:55:51 aziz Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

use pool\classes\Core\Input\Input;
use pool\classes\Core\Input\Session;

/**
 * GUI_InputElement
 *
 * Grundelemente von Input (<input type=text>).
 *
 * @package pool
 * @author manhart
 * @version $Id: GUI_InputElement.class.php,v 1.8 2007/07/12 12:55:51 aziz Exp $
 */
class GUI_InputElement extends GUI_Universal
{
    /**
     * Initialisiert Standardwerte:
     *
     * TODO Parameter
     *
     * Ueberschreiben moeglich durch Variablen von INPUT_GET und INPUT_POST.
     */
    public function init(?int $superglobals = Input::EMPTY)
    {
        $this->Defaults->addVars([
                'name' => $this->getName(),

                'type' => '',
                'value' => '',
                'defaultvalue' => '',
                'save' => '',
                'use_session' => 0,
                'session_var' => $this->getName(),

                'accept' => null,
                'accesskey' => null,
                'align' => null,
                'alt' => null,
                'checked' => null,
                'datafld' => null,
                'datasrc' => null,
                'dataformatas' => null,
                'disabled' => null,
                'ismap' => null,
                'maxlength' => null,
                'readonly' => null,
                'size' => null,
                'src' => null,
                'tabindex' => null,
                'usemap' => null,
                'placeholder' => null,

                'onfocus' => '',
                'onchange' => '',
                'onblur' => '',
                'onselect' => ''
            ]
        );

        parent::init($superglobals);
    }

    public function prepareName(): void
    {
        $id = $this->id;
        $name = $this->Input->getVar('name');

        // id mit name (sowie umgekehrt) abgleichen
        if($name != $this->Defaults->getVar('name') and $id == $this->getName()) {
            $id = $name;
        }
        if($id != $this->Defaults->getVar('name') and $name == $this->getName()) {
            $name = $id;
        }
        $this->Input->setVars(array(
                'name' => $name,
                'id' => $id)
        );
    }

    /**
     * @return void
     */
    function prepare(): void
    {
        parent::prepare();

        $session_variable = $this->Input->getVar('session_var');

        // Namensabgleich
        $this->prepareName();

        $name = $this->Input->getVar('name');
        $id = $this->Input->getVar('id');

        #### Events
        $events = $this->events;
        $onfocus = $this->Input->getVar('onfocus');
        if($onfocus) {
            $events .= ' ';
            $events .= 'onfocus="' . $onfocus . '"';
        }
        $onchange = $this->Input->getVar('onchange');
        if($onchange) {
            $events .= ' ';
            $events .= 'onchange="' . $onchange . '"';
        }
        $onblur = $this->Input->getVar('onblur');
        if($onblur) {
            $events .= ' ';
            $events .= 'onblur="' . $onblur . '"';
        }
        $onselect = $this->Input->getVar('onselect');
        if($onselect) {
            $events .= ' ';
            $events .= 'onselect="' . $onselect . '"';
        }

        #### leere Attribute
        $emptyattributes = '';
        $checked = $this->Input->getVar('checked');
        if($checked) {
            $emptyattributes .= 'checked';
        }
        $disabled = $this->Input->getVar('disabled');
        if($disabled) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'disabled';
        }
        $ismap = $this->Input->getVar('ismap');
        if($ismap) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'ismap';
        }
        $readonly = $this->Input->getVar('readonly');
        if($readonly) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'readonly';
        }

        #### Attribute
        $attributes = $this->attributes;
        $accept = $this->Input->getVar('accept');
        if($accept) {
            $attributes .= ' ';
            $attributes .= 'accept="' . $accept . '"';
        }
        $accesskey = $this->Input->getVar('accesskey');
        if($accesskey) {
            $attributes .= ' ';
            $attributes .= 'accesskey="' . $accesskey . '"';
        }
        $align = $this->Input->getVar('align');
        if($align) {
            $attributes .= ' ';
            $attributes .= 'align="' . $align . '"';
        }
        $alt = $this->Input->getVar('alt');
        if($alt) {
            $attributes .= ' ';
            $attributes .= 'alt="' . $alt . '"';
        }
        $datafld = $this->Input->getVar('datafld');
        if($datafld) {
            $attributes .= ' ';
            $attributes .= 'datafld="' . $datafld . '"';
        }
        $datasrc = $this->Input->getVar('datasrc');
        if($datasrc) {
            $attributes .= ' ';
            $attributes .= 'datasrc="' . $datasrc . '"';
        }
        $dataformatas = $this->Input->getVar('dataformatas');
        if($dataformatas) {
            $attributes .= ' ';
            $attributes .= 'dataformatas="' . $dataformatas . '"';
        }
        $maxlength = $this->Input->getVar('maxlength');
        if($maxlength) {
            $attributes .= ' ';
            $attributes .= 'maxlength="' . $maxlength . '"';
        }
        $size = $this->Input->getVar('size');
        if($size) {
            $attributes .= ' ';
            $attributes .= 'size="' . $size . '"';
        }
        $tabindex = $this->Input->getVar('tabindex');
        if($tabindex) {
            $attributes .= ' ';
            $attributes .= 'tabindex="' . $tabindex . '"';
        }
        $type = $this->Input->getVar('type');
        if($type) {
            $attributes .= ' ';
            $attributes .= 'type="' . $type . '"';
        }
        $defaultValue = $this->Input->getVar('defaultvalue');
        if($defaultValue) {
            $attributes .= ' ';
            $attributes .= 'defaultvalue="' . $defaultValue . '"';
        }

        $placeholder = $this->Input->getVar('placeholder');
        if($placeholder) {
            $attributes .= ' ';
            $attributes .= 'placeholder="' . $placeholder . '"';
        }

        #### Set Template wildcards
        $this->Template->setVar(
            array(
                'ID' => $id,
                'NAME' => $name,
                'ATTRIBUTES' => ltrim($attributes),
                'EVENTS' => ltrim($events),
                'EMPTYATTRIBUTES' => ltrim($emptyattributes)
            )
        );

        $valueByName = ($this->Input->getVar($name) != $name) ? $this->Input->getVar($name) : '';

        // save value into session
        $value = '';

        $buf_save = $this->Input->getVar('save');
        if($this->Session instanceof Session and $this->Input->getAsInt('use_session') == 1) {
            if(!empty($buf_save) and $this->Input->getAsInt($buf_save) == 1) {
                $this->Session->setVar($session_variable, $this->Input->getVar('value') == '' ? $valueByName : $this->Input->getVar('value'));
            }

            // Wert (value) ermitteln (session, object name, value, defaultvalue)
            $value = $this->Session->getVar($session_variable);
        }
        else {
            $value = $this->Input->getVar('value') != '' ? $this->Input->getVar('value') : $valueByName;
            if($value == '{' . $name . '}') {
                $value = '';
            }
        }
        if($value == '' or is_null($value)) {//  or (($Input -> getVar('value') == '') and $name == $Input -> getVar($name)) // fix
            $value = $this->Input->getVar('defaultvalue');
        }

        $this->Template->setVar('VALUE', $value, Template::CONVERT_HTMLSPECIALCHARS);
    }
}