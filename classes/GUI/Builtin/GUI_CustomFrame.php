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

/** @noinspection PhpUnused, PhpUnusedParameterInspection */

namespace pool\classes\GUI\Builtin;

use pool\classes\Core\Component;
use pool\classes\Exception\ModulNotFoundException;
use pool\classes\GUI\GUI_Module;
use pool\classes\translator\TranslationProvider_ToolDecorator;
use pool\guis\GUI_HeadData\GUI_HeadData;

/**
 * GUI_CustomFrame is an abstract class. The main purpose of this class is to
 * keep header, menu, footer and sidebar in a central place.
 * In 85-90% of cases, the header and footer are the same on each page and only the content changes.
 *
 * @since 2003-07-10
 * @link https://github.com/manhart/pool
 */
class GUI_CustomFrame extends GUI_Module
{
    protected GUI_HeadData $HeadData;

    /**
     * @var array event container
     */
    private array $events = [/*
        'onafterprint' => [], // Script to be run after the document is printed
        'onbeforeprint' => [], // Script to be run before the document is printed
        'onbeforeunload' => [], // Script to be run when the document is about to be unloaded
        'onerror' => [], // Script to be run when an error occurs
        'onhashchange' => [],// Script to be run when there has been changes to the anchor part of the a URL
        'onload' => [], // Fires after the page is finished loading
        'onmessage' => [], // Script to be run when the message is triggered
        'onoffline' => [], // Script to be run when the browser starts to work offline
        'ononline' => [], // Script to be run when the browser starts to work online
        'onpagehide' => [], // Script to be run when a user navigates away from a page
        'onpageshow' => [], // Script to be run when a user navigates to a page
        'onpopstate' => [], // Script to be run when the window's history changes
        'onresize' => [], // Fires when the browser window is resized
        'onstorage' => [], // Script to be run when a Web Storage area is updated
        'onunload' => [], // Fires once a page has unloaded (or the browser window has been closed)
*/
    ];

    private array $bodyClasses = [];

    /**
     * @var array|callable|null
     */
    private $addFileFct = null;

    private array $scriptAtTheEnd = [];

    private array $scriptFilesAtTheEnd = [];

    private array $scriptWhenReady = [];

    /**
     * @throws ModulNotFoundException|\Exception
     */
    public function __construct(?Component $Owner, array $params = [])
    {
        parent::__construct($Owner, $params);
        $GUI_Module = GUI_Module::createGUIModule(GUI_HeadData::class, $this->Weblication, $this);
        assert($GUI_Module instanceof GUI_HeadData);
        $this->HeadData = $GUI_Module;
        $this->HeadData->setName('HeadData');
        $this->HeadData->setMarker('<headdata></headdata>');
        $this->insertModule($this->HeadData);
    }

