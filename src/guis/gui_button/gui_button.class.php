<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_button.class.php
 *
 * @version $Id: gui_button.class.php 37657 2019-03-20 16:46:08Z manhart $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

/**
 * Schaltfläche / Button
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_button.class.php 37657 2019-03-20 16:46:08Z manhart $
 * @access public
 **/
class GUI_Button extends GUI_Universal
{
    /**
     * Initialisiert Standardwerte!
     *
     * @access public
     * @param int Superglobals I_GET, I_POST, I_REQUEST....
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'name'	=> $this->getName(),
                'type'  => 'button', // button, reset, submit
                'value' => null,
                'content' => '', // = Caption oder Image
            )
        );

        parent::init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_button.html', 'gui_button', true);
        $this->Template->setFilePath('stdout', $file);
    }

    /**
     * @return
     **/
    function prepare ()
    {
        parent::prepare();

        $id = $this->id;
        $name = $this->Input->getVar('name');
        $content = $this->Input->getVar('content');
        $type = $this->Input->getVar('type');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> Defaults -> getVar('name') and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> Defaults -> getVar('name') and $name == $this -> getName()) {
            $name = $id;
        }
        $valueByName = $this->Input->getVar($name);

        #### Events
        $events = $this->events;

        #### leere Attribute
        $emptyattributes = '';
        if ($disabled = $this->Input->getVar('autofocus')) {
            $emptyattributes .= 'autofocus';
        }
        if ($disabled = $this->Input->getVar('disabled')) {
            if($emptyattributes != '') $emptyattributes .= ' ';
            $emptyattributes .= 'disabled';
        }
        if ($formnovalidate = $this->Input->getVar('formnovalidate')) {
            if($emptyattributes != '') $emptyattributes .= ' ';
            $emptyattributes .= 'formnovalidate';
        }

        #### Attribute
        $attributes = $this->attributes;
        if ($form = $this->Input->getVar('form')) {
            $attributes .= ' ';
            $attributes .= 'form="' . $form . '"';
        }
        if ($formaction = $this->Input->getVar('formaction')) {
            $attributes .= ' ';
            $attributes .= 'formaction="' . $formaction . '"';
        }
        if ($formenctype = $this->Input->getVar('formenctype')) {
            $attributes .= ' ';
            $attributes .= 'formenctype="' . $formenctype . '"';
        }
        if ($formmethod = $this->Input->getVar('formmethod')) {
            $attributes .= ' ';
            $attributes .= 'formmethod="' . $formmethod . '"';
        }
        if ($formtarget = $this->Input->getVar('formtarget')) {
            $attributes .= ' ';
            $attributes .= 'formtarget="' . $formtarget . '"';
        }

        #### Set Template wildcards
        $this->Template->setVar(
            array(
                'ID' 				=> $id,
                'NAME' 				=> $name,
                'TYPE'              => $type,
                'ATTRIBUTES'		=> ltrim($attributes),
                'EMPTYATTRIBUTES' 	=> $emptyattributes,
                'CONTENT'           => $content,
                'EVENTS'            => $events
            )
        );
    }

    function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}