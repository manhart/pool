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

use pool\classes\Exception\InvalidArgumentException;
use Stringable;

/**
 * Building a simple Expressions e.g. for JOIN conditions "t1.col = t2.col"
 */
class BuildExpression implements Stringable
{
    public function __construct(
        protected string $leftExpr,
        protected Operator $operator,
        protected string $rightExpr,
    ) {}

    /**
     * get matching operator from Operator::class
     */
    private function getOperator(): string
    {
        // NOTE: this should be easily removable if pool\classes\Database\Operator::class would return strings. is there a reason for not doing this?
        return match ($this->operator) {
            Operator::equal => '=',
            Operator::greater => '>',
            Operator::greaterEqual => '>=',
            Operator::less => '<',
            Operator::lessEqual => '<=',
            default => throw new InvalidArgumentException('Invalid operator'),
        };
    }

    /**
     * Convert the expression to a string
     */
    public function __toString(): string
    {
        return $this->leftExpr.' '.$this->getOperator().' '.$this->rightExpr;
    }
}