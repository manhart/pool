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

namespace pool\classes\Core\Http;

use pool\classes\Exception\InvalidArgumentException;

final class HttpErrorMapper {
    public static function statusCode(\Throwable $e): int {
        if ($e instanceof HttpStatusInterface) {
            return $e->httpStatus();
        }

        // Canonical mapping without interface (e.g., 400 for parameter errors)
        if ($e instanceof InvalidArgumentException) {
            return 400;
        }
        return 500;
    }
}
