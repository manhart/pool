<?php declare(strict_types=1);
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
use PoolObject;

class Autoloader
{
    private static Autoloader $PoolLoader;

    /**
     * @return Autoloader
     */
    static function getLoader(): Autoloader
    {
        if(isset(self::$PoolLoader)) {
            return self::$PoolLoader;
        }
        self::$PoolLoader = new Autoloader();
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
     * loads class
     *
     * @param string $class
     * @return bool
     */
    public function loadClass(string $class): bool
    {
        $isGUI = str_starts_with($class, 'GUI');
        if($isGUI) {
            return GUI_Module::autoloadGUIModule($class);
        }
        else {
            return PoolObject::autoloadClass($class);
        }
    }

    /**
     * Unregister autoloader
     */
    public function unregister(): void
    {
        spl_autoload_unregister([$this, 'loadClass']);
    }
}