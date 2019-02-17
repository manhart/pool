<?php
/**
 * POOL (PHP Log Oriented Library): die Datei Log.class.php enthält die Klasse Log zum Loggen von Ablaeufen in Dateien.
 *
 * Letzte aenderung am: $Date: 2006/02/21 10:47:29 $
 *
 * @version $Id: Log.class.php,v 1.6 2006/02/21 10:47:29 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2005-06-16
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 * @package pool
 */

if(!defined('CLASS_LOG')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_LOG',			1);
    /**
     * Die Grundklasse, der Uhrahn aller Objekte.
     *
     * Die Klasse Log verf�gt �ber folgende Verhalten:
     * - stellt eine Art Debug-Modus bereit.
     * - Objektinstanzen erzeugen, verwalten und aufl�sen.
     * - auf objektspezifische Informationen �ber den Klassentyp und die Instanz zugreifen.
     * - enth�lt Fehler�berpr�fung und kann Fehler ausl�sen.
     * - stellt ein Verfahren bereit mit dem ein Inhalt eines Objekts einem anderen zugewiesen werden kann.
     *
     * Log wird nie direkt instantiiert. Obwohl keine Programmiersprachenelemente zum Verhindern der Instantiierung verwendet werden, ist Log eine abstrakte Klasse.
     *
     * @access public
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @package pool
     */
    class Log extends Object
    {
        /**
         * Datei
         *
         * @access private
         * @var string $file
         */
        var $file='';

        /**
         * Datei-Resource
         *
         * @access private
         * @var resource $fp
         */
        var $fp=null;

        /**
         * Logfiles rotieren (Standard ausgeschaltet)
         *
         * @access private
         * @var boolean
         */
        var $logRotate=false;

        /**
         * Maximale Dateigr��e des Logfiles (standard 2 MB)
         *
         * @access private
         * @var string
         */
        var $maxFileSize=2097152;

        /**
         * Rotiert die Datei beginnend mit einem neuen Tag
         *
         * @var boolean
         */
        var $rotateByDate = false;

        /**
         * Trennzeichen
         *
         * @access private
         * @var string
         */
        var $separator = "\t";

        /**
         * Zeilenumbruch
         *
         * @access private
         * @var string
         */
        var $lineFeed = "\n";

        /**
         * Speichert die geschriebenen Zeilen zwischen
         *
         * @var array
         */
        var $cache = array();

        /**
         * Cache aktiviert
         *
         * @var boolean
         */
        var $enableCache = false;

        var $sid = 0000;
        var $withSID = true;

        /**
         * Format des Zeitstempels
         *
         * @var string
         */
        var $formatDateTime = '[%d.%m.%Y %H:%M:%S]';

        /**
         * Konstruktor
         *
         * @access public
         */
        function Log()
        {
            $this->sid = rand(0, 9999);
        }

        function activateLogRotate()
        {
            $this->logRotate = true;
        }

        /**
         * Deaktiviert Log-Session
         *
         */
        function disableSID()
        {
            $this->withSID = false;
        }

        /**
         * �ffnet Logfile
         *
         * @param string $file
         */
        function open($file, $mode='a')
        {
            $this->file = $file;
            if(file_exists($file)) {
                $filesize = filesize($file);
                if($this->logRotate) {
                    if($this->rotateByDate) {
                        $newDate = (floor(filemtime($file)/86400) != floor(time()/86400));
                        if($newDate) $this->rotate($file);
                    }
                    else if($filesize > $this->maxFileSize) {
                        $this->rotate($file);
                    }
                    // $mode = 'w';
                }
            }
            else {
                // $mode = 'w';
            }
            $this->_open($mode);
        }

        /**
         * Rotiert das Logfile
         *
         * @param string $file
         */
        function rotate($file)
        {
            $reOpen = false;
            if($this->isLogging()) {
                $reOpen = true;
                $this->close();
            }

            $nr = 1;
            while(file_exists($file.'.'.$nr . '.gz.tar')) {
                $nr++;
            }
            $Tar = new Tar();
            if (version_compare(phpversion(), '4.3.0', '>=')) {
                $Tar->addData(basename($file), file_get_contents($file));
            }
            else {
                $Tar->addFile($file);
            }
            $Tar->toTar($file.'.'.$nr.'.gz.tar', true);
            @unlink($file);

            if($reOpen) {
                $this->_open();
            }
        }

        /**
         * �ffnet die Datei zum Schreiben
         *
         * @param string $mode
         */
        function _open($mode='a')
        {
            $this->fp = fopen($this->file, $mode);
        }

        /**
         * Logging aktiviert? bzw. File geoeffnet
         *
         * @return boolean
         */
        function isLogging()
        {
            return ($this->fp != null);
        }

        /**
         * Setzt die maximale Dateigroesse
         *
         * @param int $fileSize
         */
        function setMaxFileSize($fileSize=2097152)
        {
            $this->maxFileSize = $fileSize;
        }

        /**
         * Trennzeichen (Standard \t)
         *
         * @param string $separator
         */
        function setSeparator($separator)
        {
            $this->separator = $separator;
        }

        /**
         * Zeilenumbruch setzen (Standard Unix \n)
         *
         * @param string $lineFeed
         */
        function setLineFeed($lineFeed)
        {
            $this->lineFeed = $lineFeed;
        }

        /**
         * Setzt das Format f�r den Zeitstempel
         *
         * @param string $format
         */
        function setFormatDateTime($format='[%d.%m.%Y %H:%M:%S]')
        {
            $this->formatDateTime = $format;
        }

        /**
         * Zeilenformat-Anfang
         *
         * @return string
         */
        function __lineFormat()
        {
            return formatDateTime(time(), $this->formatDateTime) . (($this->withSID) ? $this->separator . '{' .
                sprintf('%04d', $this -> sid) . '}' : '').$this->separator.'%s';
        }

        /**
         * Ergaenzt einen Logeintrag.
         *
         * @param string $text
         * @param string $separator
         */
        function addLine($text)
        {
            $text = sprintf($this->__lineFormat(), $text);
            if($this->enableCache) {
                $this->cache[] = $text;
            }
            $text .= $this->lineFeed;

            $result = fwrite($this->fp, $text, strlen($text));
        }

        /**
         * Logfile schlie�en
         */
        function close()
        {
            $this->cache = array();
            fclose($this->fp);
            $this->fp = null;
        }
    }
}