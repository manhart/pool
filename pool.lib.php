<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool
{

    use pool\classes\Autoloader;

    use function define;
    use function defined;

    const IS_CLI = PHP_SAPI === 'cli';// check if we are in command line mode
    const LINE_BREAK = IS_CLI ? PHP_EOL : '<br>';
    const NAMESPACE_SEPARATOR = '\\';

    if (!defined('DIR_POOL_ROOT')) {
        define('DIR_POOL_ROOT', realpath(__DIR__));
    }

    // Determines the relative path from the pool root directory to the document root directory (e.g., /var/www/html)
    // for including pool files such as JavaScript, CSS or images.
    if (!defined('DIR_RELATIVE_POOL_TO_DOC_ROOT')) {
        $dirDocumentRoot = realpath($_SERVER['DOCUMENT_ROOT']);

        if (str_starts_with(DIR_POOL_ROOT, $dirDocumentRoot)) {
            $dirRelativePoolRoot = substr(DIR_POOL_ROOT, strlen($dirDocumentRoot));
            define('DIR_RELATIVE_POOL_TO_DOC_ROOT', $dirRelativePoolRoot);
        }
    }

    if (!defined('DIR_APP_ROOT')) { //necessary for applications without PSR-4
        define('DIR_APP_ROOT', dirname($_SERVER['SCRIPT_FILENAME']));//includes symlinked directory
    }

    const PWD_TILL_INCLUDES = 'includes'; // todo remove after refactoring to PSR-4
    const PWD_TILL_CLASSES = 'classes'; // todo remove after refactoring to PSR-4
    const PWD_TILL_GUIS = 'guis'; // todo remove after refactoring to PSR-4
    const PWD_TILL_SCHEMES = 'schemes';
    const PWD_TILL_SKINS = 'skins';
    const PWD_TILL_JS = 'js';

    require_once(__DIR__.'/'.PWD_TILL_CLASSES.'/Autoloader.php');
    Autoloader::getLoader()->register();

    // @todo replace against Utils classes after refactoring to PSR-4
    require __DIR__.'/'.PWD_TILL_INCLUDES.'/includes.lib.php';
}