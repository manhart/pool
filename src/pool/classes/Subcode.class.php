<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * Subcode.class.php
 *
 * @date $Date: 2006/10/20 08:44:20 $
 * @version $Id: Subcode.class.php,v 1.5 2006/10/20 08:44:20 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CLASS_SUBCODE'))
{
    define('CLASS_SUBCODE', '1');

    /**
     * Subcode
     *
     * Subcodes sind einzeln wiederverwendbare Programmteile ohne graphische Elemente.
     * Ein Subcode erledigt eine bestimmte Aufgabe und liefert ein Ergebnis.
     *
     * @package pool
     * @author manhart <alexander.manhart@freenet.de>
     * @version $Id: Subcode.class.php,v 1.5 2006/10/20 08:44:20 manhart Exp $
     * @access public
     **/
    class Subcode extends Module
    {
        /**
         * @var Input $Input
         * @access private
         **/
        var $Input = null;

        /**
         * @var SubcodeResult $SubcodeResult
         * @access private
         **/
        var $SubcodeResult = null;

        /**
         * Konstruktor
         *
         * @access public
         * @param constant $superglobals Input Konstanten fuer die Superglobals
         * @see Input
         **/
        function __construct(& $Owner)
        {
            parent::__construct($Owner);

            $this->SubcodeResult = new SubcodeResult();
        }

        /**
         * Legt eine neue Aufgabe (Subcode) an.
         *
         * @method static
         * @access public
         * @param string $class_name Name der Klasse
         * @param object $Owner Besitzer
         * @return object Instanz
         **/
        static function &createSubcode($class_name, &$Owner)
        {
            if (class_exists($class_name)) {
                $Subcode = new $class_name($Owner);
                return $Subcode;
            }
            else{
                die($class_name.' does not exist!');
            }
        }

        /**
         * Importiert die Daten in das Input Objekt.
         *
         * @access public
         * @param object $Input Input Objekt
         **/
        function import($Input)
        {
            $this -> Input -> mergeVars($Input, false);
        }

        /**
         * Subcode::setVar()
         *
         * @access public
         * @param string|array $key Schlussel oder Array Element
         * @param string $value Wert
         **/
        function setVar($key, $value='')
        {
            if (is_array($key)) {
                $this -> Input -> setVar($key);
            }
            else {
                $this -> Input -> setVar($key, $value);
            }
        }

        /**
         * Fuehrt den Subcode (die Aufgabe) aus.
         * (muss ueberschrieben werden)
         *
         * @method virtual
         * @access protected
         * @return object SubcodeResult
         **/
        function &execute()
        {
            return $this->getResult();
        }

        /**
         * Subcode::getResult()
         *
         * Ergebnis des Subcodes.
         *
         * @return object SubcodeResult
         * @see SubcodeResult
         **/
        function &getResult()
        {
            return $this->SubcodeResult;
        }
    }
}