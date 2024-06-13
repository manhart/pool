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
use pool\classes\Core\Input\Session;

/**
 * GUI_Select
 *
 * @package POOL
 * @since 2004/07/07
 */
class GUI_Select extends GUI_Universal
{
    /**
     * Defaults
     */
    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVars(
            [
                'name' => $this->getName(),

                'options'         => [],    // oder String getrennt mit ;
                'values'          => [],    // oder String getrennt mit ;
                'styles'          => [], // oder String getrennt mit ;
                'selected'        => '',        // entspricht einem Wert von "values"
                'defaultselected' => '',
                'defaultvalue'    => '',        // similar to defaultselected

                'save'        => '',
                'use_session' => 0,
                'session_var' => $this->getName(),

                'datafld'      => null,
                'datasrc'      => null,
                'dataformatas' => null,
                'disabled'     => null,
                'multiple'     => null,
                'size'         => null,
                'tabindex'     => null,

                'onfocus'  => '',
                'onchange' => '',
                'onblur'   => '',
            ]
        );

        parent::init(Input::GET | Input::POST);
    }

    public function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_option.html', self::class, true);
        $this->Template->setFilePath('stdoutOption', $file);

        $file = $this->Weblication->findTemplate('tpl_select.html', self::class, true);
        $this->Template->setFilePath('stdout', $file);

        $this->Template->useFile('stdout');
    }

    /**
     * Prepare template
     */
    protected function prepare(): void
    {
        if($this->Input->getVar('defaultvalue')) {
            $this->Input->setVar('defaultselected', $this->Input->getVar('defaultvalue'));
        }

        parent::prepare();

        $id = $this->id;
        $name = $this->Input->getVar('name');

        $session_var = $this->Input->getVar('session_var');

        // id mit name (sowie umgekehrt) abgleichen
        if($name != $this->Defaults->getVar('name') && $id == $this->getName()) {
            $id = $name;
        }
        if($id != $this->Defaults->getVar('name') && $name == $this->getName()) {
            $name = $id;
        }

        $nameForValue = str_ends_with($name, '[]') ? substr($name, 0, strlen($name) - 2) : $name;
        $valueByName = $this->Input->getVar($nameForValue) != $name ? $this->Input->getVar($nameForValue) : '';

        // save value into session
        $buf_save = $this->Input->getVar('save');
        if($this->Session instanceof Session && $this->Input->getAsInt('use_session') == 1) {
            if(!empty($buf_save) && $this->Input->getVar($buf_save) == 1) {
                $this->Session->setVar($session_var, $this->Input->getVar('selected') == '' ? $valueByName : $this->Input->getVar('selected'));
            }
            // Wert (value) ermitteln (session, object name, value, defaultvalue)
            $selected = $this->Session->getVar($session_var);
        }
        else {
            $selected = $this->Input->getVar('selected') != '' ? $this->Input->getVar('selected') : $valueByName;
        }
        if(empty($selected)) {
            $selected = $this->Input->getVar('defaultselected');
        }


        #### Events
        $events = $this->events;
        if($onfocus = $this->Input->getVar('onfocus')) {
            $events .= ' ';
            $events .= 'onfocus="'.$onfocus.'"';
        }
        if($onchange = $this->Input->getVar('onchange')) {
            $events .= ' ';
            $events .= 'onchange="'.$onchange.'"';
        }
        if($onblur = $this->Input->getVar('onblur')) {
            $events .= ' ';
            $events .= 'onblur="'.$onblur.'"';
        }

        #### leere Attribute
        $emptyattributes = '';
        if($this->Input->getVar('disabled')) {
            $emptyattributes .= 'disabled';
        }
        if($this->Input->getVar('multiple')) {
            $emptyattributes .= ' ';
            $emptyattributes .= 'multiple';
        }

        #### Attribute
        $attributes = $this->attributes;
        if($datafld = $this->Input->getVar('datafld')) {
            $attributes .= ' ';
            $attributes .= 'datafld="'.$datafld.'"';
        }
        if($datasrc = $this->Input->getVar('datasrc')) {
            $attributes .= ' ';
            $attributes .= 'datasrc="'.$datasrc.'"';
        }
        if($dataformatas = $this->Input->getVar('dataformatas')) {
            $attributes .= ' ';
            $attributes .= 'dataformatas="'.$dataformatas.'"';
        }
        if($size = $this->Input->getVar('size')) {
            $attributes .= ' ';
            $attributes .= 'size="'.$size.'"';
        }
        if($tabindex = $this->Input->getVar('tabindex')) {
            $attributes .= ' ';
            $attributes .= 'tabindex="'.$tabindex.'"';
        }
        if($defaultvalue = $this->Input->getVar('defaultselected')) {
            $attributes .= ' ';
            $attributes .= 'defaultvalue="'.$defaultvalue.'"';
        }

        #### options
        $options = $this->Input->getVar('options');
        if(!is_array($options)) $options = explode(';', $options);
        $values = $this->Input->getVar('values');
        if(!is_array($values)) $values = explode(';', $values);
        $styles = $this->Input->getVar('styles');
        if(!is_array($styles)) $styles = explode(';', $styles);
        if(!$options) $options = $values;
        $option_content = '';
        $sizeofOptions = count($options);
        $this->Template->useFile('stdoutOption');
        for($i = 0; $i < $sizeofOptions; $i++) {
            $value = $values[$i] ?? '';
            $content = $options[$i] ?? '';
            $style = $styles[$i] ?? '';

            $select = is_array($selected) ? (in_array($value, $selected) ? 1 : null) : (($selected == $value) ? 1 : null);

            $oemptyattributes = '';
            if($select) {
                $oemptyattributes .= ' ';
                $oemptyattributes .= 'selected';
            }

            $this->Template->setVar([
                    'ID'              => $name.'_'.$i,
                    'VALUE'           => $value,
                    'CLASS'           => $style,
                    'CONTENT'         => $content,
                    'ATTRIBUTES'      => '',
                    'EMPTYATTRIBUTES' => $oemptyattributes,
                    'EVENTS'          => '',
                ]
            );
            $this->Template->parse('stdoutOption');
            $option_content .= $this->Template->getContent('stdoutOption');
        }
        $this->Template->useFile('stdout');

        #### Set Template wildcards
        $this->Template->setVar([
                'ID'              => $id,
                'NAME'            => $name,
                'ATTRIBUTES'      => ltrim($attributes),
                'EVENTS'          => ltrim($events),
                'EMPTYATTRIBUTES' => $emptyattributes,
                'CONTENT'         => $option_content,
            ]
        );
    }
}