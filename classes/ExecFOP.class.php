<?php
/**
* POOL (PHP Object Oriented Library): die Datei ExecFOP.class.php ist eine Wrapper Klasse f�r das Java Programm FOP (Formatting Objects Processor).
*
* Letzte �nderung am: $Date: 2007/01/26 11:43:16 $
*
* @version $Id: ExecFOP.class.php,v 1.8 2007/01/26 11:43:16 schmidseder Exp $
* @version $Revision: 1.8 $
* @version
*
* @since 2005-05-18
* @author Alexander Manhart <alexander@manhart.bayern>
* @link https://alexander-manhart.de
* @package pool
*/

if(!defined('CLASS_EXECFOP')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     *
     * @ignore
     */
    define('CLASS_EXECFOP',			1);


    /**
     * Wrapper Klasse f�r das Java FOP (Formatting Objects Processor) Programm
     * Mit fo (formating objects) ist es simple XML-Dokumente in PDF-Dokumente zu konvertieren. Au�er PDF werden noch
     *  ps, pcl und andere unterst�tzt.
     *
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
     * @package pool
     */
    class ExecFOP extends PoolObject
    {
        var $progressBar = null;

        /**
         * fo-Datei
         * @access private
         */
        var $fo = '';

        /**
         * Output-Datei
         *
         * @access private
         * @var string
         */
        var $outputFile = '';

        /**
         * Tempverzeichnis
         *
         * @access private
         * @var string
         */
        var $tmpdir = '/tmp';

        /**
         * Prefix f�r tempor�re PDF Dateien
         *
         * @access private
         * @var  string
         */
        var $tmppdfprefix = 'javafop_';

        /**
         * Render Typ (Standard: pdf). M�gliche Werte sind:
         * - awt
         * - mif
         * - pcl
         * - pdf
         * - ps
         * - txt
         * - xml
         *
         * @access private
         * @var string
         */
        var $renderer = 'pdf';

        /**
         * ContentType.
         *
         * @access private
         * @var contentType
         */
        var $contentType = 'application/pdf';

        /**
         * Wenn man mehr Schriften oder andere zus�tzliche Sachen ben�tigt, m�ssen diese in einer FOP-Konfigurationsdatei eingerichtet werden.
         *  Fop-Configfile, you can assign one
         *
         * See http://xml.apache.org/fop/fonts.html f�r Details zum Einbinden von Schriften
         *
         * @access private
         * @var configFile
         */
        var $configFile = null;

        /**
         * Daten als XML-File
         *
         * @access private
         * @var string
         */
        var $xmlFile = null;

        /**
         * Layout als XML-File
         *
         * @access private
         * @var string
         */
        var $xslFile = null;

        /**
         * Callback Funktion fuer die schrittweise Ausgabe auf der Konsole
         *
         * @var mixed
         */
        var $callback_function = null;


        /**
         * stores the path to the fop executable
         *
         * @var string
         */
        var $fopPath = null;

        /**
         * strict validation
         */
        var $strictValidation = true;

        /**
         * Calls the Main Fop-Java-Programm
         *
         *  One has to pass an input fo-file
         *  and if the pdf should be stored permanently, a filename/path for
         *  the pdf.
         *
         * @param string $fo file input fo-file
         * @param string $outputFile Zieldatei (wird das Ziel nicht angegeben, wird eine tempor�re Datei erzeugt)
         * @see runFromString()
         */
        function run($outputFile = '')
        {
            if (!$outputFile) {
                $outputFile = tempnam($this -> tmpdir, $this -> tmppdfprefix);
            }

            $this->outputFile = $outputFile;

            $options   = array();
            if(file_exists($this -> xmlFile)) {
                array_push($options, '-xml', $this -> xmlFile);
            }
            if(file_exists($this -> xslFile)) {
                array_push($options, '-xsl', $this -> xslFile);
            }
            if(file_exists($this -> configFile)) {
                array_push($options, '-c', $this -> configFile);
            }
            if(file_exists($this -> fo)) {
                array_push($options, '-fo', $this -> fo);
            }

            array_push($options, '-' . $this -> renderer, $this -> outputFile);

           # $javaPath = JAVA_PATH;

            $strictStr = ($this->strictValidation) ? '' : ' -r ';

            $commandLine = $this->getFopPath() . ' ' . $strictStr . implode(' ', $options) . ' 2>&1 ';

            if (is_null($this->callback_function)) {

                //$logcommand = "ps -eo pid,args | grep 'fop' | grep -v 'grep' | xargs echo `date '+%d.%m.%y %H:%M:%S ---> '` >> /virtualweb/data/fop-`hostname`.log";

                exec($commandLine, $output, $returnVal);

                $success = ($returnVal==1) ? false : true ;
                $return = array( 'success' => $success, 'errors' => $output);

                return $return;
            }
            else {
                $handle = popen($commandLine, 'r');

                $callback_function = $this->callback_function;
                while(!feof($handle)) {
                    $line = fgets($handle);
                    if(is_array($callback_function)) {
                        $callback_function[0] -> $callback_function[1]($line);
                    }
                    else {
                        $callback_function($line);
                    }
//						echo "$line <br>";
//						flush();
                }
                pclose($handle);
                $return = array('success' => file_exists($outputFile), 'errors' => 'Fehler sind aufgetreten!');

                return $return;
            }
        }

        function setCallback($callback_function)
        {
            $this -> callback_function = $callback_function;
        }

        /**
         * fo als String in das FOP Java Main Programm schie�en.
         * Falls man das fo fo dynamisch generiert (z.B. mit einem
         *  xsl-stylesheet), kann man diese Funktion verwenden.
         *  The Fop-Java program needs a file as an input, so a
         *  temporary fo-file is created here (and will be deleted
         *  in the run() function.)
         *
         * @param    string  $fostring   fo input fo-string
         * @param    string  $outputFile        file output-file
         * @see run()
         */
        function &runFromFoString($fostring, $outputFile='')
        {
            $fo = tempnam($this->tmpdir, $this->tmppdfprefix);
            $fp = fopen($fo, 'w+');
            fwrite($fp, $fostring);
            fclose($fp);

            $this->setFoFile($fo);
            $result = $this->run($outputFile);
            @unlink($fo);

            return $result;
        }

        /**
         * Eine Wrapper Funktion zur besseren Lesbarkeit
         * Diese Funktion ruft lediglich run auf
         *
         * @param string $fo fo input fo-string
         * @param string $outputFile file output-file
         * @see run()
         */
       function runFromFoFile($foFile, $outputFile = '')
        {
            $this -> setFoFile($foFile);

            return $this -> run($outputFile);
        }

        /**
         * L�scht die generierte Datei
         *
         * @access public
         */
        function deleteOutputFile()
        {
            @unlink($this -> outputFile);
        }

        /**
         * Enter description here...
         *
         * @return unknown
         */
        function getOutputFile()
        {
            return $this -> outputFile;
        }

        /**
         * Liefert die generierte Datei als String zur�ck
         *
         * @return string Inhalt des OutputFile
         * @see run()
         */
        function getContentFromOutputFile()
        {
            $outputFile = $this -> getOutputFile();
            $fd = fopen($outputFile, 'r');
            $content = fread($fd, filesize($outputFile));
            fclose($fd);

            return $content;
        }

        /**
         * Setzt den Rendertyp.
         *
         * @param string $renderer Rendertyp (Standard: pdf)
         * @see $renderer
         * @access public
         */
        function setRenderer($renderer = 'pdf')
        {
            $this -> renderer = $renderer;

            switch ($renderer) {
                case 'pdf':
                    $this -> contentType = 'application/pdf';
                    break;

                case 'ps':
                    $this -> contentType = 'application/ps';
                    break;

                case 'pcl':
                    $this -> contentType = 'application/pcl';
                    break;

                case 'txt':
                    $this -> contentType = 'text/plain';
                    break;

                case 'xml':
                    $this -> contentType = 'text/xml';
                    break;

                default:
                    throw new Exception('Render type: "' . $renderer . '" undefined!');
             }
        }

        /**
         * Liefert den ContentType der generierten Datei
         *
         * @access public
         * @return string
         */
        function getContentType()
        {
            return $this -> contentType;
        }

        /**
         * Setzt die Config Datei f�r FOP (-c)
         *
         * @param string $configFile Die Config Datei f�r FOP
         * @access public
         * @see $configFile
         */
        function setConfigFile($configFile)
        {
            $this -> configFile = $configFile;
        }

        /**
         * Setzt Daten als XML-Datei
         *
         * @param unknown_type $xmlFile
         * @access public
         * @see $xmlFile
         */
        function setDataFile($xmlFile)
        {
            $this -> xmlFile = $xmlFile;
        }

        /**
         * Setzt das Layout als XSL-Datei
         *
         * @param unknown_type $xslFile
         * @access public
         * @see $xslFile
         */
        function setLayoutFile($xslFile)
        {
            $this -> xslFile = $xslFile;
        }

        function setFopPath($fopPath)
        {
            $this->fopPath = $fopPath;
        }

        function getFopPath()
        {
            if (is_null($this->fopPath)) {
                return FOP_PATH;
            }
            else {
                return $this->fopPath;
            }
        }

        /**
         * Setzt die fo-Datei (formatting objects)
         *
         * @param string $foFile fo-Datei
         */
        function setFoFile($foFile)
        {
            $this -> fo = $foFile;
        }

        function setStrictValidation($strict)
        {
            $this->strictValidation = $strict;
        }

        function setProgressBar( $paramProgressBar)
        {
            $this -> progressBar = $paramProgressBar;
        }



    }
}