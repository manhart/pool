<?php
/**
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * Includes all necessary base files for the use of POOL
 *
 * @version $Id: pool.lib.php,v 1.2 2005/06/03 08:32:08 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-09-30
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

const POOL = 'POOL';

// subdirectories
const PWD_TILL_INCLUDES = 'includes';
const PWD_TILL_CLASSES = 'classes';
const PWD_TILL_GUIS = 'guis';
const PWD_TILL_SCHEMES = 'schemes';
const PWD_TILL_SKINS = 'skins';
const PWD_TILL_JAVASCRIPTS = 'javascripts';
// const PWD_TILL_3RDPARTY = '3rdparty';
const PWD_TILL_SUBCODES = 'subcodes';

if(!defined('DIR_POOL_ROOT')) {
    define('DIR_POOL_ROOT', __DIR__);
}

# Bindet die jeweiligen Includes (.lib.php) der Unterverzeichnisse mit ein.
require __DIR__.'/'.PWD_TILL_INCLUDES.'/includes.lib.php';
require __DIR__.'/'.PWD_TILL_CLASSES.'/classes.lib.php';
//require (PWD_TILL_3RDPARTY.'/3rdparty.lib.php');

if (defined('DIR_SUBCODES_ROOT') and is_dir(DIR_SUBCODES_ROOT)) {
    include_once __DIR__.'/'.DIR_SUBCODES_ROOT.'/subcodes.lib.php';
}

require_once('autoload.inc.php');
PoolAutoloader::getLoader()->register();