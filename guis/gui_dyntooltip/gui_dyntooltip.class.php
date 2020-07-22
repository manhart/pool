<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * GUI_DynToolTip.class.php
 *
 * GUI_DynToolTip wie der Name schon sagt ein dynamischer ToolTip (Verwendung von D-HTML).
 * Das graphische Element wird ueber den HTML Tag <DynToolTip> in der HTML Vorlage des GUI_Frame's eingebunden (im Body Bereich).
 * Steht im Projekt kein GUI_Frame zur Verfuegung, kann das Modul DynToolTip auch manuell eingebunden werden.
 *
 * Um DynToolTip zum Laufen zu bekommen brauchst man als erstes einen Style (der Style kommt in eine HTML Vorlage, am Besten auch in den Frame):
 *
 * DynToolTipStyle[...]=[TitleColor,TextColor,TitleBgColor,TextBgColor,TitleBgImag,TextBgImag,TitleTextAlign,TextTextAlign, TitleFontFace,
 *	TextFontFace, TipPosition, StickyStyle, TitleFontSize, TextFontSize, Width, Height, BorderSize, PadTextArea,
 *	CoordinateX , CoordinateY, TransitionNumber, TransitionDuration, TransparencyLevel ,ShadowType, ShadowColor]
 *
 *	DynToolTipStyle[0]=["white", "black", "#386A9D", "#FFFFDD", "", "", "", "", "", "", "", "", "", "", 200, "", 2, 2, 10, 10, 12, 1, 0, "", ""];
 *
 *
 * @version $Id: gui_dyntooltip.class.php,v 1.8 2007/03/01 12:57:48 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @package GUI_DynToolTip
 * @since 2003-09-26
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DynToolTip
 *
 * GUI_DynToolTip wie der Name schon sagt ein dynamischer ToolTip.
 * Das graphische Element wird ueber den HTML Tag <DynToolTip> in der HTML Vorlage des GUI_Frame's eingebunden (im Body Bereich).
 * Steht im Projekt kein GUI_Frame zur Verfuegung, kann das Modul DynToolTip auch manuell eingebunden werden.
 *
 * Um DynToolTip zum Laufen zu bekommen brauchst man als erstes einen Style (der Style kommt in eine HTML Vorlage, am Besten auch in den Frame):
 *
 * DynToolTipStyle[...]=[TitleColor,TextColor,TitleBgColor,TextBgColor,TitleBgImag,TextBgImag,TitleTextAlign,TextTextAlign, TitleFontFace,
 *	TextFontFace, TipPosition, StickyStyle, TitleFontSize, TextFontSize, Width, Height, BorderSize, PadTextArea,
 *	CoordinateX , CoordinateY, TransitionNumber, TransitionDuration, TransparencyLevel ,ShadowType, ShadowColor]
 *
 *	DynToolTipStyle[0]=["white", "black", "#386A9D", "#FFFFDD", "", "", "", "", "", "", "", "", "", "", 200, "", 2, 2, 10, 10, 12, 1, 0, "", ""];
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_dyntooltip.class.php,v 1.8 2007/03/01 12:57:48 manhart Exp $
 * @access public
 **/
class GUI_DynToolTip extends GUI_Module
{
    //@var array Enthaelt ToolTip Titel und Text
    //@access private
    var $text = Array();

    //@var integer Index fuer das Text Array
    //@access private
    var $textIndex = -1;

    //@var array Enthaelt ToolTip Styles
    //@access private
    var $style = Array();

    //@var array Index fuer das Style Array
    //@access private
    var $styleIndex = -1;

    /**
     * Initialisierung der Standard Werte und Superglobals.
     *
     * Standard:
     * enableFilter = 1
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar('enableFilter', 1);

        parent::init($superglobals=I_EMPTY);
    }

    /**
     * Laedt die HTML Vorlage "tpl_dyntooltip.html".
     *
     * @access public
     * @param array $schemes
     **/
    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_dyntooltip.html', 'gui_dyntooltip', true);
        $this -> Template -> setFilePath('stdout', $file);
    }

    /**
     * GUI_DynToolTip::enableFilter()
     *
     * Aktiviert den DX Microsoft Filter beim DynToolTip.
     *
     * @access public
     **/
    function enableFilter()
    {
        $this -> Input -> setVar('enableFilter', 1);
    }

    /**
     * Deaktiviert den DX Microsoft Filter beim DynToolTip.
     *
     * @access public
     **/
    function disableFilter()
    {
        $this -> Input -> setVar('enableFilter', 0);
    }

    /**
     * Erzeugt einen neuen ToolTip Text und gibt die dazugehoerige ID zurueck.
     *
     * @param string $title Titel
     * @param string $text Text
     * @return integer ID
     **/
    function addText($title, $text)
    {
        $this -> textIndex++;
        $textArray = array('index' => $this -> textIndex, 'title' => addslashes($title),
            'text' => str_replace(array("\r", "\n"), "", nl2br(addslashes($text))));
        array_push($this -> text, $textArray);
        return $this -> textIndex;
    }

    /**
     * Setzt einen eindeutigen Merker (Index), Titel und Text.
     *
     * @access public
     **/
    function prepare()
    {
        $jsfile = $this -> Weblication -> findJavaScript('dyntooltip.js', $this -> getClassName(), true);
        if (is_a($this -> Weblication -> Main, 'GUI_Module')) {
            if (is_a($this -> Weblication -> Main -> Headerdata, 'GUI_Headerdata')) {
                $this -> Weblication -> Main -> Headerdata -> addJavaScript($jsfile);
            }
        }

        $sizeofText = SizeOf($this -> text);
        for ($i=0; $i < $sizeofText; $i++) {
            $this -> Template -> newBlock('DYNTOOLTIPTEXT');
            $this -> Template -> setVar(
                array(
                    'INDEX' => $this -> text[$i]['index'],
                    'TITLE' => $this -> text[$i]['title'],
                    'TEXT' => $this -> text[$i]['text']
                )
            );
        }

        $this -> Template -> leaveBlock();
        $this -> Template -> setVar('FILTERENABLED', $this -> Input -> getVar('enableFilter'));
    }

    /**
     * GUI_DynToolTip::finalize()
     *
     * Daten werden geparst und weiter gereicht.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}