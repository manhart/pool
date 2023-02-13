<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

#### global databases:
const DB_TESTING = 'testing';

/* ===== */
/* MySQL */
/* ===== */
$Testing_User = array(MySQLi_Interface::class, DB_TESTING, 'User');

define('DBACCESSFILE', DIR_CONFIGS_ROOT . '/access.inc.php');