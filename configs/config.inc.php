<?php
/**
* config.inc.php
*
* Diese Datei konfiguriert die Hauptoptionen unserer Projekte.
*
* @date $Date: 2004/09/21 07:49:30 $
* @version $Id: config.inc.php,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
* @version $Revision 1.0$
*
* @since 2004/01/19
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
**/
/* ###########################################################################################################################
* Die Datei stellt folgende Konstanten zur Verfuegung:
*
* DIR_DOCUMENT_ROOT (string) = (absoluter pfad) zeigt auf DOCUMENT_ROOT des Apache Webservers
* DIR_RELATIVE_DOCUMENT_ROOT (string) = (relativer Pfad) zeigt auf DOCUMENT_ROOT des Apache Webservers
*
* DIR_LIB_ROOT (string) = (absoluter) Pfad zu den Hauptbibliotheken (POOL, DAOS, SUBCODES)
* DIR_RELATIVE_LIB_ROOT (string) = (relativer) Pfad zu den Hauptbibliotheken (POOL, DAOS, SUBCODES)
*
* DIR_DATA_DIR (string) = (absoluter pfad) zeigt auf das DATA Verzeichnis
*
* DIR_DAOS_ROOT (string) = (absoluter) Pfad zeigt direkt auf das DAOS Verzeichnis
* DIR_RELATIVE_DAOS_ROOT (string) = (relativer) Pfad zeigt direkt auf das DAOS Verzeichnis
*
* DIR_BASELIB_ROOT (string) = (absoluter) Pfad zeigt direkt auf den POOL
* DIR_RELATIVE_BASELIB_ROOT (string) = (relativer) Pfad zeigt direkt auf den POOL
*
* IS_TESTSERVER (boolean) = gibt an, ob es sich einen Testrechner handelt.
*
* ############################################################################################################################
*/
$loc = setlocale(LC_TIME, 'de_DE.UTF-8'); // charset must be installed on system!
$loc = setlocale(LC_COLLATE, 'de_DE.UTF-8'); // charset must be installed on system!
$loc = setlocale(LC_CTYPE, 'de_DE.UTF-8'); // charset must be installed on system!

/* check Servername und stelle die Weichen */
switch($_SERVER['SERVER_NAME']) {
    case 'develop.localhost':
    # VM develop.manhart.xx
    case 'develop.manhart.xx':
        define('DIR_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        define('DIR_RELATIVE_DOCUMENT_ROOT', '/');
        define('MYSQL_HOST', 'localhost');
        define('IS_TESTSERVER', true);
        break;

    # VM prod.manhart.xx
    case 'prod.manhart.xx':
        define('DIR_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        define('DIR_RELATIVE_DOCUMENT_ROOT', '/');
        define('MYSQL_HOST', 'localhost');
        define('IS_TESTSERVER', false);
        break;

    default:
        die ('Unknown server "' . $_SERVER['SERVER_NAME'] . '"! Please update configs.');
}

define('JAVA_PATH', '/usr/bin/java');
// define('FOP_PATH', '/opt/fop/current/fop');

// verwendet in der App
define('DIR_POOL_ROOT', DIR_DOCUMENT_ROOT.'/pool/src');
// aus der App Sicht (für js from pool):
define('DIR_POOL_ROOT_REL', '../../src'); // for webprojects it would be better to symlink javascripts folder

// This constant points to the root directory of the configuration files, which is the directory I am currently in.
// The directory where the "config.inc.php" is located is considered as the DIR_CONFIGS_ROOT.
define('DIR_CONFIGS_ROOT', __DIR__);

// This constant points to the common directory, where global code, e.g. company-specific GUI modules are located.
define('DIR_COMMON_ROOT', DIR_DOCUMENT_ROOT . '/pool/examples/common');
define('DIR_COMMON_ROOT_REL', '../common');

// falls benoetigt, anpassen:
//define('DIR_DATA_ROOT', DIR_DOCUMENT_ROOT . 'data');
//define('DIR_RELATIVE_DATA_ROOT', DIR_RELATIVE_DOCUMENT_ROOT . 'data');
//define('DIR_PROJECT_TO_DATA_ROOT', '../../data');

define('DIR_DAOS_ROOT', DIR_DOCUMENT_ROOT.'/pool/examples/daos');
// define('DIR_RELATIVE_DAOS_ROOT', DIR_RELATIVE_LIB_ROOT . '/examples/daos'); wird nie benötigt

define('DIR_SUBCODES_ROOT', DIR_DOCUMENT_ROOT.'/pool/examples/subcodes');
//	define('DIR_RELATIVE_SUBCODES_ROOT', DIR_RELATIVE_LIB_ROOT . '/subcodes');

//define('DIR_BASELIB_ROOT', DIR_LIB_ROOT);
//define('DIR_RELATIVE_BASELIB_ROOT', DIR_RELATIVE_LIB_ROOT);

//define('DIR_PUBLIC_ROOT', DIR_DOCUMENT_ROOT . 'public/');
//define('DIR_RELATIVE_PUBLIC_ROOT', DIR_RELATIVE_DOCUMENT_ROOT . 'public/');

define('DIR_RELATIVE_3RDPARTY_ROOT', '../3rdParty');