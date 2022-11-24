<?php
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
     * @var GUI_Head
     */
    protected GUI_Head $Head;

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

    //@var string Body Event OnUnload=""
    //@access private
    var $DoUnload = array();

    //@var string Body Event OnMouseover=""
    //@access private
    var $DoMouseover = array();

    //@var string Body Event OnMousemove=""
    //@access private
    var $DoMousemove = array();

    //@var string Body Event OnMouseout=""
    //@access private
    var $DoMouseout = array();

    //@var string Body Event OnMousedown=""
    //@access private
    var $DoMousedown = array();

    //@var string Body Event OnMouseup=""
    //@access private
    var $DoMouseup = array();

    //@var string Body Event OnKeydown=""
    //@access private
    var $DoKeydown = array();

    /**
     * JavaScript-Funktionen fuer Onkeypress
     *
     * @var array JavaScript-Funktionen fuer Onkeypress
     */
    var $DoKeypress = array();

    /**
     * @var bool Verhindert das voreingestellte Laden des GUI's Headerdata
     */
    var $preventDefaultHeaderdata = false;

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
     * Konstruktor: erzeugt das GUI_Head Object
     *
     * Um DynToolTip zu aktivieren, wird im Frame ein DIV Element (innerhalb des body-tags) gebraucht:
     * <DIV id="TipLayer" style="visibility:hidden;position:absolute;z-index:1000;top:-100;"></DIV>
     *
     * @param object $Owner
     * @param array $params
     */
    function __construct($Owner, array $params = [])
    {
        parent::__construct($Owner, $params);

        if(!$this->preventDefaultHeaderdata) {
            $this->Head = new GUI_Head($Owner);
            $this->Head->loadFiles();
            $this->Head->setName('Head');
        }
    }

    /**
     * load default Weblication.class.js and GUI_Module.class.js
     */
    public function loadFiles()
    {
        if(!$this->preventDefaultHeaderdata) {
            $this->Head->addJavaScript($this->Weblication->findJavaScript('Weblication.class.js', '', true));
            $this->Head->addJavaScript($this->Weblication->findJavaScript('GUI_Module.class.js', '', true));
        }
    }

    /**
     * Liefert das GUI_Head Object zum Aendern der Html Kopfdaten.
     *
     * @return GUI_Head Head of HTML
     */
    public function getHead(): GUI_Head
    {
        return $this->Head;
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
     * Fuegt die Html Kopfdaten zur Seite hinzu.
     *
     * @param string $content
     * @return string Inhalt (Content)
     **/
    public function finalize(string $content=''): string
    {
        $headTag = '<head>';
        if(!$this->preventDefaultHeaderdata) {
            $this->Head->prepare();
            $content_header = $this->Head->finalize();
        }

        $scriptAtTheEnd = count($this->scriptAtTheEnd) ? implode(';', $this->scriptAtTheEnd) : '';
        $scriptFilesAtTheEnd = '';
        if(count($this->scriptFilesAtTheEnd)) {
            foreach($this->scriptFilesAtTheEnd as $scriptFile) {
                $scriptFilesAtTheEnd .= '<script src="' . $scriptFile . '"></script>'.chr(10);
            }
        }
        $scriptWhenReady = count($this->scriptWhenReady) ? implode(';', $this->scriptWhenReady) : '';

        $content = str_replace(
            ['{ScriptWhenReady}', '{ScriptAtTheEnd}', '{ScriptFilesAtTheEnd}'],
            [$scriptWhenReady, $scriptAtTheEnd, $scriptFilesAtTheEnd],
            $content
        );

        $replace_pair = [];
        foreach($this->events as $event => $functions) {
            $replace_pair['{'.$event.'}'] = implode(';', $functions);
        }
        $content = strtr($content, $replace_pair);

        return str_ireplace($headTag, "$headTag\n$content_header", $content);
    }
}