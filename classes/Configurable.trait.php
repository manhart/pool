<?php declare(strict_types=1);
/*
 * pool
 *
 * Configurable.trait.php created at 16.06.21, 08:51
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

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
        ],
        'moduleDirectory' => [
            'pool' => true,
            'value' => '',
            'type' => 'string',
            'element' => 'input',
            'inputType' => 'hidden',
        ]
    ];

    /**
     * @var array contains the actual configuration for the module
     */
    private array $configuration = [];

    /**
     * @var array|string[] defines the supported configuration loaders
     */
    protected array $supportedConfigurationLoader = ['JSONConfigurationLoader'];

    /**
     * @var ConfigurationLoader default configuration loader
     */
    protected ConfigurationLoader $ConfigurationLoader;

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
     * returns default value from an inspector property
     *
     * @param $property
     * @return mixed|string|null
     */
    public function getConfigurationValue($property)
    {
        return $this->getInspectorProperties()[$property]['value'] ?? null;
    }

    public function getConfigurationLoader(): ConfigurationLoader
    {
        if(!isset($this->ConfigurationLoader)) {
            $this->ConfigurationLoader = new JSONConfigurationLoader();

            $this->ConfigurationLoader->setup([
                'filePath' => $this->getConfigurationValue('moduleDirectory'),
                'fileName' => $this->getName().'.json'
            ]);
        }
        return $this->ConfigurationLoader;
    }

    public function setConfigurationLoader(ConfigurationLoader $ConfigurationLoader)
    {
        $this->ConfigurationLoader = $ConfigurationLoader;
    }

    public function getSupportedConfigurationLoader(): array
    {
        return $this->supportedConfigurationLoader;
    }

    /**
     * we mix the inspector property values into the defaults. Overrides the getDefaults method of Module.
     * we also write the moduleName in the inspector properties as default value.
     *
     * @see Module::getDefaults()
     * @return Input
     */
    public function getDefaults(): Input
    {
        // set default moduleName
        $this->defaultInspectorProperties['moduleName']['value'] = $this->getName();
        // set default directory
        $this->defaultInspectorProperties['moduleDirectory']['value'] = $this->getClassDirectory();

        foreach($this->getInspectorProperties() as $key => $property) {
            $this->Defaults->setVar($key, $property['value']);
        }
        return $this->Defaults;
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
            if(isset($properties[$key])) {
                $property = $properties[$key];
                $type = $property['type'] ?? '';
                switch($type) {
                    case 'boolean':
                        if(is_string($value)) {
                            $value = string2bool($value);
                        }
                        break;
                }

                //                $isPoolOption = $this->getInspectorProperties()[$key]['pool'] ?? false; // serverside only
                $defaultValue = $property['value'] ?? '';
                if($defaultValue != $value) {
                    $config[$key] = $value;
                }

                if(isset($property['properties']) and is_array($property['properties']) and is_array($configuration[$key])) {
                    foreach($configuration[$key] as $z => $sub_configuration) {
                        $config[$key][$z] = $this->formatConfigurationValues($sub_configuration, $property['properties']);
                    }
                }

            }
            //            else {
            //                $this->poolOptions[$key] = $value;
            //            }
        }
        return $config;
    }

    /**
     * set configuration for module (it only takes different values)
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $this->formatConfigurationValues($configuration, $this->getInspectorProperties());

        if(isset($this->configuration['moduleName'])) {
            $this->setName($this->configuration['moduleName']);
        }

        $this->Input->setVars($this->configuration);
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

    public function provision(): void
    {
        // todo auto config

//        if($this->getConfigurationLoader()->configureAutomatically()) {
//
//        }
    }
}
