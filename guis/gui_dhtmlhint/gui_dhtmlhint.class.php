<?php
/**
 * -= PHP Object Oriented Library =-
 *
 * gui_dhtmlhint.class.php
 *
 * Vorlage zum Erstellen neuer GUIs.
 *
 * @version $Id: gui_dhtmlhint.class.php,v 1.2 2005/06/03 07:01:10 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-11-24
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DHtmlHint
 *
 * @package pool
 * @author Alexander Manhart <misterelsa@gmx.de>
 * @version $Id: gui_dhtmlhint.class.php,v 1.2 2005/06/03 07:01:10 manhart Exp $
 * @access public
 **/
class GUI_DHtmlHint extends GUI_Module
{
    /**
     * GUI_DHtmlHint::init()
     *
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     **/
    function init(?int $superglobals=I_EMPTY)
    {
        parent::init($superglobals);
    }

    /**
     * GUI_DHtmlHint::loadFiles()
     *
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this -> Weblication -> findTemplate('tpl_dhtmlhint.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $template);
    }

    /**
     * GUI_DHtmlHint::prepare()
     *
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
        /* @var $Frame GUI_CustomFrame */
        $Frame = &$this -> Weblication -> getFrame();

        $jsfile = $this -> Weblication -> findJavaScript('dhtmlhint.js', $this -> getClassName(), true);
        if (is_a($Frame, 'GUI_CustomFrame') and is_a($Frame -> getHeaderdata(), 'GUI_Headerdata')) {
            $Headerdata = &$Frame -> getHeaderdata();
            $Headerdata -> addJavaScript($jsfile);
        }

        if (is_a($Frame, 'GUI_CustomFrame')) {
            $Frame -> addBodyMousemove('MousePosition.detect(event)');
            $Frame -> addBodyMousemove('DHtmlHintObject.doMouseMove(event)');
        }
    }

    /**
     * GUI_DHtmlHint::finalize()
     *
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