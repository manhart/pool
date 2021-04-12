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
     * @var array options for the table
     */
    protected array $options = [
        'data-url' => null,
        'data-classes' => 'table table-bordered table-hover',
        'data-thead-classes' => 'undefined',
        'data-click-to-select' => false,
        'data-pagination' => false,
        'data-search-highlight' => false,
        'data-show-columns' => false,
        'data-show-fullscreen' => false,
        'data-show-refresh' => false,
        'data-show-toggle' => false,
        'data-sortable' => true,
        'data-search' => false,
        'data-side-pagination' => 'client'
    ];

    protected array $columns = [];

    /**
     * @param const|int $superglobals
     */
    public function init($superglobals = I_EMPTY)
    {
        $this->Defaults->addVar('framework', 'bs4');
        $this->Defaults->addVar('data-url', null);
        $this->Defaults->addVar('columns', null);
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

    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
    }

    public function provision()
    {
        if($this->Input->getVar('columns') != null) {
            $columns = $this->Input->getVar('columns');
            switch (gettype($columns)) {
                case 'string':
                    if(isJSON($columns)) {
                        $this->columns = json_decode($columns, true);
                    }
            }
        }
    }

    /**
     * prepare content
     */
    public function prepare()
    {
        $this->Template->setVar('moduleName', $this->getName());

        $this->Template->newBlock('tableAttributes');
        foreach($this->options as $attrName => $attrValue) {
            $inpValue = $this->Input->getVar($attrName);
            $attrValue = $inpValue ?? $attrValue;

            if(is_bool($attrValue)) {
                $attrValue = bool2string($attrValue);
            }

            $TableAttributeBlock = $this->Template->newBlock('tableAttribute');
            $TableAttributeBlock->setVar([
                    'data-name' => $attrName,
                    'data-value' => $attrValue
                ]
            );
        }

        $this->Template->newBlock('row');
        foreach($this->columns as $column) {
            $ColumnBlock = $this->Template->newBlock('column');
            $ColumnBlock->setVar('caption', $column['caption'] ?? '');
            unset($column['caption']);
            foreach($column as $attrName => $attrValue) {
                $ColumnAttributeBlock = $this->Template->newBlock('columnAttribute');
                $ColumnAttributeBlock->setVar([
                    'data-name' => $attrName,
                    'data-value' => $attrValue
                ]);
            }
        }
        parent::prepare();
    }

    /**
     * Creates data format for the bootstrap table
     */
    static function getRowSetAsArray(Resultset $ResultSet, int $total): array
    {
        $return = [];
        $return['total'] = $total;
        //            $return['totalNotFiltered'] = $total;
        $return['rows'] = $ResultSet->getRowset();
        return $return;
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