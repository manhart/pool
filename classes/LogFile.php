<?php
/**
 * POOL (PHP Log Oriented Library): die Datei Log.class.php enthält die Klasse Log zum Loggen von Abläufen in Dateien.
 * Letzte Änderung am: $Date: 2006/02/21 10:47:29 $
 *
 * @version $Id: Log.class.php,v 1.6 2006/02/21 10:47:29 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * @since 2005-06-16
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 * @package pool
 */

use pool\classes\Core\PoolObject;
use pool\classes\Exception\FileOperationException;

/**
 * Die Klasse Log verfügt über folgende Verhalten:
 * - stellt eine Art Debug-Modus bereit.
 * - Objektinstanzen erzeugen, verwalten und auflösen.
 * - auf objektspezifische Informationen über den Klassentyp und die Instanz zugreifen.
 * - enthält Fehlerüberprüfung und kann Fehler auslösen.
 * - stellt ein Verfahren bereit, mit dem ein Inhalt eines Objekts einem anderen zugewiesen werden kann.
 * Log wird nie direkt instantiiert. Obwohl keine Programmiersprachenelemente zum Verhindern der Instantiierung verwendet werden, ist Log eine abstrakte Klasse.
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */
class LogFile extends PoolObject
{
    private const string DEFAULT_MODE = 'ab';
    private const int DEFAULT_MAX_FILE_SIZE = 2097152;
    private const string DEFAULT_DATE_TIME_FORMAT = '[d.m.Y H:i:s]';
    private const string ROTATED_FILE_SUFFIX = '.gz.tar';
    private const string SID_FORMAT = '%04d';

    /**
     * Datei
     *
     * @var string $file
     */
    private readonly string $file;

    private string $mode = self::DEFAULT_MODE;

    /**
     * Datei-Resource
     *
     * @var resource $fp
     */
    private mixed $fp = null;

    /**
     * Logfiles rotieren (Standard ausgeschaltet)
     */
    private bool $logRotate = false;

    /**
     * Maximale Dateigr��e des Logfiles (standard 2 MB)
     */
    private int $maxFileSize = 2097152;

    /**
     * Rotiert die Datei beginnend mit einem neuen Tag
     *
     * @var boolean
     */
    public bool $rotateByDate = false;

    /**
     * Trennzeichen
     */
    public string $separator = "\t";

    /**
     * Zeilenumbruch
     */
    public string $lineFeed = "\n";

    /**
     * Speichert die geschriebenen Zeilen zwischen
     *
     * @var array
     */
    public array $cache = [];

    /**
     * Cache aktiviert
     *
     * @var boolean
     */
    protected bool $enableCache = false;

    private int $sid = 0;

    private bool $withSID = true;

    /**
     * Format des Zeitstempels
     *
     * @var string
     */
    private string $formatDateTime = self::DEFAULT_DATE_TIME_FORMAT;

    /**
     * Konstruktor
     */
    public function __construct(string $file)
    {
        $this->file = $file;
        try {
            $this->sid = random_int(0, 9999);
        } catch (Exception) {
            $this->sid = 0;
        }
    }

    public function activateLogRotate(bool $rotateByDate = false): void
    {
        $this->logRotate = true;
        $this->rotateByDate = $rotateByDate;
    }

    /**
     * Deaktiviert Log-Session
     */
    public function disableSID(): void
    {
        $this->withSID = false;
    }

    /**
     * Open the file
     */
    private function open(): bool
    {
        if (file_exists($this->file)) {
            $filesize = filesize($this->file);
            if ($this->logRotate) {
                if ($this->rotateByDate) {
                    $newDate = (floor(filemtime($this->file) / 86400) !== floor(time() / 86400));
                    if ($newDate) $this->rotate($this->file);
                } elseif ($filesize > $this->maxFileSize) {
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
     */
    public function rotate(string $file): void
    {
        $reOpen = false;
        if ($this->opened()) {
            $reOpen = true;
            $this->close();
        }

        $nr = 1;
        while (file_exists($file.'.'.$nr.self::ROTATED_FILE_SUFFIX)) {
            $nr++;
        }
        $Tar = new Tar();
        $Tar->addData(basename($file), file_get_contents($file));
        $Tar->toTar($file.'.'.$nr.self::ROTATED_FILE_SUFFIX, true);
        @unlink($file);

        if ($reOpen) {
            $this->fp = fopen($this->file, self::DEFAULT_MODE);
        }
    }

    /**
     * Logging aktiviert? bzw. File geöffnet
     */
    private function opened(): bool
    {
        return is_resource($this->fp);
    }

    /**
     * Setzt die maximale Dateigröße
     */
    public function setMaxFileSize(int $fileSize = self::DEFAULT_MAX_FILE_SIZE): void
    {
        $this->maxFileSize = $fileSize;
    }

    /**
     * Trennzeichen (Standard \t)
     */
    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    /**
     * Zeilenumbruch setzen (Standard Unix \n)
     */
    public function setLineFeed(string $lineFeed): void
    {
        $this->lineFeed = $lineFeed;
    }

    /**
     * Setzt das Format f�r den Zeitstempel
     */
    public function setFormatDateTime(string $format = self::DEFAULT_DATE_TIME_FORMAT): static
    {
        $this->formatDateTime = $format;
        return $this;
    }

    /**
     * Creating Formatted Initial Strings
     */
    protected function generateFormattedInitialLogString(): string
    {
        try {
            $microTime = microtime(true);
            $microseconds = sprintf('%06d', ($microTime - floor($microTime)) * 1000000);
            $formattedDateTime = new DateTime(date('Y-m-d H:i:s').".$microseconds")->format($this->formatDateTime);
        } catch (Exception) {
            $formattedDateTime = (string)time();
        }
        return $formattedDateTime.(($this->withSID) ? $this->separator.'{'.
                sprintf(self::SID_FORMAT, $this->sid).'}' : '').$this->separator.'%s';
    }

    /**
     * Adds a log entry.
     */
    public function addLine(string $text): false|int
    {
        if (!$this->opened()) {
            $file = IS_TESTSERVER ? $this->file : basename($this->file);
            if (!$this->open()) throw new FileOperationException("Could not open log file: $file");
        }
        $text = sprintf($this->generateFormattedInitialLogString(), $text);
        if ($this->enableCache) {
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
        if ($this->opened()) {
            fclose($this->fp);
        }
        $this->fp = null;
    }
}
