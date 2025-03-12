<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\utils;

final class ArrayHelper
{
    public static function trimValues(array $array): array
    {
        return array_filter(array_map(trim(...), $array), fn($value) => $value !== '');
    }
}