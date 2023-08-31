<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Database\Exception;

use pool\classes\Exception\PoolExceptionInterface;
use RuntimeException as PhpRuntimeException;

class DatabaseConnectionException extends PhpRuntimeException implements PoolExceptionInterface
{
}