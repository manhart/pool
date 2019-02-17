<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_record.class.php
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_record.class.php,v 1.1 2006/03/23 15:47:14 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Record
 *
 * GUI_Record fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_record.class.php,v 1.1 2006/03/23 15:47:14 manhart Exp $
 * @access public
 **/
class GUI_Record extends GUI_Module
{
    var $output='';

    /**
     * GUI_Record::init()
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
        $this -> Defaults -> addVar('tabledefine', 'sinnic_tbl_firma');
        $this -> Defaults -> addVar('id', 0); 	// separated by ;
        $this -> Defaults -> addVar('key', ''); 	// separated by ;
        $this -> Defaults -> addVar('autoload_fields', 1);
        $this -> Defaults -> addVar('pk', ''); 		// separated by ;
        $this -> Defaults -> addVar('header', 1);
        // $this -> Defaults -> addVar('columns', ''); // separated by ;

        parent :: init($superglobals);
    }

    /**
     * GUI_Record::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Edit aufgerufen und setzt die Werte fuer das Eingabefeld.
     *
     * @access public
     **/
    function prepare ()
    {
        $Input = & $this -> Input;

        $output = '';
        $Subcode = Subcode::createSubcode('DataRecordSubcode', $this -> Owner);
        $Subcode -> import($Input);
        $SubcodeResult = & $Subcode -> execute();
        if ($SubcodeResult -> isOk()) {
            $resultlist = $SubcodeResult -> getResultList();

            foreach($resultlist as $record) {
                if($output == '' and $Input -> getVar('header')==1)
                    $output = implode(';', array_keys($record)) . "\n";
                $output .= implode(';', array_values($record));
            }
            //$Input -> setVar('value', $resultlist[0][$name]);
        }
        else {
            // $result -> getErrorList();
        }
        $this -> output = $output;

        parent :: prepare();
    }

    function finalize()
    {
        return $this -> output;
    }
}