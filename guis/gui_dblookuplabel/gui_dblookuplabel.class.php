<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dblookuplabel.class.php
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_dblookuplabel.class.php 38772 2019-09-30 09:31:12Z manhart $
 * @version $revision$
 * @version
 *
 * @since 2004-02-18
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBLookupLabel
 *
 * GUI_DBLookupLabel fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dblookuplabel.class.php 38772 2019-09-30 09:31:12Z manhart $
 * @access public
 **/
class GUI_DBLookupLabel extends GUI_Label
{
    /**
     * GUI_DBLookupLabel::init()
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
    function init(?int $superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('tabledefine', '');
        $this -> Defaults -> addVar('autoload_fields', 1);
        $this -> Defaults -> addVar('datafield', '');
        $this -> Defaults -> addVar('keyfield', '');
        $this -> Defaults -> addVar('keyvalue', '');

        parent :: init($superglobals);
    }

    /**
     * GUI_DBLookupLabel::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Edit aufgerufen und setzt die Werte fuer das Eingabefeld.
     *
     * @access public
     **/
    function prepare ()
    {
        $interfaces = $this -> Weblication -> getInterfaces();
        $Input = & $this -> Input;


        if ($Input -> getVar('keyvalue') != '') {
            if ($Input -> getVar('tabledefine')) {
                $DAO = DAO::createDAO($interfaces, $Input -> getVar('tabledefine'));
                $Resultset = & $DAO -> get($Input -> getVar('keyvalue'), $Input -> getVar('keyfield'));
                if ($Resultset -> count() == 1) {
                    $datavalue = $Resultset -> getValue($Input -> getVar('datafield'));
                    $Input -> setVar('caption', $datavalue);
                }
            }
            else {
                // wichtige angabe fehlt
            }
        }

        parent :: prepare();
    }
}