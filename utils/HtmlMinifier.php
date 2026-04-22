<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

declare(strict_types = 1);

namespace pool\utils;

use function preg_replace;

/**
 * High-performance, KISS HTML minifier.
 * This implementation utilizes PCRE control verbs (*SKIP)(*F) to achieve maximum performance
 * by avoiding expensive context switches between the PCRE C-engine and the PHP interpreter (callbacks).
 * Strategy:
 *  - Use (*SKIP)(*F) to "protect" specific zones like <pre>, <script>, and comments.
 *  - Only collapse whitespace runs that contain at least one newline into a single space.
 *  - This preserves intentional inline spacing while removing unnecessary indentation and vertical bloat.
 *
 * @package pool\classes\Core
 */
final class HtmlMinifier
{
    /**
     * Minify modes
     */
    public const int MODE_OFF = 0;
    public const int MODE_LEAN = 1;
    public const int MODE_FULL = 2;

    /**
     * Minify HTML according to the given mode.
     * Options:
     *  - remove_comments (bool): strip HTML comments.
     *    Default: false for LEAN, true for FULL.
     *
     * @param string $html Input HTML
     * @param int $mode One of self::MODE_OFF|MODE_LEAN|MODE_FULL
     * @param array $options See above
     */
    public static function minify(string $html, int $mode, array $options = []): string
    {
        if ($mode === self::MODE_OFF || $html === '') {
            return $html;
        }

        $removeComments = $options['remove_comments'] ?? ($mode === self::MODE_FULL);

        /**
         * PCRE Control Verbs Logic:
         * -------------------------
         * `(*SKIP)(*F)` tells the engine: "If this part matches, DO NOT try any other alternatives
         * from this point and DO NOT replace anything here. Instead, jump (SKIP) behind this match
         * and continue searching from there."
         * 1. Protect <pre>, <textarea>, <script>, <style> blocks.
         * 2. Protect CDATA sections.
         * 3. Handle standard comments: either skip them (keep) or let them fall through (strip).
         * 4. Protect all other HTML tags themselves.
         * 5. Match any whitespace run containing a newline and replace it with a single space.
         */
        $pattern = '~
            <(pre|textarea|script|style)\b[^>]*>.*?</\1> (*SKIP)(*F)
            | <!\[CDATA\[.*?\]\]> (*SKIP)(*F)
            '.($removeComments ? '| <!--.*?-->' : '| <!--.*?--> (*SKIP)(*F)').'
            | <[^>]+> (*SKIP)(*F)
            | \s*[\r\n]\s*
            ~sxi';

        $result = preg_replace($pattern, ' ', $html);
        return $result ?? $html;
    }
}
