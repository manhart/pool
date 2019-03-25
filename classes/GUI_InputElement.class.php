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

/**
 * GUI_InputElement
 *
 * Grundelemente von Input (<input type=text>).
 *
 * @package pool
 * @author manhart
 * @version $Id: GUI_InputElement.class.php,v 1.8 2007/07/12 12:55:51 aziz Exp $
 * @access public
 **/
class GUI_InputElement extends GUI_Universal
{
    /**
     * GUI_InputElement::init()
     *
     * Initialisiert Standardwerte:
     *
     * TODO Parameter
     *
     * Ueberschreiben moeglich durch Variablen von INPUT_GET und INPUT_POST.
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'name'			=> $this -> getName(),

                'type'			=> '',
                'value'			=> '',
                'defaultvalue'	=> '',
                'save'			=> '',
                'use_session'	=> 0,
                'session_var' 	=> $this -> getName(),

                'accept'		=> null,
                'accesskey'		=> null,
                'align'			=> null,
                'alt'			=> null,
                'checked'		=> null,
                'datafld'		=> null,
                'datasrc'		=> null,
                'dataformatas'	=> null,
                'disabled'		=> null,
                'ismap'			=> null,
                'maxlength'		=> null,
                'readonly'		=> null,
                'size'			=> null,
                'src'			=> null,
                'tabindex'		=> null,
                'usemap'		=> null,
                'placeholder'   => null,

                'onfocus' 		=> '',
                'onchange'		=> '',
                'onblur'		=> '',
                'onselect'		=> ''
            )
        );

        parent::init($superglobals);

    }

    function prepareName()
    {
        $id = $this -> id;
        $name = $this -> Input -> getVar('name');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> Defaults -> getVar('name') and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> Defaults -> getVar('name') and $name == $this -> getName()) {
            $name = $id;
        }
        $this -> Input -> setVar(array('name' => $name, 'id' => $id));
    }

    /**
     * GUI_InputElement::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        parent :: prepare();

        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        $session_variable = $this->Input->getVar('session_var');

        // Namensabgleich
        $this -> prepareName();

        $name = $Input -> getVar('name');
        $id = $Input -> getVar('id');

        // abgleich session variable
//			if ($session_variable == $this -> getName() and $name != $this -> getName()) {
//				$session_variable = $name;
//			}

        #### Events
        $events = $this->events;
        $onfocus = $Input->getVar('onfocus');
        if ($onfocus) {
            $events .= ' ';
            $events .= 'onfocus="' . $onfocus . '"';
        }
        $onchange = $Input->getVar('onchange');
        if ($onchange) {
            $events .= ' ';
            $events .= 'onchange="' . $onchange . '"';
        }
        $onblur = $Input->getVar('onblur');
        if ($onblur) {
            $events .= ' ';
            $events .= 'onblur="' . $onblur . '"';
        }
        $onselect = $Input->getVar('onselect');
        if ($onselect) {
            $events .= ' ';
            $events .= 'onselect="' . $onselect . '"';
        }

        #### leere Attribute
        $emptyattributes = '';
        $checked = $Input->getVar('checked');
        if ($checked) {
            $emptyattributes .= 'checked';
        }
        $disabled = $Input->getVar('disabled');
        if ($disabled) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'disabled';
        }
        $ismap = $Input->getVar('ismap');
        if ($ismap) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'ismap';
        }
        $readonly = $Input->getVar('readonly');
        if ($readonly) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'readonly';
        }

        #### Attribute
        $attributes = $this -> attributes;
        $accept = $Input->getVar('accept');
        if ($accept) {
            $attributes .= ' ';
            $attributes .= 'accept="' . $accept . '"';
        }
        $accesskey = $Input->getVar('accesskey');
        if ($accesskey) {
            $attributes .= ' ';
            $attributes .= 'accesskey="' . $accesskey . '"';
        }
        $align = $Input->getVar('align');
        if ($align) {
            $attributes .= ' ';
            $attributes .= 'align="' . $align . '"';
        }
        $alt = $Input->getVar('alt');
        if ($alt) {
            $attributes .= ' ';
            $attributes .= 'alt="' . $alt . '"';
        }
        $datafld = $Input->getVar('datafld');
        if ($datafld) {
            $attributes .= ' ';
            $attributes .= 'datafld="' . $datafld . '"';
        }
        $datasrc = $Input->getVar('datasrc');
        if ($datasrc) {
            $attributes .= ' ';
            $attributes .= 'datasrc="' . $datasrc . '"';
        }
        $dataformatas = $Input->getVar('dataformatas');
        if ($dataformatas) {
            $attributes .= ' ';
            $attributes .= 'dataformatas="' . $dataformatas . '"';
        }
        $maxlength = $Input->getVar('maxlength');
        if ($maxlength) {
            $attributes .= ' ';
            $attributes .= 'maxlength="' . $maxlength . '"';
        }
        $size = $Input->getVar('size');
        if ($size) {
            $attributes .= ' ';
            $attributes .= 'size="' . $size . '"';
        }
        $tabindex = $Input->getVar('tabindex');
        if ($tabindex) {
            $attributes .= ' ';
            $attributes .= 'tabindex="' . $tabindex . '"';
        }
        $type = $Input->getVar('type');
        if ($type) {
            $attributes .= ' ';
            $attributes .= 'type="' . $type . '"';
        }
        $defaultvalue = $Input->getVar('defaultvalue');
        if ($defaultvalue) {
            $attributes .= ' ';
            $attributes .= 'defaultvalue="' . $defaultvalue . '"';
        }

        $placeholder = $Input->getVar('placeholder');
        if ($placeholder) {
            $attributes .= ' ';
            $attributes .= 'placeholder="' . $placeholder . '"';
        }

        #### Set Template wildcards
        $Template -> setVar(
            array(
                'ID' 				=> $id,
                'NAME' 				=> $name,
                'ATTRIBUTES'		=> ltrim($attributes),
                'EVENTS' 			=> ltrim($events),
                'EMPTYATTRIBUTES' 	=> ltrim($emptyattributes)
            )
        );

        $valueByName = ($Input -> getVar($name) != $name) ? $Input -> getVar($name) : '';

        // save value into session
        $value = '';

        $buf_save = $Input -> getVar('save');
        if (is_a($Session, 'ISession') and $Input->getVar('use_session') == 1) {
            if (empty($buf_save) == false and $Input -> getVar($buf_save) == 1) {
                $Session -> setVar($session_variable, $Input -> getVar('value') == ''  ? $valueByName : $Input -> getVar('value'));
            }

            // Wert (value) ermitteln (session, object name, value, defaultvalue)
            $value = $Session->getVar($session_variable);
        }
        else {
            $value = $Input -> getVar('value') != ''  ? $Input -> getVar('value') : $valueByName;
            if ($value == '{' . $name . '}') {
                $value = '';
            };
        }
        if ($value=='' or is_null($value)) {//  or (($Input -> getVar('value') == '') and $name == $Input -> getVar($name)) // fix
            $value = $Input -> getVar('defaultvalue');
        }

        $Template -> setVar('VALUE', $value);
    }
}