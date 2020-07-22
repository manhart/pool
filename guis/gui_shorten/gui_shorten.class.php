<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_shorten.class.php
 *
 * Das GUI Shorten kuerzt anzuzeigende Texte und zeigt den Text auf Wunsch in voller Laenge als Tooltip an.
 *
 * Tooltip einbinden:
 * [GUI_DHtmlHint] in Template einbinden.
 * Stylesheet definieren zum Beispiel:
 * 	#DHtmlHint {
 *		position: absolute;
 *		width: 150px;
 *		border: 1px solid black;
 *		padding: 0px;
 *		background-color: lightyellow;
 *		visibility: hidden;
 *		z-index: 100;
 *		font-size: 11px;
 *		font-family: helvetica;
 *		Remove below line to remove shadow. Below line should always appear last within this CSS
 *		filter: progid:DXImageTransform.Microsoft.Shadow(color=gray,direction=135);
 *  }
 *
 * Darauf achten, dass im Body Tag die Maus-Events: onMousemove="MousePosition.detect(event);DHtmlHintObject.doMouseMove(event)" implementiert werden.
 *
 *
 * @version $Id: gui_shorten.class.php,v 1.7 2005/07/25 08:45:21 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/06
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */


/**
 * GUI_Shorten
 *
 * Kuerzt Text und zeigt dafuer ein Tooltip an.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_shorten.class.php,v 1.7 2005/07/25 08:45:21 manhart Exp $
 * @access public
 **/
class GUI_Shorten extends GUI_Module
{
    /**
     * Gek�rzter Text
     *
     * @access private
     * @var string
     */
    var $shortenText = '';

    /**
     * Flag, ob der Text gek�rzt wurde.
     *
     * @var bool
     */
    var $modified = false;

    /**
     * Standardwerte initialisieren:
     *
     * - text = Text
     * - len = Laenge auf die der Text gek�rzt werden soll
     * - more = 1 haengt drei Puenktchenbei am gekuerzten Text dran, oder man �bergibt selbst ein Erkennungsmerkmal.
     * - hint = 1 schaltet ToolTip Hint ein (zeigt vollstaendigen Text an)
     * - url = Url im ToolTip Hint
     * - htmlTag = HTML Tag, dass OnMouseOver f�r ToolTip Hint enth�lt. Standard "p" f�r <p>
     * - htmlTagAttr = HTML Tag Attribute als Array z.B. array('class' => 'fontcss');
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'text'			=> '',
                'len'			=> 150,
                'more'			=> 1,
                'hint'			=> 1,
                'url'			=> '',
                'htmlTag'		=> 'p',
                'htmlTagAttr'	=> null,
                'backtrack'		=> true
            )
        );

        parent::init($superglobals);
    }

    function loadFiles()
    {
    }

    /**
     * Kuerzt den Text
     *
     * @access public
     **/
    function prepare ()
    {
        $Input = &$this -> Input;
        $text = shorten($Input -> getVar('text'), $Input -> getVar('len'), $Input -> getVar('more'), $Input -> getVar('backtrack'));
        $this -> modified = strcmp($text, $Input -> getVar('text')) != 0;
        $this -> shortenText = $text;
    }

    /**
     * Gibt den gek�rzten Text zur�ck
     *
     * @access public
     * @return string Splitter
     **/
    function finalize()
    {
        // onmouseover="DHtmlHintObject.showAtObject(this, '{filename}', '', '', 0, 0);

        $Input = &$this -> Input;

        $url = $Input -> getVar('url');
        $text = $Input -> getVar('text');
        $htmlTag = $Input -> getVar('htmlTag');
        $htmlTagAttr = $Input -> getVar('htmlTagAttr');
        $bHint = ($Input -> getVar('hint') == 1);
        $strHtmlTagAttr = arrayToAttr($htmlTagAttr);

        return '<' . $htmlTag . ' ' . $strHtmlTagAttr .(($this -> modified && $bHint) ?
            ' onMouseOver="DHtmlHintObject.showAtObject(this, \'' .
            addslashes(str_replace(array('"', "\r", "\n"), array('&#34;', '', ''), (($url) ? ('<a href="'.$url.'">') : '').nl2br($text))) .
            (($url) ? '</a>' : '') . '\', \'\', \'\', 0, 0);"' : '') .'>' . $this -> shortenText . '</' . $htmlTag . '>';
    }
}