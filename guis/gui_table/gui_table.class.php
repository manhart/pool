<?php
/*
 * POOL
 *
 * gui_table.class.php created at 08.04.21, 13:16
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */


class GUI_Table extends GUI_Module
{
    /**
     * @param const|int $superglobals
     */
    public function init($superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('framework', 'bs4');
        $this->Defaults->addVar('data-url', '');
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
        $tpl = $this->Weblication->findTemplate('tpl_table_'.$fw.'.html', $className, true);
        $this->Template->setFilePath('stdout', $tpl);

        if($this->Weblication->hasFrame()) {
            $this->Weblication->getFrame()->Headerdata->addJavaScript($this->Weblication->findJavaScript('table.js', $className, true));
            //$this->Weblication->getFrame()->Headerdata->addStyleSheet($this->Weblication->findStyleSheet('table_'.$fw.'.css', $className, true));
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