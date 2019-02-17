<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class DataInterface ist die abstrakte Basis Klasse fuer
* alle Speichermedien (z.B. MySQL).
* Diese Klasse vereinheitlicht fuer Alle das Setzen von
* Einstellungen (DataInterface::setOptions) und das
* Instanzieren eines DataInterfaces
* (DataInterface::createDataInterface).
*
* Dabei hat jedes Medium seine eigenen Einstellungen. Diese
* werden in Form eines Pakets uebermittelt. Die jeweiligen
* Optionen des Pakets sind in der entsprechenden Klasse
* nachzulesen.
*
* Verfuegbare DataInterface Typen (Konstanten, siehe database.inc.php):
* - DATAINTERFACE_MYSQL
* - DATAINTERFACE_CISAM
*
* $Log: DataInterface.class.php,v $
* Revision 1.1.1.1  2004/09/21 07:49:25  manhart
* initial import
*
* Revision 1.2  2004/04/01 15:11:02  manhart
* Interface Types implemented
*
* Revision 1.1  2004/04/01 07:27:36  manhart
* Initial Import
*
*
* @version $Id: DataInterface.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
* @version $Revision: 1.1.1.1 $
*
* @see MySQL_db.class.php
* @see CISAM_client.class.php
* @since 2004/03/31
* @author Alexander Manhart <alexander@manhart.bayern>
* @link https://alexander-manhart.de
*/

if(!defined('CLASS_DATAINTERFACE')) {

    #### Prevent multiple loading
    define('CLASS_DATAINTERFACE', 1);

    /**
     * DataInterface
     *
     * Siehe Datei fuer ausfuehrliche Beschreibung!
     *
     * @package pool
     * @author manhart
     * @version $Id: DataInterface.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
     * @access public
     **/
    class DataInterface extends Object
    {
        //@var string Interface Typ
        //@access private
        var $InterfaceType = '';

        /**
         * DataInterface::setInterfaceType()
         *
         * Setzt den Interface Typ (= Klasse)
         *
         * @access private
         * @param string $InterfaceType Interface Typ
         **/
        function setInterfaceType($InterfaceType)
        {
            $this -> InterfaceType = $InterfaceType;
        }

        /**
         * DataInterface::getInterfaceType()
         *
         * Gibt den Interface Typ (= Klasse) zurueck
         *
         * @return string Interface Typ
         **/
        function getInterfaceType()
        {
            return $this -> InterfaceType;
        }

        /**
         * DataInterface::setOptions()
         *
         * Setzt Optionen (Einstellungen) fuer die Schnittstelle
         * zum Speichermedium (z.B. host).
         * Uebergabe der Einstellungen als Paket (=array).
         *
         * @abstract
         * @param array $Packet Paket mit den Einstellungen (Key=Optionsname, Value=Optionswert)
         **/
        function setOptions($Packet)
        {
        }

        /**
         * Legt eine Instanz des DataInterfaces an. Setzt ueber
         * ein Paket alle relevanten Einstellungen fuer die
         * Schnittstelle (Verbindung).
         *
         * @param string $InterfaceType Interface Typ
         * @param array $Packet Optionen
         * @return object DataInterface
         **/
        public static function &createDataInterface($InterfaceType, $Packet)
        {
            /** @var DataInterface $DataInterface */
            $DataInterface = new $InterfaceType();
            $DataInterface->setInterfaceType($InterfaceType);
            $DataInterface->setOptions($Packet);
            return $DataInterface;
        }
    }
}