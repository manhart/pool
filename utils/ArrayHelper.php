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

    /**
     * Sets a value in a nested array by a delimiter-separated path (e.g. "rootNode.childNode.leafNode").
     * If intermediate path segments do not exist, they are created as arrays.
     * If an intermediate segment exists but is not an array:
     *  - strict=false: it will be overwritten with an array
     *  - strict=true : an \InvalidArgumentException is thrown
     *
     * @param array $array Target array (modified in place)
     * @param string $path Delimiter-separated path
     * @param mixed $value Value to set
     * @param string $delimiter Path delimiter (default ".")
     * @param bool $strict Throw on scalar/array collisions
     */
    public static function setByPath(
        array &$array,
        string $path,
        mixed $value,
        string $delimiter = '.',
        bool $strict = false,
    ): void {
        if ($path === '') {
            throw new \InvalidArgumentException('Path must not be empty.');
        }

        $segments = explode($delimiter, $path);
        $cursor = &$array;

        foreach ($segments as $segment) {
            if ($segment === '') throw new \InvalidArgumentException('Path contains an empty segment.');
            if (!array_key_exists($segment, $cursor)) {
                $cursor[$segment] = [];
            } elseif (!is_array($cursor[$segment])) {
                if ($strict) throw new \InvalidArgumentException("Cannot descend into \"$segment\": existing value is not an array.");
                $cursor[$segment] = [];
            }
            $cursor = &$cursor[$segment];
        }

        $cursor = $value;
        unset($cursor);
    }
}
