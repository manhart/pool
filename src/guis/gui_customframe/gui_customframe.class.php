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
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

/**
 * GUI_CustomFrame
 *
 * Abstrakte Klasse GUI_CustomFrame. An jeder Webseite (an sich) aendert sich meistens nur der Inhalt.
 * Bei diesen Bedingungen kommt GUI_CustomFrame gerade richtig zum Einsatz:
 *
 * GUI_CustomFrame kuemmert sich um den Rahmen der Webseite (Kopfdaten, Fusszeile, Menue, seitliche Boxen).
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_customframe.class.php,v 1.5 2006/01/19 10:07:05 manhart Exp $
 * @access public
 **/
class GUI_CustomFrame extends GUI_Module
{
    /**
     * @var GUI_Headerdata
     */
    public GUI_Headerdata $Headerdata;

    /**
     * ToolTip
     *
     * @var GUI_DynToolTip
     */
    var $DynToolTip;

    //@var string Body Event OnLoad=""
    //@access private
    var $DoLoad = array();

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
     * Verhindert das voreingestellte Laden der "Standard" JavaScript-Dateien
     *
     * @var boolean
     */
    var $preventDefaultJavaScript = false;

    /**
     * @var bool Verhindert das voreingestellte Laden des DynToolTips
     */
    var $preventDefaultDynToolTip = false;

    /**
     * @var bool Verhindert das voreingestellte Laden des GUI's Headerdata
     */
    var $preventDefaultHeaderdata = false;

