<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_edit.class.php
 *
 * Die Klasse GUI_Edit erzeugt ein HTML Eingabefeld (<input type="text">).
 * Siehe fuer Uebergabeparameter auch in die abgeleitete Klasse GUI_FormElement!!
 *
 * @version $Id: gui_edit.class.php,v 1.3 2007/05/16 15:17:34 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 *
 * @see GUI_FormElement
 */

/**
 * GUI_Edit
 *
 * Siehe Datei fuer ausfuehrliche Beschreibung!
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_edit.class.php,v 1.3 2007/05/16 15:17:34 manhart Exp $
 * @access public
 * @see GUI_FormElement
 **/
class GUI_Edit extends GUI_InputElement
{
    /**
     * Initialisiert Standardwerte:
     *
     * Ueberschreiben moeglich ueber GET und POST.
     *
     * Parameter:
     * - type Typ ist fest "text" (bitte diesen Parameter unberuehrt belassen!)
     * - size Bestimmt die Anzeigebreite des Elements (Standard: 20)
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar(
            array(
                'type'			=> 'text',
                'size'			=> 20,
            )
        );

        parent::init(I_GET|I_POST);
    }

    /**
     * Laedt Template "tpl_edit.html". Ist im projekteigenen Skinordner ueberschreibbar!
     *
     * @access public
     **/
    function loadFiles()
    {
        $file = $this->Weblication->findTemplate('tpl_edit.html', 'gui_edit', true);
        $this->Template->setFilePath('stdout', $file);
    }

    /**
     * Verarbeitet Template (Platzhalter, Bloecke, etc.) und generiert HTML Output.
     *
     * @access public
     * @return string HTML Output (Content)
     **/
    function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}