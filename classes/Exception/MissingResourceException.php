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

use pool\classes\Core\Http\hasHttpStatus;
use pool\classes\Core\Http\HttpStatusInterface;

class MissingResourceException extends RuntimeException implements HttpStatusInterface {
    use hasHttpStatus;
    protected const int HTTP_STATUS = 404;
}
