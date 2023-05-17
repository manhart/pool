<?php

class GUI_AlertBox extends GUI_Module
{
    /**
     * Class GUI_AlertBox
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param int $superglobals
     **/
    function init($superglobals=0)
    {
        $this->Defaults->addVar('buttonLabel', 'OK');
        parent::init(Input::INPUT_GET|Input::INPUT_POST);
    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this->Weblication->findTemplate('tpl_alertbox.html', $this->getClassName());
        $this->Template->setFilePath('stdout', $template);

        if(is_a($this->Weblication->getMain(), 'GUI_CustomFrame')) {
            $Frame = &$this->Weblication->getMain();

            $className=$this->getClassName();

            $js = $this->Weblication->findJavaScript('alertbox.js', strtolower($className), false);
            $Frame->Headerdata->addJavaScript($js);

            $jqueryVersion = '3.3.1';
            //             $jqueryUIVersion = '1.12.1';

            $jqueryPath = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT).'js/jquery/'.$jqueryVersion;
            // $jqueryUIPath = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT).'js/jquery-ui/'.$jqueryUIVersion;
            $jsfile = addEndingSlash($jqueryPath).'jquery-'.$jqueryVersion.'.min.js';
            $Frame->Headerdata->addJavaScript($jsfile);

            // jQuery-UI
            // $jsfile = addEndingSlash($jqueryUIPath).'jquery-ui-'.$jqueryUIVersion.'.custom.min.js';
            // $this->Headerdata->addJavaScript($jsfile);

            // Popper -------------------------------------------------------------------------------
            $jsfile = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT) . 'js/popper/popper.min.js';
            $Frame->Headerdata->addJavaScript($jsfile);


            // Bootstrap ----------------------------------------------------------------------------
            $bootstrapVersion = '4.3.1';
            $bootstrapPath = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT) . 'js/bootstrap/' . $bootstrapVersion;

            $jsfile = addEndingSlash($bootstrapPath) . 'js/bootstrap.js';
            $Frame->Headerdata->addJavaScript($jsfile);

            // Bootstrap CSS
            $cssfile = addEndingSlash($bootstrapPath) . 'css/bootstrap.css';
            $Frame->Headerdata->addStyleSheet($cssfile);
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
        $this->Template->setVar('name', $this->getName());
        $this->Template->setVar('buttonLabel', $this->Input->getVar('buttonLabel'));
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