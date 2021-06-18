<?php
/*
 * g7system.local
 *
 * GUI_ConfigurableModule.class.php created at 16.06.21, 08:48
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_ConfigurableModule extends GUI_Module
{
    /**
     * Options for the module-inspector
     *
     * @var array|array[]
     */
    private array $inspectorProperties = [
        'moduleName' => [ // pool
            'pool' => true,
            'caption' => 'ModuleName',
            'type' => 'string',
            'value' => '',
            'element' => 'input',
            'inputType' => 'text'
        ]
    ];

    protected array $configuration = [];

    /**
     * @return Input
     */
    public function getDefaults(): Input
    {
        // set default moduleName
        $this->inspectorProperties['moduleName']['value'] = $this->getName();

        foreach($this->getInspectorProperties() as $key => $property) {
            $this->Defaults->setVar($key, $property['value']);
        }
        return $this->Defaults;
    }

    /**
     * provides all properties for the Inspector module
     *
     * @return array|array[]
     */
    public function getInspectorProperties(): array
    {
        return $this->inspectorProperties;
    }

    /**
     * @param array $configuration
     * @param array $properties
     * @return array
     */
    public function optimizeConfiguration(array $configuration, array $properties): array
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
                        $config[$key][$z] = $this->optimizeConfiguration($sub_configuration, $property['properties']);
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
        $this->configuration = $this->optimizeConfiguration($configuration, $this->getInspectorProperties());

        if(isset($this->configuration['moduleName'])) {
            $this->setName($this->configuration['moduleName']);
        }

        $this->Input->setVar($this->configuration);
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

    /**
     * returns the configuration as json
     *
     * @return string
     */
    public function getConfigurationAsJSON(): string
    {
        return json_encode($this->getConfiguration());
    }

    /**
     * returns the configuration as yaml
     *
     * @return string
     */
    public function getConfigurationAsYAML(): string
    {
        return yaml_emit($this->getConfiguration());
    }

    /**
     * @return array|array[]
     */
//    public function getInspectorValues(): array
//    {
//        $result = $this->getInspectorProperties();
//        foreach($this->options as $key => $value) {
//            $result[$key]['value'] = $value;
//        }
//        return $result;
//    }
}