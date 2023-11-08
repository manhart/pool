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

use pool\classes\Core\RecordSet;

interface DatabaseAccessObjectInterface
{
    /**
     * Fetches the columns automatically from the driver / interface
     */
    public function fetchColumns(): static;

    /**
     * Returns a column list of the table with information about the columns
     */
    public function getColumns(): array;

    /**
     * Returns the data type of column
     */
    public function getColumnDataType(string $column): string;

    /**
     * Returns all information about a column
     */
    public function getColumnInfo(string $column): array;

    /**
     * Returns the number of records of the assembled SQL statement as a RecordSet
     */
    public function getCount(null|int|string|array $id = null, null|string|array $key = null, array $filter = []): RecordSet;

    /**
     * Returns a single record e.g. by primary key
     */
    public function get(int|string|array $id, null|string|array $key = null): RecordSet;

    /**
     * Returns all data records of the assembled SQL statement as a RecordSet
     */
    public function getMultiple(null|int|string|array $id = null, null|string|array $key = null, array $filter = [], array $sorting = [],
        array $limit = [], array $groupBy = [], array $having = [], array $options = []): RecordSet;

    /**
     * Fetching row is a hook that goes through all the retrieved rows. Can be used to modify the row (column content) before it is returned.
     */
    public function fetchingRow(array $row): array;

    /**
     * @return int Number of records / rows
     */
    public function foundRows(): int;

    /**
     * Insert a new record based on the data passed as an array, with the key corresponding to the column name.
     */
    public function insert(array $data): RecordSet;

    /**
     * Update a record by primary key (put the primary key in the data array)
     */
    public function update(array $data): RecordSet;

    /**
     * Delete a record by primary key
     */
    public function delete(int|string|array $id): RecordSet;

    /**
     * Delete multiple records at once
     */
    public function deleteMultiple(array $filter_rules = []): RecordSet;
}