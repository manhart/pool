<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dbedit.class.php
 *
 * Das GUI DBEdit ist ein Datenbank Control. Es steuert ein Eingabefeld (extends GUI_Edit).
 * Uebergibt man die Parameter "tabledefine" und "id" korrekt, fuellt sich GUI_DBEdit selbst aus der Datenbank.
 *
 * Benoetigt:
 * DAO (Data Access Objects)
 *
 * @version $Id: gui_dbedit.class.php 38772 2019-09-30 09:31:12Z manhart $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004-02-10
 * @author alexander manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

/**
 * GUI_DBEdit
 *
 * GUI_DBEdit fuellt ein Eingabefeld (<input type=text>) mit einem Datenbankwert.
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dbedit.class.php 38772 2019-09-30 09:31:12Z manhart $
 **/
class GUI_DBEdit extends GUI_Edit
{
    /**
     * Initialisiert Standardwerte:
     *
     * tabledefine 		= ''	Tabellendefinition (siehe database.inc.php)
     * id				= 0		IDs (bei zusammengesetzten Primaerschluessel werden die IDs mit ; getrennt)
     * key				= ''	Keys (bei zusammengesetzten Primaerschluessel werden die Keys mit ; getrennt)
     * autoload_fields 	= 1		1 laedt automatisch alle Felder, 0 nicht
     * pk				= ''	Primaerschluessel (mehrere Spaltennamen werden mit ; getrennt)
     * columns			= ''	Auszulesende Spalten (Spaltennamen werden mit ; getrennt)
     *
     * @access public
     **/
    function init(?int $superglobals = I_EMPTY)
    {
        $this -> Defaults -> addVar('tabledefine', '');
        $this -> Defaults -> addVar('id', 0); 	// separated by ;
        $this -> Defaults -> addVar('key', ''); 	// separated by ;
        $this -> Defaults -> addVar('autoload_fields', 1);
        $this -> Defaults -> addVar('pk', ''); 		// separated by ;
        // $this -> Defaults -> addVar('columns', ''); // separated by ;

        parent :: init($superglobals);
    }

    /**
     * GUI_DBEdit::prepare()
     *
     * Liest Daten aus der Datenbank und legt bei Erfolg den Wert des Feldes im Input ab.
     * Anschliessend wird der Parent GUI_Edit aufgerufen und setzt die Werte fuer das Eingabefeld.
     **/
    public function prepare ()
    {
        $Subcode = Subcode::createSubcode('DataRecordSubcode', $this->getOwner());
        $Subcode->import($this->Input);
        $Subcode->setVar('columns', $this->Input->getVar('name'));
        $SubcodeResult = & $Subcode -> execute();
        if ($SubcodeResult->isOk()) {
            $resultlist = $SubcodeResult -> getResultList();
            $name = $this->Input->getVar('name');
            $this->Input->setVar('value', $resultlist[0][$name]);
        }
        else {
            // $result -> getErrorList();
        }

        parent :: prepare();
    }
}