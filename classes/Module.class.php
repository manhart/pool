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
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
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
         * Modul-Container enth�lt alle Kinder-Objekte (Childs)->welche Module sitzen auf mir?
         *
         * @var array $Modules
         * @access private
         */
        var $Modules=array();

        /**
         * Standardwerte sollten gew�hrleisten, dass das Modul auch ohne Parametrisierung l�uft. Die Standardwerte werden in der Funktion "init" festgelegt und bestimmen das normale Verhalten des Moduls.
         *
         * @var Input $Defaults
         * @access public
         */
        var $Defaults=null;

        /**
         * Superglobals. Alle Parameter-/Variablen�bergaben, sei es �ber Url (Get) oder Formular (Post) werden im Objekt Input festgehalten. Fehlen wichtige Parameter�bergaben, werden diese durch vorhandene Standardwerte ausgeglichen.
         *
         * @var Input $Input
         * @access public
         */
        var $Input = null;

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
         * Konstruktor
         *
         * Instanzierung von Objekten. Aufruf der "init" Funktion und anschlie�end Abgleich fehlender Werte durch Standardwerte.
         *
         * @access public
         * @param Component $Owner Eigent�mer dieser Instanz
         **/
        function __construct(&$Owner)
        {
            parent::__construct($Owner);

            $this->Modules = Array();
            $this->Handoff = Array();
            $this->Defaults = new Input(I_EMPTY);

            $this->init();
        }

        /**
         * Festlegen von Standardwerten und initialisierung der Superglobals (im Input Objekt).
         *
         * @access public
         * @param const $superglobals Konstanten aus der Input.class.php
         * @see Input.class.php
         **/
        function init($superglobals=I_EMPTY)
        {
            $this->Input = new Input($superglobals);
            $this->mergeDefaults();
        }

        /**
         * Gleicht fehlende Parameter/Variablen im Input Objekt anhand der festgelegten Standardwerte ab.
         *
         * @access public
         **/
        function mergeDefaults()
        {
            if ($this->Input instanceof Input) {
                if (count($this->Defaults->Vars) > 0) {
                    return $this->Input->mergeVarsSkipEmpty($this->Defaults);
                }
            }
            else {
                // ERROR init wurde nicht aufgerufen
            }
            return false;
        }

        /**
         * Importiert Variablen vom Elternmodul (durchschleifen von Variablen).
         *
         * @access public
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
                $this->Input->setVar($Handoff);
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
         * @access public
         * @see Component::setName()
         * @see Module::disable()
         * @param string $params Im Format: key=value&key2=value2&
         * @return bool Erfolgsstatus
         **/
        function importParams($params)
        {
            if (!$this->Input instanceof Input) {
                return false;
            }

            if(is_array($params)) {
                $this->Input->setVar($params);
            }
            else {
                $this->Input->setParams($params);
            }

            // set Component Name, if set by param.
            $ModuleName = $this->Input->getVar('ModuleName');
            if ($ModuleName == null) {
                $ModuleName = $this->Input->getVar('modulename');
            }
            if ($ModuleName != null) {
                $this->setName($ModuleName);
            }
            $disabled = $this->Input->getVar('ModuleDisabled');
            if ($disabled == 1) {
                $this->disable();
            }
            return true;
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
         */
        function setParam($modulename, $param, $value=null)
        {
            $bResult = false;
            $Module = &$this->Weblication->findComponent($modulename);
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
         * Setzt eine Variable in den Container Input
         *
         * @param string $key
         * @param mixed $value
         */
        function setVar($key, $value='')
        {
            $this->Input->setVar($key, $value);
        }

        /**
         * Liest eine Variable aus dem Container Input
         *
         * @param string $key
         * @return mixed
         */
        function getVar($key)
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
         * @param Module $parent Klasse vom Typ Module
         */
        function setParent(&$parent)
        {
            $this->Parent = &$parent;
        }

        /**
         * Gibt das Eltern-Modul (Parent) zurueck.
         *
         * @access public
         * @return Module Ergebnis vom Typ Module
         */
        function &getParent()
        {
            return $this->Parent;
        }

        /**
         * Fuegt ein Modul in den internen Modul-Container ein.
         *
         * @access public
         * @param object $Module
         **/
        function insertModule(& $Module)
        {
            $this->Modules[] = &$Module;
        }

        /**
         * Entfernt ein Modul
         *
         * @access public
         * @param object $Module
         **/
        function removeModule(& $Module)
        {
            $new_Modules = Array();

            // Rebuild Modules
            for ($i = 0; $i < count($this->Modules); $i++) {
                if ($Module != $this->Modules[$i]) {
                    $new_Modules[] = &$this->Modules[$i];
                }
            }

            $this->Modules = $new_Modules;
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

        /**
         * Destruktor
         *
         * Objekte, Arrays und Variablen werden freigegeben.
         *
         * @access public
         **/
        function destroy()
        {
            parent::destroy();

            unset($this->Modules);
        }
    }
}