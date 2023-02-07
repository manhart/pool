<?php
/*
 * POOL
 *
 * gui_dbtable.class.php created at 08.04.21, 13:16
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

class GUI_DBTable extends GUI_Table
{
    /**
     * @param int|null $superglobals
     */
    public function init(?int $superglobals = Input::INPUT_EMPTY)
    {
        $this->Defaults->addVar('tabledefine', '');
        parent::init($superglobals);
    }
}