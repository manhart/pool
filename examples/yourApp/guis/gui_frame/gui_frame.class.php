<?php
    /**
     * 
     * @author Christian Schmidseder <c.schmidseder@gmx.de>
     *
     */
    class GUI_Frame extends GUI_CustomFrame
    {
        function __construct(&$Owner)
        {
            $this->preventDefaultJavaScript = true;
            $this->preventDefaultDynToolTip = true;
            parent::__construct($Owner);
        }

        /**
         * init input
         *
         * @param int $superglobals
         */
        function init($superglobals = 0)
        {
            parent::init(I_GET|I_POST);
        }

        /**
         * load files
         */
        function loadFiles()
        {
            $tpl = $this->Weblication->findTemplate('tpl_frame.html', $this->getClassName(), false);
            $this->Template->setFilePath('frame', $tpl);

            // js from 3rdParty
            $jqueryVersion = '3.3.1';
            $jqueryUIVersion = '1.12.1';

            $jqueryPath = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT).'js/jquery/'.$jqueryVersion;
            // $jqueryUIPath = addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT).'js/jquery-ui/'.$jqueryUIVersion;

            //
            // jQuery
            $jsfile = addEndingSlash($jqueryPath).'jquery-'.$jqueryVersion.'.min.js';
            $this->Headerdata->addJavaScript($jsfile);

            // jQuery-UI
            // $jsfile = addEndingSlash($jqueryUIPath).'jquery-ui-'.$jqueryUIVersion.'.custom.min.js';
            // $this->Headerdata->addJavaScript($jsfile);

            // js from pool (needs jquery)
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('jquery.ajax.js', '', true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('helpers.js', '', true));
            $this->Headerdata->addJavaScript($this->Weblication->findJavaScript('url.js', '', true));

            // resources from myApp (always at the end!)
            $myappJS = $this->Weblication->findJavaScript('yourapp.js', '', false);
            $this->Headerdata->addJavaScript($myappJS);

            $myappCSS = $this->Weblication->findStyleSheet('yourapp.css', '', false);
            $this->Headerdata->addStyleSheet($myappCSS);

        }

        /**
         * prepare template & other
         */
        function prepare()
        {
            $lang = $this->Weblication->getLanguage();
            $this->Template->setVar('lang', $lang);

            parent::prepare();
        }

        /**
         * print errors e.g. via Javascript
         */
        function showErrorList($classname)
        {
            $Template = &$this->Template;

            if(count($this->errorList)) {
                foreach($this->errorList as $type => $messages) {
                    if(count($messages)) {
                        $Template->newBlock('showMessageBox');
                        foreach ($messages as $message) {
                            $errorMsg = nl2br($message).'<br>';
                        }
                        $Template->setVar('errorList', utf8_encode(addslashes(($errorMsg))));
                        $Template->setVar('type', $type);
                    }
                }
                $Template->leaveBlock();

                $this->Session->delVar($this->Weblication->Project.'.'.$classname.'.errorList');
            }
        }

        /**
         * add an error
         *
         * @param string $message
         */
        function addError($message, $type='error')
        {
            if(!is_array($this->errorList[$type])) $this->errorList[$type] = array();
            array_push($this->errorList[$type], $message);
        }

        /**
         * handle errors
         *
         * @param Resultset $Resultset
         * @return boolean ob Fehler aufgetreten sind
         */
        function handleResultsetErrors($Resultset, $type='error')
        {
            $lastError = $Resultset->getLastError();
            if($lastError) {
                $error='';
                $errorList = $Resultset->getErrorList();
                foreach($errorList as $error) {
                    $this->addError($error['message']);
                }
                return true;
            }
            return false;
        }

        /**
         * save errors
         */
        function memorizeErrorList($classname)
        {
            $Session = &$this->Session;
            $Session->setVar($this->Weblication->Project.'.'.$classname.'.errorList', ($this->errorList));
        }

        /**
         * read errors
         */
        function readoutErrorList($classname)
        {
            $Session = &$this->Session;
            if($Session->exists($this->Weblication->Project.'.'.$classname.'.errorList')) {
                $this->errorList = ($Session->getVar($this->Weblication->Project.'.'.$classname.'.errorList'));
            }
        }

        /**
         * parse template of this gui
         *
         * @param string $content
         * @return string
         */
        function finalize($content = '')
        {
            $this->Template->parse('frame');
            return parent::finalize($this->Template->getContent('frame'));
        }
    }