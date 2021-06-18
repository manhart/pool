<?php
/*
 * g7system.local
 *
 * Configurable.class.php created at 10.06.21, 11:23
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2021, GROUP7 AG
 */

namespace pool\classes;

trait Configurable {
    protected array $configurationKeys = [];
    protected string $storageEngine = '';
    public function getStorageEngine(): string
    {
        return $this->storageEngine;
    }
    public function setupConfiguration(array $keys) {
        $this->configurationKeys = $keys;
    }
    public abstract function hasConfiguration();
    public abstract function loadConfiguration();
    public abstract function saveConfiguration();
    public abstract function getConfiguration(): array;
    public abstract function setConfiguration(array $configuration);
}
