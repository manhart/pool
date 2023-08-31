<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Exception;

use pool\classes\Core\RecordSet;
use RuntimeException as PhpRuntimeException;

class DAOException extends PhpRuntimeException implements PoolExceptionInterface
{
    /**
     * You can pass the last error from the pool\classes\Core\ResultSet to this exception.
     *
     * @param array $lastError
     * @return $this
     * @see RecordSet::getLastError()
     */
    public function setLastError(array $lastError): static
    {
        $this->message = $lastError['message'] ?? $this->message;
        $this->code = $lastError['code'] ?? $this->code;
        return $this;
    }
}