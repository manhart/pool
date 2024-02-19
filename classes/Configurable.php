<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//namespace pool\classes;

use pool\classes\Core\Input\Input;

trait Configurable
{
    /**
     * @var array|array[] Default options for the module-inspector, e.g. moduleName is necessary!
     */
    private array $defaultInspectorProperties = [
        'moduleName' => [ // pool
            'pool' => true,
            'caption' => 'ModuleName',
            'type' => 'string',
            'value' => '',
            'element' => 'input',
            'inputType' => 'text',
            'mandatory' => true,
            'showTop' => true,
            'configurable' => true,
        ],
        'moduleDirectory' => [
            'pool' => true,
            'value' => '',
            'type' => 'string',
            'element' => 'input',
            'inputType' => 'hidden',
            'configurable' => true,
        ],
        'configuratorSessionProperties' => [ // property for the module configurator. define properties to automatically write them into session
            'pool' => true,
            'value' => [],
            'type' => 'array',
            'configurable' => false
        ]
    ];

    /**
     * @var bool
     */
    protected bool $autoloadConfiguration = true;

    /**
     * @var bool defines if the module is in design mode
     */
    private bool $inDesignMode = false;

    /**
     * @var array contains the actual configuration for the module
     */
    private array $configuration = [];

    /**
     * @var array|string[] defines the supported configuration loaders
     */
    protected array $supportedConfigurationLoader = ['DatabaseConfigurationLoader', 'JSONConfigurationLoader'];

    /**
     * @var ConfigurationLoader default configuration loader
     */
    protected ConfigurationLoader $configurationLoader;

    /**
     * @return array returns all inspector properties for the module. it must be declared and also return the getDefaultInspectorProperties!
     */
    abstract public function getInspectorProperties(): array;

    /**
     * @return array returns default inspector properties from configurable trait
     */
    public function getDefaultInspectorProperties(): array
    {
        return $this->defaultInspectorProperties;
    }

    /**
     * @return array
     */
    public function getConfiguratorSessionProperties(): array
    {
        return $this->getInspectorProperties()['configuratorSessionProperties']['value'] ?? [];
    }

    /**
     * returns default value from an inspector property
     *
     * @param $property
     * @return mixed|string|null
     */
    public function getConfigurationValue($property): mixed
    {
        return $this->getInspectorProperties()[$property]['value'] ?? null;
    }

    /**
     * @return ConfigurationLoader
     */
    public function getConfigurationLoader(): ConfigurationLoader
    {
        if(!isset($this->configurationLoader)) {
            $this->configurationLoader = new JSONConfigurationLoader($this);

            $this->configurationLoader->setup([
                'filePath' => $this->getConfigurationValue('moduleDirectory'),
                'fileName' => $this->getName().'.json'
            ]);
        }
        return $this->configurationLoader;
    }

    public function setConfigurationLoader(ConfigurationLoader $configurationLoader): void
    {
        $this->configurationLoader = $configurationLoader;
    }

    public function getSupportedConfigurationLoader(): array
    {
        return $this->supportedConfigurationLoader;
    }

    /**
     * we mix the inspector property values into the defaults. Overrides the getDefaults method of Module.
     * we also write the moduleName in the inspector properties as default value.
     *
     * @return Input
     * @see Module::getDefaults()
     */
    public function getDefaults(): Input
    {
        // set default moduleName
        $this->defaultInspectorProperties['moduleName']['value'] = $this->getName();
        // set default directory
        $this->defaultInspectorProperties['moduleDirectory']['value'] = $this->getClassDirectory();

        $inspectorProperties = $this->getInspectorProperties();
        foreach($inspectorProperties as $key => $property) {
            if(!$property) continue;
            $this->Defaults->setVar($key, $property['value']);
        }
        return $this->Defaults;
    }

    /**
     * @param string $new_name
     * @return Configurable
     */
    public function setName(string $new_name): static
    {
        $this->defaultInspectorProperties['moduleName']['value'] = $new_name;
        $this->Defaults->setVar('moduleName', $new_name);
        return parent::setName($new_name);
    }

    /**
     * @param array $configuration
     * @param array $properties
     * @return array
     */
    public function formatConfigurationValues(array $configuration, array $properties): array
    {
        $config = [];
        foreach($configuration as $key => $value) {
            if(!isset($properties[$key])) {
                continue;
            }
            $property = $properties[$key];
            $type = $property['type'] ?? 'string';
            $defaultValue = $property['value'] ?? null;

            $value = match($type) {
                'boolean' => is_string($value) ? string2bool($value) : (bool)$value,
                'integer' => $value !== '' ? (int)$value : $defaultValue,
                default => $value
            };

            if($defaultValue !== $value) {
                $config[$key] = $value;
            }

            if(isset($property['properties']) && is_array($property['properties']) && is_array($configuration[$key])) {
                foreach($configuration[$key] as $z => $sub_configuration) {
                    $config[$key][$z] = $this->formatConfigurationValues($sub_configuration, $property['properties']);
                }
            }
            //            else {
            //                $this->poolOptions[$key] = $value;
            //            }
        }
        return $config;
    }

    /**
     * Set configuration for module (it only takes different values)
     *
     * @param array $configuration
     * @param bool $inDesignMode
     * @return Configurable
     */
    public function setConfiguration(array $configuration, bool $inDesignMode = false): static
    {
        $this->configuration = $this->formatConfigurationValues($configuration, $this->getInspectorProperties());

        if(isset($this->configuration['moduleName'])) {
            $this->setName($this->configuration['moduleName']);
        }

        if($inDesignMode) {
            $this->inDesignMode = true;
            $this->writeSessionProperties();
        }

        $this->Input->setVars($this->configuration);
        return $this;
    }

    public function isInDesignMode(): bool
    {
        return $this->inDesignMode;
    }

    /**
     * @return void
     */
    private function writeSessionProperties(): void
    {
        if(!$this->configuration) return;

        $sessionProperties = $this->getConfiguratorSessionProperties();
        foreach($sessionProperties as $sessionPropertyName => $sessionPropertyNameOrArray) {
            if(is_array($sessionPropertyNameOrArray)) { // we've to write an array into session
                $key = array_key_first($sessionPropertyNameOrArray);
                if(!isset($this->configuration[$sessionPropertyName])) continue;
                $sessionPropertyNameOrArray[$key][$sessionPropertyName] = $this->configuration[$sessionPropertyName];
                $this->Session->setVars($sessionPropertyNameOrArray);
            }
            else {
                if(!isset($this->configuration[$sessionPropertyNameOrArray])) continue;
                $this->Session->setVar($sessionPropertyNameOrArray, $this->configuration[$sessionPropertyNameOrArray]);
            }
        }
    }

    /**
     * returns the configuration
     *
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function isConfigurable(): bool
    {
        return true;
    }

    /**
     * @param bool $autoloadConfiguration
     * @return $this
     */
    public function autoloadConfiguration(bool $autoloadConfiguration): static
    {
        $this->autoloadConfiguration = $autoloadConfiguration;
        return $this;
    }

    public function provision(): void
    {
        if($this->autoloadConfiguration) {
            $this->getConfigurationLoader()->attemptAutoloadConfiguration();
        }
    }
}