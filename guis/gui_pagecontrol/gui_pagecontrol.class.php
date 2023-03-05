<?php
/**
 * Class GUI_PageControl
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

class GUI_PageControl extends GUI_Module
{
    var $function_name = null;
    var $Url = null;

    function __construct(& $Owner, $autoLoadFiles = true, array $params = [])
    {
        parent::__construct($Owner, $params);

        $this->Url = new Url();
    }

    function init(?int $superglobals= Input::INPUT_EMPTY)
    {
        $this->Defaults->addVar('id', $this -> getName());
        $this->Defaults->addVar('name', $this -> getName());
        $this->Defaults->addVar('pageTitles', '');
        $this->Defaults->addVar('pageNames', 'Reiter 1;Reiter 2');
        $this->Defaults->addVar('pageGUIs', '');
        $this->Defaults->addVar('pageSchemes', '');
        $this->Defaults->addVar('activePageUrlParam', 'activePage');
        $this->Defaults->addVar('additionalUrlParams', '');
        $this->Defaults->addVar('startPage', '');
        $this->Defaults->addVar('template', 'tpl_pagecontrol.html');

        parent::init(Input::INPUT_REQUEST);
    }

    function loadFiles()
    {
        $file = $this->Weblication->findTemplate($this->Input->getVar('template'), $this->getClassName(), true);
        $this->Template->setFilePath('stdout', $file);
    }

    /**
     * GUI_PageControl::prepare()
     *
     * @return
     **/
    function prepare ()
    {
        $Template = & $this -> Template;
        $Session = & $this -> Session;
        $Input = & $this -> Input;

        $id = $Input -> getVar('id');
        $name = $Input -> getVar('name');

        // id mit name (sowie umgekehrt) abgleichen
        if ($name != $this -> getName() and $id == $this -> getName()) {
            $id = $name;
        }
        if ($id != $this -> getName() and $name == $this -> getName()) {
            $name = $id;
        }

        $activePageUrlParam = $Input -> getVar('activePageUrlParam');
        // startPage => activePage
        if ($Input -> getVar('startPage')) {
            if ($Input -> getVar($activePageUrlParam) == '') {
                $Input -> setVar($activePageUrlParam, $Input -> getVar('startPage'));
            }
        }

        $Url = & $this -> Url;

        $additionalUrlParams = $Input -> getVar('additionalUrlParams');
        if ($additionalUrlParams) {
            $params = explode(';', $additionalUrlParams);
            if (is_array($params)) {
                foreach($params as $param) {
                    list($paramname, $paramvalue) = explode(':', $param);
                    $Url -> setParam($paramname, $paramvalue);
                }
            }
        }

        $pagenames = explode(';', $Input -> getVar('pageNames'));
        $pagetitles = array();
        if ($Input -> getVar('pageTitles')) {
            $pagetitles = explode(';', $Input -> getVar('pageTitles'));
        }

        $pageguis = array();
        if ($Input -> getVar('pageGUIs')) {
            $pageguis = explode(';', $Input -> getVar('pageGUIs'));
        }

        $pageschemes = array();
        if ($Input -> getVar('pageSchemes')) {
            $pageschemes = explode(';', $Input -> getVar('pageSchemes'));
        }

        $content = '';
        if (is_array($pagenames)) {
            $prefix = '';
            $edge = '';
            $count = count($pagenames);
            for ($i=0; $i<$count; $i++) {
                $pagename = $pagenames[$i];

                $function_name = $this->function_name;
                if (!is_null($function_name)) {
                    if (is_array($function_name)) {
                        $obj = $function_name[0];
                        $func = $function_name[1];
                        $obj->$func($pagename);
                    }
                    else {
                        call_user_func($function_name, $pagename);
                    }
                }

                if ($i == 0) {
                    $prefix = 'first';
                }
                elseif ($i == $count - 1) {
                    $prefix = 'last';
                }
                else {
                    $prefix = '';
                }
                if (isset($pagenames[$i+1]) and (strcasecmp($pagenames[$i+1], $Input -> getVar($activePageUrlParam)) == 0)) {
                    $edge = 'change';
                }
                else {
                    $edge = 'normal';
                }
                if (strcasecmp($pagename, $Input -> getVar($activePageUrlParam)) == 0) {
                    $state = 'active';
                }
                else {
                    $state = 'inactive';
                }
                $Template -> newBlock($prefix . 'page');
                $Template -> newBlock($prefix . 'page_' . $state);
                if (isset($pagetitles[$i])) {
                    $Template -> setVar('pagename', $pagetitles[$i]);
                }
                else {
                    $Template -> setVar('pagename', $pagename);
                }

                $Url->setParam($activePageUrlParam, $pagename);
                $Template -> setVar('pageurl', $Url -> getUrl());

                $Template -> newBlock($prefix . 'page_' . $state . '_edge_' . $edge);
                $Template -> backToFile('stdout');

                if ($state == 'active' and is_array($pageguis) and count($pageguis) > 0) {
                    if ($pageguis[$i]) {
                        $GUI = GUI_Module::createGUIModule($pageguis[$i], $this->getOwner(), $this);
                        if (is_a($GUI, 'GUI_Module')) {
                            $GUI -> importHandoff($this -> Handoff);
                            $GUI -> prepareContent();
                            $content .= $GUI -> finalizeContent();
                        }
                        else {
                            $this -> raiseError(__FILE__, __LINE__, 'Couldn\'t create GUI_Module "' . $pageguis[$i] . '"');
                        }
                    }
                }

                if ($state == 'active' and is_array($pageschemes) and count($pageschemes) > 0) {
                    if ($pageschemes[$i]) {
                        $GUI = GUI_Module::createGUIModule('GUI_Schema', $this->getOwner(), $this);
                        if (is_a($GUI, 'GUI_Schema')) {
                            $GUI -> Input -> setVar('schema', $pageschemes[$i]);
                            $GUI -> importHandoff($this -> Handoff);
                            $GUI -> prepareContent();
                            $content .= $GUI -> finalizeContent();
                        }
                        else {
                            $this -> raiseError(__FILE__, __LINE__, 'Couldn\'t create GUI_Schema "' . $pageschemes[$i] . '"');
                        }
                    }
                }

                $Template -> setVar('content', $content);
            }
        }
    }

    /**
     * Ruft die benutzerdefinierte Funktion fuer jede Page auf. Z.B. k�nnte hier die URL fuer jeden Reiter geaendert werden.
     * $GUI_PageControl -> Url -> setParam('mode', 'liste');
     *
     * @param unknown_type $function_name
     */
    function setUserFunction($function_name)
    {
        $this -> function_name = $function_name;
    }

    /**
     * GUI_PageControl::finalize()
     *
     * Inhalt parsen und zurueck geben (revive).
     *
     * @return string Content
     **/
    function finalize(): string
    {
        $this -> Template -> parse('stdout');
        return $this -> Template -> getContent('stdout');
    }
}