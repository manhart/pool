<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_url.class.php
 *
 * @version $Id: gui_url.class.php,v 1.4 2007/05/31 14:34:40 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Url
 *
 * Klasse zum Erstellen von graphischen Boxen (z.B. News-Boxen, Bl�cke, Container).
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_url.class.php,v 1.4 2007/05/31 14:34:40 manhart Exp $
 * @access public
 **/
class GUI_Url extends GUI_Module
{
    var $returnValue = '';

    public function __construct(&$Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param integer $superglobals Superglobals (siehe Klasse Input)
     **/
    function init($superglobals=0)
    {
        $this->Defaults -> addVar('script', '');
        $this->Defaults -> addVar('params', '');
        $this->Defaults -> addVar('passthrough', '');
        $this->Defaults -> addVar('eliminate', '');
        $this->Defaults -> addVar('empty', 0);
        parent::init($superglobals);
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $Input = & $this -> Input;

        if ($Input -> getVar('empty')) {
            $Url = new Url(0);
        }
        else {
            $Url = new Url();
        }

        $script = trim($Input -> getVar('script'));
        if ($script != '') {
            $Url->setScript($script);
        }

        $params = trim($Input -> getVar('params'));

        if ($params != '') {
            $pieces = explode(';', $params);
            foreach ($pieces as $piece) {
                $param = explode(':', $piece);
                $key = $param[0];
                $value = isset($param[1]) ? $param[1] : null;
                $Url -> modifyParam($key, $value);
            }
        }

        $passthrough = trim($Input -> getVar('passthrough'));
        if ($passthrough != '') {
            $IGet = new Input(I_GET);
            $passthrough = explode(';', $passthrough);
            foreach($passthrough as $param) {
                $Url -> modifyParam($param, $IGet -> getVar($param));
            }
            unset($IGet);
        }
        $eliminate = trim($Input -> getVar('eliminate'));
        if ($eliminate != '') {
            $eliminate = explode(';', $eliminate);
            foreach ($eliminate as $param) {
                $Url -> modifyParam($param, NULL);
            }
        }
        #echo $Url -> getUrl()."<br>";
        $this -> returnValue = $Url -> getUrl();
    }

    /**
     * Box Inhalt parsen und zurueck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        return $this->returnValue;
    }
}