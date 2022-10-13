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
 * Fast way to render a URL in.
 *
 * @package pool
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: gui_url.class.php,v 1.4 2007/05/31 14:34:40 manhart Exp $
 **/
class GUI_Url extends GUI_Module
{
    private string $returnValue = '';

    public function __construct(&$Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param integer|null $superglobals Superglobals (siehe Klasse Input)
     **/
    function init(?int $superglobals=I_EMPTY)
    {
        $this->Defaults->addVar('script', '');
        $this->Defaults->addVar('params', '');
        $this->Defaults->addVar('passthrough', '');
        $this->Defaults->addVar('eliminate', '');
        $this->Defaults->addVar('empty', 0);
        parent::init($superglobals);
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $empty = (int)$this->Input->getVar('empty');
        $Url = new Url($empty ? I_EMPTY : I_GET);

        $script = trim($this->Input->getVar('script'));
        if ($script != '') {
            $Url->setScript($script);
        }

        $params = trim($this->Input->getVar('params'));
        if ($params != '') {
            $pieces = explode(';', $params);
            foreach ($pieces as $piece) {
                $param = explode(':', $piece);
                $key = $param[0];
                $value = $param[1] ?? null;
                $Url->setParam($key, $value);
            }
        }

        $passthrough = trim($this->Input->getVar('passthrough'));
        if ($passthrough != '') {
            $IGet = new Input(I_GET);
            $passthrough = explode(';', $passthrough);
            foreach($passthrough as $param) {
                $Url->setParam($param, $IGet -> getVar($param));
            }
            unset($IGet);
        }
        $eliminate = trim($this->Input->getVar('eliminate'));
        if ($eliminate != '') {
            $eliminate = explode(';', $eliminate);
            foreach ($eliminate as $param) {
                $Url->setParam($param, NULL);
            }
        }

        $this->returnValue = $Url->getUrl();
    }

    /**
     * Box Inhalt parsen und zurueck geben.
     *
     * @return string Content
     **/
    function finalize(): string
    {
        return $this->returnValue;
    }
}