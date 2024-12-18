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

namespace pool\classes\GUI\Builtin;

use pool\classes\Core\Component;
use pool\classes\Exception\ModulNotFoundException;
use pool\classes\GUI\GUI_Module;
use pool\classes\translator\TranslationProvider_ToolDecorator;
use pool\guis\GUI_HeadData\GUI_HeadData;

/**
 * GUI_CustomFrame ist eine abstrakte Klasse. Der Haupteinsatzzweck dieser Klasse besteht darin,
 * Kopf- Menue- Fuss- und Seitenleiste an zentraler Stelle zu halten.
 * In 85-90% der Faelle ist der Kopf und die Fusszeile auf jeder Seite gleich und nur der Inhalt aendert sich!
 *
 * @since 2003-07-10
 * @link https://github.com/manhart/pool
 */
class GUI_CustomFrame extends GUI_Module
{
    /**
     * @var GUI_HeadData
     */
    protected GUI_HeadData $HeadData;

    /**
     * @var array event container
     * @deprecated
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

    /**
     * @var array|callable|null
     * @deprecated
     */
    private $addFileFct = null;

    /**
     * @var array
     * @deprecated
     */
    private array $scriptAtTheEnd = [];

    /**
     * @var array
     * @deprecated
     */
    private array $scriptFilesAtTheEnd = [];

    /**
     * @var array
     * @deprecated
     */
    private array $scriptWhenReady = [];

    /**
     * @param Component|null $Owner
     * @param array $params
     * @throws ModulNotFoundException|Exception
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
     * load default Weblication.class.js and GUI_Module.js
     */
    public function loadFiles(): static
    {
        parent::loadFiles();
        $this->HeadData
            ?->addClientWebAsset('js', 'helpers', baseLib: true)
            ?->addClientWebAsset('js', 'Error', baseLib: true)
            ?->addClientWebAsset('js', 'Weblication', baseLib: true)
            ?->addClientWebAsset('js', 'GUI_Module', baseLib: true)
            ?->if(@TranslationProvider_ToolDecorator::isActive())
            ?->addClientWebAsset('js', 'translatorToolInline', baseLib: true)
            ?->addClientWebAsset('css', 'translatorToolInline')
        ;
        return $this;
    }

    /**
     * Liefert das GUI_Head Object zum Aendern der Html Kopfdaten.
     *
     * @return GUI_HeadData Head of HTML
     */
    public function getHeadData(): GUI_HeadData
    {
        return $this->HeadData;
    }

    /**
     * Adds a window event to the html body
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Element/body?retiredLocale=de
     * @param string $event an event like onload
     * @param string $function
     * @return GUI_CustomFrame
     * @deprecated
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
     * Add a javascript file at the end of the body
     *
     * @param string $jsFile
     * @param null $position (not yet implemented, should control position)
     * @return GUI_CustomFrame
     * @deprecated
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
     * Add javascript or a javascript function at the end of the body
     *
     * @param string $function
     * @return GUI_CustomFrame
     * @deprecated
     */
    public function addScriptAtTheEnd(string $function): static
    {
        array_unshift($this->scriptAtTheEnd, $function);
        return $this;
    }

    /**
     * Add javascript or a javascript function at the end of the body and call it when DOM is loaded/ready
     *
     * @param string $function
     * @return GUI_CustomFrame
     * @deprecated
     */
    public function addScriptWhenReady(string $function): static
    {
        array_unshift($this->scriptWhenReady, $function);
        return $this;
    }

    /**
     * set callable for event on add file
     *
     * @param callable $addFileFct
     * @return GUI_CustomFrame
     * @see GUI_CustomFrame::addScriptFileAtTheEnd()
     * @deprecated
     */
    public function onAddFile(callable $addFileFct): GUI_CustomFrame
    {
        $this->addFileFct = $addFileFct;
        return $this;
    }

    /**
     * Calls Weblication->run on the client
     *
     * @return void
     */
    public function prepareContent(): void
    {
        $this->Template->setVar('lang', $this->Weblication->getLanguage());
        parent::prepareContent();
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('run.js', '', true));
    }

    /**
     * puts javascript code into the template
     *
     * @return string parsed content
     */
    protected function finalize(): string
    {
        // no templates assigned
        if (!$this->Template->countFileList()) return '';

        if ($this->scriptWhenReady || $this->scriptAtTheEnd) {
            $InlineScriptBlock = $this->Template->newBlock('INLINE-SCRIPT');
            $InlineScriptBlock?->setVars([
                'ScriptWhenReady' => implode(';', $this->scriptWhenReady),
                'ScriptAtTheEnd' => implode(';', $this->scriptAtTheEnd),
            ]);
            $this->Template->leaveBlock();
        }
        $scriptFilesAtTheEnd = implode(array_map(fn($scriptFile) => "<script src='$scriptFile'></script>\n", $this->scriptFilesAtTheEnd));
        $this->Template->setVar('ScriptFilesAtTheEnd', $scriptFilesAtTheEnd);

        //concatenating event function code
        $eventHandlers = array_map(fn($onEvent) => implode(';', $onEvent), $this->events);
        $this->Template->setVars($eventHandlers);

        return parent::finalize();
    }
}
