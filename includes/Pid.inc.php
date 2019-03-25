<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * $HeadURL$
 *
 * Erweiterung zur Utils.inc.php: Prozess ID Management
 *
 * @version $Id$
 * @version $Revision$
 * @version $Author$
 * @version $Date$
 *
 * @since 2007-09-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 **/

function open_pid_file($file) {
    if(file_exists($file)) {
        $fp = fopen($file, 'r');
        $pid = fgets($fp, 1024);
        fclose($fp);
        if(posix_kill($pid, 0)) {
            print 'Cronjob '.$_SERVER['PHP_SELF'].' already running with PID: '.$pid.chr(10);
            exit;
        }
        print 'Removing PID file for defunct server process '.$pid.chr(10);
        if(!unlink($file)) {
            print 'Cannot unlink PID file '.$file.chr(10);
            exit;
        }
    }
    if($fp = fopen($file, 'w')) {
        fputs($fp, posix_getpid());
        fclose($fp);
        return $fp;
    }
    else {
        print 'Unable to open PID file '.$file.' for writing...'.chr(10);
        exit;
    }
}