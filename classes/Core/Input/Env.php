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
 * Class Env
 *
 * @package pool\classes\Core\Input
 * @since 2003-07-10
 */
class Env extends Input
{
    function __construct(int $superglobals = Input::ENV)
    {
        parent::__construct($superglobals);
    }
}