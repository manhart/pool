<?php

use pool\classes\Core\Input;
use pool\classes\Core\Url;

/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_url.class.php - fast way to render an url
 *
 * @version $Id: gui_url.class.php,v 1.4 2007/05/31 14:34:40 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-19
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

class GUI_Url extends GUI_Module
{
    /**
     * @var string contains the rendered url
     */
    private string $content = '';

    /**
     * @var bool no files needed
     */
    protected bool $autoLoadFiles = false;

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @param int|null $superglobals Superglobals (siehe Klasse Input)
     *
     * @throws Exception
     */
    public function init(?int $superglobals= Input::INPUT_EMPTY)
    {
        $this->Defaults->addVar('script');
        $this->Defaults->addVar('params');
        $this->Defaults->addVar('passthrough');
        $this->Defaults->addVar('eliminate');
        $this->Defaults->addVar('empty', 0);
        parent::init($superglobals);
    }

    /**
     * prepare url
     */
    public function prepare()
    {
        $empty = (int)$this->Input->getVar('empty');

        $script = $this->Input->getVar('script');
        if ($script != '') {
            $Url = Url::fromString($script);
        }
        else {
            $Url = new Url(!$empty);
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

        $passThrough = trim($this->Input->getVar('passthrough'));
        if ($passThrough != '') {
            $IGet = new Input(Input::INPUT_GET);
            $passThrough = explode(';', $passThrough);
            foreach($passThrough as $param) {
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

        $this->content = $Url->getUrl();
    }

    /**
     * @return string return url
     */
    public function finalize(): string
    {
        return $this->content;
    }
}