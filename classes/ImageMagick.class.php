<?php
/**
* -= PHP Object Oriented Library =-
*
* ImageMagick.class.php
*
* Die Klasse ImageMagick erleichtert die Ansteuerung von ImageMagick ueber die Konsole.
* ImageMagick erwartet beim Konstruktor den Pfad zum "Temp" Verzeichnis und den Pfad zu den ImageMagick Programmen.
*
* Zu beachten, bei jeder Aktion erzeugt die Klasse ein neues Bild im "Temp" Verzeichnis (History). Damit die Bild History bei einem neuen
* Request nicht verloren geht, stehen die Funktionen saveIntoSession() und loadFromSession() bereit.
* loadFromSession() gibt einen Boolean zurueck. Anhand des Boolean kann festgestellt werden, ob bereits Daten vorhanden sind.
* Falls nicht, entweder per uploadImage oder editImage die Bearbeitung starten.
*
* @date $Date: 2007/07/11 07:56:54 $
* @version $Id: ImageMagick.class.php,v 1.5 2007/07/11 07:56:54 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-26-11
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
*/

/**
 * ImageMagick
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: ImageMagick.class.php,v 1.5 2007/07/11 07:56:54 manhart Exp $
 * @access public
 **/
class ImageMagick extends PoolObject
{
    //@var string Zielverzeichnis, wohin das fertige Bild gespeichert werden soll
    //@access private
    var $targetdir      = '';

    //@var string Zieldatei, Dateiname wie das fertige Bild heissen wird.
    //@access private
    var $targetfile		= '';

    //@var string Pfad zu den ImageMagick Programmen
    //@access private
    var $imagemagickdir = '/usr/local/bin';

    //@var string "Temp"-Verzeichnis (httpd must be able to write there)
    //@access private
    var $temp_dir		= '/var/tmp';

    //@var array Datei-/Bild- History
    //@access private
    var $file_history   = array();

    //@var string original Dateiname
    //@access private
    var $temp_file      = '';

    //@var string JPG Qualitaet
    //@access private
    var $jpg_quality	= '99';

    //@var integer Interner Zaehler
    //@access private
    var $count			= 0;

    //@var array Ablage fuer Bilddaten
    //@access private
    var $image_data     = array();

    //@var string Fehler
    //@access public
    var $error          = '';

    //@var boolean Verbose
    //@access private
    var $verbose        = false;

    /**
     * Seitenbreite in Pixel
     *
     * @var int
     */
    var $pageX=-1;

    /**
     * Seitenh�he in Pixel
     *
     * @var int
     */
    var $pageY=-1;

    /**
     * DPI
     *
     * @var integer
     */
    var $dpi=0;

    var $frame=false;
    var $mattecolor=false;
    var $raise=false;

    /**
     * Konstruktor erwartet Pfad zu den ImageMagick Programmen, Pfad zum temporaeren Ordner und verbose.
     *
     * @param string $imagemagickdir Pfad zu den ImageMagick Programmen
     * @param string $temp_dir "Temp" Verzeichnis
     * @param boolean $verbose Debugausgaben
     **/
    function __construct($imagemagickdir='/usr/local/bin', $temp_dir='/var/tmp', $verbose=false)
    {
        $this -> setVerbose($verbose);
        $this -> imagemagickdir = $imagemagickdir;
        $this -> temp_dir = $temp_dir;
    }

    function reset()
    {
        $this->frame=false;
        $this->mattecolor=false;
        $this->raise=false;
    }

    /**
     * Funktion platziert hochgeladene Dateien in $this -> temp_dir
     * Holt die Imagedaten und speichert sie in $this -> image_data
     * $filedata = $_FILES['file1']
     *
     * @param array $filedata => $_FILES['file1']
     * @return boolean Erfolgsstatus
     **/
    function uploadImage($filedata)
    {
        $bResult = false;
        // echo get_cfg_var('upload_tmp_dir');
        if (is_uploaded_file($filedata)) {
            $this -> temp_file = time() . ereg_replace("[^a-zA-Z0-9_.]", '_', $filedata['name']);
            $this -> targetfile = $filedata['name'];
            if(!@rename($filedata['tmp_name'], $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file)) {
                die("Imagemagick: Upload failed");
            }
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
            $this -> getSize();
            $bResult = true;
        }
        return $bResult;
    }

