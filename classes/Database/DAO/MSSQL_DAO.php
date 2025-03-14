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

use pool\classes\Core\RecordSet;
use pool\classes\Database\DAO;

use function array_fill_keys;

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
     * MSSQL requires a ORDER BY-clause if there is a LIMIT-clause. By default, if nothing is given we sort by the primary key.
     */
    public function getMultiple(
        array|int|string|null $id = null,
        array|string|null $key = null,
        array $filter = [],
        array $sorting = [],
        array $limit = [],
        array $groupBy = [],
        array $having = [],
        array $options = [],
    ): RecordSet {
        if ($limit && !$sorting) {
            // sort by primary key ascending
            $sorting = array_fill_keys($this->getPrimaryKey(), 'ASC');
        }
        return parent::getMultiple($id, $key, $filter, $sorting, $limit, $groupBy, $having, $options);
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