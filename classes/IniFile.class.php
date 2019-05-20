<?php
/**
 *IniFile
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

class IniFile extends PoolObject
{
    var $filename="";
    var $content="";
    var $php_parser=false;			// use self-made parser or built-in php parser
    var $filetime=0;

    // Constructor
    function __construct($filename="")
    {
        $this->setIniFile($filename);
        parent::__construct();
    }

    // Destructor
    function destroy()
    {
        $this->clear();
    }

    // Leere Buffer
    function clear() {
        unset($this->content);
    }

    // Aktiviere php Parser
    function enable_php_parser() {
        $this->php_parser=true;
    }

    // Deaktiviere php Parser
    function disable_php_parser() {
        $this->php_parser=false;
    }

    // Setze Ini-Datei
    function setIniFile($filename, $clear=false)
    {
        if ($filename != "") {
            $this->filename = $filename;
            if ($clear) {
                $this->clear();
            }
            $this->loadFile();
        }
    }

    // Lade & Parse Ini-Datei in "content"
    function loadFile()
    {
        // Untersuche, ob die Datei existiert und lesbar ist
        if(file_exists($this->filename) and is_file($this->filename) and is_readable($this->filename)) {
            if ($this->php_parser) {
                $this->content = parse_ini_file($this->filename, true);
            }
            else {
                $this->content = $this->parse_ini_file($this->filename);
            }
        }
    }

    // Untersuche, ob Section existiert
    function sectionExists($section)
    {
        return isset($this->content[$section]);
    }

    // Untersuche, ob Section und Wert existieren
    function valueExists($section, $option)
    {
        if($this->sectionExists($section)) {
            return isset($this->content[$section][$option]);
        }
        else {
            return false;
        }
    }

    // Gibt den Wert einer Option in einer vorhandenen Section zur�ck
    // Oder einen Leerstring, falls nichts gefunden wurde.
    function getValue($section, $option="")
    {
        if($this->sectionExists($section)) {
            $sectionValues = $this->content[$section];

            // Test, ob das Ergebnis ein Array ist.
            if(is_array($sectionValues)) {
                // Untersuche, ob die Option definiert ist
                if(isset($sectionValues[$option])) {
                    return $sectionValues[$option];
                }
                else {
                    return "";
                }
            }
            // Falls es kein Array ist, ist es eine Option ohne Section ;)
            else {
                return $sectionValues;
            }
        }
        else {
            return "";
        }
    }

    // Gibt alle Optionen einer Section als Array zur�ck
    function getSection($section)
    {
        if($this->sectionExists($section)) {
            // Hole die Werte dieser Section aus dem Content array
            $sectionValues = $this->content[$section];

            if(is_array($sectionValues)) {
                return $sectionValues;
            }
            else {
                $tmp = array();
                $tmp[$section] = $sectionValues;
                return $tmp;
            }
        }
        return array();
    }

    // Erh�lt alle Namen der vorhanden Sections
    function getSectionNames()
    {
        return isset($this->content) ? array_keys($this->content) : array();
    }

    // Setzt oder �ndert einen Wert einer Option in einer gegebenen Section.
    // Falls Section einen Leerstring enth�lt, wird eine Option ohne Section gesetzt/ge�ndert
    // Ist der Parameter $write wahr, wird die Datei auf der Disk geschrieben, ansonsten nur im Speicher
    function setValue($section, $option, $value, $write = false)
    {
        // Section �bergeben?
        if($section != "") {
            $this->content[$section][$option] = $value;
        }
        else {
            $this->content[$option] = $value;
        }
        if ($write) {
            return $this->writeFile();
        }
        return true;
    }

    // Schreibt den Buffer in die Datei.
    // Hinweis: Kommentare und leere Zeilen innerhalb der Datei gehen dabei verloren
    function writeFile()
    {
        $success=true;

        // �ffne eine neue unique Datei zum Schreiben +b Windows kompatibel (binary flag)
        $tmpfile = $this->filename."-stellvertreter";
        if (!file_exists($tmpfile)) {
            touch($tmpfile);
        }
        $fp_new = fopen($tmpfile, "r+");
        if(!$fp_new) {
            $success=false;
        }
        else {
            if (flock($fp_new, LOCK_EX+LOCK_NB)) {
                // Haben wir etwas zum Schreiben im Buffer...
                if(isset($this->content)) {
                    $data = "";
                    // Hole jede Section und deren Werte
                    while(list($section, $values) = each($this->content)) {
                        if(is_array($values)) {
                            // Schreibe Section
                            $data .= "[$section]\n";

                            // Hole Options mit Werten
                            while(list($option, $value) = each($values)) {
                                // Schreibe Options und deren Werte
                                $data .= "$option=$value\n";
                            }

                            // Leerzeile
                            $data .= "\n";
                        }
                        // kein Array
                        else {
                            // Schreibe Option ohne Section
                            $data .= "$section=$values\n";
                        }
                    }
                    $strlen = strlen($data);
                    if (!fwrite($fp_new, $data, $strlen)) {
                        $success=false;
                    }
                    else {
                        ftruncate($fp_new, $strlen);
                    }
                    unset($data, $strlen);
                }

                // Bei Erfolg neu geschriebenes Tmpfile atomisch in Original File umbenennen, (Zeitstempel beachten)
                if ($success) {
                    // falls nicht ein anderer Prozess schneller war und bereits geschrieben hat...
                    if ((file_exists($this->filename) and filemtime($this->filename) <= $this->filetime) or ($this->filetime==0)) {
                        rename($tmpfile, $this->filename);
                    }
                }
                flock($fp_new, LOCK_UN);
            }

            if (!fclose($fp_new)) {
                $success=false;
            }
            else {
                if ($success) {

                    copy($this->filename, dirname($this->filename)."/".uniqid("")."_".basename($tmpfile));
                }
                else {
                    copy($this->filename, dirname($this->filename)."/".uniqid("")."_writeerror_".basename($tmpfile));
                }
            }
        }

        // Gib Erfolgsstatus zur�ck
        return $success;
    }

    // eigene Implementierung von parse_ini_file (ohne Abbruch des Scripts)
    function parse_ini_file($filename)
    {
        $res = array();
        if (file_exists($filename) && is_readable($filename)) {
            $section = "";

            // Shared Lock auf die Quelldatei
            $this->filetime = filemtime($filename);
            $fd = @fopen($filename, "r");
            while(!feof($fd)) {
                $line = trim(@fgets($fd, 4096));
                $len = strlen($line);

                // �berspring Leerzeilen
                if($len != 0) {
                    // �berspringe Kommentare
                    if($line[0] != ';') {
                        // Section?
                        if(($line[0] == '[') && ($line[$len-1] == ']')) {
                        // Hole Section Name
                            $section = substr($line, 1, $len-2);

                            // Check if the section is already included in result array
                            if(!isset($res[$section])) {
                                $res[$section] = array();
                            }
                        }
                        // Untersuche nach Eintr�ge
                        $pos = strpos($line, '=');
                        // Eintrage gefunden
                        if($pos != false) {
                            // Hole den Namen des Eintrags
                            $name = substr($line, 0, $pos);
                            // Hole den Wert des Eintrats
                            $value = substr($line,$pos+1,$len-$pos-1);
                            // Speichere den Eintrag

                            // Innerhalb einer Section?
                            if($section != "") {
                                $res[$section][$name] = $value;
                            }
                            else {
                                $res[$name] = $value;
                            }
                        }
                    }
                }
            }
            // Schlie�e Datei
            @fclose($fd);
        }
        return $res;
    }

}