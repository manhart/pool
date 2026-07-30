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
use pool\utils\StringHelper;

final class StringHelperTest extends TestCase
{
    public function testShortenMiddlePreservesBothEndsAndMaximumLength(): void
    {
        $value = 'UDM_TRX_XDC_1234567890_C006699725.xml';
        $shortened = StringHelper::shortenMiddle($value, 35);

        self::assertSame(35, mb_strlen($shortened));
        self::assertSame('UDM_TRX_XDC_1234...0_C006699725.xml', $shortened);
        self::assertSame(
            'SXML26256121_202...7aaa42c5e1d.json',
            StringHelper::shortenMiddle('SXML26256121_20260713064101_67aaa42c5e1d.json', 35),
        );
    }

    public function testShortenMiddleReturnsShortValuesUnchanged(): void
    {
        self::assertSame('SXML26256121.json', StringHelper::shortenMiddle('SXML26256121.json', 35));
    }

    public function testShortenMiddleSupportsMultibyteCharacters(): void
    {
        self::assertSame('ÄÖÜ...567', StringHelper::shortenMiddle('ÄÖÜ1234567', 9));
    }
}
