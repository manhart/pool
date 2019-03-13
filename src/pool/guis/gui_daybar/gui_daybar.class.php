<?php
/**
 * -= PHP Object Oriented Library =-
 *
 *
 *
 * @version $Id: gui_daybar.class.php,v 1.7 2007/05/31 14:35:30 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2005/12/20
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DayBar
 *
 * @package pool
 * @author Alexander Manhart <misterelsa@gmx.de>
 * @version $Id: gui_daybar.class.php,v 1.7 2007/05/31 14:35:30 manhart Exp $
 * @access public
 **/
class GUI_DayBar extends GUI_Module
{
    /**
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner, true);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'name'			=> $this -> getClassName(),
                'value'			=> 0,
                'defaultvalue'	=> 0,


                // Events
                'onbeforeclick' => '',
                'onclick'		=> '',
                'pathToImages'	=> 'eingabemaske'
            )
        );
        parent :: init(I_GET);
    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this -> Weblication -> findTemplate('tpl_daybar.html', 'gui_daybar', true);
        $this -> Template -> setFilePath('stdout', $template);

        $jsFile = $this -> Weblication -> findJavaScript('daybar.js', 'gui_daybar', true);
        $frame = $this -> Weblication -> getMain();
        if(is_a($frame, 'gui_customframe')) {
            /* @var $Headerdata GUI_Headerdata */
            $Headerdata = &$frame -> getHeaderdata();
            $Headerdata -> addJavaScript($jsFile);
        }
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $interfaces = & $this -> Weblication -> getInterfaces();
        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;
        $Frame = & $this -> Weblication -> Main;

        #### Bindet gui_....css ein:
        $cssfile = @$this -> Weblication -> findStyleSheet($this -> getClassName() . '.css', $this -> getClassName(), true);
        if ($cssfile) {
            if (is_a($this -> Weblication -> Main, 'GUI_Module')) {
                if (isset($this->Weblication->Main->Headerdata) and is_a($this->Weblication->Main->Headerdata, 'GUI_Headerdata')) {
                    $this->Weblication->Main->Headerdata->addStyleSheet($cssfile);
                }
            }
        }

        $dayBarValue = $Input -> getVar('value');

        $Template -> setVar('defaultValue', $Input -> getVar('defaultvalue'));
        $Template -> setVar('dayBarValue', $dayBarValue);
        $Template -> setVar('name', $this -> getName());
        $Template -> setVar('pathToImages', addEndingSlash($Input -> getVar('pathToImages')));

        $onbeforeclick = $Input -> getVar('onbeforeclick');
        $Template -> setVar('onbeforeclick', $onbeforeclick);

        $onclick = $Input -> getVar('onclick');
        $Template -> setVar('onclick', $onclick);
    }

    /**
     * Inhalt parsen und zur�ck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}