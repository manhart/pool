<?php declare(strict_types=1);
/*
 * pool
 *
 * GUI_ConfigurableModule.class.php created at 16.06.21, 08:48
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_ModuleConfigurable extends GUI_Module
{
    use Configurable;

    /**
     * provides all properties for the Inspector module
     *
     * @return array|array[]
     */
    public function getInspectorProperties(): array
    {
        return $this->inspectorProperties + $this->getDefaultInspectorProperties();
    }


    /**
     * returns the configuration as json
     *
     * @return string
     */
//    public function getConfigurationAsJSON(): string
//    {
//        return json_encode($this->getConfiguration());
//    }

    /**
     * returns the configuration as yaml
     *
     * @return string
     */
//    public function getConfigurationAsYAML(): string
//    {
//        return yaml_emit($this->getConfiguration());
//    }

    public function getConfigurationLoader(): ConfigurationLoader
    {
        return new JSONConfigurationLoader();
    }
}