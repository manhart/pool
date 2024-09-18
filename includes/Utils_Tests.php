<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\includes;

use PHPUnit\Framework\TestCase;

class Utils_Tests extends TestCase
{
    public function testBytes(): void
    {
        include "Utils.inc.php";
        self::assertEquals('20,00 Bytes', formatBytes(20));
        self::assertEquals('20 B', formatBytes(20, true, 0));
        self::assertEquals('2,00 KBytes', formatBytes(2048));
        self::assertEquals('2,00 TBytes', formatBytes(pow(2, 10 * 4 + 1)));
        self::assertEquals('2,00 TB', formatBytes(pow(2, 10 * 4 + 1), true));
        self::assertEquals(
            '20.

        000 TB',
            formatBytes(pow(2, 10 * 4 + 1) * 10000, true, 0),
        );
    }

    public function testNumberAbbreviation(): void
    {
        include "Utils.inc.php";
        self::assertEquals('20,00', abbreviateNumber(20));
        self::assertEquals('20', abbreviateNumber(20, 0));
        self::assertEquals('2.48 K', abbreviateNumber(2480, decimal_separator: '.', blank: ' '));
        self::assertEquals('1B', abbreviateNumber(pow(10, 9), 0));
        self::assertEquals('2.000,000Q', abbreviateNumber(pow(10, 18) * 2, 3));
    }
}