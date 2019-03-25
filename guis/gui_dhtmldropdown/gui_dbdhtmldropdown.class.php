<?php
/**
 * -= PHP Object Oriented Library =-
 *
 * gui_dbdhtmldropdown.class.php
 *
 *
 *
 * @version $Id: gui_dbdhtmldropdown.class.php,v 1.2 2005/06/14 11:44:46 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2004/08/03
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DBDHTMLDropdown
 *
 * @package pool
 * @author Alexander Manhart <misterelsa@gmx.de>
 * @version $Id: gui_dbdhtmldropdown.class.php,v 1.2 2005/06/14 11:44:46 manhart Exp $
 * @access public
 **/
class GUI_DBDHTMLDropdown extends GUI_DHTMLDropdown
{
    /**
     * GUI_DBDHTMLDropdown::init()
     *
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'tabledefine' => '',
                'datafield' => '',
                'sortfield' => ''
            )
        );
        parent :: init($superglobals);
    }

    /**
     * GUI_DBDHTMLDropdown::prepare()
     *
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $interfaces = & $this -> Weblication -> getInterfaces();
        $Input = & $this -> Input;

        $DAO = & DAO::createDAO($interfaces, $Input -> getVar('tabledefine'));
        $filter = array();
        $sorting = null;
        if ($sortfield = $Input -> getVar('sortfield')) {
            $sorting = array($sortfield => 'ASC');
        }
        $DAO -> setColumnsAsString($Input -> getVar('datafield'));
        $Resultset = & $DAO -> getMultiple(null, null, $filter, $sorting);
        $rowset = $Resultset -> getRowset();
        if (count($rowset)) {
            foreach($rowset as $record) {
                $list[] = $record[$Input -> getVar('datafield')];
            }
        }
        $Input -> setVar('list', $list);

        parent::prepare();
    }
}