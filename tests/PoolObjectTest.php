<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Core\PoolObject;


class PoolObjectTest extends TestCase
{
    public function testPoolObject()
    {
        $PoolObject = new PoolObject();
        $this->assertEquals('PoolObject', $PoolObject->getClassName());
    }
}