    /**
     * ImageMagick::editImage()
     *
     * Sagt der Klasse, er soll jetzt von diesem Image eine Kopie anlegen und diese Kopie fuer die Bearbeitung auswaehlen.
     *
     * @param string $filedata Pfad und Dateiname des zu bearbeitenden Bildes.
     * @return boolean Erfolgsstatus
     **/
    function editImage($filedata)
    {
        $filename = basename($filedata);
        $this -> temp_file = time() . ereg_replace("[^a-zA-Z0-9_.]", '_', $filename);
        $this -> targetfile = $filename;
        if (!@copy($filedata, $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file)) {
            die("Imagemagick: Copy failed (from: ".$filedata." to: ".$this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file . ')');
        }

        $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        $this -> getSize();
        return true;
    }

    /**
     * ImageMagick::saveIntoSession()
     *
     * Speichert die History in die Session.
     *
     * @access public
     * @param object $Session ISession
     * @see ISession
     * @return boolean Erfolgsstatus
     **/
    function saveIntoSession(& $Session)
    {
        $Session -> setVar('ImageMagick_targetfile', $this -> targetfile);
        $Session -> setVar('ImageMagick_temp_file', $this -> temp_file);
        $Session -> setVar('ImageMagick_file_history', $this -> file_history);
        return true;
    }

    /**
     * ImageMagick::loadFromSession()
     *
     * Laedt die History aus der Session und initialisiert unser Objekt ImageMagick.
     *
     * @access public
     * @param object $Session ISession
     * @return boolean Erfolgsstatus
     **/
    function loadFromSession(& $Session)
    {
        $bResult = false;
        $targetfile = $Session -> getVar('ImageMagick_targetfile');
        $temp_file = $Session -> getVar('ImageMagick_temp_file');
        $file_history = $Session -> getVar('ImageMagick_file_history');
        if ((strlen(trim($temp_file)) > 0) and is_array($file_history) and (count($file_history) > 0) and is_file($file_history[0])) {
            $this -> targetfile = $targetfile;
            $this -> temp_file = $temp_file;
            $this -> file_history = $file_history;
            $this -> count = (count($this -> file_history)-1);
            $this -> getSize();
            $bResult = true;
        }
        else {
            $this -> targetfile = '';
            $this -> temp_file = '';
            $this -> file_history = array();
            $this -> count = 0;
        }
        return $bResult;
    }

    /**
     * ImageMagick::setTargetdir()
     *
     * Setzt das Verzeichnis, wohin die Bilder gespeichert werden.
     * Der httpd User muss dort Schreibzugriff haben!
     *
     * @access public
     * @param string $target Zielverzeichnis
     * @return boolean
     **/
    function setTargetdir($target)
    {
        if($target == '') {
            $this -> targetdir = $this -> temp_dir;
        }
        else {
            $this -> targetdir = $target;
        }
        if($this -> verbose == true) {
            echo "Set target dir to '{$this->targetdir}'\n";
        }
        return true;
    }

    /**
     * ImageMagick::setTargetfile()
     *
     * Setzt die Zieldatei, worin das Bild gespeichert wird.
     *
     * @param string $target Zieldatei
     **/
    function setTargetfile($target)
    {
        if (!empty($target)) {
            $this -> targetfile = $target;
        }
        else {
            $this -> targetfile = $this -> temp_file;
        }
        if($this -> verbose == true) {
            echo "Set target file to '{$this->targetfile}'\n";
        }
        return true;
    }

    /**
     * Setzt die JPG Qualitaet fuer die Konvertierung: $this -> convert().
     *
     * @param string $jpg_quality JPG Qualitaet
     * @return boolean Erfolgsstatus
     **/
    function setJPGQuality($jpg_quality)
    {
        $this -> jpg_quality = $jpg_quality;
        return true;
    }

    function setDpi($dpi)
    {
        $this->dpi=$dpi;
        return true;
    }

    /**
     * ImageMagick::getJPGQuality()
     *
     * Gibt die aktuelle JPG Qualitaet aus.
     *
     * @return integer JPEG Qualitaet
     **/
    function getJPGQuality()
    {
        return $this -> jpg_quality;
    }

