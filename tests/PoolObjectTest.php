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


class PoolObjectTest extends TestCase
{
    public function init()
    {
        require_once __DIR__.'/../configs/config.inc.php';
        if(!class_exists(\pool\classes\Core\PoolObject::class)) {
            include __DIR__.'/../classes/Core/PoolObject.php';
        }
    }

    public function testUrl()
    {
        $this->init();

        //        require_once __DIR__.'/../pool.lib.php';

        $PoolObject = new \pool\classes\Core\PoolObject();

        $this->assertEquals('PoolObject', $PoolObject->getClassName());
    }
}