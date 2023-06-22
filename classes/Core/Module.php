<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

class Module extends Component
{
    /**
     * Parent module
     *
     * @var Module $Parent
     */
    private Module $Parent;

    /**
     * @var array<Module> $childModules contains all children modules
     */
    protected array $childModules = [];

    /**
     * Default values should ensure that the module runs even without parameterization. The default values are set in the 'init' function and determine the normal behavior of the module.
     *
     * @var Input $Defaults
     */
    protected Input $Defaults;

    /**
     * Input contains the external input parameters/values. Which parameters are imported is defined by the superglobals.
     *
     * @var Input $Input
     */
    public Input $Input;

    /**
     * Variables/parameters to be passed to the child modules.
     *
     * @var array $handoff
     */
    protected array $handoff;

    /**
     * states whether the module is enabled or not
     *
     * @var bool $enabled
     */
    private bool $enabled = true;

    /**
     * @var array Nothing else than internal (externally protected) parameters that are given when the module is created or defined via a template.
     * @see Module::importInternalParams()
     */
    private array $internalParams;

    /**
     * @var int defines which superglobals should be used in this module. Superglobal variables are passed to superglobals in the Input class.
     */
    protected int $superglobals = Input::EMPTY;

    /**
     * data for the client
     *
     * @var array
     */
    private array $clientVars = [];

