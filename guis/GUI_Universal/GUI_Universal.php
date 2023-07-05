<?php

use pool\classes\Core\Input;

/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_universal.class.php
 *
 * @version $Id: GUI_Universal.class.php,v 1.5 2007/02/27 10:36:31 hoesl Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

class GUI_Universal extends GUI_Module
{
    /**
     * @var string ID (unique identifier)
     */
    protected string $id = '';

    protected string $attributes = '';

    protected string $events = '';

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
                /* Allgemeine Universalattribute */
                'id' => $this->getName(),
                'title' => '',
                'style' => null,
                'class' => $this->getClassName(),
                'class_error' => $this->getClassName().'_error',
                'attributes' => '',

                /* Universalattribute zur Internationalisierung */
                'dir' => 'ltr',
                'lang' => null,

                /* Universalattribute fuer Event-Handler */
                'onclick' => '',
                'ondblclick' => '',
                'onmousedown' => '',
                'onmouseup' => '',
                'onmouseover' => '',
                'onmousemove' => '',
                'onmouseout' => '',
                'onkeypress' => '',
                'onkeydown' => '',
                'onkeyup' => '',

                'guierror' => null
            ]
        );

        parent::init($superglobals);
    }

    /**
     * main logic
     */
    protected function prepare()
    {
        #### Bindet gui_....css ein:
        if($this->Weblication->hasFrame()) {
            $cssFile = @$this->Weblication->findStyleSheet($this->getClassName().'.css', $this->getClassName(), true);
            $this->Weblication->getFrame()->getHeadData()->addStyleSheet($cssFile);
        }

        $this->id = $this->Input->getVar('id');


        $class = $this->Input->getVar('class');
        $class_error = $this->Input->getVar('class_error');
        $guierror = $this->Input->getVar('guierror');
        if($guierror and $guierror == $this->Input->getVar('name')) {
            $class = $class_error;
        }
        #### Events
        $events = '';
        $onclick = $this->Input->getVar('onclick');
        if($onclick) {
            $events .= 'onclick="'.$onclick.'"';
        }
        $ondblclick = $this->Input->getVar('ondblclick');
        if($ondblclick) {
            $events .= ' ';
            $events .= 'ondblclick="'.$ondblclick.'"';
        }
        $onmousedown = $this->Input->getVar('onmousedown');
        if($onmousedown) {
            $events .= ' ';
            $events .= 'onmousedown="'.$onmousedown.'"';
        }
        $onmouseup = $this->Input->getVar('onmouseup');
        if($onmouseup) {
            $events .= ' ';
            $events .= 'onmouseup="'.$onmouseup.'"';
        }
        $onmouseover = $this->Input->getVar('onmouseover');
        if($onmouseover) {
            $events .= ' ';
            $events .= 'onmouseover="'.$onmouseover.'"';
        }
        $onmousemove = $this->Input->getVar('onmousemove');
        if($onmousemove) {
            $events .= ' ';
            $events .= 'onmousemove="'.$onmousemove.'"';
        }
        $onmouseout = $this->Input->getVar('onmouseout');
        if($onmouseout) {
            $events .= ' ';
            $events .= 'onmouseout="'.$onmouseout.'"';
        }
        $onkeypress = $this->Input->getVar('onkeypress');
        if($onkeypress) {
            $events .= ' ';
            $events .= 'onkeypress="'.$onkeypress.'"';
        }
        $onkeydown = $this->Input->getVar('onkeydown');
        if($onkeydown) {
            $events .= ' ';
            $events .= 'onkeydown="'.$onkeydown.'"';
        }
        $onkeyup = $this->Input->getVar('onkeyup');
        if($onkeyup) {
            $events .= ' ';
            $events .= 'onkeyup="'.$onkeyup.'"';
        }

        $this->events .= $events;

        #### Universal Attribute
        $attributes = '';
        $title = $this->Input->getVar('title');
        if($title) {
            $attributes .= 'title="'.$title.'"';
        }
        $style = $this->Input->getVar('style');
        if($style) {
            $attributes .= ' ';
            $attributes .= 'style="'.$style.'"';
        }
        if($class) {
            $attributes .= ' ';
            $attributes .= 'class="'.$class.'"';
        }
        if($class_error) {
            $attributes .= ' ';
            $attributes .= 'class_error="'.$class_error.'"';
        }
        #### Universal Attribute f�r Internationalisierung
        $lang = $this->Input->getVar('lang');
        if($lang) {
            $attributes .= ' ';
            $attributes .= 'lang="'.$lang.'"';
        }
        $dir = $this->Input->getVar('dir');
        if($dir) {
            $attributes .= ' ';
            $attributes .= 'dir="'.$dir.'"';
        }
        $custom_attributes = $this->Input->getVar('attributes');
        if($custom_attributes) {
            $attributes .= ' ';
            $attributes .= $custom_attributes;
        }

        $this->attributes .= $attributes;
    }
}