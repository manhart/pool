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
use pool\classes\Database\Commands;
use pool\classes\Database\DAO;
use pool\classes\Exception\DAOException;

use function strtolower;

class Informix_DAO extends DAO
{
    protected array $symbolQuote = ['"', '"'];

    /**
     * Set to true for DSNs configured with DELIMIDENT=y.
     */
    protected static bool $delimitedIdentifiers = false;

    protected function __construct(?string $databaseAlias = null, ?string $table = null)
    {
        parent::__construct($databaseAlias, $table);
        $this->setColumns(...$this->columns);
    }

    protected function wrapSymbols(string $string): string
    {
        return static::$delimitedIdentifiers ? parent::wrapSymbols($string) : $string;
    }

    protected function createCommands(): array
    {
        return [
            Commands::Now->name => 'CURRENT YEAR TO SECOND',
            Commands::CurrentDate->name => 'TODAY',
            Commands::CurrentTimestamp->name => 'CURRENT YEAR TO SECOND',
            Commands::CurrentTimestampUs6->name => 'CURRENT YEAR TO FRACTION(5)',
            Commands::Increase->name => fn($field) => "$field+1",
            Commands::Decrease->name => fn($field) => "$field-1",
            Commands::Reset->name => static fn() => 'DEFAULT',
            Commands::Self->name => fn($field) => $field,
        ];
    }

    protected function buildSelectPrefix(array $limit): string
    {
        if (!$limit) {
            return '';
        }

        return isset($limit[1]) ?
            'SKIP '.(int)$limit[0].' FIRST '.(int)$limit[1].' ' :
            'FIRST '.(int)$limit[0].' ';
    }

    protected function buildLimit(array $limit): string
    {
        return '';
    }

    protected function prepareInsertParts(array $data, string $mode): array
    {
        if (strtolower($mode) !== 'normal') {
            throw new DAOException(__CLASS__.'::insert failed. Informix does not support MySQL insert mode '.$mode.'.');
        }

        return parent::prepareInsertParts($data, 'normal');
    }

    public function upsert(array $data, array|true $onDuplicate = true, string $mode = 'normal'): RecordSet
    {
        throw new DAOException(__CLASS__.'::upsert is not supported for Informix.');
    }

    public function foundRows(): int
    {
        throw new DAOException(__CLASS__.'::foundRows is not supported for Informix.');
    }

    public function optimize(): RecordSet
    {
        throw new DAOException(__CLASS__.'::optimize is not supported for Informix.');
    }

    public function resetAutoIncrement(): RecordSet
    {
        throw new DAOException(__CLASS__.'::resetAutoIncrement is not supported for Informix.');
    }

    public function __toString(): string
    {
        // Informix qualifies tables as [owner.]table — the database name is implied by the ODBC connection.
        return $this->quotedSchema ? "$this->quotedSchema.$this->quotedTable" : $this->quotedTable;
    }
}