    /**
     * Creates the Input Defaults. Stores internal parameters and calls the init method.
     *
     * @param Component|null $Owner Owner
     * @param array $params internal parameters
     */
    function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner);
        $this->childModules = [];
        $this->handoff = [];
        $this->Defaults = new Input(Input::EMPTY);
        $this->internalParams = $params;
        $this->init();
    }

    /**
     * set default values for external inputs
     *
     * @param int|null $superglobals Konstanten aus der Input.class.php
     * @see Input.class.php
     */
    public function init(?int $superglobals = null)
    {
        if(!isset($superglobals)) {
            $superglobals = $this->superglobals;
        }
        // fill variable container input with external variables
        $this->Input = new Input($superglobals);
        // assigns also the module name
        $this->importInternalParams($this->internalParams);
        // if the external variables are not defined ($this->Input), they are merged with the defaults.
        $this->mergeDefaults();
    }

    /**
     * provides the Input Container for the Defaults
     *
     * @return Input
     */
    public function getDefaults(): Input
    {
        return $this->Defaults;
    }

    /**
     * Gleicht fehlende Parameter/Variablen im Input Objekt anhand der festgelegten Standardwerte ab.
     */
    private function mergeDefaults(): void
    {
        $this->Input->mergeVarsIfNotSet($this->getDefaults());
    }

    /**
     * Imports variables from the parent module.
     *
     * @param array $handoff Liste bestehend aus Variablen
     *
     * @return Module Erfolgsstatus
     */
    protected function importHandoff(array $handoff): self
    {
        if(count($handoff) == 0) {
            return $this;
        }
        $this->addHandoffVar($handoff);
        $this->Input->setVars($handoff);

        return $this;
    }

    /**
     * Imports internal parameters into the module
     *
     * special treated parameters:
     *   moduleName: sets the module name
     *
     * @param array $params Im Format: key=value&key2=value2&
     * @return bool Erfolgsstatus
     * @see Component::setName()
     * @see Module::disable()
     */
    public function importInternalParams(array $params): bool
    {
        $this->setVars($params);
        // set Component Name, if set by param
        $moduleName = $this->getVar('moduleName');

        if($moduleName) {
            $this->setName($moduleName);
        }
        return true;
    }

    /**
     * exports internal params as base64
     *
     * @return string params
     */
    public function exportInternalParams(array $otherParams = []): string
    {
        return base64url_encode(http_build_query(array_merge($otherParams, $this->getInternalParams())));
    }

    /**
     * get all internal params
     * @return array
     */
    public function getInternalParams(): array
    {
        return $this->internalParams;
    }

    /**
     * get internal param
     *
     * @param string $param parameter name
     * @param mixed|null $default default value null if omitted
     * @return mixed
     */
    public function getInternalParam(string $param, mixed $default = null): mixed
    {
        return $this->internalParams[$param] ?? $default;
    }

    /**
     * set internal param
     *
     * @param string $param
     * @param mixed $value
     * @return Module
     */
    public function setInternalParam(string $param, mixed $value): Module
    {
        $this->internalParams[$param] = $value;
        return $this;
    }

    /**
     * Inserting variables that are passed to the child modules.
     *
     * @param mixed $key name of the variable
     * @param mixed $value value of the variable
     */
    public function addHandoffVar(mixed $key, mixed $value = ''): static
    {
        if(!is_array($key)) {
            $this->handoff[$key] = $value;
        }
        else {
            $this->handoff = array_merge($key, $this->handoff);
        }
        return $this;
    }

    /**
     * puts variables into the Input container
     *
     * @param string $key
     * @param mixed $value
     * @return Input
     */
    public function setVar(string $key, mixed $value = ''): Input
    {
        return $this->Input->setVar($key, $value);
    }

    /**
     * puts the values of an array into the input container
     *
     * @param array $assoc
     * @return Input
     */
    public function setVars(array $assoc): Input
    {
        return $this->Input->setVars($assoc);
    }

    /**
     * passes data to the client
     *
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setClientVar(string $key, mixed $value): self
    {
        $this->clientVars[$key] = $value;
        return $this;
    }

    /**
     * passes data to the client
     *
     * @param array $vars
     * @return $this
     */
    public function setClientVars(array $vars): self
    {
        $this->clientVars = array_merge($this->clientVars, $vars);
        return $this;
    }

    /**
     * returns client data
     * @return array
     */
    public function getClientVars(): array
    {
        return $this->clientVars;
    }

    /**
     * Liest eine Variable aus dem Container Input
     *
     * @param string $key
     * @return mixed
     */
    public function getVar(string $key): mixed
    {
        return $this->Input->getVar($key);
    }

    /**
     * Wrapper for Input::emptyVar
     *
     * @param string $key
     * @return boolean
     * @see Input
     */
    public function emptyVar(string $key): bool
    {
        return $this->Input->emptyVar($key);
    }

    /**
     * set the parent module
     *
     * @param Module $Parent Klasse vom Typ Module
     */
    public function setParent(Module $Parent): static
    {
        $this->Parent = $Parent;
        return $this;
    }

    /**
     * returns the parent module
     *
     * @return Module|null Ergebnis vom Typ Module
     */
    public function getParent(): ?Module
    {
        return $this->Parent ?? null;
    }

    /**
     * insert module as child
     *
     * @param Module $Module
     * @return Module
     */
    public function insertModule(Module $Module): static
    {
        $this->childModules[] = $Module;
        return $this;
    }

    /**
     * remove module
     *
     * @param Module $Module
     * @return Module
     */
    public function removeModule(Module $Module): static
    {
        $new_Modules = [];
        // rebuild modules
        foreach($this->childModules as $SearchedModule) {
            if($Module != $SearchedModule) {
                $new_Modules[] = $SearchedModule;
            }
        }
        $this->childModules = $new_Modules;
        return $this;
    }

    /**
     * Search for a child module.
     *
     * @param string $moduleName name of module
     * @return Module|null module
     */
    public function findChild(string $moduleName): ?Module
    {
        if($moduleName == '') return null;

        foreach($this->childModules as $Module) {
            if(strcmp($Module->getName(), $moduleName) == 0) {
                return $Module;
            }
        }
        return null;
    }

    /**
     * disables module
     */
    public function disable(): static
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * enables module
     */
    public function enable(): static
    {
        $this->enabled = true;
        return $this;
    }

    /**
     * Returns the enabled state of the module
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }
}