    /**
     * ImageMagick::getFilename()
     *
     * Gibt den aktuellen Dateinamen zurueck
     *
     * @return string aktueller Dateiname
     **/
    function getFilename($absolut=true)
    {
        if ($absolut) {
            $filename = $this -> file_history[$this -> count];
        }
        else {
            $filename = $this -> temp_file;
        }
        return $filename;
    }

    /**
     * ImageMagick::setVerbose()
     *
     * Wenn true gesetzt wird, werden alle Debug Informationen ausgegeben.
     *
     * @access public
     * @param boolean $v Verbose
     **/
    function setVerbose($v=false)
    {
        $this -> verbose = $v;
        if($v == true) {
            echo '<pre>';
        }
    }

    /**
     * ImageMagick::getSize()
     *
     * Gibt die Groesse des Bildes in einem Array zurueck
     *
     * @access public
     * @return array array[0] = x-size, array[1] = y-size
     **/
    function getSize()
    {
        $command = $this -> imagemagickdir . "/identify -verbose '" . $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file . "'";
        if ($this -> verbose == true) {
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            die('ImageMagick: Corrupt image');
        }
        else {
            $num = count($returnarray);
            for($i=0; $i<$num; $i++) {
                $returnarray[$i] = trim($returnarray[$i]);
            }
            $this -> image_data = $returnarray;
        }
        $num = count($this -> image_data);
        for($i=0; $i<$num; $i++) {
            if(eregi('Geometry', $this -> image_data[$i])) {
                $tmp1 = explode(' ', $this -> image_data[$i]);
                $tmp2 = explode('x', $tmp1[1]);
                $this -> size = $tmp2;
                return $tmp2;
            }
        }
    }


    /**
     * ImageMagick::flip()
     *
     * Dreht das Bild horizontal oder vertikal (flip).
     *
     * @access public
     * @param string $var Moegliche Argumente 'horizontal' und 'vertical', default ist 'horizontal'
     **/
    function flip($var='horizontal')
    {
        $tmp = $var == 'horizontal' ? '-flop' : ($var == 'vertical' ? '-flip' : '');
        //$command = "{$this->imagemagickdir}/convert {$tmp} '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("{$tmp}");
        if($this -> verbose == true) {
            echo "Flip:\n";
            echo "  Method: {$var}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Flip failed\n";
            if($this -> verbose == true) {
                echo "Flip failed\n";
            }
        }
        else {
            $this -> file_history[] = $this->temp_dir . '/tmp' . $this->count . '_' . $this->temp_file;
        }
    }

