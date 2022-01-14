<?php
/**
 * POOL (PHP Object Oriented Library): Module sind baukastenartige Klassen.
 *
 * Sie enthalten Input (PHP Autoglobals), Standardwerte fuer nicht �bergebene Variablen,
 * Parametersteuerung (z.B. aus dem Template heraus), einem Modulnamen und Handoffs
 * (Variablenwerte werden durchgereicht).
 *
 * - Festlegen von Standardwerten zur Gew�hrleistung der Funktion des Moduls
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

if(!defined('CLASS_MODULE')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_MODULE', 1);

    /**
     * Module sind baukastenartige Klassen (Features: Parametrisierung, Handoffs, Defaults, Enable/Disable).
     *
     * @author Alexander Manhart <alexander@manhart-it.de>
     * @package pool
     **/
    class Module extends Component
    {
        /**
         * Eltern-Objekt (Parent)->auf wem sitze ich fest?
         *
         * @var Module $Parent
         * @access private
         */
        var $Parent=null;

        /**
         * @var array{Module} $Modules contains all children modules
         */
        protected array $Modules = [];

        /**
         * Standardwerte sollten gew�hrleisten, dass das Modul auch ohne Parametrisierung l�uft. Die Standardwerte werden in der Funktion "init" festgelegt und bestimmen das normale Verhalten des Moduls.
         *
         * @var Input $Defaults
         * @access public
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
         * @access private
         */
        var $enabled = true;

        /**
         * @var array fixed params
         * @see Module::importParams()
         */
        private array $fixedParams = [];

        /**
         * Instanzierung von Objekten. Aufruf der "init" Funktion und anschlie�end Abgleich fehlender Werte durch Standardwerte.
         *
         * @param Component|null $Owner Owner
         * @param array $params fixed params
         * @throws ReflectionException
         */
        function __construct(?Component $Owner, array $params = [])
        {
            parent::__construct($Owner);

            $this->Modules = Array();
            $this->Handoff = Array();
            $this->Defaults = new Input(I_EMPTY);
            $this->fixedParams = $params;

            $this->init();
        }

        /**
         * set default values for external inputs
         *
         * @param int $superglobals Konstanten aus der Input.class.php
         * @see Input.class.php
         **/
        public function init(int $superglobals = I_EMPTY)
        {
            $this->Input = new Input($superglobals);
            $this->mergeDefaults();
            // assigns the module name
            $this->importParams($this->fixedParams);
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
         **/
        private function mergeDefaults(): void
        {
            $this->Input->mergeVarsIfNotSet($this->getDefaults());
        }

        /**
         * Importiert Variablen vom Elternmodul (durchschleifen von Variablen).
         *
         * @param array $Handoff Liste bestehend aus Variablen
         * @return bool Erfolgsstatus
         **/
        function importHandoff($Handoff)
        {
            if (!$this->Input instanceof Input) {
                return false;
            }

            if (count($Handoff) > 0) {
                $this->addHandoffVar($Handoff);
                $this->Input->setVars($Handoff);
            }
            return true;
        }

        /**
         * Importiert Parameter in das Modul (bzw. �bergibt sie dem Input Objekt).
         *
         * Spezielle Parameternamen:
         * - ModuleName: setzt den Komponentennamen
         * - ModuleDisabled: deaktiviert das Modul
         *
         * @see Component::setName()
         * @see Module::disable()
         * @param array $params Im Format: key=value&key2=value2&
         * @return bool Erfolgsstatus
         **/
        public function importParams(array $params): bool
        {
            if (!$this->Input instanceof Input) {
                return false;
            }

            $this->setVar($params);


            // set Component Name, if set by param
            $moduleName = $this->Input->getVar('moduleName');

            // old crime / delict. Should be removed! @deprecated
            if(($otherFixedName = $this->getFixedParam('modulename')) != null) {
                $moduleName = $otherFixedName;
            }
            if ($moduleName != null) {
                $this->setName($moduleName);
            }

            // @deprecated
            // $disabled = $this->Input->getVar('ModuleDisabled');
            // if ($disabled == 1) {
            //   $this->disable();
            // }
            return true;
        }

        /**
         * exports fixed params as base64
         *
         * @return string params
         */
        public function exportParams(): string
        {
            return base64url_encode(http_build_query($this->fixedParams));
        }

        /**
         * Setzt einen Parameter in einem vorhandenen Modul. Benutzt die Weblication Funktion "findComponent" zum Ermitteln des Moduls.
         * Es werden nur Module gefunden, deren Eigent�mer Weblication zugewiesen wurde.
         *
         * @access public
         * @param string $modulename Name des Moduls
         * @param string $param Name des Parameters
         * @param string $value Zu setzender Wert
         * @return bool Erfolgsstatus
         * @deprecated
         */
        function setParam($modulename, $param, $value=null)
        {
            $bResult = false;
            $Module = $this->Weblication->findComponent($modulename);
            if ($Module instanceof Module) {
                $Module->Input->setVar($param, $value);
                $bResult = true;
            }
            else {
                $this->raiseError(__FILE__, __LINE__,
                    sprintf('Cannot find Module "%s" (@setParam). Param "%s" not set.', $modulename, $param));
            }
            return $bResult;
        }


        /**
         * get fixed param
         *
         * @param $param
         * @return mixed|null
         */
        public function getFixedParam($param)
        {
            return $this->fixedParams[$param] ?? null;
        }

        /**
         * Einf�gen von Variablen, die an die Kinder-Module (Childs) weitergereicht werden.
         *
         * @access public
         * @param string $key Schluessel der Variable
         * @param string $value Wert der Variable
         * @return bool Erfolgsstatus
         */
        function addHandoffVar($key, $value = '')
        {
            if (!is_array($key)) {
                $this->Handoff[$key] = $value;
            }
            else {
                $this->Handoff = array_merge($key, $this->Handoff);
            }
            return true;
        }

        /**
         * Einf�gen von Variablen, die an die Kinder-Module (Childs) weitergereicht werden.
         *
         * @access public
         * @param string $key Schluessel der Variable
         * @param string $value Wert der Variable
         * @return bool Erfolgsstatus
         */
        function setHandoffVar($key, $value = '')
        {
            return $this->addHandoffVar($key, $value);
        }

        /**
         * puts variables into the Input container
         *
         * @param string|array $key
         * @param mixed $value
         */
        public function setVar($key, $value='')
        {
            if(is_array($key)) {
                $this->Input->setVars($key);
            }
            else {
                $this->Input->setVar($key, $value);
            }
        }

        /**
         * Liest eine Variable aus dem Container Input
         *
         * @param string $key
         * @return mixed
         */
        public function getVar(string $key)
        {
            return $this->Input->getVar($key);
        }

        /**
         * Prueft ob eine Variable im Container Input enthalten ist
         *
         * @param string $key
         * @return boolean
         */
        function emptyVar($key)
        {
            return $this->Input->emptyVar($key);
        }

        /**
         * Setzt das Eltern-Modul (Parent = wem gehoere ich?)
         *
         * @access public
         * @param Module|null $Parent Klasse vom Typ Module
         */
        function setParent(?Module $Parent)
        {
            $this->Parent = $Parent;
        }

        /**
         * Gibt das Eltern-Modul (Parent) zurueck.
         *
         * @access public
         * @return Module Ergebnis vom Typ Module
         */
        public function getParent(): ?Module
        {
            return $this->Parent;
        }

        /**
         * Fuegt ein Modul in den internen Modul-Container ein.
         *
         * @param Module $Module
         */
        public function insertModule(Module $Module)
        {
            $this->Modules[] = $Module;
        }

        /**
         * Entfernt ein Modul
         *
         * @param Module $Module
         */
        public function removeModule(Module $Module)
        {
            $new_Modules = Array();

            // Rebuild Modules
            $max = count($this->Modules);
            for ($i = 0; $i < $max; $i++) {
                if ($Module != $this->Modules[$i]) {
                    $new_Modules[] = $this->Modules[$i];
                }
            }

            $this->Modules = $new_Modules;
        }

        /**
         * Search for a child module.
         *
         * @param string $moduleName name of module
         * @return Module|null module
         */
        public function findChild(string $moduleName): ?Module
        {
            $result = null;
            if($moduleName == '') return null;

            $max = count($this->Modules);
            for ($i = 0; $i < $max; $i++){
                if (strcasecmp($this->Modules[$i]->getName(), $moduleName) == 0) {
                    $result = $this->Modules[$i];
                    break;
                }
            }
            return $result;
        }

        /**
         * Modul wird deaktiviert.
         *
         * @access public
         **/
        function disable()
        {
            $this->enabled = false;
        }

        /**
         * Modul wird aktiviert.
         *
         * @access public
         **/
        function enable()
        {
            $this->enabled = true;
        }
    }
}