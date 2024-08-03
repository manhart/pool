<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

use pool\classes\Core\Input\Input;

/**
 * Class GUI_HTMLElement
 *
 * @package pool\guis\GUI_HTMLElement
 * @since 2004/07/07
 */
class GUI_HTMLElement extends GUI_Module
{
    /**
     * @var string ID (unique identifier)
     */
    protected readonly string $id;
    protected string $attributes = '';

    public function init(?int $superglobals = Input::EMPTY): void
    {
        $this->Defaults->addVars([
                /* General universal attributes */
                'id' => $this->getName(),
                'title' => '',
                'class' => $this->getClassName(),
                'class_error' => $this->getClassName().'_error',
                'attributes' => '',
                'autoCapitalize' => null,
                'contentEditable' => null,
                'spellcheck' => null,
                'tabIndex' => null,
                'translate' => true,
                'hidden' => null,
                'nonce' => null,

                /* Universal attributes for internationalization */
                'dir' => '',
                'lang' => null,

                'guierror' => null,
            ]
        );

        parent::init($superglobals);
        $this->id = $this->Input->getVar('id');
    }

    /**
     * main logic
     */
    protected function prepare(): void
    {
        $weblication = $this->getWeblication();
        if($weblication?->hasFrame()) {
            $cssFile = @$weblication->findStyleSheet($this->getClassName().'.css', $this->getClassName(), true, false);
            $weblication->getFrame()->getHeadData()->addStyleSheet($cssFile);
        }

        $class = $this->Input->getVar('class');
        $class_error = $this->Input->getVar('class_error');
        $guierror = $this->Input->getVar('guierror');
        if($guierror and $guierror == $this->Input->getVar('name')) {
            $class = $class_error;
        }

        #### Universal Attribute
        $attributes = ($title = $this->Input->getVar('title')) ? "title=\"$title\"" : '';
        $attributes .= ($class) ? ' class="'.$class.'"' : '';
        $attributes .= ($class_error) ? ' class_error="'.$class_error.'"' : '';
        #### Universal Attribute for Internationalisierung
        $attributes .= ($lang = $this->Input->getVar('lang')) ? " lang=\"$lang\"" : '';
        $attributes .= ($dir = $this->Input->getVar('dir')) ? " dir=\"$dir\"" : '';
        $attributes .= ($custom_attributes = $this->Input->getVar('attributes')) ? " $custom_attributes" : '';
        $this->attributes .= $attributes;
    }
}