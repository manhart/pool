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

namespace pool\utils;

use DateTimeInterface;

class PublicHoliday
{
    /**
     * @var int key constant of PublicHolidays
     */
    private int $key;

    /**
     * @var DateTimeInterface Date
     */
    private DateTimeInterface $Date;

    /**
     * @var string name of holiday
     */
    private string $name = '';

    /**
     * @param int $key
     * @param DateTimeInterface $Date
     */
    public function __construct(int $key, DateTimeInterface $Date)
    {
        $this->key = $key;
        $this->Date = $Date;
    }

    /**
     * returns date
     *
     * @return DateTimeInterface
     */
    public function getDate(): DateTimeInterface
    {
        return clone $this->Date;
    }

    /**
     * returns name of holiday
     *
     * @return string
     */
    public function getName(): string
    {
        if (!$this->name) {
            $this->name = PublicHolidays::getHolidayName($this->key);
        }
        return $this->name;
    }
}