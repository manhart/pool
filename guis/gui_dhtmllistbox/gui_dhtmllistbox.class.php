<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * gui_dhtmllistbox.class.php
 *
 * Das GUI Edit steuert ein Eingabefeld.
 *
 * @version $Id: gui_dhtmllistbox.class.php,v 1.4 2007/01/09 10:21:06 manhart Exp $
 * @version $revision 1.0$
 * @version
 *
 * @since 2004/07/07
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 */

/**
 * GUI_DHTMLListbox
 *
 * Edit steuert ein Eingabefeld (<input type=text>).
 *
 * @package pool
 * @author manhart
 * @version $Id: gui_dhtmllistbox.class.php,v 1.4 2007/01/09 10:21:06 manhart Exp $
 * @access public
 **/
class GUI_DHTMLListbox extends GUI_Module
{
    /**
     *
     * Initialisiert Standardwerte:
     *
     * TODO Parameter
     *
     * Ueberschreiben moeglich durch Variablen von INPUT_GET und INPUT_POST.
     *
     * @access public
     **/
    function init(?int $superglobals=I_GET|I_POST)
    {
        $this -> Defaults -> addVar(
            array(
                'id' 				=> $this -> getName(),
                'name'				=> $this -> getName(),

                'caption-align'		=> 'left',
                'caption-width'		=> '',
                'caption'			=> 'Caption',
                'height'			=> 100,
                'width'				=> 200,
                'list'				=> '',
                'content'			=> '',
                'plus_image' 		=> 'images/plus.gif',
                'minus_image'		=> 'images/minus.gif',
                'plus_title'		=> 'plus',
                'minus_title'		=> 'minus',
                'plus_action'		=> 'void(0);',
                'minus_action'		=> 'void(0);',
                'enableMinusButton' => 0,
                'enablePlusButton' => 1
            )
        );

        parent :: init($superglobals);
    }

    function loadFiles()
    {
        $file = $this -> Weblication -> findTemplate('tpl_dhtmllistbox.html', $this -> getClassName(), true);
        $file_rows = $this -> Weblication -> findTemplate('tpl_dhtmllistbox_rows.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('stdout', $file);
        $this -> Template -> setFilePath('stdout_rows', $file_rows);
    }

    function prepare ()
    {
        #### Bindet gui_dhtmllistbox.css ein:
        $cssfile = @$this -> Weblication -> findStyleSheet($this -> getClassName() . '.css', $this -> getClassName(), true);
        if (is_a($this->Weblication->getFrame(), 'GUI_Module')) {
            if (is_a($this->Weblication->getFrame()->getHeadData(), 'GUI_HeadData')) {
                if ($cssfile) {
                    $this->Weblication->getFrame()->getHeadData()->addStyleSheet($cssfile);
                }
            }
        }

        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        $id = $Input -> getVar('id');
        $name = $Input -> getVar('name');
        $list = $Input -> getVar('list');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> getName() and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> getName() and $name == $this -> getName()) {
            $name = $id;
        }

        $Template -> setVar(
            array(
                'ID' 				=> $id,
                'NAME' 				=> $name,
                'HEIGHT'			=> $Input -> getVar('height'),
                'WIDTH'				=> $Input -> getVar('width')
            )
        );

        if($Input -> getVar('enablePlusButton') == 1) {
            $Template -> newBlock('plus_button');
            $Template -> setVar(
                array(
                    'PLUS_IMAGE'		=> $Input -> getVar('plus_image'),
                    'PLUS_TITLE'		=> $Input -> getVar('plus_title'),
                    'PLUS_ACTION'		=> $Input -> getVar('plus_action')
                )
            );
            $Template -> leaveBlock();

        }
        if($Input -> getVar('enableMinusButton') == 1) {
            $Template -> newBlock('minus_button');
            $Template -> setVar(
                array(
                    'MINUS_IMAGE'		=> $Input -> getVar('minus_image'),
                    'MINUS_TITLE'		=> $Input -> getVar('minus_title'),
                    'MINUS_ACTION'		=> $Input -> getVar('minus_action')
                )
            );
            $Template -> leaveBlock();
        }

        #### Caption (Text and Alignment)
        $caption_width = (int)$Input->getVar('caption-width');
        $caption_align = $Input->getVar('caption-align');
        $caption = $Input->getVar('caption');
        if($caption_width != 0) {
            $Template->newBlock('caption_align_'.$caption_align);
            $Template->setVar(
                array(
                    'CAPTION' => $caption,
                    'CAPTIONWIDTH' => $caption_width
                )
            );
            $Template -> leaveBlock();
        }

        #### Scrollbox
        $GUI_Scrollbox = new GUI_Scrollbox($this->getOwner());
        $GUI_Scrollbox->Input->setVar(
            array(
                'boxheight' => ($caption_align == 'top') ? ($Input -> getVar('height') - 20) : $Input -> getVar('height'), // Hoehe der ersten Zeile
                'boxwidth' => ($Input -> getVar('width') - 20), // Breite der Bilder fuer Plus / Minus
                'gapheight' => 2, // border 1px * 2 (left-right)
                'gapwidth' => 14 // bild 12px + border 1px * 2 (top-bottom)
            )
        );
        $GUI_Scrollbox -> prepare();
        // $Template -> useFile('stdout_rows');
        $content = $Input -> getVar('content');
        $scrollbox_content = $GUI_Scrollbox->finalize($content);
        $Template -> setVar('SCROLLBOX', $scrollbox_content);
    }

    function finalize(): string
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}