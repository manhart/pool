<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dblabel.class.php
 *
 * Das GUI DBLabel ist ein Datenbank Control. Es steuert ein Anzeigefeld (extends GUI_Label).
 * Uebergibt man die Parameter "tabledefine" und "id" korrekt, bezieht GUI_DBLabel die Daten selber aus der Datenbank.
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_dblabel.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-18
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBLabel fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dblabel.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_DBLabel extends GUI_Label
{
    /**
     * GUI_DBLabel::init()
     *
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

        parent :: init($superglobals);
    }

    /**
     * GUI_DBLabel::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Edit aufgerufen und setzt die Werte fuer das Eingabefeld.
     *
     * @access public
     **/
    function prepare ()
    {
        $Input = & $this -> Input;

        $Subcode = Subcode::createSubcode('DataRecordSubcode', $this -> Owner);
        $Subcode -> import($Input);
        $SubcodeResult = & $Subcode -> execute();
        if ($SubcodeResult -> isOk()) {
            $resultlist = $SubcodeResult -> getResultList();
            $name = $Input -> getVar('name');
            $Input -> setVar('caption', $resultlist[0][$name]);
        }
        else {
        }

        parent :: prepare();
    }
}