<?php declare(strict_types = 1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes;

use GUI_Module;
use const pool\PWD_TILL_CLASSES;

class Autoloader
{
    /**
     * @constant string the extension of the class files
     */
    public const CLASS_EXTENSION = '.class.php';

    /**
     * @var Autoloader
     */
    private static Autoloader $PoolLoader;

    /**
     * @return Autoloader
     */
    public static function getLoader(): Autoloader
    {
        if(isset(self::$PoolLoader)) {
            return self::$PoolLoader;
        }
        self::$PoolLoader = new self();
        return self::$PoolLoader;
    }

    /**
     * Register autoloader for Classes and GUIs
     */
    public function register(): void
    {
        spl_autoload_register([$this, 'loadClass']);
    }

    /**
     * Loads POOL classes and GUIs
     *
     * @param string $class
     * @return bool
     */
    public function loadClass(string $class): bool
    {
        $isGUI = str_starts_with($class, 'GUI_') && $class !== 'GUI_Module';
        if($isGUI) {
            return GUI_Module::autoloadGUIModule($class);
        }

        return self::autoloadClass($class);
    }

    /**
     * Autoloader for POOL Classes
     *
     * @param string $className Klasse
     * @return bool
     */
    public static function autoloadClass(string $className): bool
    {
        $hasNamespace = str_contains($className, '\\');

        if($hasNamespace) {
            $classRootDirs = [
                defined('BASE_NAMESPACE_PATH') ? constant('BASE_NAMESPACE_PATH') : DIR_DOCUMENT_ROOT
            ];

            $className = str_replace('\\', '/', $className);
        }
        else {
            $classRootDirs = [
                getcwd().'/'.PWD_TILL_CLASSES
            ];
            if(defined('DIR_POOL_ROOT')) {
                $classRootDirs[] = DIR_POOL_ROOT.'/'.PWD_TILL_CLASSES;
            }
            if(defined('DIR_COMMON_ROOT')) {
                $classRootDirs[] = DIR_COMMON_ROOT.'/'.PWD_TILL_CLASSES;
            }
        }

        foreach($classRootDirs as $classRootDir) {
            $classRootDir = addEndingSlash($classRootDir);

            // old style
            $filename = $classRootDir.$className.self::CLASS_EXTENSION;
            if(file_exists($filename)) {
                require_once $filename;
                return true;
            }

            // PSR-4 style
            $filename = "$classRootDir$className.php";
            if(file_exists($filename)) {
                require_once $filename;
                return true;
            }
        }
        return false;
    }

    /**
     * Unregister autoloader
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }
}