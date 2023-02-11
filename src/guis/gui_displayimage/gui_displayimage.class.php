<?php
/**
 * -= PHP Object Oriented Library =-
 *
 * gui_displayimage.class.php
 *
 * Zeigt ein Bild an.
 *
 * @version $Id: gui_displayimage.class.php,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DisplayImage
 *
 * Simple Class to show a picture.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_displayimage.class.php,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
 * @access public
 **/
class GUI_DisplayImage extends GUI_Module
{
    /**
     * @var bool
     */
    protected bool $autoLoadFiles = false;

    /**
     * GUI_DisplayImage::init()
     *
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     **/
    function init(?int $superglobals=I_EMPTY)
    {
        $this->Defaults -> addVar('border', 0);
        $this->Defaults -> addVar('filename', 'images/pl.gif');
        $this->Defaults -> addVar('readdirect', 0);
        $this->Defaults -> addVar('maxHeight', 0);
        $this->Defaults -> addVar('maxWidth', 0);
        $this->Defaults -> addVar('height', 0);
        $this->Defaults -> addVar('width', 0);
        $this->Defaults -> addVar('keep_aspect', 0);
        $this->Defaults -> addVar('link', 0);
        $this->Defaults -> addVar('hint', '');

        parent::init($superglobals);
    }

    /**
     * GUI_DisplayImage::loadFiles()
     *
     * Templates laden.
     *
     * @access public
     **/
    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_displayimage.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    /**
     * GUI_DisplayImage::prepare()
     *
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $this -> loadFiles();
        $Template = & $this -> Template;
        $Input = & $this -> Input;

        $filename = $Input -> getVar('filename');
        $maxHeight = $Input -> getVar('maxHeight');
        $maxWidth = $Input -> getVar('maxWidth');
        $keep_aspect = $Input -> getVar('keep_aspect');

        /*
        * Im Array-Index 0 steht die Breite. Index 1 enth�lt die H�he, 2 ein Flag je nach Grafik-Typ (1 = GIF, 2 = JPG, 3 = PNG, SWF = 4)
        * und Index 3 die richtige Zeichenkette im Format "height=xxx width=xxx" zur Verwendung im IMG-Tag von HTML.
        */
        $size = getimagesize($filename);
        if ($maxWidth > 0 and $maxWidth < $size[0]) {
            $width = $maxWidth;
            if ($keep_aspect) {
                $height = round(($width * $size[1]) / $size[0]);
                $size[0] = (int)$width;
                $size[1] = (int)$height;
            }
        }
        else {
            $width = $size[0];
        }
        if ($maxHeight > 0 and $maxHeight < $size[1]) {
            $height = $maxHeight;
            if ($keep_aspect) {
                $width = round(($height * $size[0]) / $size[1]);
                //$size[0] = (int)$width;
                //$size[1] = (int)$height;
            }
        }
        else {
            $height = $size[1];
        }

        if ($Input -> getVar('readdirect')) {
            $filename = addEndingSlash(DIR_RELATIVE_BASELIB_ROOT) . 'displayimage.php?image=' . $filename;
        }

        $Template -> setVar(
            array(
                'border' => $Input -> getVar('border'),
                'filename' =>  $filename,
                'width' => $width,
                'height' => $height,
                'hint' => $Input -> getVar('hint')
            )
        );
    }

    /**
     * GUI_DisplayImage::finalize()
     *
     * Template parsen.
     *
     * @return string Content
     **/
    function finalize($content = ''): string
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}