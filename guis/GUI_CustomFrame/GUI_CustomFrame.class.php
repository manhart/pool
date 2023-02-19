<?php

use pool\classes\Core\Component;
use pool\classes\Exception\ModulNotFoundException;

/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_customframe.class.php
 *
 * GUI_CustomFrame ist eine abstrakte Klasse. Der Haupteinsatzzweck dieser Klasse besteht darin,
 * Kopf- Menue- Fuss- und Seitenleiste an zentraler Stelle zu halten.
 * In 85-90% der Faelle ist der Kopf und die Fusszeile auf jeder Seite gleich und nur der Inhalt aendert sich!
 *
 * @version $Id: gui_customframe.class.php,v 1.5 2006/01/19 10:07:05 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link http://www.misterelsa.de
 */

class GUI_CustomFrame extends GUI_Module
{
    /**
     * @var GUI_HeadData
     */
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

    /**
     * @var array|callable|null
     */
    private $addFileFct = null;

    /**
     * @var array
     */
    private array $scriptAtTheEnd = [];

    /**
     * @var array
     */
    private array $scriptFilesAtTheEnd = [];

    /**
     * @var array
     */
    private array $scriptWhenReady = [];

    /**
     * @param Component|null $Owner
     * @param array $params
     * @throws ModulNotFoundException
     */
    function __construct(?Component $Owner, array $params = [])
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
     * load default Weblication.class.js and GUI_Module.class.js
     */
    public function loadFiles()
    {
        parent::loadFiles();
        if (@\pool\classes\translator\TranslationProvider_ToolDecorator::isActive()){
            $this->HeadData->addStyleSheet($this->Weblication->findStyleSheet('translatorToolInline.css', '', false));
            $this->HeadData->addJavaScript($this->Weblication->findJavaScript('translatorToolInline.js', '', true));
        }
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('helpers.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('Error.class.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('Weblication.class.js', '', true));
        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('GUI_Module.class.js', '', true));
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
     */
    public function addBodyEvent(string $event, string $function): self
    {
        if(!isset($this->events[$event])) $this->events[$event] = [];

        if(!in_array($function, $this->events[$event])) {
            $this->events[$event][] = $function;
        }
        return $this;
    }

    /**
     * Add a javascript file at the end of the body
     *
     * @param string $jsFile
     * @param null $position (not yet implemented, should control position)
     */
    public function addScriptFileAtTheEnd(string $jsFile, $position=null)
    {
        if($this->addFileFct) $jsFile = call_user_func($this->addFileFct, $jsFile);
        array_unshift($this->scriptFilesAtTheEnd, $jsFile);
    }

    /**
     * Add javascript or a javascript function at the end of the body
     *
     * @param string $function
     */
    public function addScriptAtTheEnd(string $function)
    {
        array_unshift($this->scriptAtTheEnd, $function);
    }

    /**
     * Add javascript or a javascript function at the end of the body and call it when DOM is loaded/ready
     *
     * @param string $function
     */
    public function addScriptWhenReady(string $function)
    {
        array_unshift($this->scriptWhenReady, $function);
    }

    /**
     * set callable for event on add file
     *
     * @param callable $addFileFct
     * @return GUI_CustomFrame
     * @see GUI_CustomFrame::addScriptFileAtTheEnd()
     */
    public function onAddFile(callable $addFileFct): GUI_CustomFrame
    {
        $this->addFileFct = $addFileFct;
        return $this;
    }

    /**
     * calls Weblication->run on the client
     *
     * @return void
     */
    public function prepareContent()
    {
        parent::prepareContent();

        $this->HeadData->addJavaScript($this->Weblication->findJavaScript('run.js', '', true));
    }

    /**
     * puts javascript code into the template
     *
     * @return string parsed content
     */
    public function finalize(): string
    {
        $scriptAtTheEnd = count($this->scriptAtTheEnd) ? implode(';', $this->scriptAtTheEnd) : '';

        $scriptFilesAtTheEnd = '';
        if(count($this->scriptFilesAtTheEnd)) {
            foreach($this->scriptFilesAtTheEnd as $scriptFile) {
                $scriptFilesAtTheEnd .= '<script src="' . $scriptFile . '"></script>'.chr(10);
            }
        }

        $scriptWhenReady = count($this->scriptWhenReady) ? implode(';', $this->scriptWhenReady) : '';


        // no templates assigned
        if(!$this->Template->countFileList()) {
            return '';
        }

        if($scriptWhenReady || $scriptAtTheEnd) {
            $InlineScriptBlock = $this->Template->newBlock('INLINE-SCRIPT');
            $InlineScriptBlock?->setVars([
                'ScriptWhenReady' => $scriptWhenReady,
                'ScriptAtTheEnd' => $scriptAtTheEnd,
            ]);
            $this->Template->leaveBlock();
        }
        $this->Template->setVar('ScriptFilesAtTheEnd', $scriptFilesAtTheEnd);

        $vars = array_map(function($functions) {
            // concatenating javascript functions
            return implode(';', $functions);
        }, $this->events);
        $this->Template->setVars($vars);

        return parent::finalize();
    }
}