<?php

/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core\Input;

/**
 * Class GET
 *
 * @package pool\classes\Core\Input
 * @since 2003-07-10
 */
class GET extends Input
{
    /**
     * Initialize GET with the superglobal $_GET
     *
     * @param int $superglobals
     */
    public function __construct(int $superglobals = Input::GET)
    {
        parent::__construct($superglobals);
    }
}