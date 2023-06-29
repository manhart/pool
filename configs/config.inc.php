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

define('POOL_START', microtime(true));

// Default application charset
const APP_CHARSET = 'UTF-8';

// Default internal domain name
const INTERNAL_DOMAIN = 'local';

// check if we are in command line mode
if(!defined('IS_CLI')) {
    define('IS_CLI', php_sapi_name() == 'cli');
}

// Default for old partly deprecated PHP dependent functions.
// Default for system responses (used in conjunction with the extension intl)
// @see https://php.watch/versions/8.0/float-to-string-locale-independent
if(!setLocale(LC_ALL, $locale = 'en_US.UTF-8')) {
    throw new Exception("Server error: the locale $locale is not installed.");
}


/* check Servername und stelle die Weichen */
switch($_SERVER['SERVER_NAME'] ?? gethostname()) {
    case 'g7system':
    case 'g7system.local':
    case 'dev':
    case 'dev.manhart.xx': // VM dev.manhart.xx
        define('DIR_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        define('DIR_RELATIVE_DOCUMENT_ROOT', '/');
        define('MYSQL_HOST', 'localhost');

        define('IS_DEVELOP', true);
        define('IS_STAGING', false);
        define('IS_PRODUCTION', false);
    break;

    case 'stage.manhart.xx': // VM stage.manhart.xx
        define('DIR_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        define('DIR_RELATIVE_DOCUMENT_ROOT', '/');
        define('MYSQL_HOST', 'localhost');

        define('IS_DEVELOP', false);
        define('IS_STAGING', true);
        define('IS_PRODUCTION', false);
        break;

    # VM prod.manhart.xx
    case 'prod.manhart.xx':
        define('DIR_DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        define('DIR_RELATIVE_DOCUMENT_ROOT', '/');
        define('MYSQL_HOST', 'localhost');

        define('IS_DEVELOP', false);
        define('IS_STAGING', false);
        define('IS_PRODUCTION', true);
        break;

    default:
        die ('Unknown server "' . $_SERVER['SERVER_NAME'] . '"! Please update configs.');
}

const IS_TESTSERVER = (IS_DEVELOP || IS_STAGING);
// This constant points to the root directory of the configuration files, which is the directory I am currently in.
// The directory where the "config.inc.php" is located is considered as the DIR_CONFIGS_ROOT.
const DIR_CONFIGS_ROOT = __DIR__;

//const JAVA_PATH = '/usr/bin/java';
// define('FOP_PATH', '/opt/fop/current/fop');

// verwendet in der App
const DIR_POOL_ROOT = DIR_DOCUMENT_ROOT . '/pool';
// aus der App Sicht (für js from pool):
const DIR_POOL_ROOT_REL = '../../'; // for webprojects it would be better to symlink javascripts folder



// This constant points to the common directory, where global code, e.g. company-specific GUI modules are located.
const DIR_COMMON_ROOT = DIR_DOCUMENT_ROOT . '/pool/examples/common';
const DIR_COMMON_ROOT_REL = '../common';

// falls benoetigt, anpassen:
//define('DIR_DATA_ROOT', DIR_DOCUMENT_ROOT . 'data');
//define('DIR_RELATIVE_DATA_ROOT', DIR_RELATIVE_DOCUMENT_ROOT . 'data');
//define('DIR_PROJECT_TO_DATA_ROOT', '../../data');

const DIR_DAOS_ROOT = DIR_DOCUMENT_ROOT . '/pool/examples/daos';
// define('DIR_RELATIVE_DAOS_ROOT', DIR_RELATIVE_LIB_ROOT . '/examples/daos'); wird nie benötigt

const DIR_SUBCODES_ROOT = DIR_DOCUMENT_ROOT . '/pool/examples/subcodes';
//	define('DIR_RELATIVE_SUBCODES_ROOT', DIR_RELATIVE_LIB_ROOT . '/subcodes');

//define('DIR_PUBLIC_ROOT', DIR_DOCUMENT_ROOT . 'public/');
//define('DIR_RELATIVE_PUBLIC_ROOT', DIR_RELATIVE_DOCUMENT_ROOT . 'public/');

const DIR_RELATIVE_3RDPARTY_ROOT = '../3rdParty';

if(extension_loaded('mbstring')) {
    mb_detect_order(APP_CHARSET . ',ISO-8859-1');
}