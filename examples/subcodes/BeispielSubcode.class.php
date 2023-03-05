<?php
	/**
	* -= PHP Object Oriented Library =-
	* 
	* BeispielSubcode.class.php
	* 
	* @date $Date: 2004/09/21 07:49:32 $
	* @version $Id: BeispielSubcode.class.php,v 1.1.1.1 2004/09/21 07:49:32 manhart Exp $
	* @version $Revision 1.0$
	* @version
	* 
	* @since 2003-07-10
	* @author Alexander Manhart <alexander.manhart@freenet.de> 
	* @link http://www.misterelsa.de
	*/
	
	/**
	 * BeispielSubcode
	 * 
	 * @package subcodes
	 * @author manhart
	 * @copyright Copyright (c) 2003
	 * @version $Id: BeispielSubcode.class.php,v 1.1.1.1 2004/09/21 07:49:32 manhart Exp $
	 * @access public
	 **/
	class BeispielSubcode extends Subcode
	{
		/**
		 * BeispielSubcode::init()
		 * 
		 * Default Werte setzen. Input initialisieren.
		 * 
		 **/
		function init()
		{
			$this -> Defaults -> addVar('daten', 'leer');
			parent :: init(INPUT_EMPTY);
		}
		
		/**
		 * BeispielSubcode::execute()
		 * 
		 * Subcode ausfuehren.
		 * 
		 * @return object SubcodeResult
		 **/
		function execute()
		{
			$SubcodeResult = & $this -> SubcodeResult;
			$Input = & $this -> Input;
			
			if ($Input -> getVar('daten') == 'sepp') {
			    $SubcodeResult -> addError('mist gebaut');
			}
			
			$SubcodeResult -> addResult($Input -> getVar('daten'));
			
			return parent :: execute();
		}
	}
?>