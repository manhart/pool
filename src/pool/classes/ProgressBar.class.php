<?php
/**
* POOL (PHP ProgressBar Oriented Library): die Datei ProgressBar.class.php enthaelt eine Klasse zum Erstellen eines Fortschrittsbalken.
*
* Vermerk Author:<br>
* Ich will an diesem System nichts verkomplizieren, fuer mich gilt der Spruch KISS (keep it simple stupid).
*
* Letzte �nderung am: $Date: 2007/01/12 10:54:53 $
*
* @version $Id: ProgressBar.class.php,v 1.5 2007/01/12 10:54:53 hoesl Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-07-10
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
* @package pool
*/

if(!defined('CLASS_PROGRESSBAR')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_PROGRESSBAR',			1);

    /**
     * Klasse zum Erzeugen von Fortschrittsanzeigen
     *
     * @access public
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @package pool
     */
    class ProgressBar extends Object
    {
        /**
         * Die Eigenschaft max legt die obere Bereichsgrenze f�r die m�glichen Positionen fest. Default 100.
         *
         * @var int $width
         * @access private
         */
        var $width=100;

        var $height=20;

        /**
         * Die Eigenschaft min legt die untere Bereichsgrenze der m�glichen Positionen fest. Default 0.
         *
         * @var int $min
         * @access private
         */
        var $minimum=0;

        /**
         * Die Eigenschaft position legt die gegenw�rtige Position der Fortschrittsanzeige fest. Default 0.
         *
         * @var int $position
         * @access private
         */
        var $position=0;

        /**
         * Die Eigenschaft step legt den Wert fest, um den die Eigenschaft position durch den Aufruf der Methode stepIt erh�ht wird
         *
         * @var int $step Standard 10
         * @access private
         */
        var $step=10;

        var $maximum=10;

        /**
         * Bild zur Fortschrittsanzeige im Browser. Am besten 1px breit.
         *
         * @var string $image
         */
        var $image='';

        /**
         * H�he einer Bar (Balken) in Pixel
         *
         * @var int Standard 10px
         * @access private
         */
        var $barHeight=10;

        /**
         * Breite einer Bar (Balken) in Pixel
         *
         * @var int Standard 1px
         * @access private
         */
        var $barWidth=1;

        /**
         * Ausgeben des Ausgabe-Puffers an den Browser
         *
         * @var bool Standard true
         * @access private
         */
        var $flush=true;

        /**
         * Anzahl ausgegebener Bars (Balken)
         *
         * @var int Standard 0
         * @access private
         */
        var $barsDone = 0;

        /**
         * Grafik als Abstand zwischen Bars (Balken)
         *
         * @var string Grafikdatei (z.B. GIF)
         * @access private
         */
        var $blindGapGif = '';

        /**
         * Abstand zwischen Bars (Balken) in Pixel
         *
         * @var int Standard 0px (kein Abstand)
         * @access private
         */
        var $gap = 0;

        var $paintFirstGap = false;

        function ProgressBar($imageBar, $flush=true)
        {
            $result = $this -> setImage($imageBar);
            if($this -> isError($result)) {
                $this -> throwException($result);
            }
            $this -> flush = $flush;
        }

        function _initialize()
        {
            if(is_null($this->barHeight)) $this->barHeight = $this->height;
        }

        function stepIt()
        {
            if($this->position==0) $this->_initialize();
            /*echo $this->gap.'<br>';*/
/*				$numBars = ($this->width / $this->barWidth)-1;
            if(is_float($numBars)) die('Gesamtl�nge nicht durch BarWidth teilbar!');
            $w = $this->width-($this->gap*$numBars);
            $numBars = $w / $this->barWidth;
            if(is_float($numBars)) die('BarWidth und GapWidth passen nicht zur Gesamtl�nge!');*/


            $this->position += $this->getProgress();

            $this -> __step();
        }

        function stepBy($delta)
        {
            $this -> position += $delta;

            $this -> __step();
        }

        function __step()
        {
            // Anzahl gesamter Balken
            $numBars = ceil($this->width / $this->barWidth);

            // Wie viele Balken passen in die derzeitige Position:
            $amountBars = ($this->position / $this->barWidth);

            // Wie viele Balken m�ssen noch gezeichnet werden:
            $barsTodo = ceil($amountBars - $this->barsDone);
            //echo ($amountBars - $this -> barsDone) . ' ';

            /*if($this->barsDone==0 and $this->paintFirstGap) $this->paintGap();*/

            for($i=0; $i<$barsTodo; $i++) {
                $this->paintImage($this->barsDone==$numBars);
                $this->barsDone++;
                //if(($this -> getPercent() < 100) or (($this -> getPercent() == 100) and ($i < $barsTodo - 1))) {
                if($this->barsDone != $numBars) {
                    $this->paintGap();
                }
            }
            //echo $stepsByImage .'vs'.$this -> stepsDone . '='.($stepsByImage - $this -> stepsDone) . ':' . $stepsTodo;
        }

        function paintImage($last)
        {
            $bw = ($last) ? intval($this->barWidth) : intval($this->barWidth-$this->gap);
            $this->paint($this->image, $bw, $this->barHeight);
        }

        function paintGap()
        {
            if(($this->gap > 0)) {
                $this->paint($this -> blindGapGif, $this->gap, $this -> barHeight);
            }
        }

        function paint($image, $width, $height)
        {
            echo '<img src="'.$image.'" width="'.$width.'" height="'.$height.'" border="0">';
            if($this -> flush) flush();
        }

        /**
         * Ermittelt wie viel Einheiten ein Schritt in der Fortschrittsanzeige ausmacht.
         *
         * @return int Einheiten
         */
        function getProgress()
        {
            if($this->maximum != 0) {
                $progress = ($this->width / $this->maximum);
            }
            else {
                $progress = 0;
            }

            return $progress;
        }

        function setMinimum($minimum)
        {
            $this->minimum = $minimum;
        }

        function getMinimum()
        {
            return $this -> minimum;
        }

        function setMaximum($maximum)
        {
            $this -> maximum = $maximum;
        }

        function getMaximum()
        {
            return $this -> maximum;
        }


        function setPosition($position)
        {
            $this -> position = $position;
        }

        function getPosition()
        {
            return $this -> position;
        }

        function setImage($image)
        {
            $bExists = file_exists($image);
            if(!$bExists) {
                $Xception = new Xception('Bild-Datei: ' . $image . ' wurde nicht gefunden!', 0,
                  array('file' => __FILE__, 'line' => __LINE__, 'function' => __FUNCTION__, 'class' => __CLASS__));
                return $Xception;
            }
            $this->image = $image;
            return true;
        }

        function getImage()
        {
            return $this -> image;
        }

        function setBarHeight($height)
        {
            $this->barHeight = $height;
        }

        function setBarWidth($width)
        {
            $this->barWidth = (int)$width;
        }

        function setBarSize($width, $height=null)
        {
            $this->setBarWidth($width);
            $this->setBarHeight($height);
        }

        function setSize($width, $height)
        {
            $this -> setWidth($width);
            $this -> setHeight($height);
        }

        function setWidth($width)
        {
            $this->width = (int)$width;
        }

        function setHeight($height)
        {
            $this->height = $height;
        }

        /**
         * Zeichne Abstand, der Abstand wird von der Balkenbreite "barWidth" abgezogen
         *
         * @param string $blindGif Datei
         * @param int $gap
         */
        function setGap($blindGif, $gap=1)
        {
            $bExists = file_exists($blindGif);
            if(!$bExists) {
                $Xception = new Xception('BlindGif-Datei: ' . $blindGif . ' wurde nicht gefunden!', 0,
                  array('file' => __FILE__, 'line' => __LINE__, 'function' => __FUNCTION__, 'class' => __CLASS__));
                $this->throwException($Xception);
            }
            $this->blindGapGif = $blindGif;
            $this->gap = $gap;
        }

        function getPercent()
        {
            return ($this->position * 100) / $this->width;
        }


        function setPaintFirstGap($value)
        {
            /* @deprecated */
            /*$this->paintFirstGap=$value;*/
        }
    }
}
//	$ProgressBar = new ProgressBar();