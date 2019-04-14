<?php
/**
 * Class GUI_Test
 *
 * @package test
 * @author Christian Schmidseder <c.schmidseder@gmx.de>
 * @access public
 **/
class GUI_Test extends GUI_Module
{
   

    /**
     * Class GUI_Test
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
        $Frame = $this->Weblication->getMain(); /* @var $Frame GUI_Frame */
        if($Frame  instanceof GUI_CustomFrame) {
            $Frame->readoutErrorList($this->getClassName());
        }
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param int $superglobals
     **/
    function init($superglobals=0)
    {
        parent::init(I_GET|I_POST);

    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this->Weblication->findTemplate('tpl_test.html', $this->getClassName());
        $this->Template->setFilePath('stdout', $template);
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
       
    }

    /**
     * Inhalt parsen und zurueck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}