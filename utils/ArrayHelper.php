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

    /**
     * Recursively compares two arrays for content equality.
     * - For associative arrays, the order of keys does not matter.
     * - For indexed (numerical) arrays, order and value equality is required.
     * - Type equality (===) is enforced for scalar values.
     *
     * @param array $a The first array to compare.
     * @param array $b The second array to compare.
     * @return bool True if arrays are equal in content and structure; false otherwise.
     */
    public static function isDeepEqual(array $a, array $b): bool
    {
        if (array_is_list($a) && array_is_list($b)) {
            return $a === $b;
        }

        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($a as $key => $valueA) {
            if (!array_key_exists($key, $b)) {
                return false;
            }

            $valueB = $b[$key];

            if (is_array($valueA) && is_array($valueB)) {
                if (!self::isDeepEqual($valueA, $valueB)) {
                    return false;
                }
            } elseif ($valueA !== $valueB) {
                return false;
            }
        }

        return true;
    }
}
