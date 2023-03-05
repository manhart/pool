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

use pool\classes\Core\Component;
use pool\classes\Core\Module;

/**
 * Subcode
 *
 * Subcodes sind einzeln wiederverwendbare Programmteile ohne graphische Elemente.
 * Ein Subcode erledigt eine bestimmte Aufgabe und liefert ein Ergebnis.
 *
 * @package pool
 * @author manhart <alexander@manhart-it.de>
 * @version $Id: Subcode.class.php,v 1.5 2006/10/20 08:44:20 manhart Exp $
 **/
class Subcode extends Module
{
    /**
     * @var Input $Input
     **/
    public Input $Input;

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
    function __construct(&$Owner)
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
     * @param Component|null $Owner Besitzer
     * @return Subcode Instanz
     */
    static function createSubcode(string $class_name, ?Component $Owner): Subcode
    {
        if(class_exists($class_name)) {
            return new $class_name($Owner);
        }
        else {
            die($class_name . ' does not exist!');
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
        $this->Input->mergeVars($Input, false);
    }

    /**
     * Fuehrt den Subcode (die Aufgabe) aus.
     * (muss ueberschrieben werden)
     *
     * @method virtual
     * @return object SubcodeResult
     **/
    public function execute()
    {
        return $this->getResult();
    }

    /**
     * Ergebnis des Subcodes.
     *
     * @return SubcodeResult|null SubcodeResult
     * @see SubcodeResult
     **/
    public function getResult(): ?SubcodeResult
    {
        return $this->SubcodeResult;
    }
}