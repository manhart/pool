<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * GUI_Textarea.class.php
 *
 * @version $Id: gui_textarea.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Textarea
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_textarea.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_Textarea extends GUI_Universal
{
    /**
     * Initialisiert Standardwerte:
     *
     * TODO Parameter
     *
     * Ueberschreiben moeglich durch Variablen von INPUT_GET und INPUT_POST.
     *
     * @access public
     **/
    function init(?int $superglobals= Input::INPUT_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'name'			=> $this -> getName(),

                'value'			=> '',
                'defaultvalue'	=> '',
                'save'			=> '',
                'use_session'	=> 0,
                'session_var' 	=> $this -> getName(),

                'accesskey'		=> null,
                'cols'			=> 20,	// Pflichtattribut
                'datafld'		=> null,
                'datasrc'		=> null,
                'dataformatas'	=> null,
                'disabled'		=> null,
                'readonly'		=> null,
                'rows'			=> 2,	// Pflichtattribut
                'tabindex'		=> null,

                'onfocus' 		=> '',
                'onchange'		=> '',
                'onblur'		=> '',
                'onselect'		=> '',
            )
        );

        parent::init(Input::INPUT_GET | Input::INPUT_POST);
    }

    /**
     * GUI_Textarea::loadFiles()
     *
     * Laedt Template "tpl_textarea.html". Ist im projekteigenen Skinordner ueberschreibbar!
     *
     * @access public
     **/
    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_textarea.html', 'gui_textarea', true);
        $this -> Template -> setFilePath('stdout', $file);

    }

    /**
     * GUI_Textarea::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        parent :: prepare();

        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        $id = $this -> id;
        $name = $Input -> getVar('name');
        $session_variable = $Input -> getVar('session_var');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> getName() and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> getName() and $name == $this -> getName()) {
            $name = $id;
        }
        $Input -> setVar(array('name' => $name, 'id' => $id));
        // abgleich session variable
        if ($session_variable == $this -> getName() and $name != $this -> getName()) {
            $session_variable = $name;
        }

        #### Events
        $events = $this -> events;
        if ($onfocus = $Input -> getVar('onfocus')) {
            $events .= 'onfocus="' . $onfocus . '"';
        }
        if ($onchange = $Input -> getVar('onchange')) {
            $events .= ' ';
            $events .= 'onchange="' . $onchange . '"';
        }
        if ($onblur = $Input -> getVar('onblur')) {
            $events .= ' ';
            $events .= 'onblur="' . $onblur . '"';
        }
        if ($onselect = $Input -> getVar('onselect')) {
            $events .= ' ';
            $events .= 'onselect="' . $onselect . '"';
        }

        #### leere Attribute
        $emptyattributes = '';
        if ($disabled = $Input -> getVar('disabled')) {
            $emptyattributes .= 'disabled';
        }
        if ($readonly = $Input -> getVar('readonly')) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'readonly';
        }

        #### Attribute
        $attributes = $this -> attributes;
        if ($accesskey = $Input -> getVar('accesskey')) {
            $attributes .= 'accesskey="' . $accesskey . '"';
        }
        if ($cols = $Input -> getVar('cols')) {
            $attributes .= ' ';
            $attributes .= 'cols="' . $cols . '"';
        }
        if ($datafld = $Input -> getVar('datafld')) {
            $attributes .= ' ';
            $attributes .= 'datafld="' . $datafld . '"';
        }
        if ($datasrc = $Input -> getVar('datasrc')) {
            $attributes .= ' ';
            $attributes .= 'datasrc="' . $datasrc . '"';
        }
        if ($dataformatas = $Input -> getVar('dataformatas')) {
            $attributes .= ' ';
            $attributes .= 'dataformatas="' . $dataformatas . '"';
        }
        if ($rows = $Input -> getVar('rows')) {
            $attributes .= ' ';
            $attributes .= 'rows="' . $rows . '"';
        }
        if ($tabindex = $Input -> getVar('tabindex')) {
            $attributes .= ' ';
            $attributes .= 'tabindex="' . $tabindex . '"';
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
        if (is_a($Session, 'InputSession') and $Input -> getVar('use_session') == 1) {
            if (empty($buf_save) == false and $Input -> getVar($buf_save) == 1) {
                $Session -> setVar($session_variable, $Input -> getVar('value') == ''  ? $valueByName : $Input -> getVar('value'));
            }

            // Wert (value) ermitteln (session, object name, value, defaultvalue)
            $value = $Session -> getVar($session_variable);
        }
        else {
            $value = $Input -> getVar('value') != ''  ? $Input -> getVar('value') : $valueByName;
            if ($value == '{' . $name . '}') {
                $value = '';
            };
        }
        if (empty($value)) {//  or (($Input -> getVar('value') == '') and $name == $Input -> getVar($name)) // fix
            $value = $Input -> getVar('defaultvalue');
        }
        $Template -> setVar('VALUE', $value);
    }

    /**
     * GUI_Textarea::finalize()
     *
     * Verarbeitet Template (Platzhalter, Bloecke, etc.) und generiert HTML Output.
     *
     * @return string HTML Output (Content)
     **/
    function finalize(): string
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}