<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if(!defined('CLASS_CUPSPRINTER')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_CUPSPRINTER',			1);

    define('CUPSPRINTER_ACTION_CANCEL', 'cancel');
    define('CUPSPRINTER_ACTION_ENABLE', 'enable');
    define('CUPSPRINTER_ACTION_DISABLE', 'disable');
    define('CUPSPRINTER_ACTION_ACCEPT', 'accept');
    define('CUPSPRINTER_ACTION_REJECT',	'reject');

    /**
     * Wrapper Klasse f�r das Java FOP (Formatting Objects Processor) Programm
     *
     * Mit fo (formating objects) ist es simple XML-Dokumente in PDF-Dokumente zu konvertieren. Au�er PDF werden noch
     *  ps, pcl und andere unterst�tzt.
     *
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
     * @package pool
     */
    class CupsPrinter extends PoolObject
    {
        /**
         * Server
         *
         * @access private
         * @var string
         */
        var $serverName = '';

        /**
         * Drucker
         *
         * @access private
         * @var string
         */
        var $printerName = '';

        /**
         * Handelt es sich um einen entfernten Rechner (REMOTE), muss SSH benutzt werden, um die Kommandos auszuf�hren.
         *
         * @var bool
         */
        var $useSSH = true;

        function __construct($serverName, $printerName)
        {
            $this -> serverName = $serverName;
            $this -> printerName = $printerName;

            if($_SERVER['SERVER_NAME'] == $this -> serverName) {
                $this -> useSSH = false;
            }
        }


        /**
         * Erstellt SSH Kommando f�r Kommandos auf entfernten Rechnern.
         *
         * @param string $command
         * @return string
         */
        function makeSSH_Command($command)
        {
            if($this -> useSSH) {
                $command = 'sudo ssh ' . $this -> serverName . ' -l root ' . $command;
            }
            return $command;
        }

        /**
         * F�hrt entsprechende Aktion aus.
         *
         * @param string $renderer Rendertyp (Standard: pdf)
         * @see $renderer
         * @access public
         */
        function perform($action)
        {
            switch ($action) {
                case CUPSPRINTER_ACTION_CANCEL:
                    $this -> cancelJob(func_get_arg(1));
                    break;

                case CUPSPRINTER_ACTION_ENABLE:
                    $command = $this -> makeSSH_Command('/usr/bin/enable ' . $this -> printerName);
                    system($command);
                    break;

                case CUPSPRINTER_ACTION_DISABLE:
                    $command = $this -> makeSSH_Command('/usr/bin/disable ' . $this -> printerName);
                    system($command);
                    break;

                case CUPSPRINTER_ACTION_ACCEPT:
                    $command = $this -> makeSSH_Command('/usr/sbin/accept ' . $this -> printerName);
                    system($command);
                    break;

                case CUPSPRINTER_ACTION_REJECT:
                    $command = $this -> makeSSH_Command('/usr/sbin/reject ' . $this -> printerName);
                    system($command);
                    break;

                default:
                    $Xception = new Xception('Action: "' . $action . '" not known!');
                    PoolObject::throwException($Xception);
                    break;
             }
        }

        function cancelJob($idJob)
        {
            // $intResult = cups_cancel_job($this -> serverName, $this -> printerName, $idJob);
            $command = $this -> makeSSH_Command('/usr/bin/cancel ' . $idJob);
            $result = system($command);

            return $result;
        }

        function getAttributes()
        {
            $tmp_attributes = cups_get_printer_attributes($this -> serverName, $this -> printerName);
            $attributes = array();

            foreach ($tmp_attributes as $Attribute) {
                $attributes[$Attribute -> name] = $Attribute -> value;
            }
            return $attributes;
        }
    }
}