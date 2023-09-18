<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * SubcodeResult.class.php
 *
 * @date $Date: 2007/02/16 07:46:03 $
 * @version $Id: SubcodeResult.class.php,v 1.5 2007/02/16 07:46:03 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

use pool\classes\Core\PoolObject;

if(!defined('CLASS_SUBCODERESULT'))
{
    define('CLASS_SUBCODERESULT', '1');

    /**
     * SubcodeResult
     *
     * @package pool
     * @author manhart
     * @version $Id: SubcodeResult.class.php,v 1.5 2007/02/16 07:46:03 manhart Exp $
     * @access public
     **/
    class SubcodeResult extends PoolObject
    {
        /**
         * Ergebnisliste
         *
         * @access private
         * @var array
         */
        var $resultlist = array();

        /**
         * Fehlerliste
         *
         * @access private
         * @var array
         */
        var $errorlist = array();

        /**
         * Abfrage des Status, ob alles ohne Fehler verlaufen ist.
         *
         * @access public
         * @return boolean
         **/
        function isOk()
        {
            return (count($this->errorlist) == 0);
        }

        /**
         * Fuegt einen aufgetretenen Fehler hinzu.
         *
         * @access public
         * @param string $value Fehlermeldung oder Anderes
         * @param string $key Schluessel (nicht notwendig)
         **/
        function addError($value, $key=NULL)
        {
            if ($key !== NULL) {
                $this -> errorlist[$key] = $value;
            }
            else {
                array_push($this -> errorlist, $value);
            }
        }

        /**
         * Fuegt einen Wert in die Ergebnismenge ein.
         *
         * @access public
         * @param string $value Wert
         * @param string $key Schluessel
         **/
        function addResult($value, $key=NULL)
        {
            if ($key !== NULL) {
                $this -> resultlist[$key] = $value;
            }
            else {
                array_push($this -> resultlist, $value);
            }
        }

        /**
         * Fuegt eine komplette Ergebnisliste ein.
         *
         * @access public
         * @param array $resultlist Ergebnisse
         **/
        function addResultList($resultlist)
        {
            $this -> resultlist = $this -> resultlist + $resultlist;
        }

        /**
         * Gibt die komplette Fehlerliste zurueck.
         *
         * @access public
         * @return array Fehlerliste
         **/
        function getErrorList()
        {
            return $this -> errorlist;
        }

        /**
         * Setzt die Fehlerliste (eventl. von Subcode zu Subcode notwendig).
         *
         * @param array $errorlist
         */
        function setErrorList($errorlist)
        {
            $this -> errorlist = $errorlist;
        }

        /**
         * Fehlerliste l�schen
         *
         */
        function clearErrorList()
        {
            $this -> errorlist=array();
        }

        function clearResultlist()
        {
            $this->resultlist = array();
        }

        /**
         * Gibt die komplette Ergebnisliste zurueck (z.B. Erfolgsmeldung, Stapelverarbeitungsmeldungen oder Status, etc.)
         *
         * @access public
         * @return array Ergebnisliste
         **/
        function getResultList()
        {
            return $this -> resultlist;
        }

        /**
         * Fraegt nach einem bestimmten Fehler ab.
         *
         * @param string $key Fehlernummer oder Fehlercode
         * @return string Fehler
         **/
        function getError($key=0)
        {
            return $this -> errorlist[$key];
        }

        /**
         * Fraegt nach einem bestimmten Ergebnis ab.
         *
         * @param string $key Schlussel oder Identcode
         * @return string Ergebnis
         **/
        function getResult($key=NULL)
        {
            if (is_null($key)) {
                $keys = array_keys($this -> resultlist);
                $anz_keys = count($keys);
                $key = ($keys[$anz_keys-1]);
            }
            return $this -> resultlist[$key];
        }
    }
}