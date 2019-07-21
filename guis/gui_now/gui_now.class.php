<?php
	/**
	 * -= PHP Object Oriented Library (POOL) =-
	 *
	 * gui_now.class.php
	 *
	 * Benoetigt:
	 * DAO (Data Access Objects)
	 *
	 * @version $Id: gui_now.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
	 * @version $revision$
	 * @version
	 *
	 * @since 2004/04/25
	 * @author alexander manhart <alexander.manhart@freenet.de>
	 * @link http://www.misterelsa.de
	 */
	 
	/**
	 * GUI_Now
	 * 
	 * GUI_Now fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
	 * 
	 * @package pool
	 * @author manhart
	 * @version $Id: gui_now.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
	 * @access public
	 **/
	class GUI_Now extends GUI_Label
	{
		/**
		 * GUI_Now::init()
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
			$this -> Defaults -> addVar('format', '');
			
			parent :: init($superglobals);
		}		
		
		/**
		 * GUI_Now::prepare()
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
			
			if ($Input -> getVar('format')) {
			    $Input -> setVar('caption', date($Input -> getVar('format'), time()));
			}
			else {
				$Input -> setVar('caption', time());
			}
			
			
			parent :: prepare();
		}
	}