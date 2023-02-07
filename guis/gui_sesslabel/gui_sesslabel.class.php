<?php
	/**
	 * -= PHP Object Oriented Library (POOL) =-
	 *
	 * gui_sesslabel.class.php
	 *
	 * Das GUI SessLabel ist ein Datenbank Control. Es steuert ein Anzeigefeld (extends GUI_Label).
	 * Uebergibt man die Parameter "tabledefine" und "id" korrekt, bezieht GUI_SessLabel die Daten selber aus der Datenbank.
	 *
	 * Benoetigt:
	 * DAO (Data Access Objects)
	 *
	 * @version $Id: gui_sesslabel.class.php 38772 2019-09-30 09:31:12Z manhart $
	 * @version $revision 1.0$
	 * @version
	 *
	 * @since 2004-02-18
	 * @author alexander manhart <alexander.manhart@freenet.de>
	 * @link http://www.misterelsa.de
	 */

	/**
	 * GUI_SessLabel
	 *
	 * GUI_SessLabel fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
	 *
	 * @package pool
	 * @author manhart
	 * @version $Id: gui_sesslabel.class.php 38772 2019-09-30 09:31:12Z manhart $
	 * @access public
	 **/
	class GUI_SessLabel extends GUI_Label
	{
		/**
		 * GUI_SessLabel::init()
		 *
		 * Initialisiert Standardwerte:
		 *
		 * sess_key 		= ''	Name der Session Variable
		 *
		 * @access public
		 **/
		function init(?int $superglobals= Input::INPUT_EMPTY)
		{
			$this -> Defaults -> addVar('sess_key', '');
			$this -> Defaults -> addVar('array_key', '');
			$this -> Defaults -> addVar('defaultvalue', '');
			$this -> Defaults -> addVar('prefix', '');
			$this -> Defaults -> addVar('suffix', '');

			parent :: init($superglobals);
		}

		/**
		 * GUI_SessLabel::prepare()
		 *
		 * @access public
		 **/
		function prepare ()
		{
			$Input = & $this -> Input;

			$Session = & $this -> Session;

			$sess_key = $Input -> getVar('sess_key');
			$sess_var = $Session -> getVar($sess_key);
			// echo pray($Session -> Vars);
			// echo $sess_key . ' vs ' . $sess_var . '<br>';

			if (!is_array($sess_var)) {
				$caption = $sess_var;
			}
			else {
				$caption = $sess_var[$Input -> getVar('array_key')];
			}

			if (empty($caption)) {
			    $caption = $Input -> getVar('defaultvalue');
			}

			if (!empty($caption)) {
				$caption = $Input -> getVar('prefix') . $caption . $Input -> getVar('suffix');
			}

			$Input -> setVar('caption', $caption);

			parent :: prepare();
		}
	}
?>