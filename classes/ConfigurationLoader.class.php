<?php declare(strict_types=1);
/*
 * pool
 *
 * ConfigurationLoader.class.php created at 22.06.21, 09:32
 *
 * @author A.Manhart <A.Manhart@manhart-it.de>
 */

abstract class ConfigurationLoader
{
    const STORAGE_ENGINE_FILESYSTEM = 1;
    const STORAGE_ENGINE_DATABASE = 2;

    // storageEngine = Filesystem, Database
    public static int $storageEngine = 0;

    // FileDialog ja/nein? - Dialog ja/nein
    // DatabaseDialog ja/nein - Dialog ja/nein
    protected GUI_Module $ConfigurableModule;

    public function __construct(?GUI_Module $ConfigurableModule = null)
    {
        if($ConfigurableModule) $this->ConfigurableModule = $ConfigurableModule;
    }

    protected array $necessaryOptions = [];

    abstract public function setup(array $options);
    abstract public function loadConfiguration(): array;
    abstract public function saveConfiguration(array $config): bool;
    abstract public function configuration_exists(): bool;
    abstract static public function getDescription(): string;

    public function attemptAutoloadConfiguration(): void
    {
        $this->ConfigurableModule?->setConfiguration($this->loadConfiguration());
    }

    public static function getStorageEngine(): int
    {
        return static::$storageEngine;
    }
}