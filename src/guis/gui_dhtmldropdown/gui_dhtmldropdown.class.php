<?php
/**
 * -= PHP Object Oriented Library =-
 *
 * gui_dhtmldropdown.class.php
 *
 *
 * @version $Id: gui_dhtmldropdown.class.php,v 1.2 2005/04/15 13:36:29 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2004/08/03
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * GUI_DHTMLDropdown
 *
 * @package pool
 * @author Alexander Manhart <misterelsa@gmx.de>
 * @version $Id: gui_dhtmldropdown.class.php,v 1.2 2005/04/15 13:36:29 manhart Exp $
 * @access public
 **/
class GUI_DHTMLDropdown extends GUI_Module
{
    public function __construct(&$Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     **/
    function init($superglobals=I_EMPTY)
    {
        $this->Defaults->addVar(
            array(
                'name' => $this->getClassName(),
                'list' => '',
                'listSeparator' => ';',
                'image' => '',
                'width' => 200,
                'height' => 21,
                'class' => '',
                'autoheight' => true,
                'defaultvalue' => '',
                'onkeyup' => '',
                'onclicklist' => '',
                'listheight' => 0,
                'value' => ''
            )
        );
        parent :: init(I_GET|I_POST);
    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this -> Weblication -> findTemplate('tpl_dhtmldropdown.html', 'gui_dhtmldropdown', true);
        $this -> Template -> setFilePath('stdout', $template);

    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $this -> loadFiles();

        $interfaces = & $this -> Weblication -> getInterfaces();
        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;
        $Frame = & $this -> Weblication -> Main;

        $listheight = $Input->getVar('listheight');
        if($listheight>0) $overflow = 'auto';

        $imagewidth = 0;
        $file_image = $this -> Weblication -> findImage(basename($Input -> getVar('image')));
        if ($file_image != '') {
            $imagesize = getimagesize($file_image);
            $imagewidth = $imagesize[0]; // siehe PHP Manual
            $imageheight = $imagesize[1];
        }
        $Template -> setVar(
            array(
                'NAME' => $Input -> getVar('name'),
                'IMAGE' => $Input -> getVar('image'),
                'WIDTH' => ((int)$Input -> getVar('width')) - $imagewidth + 4,
                'EDWIDTH' => ((int)$Input -> getVar('width') - $imagewidth),
                'EDHEIGHT' => ($Input -> getVar('autoheight') ? $imageheight : $Input -> getVar('height')),
                'DEFAULTVALUE' => $Input -> getVar('defaultvalue'),
                'TABINDEX' => $Input->getVar('tabindex'),
                'ONKEYUP' => $Input->getVar('onkeyup'),
                'LISTHEIGHT' => $listheight,
                'OVERFLOW' => $overflow,
                'VALUE' => $this->Input->getVar('value')
            )
        );

        $list = $Input -> getVar('list');
        if (!is_array($list) and strlen($list) > 0) {
            $list = explode($Input -> getVar('listSeparator'), $list);
        }

        if (is_array($list) and count($list)) {
            foreach($list as $listElement) {
                $Template->newBlock('listElement');
                $Template->ActiveBlock->setVar(
                    array(
                        'VALUE' => $listElement,
                        'NAME' => $Input->getVar('name'),
                        'CLASS' => $Input->getVar('class'),
                        'DOCLICKLIST' => $Input->getVar('onclicklist')
                    )
                );
            }
        }
        else {
            $Template -> newBlock('listElement_empty');
            $Template -> setVar('CLASS', $Input -> getVar('class'));
        }
        $Template -> leaveBlock();

        #### Funktionen fuer D-Html Dropdown einbinden
        if (is_a($Frame, 'GUI_CustomFrame')) {
            $jsfile = $this -> Weblication -> findJavaScript('dropdown.js', $this -> getClassName(), true);
            $Frame->Headerdata->addJavaScript($jsfile);
            $Frame->addBodyMousemove('MousePosition.detect(event)');
            $Frame->addBodyMouseup('closeDropdownLayer()');
        }
    }

    /**
     * GUI_DHTMLDropdown::finalize()
     *
     * Inhalt parsen und zur�ck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $this -> Template -> parse('stdout');
        return $this -> reviveChildGUIs($this -> Template -> getContent('stdout'));
    }
}