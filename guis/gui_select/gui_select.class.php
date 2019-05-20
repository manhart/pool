<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * GUI_Select.class.php
 *
 * @version $Id: gui_select.class.php,v 1.13 2007/05/15 14:10:46 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Select
 *
 * Edit steuert ein Eingabefeld (<input type=text>).
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_select.class.php,v 1.13 2007/05/15 14:10:46 manhart Exp $
 * @access public
 **/
class GUI_Select extends GUI_Universal
{
    /**
     * GUI_Select::init()
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
                'name'				=> $this -> getName(),

                'options'			=> array(),	// oder String getrennt mit ;
                'values'			=> array(),	// oder String getrennt mit ;
                'styles'			=> array(), // oder String getrennt mit ;
                'selected'			=> '', 		// entspricht einem Wert von "values"
                'defaultselected'	=> '',
                'defaultvalue'		=> '',		// similar to defaultselected

                'save'				=> '',
                'use_session'		=> 0,
                'session_var' 		=> $this -> getName(),

                'datafld'			=> null,
                'datasrc'			=> null,
                'dataformatas'		=> null,
                'disabled'			=> null,
                'multiple'			=> null,
                'size'				=> null,
                'tabindex'			=> null,

                'onfocus' 			=> '',
                'onchange'			=> '',
                'onblur'			=> ''
            )
        );

        parent::init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_option.html', 'gui_select', true);
        $this -> Template -> setFilePath('stdoutOption', $file);

        $file = $this -> Weblication -> findTemplate('tpl_select.html', 'gui_select', true);
        $this -> Template -> setFilePath('stdout', $file);

        $this -> Template -> useFile('stdout');
    }

    /**
     * GUI_Select::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        if($this -> Input -> getVar('defaultvalue')) {
            $this -> Input -> setVar('defaultselected', $this -> Input -> getVar('defaultvalue'));
        }

        parent :: prepare();

        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        $id = $this -> id;
        $name = $Input -> getVar('name');

        $save_form = $Input -> getVar('save_form');
        $use_session = $Input -> getVar('use_session');
        $session_var = $Input -> getVar('session_var');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> Defaults -> getVar('name') and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> Defaults -> getVar('name') and $name == $this -> getName()) {
            $name = $id;
        }
        if(substr($name, -2) == '[]') {
            $nameForValue = substr($name, 0, strlen($name) - 2);
        }
        else {
            $nameForValue = $name;
        }

        $valueByName = ($Input -> getVar($nameForValue) != $name) ? $Input -> getVar($nameForValue) : '';

        // save value into session
        $selected = '';
        $buf_save = $Input -> getVar('save');
        if (is_a($Session, 'ISession') and $Input -> getVar('use_session') == 1) {
            if (empty($buf_save) == false and $Input -> getVar($buf_save) == 1) {
                $Session -> setVar($session_var, $Input -> getVar('selected') == ''  ? $valueByName : $Input -> getVar('selected'));
            }
            // Wert (value) ermitteln (session, object name, value, defaultvalue)
            $selected = $Session -> getVar($session_var);
        }
        else {
            $selected = $Input -> getVar('selected') != ''  ? $Input -> getVar('selected') : $valueByName;
        }
        if (empty($selected)) {
            $selected = $Input -> getVar('defaultselected');
        }

        #### selected ermitteln (selected, name oder defaultselected)
/*			$selected = $Input -> getVar('selected');
        if (empty($selected)) {
            $selected = $Input -> getVar($name);

            if ($selected == '') {
                $selected = $Input -> getVar('defaultselected');
            }
        }*/


        #### Events
        $events = $this -> events;
        if ($onfocus = $Input -> getVar('onfocus')) {
            $events .= ' ';
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

        #### leere Attribute
        $emptyattributes = '';
        if ($disabled = $Input -> getVar('disabled')) {
            $emptyattributes .= 'disabled';
        }
        if ($multiple = $Input -> getVar('multiple')) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'multiple';
        }

        #### Attribute
        $attributes = $this -> attributes;
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
        if ($size = $Input -> getVar('size')) {
            $attributes .= ' ';
            $attributes .= 'size="' . $size . '"';
        }
        if ($tabindex = $Input -> getVar('tabindex')) {
            $attributes .= ' ';
            $attributes .= 'tabindex="' . $tabindex . '"';
        }
        if($defaultvalue = $Input -> getVar('defaultselected')) {
            $attributes .= ' ';
            $attributes .= 'defaultvalue="' . $defaultvalue . '"';
        }

