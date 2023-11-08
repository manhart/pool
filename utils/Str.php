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

final class Str
{
    /**
     * Removes a prefix from a string.
     *
     * @param string $value The input string
     * @param string $prefix The prefix to remove (default: '/')
     * @return string The modified string with the prefix removed
     */
    public static function removePrefix(string $value, string $prefix = '/'): string
    {
        if(str_starts_with($prefix, $value)) {
            $value = \substr($value, \strlen($prefix));
        }
        return $value;
    }

    /**
     * Removes a suffix from a given string.
     *
     * @param string $value The input string to remove the suffix from.
     * @param string $suffix The suffix that should be removed. Default value is '/'.
     * @return string The input string without the suffix.
     */
    public static function removeSuffix(string $value, string $suffix = '/'): string
    {
        if(str_ends_with($suffix, $value)) {
            $value = \substr($value, 0, -\strlen($suffix));
        }
        return $value;
    }

    /**
     * Slices a string after the specified marker.
     *
     * @param string $string The input string to slice.
     * @param string $marker The marker after which the string should be sliced. Default value is '/'.
     * @param bool $returnEmptyIfNotFound Specifies whether to return an empty string if the marker is not found. Default value is true.
     * @return string The sliced string.
     */
    public static function sliceAfter(string $string, string $marker = '/', bool $returnEmptyIfNotFound = true): string
    {
        // @todo replace in PHP 8.3 through strstr($string, $separator, true)
        $pos = \strpos($string, $marker);

        // Falls ein Slash gefunden wurde und nicht am Anfang steht, schneide den String dahinter aus
        if($pos !== false) {
            return \substr($string, $pos + \strlen($marker));
        }

        return $returnEmptyIfNotFound ? '' : $string;
    }

    /**
     * Retrieves the portion of a string before a specified marker.
     *
     * @param string $string The input string to extract from.
     * @param string $marker The marker used to determine the extraction point. Default value is '/'.
     * @param bool $returnEmptyIfNotFound Determines whether to return an empty string if the marker is not found. Default value is true.
     * @return string The portion of the input string before the marker. If the marker is not found and $returnEmptyIfNotFound is true, an empty string is returned.
     */
    public static function sliceBefore(string $string, string $marker = '/', bool $returnEmptyIfNotFound = true): string
    {
        $pos = \strrpos($string, $marker);

        // Falls ein Slash gefunden wurde und nicht am Anfang steht, schneide den String dahinter aus
        if($pos !== false) {
            return \substr($string, 0, $pos);
        }

        return $returnEmptyIfNotFound ? '' : $string;
    }
}