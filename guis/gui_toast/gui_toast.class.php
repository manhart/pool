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
     * Load files
     *
     * @throws ReflectionException
     */
    public function loadFiles()
    {
        $className = strtolower($this->getClassName());
        $fw = $this->getVar('framework');
        $tpl = $this->Weblication->findTemplate('tpl_toast_'.$fw.'.html', $className, true);
        $this->Template->setFilePath('stdout', $tpl);

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->Headerdata->addJavaScript($this->Weblication->findJavaScript('toast.js', $className, true));
            $this->Weblication->getFrame()->Headerdata->addStyleSheet($this->Weblication->findStyleSheet('toast_'.$fw.'.css', $className, true));
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
     * render content
     *
     * @return string
     */
    public function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}