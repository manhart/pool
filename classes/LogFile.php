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

use pool\classes\Core\PoolObject;

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
 * @author Alexander Manhart <alexander@manhart-it.de>
 */
class LogFile extends PoolObject
{
    /**
     * Datei
     *
     * @var string $file
     */
    private readonly string $file;

    private string $mode = 'ab';

    /**
     * Datei-Resource
     * @var resource $fp
     */
    private mixed $fp;

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
    protected $enableCache = false;

    private int $sid = 0;
    private bool $withSID = true;

    /**
     * Format des Zeitstempels
     *
     * @var string
     */
    private string $formatDateTime = '[d.m.Y H:i:s]';

    /**
     * Konstruktor
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        try {
            $this->sid = random_int(0, 9999);
        }
        catch(Exception $e) {
            $this->sid = 0;
        }
    }

    /**
     * @param bool $rotateByDate
     */
    function activateLogRotate($rotateByDate = false)
    {
        $this->logRotate = true;
        $this->rotateByDate = $rotateByDate;
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
     * Open the file
     *
     * @return bool
     */
    private function open(): bool
    {
        if(file_exists($this->file)) {
            $filesize = filesize($this->file);
            if($this->logRotate) {
                if($this->rotateByDate) {
                    $newDate = (floor(filemtime($this->file) / 86400) !== floor(time() / 86400));
                    if($newDate) $this->rotate($this->file);
                }
                else if($filesize > $this->maxFileSize) {
                    $this->rotate($this->file);
                }
                // $mode = 'w';
            }
        }
        $this->fp = fopen($this->file, $this->mode);
        return $this->opened();
    }

    /**
     * Rotiert das Logfile
     *
     * @param string $file
     */
    function rotate($file)
    {
        $reOpen = false;
        if($this->opened()) {
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
            $this->fp = fopen($this->file, 'ab');
        }
    }

    /**
     * Logging aktiviert? bzw. File geoeffnet
     *
     * @return boolean
     */
    private function opened(): bool
    {
        return isset($this->fp) && is_resource($this->fp);
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
     * @return LogFile
     */
    public function setFormatDateTime(string $format='[d.m.Y H:i:s]'): static
    {
        $this->formatDateTime = $format;
        return $this;
    }

    /**
     * Creating Formatted Initial Strings
     *
     * @return string
     */
    protected function generateFormattedInitialLogString(): string
    {
        try {
            $microTime = microtime(true);
            $microseconds = sprintf('%06d', ($microTime - floor($microTime)) * 1000000);
            $formattedDateTime = (new DateTime(date('Y-m-d H:i:s').".$microseconds"))->format($this->formatDateTime);
        }
        catch(Exception) {
            $formattedDateTime = (string)time();
        }
        return $formattedDateTime. (($this->withSID) ? $this->separator . '{' .
            sprintf('%04d', $this -> sid) . '}' : '').$this->separator.'%s';
    }

    /**
     * Adds a log entry.
     *
     * @param string $text
     */
    public function addLine(string $text): false|int
    {
        if(!$this->opened()) {
            $this->open();
        }
        $text = sprintf($this->generateFormattedInitialLogString(), $text);
        if($this->enableCache) {
            $this->cache[] = $text;
        }
        $text .= $this->lineFeed;

        return fwrite($this->fp, $text);
    }

    /**
     * Close the file
     */
    public function close(): void
    {
        $this->cache = [];
        if($this->opened()) {
            fclose($this->fp);
        }
        $this->fp = null;
    }
}