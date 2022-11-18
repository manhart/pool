<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dbcount.class.php
 *
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_dbcount.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 */

/**
 * GUI_DBCount
 *
 * GUI_DBCount fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dbcount.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_DBCount extends GUI_Module
{
    var $returnValue = '';

    public function __construct(&$Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, $params);
    }

    function loadFiles()
    {
    }

    /**
     * GUI_DBCount::init()
     *
     * Initialisiert Standardwerte:
     *
     * tabledefine 		= ''	Tabellendefinition (siehe database.inc.php)
     * fk				= ''	Name Fremdschluessel
     * fk_value			= ''	Wert fuer Fremdschluessel
     * text				= ''	Anzuzeigender Text: Count/Anzahl wird mit printf ueber %s eingesetzt
     *
     * @access public
     **/
    function init(?int $superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('tabledefine', '');
        $this -> Defaults -> addVar('fk', '');
        $this -> Defaults -> addVar('fk_value', '');
        $this -> Defaults -> addVar('text', '');

        parent :: init(I_GET);
    }

    /**
     * @access public
     **/
    function prepare ()
    {
        $interfaces = $this -> Weblication -> getInterfaces();
        $Input = & $this -> Input;

        $tabledefine = $Input -> getVar('tabledefine');
        if ($tabledefine) {
            $count = 0;
            if ($Input -> getVar('fk_value') > 0) {
                $DAO = DAO::createDAO($interfaces, $tabledefine);
                $Resultset = $DAO -> getCount(NULL, NULL, array(array($Input -> getVar('fk'), 'equal', $Input -> getVar('fk_value'))));
                $count = $Resultset -> getValue('count');
            }
            $text = $Input -> getVar('text');
            $text = sprintf($text, (int)$count);
            $this -> returnValue = $text;
        }
        else {
            $this -> returnValue = 'Error: No Tabledefine';
        }
    }

    /**
     * GUI_DBCount::finalize()
     *
     * Inhalt parsen und zurueck geben (revive).
     *
     * @return string
     **/
    function finalize(): string
    {
        return $this -> returnValue;
    }
}