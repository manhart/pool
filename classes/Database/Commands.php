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

/**
 * Define general commands for DAO's
 */
enum Commands
{
    case Now;
    case CurrentDate;
    case CurrentTimestamp;
    case CurrentTimestampUs6; // greek µs
    case Increase;
    case Decrease;
    case Reset;
}