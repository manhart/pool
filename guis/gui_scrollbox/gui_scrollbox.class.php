<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_scrollbox.class.php
 *
 * GUI_Scrollbox ist eine einfach Scrollbox, die man mit Inhalt fuellt. Ist der Inhalt hoeher als die Box, kann man darin automatisch scrollen.
 *
 * @version $Id: gui_scrollbox.class.php,v 1.2 2004/09/23 07:49:34 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Scrollbox
 *
 * Klasse zum Erstellen von D-Html Scrollboxen.
 *
 * @package pool
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_scrollbox.class.php,v 1.2 2004/09/23 07:49:34 manhart Exp $
 * @access public
 **/
class GUI_Scrollbox extends GUI_Module
{
    //@var object Template
    //@access private
    var $TplSbox = null;

    /**
     * GUI_Scrollbox::GUI_Scrollbox()
     *
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     * @param bool $autoLoadFiles
     * @param array $params
     */
    function __construct($Owner, $autoLoadFiles = true, array $params = [])
    {
        $this->TplSbox = new Template();

        parent::__construct($Owner, $autoLoadFiles, $params);

        $file = $this->Weblication->findTemplate($this->Input->getVar('fileTemplateHTML'),
            'gui_scrollbox', true);
        $this->TplSbox->setFilePath('scrollbox', $file);
    }

    /**
     * GUI_Scrollbox::init()
     *
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param integer|null $superglobals Superglobals (siehe Klasse Input)
     **/
    function init(?int $superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar(
            array(
                'boxwidth'			=> 190,
                'boxheight'			=> 160,
                'gapheight'			=> 37, // Summe Kopfzeilenhoehe + Fusszeilenhoehe und Abstaende; daraus errechnet sich CONTENTHEIGHT der div's
                'fileTemplateHTML'	=> 'tpl_scrollbox.html'
            )
        );

        parent :: init($superglobals);
    }

    /**
     * GUI_Scrollbox::prepare()
     *
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $Weblication = &$this -> getWeblication();
        $Frame = &$Weblication -> getFrame();

        if (is_a($Frame, 'GUI_CustomFrame')) {
            $this -> TplSbox -> setVar('NAME', $this -> Name);
            $Frame -> addBodyLoad('InitScrollbox(\''.$this -> Name.'\')');
            $Frame -> addBodyMousemove('Scrollbox_MoveController(event)');
            $Frame -> addBodyMouseup('Scrollbox_DropController(event)');
            $jsFile = $Weblication -> findJavaScript('scrollbox.js', $this -> getClassName(), true);
            $Headerdata = &$Frame -> getHeaderdata();
            $Headerdata -> addJavaScript($jsFile);
        }

        $gapheight = $this -> Input -> getVar('gapheight');
        $boxwidth = $this -> Input -> getVar('boxwidth');
        $boxheight = $this -> Input -> getVar('boxheight');

        $contentheight = ($boxheight - $gapheight);

        $this -> TplSbox -> setVar(
            array(
                'BOXWIDTH' => $boxwidth,
                'BOXHEIGHT' => $boxheight,
                'CONTENTHEIGHT' => $contentheight
            )
        );
    }

    /**
     * GUI_Scrollbox::setTitle()
     *
     * Setzt einen Titel fuer die Scrollbox.
     *
     * @access public
     * @param string $title Titel
     **/
    function setTitle($title)
    {
        $this -> TplSbox -> setVar('TITLE', $title);
    }

    /**
     * GUI_Scrollbox::finalize()
     *
     * Scrollbox Inhalt parsen und zurueck geben.
     *
     * @return string Content
     **/
    function finalize($content=''): string
    {
        $this -> TplSbox -> setVar('CONTENT', $content);

        $this -> TplSbox -> parse('scrollbox');
        return $this -> TplSbox -> getContent('scrollbox');
    }
}