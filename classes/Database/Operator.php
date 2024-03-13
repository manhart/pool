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

namespace pool\classes\Database;

/**
 * Define general operators for DAO's
 */
enum Operator
{
    case equal;
    case notEqual;
    case greater;
    case greaterEqual;
    case less;
    case lessEqual;
    case like;
    case notLike;
    case in;
    case notIn;
    case is;
    case isNot;
    case isNull;
    case isNotNull;
    case between;
    case notBetween;
    case exists;
    case notExists;
    case all;
    case any;
    case or;
    case and;
    case xor;
    case not;
}