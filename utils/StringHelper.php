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

use function strlen;
use function strpos;
use function strrpos;
use function substr;
use function trim;

final class StringHelper
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
        if ($prefix !== '' && str_starts_with($value, $prefix)) {
            $value = substr($value, strlen($prefix));
        }
        return $value;
    }

    /**
     * Removes a suffix from a given string.
     *
     * @param string $value The input string to remove the suffix from.
     * @param string $suffix The suffix that should be removed. The default value is '/'.
     * @return string The input string without the suffix.
     */
    public static function removeSuffix(string $value, string $suffix = '/'): string
    {
        if ($suffix !== '' && str_ends_with($value, $suffix)) {
            $value = substr($value, 0, -strlen($suffix));
        }
        return $value;
    }

    /**
     * Slices a string after the specified marker.
     *
     * @param string $string The input string to slice.
     * @param string $marker The marker after which the string should be sliced. The default value is '/'.
     * @param bool $returnEmptyIfNotFound Specifies whether to return an empty string if the marker is not found. Default value is true.
     * @return string The sliced string.
     */
    public static function sliceAfter(string $string, string $marker = '/', bool $returnEmptyIfNotFound = true): string
    {
        $pos = strpos($string, $marker);
        if ($pos !== false) {
            return substr($string, $pos + strlen($marker));
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
        $pos = strrpos($string, $marker);
        if ($pos !== false) {
            return substr($string, 0, $pos);
        }
        return $returnEmptyIfNotFound ? '' : $string;
    }


    /**
     * Normalize a delimiter-separated list into an array of canonicalized tokens.
     *
     * @param string            $input             Raw input string
     * @param array<int,string> $separators        Delimiters to split on (default: [",",";"])
     * @param bool              $caseInsensitive   Whether uniqueness check ignores case
     * @param null|callable     $elementNormalizer Optional callback fn(string $e): ?string
     *                                             Return null/"" to drop the item
     * @param bool              $unique            Remove duplicates if true
     * @return array<int,string>
     * @noinspection GrazieInspection
     */
    public static function normalizeListToArray(
        string $input,
        array $separators = [',', ';'],
        bool $caseInsensitive = false,
        ?callable $elementNormalizer = null,
        bool $unique = true,
    ): array {
        if (trim($input) === '') return [];

        // 1) Sanitize separators
        $separators = \array_values(\array_unique(\array_filter($separators, static fn(string $s) => $s !== '')));
        if ($separators === []) $separators = [','];//fallback to comma if no valid separators

        // 2) Decide: all single-char vs. any multi-char
        $allSingle = true;
        foreach ($separators as $s) if (\mb_strlen($s, 'UTF-8') !== 1) { $allSingle = false; break; }

        // 3) Build split pattern
        if ($allSingle) {
            // Fast path: character class (no sorting needed)
            $alts    = \array_map(static fn(string $d) => \preg_quote($d, '/'), $separators);
            $pattern = '/[' . \implode('', $alts) . ']\h*/u';
        } else {
            // Multi-char present: use alternation, longest-first to avoid premature matches
            \usort($separators, static fn(string $a, string $b) => \mb_strlen($b, 'UTF-8') <=> \mb_strlen($a, 'UTF-8'));
            $alts    = \array_map(static fn(string $d) => \preg_quote($d, '/'), $separators);
            $pattern = '/(?:' . \implode('|', $alts) . ')\h*/u';
        }

        // Split input string
        $parts = \preg_split($pattern, $input, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        $out = [];
        $seen = [];

        foreach ($parts as $p) {
            $e = trim($p);
            if ($e === '') continue;

            if ($elementNormalizer) {
                $e = $elementNormalizer($e);
                if ($e === null || $e === '') continue;
            }

            if ($unique) {
                $key = $caseInsensitive ? \mb_strtolower($e, 'UTF-8') : $e;
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
            }

            $out[] = $e;
        }

        return $out;
    }

    /**
     * Normalize a delimiter-separated list into a single canonicalized string.
     * Internally delegates to {@see normalizeListToArray()} and then joins the
     * elements with the specified output separator.
     *
     * @param string            $input             Raw input string
     * @param array<int,string> $separators        Delimiters to split on (default: [",",";"])
     * @param string            $outputSeparator   Separator used to join normalized elements in the output
     * @param bool              $caseInsensitive   Whether uniqueness check ignores case
     * @param null|callable     $elementNormalizer Optional callback fn(string $e): ?string
     *                                             Return null/"" to drop the item
     * @param bool              $unique            Remove duplicates if true
     * @return string                              The normalized list as a single string
     * @noinspection GrazieInspection
     */
    public static function normalizeList(
        string $input,
        array $separators = [',', ';'],
        string $outputSeparator = ',',
        bool $caseInsensitive = false,
        ?callable $elementNormalizer = null,
        bool $unique = true,
    ): string
    {
        return \implode($outputSeparator, self::normalizeListToArray(
            $input,
            $separators,
            $caseInsensitive,
            $elementNormalizer,
            $unique
        ));
    }
}
