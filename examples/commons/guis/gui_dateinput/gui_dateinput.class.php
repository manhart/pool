<?php
/**
 * Class GUI_DateInput
 *
 * @author Christian Schmidseder <c.schmidseder@gmx.de>
 * @access public
 **/
class GUI_DateInput extends GUI_Module
{
   
    /**
     * Class GUI_DateInput
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

        parent::init(I_REQUEST);


    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this->Weblication->findTemplate('tpl_dateinput.html', $this->getClassName());
        $this->Template->setFilePath('stdout', $template);

        if(is_a($this->Weblication->getMain(), 'GUI_CustomFrame')) {
            $Frame = &$this->Weblication->getMain();

            $className=$this->getClassName();
            $js = $this->Weblication->findJavaScript('dateinput.js', strtolower($className), false);         
            $Frame->Headerdata->addJavaScript($js);
        }
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        if($this->Weblication->hasFrame()) {
            $Frame = &$this->Weblication->getMain(); /* @var $Frame GUI_CustomFrame */
            $this->prepareDefaults($Frame);
        }
    }




    /**
     * Standard Variablen für das Template vorbereiten
     *
     * @param $Frame GUI_CustomFrame
     */
    function prepareDefaults(&$Frame)
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