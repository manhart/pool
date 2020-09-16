<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * classes.lib.php
 *
 * Include File fuer alle Basisklassen.
 *
 * @version $Id: classes.lib.php,v 1.14 2007/07/27 07:30:38 manhart Exp $
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if (!defined('PWD_TILL_CLASSES')) {
    define('PWD_TILL_CLASSES', '.');
}

// base classes
require_once(PWD_TILL_CLASSES.'/PoolObject.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/Component.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/Module.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/GUI_Module.class.php');	// necessary
require_once(PWD_TILL_CLASSES.'/DAO.class.php');			// necessary
require_once(PWD_TILL_CLASSES.'/Resultset.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/Singleton.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/Translator.class.php');		// necessary
//require_once(PWD_TILL_CLASSES.'/Kontocheck.class.php');
//require_once(PWD_TILL_CLASSES.'/ActionHandler.class.php');
//require_once(PWD_TILL_CLASSES.'/TreeStructure.class.php');
//require_once(PWD_TILL_CLASSES.'/ProgressBar.class.php');
//require_once(PWD_TILL_CLASSES.'/Log.class.php');
//require_once(PWD_TILL_CLASSES.'/IniFile.class.php');
require_once(PWD_TILL_CLASSES.'/Exception.class.php');

//require_once(PWD_TILL_CLASSES.'/SessionHandler.class.php');

// derived from Object
//require_once(PWD_TILL_CLASSES.'/Template.class.php');			// necessary
require_once(PWD_TILL_CLASSES.'/Input.class.php');			// necessary
//require_once(PWD_TILL_CLASSES.'/Stopwatch.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/Url.class.php');				// necessary
require_once(PWD_TILL_CLASSES.'/Subcode.class.php');			// necessary
require_once(PWD_TILL_CLASSES.'/SubcodeResult.class.php');	// necessary
//require_once(PWD_TILL_CLASSES.'/Tar.class.php');
//require_once(PWD_TILL_CLASSES.'/ImageMagick.class.php');
//require_once(PWD_TILL_CLASSES.'/RSS.class.php');
//require_once(PWD_TILL_CLASSES.'/DBSession.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/DataInterface.class.php');
//require_once(PWD_TILL_CLASSES.'/MCrypt.class.php');
//require_once(PWD_TILL_CLASSES.'/PublicHoliday.class.php');

// derived from DataInterface
//require_once(PWD_TILL_CLASSES.'/MySQL_Interface.class.php');		// necessary
require_once(PWD_TILL_CLASSES.'/MySQLi_Interface.class.php');		// necessary
//require_once(PWD_TILL_CLASSES.'/CISAM_Interface.class.php'); // necessary
// require_once(PWD_TILL_CLASSES.'/C16_Interface.class.php');		// necessary

// Third Party Tools
require_once(PWD_TILL_CLASSES.'/htmlMimeMail-2.5.1/mimePart.class.php');
require_once(PWD_TILL_CLASSES.'/htmlMimeMail-2.5.1/HtmlMimeMail.class.php');
require_once(PWD_TILL_CLASSES.'/htmlMimeMail-2.5.1/smtp.class.php');
require_once(PWD_TILL_CLASSES.'/htmlMimeMail-2.5.1/RFC822.class.php');

// derived from Component
//require_once(PWD_TILL_CLASSES.'/Weblication.class.php');

// derived from DAO
require_once(PWD_TILL_CLASSES.'/MySQL_DAO.class.php');			// necessary
// require_once(PWD_TILL_CLASSES.'/CISAM_DAO.class.php');			// necessary
// require_once(PWD_TILL_CLASSES.'/C16_DAO.class.php');				// necessary

// derived from Resultset
//require_once(PWD_TILL_CLASSES.'/MySQL_Resultset.class.php');	// necessary
// require_once(PWD_TILL_CLASSES.'/CISAM_Resultset.class.php');	// necessary
// require_once(PWD_TILL_CLASSES.'/C16_Resultset.class.php');	// necessary

// derived from Module

// derived from GUI_Module
require_once(PWD_TILL_CLASSES.'/GUI_Universal.class.php');
require_once(PWD_TILL_CLASSES.'/GUI_InputElement.class.php');

// Admin Tools (derived from Object)
// require_once(PWD_TILL_CLASSES.'/Worms.class.php');

// Java
//require_once(PWD_TILL_CLASSES.'/ExecFOP.class.php');

// CUPS
//require_once(PWD_TILL_CLASSES.'/CupsPrinter.class.php');

// LPC
// require_once(PWD_TILL_CLASSES.'/LinePrinterControl.class.php');

require_once(PWD_TILL_CLASSES.'/Net_Ping.class.php');

//	require_once(PWD_TILL_CLASSES.'/xml/XML_Parser.class.php');
//	require_once(PWD_TILL_CLASSES.'/xml/XML_Tree.class.php');
//	require_once(PWD_TILL_CLASSES.'/xml/XML_TreeNode.class.php');