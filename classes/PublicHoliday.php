<?php
/*
 * g7system.local
 *
 * German Public Holiday
 *
 * Feiertag.class.php created at 25.03.22, 10:19
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

class PublicHoliday
{
    /**
     * @var int key constant of PublicHolidays
     */
    private int $key;

    /**
     * @var DateTimeInterface Date
     */
    private \DateTimeInterface $Date;

    /**
     * @var string name of holiday
     */
    private string $name = '';

    /**
     * @param int $key
     * @param DateTimeInterface $Date
     */
    public function __construct(int $key, \DateTimeInterface $Date)
    {
        $this->key = $key;
        $this->Date = $Date;
    }

    /**
     * returns date
     *
     * @return DateTimeInterface
     */
    public function getDate(): \DateTimeInterface
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
        if(!$this->name) {
            $this->name = PublicHolidays::getHolidayName($this->key);
        }
        return $this->name;
    }
}