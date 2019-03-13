<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_displaynumbers.class.php
 *
 * @version $Id: gui_displaynumbers.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $Revision: 1.1.1.1 $
 * @version
 *
 * @since 2004/06/28
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DisplayNumbers
 *
 * Zeigt Zahlen als Bilder an.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_displaynumbers.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_DisplayNumbers extends GUI_Module
{
    var $returnValue = '';

    /**
     * GUI_DisplayNumbers::GUI_DisplayNumbers()
     *
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner, $AutoLoadFiles=false)
    {
        parent::__construct($Owner, $AutoLoadFiles);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param integer $superglobals Superglobals (siehe Klasse Input)
     **/
    function init($superglobals=0)
    {
        $this -> Defaults -> addVar('value', '');
        $this -> Defaults -> addVar('path', '');
        $this -> Defaults -> addVar('extension', '.gif');
        parent :: init($superglobals);
    }

    /**
     * GUI_DisplayNumbers::prepare()
     *
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $Input = & $this -> Input;

        $buf = '';
        $value = $Input -> getVar('value');
        for ($i=0; $i <= strlen($value)-1; $i++) {
            $number = substr($value, $i, 1);
            $buf .= '<img src="' . addEndingSlash($Input -> getVar('path')) . $number . $Input -> getVar('extension') . '" border="0">';
        }
        $this -> returnValue = $buf;
    }

    /**
     * GUI_DisplayNumbers::finalize()
     *
     * Box Inhalt parsen und zurueck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        return $this -> returnValue;
    }
}