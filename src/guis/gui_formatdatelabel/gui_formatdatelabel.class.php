<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_formatdatelabel.class.php
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_formatdatelabel.class.php 38772 2019-09-30 09:31:12Z manhart $
 * @version $revision$
 * @version
 *
 * @since 2004/04/25
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 */

/**
 * GUI_FormatDateLabel fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_formatdatelabel.class.php 38772 2019-09-30 09:31:12Z manhart $
 * @access public
 **/
class GUI_FormatDateLabel extends GUI_Label
{
    /**
     * GUI_FormatDateLabel::init()
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
        $this -> Defaults -> addVar('format', 'd.m.Y H:i:s');
        $this -> Defaults -> addVar('timestamp', time());

        parent :: init($superglobals);
    }

    /**
     * GUI_FormatDateLabel::prepare()
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


        $Input -> setVar('caption', date($Input -> getVar('format'), $Input -> getVar('timestamp')));

        parent :: prepare();
    }
}