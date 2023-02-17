<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Utils;

enum System: int
{
    case OS_UNKNOWN = 1;
    case OS_LINUX = 2;
    case OS_MACOS = 3;
    case OS_WINDOWS = 4;

    /**
     * get server-side operating system
     *
     * @return System
     */
    public static function get(): System
    {
        return match (PHP_OS) {
            'Linux' => System::OS_LINUX,
            'Darwin' => System::OS_MACOS,
            'WINNT', 'WIN32', 'Windows' => System::OS_WINDOWS,
            default => System::OS_UNKNOWN,
        };
    }
}