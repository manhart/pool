<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * pool.lib.php
 *
 * Inkludiert alle benoetigten Basisdateien fuer die Verwendung der Rapid Module Library!
 *
 * @version $Id: pool.lib.php,v 1.2 2005/06/03 08:32:08 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-09-30
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

define('POOL', 'POOL');

# Konstanten f�r die Unterverzeichnisse
define('PWD_TILL_INCLUDES', 'includes');
define('PWD_TILL_CLASSES', 'classes');
define('PWD_TILL_GUIS', 'guis');
define('PWD_TILL_SCHEMES', 'schemes');
define('PWD_TILL_SKINS', 'skins');
define('PWD_TILL_JAVASCRIPTS', 'javascripts');
define('PWD_TILL_3RDPARTY', '3rdparty');
define('PWD_TILL_SUBCODES', 'subcodes');

if(!defined('DIR_POOL_ROOT')) {
    define('DIR_POOL_ROOT', __DIR__);
}

# Bindet die jeweiligen Includes (.lib.php) der Unterverzeichnisse mit ein.
require (PWD_TILL_INCLUDES.'/includes.lib.php');
require (PWD_TILL_CLASSES.'/classes.lib.php');
//require (PWD_TILL_GUIS.'/guis.lib.php');
//require (PWD_TILL_3RDPARTY.'/3rdparty.lib.php');

if (defined('DIR_SUBCODES_ROOT') and is_dir(DIR_SUBCODES_ROOT)) {
    include_once (DIR_SUBCODES_ROOT.'/subcodes.lib.php');
}

require_once('autoload.inc.php');
PoolAutoloader::getLoader()->register();