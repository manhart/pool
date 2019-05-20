<?php
/**
 * POOL (PHP Object Oriented Library): die Datei PoolObject.class.php enthaelt die Grundklasse, der Uhrahn aller Objekte.
 *
 * Die Klasse Nil ist ein NULL Objekt und hat keine Bedeutung (wie in Pascal/Delphi).<br>
 *
 * Vermerk Author:<br>
 * Ich will an diesem System nichts verkomplizieren, keep it simple stupid.
 *
 * Letzte aenderung am: $Date: 2019-03-21 13:05:12 +0100 (Do, 21 Mrz 2019) $
 *
 * $Log: PoolObject.class.php,v $
 * Revision 1.12  2006/10/20 08:42:17  manhart
 * n/a
 *
 * Revision 1.11  2006/08/07 11:36:16  manhart
 * Exception->Xception (PHP5 kompatibel)
 *
 *
 * @version $Id: PoolObject.class.php 37855 2019-03-21 12:05:12Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 * @package pool
 */

if(!defined('CLASS_POOLOBJECT')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_POOLOBJECT',			1);

    if (substr(PHP_OS, 0, 3) == 'WIN') {
        $os_windows = true;
    }
    else {
        $os_windows = false;
    }

    /**
     * Konstante sagt aus, ob PHP unter Windows laeuft
     */
    define('OS_WINDOWS',			$os_windows);

    /**
     * Konstante sagt aus, ob PHP unter Linux laeuft
     */
    define('OS_UNIX',				!$os_windows);

    /**
     * Konstante enthaelt Name des Betriebssystems
     */
    define('OS_POOL_NAME',			($os_windows) ? 'Windows' : 'Linux');

    /**
     * Die Grundklasse, der Uhrahn aller Objekte.
     *
     * Die Klasse Object verf�gt �ber folgende Verhalten:
     * - stellt eine Art Debug-Modus bereit.
     * - Objektinstanzen erzeugen, verwalten und aufl�sen.
     * - auf objektspezifische Informationen �ber den Klassentyp und die Instanz zugreifen.
     * - enth�lt Fehler�berpr�fung und kann Fehler ausl�sen.
     * - stellt ein Verfahren bereit mit dem ein Inhalt eines Objekts einem anderen zugewiesen werden kann.
     *
     * Object wird nie direkt instantiiert. Obwohl keine Programmiersprachenelemente zum Verhindern der Instantiierung verwendet werden, ist Object eine abstrakte Klasse.
     *
     * @access public
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @package pool
     */
    class PoolObject extends stdClass
    {
        /**
         * Debug-Modus (Default: false)
         *
         * @var bool $debug
         * @access public
         */
        var $isDebugMode=false;

        /**
         * Zeilenumbruch (fuer HTML/Konsolen Ausgaben)
         *
         * @var string $new_line
         * @access private
         */
        var $new_line='<br>';

        /**
         * Konstruktor
         *
         * @access public
         */
        function __construct()
        {
        }

        /**
         * Erzeugt eine neue Instanz der Klasse.
         *
         * @access public
         * @return PoolObject
         */
        function &createNew()
        {
            $ClassName = $this->getClassName();
            $new_obj = new $ClassName();
            return $new_obj;
        }

        /**
         * Liefert den Namen der Klasse inkl. Namespace.
         *
         * @access public
         * @return string Name der Klasse
         */
        function getClassName()
        {
            return get_class($this);
        }

        /**
         * Liefert den Klassennamen ohne Namespace
         *
         * @return bool|string
         */
        function getClassNameShort()
        {
            return (substr(strrchr($this->getClassName(), '\\'),1));
        }

        /**
         * Gibt den Namen der Elternklasse (von dem das Objekt abgeleitet wurde) zurueck.
         *
         * @access public
         * @return string Name der Elternklasse
         */
        function getParentClass()
        {
            return get_parent_class($this);
        }

        /**
        * Die Methode assignObject kopiert alle Eigenschaften eines Objektes gleichen Typs auf sich selbst.
        *
        * @access public
        * @param PoolObject $object Objekt, von dem Eigenschaften uebernommen werden sollen
        * @return bool Erfolgsstatus
        */
        function assignObject($object)
        {
            if (is_a($object, $this->getClassName())) {
                $vars = get_object_vars($object); reset($vars);
                while(list($key, $value) = each($vars)) {
                    $this->$key = $value;
                }
                return true;
            }
            else {
                $this->raiseError(__FILE__, __LINE__,
                    'Eigenschaften unterschiedlicher Klassen koennen nicht zugewiesen werden (@assignObject).');
                return false;
            }
        }

        /**
         * Aktiviere Fehlersuche und -behebung.
         *
         * @access public
         **/
        function enableDebugging()
        {
            $this->isDebugMode = true;
        }

        /**
         * Deaktiviere Fehlersuche und - behebung.
         *
         * @access public
         **/
        function disableDebugging()
        {
            $this->isDebugMode = false;
        }

        /**
         * Erzeugt eine Debug Ausgabe, sofern Debugging aktiviert wurde.
         *
         * @access public
         * @param string $text Text
         * @see PoolObject::$new_line
         */
        function debug($text)
        {
            if ($this->isDebugMode == true) {
                print($text.$this->new_line);
            }
        }

        /**
        * Loest eine PHP Fehlermeldung vom Typ E_USER_NOTICE aus.
        * Ab PHP 4.3.0 stehen noch __CLASS__ und __FUNCTION__ zur Verfuegung. Da diese Objekt Version jedoch unter 4.1.6 entwickelt wird, stehen nur __FILE__ und __LINE__ als Parameter bereit.
        * Die Funktion raiseError arbeitet mit der PHP Funktion trigger_error(). Wird der PHP Error Handler ueberschrieben, koennen individuelle Fehlerprotokolle erstellt werden.
        *
        * @param string $file Datei (in der, der Fehler aufgetreten ist)
        * @param string $line Zeilennummer
        * @param string $msg Fehlermeldung (Setzt sich zusammen aus: Fehler in der Klasse -Platzhalter-, Datei -Platzhalter-, Zeile -Platzhalter- Meldung: -Fehlermeldung-
        */
        function raiseError($file, $line, $msg)
        {
            $error = $msg;
            $error .= ' in ';
            if($this) {
                $error .= 'der Klasse ' . $this->getClassName() . ', ';
            }
            $error .= 'Datei ' . $file .
                ', Zeile ' . $line;

            trigger_error($error);
            return 0;
        }

        /**
         * Wirft eine Xception am Bildschirm aus oder schreibt sie in das PHP Logfile aus.
         *
         * Wohin die Xception Ihre Fehlermeldung sendet (Bildschirm, Logfile, Mail), wird in der Xception Klasse festgelegt.
         *
         * @param Xception $Xception Die Xception oder null, falls es sich bei diesem Objekt selbst um eine Xception handelt.
         * @access public
         **/
        function throwException($Xception=null)
        {
            if(is_null($Xception) and $this instanceof Xception) {
                $Xception = &$this;
            }
            if($Xception) {
                /* @var $Xception Xception */
                $Xception->raiseError();
            }
            else {
                echo 'Fatal exception error in Object::throwException!'.$this->new_line;
                die('Script terminated');
            }
        }

        /**
         * Ueberprueft Parameter $data, ob es sich um eine Xception handelt.
         *
         * @access  public
         * @param Xception $data Wert, der ueberprueft wird, ob es sich um eine Xception handelt.
         * @param int $code Wenn $data eine Xception ist, wird true zurueckgeben; aber wenn der Parameter $code ein String ist und $obj->getMessage() == $code oder $code ist ein integer und $obj->getCode() == $code
         * @return bool true wenn es sich bei dem Parameter um eine Xception handelt.
         **/
        function isError($data, $code=null)
        {
            if($data instanceof Xception) {
                if (is_null($code)) {
                    return true;
                }
                elseif (is_string($code)) {
                    return ($data->getMessage() == $code);
                }
                else {
                    return ($data->getCode() == $code);
                }
            }
            return false;
        }

        /**
         * OS independant PHP extension load. Remember to take care
         * on the correct extension name for case sensitive OSes.
         *
         * @param string $ext The extension name
         * @return bool Success or not on the dl() call
         */
        function loadExtension($ext)
        {
            if (!extension_loaded($ext)) {
                // if either returns true dl() will produce a FATAL error, stop that
                if ((ini_get('enable_dl') != 1) || (ini_get('safe_mode') == 1)) {
                    return false;
                }
                if (constant('OS_WINDOWS')) {
                    $suffix = '.dll';
                }
                elseif (PHP_OS == 'HP-UX') {
                    $suffix = '.sl';
                }
                elseif (PHP_OS == 'AIX') {
                    $suffix = '.a';
                }
                elseif (PHP_OS == 'OSX') {
                    $suffix = '.bundle';
                }
                else {
                    $suffix = '.so';
                }
                return @dl('php_'.$ext.$suffix) || @dl($ext.$suffix);
            }
            return true;
        }

        /**
         * Destruktor
         *
         * @access protected
         */
        function destroy()
        {
            unset($this->new_line);
            unset($this->isDebugMode);
        }
    }

/* --------------------- */
######### Nil     #########
/* --------------------- */


    /**
     * Nil ist ein NULL Objekt. Ueber die Funktion isNil kann ein NULL Objekt ueberprueft werden.
     *
     * @package pool
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
     **/
    class Nil
    {
    }

    /**
     * Ueberprueft Parameter $instance, ob es sich um das Objekt Nil handelt. Nil ist ein NULL Objekt.
     *
     * @access public
     * @param object $instance Zu untersuchende Objekt-Instanz
     * @return bool Objekt-Instanz ist ein NULL Objekt (true), Objekt-Instanz ist kein NULL Objekt (false)
     **/
    function isNil($instance)
    {
        if ($instance instanceof Nil) {
            return true;
        }
        return false;
    }
}