<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database;

class SqlStatement
{
    private string $value;

    /**
     * @param string $value
     */
    public function __construct(string $value) { $this->value = $value; }

    /**
     * @return string
     */
    public function getStatement(): string
    {
        return $this->value;
    }
}