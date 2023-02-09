<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const POOL = 'POOL';
const pool = 'pool';

if(!defined('DIR_POOL_ROOT')) {
    define('DIR_POOL_ROOT', __DIR__);
}

const PWD_TILL_INCLUDES = 'includes';
const PWD_TILL_CLASSES = 'classes';
const PWD_TILL_GUIS = 'guis';
const PWD_TILL_SCHEMES = 'schemes';
const PWD_TILL_SKINS = 'skins';
const PWD_TILL_JAVASCRIPTS = 'js';
const PWD_TILL_SUBCODES = 'subcodes';

require_once(__DIR__ . '/' . PWD_TILL_CLASSES . '/Autoloader.php');
\pool\classes\Autoloader::getLoader()->register();

// @todo replace against Utils classes
require __DIR__ . '/' . PWD_TILL_INCLUDES . '/includes.lib.php';
// @todo load from autoloader
require __DIR__ . '/' . PWD_TILL_CLASSES . '/classes.lib.php';