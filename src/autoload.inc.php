<?php

/**
 * Class PoolAutoloader
 */
class PoolAutoloader
{
    private static $PoolLoader;

    /**
     * @return PoolAutoloader
     */
    static function getLoader()
    {
        if(null !== self::$PoolLoader) {
            return self::$PoolLoader;
        }
        self::$PoolLoader = new PoolAutoloader();
        return self::$PoolLoader;
    }

    /**
     * Register autloader for Classes and GUIs
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * loads class
     *
     * @param string $class
     * @throws ReflectionException
     */
    public function loadClass($class): bool
    {
        $isGUI = (strpos($class, 'GUI') === 0);
        if($isGUI) {
            return GUI_Module::autoloadGUIModule($class, null);
        }
        else {
            return PoolObject::autoloadClass($class);
        }
    }

    /**
     * Unregister autoloader
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
}