    /**
     * ImageMagick::dither()
     *
     * Aus der IM Doku:
     * apply Floyd/Steinberg error diffusion to the image
     * The basic strategy of dithering is to trade intensity resolution for spatial resolution by averaging the intensities of several neighboring pixels. Images which suffer from severe contouring when reducing colors can be improved with this option.
     *  The -colors or -monochrome option is required for this option to take effect.
     *  Use +dither to turn off dithering and to render PostScript without text or graphic aliasing.
     *
     * @access public
     **/
    function dither()
    {
        //$command = "{$this->imagemagickdir}/convert -dither '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-dither");
        if($this -> verbose == true) {
            echo "Dither:\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Dither failed\n";
            if($this -> verbose == true) {
                echo "Dither failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::monochrome()
     *
     * Konvertiert das Bild in monochrome (2 Farben schwarz-weiss)
     *
     * @access public
     **/
    function monochrome()
    {
        //$command = "{$this->imagemagickdir}/convert -monochrome '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-monochrome");
        if($this -> verbose == true) {
            echo "Monochrome:\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Monochrome failed\n";
            if($this -> verbose == true) {
                echo "Monochrome failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::negative()
     *
     * Konvertiert das Bild in ein Negativbild
     *
     * @access public
     **/
    function negative()
    {
        //$command = "{$this->imagemagickdir}/convert -negate '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-negate");
        if($this -> verbose == true) {
            echo "Negative:\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Negative failed\n";
            if($this -> verbose == true) {
                echo "Negative failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::contrast()
     *
     * enhance or reduce the image contrast
     *
     * @access public
     * @param string $how 'more' > enhance contrast 'less' > reduce contrast
     **/
    function contrast($how='more')
    {
        if ($how=='more') {
            $how='+';
        }
        elseif($how=='less') {
            $how='-';
        }
        //$command = "{$this->imagemagickdir}/convert {$how}contrast '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("{$how}contrast");
        if($this -> verbose == true) {
            echo "Contrast:\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Contrast failed\n";
            if($this -> verbose == true) {
                echo "Contrast failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::antialias()
     *
     * By default antialiasing algorithms are used when drawing objects (e.g. lines) or rendering vector formats
     * (e.g. WMF and Postscript). Use +antialias to disable use of antialiasing algorithms. Reasons to disable antialiasing
     * include avoiding increasing colors in the image, or improving rendering speed.
     *
     * @access public
     **/
    function antialias()
    {
        //$command = "{$this->imagemagickdir}/convert -antialias '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-antialias");
        if($this -> verbose == true) {
            echo "Antialias:\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Antialias failed\n";
            if($this -> verbose == true) {
                echo "Antialias failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::rotate()
     *
     * Rotiert das Bild.
     *
     * @access public
     * @param integer $deg Zahlen von 0-360
     * @param string $bgcolor Hexadezimal Farbe ohne dem "#", z.B. C3D6A0
     * @param string $how bei keinem Wert > Standard Rotation, 'morewidth' > rotiert das Bild nur, wenn die Breite die Hoehe ueberschreitet, 'lesswidth' > rotiert das Bild nur, wenn die Breite kleiner ist als die Hoehe.
     **/
    function rotate($deg=90, $bgcolor='000000', $how='')
    {
        $tmp = $how=='lesswidth'?"<":($how=='morewidth'?">":'');
        //$command = "{$this->imagemagickdir}/convert -background '#{$bgcolor}' -rotate '{$deg}{$tmp}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-background '#{$bgcolor}' -rotate '{$deg}{$tmp}'");
        if($this -> verbose == true) {
            echo "Rotate:\n";
            echo "  Degrees: {$deg}\n";
            echo "  Method: ".($how==''?'standard':$how)."\n";
            echo "  Background color: #{$bgcolor}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Rotate failed\n";
            if($this -> verbose == true) {
                echo "Rotate failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::blur()
     *
     * Weichzeichnen, mit Gaussian Operator
     *
     * @access public
     * @param integer $radius Radius
     * @param integer $sigma Sigma
     **/
    function blur($radius=0, $sigma=1)
    {
        //$command = "{$this->imagemagickdir}/convert -blur '{$radius}x{$sigma}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-blur '{$radius}x{$sigma}'");
        if($this -> verbose == true) {
            echo "Blur:\n";
            echo "  Radius: {$radius}\n";
            echo "  Sigma: {$sigma}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Blur failed\n";
            echo "Blur failed\n";
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::sharpen()
     *
     * Schaerft das Bild.
     *
     * @access public
     * @param integer $radius Radius
     * @param integer $sigma Sigma
     **/
    function sharpen($radius=0, $sigma=1)
    {
        //$command = "{$this->imagemagickdir}/convert -sharpen '{$radius}x{$sigma}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-sharpen '{$radius}x{$sigma}'");
        if($this -> verbose == true) {
            echo "Sharpen:\n";
            echo "  Radius: {$radius}\n";
            echo "  Sigma: {$sigma}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Sharpen failed\n";
            echo "Sharpen failed\n";
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::modulate()
     *
     * Aendert die Helligkeit und/oder Saettigung des Bildes.
     *
     * @access public
     * @param integer $brightness Prozentangabe ueber Hellikeitsaenderung (100 := normal)
     * @param integer $saturation Prozentangabe ueber Saettigungsaenderung (100 := normal)
     **/
    function modulate($brightness=100, $saturation=100)
    {
        //$command = "{$this->imagemagickdir}/convert -modulate '{$brightness}x{$saturation}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-modulate '{$brightness}x{$saturation}'");
        if($this -> verbose == true) {
            echo "Modulate:\n";
            echo "  Brightness: {$brightness}\n";
            echo "  Saturation: {$saturation}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Modulate failed\n";
            echo "Modulate failed\n";
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::frame()
     *
     * Zeichnet einen Rahmen um das Bild
     *
     * @access public
     * @param integer $width Breite in Pixel
     * @param string $color Farbe in hexadezimal, z.B. 4AF2C9
     **/
    function frame($width=6, $color='666666')
    {
        //$command = "{$this->imagemagickdir}/convert -mattecolor '#{$color}' -frame '{$width}x{$width}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-mattecolor '#{$color}' -frame '{$width}x{$width}'");
        if($this -> verbose == true) {
            echo "Frame:\n";
            echo "  Width: {$width}\n";
            echo "  Color: {$color}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Frame failed\n";
            if($this -> verbose == true) {
                echo "Frame failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::resize()
     *
     * Passt die Groesse des Bildes an.
     *
     * @access public
     * @param integer $x_size x-size
     * @param integer $y_size y-size
     * @param string $how resize Methode; 'keep_aspect' > aendert nur Breite oder Hoehe des Bildes (behaelt Proportion), 'fit' gleicht das Bild an die gegebene Groesse an.
     **/
    function resize($x_size, $y_size, $how='keep_aspect')
    {
        $method = $how=='keep_aspect'?'>':($how=='fit'?'!':'');
        //$command = "{$this->imagemagickdir}/convert -geometry '{$x_size}x{$y_size}{$method}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-geometry '{$x_size}x{$y_size}{$method}'");
        if($this -> verbose == true) {
            echo "Resize:\n";
            echo "  Resize method: {$how}\n";
            echo "  Command: {$command}\n";
        }

        exec($command, $returnarray, $returnvalue);

        if($returnvalue) {
            $this -> error .= "ImageMagick: Resize failed\n";
            if($this -> verbose == true) {
                echo "Resize failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::square()
     *
     * Macht das Bild zu einem Quadrat
     *
     * @param string $how 'center' > schneidet ein Quadrat von der Mitte des Bildes heraus, 'left' > schneidet ein Quadrat von der linken Seite des Bildes heraus, 'right' > schneidet ein Quadrat von der rechten Seite des Bildes heraus
     **/
    function square($how='center')
    {
        $this -> size = $this -> getSize();
        if($how == 'center') {
            if($this -> size[0] > $this -> size[1]) {
                $line = $this -> size[1].'x'.$this -> size[1].'+'.round((($this -> size[0] - $this -> size[1]) / 2)).'+0';
            }
            else {
                $line = $this -> size[0].'x'.$this -> size[0].'+0+'.round((($this -> size[1] - $this -> size[0])) / 2);
            }
        }
        if($how == 'left') {
            if($this -> size[0] > $this -> size[1]) {
                $line = $this -> size[1].'x'.$this -> size[1].'+0+0';
            }
            else {
                $line = $this -> size[0].'x'.$this -> size[0].'+0+0';
            }
        }
        if($how == 'right') {
            if($this -> size[0] > $this -> size[1]) {
                $line = $this -> size[1].'x'.$this -> size[1].'+'.($this -> size[0]-$this -> size[1]).'+0';
            }
            else {
                $line = $this -> size[0].'x'.$this -> size[0].'+0+'.($this -> size[1] - $this -> size[0]);
            }
        }

        $command = "{$this->imagemagickdir}/convert -crop '{$line}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-crop '{$line}'");

        if($this -> verbose == true) {
            echo "Square:\n";
            echo "  Method: {$how}\n";
            echo "  Command: {$command}\n";
        }
        exec($command, $returnarray, $returnvalue);
        if($returnvalue) {
            $this -> error .= "ImageMagick: Square failed\n";
            if($this -> verbose == true) {
                echo "Square failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::crop()
     *
     * Schneidet das Bild in der gegebenen Groesse.
     *
     * @param integer $size_x x-size
     * @param integer $size_y y-size
     * @param string $how Methode 'center' > crops the image leaving the center, 'left' > crops only from the right side, 'right' > crops only from the left side
     * @return
     **/
    function crop($size_x, $size_y, $how='center')
    {
        if($this -> verbose == true) {
            echo "Crop:\n";
        }

        $this -> size = $this -> getSize();

        if($size_x > $this -> size[0]) {
            $size_x = $this -> size[0];
        }

        if($size_y > $this -> size[1]) {
            $size_y = $this -> size[1];
        }

        if($this -> verbose == true) {
            echo "  Args: size_x = {$size_x}\n";
            echo "  Args: size_y = {$size_y}\n";
            echo "  Crop method: {$how}\n";
            echo "  GetSize: size_x = {$this->size[0]}\n";
            echo "  GetSize: size_y = {$this->size[1]}\n";
        }

        if($how == 'center') {
            $line = $size_x.'x'.$size_y.'+'.round( ($this->size[0] - $size_x) / 2 ).'+'.round((($this->size[1] - $size_y) / 2));
        }

        if($how == 'left') {
            $line = $size_x.'x'.$size_y.'+0+0';
        }

        if($how == 'right') {
            $line = $size_x.'x'.$size_y.'+'.($this->size[0] - $size_x).'+0';
        }

        //$command = "{$this->imagemagickdir}/convert -crop '{$line}' '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
        $command = $this -> getCommandConvert("-crop '{$line}'");

        if($this -> verbose == true) {
            echo "  Command: {$command}\n";
        }

        exec($command, $returnarray, $returnvalue);

        if($returnvalue) {
            $this -> error .= "ImageMagick: Crop failed\n";
            if($this -> verbose == true) {
                echo "crop failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::convert()
     *
     * Konvertiert ein Bild in irgendein Format (using the given file extension)
     * Default ist jpg
     *
     * @access public
     * @param string $format Format (siehe ImageMagick Dokumentation)
     **/
    function convert($format='jpg')
    {
        $name = explode('.' , $this->temp_file);
        $name = "{$name[0]}.{$format}";

        //$command = "{$this->imagemagickdir}/convert -colorspace RGB -quality {$this->jpg_quality} '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$name}'";
        $command = $this -> getCommandConvert("-colorspace RGB");

        if($this -> verbose == true) {
            echo "Convert:\n";
            echo "  Desired format: {$format}\n";
            echo "  Constructed filename: {$name}\n";
            echo "  Command: {$command}\n";
        }

        exec($command, $returnarray, $returnvalue);

        $this -> temp_file = $name;

        if($returnvalue) {
            $this -> error .= "ImageMagick: Convert failed\n";
            if($this -> verbose == true) {
                echo "Convert failed\n";
            }
        }
        else {
            $this -> file_history[] = $this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file;
        }
    }

    /**
     * ImageMagick::getCommandConvert()
     *
     * @access private
     * @param string $command Spezielles Kommando
     * @return
     **/
    function getCommandConvert($command)
    {
        return "{$this->imagemagickdir}/convert -quality {$this->jpg_quality} {$command} '{$this->temp_dir}/tmp{$this->count}_{$this->temp_file}' '{$this->temp_dir}/tmp".++$this->count."_{$this->temp_file}'";
    }

    /**
     * ImageMagick::save()
     *
     * Speichert das Bild ins Zielverzeichnis $this -> targetdir
     *
     * @access public
     * @param string $prefix Prefix an den Dateinamen anhaengen (Standard kein Wert), z.B. 'thumb_'
     **/
    function save($prefix='')
    {
        if($this -> verbose == true) {
            echo "Save:\n";
        }

        if(!@copy($this -> temp_dir . '/tmp' . $this -> count . '_' . $this -> temp_file, addEndingSlash($this -> targetdir) . $prefix . $this -> targetfile)) {
            $this -> error .= "ImageMagick: Couldn't save to ". addEndingSlash($this->targetdir)."{$prefix}{$this->temp_file}\n";
            if($this -> verbose == true) {
                echo "Save failed to {$this->targetdir}/{$prefix}{$this->temp_file}\n";
            }
        }
        else {
            if($this -> verbose == true) {
                echo "  Saved to {$this->targetdir}/{$prefix}{$this->temp_file}\n";
            }
        }
        return $prefix . $this -> temp_file;
    }

    /**
     * ImageMagick::undo()
     *
     * Rueckgaengig.
     *
     * @param integer $steps Anzahl Schritte zurueck
     **/
    function undo($steps=1)
    {
        $buffer = false;
        $count = count($this -> file_history) - 1;
        $index = $count - $steps;
        if ($index < 0) {
            $index = 0;
        }
        $new_history = array();
        for ($i=0; $i<$count; $i++) {
            //if ($i == $index) {
                //$buffer = $this -> file_history[$i];
            //}
            if ($i <= $index) {
                array_push($new_history, $this -> file_history[$i]);
            }
            else {
                !unlink($this -> file_history[$i]);
            }
        }
        if ($buffer) {
            array_push($new_history, $buffer);
        }

        $this -> file_history = $new_history;
    }

    /**
     * ImageMagick::cleanUp()
     *
     * Saeubert alle temporaeren Daten in $this -> temp_dir
     *
     * @access public
     **/
    function cleanUp()
    {
        if($this -> verbose == true) {
            echo "cleanup:\n";
        }

        $num = count($this -> file_history);

        for($i=0; $i<$num; $i++) {
            if(!unlink($this->file_history[$i])) {
                $this -> error .= "ImageMagick: Removal of temporary file '{$this->file_history[$i]}' failed\n";
                if($this -> verbose == true) {
                    echo "  Removal of temporary file '{$this->file_history[$i]}' failed\n";
                }
            }
            else {
                if($this -> verbose == true) {
                    echo "  Deleted temp file: {$this->file_history[$i]}\n";
                }
            }
        }

        if($this -> verbose == true) {
            echo '</pre>';
        }
    }

    /**
     * PDF in JPG umwandeln (ImageMagick verwendet intern ebenfalls Ghostscript)
     *
     * Bei der 5er Version gibts einen Fehler, daher muss man am Develop die Page Size des PDF's �bergeben
     *
     * @param string $inpPdf
     * @param string $outJpg
     * @param string $resize
     * @param string $crop
     * @param boolean $trim
     * @return boolean
     */
    function pdf2jpg($inpPdf, $outJpg, $resize=false, $crop=false, $trim=false)
    {
        $cmd = $this->__2jpgCmd($inpPdf, $outJpg, $resize, $crop, $trim);

        exec($cmd, $returnarray, $returnvalue);
        if($returnvalue) {
            $this->error .= "ImageMagick: pdf2jpg failed\n";
            if($this->verbose) {
                echo "pdf2jpg failed\n";
            }
            return false;
        }
        else {
            return true;
        }
    }

    /**
     * PNG in JPG umwandeln
     *
     * @param string $inpPng
     * @param string $outJpg
     * @param string $resize
     * @param string $crop
     * @param boolean $trim
     * @return boolean
     */
    function png2jpg($inpPng, $outJpg, $resize=false, $crop=false, $trim=false)
    {
        $cmd = $this->__2jpgCmd($inpPng, $outJpg, $resize, $crop, $trim);

        exec($cmd, $returnarray, $returnvalue);
        if($returnvalue) {
            $this->error .= "ImageMagick: pngjpg failed\n";
            if($this->verbose) {
                echo "png2jpg failed\n";
            }
            return false;
        }
        else {
            return true;
        }
    }

    function __2jpgCmd($inp, $outJpg, $resize=false, $crop=false, $trim=false)
    {
        $cmd = addEndingSlash($this->imagemagickdir).'convert ';

        if($this->verbose) {
            $cmd .= '-verbose ';
        }

        if($this->pageX>=0 and $this->pageY>=0) {
            $cmd .= '-page '.$this->pageX.'x'.$this->pageY.' ';
        }
        else $cmd .= '+page ';

        if($trim) $cmd .= '-trim ';

        if($this->dpi > 0) {
            $cmd .= '-resample '.$this->dpi.' ';
        }

        $cmd .= $inp.' ';

        if($resize) {
            $cmd .= '-resize '.$resize.' ';
        }

        if($crop) {
            $cmd .= '-crop '.$crop.' ';
        }

        if($this->frame) {
            $cmd .= '-frame '.$this->frame.' ';
        }

        if($this->mattecolor) {
            $cmd .= '-mattecolor '.$this->mattecolor.' ';
        }

        if($this->raise) {
            $cmd .= '-raise '.$this->raise.' ';
        }

        $cmd .= '-quality '.$this->jpg_quality.'% ';

        $cmd .= $outJpg;

        if($this->verbose) {
            echo "Convert:\n";
            echo "  Command: {$cmd}\n";
        }

        return $cmd;
    }
}