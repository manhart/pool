<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_box.class.php
 *
 * GUI_DHtmlXGridExport dient dem dhtmlxGrid zum Exportieren von PDF's und Excel-Dateien.
 *
 * @version $Id: gui_dhtmlxgridexport.class.php 37657 2019-03-20 16:46:08Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2011-01-12
 * @see http://www.dhtmlx.com/docs/products/goodies/index.shtml
 * @author Alexander Manhart <alexander.manhart@gmx.de>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DHtmlXGridExport
 *
 * Klasse zum Erstellen von graphischen Boxen (z.B. News-Boxen, Bl�cke, Container).
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_dhtmlxgridexport.class.php 37657 2019-03-20 16:46:08Z manhart $
 * @access public
 **/
class GUI_DHtmlXGridExport extends GUI_Module
{
    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param integer|null $superglobals Superglobals (siehe Klasse Input)
     **/
    function init(?int $superglobals = Input::INPUT_REQUEST)
    {
        $this->Defaults->addVar('type', 'pdf');

        $this->Defaults->addVar('name', 'grid');
        $this->Defaults->addVar('dest', 'I');

        // TODO PDF Settings @see http://docs.dhtmlx.com/doku.php?id=dhtmlxgrid:pdf
        $this->Defaults->addVar('headerImage', '');
        $this->Defaults->addVar('footerImage', '');
        $this->Defaults->addVar('strip_tags', false);
        parent::init($superglobals);
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        set_time_limit(0);
        $grid_xml = $this->Input->getVar('grid_xml');
        if (get_magic_quotes_gpc()) {
            $grid_xml = stripslashes($grid_xml);
        }
        $grid_xml = urldecode($grid_xml);
        $xml = simplexml_load_string($grid_xml);

        $strip_tags = $this->Input->getVar('strip_tags');
        if(is_string($strip_tags)) $strip_tags = string2bool($strip_tags);

        $type = $this->Input->getVar('type');
        $filename = $this->Input->getVar('name').'.'.$type;

        if($type == 'pdf') {
            require_once(DIR_3RDPARTY_ROOT.'/tcpdf-5.9.136.lib.php'); // TCPDF 5.9
            require_once(DIR_3RDPARTY_ROOT.'/grid2pdf-100909.lib.php'); // grid2pdf 0.2
            $Pdf = new gridPdfGenerator();

            if($headerImage = $this->Input->getVar('headerImage')) $Pdf->headerFile = $headerImage;
            if($footerImage = $this->Input->getVar('footerImage')) $Pdf->footerFile = $footerImage;
            // TODO PDF Settings @see http://docs.dhtmlx.com/doku.php?id=dhtmlxgrid:pdf

            $Pdf->strip_tags = $strip_tags;

            $Pdf->printGrid($xml, $filename, $this->Input->getVar('dest'));
        }
        elseif($this->Input->getVar('type') == 'xls') {
            require_once(DIR_3RDPARTY_ROOT.'/PHPExcel-1.7.6.lib.php'); // PHPExcel 1.7.6
            require_once(DIR_3RDPARTY_ROOT.'/grid2xls-100909.lib.php'); // grid2pdf 100909

            $Excel = new gridExcelGenerator();
            $Excel->printGrid($xml, $filename);
        }
    }

    /**
     * Box Inhalt parsen und zurueck geben.
     *
     * @return string Content
     **/
    function finalize($content = ''): string
    {
        return $content;
    }
}