    /**
     * Konstruktor: erzeugt das GUI_Headerdata Object
     *
     * Um DynToolTip zu aktivieren, wird im Frame ein DIV Element (innerhalb des body-tags) gebraucht:
     * <DIV id="TipLayer" style="visibility:hidden;position:absolute;z-index:1000;top:-100;"></DIV>
     *
     * @access public
     * @param object $Owner
     * @param bool $autoLoadFiles
     * @param array $params
     * @return
     **/
    function __construct(&$Owner, $autoLoadFiles=true, array $params = [])
    {
        parent::__construct($Owner, $autoLoadFiles, $params);

        if(!$this->preventDefaultHeaderdata) {
            $this->Headerdata = new GUI_Headerdata($Owner);
            $this->Headerdata->loadFiles();
            $this->Headerdata->setName('Headerdata');
        }

        if(!$this->preventDefaultDynToolTip) {
            $this->DynToolTip = new GUI_DynToolTip($Owner);
            $this->DynToolTip->loadFiles();
            $this->DynToolTip->setName('DynToolTip');
        }

        if(!$this->preventDefaultJavaScript) {
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('browser.js', $this -> getClassName(), true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('array.js', $this -> getClassName(), true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('helpers.js', $this -> getClassName(), true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('mouseposition.js', $this -> getClassName(), true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('layer.js', $this -> getClassName(), true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('XMLHTTPRequestObject.js', $this -> getClassName(), true));
        }

        //$this -> Headerdata -> addJavaScript($this -> Weblication -> findJavaScript('dyntooltip.js', $this -> getClassName(), true));
        //$this -> Headerdata -> addJavaScript($this -> Weblication -> getRelativePathBaselib(PWD_TILL_JAVASCRIPTS . 'windowmanager.js'));
    }

    /**
     * Liefert das GUI_Headerdata Object zum Aendern der Html Kopfdaten.
     *
     * @access public
     * @return object GUI_Headerdata
     **/
    function &getHeaderdata()
    {
        return $this->Headerdata;
    }

    /**
     * Liefert das GUI_DynToolTip Objekt fuer ToolTip Texte.
     *
     * @access public
     * @return object GUI_DynToolTip
     **/
    function &getDynToolTip()
    {
        return $this->DynToolTip;
    }

    /**
     * Fuegt dem (Document) Body Load-Ereignis eine (JavaScript-)Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion (bitte ohne ; abschließen)
     **/
    function addBodyLoad($func)
    {
        if (!in_array($func, $this -> DoLoad)) {
            $this -> DoLoad[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Unload-Ereignis eine (JavaScript-) Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyUnload($func)
    {
        if (!in_array($func, $this -> DoUnload)) {
            $this -> DoUnload[] = $func;
        }
    }

    /**
     * GUI_CustomFrame::addBodyMouseover()
     *
     * Fuegt dem (Document) Body Mouseover-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyMouseover($func)
    {
        if (!in_array($func, $this -> DoMouseover)) {
            $this -> DoMouseover[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Mousemove-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyMousemove($func)
    {
        if (!in_array($func, $this -> DoMousemove)) {
            $this -> DoMousemove[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Mouseout-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyMouseout($func)
    {
        if (!in_array($func, $this -> DoMouseout)) {
            $this -> DoMouseout[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Mousedown-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyMousedown($func)
    {
        if (!in_array($func, $this -> DoMousedown)) {
            $this -> DoMousedown[] = $func;
        }
    }

    /**
     * GUI_CustomFrame::addBodyMouseup()
     *
     * Fuegt dem (Document) Body Mouseup-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyMouseup($func)
    {
        if (!in_array($func, $this -> DoMouseup)) {
            $this -> DoMouseup[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Keydown-Ereignis eine JavaScript-Funktion hinzu.
     *
     * @access public
     * @param string $func Funktion
     **/
    function addBodyKeydown($func)
    {
        if (!in_array($func, $this->DoKeydown)) {
            $this->DoKeydown[] = $func;
        }
    }

    /**
     * Fuegt dem (Document) Body Keypress-Ereignis eine JavaScript-Funktion hinzu
     *
     * @param string $func Funktion
     */
    function addBodyKeypress($func)
    {
        if(!in_array($func, $this->DoKeypress)) {
            $this->DoKeypress[] = $func;
        }
    }

    /**
     * Laden der Default Einstellungen.
     *
     * @access public;
     **/
    function init($superglobals=0)
    {
        parent::init($superglobals);
    }

    /**
     * Fuegt die Html Kopfdaten zur Seite hinzu.
     *
     * @param string $content
     * @return string Inhalt (Content)
     **/
    function finalize($content='')
    {
        $tooltip_name = '';
        if(!$this->preventDefaultDynToolTip) {
            $this->DynToolTip->prepare();
            $content_tooltip = $this->DynToolTip->finalize();
            $tooltip_name = $this->DynToolTip->getName();
        }

        $header_name = '';
        if(!$this->preventDefaultHeaderdata) {
            $this->Headerdata->prepare();
            $content_header = $this->Headerdata->finalize();
            $header_name = $this->Headerdata->getName();
        }

        $doload = (count($this -> DoLoad) > 0 and is_array($this -> DoLoad)) ? implode(';', $this -> DoLoad) : '';
        $dounload = (count($this -> DoUnload) > 0 and is_array($this -> DoUnload)) ? implode(';', $this -> DoUnload) : '';
        $domouseover = (count($this -> DoMouseover) > 0 and is_array($this -> DoMouseover)) ? implode(';', $this -> DoMouseover) : '';
        $domousemove = (count($this -> DoMousemove) > 0 and is_array($this -> DoMousemove)) ? implode(';', $this -> DoMousemove) : '';
        $domouseout = (count($this -> DoMouseout) > 0 and is_array($this -> DoMouseout)) ? implode(';', $this -> DoMouseout) : '';
        $domousedown = (count($this -> DoMousedown) > 0 and is_array($this -> DoMousedown)) ? implode(';', $this -> DoMousedown) : '';
        $domouseup = (count($this -> DoMouseup) > 0 and is_array($this -> DoMouseup)) ? implode(';', $this -> DoMouseup) : '';
        $dokeydown = (count($this->DoKeydown) > 0) ? implode(';', $this -> DoKeydown) : '';
        $dokeypress = (count($this->DoKeypress) > 0) ? implode(';', $this->DoKeypress) : '';

        $content = str_replace('{DOLOAD}', $doload, $content);
        $content = str_replace('{DOUNLOAD}', $dounload, $content);
        $content = str_replace('{DOMOUSEOVER}', $domouseover, $content);
        $content = str_replace('{DOMOUSEMOVE}', $domousemove, $content);
        $content = str_replace('{DOMOUSEOUT}', $domouseout, $content);
        $content = str_replace('{DOMOUSEDOWN}', $domousedown, $content);
        $content = str_replace('{DOMOUSEUP}', $domouseup, $content);
        $content = str_replace('{DOKEYDOWN}', $dokeydown, $content);
        $content = str_replace('{DOKEYPRESS}', $dokeypress, $content);


        if (version_compare(phpversion(), '5.0.0', '>=')) {
            if($header_name) $content = str_ireplace(array('<'.$header_name.'>'.'</'.$header_name.'>','<'.$header_name.'>', '<'.$header_name.'/>'), $content_header, $content);
            if($tooltip_name) $content = str_ireplace(array('<'.$tooltip_name.'>'.'</'.$tooltip_name.'>','<'.$tooltip_name.'>', '<'.$tooltip_name.'/>'), $content_tooltip, $content);
        }
        else {
            if($header_name) $content = str_replace(array('<'.$header_name.'>'.'</'.$header_name.'>','<'.$header_name.'>', '<'.$header_name.'/>'), $content_header, $content);
            if($tooltip_name) $content = str_replace(array('<'.$tooltip_name.'>'.'</'.$tooltip_name.'>','<'.$tooltip_name.'>', '<'.$tooltip_name.'/>'), $content_tooltip, $content);
        }

        return $content;
    }
}