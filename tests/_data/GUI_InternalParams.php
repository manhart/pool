<?php
declare(strict_types=1);

namespace pool\tests;

use pool\classes\GUI\GUI_Module;

final class GUI_InternalParams extends GUI_Module
{
    public array $paramsAtInit = [];

    public function init(?int $superglobals = null): void
    {
        parent::init($superglobals);
        $this->paramsAtInit = $this->getInternalParams();
    }
}
