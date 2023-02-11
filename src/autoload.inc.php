<?php

/**
 * Class PoolAutoloader
 */
class PoolAutoloader
{
    private static PoolAutoloader $PoolLoader;

    /**
     * @return PoolAutoloader
     */
    static function getLoader(): PoolAutoloader
    {
        if(isset(self::$PoolLoader)) {
            return self::$PoolLoader;
        }
        self::$PoolLoader = new PoolAutoloader();
        return self::$PoolLoader;
    }

    /**
     * Register autloader for Classes and GUIs
     */
    public function register(): void
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * loads class
     *
     * @param string $class
     * @throws ReflectionException
     */
    public function loadClass(string $class): bool
    {
        $isGUI = (str_starts_with($class, 'GUI'));
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