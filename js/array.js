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

/**
 * Uniquely combines two arrays
 */
function array_union(a, b)
{
    return Array.from(new Set([...a, ...b]));
}

/**
 * Removes the differences between 2 arrays
 */
function array_difference(a, b)
{
    return a.filter(function(i) {
        return b.indexOf(i) < 0;
    });
}