<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dbtextarea.class.php
 *
 * Das GUI DBTextarea ist ein Datenbank Control. Es steuert ein mehrzeiliges Eingabefeld (extends GUI_Textarea).
 * Uebergibt man die Parameter "tabledefine" und "id" korrekt, fuellt sich GUI_DBTextarea selbst aus der Datenbank.
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_dbtextarea.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-03-16
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBTextarea
 *
 * GUI_DBTextarea fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dbtextarea.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_DBTextarea extends GUI_Textarea
{
    /**
     * GUI_DBTextarea::init()
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
     * GUI_DBTextarea::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Textarea aufgerufen und setzt die Werte fuer das Eingabefeld.
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
            $Input -> setVar('value', $resultlist[0][$name]);
        }
        else {
            // $result -> getErrorList();
        }

        parent :: prepare();
    }
}