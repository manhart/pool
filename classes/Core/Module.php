<?php declare(strict_types = 1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

use pool\classes\Core\Input\Input;
use function array_merge;

/**
 * Core class for POOL modules. Provides the basic functionality for modules.
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class Module extends Component
{
    /**
     * Input contains the external input parameters/values. Which parameters are imported is defined by the superglobals.
     *
     * @var Input $Input
     */
    public Input $Input;

    /**
     * @var array<Module> $childModules contains all children modules
     */
    protected array $childModules = [];

    /**
     * Default values should ensure that the module runs even without parameterization. The default values are set in the 'init' function and determine
     * the normal behavior of the module.
     *
     * @var Input $Defaults
     */
    protected Input $Defaults;

    /**
     * Define how external variables are filtered and which default values are used, if no external variables are defined.
     * Format: ['key' => [DataType, default value]]
     *
     * @var array<string, array> $inputFilter
     */
    protected array $inputFilter = [];

    /**
     * Variables/parameters to be passed to the child modules.
     *
     * @var array $handoff
     */
    protected array $handoff;

    /**
     * @var int defines which superglobals should be used in this module. Superglobal variables are passed to superglobals in the Input class.
     */
    protected int $superglobals = Input::EMPTY;

    /**
     * Parent module
     *
     * @var Module $Parent
     */
    private Module $Parent;

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
    public function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner);
        $this->childModules = [];
        $this->handoff = [];
        $this->Defaults = new Input(Input::EMPTY);
        $this->internalParams = $params;
        $this->init();
    }

    /**
     * Set default values for external inputs
     *
     * @param int|null $superglobals Konstanten aus der Input.class.php
     * @see Input::init()
     */
    public function init(?int $superglobals = null)
    {
        // fill variable container input with external variables
        $this->Input = new Input($superglobals ?? $this->superglobals, $this->inputFilter);

        if($this->inputFilter) {
            $this->Input->applyDefaults();
        }
        else {
            // if the external variables are not defined ($this->Input), they are merged with the defaults.
            $this->Input->mergeVarsIfNotSet($this->getDefaults());
        }

        // Assigns also the module name
        $moduleName = $this->Input->setVars($this->internalParams)->getVar('moduleName');
        if($moduleName) $this->setName($moduleName);
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
     * provides the Input Container for the Defaults
     *
     * @return Input
     */
    public function getDefaults(): Input
    {
        return $this->Defaults;
    }

    /**
     * exports internal params as base64
     *
     * @return string params
     */
    public function exportInternalParams(array $otherParams = []): string
    {
        return \base64url_encode(\http_build_query(array_merge($otherParams, $this->getInternalParams())));
    }

    /**
     * get all internal params
     *
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
     * passes data to the client
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setClientVar(string $key, mixed $value): self
    {
        $this->clientVars[$key] = $value;
        return $this;
    }

    /**
     * returns client data
     *
     * @return array
     */
    public function getClientVars(): array
    {
        return $this->clientVars;
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
     * returns the parent module
     *
     * @return Module|null Ergebnis vom Typ Module
     */
    public function getParent(): ?Module
    {
        return $this->Parent ?? null;
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
     * Remove module
     *
     * @param Module $Module
     * @return Module
     */
    public function removeModule(Module $Module): static
    {
        $this->childModules = \array_diff($this->childModules, [$Module]);
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
        foreach($this->childModules as $Module) {
            if(\strcasecmp($Module->getName(), $moduleName) === 0) {
                return $Module;
            }
        }
        return null;
    }

    /**
     * Disables module
     */
    public function disable(): static
    {
        $this->enabled = false;
        return $this;
    }

    /**
     * Enables module
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

    /**
     * Imports variables from the parent module.
     *
     * @param array $handoff Liste bestehend aus Variablen
     * @return Module Erfolgsstatus
     */
    protected function importHandoff(array $handoff): self
    {
        if(\count($handoff)) {
            $this->addHandoffVar($handoff)->Input->setVars($handoff);
        }
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
        if(!\is_array($key)) {
            $this->handoff[$key] = $value;
        }
        else {
            $this->handoff = array_merge($key, $this->handoff);
        }
        return $this;
    }
}