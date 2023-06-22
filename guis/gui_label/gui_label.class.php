<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * Das GUI_Label ist lediglich ein Anzeigefeld fuer Text.
 *
 * @version $Id: gui_label.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-18
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

use pool\classes\Core\Input;

/**
 * GUI_Label
 *
 * Das GUI_Label ist lediglich ein Anzeigefeld fuer Text.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_label.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_Label extends GUI_Module
{
    /**
     * Initialisiert Standardwerte;
     * Verwendet INPUT_GET und INPUT_POST Variablen.
     * INPUT_SESSION nur zum Speichern von abgeschickten Daten.
     *
     * @access public
     **/
    function init(?int $superglobals= Input::EMPTY)
    {
        $this -> Defaults -> addVar('id', $this -> getName());
        $this -> Defaults -> addVar('name', $this -> getName());

        $this -> Defaults -> addVar('caption', '');

        // $this -> Defaults -> addVar('formname', '');

        // $this -> Defaults -> addVar('use.session', 0);
        // $this -> Defaults -> addVar('session.variable', $this -> getName());

        // $this -> Defaults -> addVar('border', 1);
        // $this -> Defaults -> addVar('size', 20);
        // $this -> Defaults -> addVar('enabled', '1');
        // $this -> Defaults -> addVar('value', '');
        // $this -> Defaults -> addVar('defaultvalue', '');
        // $this -> Defaults -> addVar('type', 'text');
        // $this -> Defaults -> addVar('width', null);
        // $this -> Defaults -> addVar('height', 0);
        // $this -> Defaults -> addVar('font-size', '8pt');
        // $this -> Defaults -> addVar('font-family', 'verdana');
        // $this -> Defaults -> addVar('save', '');

        // $this -> Defaults -> addVar('bordercolor', 'black');
        // $this -> Defaults -> addVar('maxlength', 0);
        // $this -> Defaults -> addVar('tabindex', '');

        // $this -> Defaults -> addVar('guierror', '');
        // $this -> Defaults -> addVar('bordercolorerror', '#FF0000');

        // $this -> Defaults -> addVar('convertcode', '');

        // $this -> Defaults -> addVar('onfocus', 'return (false);');
        // $this -> Defaults -> addVar('onkeypress', '');
        // $this -> Defaults -> addVar('onchange', '');


        // $this -> Defaults -> addVar('gap', 0);

        parent::init(Input::GET | Input::POST);
    }

    function loadFiles()
    {
        // $file = $this -> Weblication -> findTemplate('tpl_label.html', 'gui_label', true);
        // $this -> Template -> setFilePath('stdout', $file);
    }

    function prepare ()
    {
        // $Template = & $this -> Template;
        // $Session = & $this -> Session;
        $Input = & $this -> Input;

        $id = $Input -> getVar('id');
        $name = $Input -> getVar('name');
        // $session_variable = $Input -> getVar('session.variable');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> getName() and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> getName() and $name == $this -> getName()) {
            $name = $id;
        }
        $Input -> setVar(array('name' => $name, 'id' => $id));

        // abgleich session variable
        //if ($session_variable == $this -> getName() and $name != $this -> getName()) {
        //	$session_variable = $name;
        //}
    }

    function finalize(): string
    {
//        $this->Template->parse('stdout');
        return $this->Input->getVar('caption'); // $this -> Template -> getContent('stdout');
    }
}