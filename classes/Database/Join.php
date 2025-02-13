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

declare(strict_types = 1);

namespace pool\classes\Database;

use Exception;
use Stringable;

/**
 * represents a SQL-JOIN statement
 */
final class Join implements Stringable
{
    public const string LEFT_JOIN = 'LEFT';
    public const string ON = 'ON';
    public const string AND = 'AND';

    public function __construct(
        private readonly DAO|string $join,
        private DAO|string $JoinWith = '',
        private readonly string|array $joinConditions = '',
        private readonly string $alias = '',
        private readonly string $conditionType = self::ON,
        private readonly string $joinType = self::LEFT_JOIN,
    ) {}

    public function getJoinDAO(): DAO|string
    {
        return $this->join;
    }

    public function getJoin(): string
    {
        return "{$this->join::getDatabaseName()}.{$this->join::getTableName()}";
    }

    public function setJoinWith(DAO|string $dao): void
    {
        $this->JoinWith = $dao;
    }

    public function getJoinWith(): DAO|string
    {
        return $this->JoinWith;
    }

    public function getAlias(): string
    {
        return $this->alias ?: $this->join::getTableName();
    }

    /**
     * Find a matching referencedTableName that equals daoTableName in the foreign key array.
     */
    private function findFKMatchInDAO(DAO $daoA, DAO $daoB): array
    {
        foreach ($daoA->getForeignKeys() as $fk) {
            if ("{$fk['referencedTableSchema']}.{$fk['referencedTableName']}" === "{$daoB::getDatabaseName()}.{$daoB::getTableName()}") {
                return $fk;
            }
        }
        return [];
    }

    /*
     * Create a join condition from a foreignKey match and construct the condition.
     */
    private function createJoinConditionFromFKMatch(array $fkMatch, DAO|null $daoMatched = null): string
    {
        [
            'columnName' => $columnName,
            'referencedTableSchema' => $referencedTableSchema,
            'referencedTableName' => $referencedTableName,
            'referencedColumnName' => $referencedColumnName,
        ] = $fkMatch;

        $leftExpr = $daoMatched
            ? "{$daoMatched::getDatabaseName()}.{$daoMatched::getTableName()}.{$columnName}"
            : "{$referencedTableSchema}.{$referencedTableName}.{$referencedColumnName}";

        $rightExpr = $daoMatched
            ? "`{$this->getAlias()}`.{$referencedColumnName}"
            : "`{$this->getAlias()}`.{$columnName}";

        return (string)new BuildExpression(
            $leftExpr,
            Operator::equal,
            $rightExpr,
        );
    }

    /**
     * Get the join conditions as a string from $this->joinConditions
     * Option A) its already a string e.g. 'a.id = b.id'
     * Option B) its an array e.g. [['a.id', Operator::equal, 'b.id'] which gets converted to 'a.id = b.id'
     * Option C) its empty and we try to find a matching FK in the DAOs and create a join condition from it
     * Option D) its empty and nothing is matching so we join without a condition
     *
     * @throws Exception
     */
    public function getJoinConditions(): string
    {
        $joinDAO = $this->join::create(throws: true);
        $joinWithDAO = $this->JoinWith::create(throws: true);

        if (!empty($this->joinConditions) && is_string($this->joinConditions)) {
            return $this->joinConditions;
        }

        if (!empty($this->joinConditions) && is_array($this->joinConditions)) {
            $conditions = [];
            foreach ($this->joinConditions as $index => $joinCondition) {
                $condition = (string)new BuildExpression($joinCondition[0], $joinCondition[1], $joinCondition[2]);
                $conditions[] = $condition;
                if (isset($this->joinConditions[$index + 1])) {
                    $conditions[] = $joinCondition[3] ?? self::AND;
                }
            }
            return implode(' ', $conditions);
        }

        if (!empty($fkInfo = $this->findFKMatchInDAO($joinDAO, $joinWithDAO))) {
            return $this->createJoinConditionFromFKMatch($fkInfo);
        }

        if (!empty($fkInfo = $this->findFKMatchInDAO($joinWithDAO, $joinDAO))) {
            return $this->createJoinConditionFromFKMatch($fkInfo, $joinWithDAO);
        }

        return '';
    }

    /**
     * NOTE: backticks for alias: this is used to escape the alias name in case it is a reserved keyword! e.g. GROUP (this is an autogenerated alias from `g7portal.group`)
     *
     * @throws Exception
     */
    public function __toString(): string
    {
        return "$this->joinType JOIN {$this->getJoin()} AS `{$this->getAlias()}` ".($this->conditionType ? "$this->conditionType ({$this->getJoinConditions()})" : "");
    }
}