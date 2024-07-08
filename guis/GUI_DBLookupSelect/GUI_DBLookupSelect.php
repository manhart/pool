<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

use pool\classes\Core\Input\Input;
use pool\classes\Database\DAO;

/**
 * Class GUI_DBLookupSelect
 *
 * @package pool\guis\GUI_DBLookupSelect
 * @since 2004-02-12
 */
class GUI_DBLookupSelect extends GUI_Select
{
    /**
     * Initialisiert Standardwerte:
     * tabledefine        = ''    Tabellendefinition (siehe database.inc.php)
     * id                = 0        IDs (bei zusammengesetzten Primaerschluessel werden die IDs mit ; getrennt)
     * key                = ''    Keys (bei zusammengesetzten Primaerschluessel werden die Keys mit ; getrennt)
     * autoload_fields    = 1        1 laedt automatisch alle Felder, 0 nicht
     * pk                = ''    Primaerschluessel (mehrere Spaltennamen werden mit ; getrennt)
     * columns            = ''    Auszulesende Spalten (Spaltennamen werden mit ; getrennt)
     **/
    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVar('tabledefine', []);
        $this->Defaults->addVar('keyValue', false);    // separated by ;
        $this->Defaults->addVar('keyField');    // separated by ;
        $this->Defaults->addVar('keyOperator', 'equal');
        $this->Defaults->getVar('filter', []);
        $this->Defaults->addVar('autoload_fields', 1);
        $this->Defaults->addVar('pk');        // separated by ;
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
    protected function prepare(): void
    {
        $tableDefine = $this->Input->getVar('tabledefine');
        /** @var array{1: DAO, 0: string} $tableDefine */
        $dao = $tableDefine[1]::create(databaseName: $tableDefine[0]);

        $filter = (array)$this->Input->getVar('filter');
        $keyField = $this->Input->getVar('keyField');
        $keyValue = $this->Input->getVar('keyValue');
        $keyOperator = $this->Input->getVar('keyOperator');
        $shorten = $this->Input->getVar('shorten');

        if($keyField && $keyValue !== false) {
            if(!str_contains($keyField, ';') && !is_array($keyField)) {
                $filter[] = [$keyField, $keyOperator, $keyValue];
            }
            else {
                if(is_string($keyField)) $keyFields = explode(';', $keyField);
                if(is_string($keyValue)) $keyValues = explode(';', $keyValue);
                if(is_string($keyOperator)) $keyOperators = explode(';', $keyOperator);
                $count = count($keyFields);
                for($i = 0; $i < $count; $i++) {
                    $keyField = $keyFields[$i];
                    $keyValue = $keyValues[$i];
                    $keyOperator = $keyOperators[$i] ?? 'equal';
                    $filter[] = [$keyField, $keyOperator, $keyValue];
                }
            }
        }

        $sorting = null;
        if($sortfield = $this->Input->getVar('sortfield')) {
            $sorting = [$sortfield => 'ASC'];
        }
        $listfield = $this->Input->getVar('listfield');
        $datafield = $this->Input->getVar('datafield');
        if(empty($listfield)) $listfield = $datafield;
        if(empty($datafield)) $datafield = $listfield;

        if($listfield != $datafield) {
            $dao->setColumnsAsString("$listfield;$datafield");
        }
        else {
            $dao->setColumnsAsString($listfield);
        }

        $listfields = explode(',', $listfield);
        $listfieldSeparator = $this->Input->getVar('listfieldSeparator');

        $options = [];
        $values = [];
        $raw = $dao->getMultiple(filter: $filter, sorting: $sorting)->getRaw();
        foreach($raw as $record) {
            $option = '';
            foreach($listfields as $field) {
                if($option != '') $option .= $listfieldSeparator;
                $option .= $record[$field];
            }

            if($shorten > 0) {
                $option = shorten($option, $shorten, 1, false);
            }
            $value = $record[$datafield];
            $options[] = $option;
            $values[] = $value;
        }
        $defaultOptions = $this->Input->getVar('options');
        if(!is_array($defaultOptions)) $defaultOptions = explode(';', $defaultOptions);
        $defaultValues = $this->Input->getVar('values');
        if(!is_array($defaultValues)) $defaultValues = explode(';', $defaultValues);

        $options = array_merge($defaultOptions, $options);
        $values = array_merge($defaultValues, $values);

        $this->Input->setVar('options', $options);
        $this->Input->setVar('values', $values);

        parent::prepare();
    }
}