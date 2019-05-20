<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * $HeadURL$
 *
 * Erweiterung zur Utils.inc.php: Sockets
 *
 * @version $Id$
 * @version $Revision$
 * @version $Author$
 * @version $Date$
 *
 * @since 2008-01-16
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://www.manhart.la
 **/

/**
 * Pr?ft ob es sich um ein Verzeichnis handelt
 *
 * @param resource $con_id
 * @param string $dir
 * @return boolean
 */
function ftp_is_dir($con_id, $dir)
{
    $pwd = ftp_pwd($con_id);
    if (@ftp_chdir($con_id, $dir)) {
        ftp_chdir($con_id, $pwd);
        return true;
    }
    else {
        return false;
    }
}

/**
 * Legt ein nicht vorhandenes Verzeichnis an
 *
 * @param resource $con_id
 * @param string $dir
 * @return boolean
 */
function ftp_make_dir($con_id, $dir)
{
    $result=false;
    if(!ftp_is_dir($con_id, $dir)) {
        $result=ftp_mkdir($con_id, $dir);
    }
    return $result;
}

/**
 * L?dt alle Dateien reskursiv hoch
 *
 * @param resource $con_id FTP-Verbindung
 * @param string $source_dir Quellverzeichnis auf dem lokalen Rechner
 * @param string $target_dir Zielverzeichnis auf dem entfernten FTP Server
 */
function ftp_upload_recursive($con_id, $source_dir, $target_dir, $pattern='*')
{
    if(!is_resource($con_id)) {
        return false;
    }

    $local_files = glob(addEndingSlash($source_dir).$pattern, GLOB_NOSORT);
    ftp_make_dir($con_id, $target_dir); //wechselt zugleich in das Verzeichnis
    foreach($local_files as $local_file) {
        if(is_dir($local_file)) {
            $dir = basename(removeEndingSlash($local_file));
            ftp_upload_recursive($con_id, addEndingSlash($source_dir).$dir, addEndingSlash($target_dir).$dir);
        }
        else {
            $local_file_basename = addEndingSlash($target_dir).basename($local_file);
            ftp_put($con_id, $local_file_basename, $local_file, FTP_BINARY);
        }
    }
    return true;
}

/**
 * L?scht Dateien rekursiv vom FTP-Server
 *
 * @param string $con_id FTP-Verbindung
 * @param string $dir Zu l?schendes Hauptverzeichnis auf dem FTP-Server
 */
function ftp_remove_recursive($con_id, $dir)
{
    $rawlist = ftp_rawlist($con_id, $dir, false);
    if(is_array($rawlist) and count($rawlist)>0)
        foreach ($rawlist as $fileinfo) {
            $fileinfo = preg_split('/[\s]+/', $fileinfo, 9);
            $file = $fileinfo[8];

            if($fileinfo[0]{0} == 'd') { // Verzeichnis
                ftp_remove_recursive($con_id, addEndingSlash($dir).$file);
            }
            else {
                ftp_delete($con_id, addEndingSlash($dir).$file);
            }
        }
    ftp_rmdir($con_id, $dir);
}