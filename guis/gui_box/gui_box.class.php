<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_box.class.php
 *
 * GUI_Box ist eine einfacher Container f�r graphische Elemente.
 *
 * @version $Id: gui_box.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_Box
 *
 * Klasse zum Erstellen von graphischen Boxen (z.B. News-Boxen, Bl�cke, Container).
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: gui_box.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 * @access public
 **/
class GUI_Box extends GUI_Module
{
    //@var object Template
    //@access private
    var $TplBox = null;

    //@var boolean Box aktiv (Standard false)
    //@access public
    var $enabledBox = false;

    /**
     * GUI_Box::GUI_Box()
     *
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner, $autoLoadFiles=true, array $params = [])
    {
        $this -> enabledBox = false;
        $this -> TplBox = & new Template();

        parent::__construct($Owner, $autoLoadFiles, $params);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param int|null $superglobals Superglobals (siehe Klasse Input)
     **/
    function init(?int $superglobals = I_EMPTY)
    {
        parent::init($superglobals);
    }

    /**
     * GUI_Box::prepare()
     *
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
    }

    /**
     * GUI_Box::enableBox()
     *
     * Aktiviert die Box. Erwartet die HTML Vorlage mit der Box. Darin muss der Platzhalter {CONTENT} stehen.
     * Bei Bedarf kann noch {TITLE} gesetzt werden.
     *
     * @param string $template HTML Vorlage (nur Dateiname ohne Pfad; Standard "tpl_box.html")
     * @access public
     **/
    function enableBox($template='tpl_box.html')
    {
        $file = $this->Weblication->findTemplate($template, $this->getClassName(), false);
        if ($file) {
            $this->TplBox -> setFilePath('box', $file);
            $this->enabledBox = true;
        }
        else {
            $this->enabledBox = false;
        }
    }

    /**
     * GUI_Box::disableBox()
     *
     * Deaktiviert die Box.
     *
     * @access public
     **/
    function disableBox()
    {
        $this->enabledBox = false;
    }

    /**
     * GUI_Box::setTitle()
     *
     * Setzt einen Titel fuer die Box.
     *
     * @access public
     * @param string $title Titel
     **/
    function setTitle($title)
    {
        $this->TplBox->setVar('TITLE', $title);
    }

    /**
     * GUI_Box::finalize()
     *
     * Box Inhalt parsen und zurueck geben.
     *
     * @param $content HTML/Texxt-Inhalt
     * @return string Content
     **/
    function finalize($content = ''): string
    {
        if ($this->enabledBox) {
            $this->TplBox->setVar('CONTENT', $content);

            $this->TplBox->parse('box');
            $content = $this->TplBox->getContent('box');
        }
        return $content;
    }
}