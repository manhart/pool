<?php
/*
 * POOL
 *
 * gui_dbtable.class.php created at 08.04.21, 13:16
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_DBTable extends GUI_Table implements JsonConfig
{
    /**
     * @param int|null $superglobals
     */
    public function init(?int $superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('tabledefine', '');
        parent::init($superglobals);
    }

    /**
     * Load files
     *
     * @throws ReflectionException
     */
    public function loadFiles()
    {
        parent::loadFiles();
//        $className = strtolower($this->getClassName());
//        $fw = $this->getVar('framework');
//        $tpl = $this->Weblication->findTemplate('tpl_table_'.$fw.'.html', $className, true);
//        $this->Template->setFilePath('stdout', $tpl);
//
//        if($this->Weblication->hasFrame()) {
//            $this->Weblication->getFrame()->Headerdata->addJavaScript($this->Weblication->findJavaScript('table.js', $className, true));
//            //$this->Weblication->getFrame()->Headerdata->addStyleSheet($this->Weblication->findStyleSheet('table_'.$fw.'.css', $className, true));
//        }
    }

    /**
     * prepare content
     */
    public function prepare()
    {
        parent::prepare();
    }
}