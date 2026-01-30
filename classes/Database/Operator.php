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
 * Define general operators for DAO's directly mapped to SQL strings
 */
enum Operator: string
{
    // Comparison operators
    case equal = '=';
    case notEqual = '!=';
    case greater = '>';
    case greaterEqual = '>=';
    case less = '<';
    case lessEqual = '<=';
    // SQL specific operators
    case like = 'like';
    case notLike = 'not like';
    case in = 'in';
    case notIn = 'not in';
    case is = 'is';
    case isNot = 'is not';
    // Null operators
    case isNull = 'is null';
    case isNotNull = 'is not null';
    // Range operators
    case between = 'between';
    case notBetween = 'not between';
    // Existence operators
    case exists = 'exists';
    case notExists = 'not exists';
    // Quantifiers
    case all = 'all';
    case any = 'any';
    // Logical operators
    case or = 'or';
    case and = 'and';
    case xor = 'xor';
    case not = 'not';

    public function isRange(): bool
    {
        return match ($this) {
            self::between, self::notBetween => true,
            default => false
        };
    }
}
