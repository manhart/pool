<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class InputFiles extends Input
{
    function __construct(int $superglobals = Input::INPUT_FILES)
    {
        parent::__construct($superglobals);
    }
}