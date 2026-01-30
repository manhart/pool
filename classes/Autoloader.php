<?php
declare(strict_types = 1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes;

use pool\classes\GUI\GUI_Module;

use function addEndingSlash;
use function constant;
use function defined;
use function file_exists;
use function getcwd;
use function spl_autoload_register;
use function spl_autoload_unregister;
use function str_replace;

use const pool\NAMESPACE_SEPARATOR;
use const pool\PWD_TILL_CLASSES;

class Autoloader
{
    private static Autoloader $PoolLoader;

    public static function getLoader(): Autoloader
    {
        if (isset(self::$PoolLoader)) {
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
     */
    public function loadClass(string $class): string|false
    {
        $isGUI = str_starts_with($class, 'GUI_') && $class !== GUI_Module::theClass();
        if ($isGUI) {
            return GUI_Module::autoloadGUIModule($class);
        }

        return self::autoloadClass($class);
    }

    /**
     * Determines whether a given class name has a namespace.
     */
    public static function hasNamespace(string $className): bool
    {
        return str_contains($className, NAMESPACE_SEPARATOR);
    }

    /**
     * Autoloader for POOL Classes
     *
     * @param string $className Fully qualified class name
     */
    public static function autoloadClass(string $className): string|false
    {
        if (static::hasNamespace($className)) {
            $classRootDirs = [
                defined('BASE_NAMESPACE_PATH') ? constant('BASE_NAMESPACE_PATH') : dirname(DIR_POOL_ROOT),
            ];

            $className = str_replace(NAMESPACE_SEPARATOR, '/', $className);
        } else {
            $cwd = getcwd();
            $classRootDirs = [$cwd.'/'.PWD_TILL_CLASSES];
            if ($cwd !== DIR_APP_ROOT) {
                $classRootDirs[] = DIR_APP_ROOT.'/'.PWD_TILL_CLASSES;
            }
            if (defined('DIR_COMMON_ROOT')) {
                $classRootDirs[] = DIR_COMMON_ROOT.'/'.PWD_TILL_CLASSES;
            }
            if (defined('DIR_POOL_ROOT')) {
                $classRootDirs[] = DIR_POOL_ROOT.'/'.PWD_TILL_CLASSES;
            }
        }

        foreach ($classRootDirs as $classRootDir) {
            $classRootDir = addEndingSlash($classRootDir);

            // PSR-4 style
            $file = "$classRootDir$className.php";
            if (self::requireFile($file)) {
                return $file;
            }
        }
        return false;
    }

    /**
     * Loads the file from the filesystem if it exists.
     */
    public static function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require_once $file;
            return true;
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
