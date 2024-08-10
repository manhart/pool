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
 * -= array.js =-
 *
 * Fuer alle Browser, die keine Array Funktionen shift, unshift, push und pop unterstuetzen (< IE 5.5 / Mac)
 * Plus erweiterte Funktionen, die man aus PHP kennt.
 *
 * $Log: array.js,v $
 * Revision 1.4  2004/09/23 14:08:02  manhart
 * Log Message included
 *
 *
 * @version $Id: array.js,v 1.4 2004/09/23 14:08:02 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
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