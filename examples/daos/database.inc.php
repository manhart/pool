<?php
	/**
	* database.inc.php
	* 
	* Datenbank- und Tabellendefinitionen fuer Data Access Objects. 
	* 
	* @version $Id: database.inc.php,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
	* @version $Revision 1.0$
	* @version
	* 
	* @since 2003-08-25
	* @author Alexander Manhart <alexander@manhart.bayern>
	* @link https://alexander-manhart.de
	*/
	
	#### global databases:
	define('DB_TESTING', 'testing');

	#### Datainterface Types:
	define('DATAINTERFACE_MYSQL', 'MySQL_Interface');
	define('DATAINTERFACE_MYSQLI', 'MySQLi_Interface');
	define('DATAINTERFACE_POSTGRESQL', 'PostgreSQL_Interface');
	define('DATAINTERFACE_CISAM', 'CISAM_Interface');
	define('DATAINTERFACE_C16', 'C16_Interface');

	/* ===== */
	/* MySQL */
	/* ===== */
	$Testing_User 	= array(DATAINTERFACE_MYSQLI, DB_TESTING, 'User');

	define('DBACCESSFILE', DIR_POOL_ROOT. '/configs/access.inc.php');