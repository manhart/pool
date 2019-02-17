<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* gui_shadowimage.class.php
*
* Class GUI_Shadowimage erzeugt einen schwarzen Rahmen mit Schatten.
* Das ganze funktioniert mit ein paar Bildern fuer Rahmenlinien und
* Schatten in einer HTML Tabelle. Die Tabelle struelpt sich um das
* anzuzeigende Bild.
*
* $Log: gui_shadowimage.class.php,v $
* Revision 1.1.1.1  2004/09/21 07:49:32  manhart
* initial import
*
* Revision 1.1  2004/08/05 06:32:26  manhart
* Initial Import
*
*
* @version $Id: gui_shadowimage.class.php,v 1.1.1.1 2004/09/21 07:49:32 manhart Exp $
* @version $Revision: 1.1.1.1 $
*
* @since 2004/08/04
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
*/

/**
 * GUI_Shadowimage
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_shadowimage.class.php,v 1.1.1.1 2004/09/21 07:49:32 manhart Exp $
 * @access public
 **/
class GUI_Shadowimage extends GUI_Module
{
    /**
     * GUI_Shadowimage::GUI_Shadowimage()
     *
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function GUI_Shadowimage(& $Owner)
    {
        parent :: GUI_Module($Owner);
    }

    /**
     * GUI_Shadowimage::init()
     *
     * Default Werte setzen. Input initialisieren.
     *
     * Parameter:
     * - title Titel fuer das Bild (wird beim Ueberfahren mit der Maus angezeigt)
     * - href Link beim Klicken des Bildes
     * - target Zielfenster beim Klicken des Bildes
     * - src Anzuzeigendes Bild
     * - onmouseover Clientseitige Eventsteuerung von onmouseover ueber Javascript
     * - onmouseout Clientseitige Eventsteuerung von onmouseout ueber Javascript
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('title', '');
        $this -> Defaults -> addVar('href', '');
        $this -> Defaults -> addVar('src', '');
        $this -> Defaults -> addVar('target', '_self');
        $this -> Defaults -> addVar('onmouseover', '');
        $this -> Defaults -> addVar('onmouseout', '');

        parent::init($superglobals);
    }

    /**
     * GUI_Shadowimage::loadFiles()
     *
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this -> Weblication -> findTemplate('tpl_shadowimage.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $template);
    }

    /**
     * GUI_Shadowimage::prepare()
     *
     * Template vorbereiten (fuellen der Platzhalter)
     *
     * @access public
     **/
    function prepare()
    {
        $Template = & $this -> Template;
        $Input = & $this -> Input;

        $Template -> setVar(
            array(
                'title' => $Input -> getVar('title'),
                'src' => $Input -> getVar('src'),
                'href' => $Input -> getVar('href'),
                'target' => $Input -> getVar('target'),
                'onmouseover' => $Input -> getVar('onmouseover'),
                'onmouseout' => $Input -> getVar('onmouseout'),
            )
        );
    }

    /**
     * Inhalt parsen und zurueck geben.
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