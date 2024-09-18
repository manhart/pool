<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\utils;

use function ftok;
use function register_shutdown_function;
use function sem_acquire;
use function sem_get;
use function sem_release;

final class Cron
{
    /**
     * Acquire a semaphore lock for a cron job
     *
     * @param string $file path to lock file
     * @param string $projectId single-letter project ID
     */
    public static function acquireSemaphoreLock(string $file, string $projectId): bool
    {
        $semaphore = sem_get(ftok($file, $projectId));
        if (sem_acquire($semaphore, true)) {
            register_shutdown_function(static fn() => sem_release($semaphore));
            return true;
        }
        return false;
    }
}