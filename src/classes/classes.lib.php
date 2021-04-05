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

// base classes
require_once __DIR__.'/PoolObject.class.php';		// necessary
require_once __DIR__.'/Component.class.php';		// necessary
require_once __DIR__.'/Module.class.php';		// necessary
require_once __DIR__.'/GUI_Module.class.php';	// necessary
require_once __DIR__.'/DAO.class.php';			// necessary
require_once __DIR__.'/Resultset.class.php';		// necessary
require_once __DIR__.'/Singleton.class.php';		// necessary
require_once __DIR__.'/Translator.class.php';		// necessary
//require_once__DIR__.'/Kontocheck.class.php');
//require_once__DIR__.'/ActionHandler.class.php');
//require_once__DIR__.'/TreeStructure.class.php');
//require_once__DIR__.'/ProgressBar.class.php');
//require_once__DIR__.'/Log.class.php');
//require_once__DIR__.'/IniFile.class.php');
require_once __DIR__.'/Exception.class.php';

//require_once__DIR__.'/SessionHandler.class.php');

// derived from Object
//require_once__DIR__.'/Template.class.php');			// necessary
require_once __DIR__.'/Input.class.php';			// necessary
//require_once__DIR__.'/Stopwatch.class.php');		// necessary
require_once __DIR__.'/Url.class.php';				// necessary
require_once __DIR__.'/Subcode.class.php';			// necessary
require_once __DIR__.'/SubcodeResult.class.php';	// necessary
//require_once__DIR__.'/Tar.class.php');
//require_once__DIR__.'/ImageMagick.class.php');
//require_once__DIR__.'/RSS.class.php');
//require_once__DIR__.'/DBSession.class.php');		// necessary
require_once __DIR__.'/DataInterface.class.php';
//require_once__DIR__.'/MCrypt.class.php');
//require_once__DIR__.'/PublicHoliday.class.php');

// derived from DataInterface
//require_once__DIR__.'/MySQL_Interface.class.php');		// necessary
require_once __DIR__.'/MySQLi_Interface.class.php';		// necessary
//require_once__DIR__.'/CISAM_Interface.class.php'); // necessary
// require_once__DIR__.'/C16_Interface.class.php');		// necessary

// Third Party Tools
require_once __DIR__.'/htmlMimeMail-2.5.1/mimePart.class.php';
require_once __DIR__.'/htmlMimeMail-2.5.1/HtmlMimeMail.class.php';
require_once __DIR__.'/htmlMimeMail-2.5.1/smtp.class.php';
require_once __DIR__.'/htmlMimeMail-2.5.1/RFC822.class.php';

// derived from Component
//require_once__DIR__.'/Weblication.class.php');

// derived from DAO
require_once __DIR__.'/MySQL_DAO.class.php';			// necessary
// require_once__DIR__.'/CISAM_DAO.class.php');			// necessary
// require_once__DIR__.'/C16_DAO.class.php');				// necessary

// derived from Resultset
//require_once__DIR__.'/MySQL_Resultset.class.php');	// necessary
// require_once__DIR__.'/CISAM_Resultset.class.php');	// necessary
// require_once__DIR__.'/C16_Resultset.class.php');	// necessary

// derived from Module

// derived from GUI_Module
require_once __DIR__.'/GUI_Universal.class.php';
require_once __DIR__.'/GUI_InputElement.class.php';

require_once __DIR__.'/Net_Ping.class.php';