<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database\DAO;

use pool\classes\Database\DAO;

class MSSQL_DAO extends DAO
{
    protected array $symbolQuote = ['[', ']'];

    /**
     * @inheritDoc
     */
    public function getColumnDataType(string $column): string
    {
        // TODO: Implement getColumnDataType() method.
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getColumnInfo(string $column): array
    {
        // TODO: Implement getColumnInfo() method.
        return [];
    }

    /**
     * Build a LIMIT statement for a MS SQL query
     *
     * @param array $limit LIMIT with format [offset, length]
     * @return string LIMIT statement
     */
    protected function buildLimit(array $limit): string
    {
        if (!$limit) return '';
        $offset = $limit[0] ?? 0;
        $rowCount = $limit[1] ?? 0;

        return "OFFSET $offset ROWS FETCH NEXT $rowCount ROWS ONLY";
    }
}