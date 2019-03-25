<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * GUI_ProtectEMail.class.php
 *
 * @version $Id: gui_protectemail.class.php,v 1.1 2004/09/27 06:27:58 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_ProtectEMail
 *
 * Edit steuert ein Eingabefeld (<input type=text>).
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_protectemail.class.php,v 1.1 2004/09/27 06:27:58 manhart Exp $
 * @access public
 **/
class GUI_ProtectEMail extends GUI_Module
{
    var $jsEMailLink='';

    function __construct(&$Owner)
    {
        parent::__construct($Owner, false);
    }

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
                'email'			=> '',
                'caption'		=> 'test'
            )
        );

        parent::init($superglobals);
    }

/*		function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_protectemail.html', 'gui_protectemail', true);
        $this -> Template -> setFilePath('stdout', $file);
    }
*/
    /**
     * GUI_ProtectEMail::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        $Input = & $this -> Input;

        $email = $Input -> getVar('email');
        $caption = $Input -> getVar('caption');

        $this -> jsEMailLink = getJSEMailLink($email, $caption);
    }

    function finalize()
    {
        return $this -> jsEMailLink;
    }
}