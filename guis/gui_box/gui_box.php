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

use pool\classes\Core\Input\Input;

/**
 * GUI_Box
 *
 * Klasse zum Erstellen von graphischen Boxen (z.B. News-Boxen, Bl�cke, Container).
 *
 * @package rml
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @version $Id: gui_box.class.php,v 1.1.1.1 2004/09/21 07:49:31 manhart Exp $
 */
class GUI_Box extends GUI_Module
{
    /**
     * @var Template
     */
    private Template $TplBox;

    /**
     * @var bool
     */
    private bool $enabledBox;

    /**
     * @var bool
     */
    protected bool $autoLoadFiles = false;

    /**
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     *
     */
    function __construct($Owner, array $params = [])
    {
        $this->enabledBox = false;
        $this->TplBox = new Template();

        parent::__construct($Owner, $params);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @param int|null $superglobals Superglobals (siehe Klasse Input)
     **/
    public function init(?int $superglobals = Input::EMPTY)
    {
        parent::init($superglobals);
    }

    /**
     * Aktiviert die Box. Erwartet die HTML Vorlage mit der Box. Darin muss der Platzhalter {CONTENT} stehen.
     * Bei Bedarf kann noch {TITLE} gesetzt werden.
     *
     * @param string $title HTML Vorlage (nur Dateiname ohne Pfad; Standard "tpl_box.html")
     **/
    public function enableBox(string $title='tpl_box.html', string $template = ''): static
    {
        $file = $this->Weblication->findTemplate($title, $this->getClassName());
        if ($file) {
            $this->TplBox -> setFilePath('box', $file);
            $this->enabledBox = true;
        }
        else {
            $this->enabledBox = false;
        }
        return $this;
    }

    /**
     * Deaktiviert die Box.
     **/
    function disableBox(): static
    {
        $this->enabledBox = false;
        return $this;
    }

    /**
     * Setzt einen Titel fuer die Box.
     *
     * @param string $title Titel
     */
    public function setTitle(string $title)
    {
        $this->TplBox->setVar('TITLE', $title);
    }

    /**
     * Box Inhalt parsen und zurueck geben.
     *
     * @param $content string Text-Inhalt
     * @return string Content
     **/
    function finalize(string $content = ''): string
    {
        if ($this->enabledBox) {
            $this->TplBox->setVar('CONTENT', $content);

            $this->TplBox->parse('box');
            $content = $this->TplBox->getContent('box');
        }
        return $content;
    }
}