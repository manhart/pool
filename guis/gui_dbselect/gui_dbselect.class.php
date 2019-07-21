<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dbselect.class.php
 *
 * Das GUI DBDBSelect ist ein Datenbank Steuerelement. Es steuert ein ComboBox (DropDown) sowie eine Multiselect Box.
 *
 * @version $Id: gui_dbselect.class.php,v 1.4 2007/05/08 08:46:48 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-12
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBSelect
 *
 * DBSelect steuert eine Dropdown- und Multiselect Box.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dbselect.class.php,v 1.4 2007/05/08 08:46:48 manhart Exp $
 * @access public
 **/
class GUI_DBSelect extends GUI_Select
{
    /**
     * Constructor
     *
     * @param object $Owner Klasse vom Typ Component (Besitzer/Owner)
     * @see Component
     * @access public
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
    }

    /**
     * Initialisiert Standardwerte:
     *
     * tabledefine 		= ''	Tabellendefinition (siehe database.inc.php)
     * id				= 0		IDs (bei zusammengesetzten Primaerschluessel werden die IDs mit ; getrennt)
     * key				= ''	Keys (bei zusammengesetzten Primaerschluessel werden die Keys mit ; getrennt)
     * autoload_fields 	= 1		1 laedt automatisch alle Felder, 0 nicht
     * pk				= ''	Primaerschluessel (mehrere Spaltennamen werden mit ; getrennt)
     * columns			= ''	Auszulesende Spalten (Spaltennamen werden mit ; getrennt)
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('tabledefine', '');
        $this -> Defaults -> addVar('id', 0); 	// separated by ;
        $this -> Defaults -> addVar('key', ''); 	// separated by ;
        $this -> Defaults -> addVar('autoload_fields', 1);
        $this -> Defaults -> addVar('pk', ''); 		// separated by ;
        $this -> Defaults -> addVar('columns', ''); // separated by ;
        $this -> Defaults -> addVar('field', '');

        parent :: init($superglobals);
    }

    /**
     * GUI_DBSelect::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Edit aufgerufen und setzt die Werte fuer das Eingabefeld.
     *
     * @access public
     **/
    function prepare ()
    {
        $interfaces = & $this -> Weblication -> getInterfaces();
        $Input = & $this -> Input;
        $Subcode = Subcode::createSubcode('DataRecordSubcode', $this -> Owner);
        $Subcode -> import($Input);
        $SubcodeResult = $Subcode->execute();
        if ($SubcodeResult -> isOk()) {
            $resultlist = $SubcodeResult -> getResultList();
            $name = $Input -> getVar('name');
            if ($Input -> getVar('field') != '') {
                $name = $Input -> getVar('field');
            }
            if(isset($resultlist[0])) $Input->setVar('selected', $resultlist[0][$name]);
        }
        else {
            // $result -> getErrorList();
        }

        parent :: prepare();
    }
}