    /**
     * Load default Weblication.js, GUI_Module.js, Error.js, helpers.js, translatorToolInline.css and translatorToolInline.js
     */
    public function loadFiles(): static
    {
        parent::loadFiles();
        if (@TranslationProvider_ToolDecorator::isActive()) {
            $this->HeadData->addStyleSheet($this->Weblication->findStyleSheet('translatorToolInline.css'));
            $this->HeadData->addJavaScript($this->Weblication->findJavaScript('translatorToolInline.js', '', true));
        }
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('helpers.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('Error.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('Weblication.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('GUI_Module.js', '', true));
        return $this;
    }

    /**
     * Returns the GUI_HeadData object for modifying HTML head data.
     *
     * @return GUI_HeadData Head of HTML
     */
    public function getHeadData(): GUI_HeadData
    {
        return $this->HeadData;
    }

    /**
     * Adds one or more CSS classes to the body.
     * Automatically stacks new classes without allowing duplicates.
     *
     * @param string|array $classes Single class or array of classes.
     * @return $this
     */
    public function addBodyClass(string|array $classes): static
    {
        if (is_string($classes)) {
            $classes = [$classes];
        }

        // Merge classes and remove duplicates
        $this->bodyClasses = array_unique(array_merge($this->bodyClasses, $classes));
        return $this;
    }

    /**
     * Removes one or more CSS classes from the body.
     *
     * @param string|array $classes Single class or array of classes.
     * @return $this
     */
    public function removeBodyClass(string|array $classes): static
    {
        if (is_string($classes)) {
            $classes = [$classes];
        }

        $this->bodyClasses = array_diff($this->bodyClasses, $classes);
        return $this;
    }

    /**
     * Checks if the body has a specific CSS class.
     *
     * @param string $class The class to check.
     * @return bool Returns `true` if the class exists, `false` otherwise.
     */
    public function hasBodyClass(string $class): bool
    {
        return in_array($class, $this->bodyClasses);
    }

    /**
     * Returns all current body classes as a string.
     *
     * @return string The classes as a space-separated string.
     */
    public function getBodyClasses(): string
    {
        return implode(' ', $this->bodyClasses);
    }

    /**
     * Adds a window event to the HTML body
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/body?retiredLocale=de
     * @param string $event an event like onload
     */
    public function addBodyEvent(string $event, string $function): static
    {
        if (!isset($this->events[$event])) {
            $this->events[$event] = [];
        }

        if (!in_array($function, $this->events[$event])) {
            $this->events[$event][] = $function;
        }
        return $this;
    }

    /**
     * Add a JavaScript file at the end of the body
     *
     * @param null $position (not yet implemented, should control position)
     */
    public function addScriptFileAtTheEnd(string $jsFile, $position = null): static
    {
        if ($this->addFileFct) {
            $jsFile = call_user_func($this->addFileFct, $jsFile);
        }
        array_unshift($this->scriptFilesAtTheEnd, $jsFile);
        return $this;
    }

    /**
     * Add JavaScript or a JavaScript function at the end of the body
     */
    public function addScriptAtTheEnd(string $function): static
    {
        array_unshift($this->scriptAtTheEnd, $function);
        return $this;
    }

    /**
     * Add JavaScript or a JavaScript function at the end of the body and call it when DOM is loaded/ready
     */
    public function addScriptWhenReady(string $function): static
    {
        array_unshift($this->scriptWhenReady, $function);
        return $this;
    }

    /**
     * Set callable for event "onAddFile"
     *
     * @see GUI_CustomFrame::addScriptFileAtTheEnd()
     */
    public function onAddFile(callable $addFileFct): GUI_CustomFrame
    {
        $this->addFileFct = $addFileFct;
        return $this;
    }

    /**
     * Calls Weblication->run on the client
     *
     */
    public function prepareContent(): void
    {
        $this->Template->setVar('lang', $this->Weblication->getLanguage());
        parent::prepareContent();
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('run.js', '', true));
    }

    /**
     * Puts JavaScript code into the template
     *
     * @return string parsed content
     */
    protected function finalize(): string
    {
        $scriptAtTheEnd = count($this->scriptAtTheEnd) ? implode(';', $this->scriptAtTheEnd) : '';

        $scriptFilesAtTheEnd = '';
        if (count($this->scriptFilesAtTheEnd)) {
            foreach ($this->scriptFilesAtTheEnd as $scriptFile) {
                $scriptFilesAtTheEnd .= '<script src="'.$scriptFile.'"></script>'.chr(10);
            }
        }

        $scriptWhenReady = count($this->scriptWhenReady) ? implode(';', $this->scriptWhenReady) : '';


        // no templates assigned
        if (!$this->Template->countFileList()) {
            return '';
        }

        if ($scriptWhenReady || $scriptAtTheEnd) {
            $inlineScriptBlock = $this->Template->newBlock('INLINE-SCRIPT');
            $inlineScriptBlock?->setVars([
                'ScriptWhenReady' => $scriptWhenReady,
                'ScriptAtTheEnd' => $scriptAtTheEnd,
            ]);
            $this->Template->leaveBlock();
        }
        $this->Template->setVar('ScriptFilesAtTheEnd', $scriptFilesAtTheEnd);

        $vars = array_map(function ($functions) {
            // concatenating javascript functions
            return implode(';', $functions);
        }, $this->events);
        $vars['bodyClass'] = $this->getBodyClasses();
        $this->Template->setVars($vars);

        return parent::finalize();
    }
}
