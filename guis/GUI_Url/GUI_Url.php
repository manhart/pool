<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use pool\classes\Core\Input\Input;
use pool\classes\Core\Url;

/**
 * Class GUI_Url
 *
 * @package pool\guis\GUI_Url
 * @since 2003-08-19
 */
class GUI_Url extends GUI_Module
{
    /**
     * @var bool no files needed
     */
    protected bool $autoLoadFiles = false;

    /**
     * @var string contains the rendered url
     */
    private string $content = '';

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @param int|null $superglobals Superglobals (siehe Klasse Input)
     *
     * @throws Exception
     */
    public function init(?int $superglobals = Input::EMPTY)
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
    protected function prepare(): void
    {
        $withQuery = !$this->Input->getAsBool('empty');

        $script = $this->Input->getVar('script');
        if($script != '') {
            $Url = Url::fromString($script);
        }
        else {
            $Url = new Url($withQuery);
        }

        $params = $this->Input->getVar('params');
        if($params) {
            $pieces = explode(';', $params);
            foreach($pieces as $piece) {
                $param = explode(':', $piece);
                $key = $param[0];
                $value = $param[1] ?? null;
                $Url->setParam($key, $value);
            }
        }

        $passThrough = $this->Input->getVar('passthrough');
        if($passThrough) {
            $IGet = new Input(Input::GET);
            $passThrough = explode(';', $passThrough);
            foreach($passThrough as $param) {
                $Url->setParam($param, $IGet->getVar($param));
            }
            unset($IGet);
        }
        $eliminate = $this->Input->getVar('eliminate');
        if($eliminate) {
            $eliminate = explode(';', $eliminate);
            foreach($eliminate as $param) {
                $Url->setParam($param, null);
            }
        }

        $this->content = $Url->getUrl();
    }

    /**
     * @return string return url
     */
    protected function finalize(): string
    {
        return $this->content;
    }
}