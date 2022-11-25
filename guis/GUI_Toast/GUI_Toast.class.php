<?php
/*
 * POOL
 *
 * gui_toast.class.php created at 18.11.20, 19:12
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_Toast extends GUI_Module
{
    /**
     * @param int|null $superglobals
     */
    public function init(?int $superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('framework', 'bs4');
        parent::init($superglobals);
    }

    /**
     * load files
     */
    public function loadFiles()
    {
        $fw = $this->getVar('framework');
        $tpl = $this->Weblication->findTemplate('tpl_toast_'.$fw.'.html', __CLASS__, true);
        $this->Template->setFilePath('stdout', $tpl);

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->getHeadData()->addJavaScript($this->Weblication->findJavaScript('toast.js', __CLASS__, true));
            $this->Weblication->getFrame()->getHeadData()->addStyleSheet($this->Weblication->findStyleSheet('toast_'.$fw.'.css', __CLASS__, true));
        }
    }

    /**
     * prepare content
     */
    public function prepare()
    {
        $this->Template->setVar('moduleName', $this->getName());
        parent::prepare();
    }

    /**
     * @return string html of toast
     */
    protected function finalize(): string
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}