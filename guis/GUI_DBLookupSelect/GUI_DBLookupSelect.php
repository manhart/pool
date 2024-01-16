<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Input\Input;
use pool\classes\Database\DAO;

/**
 * Class GUI_DBLookupSelect
 * @package pool\guis\GUI_DBLookupSelect
 * @since 2004-02-12
 */
class GUI_DBLookupSelect extends GUI_Select
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
    function init(?int $superglobals= Input::EMPTY)
    {
        $this->Defaults->addVar('tabledefine', []);
        $this->Defaults->addVar('keyValue', false); 	// separated by ;
        $this->Defaults->addVar('keyField'); 	// separated by ;
        $this->Defaults->addVar('keyOperator', 'equal');
        $this->Defaults->getVar('filter', array());
        $this->Defaults->addVar('autoload_fields', 1);
        $this->Defaults->addVar('pk'); 		// separated by ;
        $this->Defaults->addVar('columns'); // separated by ;
        $this->Defaults->addVar('listfieldSeparator', ' ');
        $this->Defaults->addVar('listfield');
        $this->Defaults->addVar('datafield');
        $this->Defaults->addVar('sortfield');
        $this->Defaults->addVar('shorten', 0);
        $this->Defaults->addVar('utf8', 0);

        parent::init($superglobals);
    }

    /**
     * @return void
     */
    public function prepare (): void
    {
        $Input = & $this -> Input;

        $utf8 = $Input->getVar('utf8');

        $tableDefine = $this->Input->getVar('tabledefine');
        if(is_array($tableDefine)) {
            /** @var array{1: DAO, 0: string} $tableDefine */
            $DAO = $tableDefine[1]::create(databaseName: $tableDefine[0]);
        }
        else
            $DAO = DAO::createDAO($tableDefine);

        # filter
        $filter = $Input->getVar('filter');

        if(!is_array($filter)) $filter=array();
        $keyField = $Input -> getVar('keyField');
        $keyValue = $Input -> getVar('keyValue');
        $keyOperator = $Input -> getVar('keyOperator');
        $shorten = $Input -> getVar('shorten');

        if($keyField and $keyValue !== false) {
            if(str_contains($keyField, ';') or is_array($keyField)) {
                if(is_string($keyField)) $keyFields = explode(';', $keyField);
                if(is_string($keyValue)) $keyValues = explode(';', $keyValue);
                if(is_string($keyOperator)) $keyOperators = explode(';', $keyOperator);
                for($i=0; $i<sizeof($keyFields); $i++) {
                    $keyField = $keyFields[$i];
                    $keyValue = $keyValues[$i];
                    $keyOperator = $keyOperators[$i] ?? 'equal';
                    $filter[] = array($keyField, $keyOperator, $keyValue);
                }
            }
            else {
                $filter[] = array($keyField, $keyOperator, $keyValue);
            }
        }
        #echo pray($filter);
        $sorting = null;
        if ($sortfield = $Input -> getVar('sortfield')) {
            $sorting = array($sortfield => 'ASC');
        }
        $listfield = $Input->getVar('listfield');
        $datafield = $Input->getVar('datafield');
        if(empty($listfield)) $listfield = $datafield;
        if(empty($datafield)) $datafield = $listfield;

        if($listfield != $datafield) {
            $DAO->setColumnsAsString($listfield . ';' . $datafield);
        }
        else {
            $DAO->setColumnsAsString($listfield);
        }

        $Resultset = $DAO->getMultiple(null, null, $filter, $sorting);
        #echo pray($Resultset);
        $rowset = $Resultset -> getRaw();

        $listfields = explode(',', $listfield);
        $listfieldSeparator = $this->Input->getVar('listfieldSeparator');

        $options = array();
        $values = array();
        if (count($rowset)) {
            foreach($rowset as $record) {
                $option = '';
                foreach ($listfields as $field) {
                    if($option != '') $option .= $listfieldSeparator;
                    $option .= $record[$field];
                }

                if($shorten > 0) {
                    $option = shorten($option, $shorten, 1, false);
                }
                $value = $record[$datafield];
                $options[] = ($utf8) ? UConverter::transcode($option, 'UTF8', 'ISO-8859-1') : $option;
                $values[] = ($utf8) ? UConverter::transcode($value, 'UTF8', 'ISO-8859-1') : $value;
            }
        }
        $defaultOptions = $Input -> getVar('options');
        if(!is_array($defaultOptions)) $defaultOptions = explode(';', $defaultOptions);
        $defaultValues = $Input -> getVar('values');
        if(!is_array($defaultValues)) $defaultValues = explode(';', $defaultValues);

        $options = array_merge($defaultOptions, $options);
        $values = array_merge($defaultValues, $values);

        $Input->setVar('options', $options);
        $Input->setVar('values', $values);

        parent :: prepare();
    }
}