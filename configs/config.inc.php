<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 * @since 2004/01/19
 */

/**
 * You have to set these variables in our App before including this config.inc.php e.g.
 *
 *  $relativeRoot = '/';
 *  $SQL_Host = 'localhost';
 *  $stage = 'develop'; // or 'staging' or 'production'
 *  $defaultSessionDuration = 1800; // 30 min
 *
 * The configuration file provides the following essential constants:
 *
 *  DIR_CONFIGS_ROOT (string) = (absoluter pfad) zeigt auf das Verzeichnis, in dem sich die Konfigurationsdateien befinden
 *  DIR_DOCUMENT_ROOT (string) = (absoluter pfad) zeigt auf DOCUMENT_ROOT des Apache Webservers
 *  DIR_RELATIVE_DOCUMENT_ROOT (string) = (relativer Pfad) zeigt auf DOCUMENT_ROOT des Apache Webservers
 *  DIR_POOL_ROOT (string) = (absoluter) Pfad zu den Hauptbibliotheken (POOL, DAOS, SUBCODES)
 *  DIR_DATA_DIR (string) = (absoluter pfad) zeigt auf das DATA Verzeichnis
 *  DIR_DAOS_ROOT (string) = (absoluter) Pfad zeigt direkt auf das DAOS Verzeichnis
 *  IS_DEVELOP (boolean) = gibt an, ob es sich um einen Entwicklungsrechner handelt.
 *  IS_STAGING (boolean) = gibt an, ob es sich um einen Stagingrechner handelt.
 *  IS_TESTSERVER (boolean) = gibt an, ob es sich einen Testrechner handelt.
 *  IS_PRODUCTION (boolean) = gibt an, ob es sich um einen Produktionsrechner handelt.
 *  MYSQL_HOST (string) = Hostname des MySQL Servers
 *  DEFAULT_SESSION_LIFETIME (int) = Standard Session Lebensdauer in Sekunden
 */

define('POOL_START', microtime(true));

// check if we are in command line mode
if(!defined('IS_CLI')) {
    define('IS_CLI', PHP_SAPI === 'cli');
}

//Config using Server environment
$baseNamespacePath ??= $_SERVER['_BaseNamespacePath'] ?? $_SERVER['DOCUMENT_ROOT'] ??
    die('Missing Config Parameter _BaseNamespacePath in Server Environment');
$relativeRoot ??= $_SERVER['_RelativeRoot'] ??
    die('Missing Config Parameter _RelativeRoot in Server Environment');
$SQL_Host ??= $_SERVER['_SQL_Host'] ??
    die('Missing Config Parameter _SQL_Host in Server Environment');
$stage ??= $_SERVER['_Stage'] ?? 'production';
$defaultSessionDuration ??= $_SERVER['_DefaultSessionDuration'] ?? 1800;

//export to constants
define('DIR_DOCUMENT_ROOT', $baseNamespacePath);
define('DIR_RELATIVE_DOCUMENT_ROOT', $relativeRoot);
define('MYSQL_HOST', $SQL_Host);
define('IS_DEVELOP', $stage === 'develop');
define('IS_STAGING', $stage === 'staging');
define('IS_PRODUCTION', $stage === 'production');

define('DEFAULT_SESSION_LIFETIME', $defaultSessionDuration);
const IS_TESTSERVER = (IS_DEVELOP || IS_STAGING);
// This constant points to the root directory of the configuration files, which is the directory I am currently in.
// The directory where the "config.inc.php" is located is considered as the DIR_CONFIGS_ROOT.
const DIR_CONFIGS_ROOT = __DIR__;

// Pool
const DIR_POOL_ROOT = DIR_DOCUMENT_ROOT . '/pool';

// Data access objects
const DIR_DAOS_ROOT = DIR_DOCUMENT_ROOT.'/commons/daos';

// Data and Resources
const DIR_DATA_ROOT = DIR_DOCUMENT_ROOT.'/data';
const DIR_RELATIVE_DATA_ROOT = DIR_RELATIVE_DOCUMENT_ROOT.'/data';

// Common GUIs
const DIR_COMMON_ROOT = DIR_DOCUMENT_ROOT.'/commons';
const DIR_COMMON_ROOT_REL = DIR_RELATIVE_DOCUMENT_ROOT.'/commons';
const DIR_RESOURCES_ROOT = DIR_COMMON_ROOT.'/resources';

//Third Party Resources
const DIR_3RDPARTY_ROOT = DIR_DOCUMENT_ROOT.'/3rdParty';
const DIR_RELATIVE_3RDPARTY_ROOT = DIR_RELATIVE_DOCUMENT_ROOT.'/3rdParty';

const PHP_MARIADB_DATE_FORMAT = 'Y-m-d';
const PHP_MARIADB_TIME_FORMAT = 'H:i:s';
const PHP_MARIADB_DATETIME_FORMAT = 'Y-m-d H:i:s';
const PHP_MARIADB_DATETIME_FORMAT_US6 = 'Y-m-d H:i:s.u';