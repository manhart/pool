<?php declare(strict_types=1);
/**
 * POOL (PHP Object Oriented Library)
 *
 * Module sind baukastenartige Klassen.
    *
 * Sie enthalten Input (PHP Autoglobals), Standardwerte fuer nicht übergebene Variablen,
     * Parametersteuerung (z.B. aus dem Template heraus), einem Modulnamen und Handoffs
     * (Variablenwerte werden durchgereicht).
     *
 * - Festlegen von Standardwerten zur Gewährleistung der Funktion des Moduls
     * - Module koennen auch deaktiviert / ausgeschaltet werden (enabled/disabled).
     * - Module enthalten 'Kinder'-Module (Childs) sowie ein 'Eltern'-Modul (Parent). Aus Template Sicht liegen Kinder-Module auf diesem Modul und dieses Modul auf dem Eltern-Modul.
     * - Durchschleifen von Variablen an die Kinder-Module (Childs)
     * - Spezielle Erweiterungen zur Parametrisierung (siehe Methode setParams)
     *
     * Letzte aenderung am: $Date: 2007/05/31 14:34:06 $
     *
     * @version $Id: Module.class.php,v 1.10 2007/05/31 14:34:06 manhart Exp $
     * @version $Revision 1.0$
     * @version
     *
     * @since 2003-07-10
     * @author Alexander Manhart <alexander@manhart-it.de>
     * @link https://alexander-manhart.de
     * @package pool
     */
class Module extends Component{
/**
 * Eltern-Objekt (Parent)->auf wem sitze ich fest?
 *
 * @var Module $Parent
 */
    private Module $Parent;
/**
     * @var array{Module} $childModules contains all children modules
*/
    protected array $childModules = [];
/**
 * Standardwerte sollten gew�hrleisten, dass das Modul auch ohne Parametrisierung l�uft. Die Standardwerte werden in der Funktion "init" festgelegt und bestimmen das normale Verhalten des Moduls.
 *
 * @var Input $Defaults
 */
    protected Input $Defaults;
/**
 * Superglobals. Alle Parameter-/Variablen�bergaben, sei es �ber Url (Get) oder Formular (Post) werden im Objekt Input festgehalten. Fehlen wichtige Parameter�bergaben, werden diese durch vorhandene Standardwerte ausgeglichen.
 *
 * @var Input $Input
 */
    public Input $Input;
/**
 * Variablen/Parameter, die an die Kinder-Module (Childs) weitergereicht werden sollen.
 *
 * @var array $Handoff
 * @access private
 */
var $Handoff = null;
/**
 * Merker, ob das Modul aktiv ist / eingebunden wird.
 *
 * @var bool $enabled
 */
    private bool $enabled = true;
    /**@var array Nothing else than internal (externally protected) parameters that are given when the module is created or defined via a template.
     * @see Module::importInternalParams()
     */
    private array $internalParams = [];

    /**
     * @var int filter that defines which superglobals are passed to input->vars
     */
    protected int $superglobals = I_EMPTY;

/**
 * Instanzierung von Objekten. Aufruf der "init" Funktion und anschlie�end Abgleich fehlender Werte durch Standardwerte.
 *
 * @param Component|null $Owner Owner
 * @param array $params fixed params
 * @throws Exception
 */
    function __construct(?Component $Owner, array $params = []){parent::__construct($Owner);
        $this->childModules = [];
        $this->Handoff = [];$this->Defaults = new Input(I_EMPTY);
        $this->internalParams = $params;
        $this->init();}
/**
 * set default values for external inputs
 *
 * @param int|null $superglobals Konstanten aus der Input.class.php
 * @throws Exception
 * @see Input.class.php
 */
    public function init(?int $superglobals = null){
        if(!isset($superglobals)) {
            $superglobals = $this->superglobals;
        }$this->Input = new Input($superglobals);
        // assigns also the module name
        $this->importInternalParams($this->internalParams);$this->mergeDefaults();
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
     *
     * @throws Exception
     */
    private function mergeDefaults(): void
    {
        $this->Input->mergeVarsIfNotSet($this->getDefaults());}
/**
 * Importiert Variablen vom Elternmodul (durchschleifen von Variablen).
 *
 * @param array $Handoff Liste bestehend aus Variablen
 * @return Module Erfolgsstatus
 */
    function importHandoff(array $Handoff): self{
        if(count($Handoff) == 0) {
            return $this;}$this->addHandoffVar($Handoff);
        $this->Input->setVars($Handoff);

        return $this;}
/**
 * Importiert Parameter in das Modul (bzw. �bergibt sie dem Input Objekt).
 *
 * Spezielle Parameternamen:
 * - ModuleName: setzt den Komponentennamen
 * - ModuleDisabled: deaktiviert das Modul
 *
 * @param array $params Im Format: key=value&key2=value2&
 * @return bool Erfolgsstatus
 * @see Component::setName()
 * @see Module::disable()
 */
    public function importInternalParams(array $params): bool
    {$this->setVars($params);
        // set Component Name, if set by param
        $moduleName = $this->getVar('moduleName');

        if($moduleName) {
            $this->setName($moduleName);
        }return true;}
    /**
     * exports internal params as base64
     *
     * @return string params
     */
    public function exportInternalParams(array $otherParams = []): string
    {
        return base64url_encode(http_build_query(array_merge($otherParams, $this->internalParams)));
    }

    /** get internal param
     * @param string $param
     * @return mixed
     */
    public function getInternalParam(string $param): mixed
    {
        return $this->internalParams[$param] ?? null;
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
 * Einf�gen von Variablen, die an die Kinder-Module (Childs) weitergereicht werden.
 *
 * @param string $key Schluessel der Variable
 * @param string $value Wert der Variable
 * @return bool Erfolgsstatus
 */
    function addHandoffVar($key, $value = ''){if(!is_array($key)) {$this->Handoff[$key] = $value;}else {$this->Handoff = array_merge($key, $this->Handoff);}return true;}
/**
 * puts variables into the Input container
 *
 * @param string $key
 * @param mixed $value
 * @return Module
 */
    public function setVar(string $key, $value = ''): Module{
        if(is_array($key)) {
            // @deprecated
            $this->Input->setVars($key);
        }
        else {$this->Input->setVar($key, $value);}
        return $this;
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
    public function getVar(string $key): mixed{
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
    {return $this->Input->emptyVar($key);}
/**
 * Setzt das Eltern-Modul (Parent = wem gehoere ich?)
 *
 * @param Module $Parent Klasse vom Typ Module
 */
    public function setParent(Module $Parent){
        $this->Parent = $Parent;}
/**
 * Gibt das Eltern-Modul (Parent) zurueck.
 *
 * @return Module|null Ergebnis vom Typ Module
 */
    public function getParent(): ?Module{
        return $this->Parent ?? null;}
    /**insert module as child
     * @param Module $Module
     * @return Module
     */
    public function insertModule(Module $Module): self {
        $this->childModules[] = $Module;
        return $this;}
    /** remove module
     * @param Module $Module
     */
    public function removeModule(Module $Module) {
        $new_Modules = [];
// Haus?
        // rebuild modules
        foreach($this->childModules as $SearchedModule) {
            if($Module != $SearchedModule) {
                $new_Modules[] = $SearchedModule;}}
        $this->childModules = $new_Modules;}
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

    /** deactivates module */
    public function disable() { $this->enabled = false; }
    /** enables module */
    public function enable() { $this->enabled = true; }

    /**
     * Returns that the module is enabled
     *
     * @return bool
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }
    }