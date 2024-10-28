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

/**
 * @since 2007-09-19
 */
function open_pid_file($file)
{
    $nl = pool\LINE_BREAK;
    if (file_exists($file)) {
        $fp = fopen($file, 'r');
        $pid = fgets($fp, 1024);
        fclose($fp);
        if (posix_kill($pid, 0)) {
            print "Script {$_SERVER['PHP_SELF']} already running with PID: $pid$nl";
            exit;
        }
        print "Removing PID file for defunct server process $pid$nl";
        if (!unlink($file)) {
            print "Cannot unlink PID file $file$nl";
            exit;
        }
    }
    if ($fp = fopen($file, 'w')) {
        fputs($fp, posix_getpid());
        fclose($fp);
        return $fp;
    } else {
        print "Unable to open PID file $file for writing...$nl";
        exit;
    }
}