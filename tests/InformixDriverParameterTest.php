<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Database\Driver\Informix;

class InformixDriverParameterTest extends TestCase
{
    public function testAParameterWrappedInSingleQuotesGetsATrailingSpace(): void
    {
        $this->assertSame(
            ["'Sample' ", "'/etc/hostname' ", "'a' "],
            Informix::bindableParams(["'Sample'", "'/etc/hostname'", "'a'"]),
        );
    }

    public function testEveryOtherParameterStaysAsItIs(): void
    {
        $params = [7, null, 1.5, true, 'abc', "'", "''", "O'Brien", "'open", "closed'", "'x' "];

        $this->assertSame($params, Informix::bindableParams($params));
    }

    public function testKeysAreKept(): void
    {
        $this->assertSame(['posnr' => "'x' "], Informix::bindableParams(['posnr' => "'x'"]));
    }
}
