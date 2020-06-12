<?php
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

/**
 * Class GUI_Universal
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: GUI_Universal.class.php,v 1.5 2007/02/27 10:36:31 hoesl Exp $
 * @access public
 **/
class GUI_Universal extends GUI_Module
{
    /**
     * @var string ID (unique identifier)
     */
    protected string $id = '';

    var $attributes = '';

    var $events = '';

    /**
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
                /* Allgemeine Universalattribute */
                'id' 			=> $this -> getName(),
                'title'			=> '',
                'style'			=> null,
                'class'			=> $this -> getClassName(),
                'class_error'	=> $this -> getClassName() . '_error',
                'attributes'	=> '',

                /* Universalattribute zur Internationalisierung */
                'dir'			=> 'ltr',
                'lang'			=> null,

                /* Universalattribute fuer Event-Handler */
                'onclick'		=> '',
                'ondblclick'	=> '',
                'onmousedown'	=> '',
                'onmouseup'		=> '',
                'onmouseover'	=> '',
                'onmousemove'	=> '',
                'onmouseout'	=> '',
                'onkeypress'	=> '',
                'onkeydown'		=> '',
                'onkeyup'		=> '',

                'guierror' 		=> null
            )
        );

        parent::init($superglobals);
    }

    /**
     * GUI_Universal::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        #### Bindet gui_....css ein:
        $cssfile = @$this->Weblication->findStyleSheet($this->getClassName() . '.css', $this->getClassName(), true);
        if ($cssfile) {
            /*if(version_compare(phpversion(), '5.0.0', '>=')) {
                if ($this->Weblication->Main instanceof GUI_Module) {
                    if (isset($this -> Weblication -> Main -> Headerdata) and is_a($this -> Weblication -> Main -> Headerdata, 'GUI_Headerdata')) {
                        $this -> Weblication -> Main -> Headerdata -> addStyleSheet($cssfile);
                    }
                }
            }
            else {*/
                if (is_a($this -> Weblication -> Main, 'GUI_Module')) {
                    if (isset($this -> Weblication -> Main -> Headerdata) and is_a($this -> Weblication -> Main -> Headerdata, 'GUI_Headerdata')) {
                        $this -> Weblication -> Main -> Headerdata -> addStyleSheet($cssfile);
                    }
                }
            /*}*/
        }

        $Input = & $this -> Input;

        $this->id = $Input->getVar('id');


        $class = $Input -> getVar('class');
        $class_error = $Input -> getVar('class_error');
        $guierror = $Input->getVar('guierror');
        if ($guierror and $guierror == $this->Input->getVar('name')) {
            $class = $class_error;
        }
        #### Events
        $events = '';
        $onclick = $Input->getVar('onclick');
        if ($onclick) {
            $events .= 'onclick="' . $onclick . '"';
        }
        $ondblclick = $Input -> getVar('ondblclick');
        if ($ondblclick) {
            $events .= ' ';
            $events .= 'ondblclick="' . $ondblclick . '"';
        }
        $onmousedown = $Input->getVar('onmousedown');
        if ($onmousedown) {
            $events .= ' ';
            $events .= 'onmousedown="' . $onmousedown . '"';
        }
        $onmouseup = $Input->getVar('onmouseup');
        if ($onmouseup) {
            $events .= ' ';
            $events .= 'onmouseup="' . $onmouseup . '"';
        }
        $onmouseover = $Input->getVar('onmouseover');
        if ($onmouseover) {
            $events .= ' ';
            $events .= 'onmouseover="' . $onmouseover . '"';
        }
        $onmousemove = $Input->getVar('onmousemove');
        if ($onmousemove) {
            $events .= ' ';
            $events .= 'onmousemove="' . $onmousemove . '"';
        }
        $onmouseout = $Input->getVar('onmouseout');
        if ($onmouseout) {
            $events .= ' ';
            $events .= 'onmouseout="' . $onmouseout . '"';
        }
        $onkeypress = $Input->getVar('onkeypress');
        if ($onkeypress) {
            $events .= ' ';
            $events .= 'onkeypress="' . $onkeypress . '"';
        }
        $onkeydown = $Input->getVar('onkeydown');
        if ($onkeydown) {
            $events .= ' ';
            $events .= 'onkeydown="' . $onkeydown . '"';
        }
        $onkeyup = $Input->getVar('onkeyup');
        if ($onkeyup) {
            $events .= ' ';
            $events .= 'onkeyup="' . $onkeyup . '"';
        }

        $this -> events .= $events;

        #### Universal Attribute
        $attributes = '';
        $title = $Input->getVar('title');
        if ($title) {
            $attributes .= 'title="' . $title . '"';
        }
        $style = $Input->getVar('style');
        if ($style) {
            $attributes .= ' ';
            $attributes .= 'style="' . $style . '"';
        }
        if ($class) {
            $attributes .= ' ';
            $attributes .= 'class="' . $class . '"';
        }
        if ($class_error) {
            $attributes .= ' ';
            $attributes .= 'class_error="' . $class_error . '"';
        }
        #### Universal Attribute f�r Internationalisierung
        $lang = $Input->getVar('lang');
        if ($lang) {
           $attributes .= ' ';
           $attributes .= 'lang="' . $lang .'"';
        }
        $dir = $Input->getVar('dir');
        if ($dir) {
            $attributes .= ' ';
            $attributes .= 'dir="' . $dir . '"';
        }
        $custom_attributes = $Input -> getVar('attributes');
        if ($custom_attributes) {
            $attributes .= ' ';
            $attributes .= $custom_attributes;
        }

        $this -> attributes .= $attributes;
    }
}