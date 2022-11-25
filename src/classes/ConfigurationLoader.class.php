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
    protected bool $configureAutomatically;

    public function __construct()
    {
        $this->configureAutomatically = false;
    }

    protected array $necessaryOptions = [];

    abstract public function setup(array $options);
    abstract public function loadConfiguration(): array;
    abstract public function saveConfiguration(array $config): bool;
    abstract public function configuration_exists(): bool;
    abstract static public function getDescription(): string;

    public function configureAutomatically(bool $automatically): ConfigurationLoader
    {
        $this->configureAutomatically = $automatically;
        return $this;
    }

    public static function getStorageEngine(): int
    {
        return static::$storageEngine;
    }
}