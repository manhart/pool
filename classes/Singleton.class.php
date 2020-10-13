<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class Singleton wird oft fuer Ressourcen-Sharing genutzt. Z.B. eine
* Klasse zur Logfile-Generierung (vermeidet I/O-Konflikte, indem
* das Logfile von genau einem Exemplar dieser Klasse verwaltet wird).
* Das Prinzip hinter dem Singleton ist in wenigen Worten zu beschreiben:
* man nutzt nicht mehr das Sprachkonstrukt "new", um ein Exemplar einer
* Klasse zu instanzieren, man ueberlaesst dies dem Singleton.
*
* Dabei ueberprueft das Singleton, ob bereits ein Exemplar dieser Klasse
* existiert. Existiert noch kein Exemplar, wird dieses erst noch instanziert,
* bevor eine Referenz darauf zurueckgegeben wird.
*
* Das Singleton wird somit der zentrale Zugriffspunkt auf Objekte, fuer die
* nur ein Exemplar existieren darf!
 *
 * Die Singleton-Klasse wird nicht direkt, sondern durch eine Funktion
 * Singleton angesprochen!
 *
 * Revision 1.1  2004/03/31 13:00:56  manhart
 * Initial Import
 *
 *
 * @version $Id: Singleton.class.php,v 1.4 2007/02/06 11:22:14 manhart Exp $
 * @version $Revision: 1.4 $
 *
 * @since 2004/03/30
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CLASS_SINGLETON')) {

    #### Prevent multiple loading
    define('CLASS_SINGLETON', 1);

    /**
     * Singleton
     *
     * Siehe Datei fuer ausfuehrliche Beschreibung!
     *
     * @package pool
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: Singleton.class.php,v 1.4 2007/02/06 11:22:14 manhart Exp $
     * @access public
     **/
    class Singleton
    {
        //@var array Speichert Instanzen zwischen (Cache)
        //@access private
        var $instances = array();

        /**
         * Die Funktion "instance" legt eine Instanz einer Klasse an.
         * Dabei koennen dem Konstruktor der Klasse noch Argumente uebergeben werden.
         *
         * @access public
         * @param string $class Klasse, die instanziert werden soll
         * @param array $args Argumentliste, wird beim Erzeugen der Instanz uebergeben
         * @return object Referenz auf die Instanz
         *
         * @throws ReflectionException
         */
        function instance($class, $args=array())
        {
            if(!isset($this->instances[$class]) or !is_object($this->instances[$class])) {

                if (count($args) > 0) {
                    $Refl = new ReflectionClass($class);
                    $this->instances[$class] = $Refl->newInstanceArgs($args);
                    // old method: eval("\$this->instances[\$class] = new $class($argumentlist);");
                }
                else {
                    $this->instances[$class] = new $class();
                }
            }

            $pointer = $this->instances[$class];
            return $pointer;
        }

        /**
         * Gibt eine existierende Instanz als Referenz zurueck
         *
         * @access public
         * @param string $class Klasse
         * @return object Referenz der Instanz
         **/
        function getInstance($class)
        {
            $pointer = null;
            if(isset($this->instances[$class])) {
                $pointer = $this->instances[$class];
            }
            return $pointer;
        }

        //function
    }

    /**
     * Singleton()
     *
     * Liefert uns ein Exemplar der Klasse $class.
     *
     * @param string $class Klasse der Instanz
     * @return object Referenz auf die Instanz
     **/
    function Singleton($class)
    {
        global $debug;
        static $Singleton = null;

        if (is_null($Singleton)) {
            if($debug === 1) echo 'Create new Singleton '.$class.'!';
            $Singleton = new Singleton();
        }

        $Instance = $Singleton->getInstance($class);
        if(is_null($Instance)) {
            $args = array();
            if (func_num_args() > 1) {
                $args = func_get_args();
                array_shift($args);
            }

            $Instance = $Singleton->instance($class, $args);
        }
        return $Instance;
    }
}