        #### options
        $options = $Input -> getVar('options');
        if(!is_array($options))	$options = explode(';', $options);
        $values = $Input -> getVar('values');
        if(!is_array($values)) $values = explode(';', $values);
        $styles = $Input -> getVar('styles');
        if(!is_array($styles)) $styles = explode(';', $styles);
        if(sizeof($options) == 0) $options = $values;
        $option_content = '';
        $sizeofOptions = SizeOf($options);
        $Template -> useFile('stdoutOption');
        for ($i=0; $i<$sizeofOptions; $i++) {
            $value = isset($values[$i]) ? $values[$i] : '';
            $content = isset($options[$i]) ? $options[$i] : '';
            $style = isset($styles[$i]) ? $styles[$i] : '';

            if(is_array($selected)) {
                $select = (in_array($value, $selected) ? 1 : null);
            }
            else {
                $select = ($selected == $value) ? 1 : null;
            }

            $oemptyattributes = '';
            if ($select) {
                $oemptyattributes .= ' ';
                $oemptyattributes .= 'selected';
            }

            $Template -> setVar(
                array(
                    'ID' => $name . '_' . $i,
                    'VALUE' => $value,
                    'CLASS' => $style,
                    'CONTENT' => $content,
                    'ATTRIBUTES' => '',
                    'EMPTYATTRIBUTES' => $oemptyattributes,
                    'EVENTS' => ''
                )
            );
            $Template -> parse('stdoutOption');
            $option_content .= $Template -> getContent('stdoutOption');

// 29.01.2007, AM, zu langsam (obiger Code effizienter)
/*				$GUI_Option = &new GUI_Option($this -> Owner);//GUI_Module::createGUI('GUI_Option', $this -> Owner);
            $GUI_Option -> autoLoadFiles();
            $GUI_Option -> Input -> setVar(
                array(
                    'value'	=> $value,
                    'selected' => $select,
                    'style' => $style,
                    'content' => $content
                )
            );
            $GUI_Option -> prepare();
            $option_content .= $GUI_Option -> finalize();*/
        }
        $Template -> useFile('stdout');

        #### Set Template wildcards
        $Template -> setVar(
            array(
                'ID' 				=> $id,
                'NAME' 				=> $name,
                'ATTRIBUTES'		=> ltrim($attributes),
                'EVENTS' 			=> ltrim($events),
                'EMPTYATTRIBUTES' 	=> $emptyattributes,
                'CONTENT'			=> $option_content
            )
        );
    }

    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}

/**
 * GUI_Option
 *
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_select.class.php,v 1.13 2007/05/15 14:10:46 manhart Exp $
 * @access public
 **/
class GUI_Option extends GUI_Universal
{
    /**
     * Initialisiert Standardwerte:
     *
     * TODO Parameter
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'value'				=> '',	// oder String getrennt mit ;
                'selected'			=> null,
                'disabled'			=> null,
                'label'				=> null,
                'style'				=> null,
                'content'			=> null
            )
        );

        parent::init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_option.html', 'gui_select', true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    /**
     * GUI_Option::prepare()
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
        $value = $Input -> getVar('value');
        $selected = $Input -> getVar('selected');

        #### Events
        $events = $this -> events;

        #### leere Attribute
        $emptyattributes = '';
        if ($disabled = $Input -> getVar('disabled')) {
            $emptyattributes .= 'disabled';
        }
        if ($selected) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'selected';
        }

        #### Attribute
        $attributes = $this -> attributes;
        if ($label = $Input -> getVar('label')) {
            $attributes .= ' ';
            $attributes .= 'label="' . $label . '"';
        }

        if($style = $Input -> getVar('style')) {
            $attributes .= ' ';
            $attributes .= 'style="' . $style . '"';
        }

        #### Set Template wildcards
        $Template -> setVar(
            array(
                'ID' 				=> $id,
                'VALUE'				=> $value,
                'ATTRIBUTES'		=> ltrim($attributes),
                'EVENTS' 			=> ltrim($events),
                'EMPTYATTRIBUTES' 	=> $emptyattributes,
                'CONTENT'			=> $Input -> getVar('content')
            )
        );
    }

    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}