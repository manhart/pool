<?php
/**
* POOL (PHP Object Oriented Library): die Datei ExecFOP.class.php ist eine Wrapper Klasse f�r das Java Programm FOP (Formatting Objects Processor).
*
* Letzte �nderung am: $Date: 2006/08/07 11:37:03 $
*
* @version $Id: LinePrinterControl.class.php,v 1.2 2006/08/07 11:37:03 manhart Exp $
* @version $Revision: 1.2 $
* @version
*
* @since 2005-07-29
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
* @package pool
*/

use pool\classes\Core\PoolObject;

if(!defined('CLASS_LPC')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_LPC',			1);

    define('LPC_ACTION_CANCEL', 'cancel');
    define('LPC_ACTION_START', 'start'); // Cups enable
    define('LPC_ACTION_STOP', 'stop'); // Cups disable
    define('LPC_ACTION_ENABLE', 'enable'); // Cups accept
    define('LPC_ACTION_DISABLE', 'disable'); // Cups reject
    define('LPC_ACTION_ABORT', 'abort');
    define('LPC_ACTION_DOWN',	'down');
    define('LPC_ACTION_UP',	'up');
    define('LPC_ACTION_CLEAN', 'clean');
    define('LPC_ACTION_RESTART', 'restart');

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
    class LinePrinterControl extends PoolObject
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

        var $useRSH = false;

        function __construct($serverName, $printerName)
        {
            $this -> serverName = $serverName;
            $this -> printerName = $printerName;

            if($_SERVER['SERVER_NAME'] == $this -> serverName) {
                $this -> useSSH = false;
                $this -> useRSH = false;
            }
        }

        function setPrinterName($printerName)
        {
            $this -> printerName = $printerName;
        }

        function enableRSH()
        {
            $this -> useRSH = true;
            $this -> useSSH = false;
        }

        function enableSSH()
        {
            $this -> useRSH = false;
            $this -> useSSH = true;
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
            else if ($this -> useRSH) {
                $command = 'sudo rsh ' . $this -> serverName . ' ' . $command;
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
                case LPC_ACTION_CANCEL:
                    $this -> cancelJob(func_get_arg(1));
                    break;

                case LPC_ACTION_START:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc start ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_STOP:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc stop ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_ENABLE:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc enable ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_DISABLE:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc disable ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_UP:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc up ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_DOWN:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc down ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_ABORT:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc abort ' . $this -> printerName);
                    system($command);
                    break;

                case LPC_ACTION_CLEAN:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc clean ' . $this -> printerName);
                    exec($command);
                    break;

                case LPC_ACTION_RESTART:
                    $command = $this -> makeSSH_Command('/usr/sbin/lpc restart ' . $this -> printerName);
                    system($command);
                    break;

                default:
                    throw new Exception('Action: "' . $action . '" not known!');
             }
        }

        function cancelJob($idJob)
        {
            // $intResult = cups_cancel_job($this -> serverName, $this -> printerName, $idJob);
            $command = $this -> makeSSH_Command('/usr/bin/lprm -P' . $this -> printerName . ' ' . $idJob);
            exec($command);

            return $intResult;
        }

        function getPrinterList()
        {
            exec($this -> makeSSH_Command('lpstat -p'), $output);
            foreach($output as $val){
                [$dummy, $printer, $rest]= split(' ', $val, 3);
                if (preg_match('/^[^@]+$/', $printer)){
                    $result[]=$printer;
                }
            }
            sort($result);
            return $result;
        }

        function getPrinterJobs()
        {
            $result = array();
            $command = $this -> makeSSH_Command('/usr/bin/lpq -P' . $this -> printerName);

            $z=0;
            $begin=false;
            exec($command, $output);
            foreach($output as $line) {
                if(empty($line)) $begin = false;

                if($begin) {
                    [$Rank, $Pri, $Owner, $Job, $Files, $Total, $Size] = preg_split('/\s+/', $line);
                    $result[$z]['Rank'] = $Rank;
                    $result[$z]['Pri'] = $Pri;
                    $result[$z]['Owner'] = $Owner;
                    $result[$z]['Job'] = $Job;
                    $result[$z]['Files'] = $Files;
                    $result[$z]['Total'] = $Total;
                    $result[$z]['Size'] = $Size;
                    $z++;
                }
                if (substr($line, 0, 4) == 'Rank') {
                    $begin=true;
                }
            }

            return $result;
        }

        function getAttributes()
        {
            $attributes = array();
            $command = $this -> makeSSH_Command('/usr/sbin/lpc status ' . $this -> printerName);
            exec($command, $output);

            foreach($output as $line) {
                $line = trim($line);
                if($line == 'queuing is enabled') {
                    $attributes['printer-is-accepting-jobs'] = 1;
                }
                else if($line == 'queuing is disabled') {
                    $attributes['printer-is-accepting-jobs'] = 0;
                }

                if($line == 'printing is enabled') {
                    $attributes['printing'] = 1;
                }
                else if ($line == 'printing is disabled') {
                    $attributes['printing'] = 0;
                }

                if($line == 'no daemon present') {
                    $attributes['daemon'] = 0;
                }
                else if ($line == 'daemon present') {
                    $attributes['daemon'] = 1;
                }
                //echo $line . '<br>';
                if($pos=strpos($line, 'entries') !== false or $pos=strpos($line, 'entry') !== false) {
                    $attributes['queued-job-count'] = substr($line, 0, $pos+1);
                }
            }

            return $attributes;
        }
    }
}