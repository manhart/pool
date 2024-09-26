<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 * $HeadURL$
 * Erweiterung zur Utils.inc.php: Imap
 *
 * @version $Id$
 * @version $Revision$
 * @version $Author$
 * @version $Date$
 * @since 2007-09-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://www.manhart.la
 **/

/**
 * Gibt den Mime Typ als String zurück (Imap Helper)
 *
 * @param object $structure
 * @return string
 */
function get_mime_type(&$structure): string
{
    $primary_mime_type = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
    if ($structure->subtype) {
        return $primary_mime_type[(int)$structure->type].'/'.$structure->subtype;
    }
    return 'TEXT/PLAIN';
}

/**
 * Extrahiert einen Teil aus einer Mail (Imap Helper)
 *
 * @param resource $stream
 * @param int $msg_number
 * @param string $mime_type
 * @param boolean $structure
 * @param boolean $part_number
 * @return string
 */
function get_mail_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false)
{
    if (!$structure) {
        $structure = imap_fetchstructure($stream, $msg_number);
    }

    if ($structure) {
        if ($mime_type == get_mime_type($structure)) {
            if (!$part_number) {
                $part_number = 1;
            }
            $text = imap_fetchbody($stream, $msg_number, $part_number);
            if ($structure->encoding == 3) {
                return imap_base64($text);
            } elseif ($structure->encoding == 4) {
                return imap_qprint($text);
            } else {
                return $text;
            }
        }

        if ($structure->type == 1) /* multipart */ {
            while (list($index, $sub_structure) = each($structure->parts)) {
                if ($part_number) {
                    $prefix = $part_number.'.';
                }
                $data = get_mail_part($stream, $msg_number, $mime_type, $sub_structure, $prefix.($index + 1));
                if ($data) {
                    return $data;
                }
            }
        }
    }
    return false;
}