<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Utils.inc.php
 *
 * @version $Id: Utils.inc.php,v 1.65 2007/07/17 13:49:10 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * @since 07/28/2003
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;


/**
 * Gibt den aktuellen UNIX-Timestamp/Zeitstempel in Mikrosekunden zurueck
 *
 * @return float Zeitstempel in Mikrosekunden
 **/
function getMicrotime($seed = 1): float
{
    [$usec, $sec] = explode(' ', microtime());
    return ((float)$usec + ((float)$sec * $seed));
}

/**
 * pray ()
 * Erweiterte var_dump Funktion mit formatierter Ausgabe
 * Durchlaeuft die Argumente (Arrays/Objects) rekursiv und gibt eine formatierte Liste aus.
 * Optional werden Funktionsnamen von Objekten ausgegeben.
 * Es zeigt alle Variablen an, die es finden kann.
 * @method static
 *
 * @param mixed $data Variable jeden Datentyps
 * @param boolean $functions Zeige Funktionsnamen der Objekte (Standard = 0)
 * @return string
 */
function pray(mixed $data, bool $functions = false): string
{
    $result = '';

    if (isset ($data)) {
        if ((is_array($data) && count($data)) || (is_object($data) && !isEmptyObject($data))) {
            $result .= "<OL>\n";
            foreach ($data as $key => $value) {
                // while (list ($key, $value) = each ($data)) {
                $type = gettype($value);

                if ($type === "array" || $type === "object") {
                    $result .= sprintf("<li>(%s) <b>%s</b>:\n", $type, $key);

                    if (strtolower($key) !== 'owner' and (strtolower($key) !== 'weblication')
                        && strtolower($key) !== 'parent' and strtolower($key) !== 'backtrace') { // prevent recursion
                        $result .= pray($value, $functions);
                    } else {
                        $result .= 'no follow, infinite loop';
                    }
                } elseif (stripos($type, 'function') !== false) {
                    if ($functions) {
                        /** @noinspection PrintfScanfArgumentsInspection */
                        $result .= sprintf("<li>(%s) <b>%s</b> </LI>\n", $type, $key, $value);
                        // There doesn't seem to be anything traversable inside functions.
                    }
                } else {
                    $result .= sprintf("<li>(%s) <b>%s</b> = %s</LI>\n", $type, $key, $value);
                }
                unset($key, $value);
            }
            $result .= "</OL>end.\n";
        } else {
            $result .= "(empty)";
        }
    }

    return $result;
}

/**
 * Formats the given number of bytes into a human-readable size.
 *
 * @param int $bytes The number of bytes.
 * @param bool $shortVersion Determines whether to use the short version for units (e.g. KB, MB) or the full version (e.g. KBytes, MBytes). Defaults to false.
 * @param int $decimals The number of decimal places to round the size to. Defaults to 2.
 * @param string $blank The string to use as a separator between the size and the unit. Defaults to a space character.
 * @return string The formatted size in human-readable format.
 */
function formatBytes(int $bytes, bool $shortVersion = false, int $decimals = 2, string $blank = ' '): string
{
    $units = [['B', 'Bytes'], ['KB', 'KBytes'], ['MB', 'MBytes'], ['GB', 'GBytes'], ['TB', 'TBytes']];
    $level = min((int)log($bytes, 1024), count($units) - 1);
    $unit = $blank.$units[$level][(int)!$shortVersion];
    return number_format($bytes / pow(1024, $level), $decimals, ',', '.').$unit;
}

/**
 * Abbreviates a large number for display.
 * If the number is less than 1000, it is returned as is.
 * If the number is between 1000 and 999999, it is divided by 1000 and suffixed with "K".
 * If the number is between 1000000 and 999999999, it is divided by 1000000 and suffixed with "M".
 * If the number is between 1000000000 and 999999999999, it is divided by 1000000000 and suffixed with "B".
 * If the number is between 1000000000000 and 999999999999999, it is divided by 1000000000000 and suffixed with "T".
 * If the number is greater than 999999999999999, it is divided by 1000000000000000 and suffixed with "Q".
 *
 * @param int $number - The number to abbreviate.
 * @param int $decimals - The number of decimals to round the result to. Default is 2.
 * @param string $decimal_separator - The symbol used as the decimal separator. Default is ','.
 * @param string $thousands_separator - The symbol used as the thousands separator. Default is '.'.
 * @param string $blank - The string to insert between the abbreviated number and the suffix. Default is an empty string.
 * @return string - The abbreviated number.
 */
function abbreviateNumber(int $number, int $decimals = 2, string $decimal_separator = ',', string $thousands_separator = '.', string $blank = ''): string
{
    $units = ['', 'K', 'M', 'B', 'T', 'Q'];
    $level = min((int)log($number, 1000), count($units) - 1);
    $unit = $level ? "$blank$units[$level]" : '';
    return number_format($number / pow(1000, $level), $decimals, $decimal_separator, $thousands_separator).$unit;
}


/**
 * @param $val
 * @return int
 */
function returnBytes($val): int
{
    if (empty($val)) return 0;

    $val = trim($val);

    preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

    $last = '';
    if (isset($matches[2])) {
        $last = $matches[2];
    }

    if (isset($matches[1])) {
        $val = (int)$matches[1];
    }

    switch (strtolower($last)) {
        case 't':
        case 'tb':
        case 'tib':
            $val *= 1024;
        case 'g':
        case 'gb':
        case 'gib':
            $val *= 1024;
        case 'm':
        case 'mb':
        case 'mib':
            $val *= 1024;
        case 'k':
        case 'kb':
        case 'kib':
            $val *= 1024;
    }

    return (int)$val;
}

/**
 * Errechnet eine Terminserie (beruecksichtigt Sommer- & Winterzeit)
 *
 * @param int $from Startzeitpunkt
 * @param int|null $to Endzeitpunkt
 * @param int $intervall Intervall in Tage (Standard 7 fuer eine Woche)
 * @param int $step Schritte (schrittweise) bedeutet zu jedem $step 'ten Intervall. Z.B. bei 2 wird jeder 2. Termin uebersprungen (bzw. 2-Wochen-Intervall bei 7 Tagen erreicht)
 * @return array
 */
function getSeriesOfAppointments(int $from, ?int $to, int $intervall = 7, int $step = 1): array
{
    $dates = [];

    if (empty($to)) {
        return [0 => ['timestamp' => $from, 'date' => date('d.m.Y', $from), 'step' => $step]];
    }

    if ($intervall > 0 and $step > 0 and $to >= $from) {
        $secDay = 86400; // Sekunden eines Tages
        $rhythmus = $intervall * $secDay;

        $diff = ($to + ($secDay - 1)) - $from;
        $numAppointments = ceil(($diff / $rhythmus) / $step);
        if (@constant('DEBUG')) echo 'Anzahl generierter Termine: '.$numAppointments."\n";
        for ($i = 0; $i < $numAppointments; $i++) {
            $new_date = [];
            $time = ($from + ($i * $step * $rhythmus));
            if (date('I', $from) < date('I', $time)) {
                $time -= 3600;
            } elseif (date('I', $from) > date('I', $time)) {
                $time += 3600;
            }
            $new_date['timestamp'] = $time;
            $new_date['date'] = date('d.m.Y', $time);
            $new_date['step'] = $step;
            if ($time <= $to) {
                $dates[] = $new_date;
            }
            unset($new_date);
        }
    }

    return $dates;
}

/**
 * Gibt true zurueck fuer ein Schaltjahr, andernfalls false
 *
 * @param string $year Jahr im Format CCYY
 * @return boolean true/false
 */
function isLeapYear(string $year = ''): bool
{
    if (empty($year)) {
        $year = date('Y');
    }

    if (preg_match('/\D/', $year)) {
        return false;
    }

    if ($year < 1000) {
        return false;
    }

    if ($year < 1582) {
        // vor Gregorio XIII - 1582
        return ($year % 4 == 0);
    } else {
        // nach Gregorio XIII - 1582
        return ((($year % 4 == 0) and ($year % 100 != 0)) or ($year % 400 == 0));
    }
} // end func isLeapYear

if (!function_exists('addEndingSlash')) {
    /**
     * Adds a possibly missing ending slash to a string. Empty strings are not changed.
     *
     * @param string $value Wert (Ordner, Verzeichnis)
     * @return string String with ending slash.
     **/
    function addEndingSlash(string $value): string
    {
        if ($value != '') {
            if ($value[-1] != '/' && $value[-1] != '\\') {
                $value .= '/';
            }
        }

        return $value;
    }
}

if (!function_exists('removeEndingSlash')) {
    /**
     * Entfernt endenden Slash im String
     *
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeEndingSlash(string $value): string
    {
        if ($value !== '') {
            if ($value[-1] === '/' || $value[-1] === '\\') {
                $value = substr($value, 0, -1);
            }
        }

        return $value;
    }
}

if (!function_exists('removeBeginningSlash')) {
    /**
     * Entfernt einen evtl. vorhandenen Slash am Anfang des uebergebenen String
     *
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeBeginningSlash(string $value): string
    {
        if ($value !== '') {
            if ($value[0] === '/' || $value[0] === '\\') {
                $value = substr($value, 1);
            }
        }

        return $value;
    }
}

/**
 * Creates directories recursively e.g. /var/log/prog/main/ups.log if prog and main don't exist, they are created.
 *
 * @param string $strPath
 * @param integer $mode
 * @return boolean success
 */
function mkdirs(string $strPath, int $mode = 0777): bool
{
    if (@is_dir($strPath)) {
        return true;
    }

    $pStrPath = dirname($strPath);
    if (!mkdirs($pStrPath, $mode)) {
        return false;
    }

    return @mkdir($strPath, $mode);
}

/**
 * hex_encode()
 * Maskiert z.B. URIs:
 * Die Maskierung besteht darin, ein Prozentzeichen % zu notieren, gefolgt von dem hexadezimal ausgedrueckten Zeichenwert des gewuenschten Zeichens.
 *
 * @param string $text Beliebiger Text, URI, E-Mail, etc.
 * @return string maskierter / codierter Text
 * @link http://selfhtml.teamone.de/html/verweise/email.htm
 **/
function hex_encode(string $text): string
{
    $encoded = '';
    if (strlen($text) > 0) {
        $encoded = bin2hex((string)$text);
        $encoded = chunk_split($encoded, 2, '%');
        $encoded = '%'.substr($encoded, 0, strlen($encoded) - 1);
    }

    return $encoded;
}

/**
 * Gibt einen klickbaren JavaScript HEX kodierten E-Mail Link zurueck.
 * Vor allem gegen Spam Bots interessant!
 *
 * @param string $email E-Mail Adresse
 * @return string JavaScript E-Mail Link
 **/
function getJSEMailLink(string $email, $caption = null): string
{
    if (strpos($email, '@') === false) {
        return '';
    }
    $email = explode('@', $email);
    $en_caption = hex_encode($email[0]);
    $en_at = hex_encode('@');
    $en_ext = hex_encode($email[1]);
    $js = '<script type="text/javascript">
				<!--
					var caption = "'.$en_caption.'";
					var at = "'.$en_at.'";
					var ext = "'.$en_ext.'";';

    $js .= 'document.write(\'<a href="mailto:\' + caption + at + ext + \'">\');';
    if ($caption) {
        $js .= '	document.write(\''.$caption.'\');';
    } else {
        $js .= '	document.write(urlDecode("'.$en_caption.'") + urlDecode("'.$en_at.'") + urlDecode("'.$en_ext.'"));';
    }
    $js .= '	document.write(\'</a>\');';

    $js .= '
				//-->
				</script>';

    return $js;
}

/**
 * Deletes a directory recursively
 *
 * @param string $dir directory to delete
 * @param boolean $rmSelf removes the directory itself if true
 * @return boolean success
 */
function deleteDir(string $dir, bool $rmSelf = true): bool
{
    if (!is_dir($dir)) {
        return false;
    }

    $dir = addEndingSlash($dir);
    $handle = opendir($dir);

    if ($handle === false) {
        return false;
    }

    while (false !== ($readdir = readdir($handle))) {
        if ($readdir !== '..' && $readdir !== '.') {
            $resource = $dir.$readdir;
            if (is_file($resource)) {
                if (!unlink($resource)) {
                    closedir($handle);
                    return false;
                }
            } elseif (is_dir($resource)) {
                // Calls itself to clear subdirectories
                if (!deleteDir($resource)) {
                    closedir($handle);
                    return false;
                }
            }
        }
    }
    closedir($handle);

    if ($rmSelf) {
        if (!rmdir($dir)) {
            return false;
        }
    }

    return true;
}

/**
 * Determines the extension of the file without the dot
 *
 * @param string $file filename
 * @return string file extension
 **@see pathinfo() buildin function since PHP 4.0.3
 */
function file_extension(string $file = ""): string
{
    return substr($file, (strrpos($file, ".") ? strrpos($file, ".") + 1 : strlen($file)), strlen($file));
}

/**
 * removes the extension from the file name
 *
 * @param string $file filename
 * @return string filename without extension
 */
function remove_extension(string $file = ''): string
{
    return substr($file, 0, (strrpos($file, '.') ?: strlen($file)));
}

/**
 * Verkuerzt einen Text auf eine bestimme Laenge. Beim Abschneiden geht die Funktion jedoch bis zum letzten Leerzeichen zurueck, damit
 * er ein Wort nicht in der Mitte teilt.
 * Wenn der Text laenger ist als der Ausschnitt, kann mittels dem Parameter more == 1 die Zeichenfolge '...' angehaengt werden.
 *
 * @param string $str Text
 * @param integer $len Maximale Laenge
 * @param mixed $more Fuege '...' hinzu, falls der Text gekuerzt wurde
 * @param bool $backtrack True bedeutet, die Funktion schneidet nicht innerhalb eines Wortes durch, sondern liefert nur vollst�ndige W�rter
 * @return string gekuerzte Version
 **/
function shorten(string $str = '', int $len = 150, $more = 1, bool $backtrack = true): string
{
    if ($str == '') return $str;
    if (is_array($str)) return $str;
    $str = trim($str);

    // if it's les than the size given, then return it
    if (strlen($str) <= $len) return $str;

    // else get that size of text
    $encoding = @mb_detect_encoding($str);
    if ($encoding === false) {
        $str = substr($str, 0, $len);
        $encoding = 'ISO-8859-1';
    } else {
        $str = mb_substr($str, 0, $len, $encoding);
    }

    // backtrack to the end of a word
    if ($str != '') {
        // check to see if there are any spaces left
        if (!substr_count($str, ' ')) {
            if ($more) $str .= (($more === 1) ? '...' : $more);

            return $str;
        }

        // backtrack
        if ($backtrack) {
            while (strlen($str) && ($str[strlen($str) - 1] != ' ')) {
                $str = mb_substr($str, 0, -1, $encoding);
            }
        }
        $str = mb_substr($str, 0, -1, $encoding);
        if ($more) $str .= (($more == 1) ? '...' : $more);
    }

    return $str;
}

/**
 * Entfernt leere Zeilen. Z.B. $lines = array_filter($lines, 'removeEmptyLines');
 *
 * @access public
 * @param string $line Wert
 * @return boolean
 **/
function removeEmptyLines(string $line): bool
{
    return trim($line) !== '';
}

/**
 * formatDateTime()
 *
 * @param $datetime
 * @param $format
 * @return string
 */
function formatDateTime($datetime, $format): string
{
    if (!is_numeric($datetime)) {
        $timestamp = strtotime($datetime);
        if ($timestamp !== -1) $datetime = $timestamp;
    }
    return (new DateTime("@$datetime"))->format($format);
}

/**
 * formatDEDateToEN()
 * Arbeitet etwas anders als formatDateTime, da es deutsches Format (01.01.2004) in
 * englisches Format (2004-01-01) umwandelt.
 *
 * @param $strDate
 * @param string $delimiter
 * @return string
 * @throws Exception
 * @author Andreas Horvath
 * @deprecated
 */
function formatDEDateToEN($strDate, string $delimiter = '.'): string
{
    $arrDate = explode($delimiter, $strDate);
    return (new DateTime(strtotime($arrDate[2]."-".$arrDate[1]."-".$arrDate[0])))->format('Y-m-d');
}

/**
 * replaces the html tag <br> by a new line
 *
 * @param string $subject text
 * @return string replaced text
 **/
function br2nl(string $subject): string
{
    return preg_replace('=<br(>|([\s/][^>]*)>)\r?\n?=i', chr(10), $subject);
}

/**
 * replaces all linebreaks to <br />
 *
 * @param string $string
 * @return string|string[]
 * @deprecated use php nl2br
 */
function nl2br2(string $string): string
{
    return str_replace(["\r\n", "\r", "\n"], '<br>', $string);
}

/**
 * strips body from html page.
 * html, head and body tags will be dropped.
 *
 * @param string $file_content Datei
 * @return string Datei ohne Html, Head und Body Tags
 **/
function strip_body(string $file_content): string
{
    $body = '';
    if (preg_match('#<body[^>]*?>(.*?)</body>#si', $file_content, $matches)) {
        $body = $matches[1];
    }

    return $body;
}

/**
 * strips head from html page
 *
 * @param string $html
 * @return string
 */
function strip_head(string $html): string
{
    $head = '';
    if (preg_match('#<head[^>]*?>(.*?)</head>#si', $html, $matches)) {
        $head = $matches[1];
    }

    return $head;
}

/**
 * Liefert den verwendeten Browser des Clients
 *
 * @return array Browser und Version
 */
function getClientBrowser(): array
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (($pos = strpos($userAgent, 'MSIE')) !== false) {
        [$version] = sscanf(substr($userAgent, $pos), 'MSIE %f; ');
        $browser = 'IE';
    } elseif (strpos($userAgent, 'Opera')) {
        $browser = 'Opera';
    } elseif (strpos($userAgent, 'Mozilla/([0-9].[0-9]{1,2})')) {
        $browser = 'Mozilla';
    } else {
        $browser = 'Other';
    }

    return [
        'name' => $browser,
        'version' => $version,
    ];
}

/**
 * Liefert die IP des Clients (kann jedoch durch proxy oder anonymizer verfaelscht werden)
 *
 * @return string Remote/Client IP Adresse
 **/
function getClientIP(): string
{
    foreach (
        [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ] as $key
    ) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
    }
    return '';
}

/**
 * creates a browser fingerprint
 *
 * @param bool $withClientIP
 * @return string
 */
function getBrowserFingerprint(bool $withClientIP = true): string
{
    $data = ($withClientIP ? getClientIp() : '');
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['HTTP_ACCEPT'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    return md5($data);
}

/**
 * Holt sich den Inhalt von PHP Skripten und gibt ihn per return Wert zurueck.
 *
 * @param string $includeFile Absoluter Dateipfad
 * @return string
 */
function getContentFromInclude(string $includeFile): string
{
    ob_start();
    include($includeFile);
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

/**
 * Wandelt ein Array in das HTML Attribute Format um: name="Manhart" vorname="Alexander"
 *
 * @param array $array Array
 * @return string
 */
function arrayToAttr(array $array): string
{
    $strHtmlTagAttr = '';
    foreach ($array as $key => $value) {
        if ($strHtmlTagAttr != '') $strHtmlTagAttr .= ' ';
        $strHtmlTagAttr .= $key.'="'.$value.'"';
    }

    return $strHtmlTagAttr;
}

/**
 * Gibt 0 zurueck (ganz nuetzlich im Zusammenhang mit Array Initialisierung array_map('zero', $arr)).
 *
 * @return int 0
 */
function zero(): int
{
    return 0;
}

/**
 * Gibt einen Leerstring zurueck (ganz nuetzlich im Zusammenhang mit Array Initialisierung array_map('emptyString', $arr)).
 *
 * @return string ''
 */
function emptyString(): string
{
    return '';
}

function identity(mixed $var): mixed
{
    return $var;
}

/**
 * Simple Filename Sanitizer
 * strtolower() guarantees the filename is lowercase (since case does not matter inside the URL, but in the NTFS filename)
 * [^a-z0-9]+ will ensure, the filename only keeps letters and numbers
 * Substitute invalid characters with '-' keeps the filename readable
 *
 * @see https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
 * @param string $filename $file without path
 * @param bool $lowerCase
 * @return string
 */
function sanitizeFilename(string $filename, bool $lowerCase = true): string
{
    $filename = umlauts($filename);
    $pattern = '/[^a-z0-9\-\. _]+/';

    // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
    if ($lowerCase) {
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
    } else {
        $pattern = '/[^a-zA-Z0-9\-\. _]+/';
    }
    $filename = preg_replace($pattern, '-', $filename);
    $filename = preg_replace(
        [
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file___name.zip" becomes "file-name.zip"
            '/_+/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/',
        ],
        '-',
        $filename,
    );
    $filename = preg_replace([
        // "file--.--.-.--name.zip" becomes "file.name.zip"
        '/-*\.-*/',
        // "file...name..zip" becomes "file.name.zip"
        '/\.{2,}/',
    ], '.', $filename);
    return trim($filename, '.-');
}

/**
 * normalize Database Column ident (remove umlauts, special characters and whitespaces)
 *
 * @param string $ident
 * @return string
 */
function sanitizeIdent(string $ident): string
{
    return preg_replace('/[^A-Za-z0-9\-_]/', '', umlauts($ident));
}

/**
 * Convert boolean expression to integer (0, 1)
 *
 * @param bool $bool boolean value
 * @return int 0 or 1
 */
function bool2int(bool $bool): int
{
    return $bool ? 1 : 0; # other: (int)$bool;
}

/**
 * Convert boolean expression to string ("true" or "false")
 *
 * @param bool $bool boolean value
 * @return string 'false' or 'true'
 */
function bool2string(bool $bool): string
{
    return $bool ? 'true' : 'false';
}

/**
 * Umwandlung string Ausdruck ('true', 'false') in booleschen Ausdruck
 *
 * @param string|null $string $string Boolean als String
 * @return bool booleschen Ausdruck
 */
function string2bool(?string $string): bool
{
    return ($string === '1' or $string === 'true');
}

/**
 * checks if the object is empty.
 *
 * @param $obj
 * @return bool
 */
function isEmptyObject($obj): bool
{
    foreach ($obj as $prop) {
        return false;
    }

    return true;
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht leer ist z.B. $arr = array_filter($arr, 'isNotEmpty').
 *
 * @param mixed $var
 * @return bool
 */
function isNotEmpty($var): bool
{
    return !empty($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht NULL ist.
 *
 * @param mixed $var
 * @return bool
 */
function isNotNull($var): bool
{
    return !is_null($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable kein leerer String ist.
 *
 * @param mixed $var
 * @return bool
 */
function isNotEmptyString($var): bool
{
    return !($var === '');
}

/**
 * returns true if value is empty
 *
 * @param $value
 * @return bool
 */
function isEmptyString($value): bool
{
    return ($value === '');
}

/**
 * Druckt Dateien aus
 *
 * @param string $printer Druckername
 * @param array $files Dateien (z.B. PDF Dokumente)
 */
function printFiles(string $printer, array $files): bool
{
    $files = array_map('escapeshellarg', $files);
    $command = 'lp -d '.$printer.' '.implode(' ', $files);
    exec($command, $output, $return_value);

    return ($return_value == 0);
}

/**
 * ueberprueft, ob die Datei lokal existiert (das PHP file_exists erkennt neu angelegte Dateien auf der Shell/NFS Laufwerke nicht)
 *
 * @param string $file
 * @param string $remote z.B. rsh root@blub.de
 * @return boolean
 */
function shellFileExists(string $file, string $remote = ''): bool
{
    $cmd = 'test -e '.$file.' && echo 1 || echo 0';
    if ($remote != '') $cmd = $remote.' "'.$cmd.'"';
    exec($cmd, $arrOutFileExists);
    if (!isset($arrOutFileExists[0]))
        return false;
    return trim($arrOutFileExists[0]) === '1';
}

/**
 * Erstellt Suchmuster fuer SQL-Statement. Sehr hilfreich, wenn man Textfeldsuche benoetigt.
 * Z.B. Eingabe von A listet alle mit A beginnenden Treffer auf. Eingabe > Parameter "$min" listet
 * alle Treffer, die die Eingabe enthalten auf.
 *
 * @param string $wert
 * @param int $min
 * @return string
 */
function getSearchPattern4SQL($wert, $min = 2): string
{
    $len_wert = strlen($wert);

    if ($len_wert > 0) {
        $pattern = $wert.'%';
        if ($len_wert > $min) {
            $pattern = '%'.$pattern;
        }

        return $pattern;
    }

    return '';
}

/**
 * Setzt eine globale Variable
 *
 * @param string $key
 * @param mixed $value
 */
function setGlobal(string $key, &$value)
{
    $GLOBALS[$key] = &$value;
}

/**
 * Liefert Wert einer globalen Variable
 *
 * @param string $key
 * @return mixed
 */
function &getGlobal(string $key)
{
    return $GLOBALS[$key];
}

/**
 * Abfrage ob die globale Variable existiert
 *
 * @param string $key
 * @return bool
 */
function global_exists(string $key): bool
{
    return isset($GLOBALS[$key]);
}

/**
 * Magische PHP Konstanten in ein Array zusammenfuehren
 *
 * @param mixed $file __FILE__
 * @param mixed $line __LINE__
 * @param mixed $function __FUNCTION__ ab PHP 4.3
 * @param mixed $class __CLASS__ ab PHP 4.3
 * @param mixed $method erst ab PHP 5
 * @return array
 */
function magicInfo($file, $line, $function, $class, $specific = []): array
{
    if (!is_array($specific)) {
        if (!is_null($specific)) {
            $specific = [$specific];
        } else $specific = [];
    }

    return array_merge([
        'file' => $file,
        'line' => $line,
        'function' => $function,
        'class' => $class,
    ], $specific);
}

if (!function_exists('mime_content_type')) {
    /**
     * Ermittelt den Mime Content Type fuer eine Datei
     *
     * @param string $f Datei
     * @return string
     */
    function mime_content_type(string $f): string
    {
        return trim(exec('file -bi '.escapeshellarg($f)));
    }
}

/**
 * Silbentrennung
 *
 * @param string $word Zu trennendes Wort
 * @return array Moegliche Trennpositionen
 */
function hyphenation(string $word): array
{
    $hyphenationPositions = [];

    $wordLen = strlen($word);
    if ($wordLen > 2) {
        $allowHyphenation = false;
        $vowels = ['a', 'e', 'i', 'o', 'u', 'ä', 'ü', 'ö'];
        /*
			-- "sch" wie in "A_sche"
			-- "ch" wie in "Untersu_chen"
			-- "ph" wie in "Ste_phan"
			-- "ck" wie in "Zu_cker"
			-- "pf" wie "A_pfel"
			-- "br" wie in "Unter_brechung"
			-- "pl" wie "Finanz_plan"
			-- "tr" wie in "An_trag"
			-- "st" wie in "Auf_stehen"
			-- "gr" wie in "Hinter_grund"
			*/
        $splices = ['sch', 'ch', 'ph', 'ck', 'pf', 'br', 'pl', 'tr', 'st', 'gr'];
        $divider = ['-', '/', '\\', '*', '#', ';', '.', '+', '=', ')', '(', '&', '!', '?', '<', '>', ':', ' ', '_', '~'];

        for ($i = 2; $i < $wordLen - 1; $i++) {
            $c0 = $word[$i - 1];
            if (!$allowHyphenation and in_array($c0, $vowels)) {
                $allowHyphenation = true;
            }
            if ($allowHyphenation) {
                $c = $word[$i];
                $c1 = $word[$i + 1];
                $v = $c0.$c;
                if ($v == 'ch' and $i > 2 and $word[$i - 2] == 's') {
                    $v = 'sch';
                }
                if (in_array($c1, $vowels) and !in_array($c, $vowels) and
                    !in_array($c, $divider) and !in_array($c0, $divider)) {
                    if (in_array($v, $splices)) {
                        $hyphenationPositions[] = ($i - strlen($v) + 1);
                    } else {
                        $hyphenationPositions[] = $i;
                    }
                }
            }
        }
    }

    return $hyphenationPositions;
}

/**
 * HTTP Protocol defined status codes
 *
 * @param int $num
 */
function HTTPStatus(int $num)
{
    static $http = [
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out",
    ];

    header($http[$num]);
}

/**
 * Moves a file
 *
 * @param string $source Source file
 * @param string $dest Target file
 * @return bool
 */
function move_file(string $source, string $dest): bool
{
    if (!file_exists($source)) return false;
    if (!is_dir(dirname($dest))) return false; // OR -> mkdirs(dirname($dest));
    $res_copy = copy($source, $dest);
    if ($res_copy) $res_unlink = unlink($source);

    return ($res_copy and $res_unlink);
}

/**
 * Read out directory: creates file list
 *
 * @param string $path root directory
 * @param boolean $absolute return file with (absolute) full path otherwise only with relative $subDir/$filename
 * @param string $filePattern filter files with regEx pattern
 * @param string $subDir [optional] read this subdirectory
 * @return array file list
 * @see glob()
 */
function readFiles(string $path, bool $absolute = true, string $filePattern = '/.JPG/i', string $subDir = ''): array
{
    $files = [];
    $path = addEndingSlash($path).($subDir = addEndingSlash($subDir));
    if ($handle = opendir($path)) {
        while (false !== ($fileName = readdir($handle))) {
            $file = $path.$fileName;
            if (is_file($file) and preg_match($filePattern, $fileName)) {
                $files[] = ($absolute) ? $file : $subDir.$fileName;
            }
        }
        closedir($handle);
    }
    return $files;
}

/**
 * Gets the reference to a value in a nested array by its key. If a Branch doesn't exist it gets created
 *
 * @param array $arr the nested array
 * @param array $keys a String of keys sep. All keys are treated as strings
 * @return mixed a reference to the value if there was no such branch it will be set to null
 * @throws Exception
 */
function &getNestedArrayValueReference(array &$arr, array $keys): mixed
{
    //go through each key in the access string
    foreach ($keys as $key) {
        if ($arr != null && !is_array($arr)) {
            $subtree = implode('.', $keys);
            assert(isset($lastKey));
            throw new Exception("Tried to access subtree of value: '{$arr}' accessing: $subtree @$lastKey");
        }
        $lastKey = $key;
        //expand branch if necessary this will crate an array if necessary
        $arr[$key] ??= null;
        //and get the reference to the next level
        $arr = &$arr[$key];
    }
    //return the reference
    return $arr;
}

/** Build a path by concatenating parts and adding '/' between them and adding an ending slash
 *
 * @param string ...$elements
 * @return string The assembled path with an ending slash
 */
function buildDirPath(...$elements): string
{
    $result = '';
    foreach ($elements as $element) {
        $result .= addEndingSlash($element);
    }
    return $result;
}

/** Build a path by concatenating parts and adding '/' between them
 *
 * @param string ...$elements
 * @return string The assembled path without an ending slash
 */
function buildFilePath(...$elements): string
{
    return removeEndingSlash(buildDirPath(...$elements));
}

/**Normalizes a path resolving steps up to containing directory's and cleaning out repeated Separators<br>
 * beginning and ending Separators will be preserved
 *
 * @param string $path the path to be normalized
 * @param bool $noFailOnRoot Drop attempts to step out of root in absolute paths instead of failing
 * @param string $separator directory separator defaults to /
 * @return false|string the normalized path or false on failure.
 */
function normalizePath(string $path, bool $noFailOnRoot = false, string $separator = '/'): bool|string
{
    $bufferOutput = [];
    $stepsOut = 0;

    $bufferInput = explode($separator, $path);
    //jump to last position
    end($bufferInput);
    //loop backwards through the parts of the path
    while (false !== ($part = current($bufferInput))) {
        //ignore self-references and separator errors
        if ($part === '' || $part === '.') /** @noinspection SuspiciousSemicolonInspection */ ;
        //normal element -> add to buffer
        elseif ($part !== '..') {
            if ($stepsOut > 0)//element was stepped out of again later in the Path
                $stepsOut--;
            else            //append on the beginning of the buffer
                array_unshift($bufferOutput, $part);
        } else //go up
            $stepsOut++;
        //set pointer to previous element of the Array
        prev($bufferInput);
    }

    $normalizedPath = implode($separator, $bufferOutput);
    //re-add the original paths beginning slash or any necessary steps out
    if ($prefix = isAbsolutePath($path)) {
        //absolute path -> check for illegal steps out of root
        if ($stepsOut && !$noFailOnRoot)
            return false;//fail
    } else {
        //relative path
        $prefix = str_repeat('..'.$separator, $stepsOut);
    }
    $normalizedPath = $prefix.$normalizedPath;
    //re-add the original paths ending directory slash
    if (str_ends_with($path, $separator))
        $normalizedPath .= $separator;
    return $normalizedPath;
}

/**Calculates a vector between two paths useful to turn an absolute path into a path relative to the script being served
 *
 * @param string|null $here the starting path if null will use the directory of $_SERVER['SCRIPT_FILENAME']
 * @param string $toThis the path to point to
 * @param bool $normalize normalize absolute paths before calculation
 * @param string|null $base an optional path to base relative paths on defaults to the directory of $_SERVER['SCRIPT_FILENAME']
 * @param string $separator directory separator defaults to /
 * @return string|false the calculated vector or false if normalization fails
 */
function makeRelativePathFrom(?string $here, string $toThis, bool $normalize = false, string $base = null, string $separator = '/'): bool|string
{
    $browserPath = dirname($_SERVER['SCRIPT_FILENAME']);
    $base ??= $browserPath;
    $here ??= $browserPath;
    //base relative paths and normalize
    if (!isAbsolutePath($here)) {
        $here = addEndingSlash($base).$here;
        $here = normalizePath($here, separator: $separator);
    } elseif ($normalize)
        $here = normalizePath($here, separator: $separator);
    if (!isAbsolutePath($toThis)) {
        $toThis = addEndingSlash($base).$toThis;
        $toThis = normalizePath($toThis, separator: $separator);
    } elseif ($normalize) {
        $toThis = normalizePath($toThis, separator: $separator);
    }
    if (!($here && $toThis))//normalization returned an invalid result
        return false;//fail

    //beginn
    $hereArr = explode($separator, removeEndingSlash($here));
    $toThisArr = explode($separator, removeEndingSlash($toThis));
    $hereCount = count($hereArr);
    //cut out the common part
    $tripped = 0;
    $vectorArr = array_udiff_assoc($toThisArr, $hereArr, function ($a, $b) use (&$tripped) { return $tripped != 0 ? $tripped : $tripped = strcmp($a, $b); },
    );
    //calculate the size of the common part not included in target
    $commonCount = count($toThisArr) - count($vectorArr);
    //get from here to the common base
    $stepsOut = $hereCount - $commonCount;
    $stepOutString = str_repeat('..'.$separator, $stepsOut);
    $stepOutString = removeEndingSlash($stepOutString);
    //build path
    array_unshift($vectorArr, $stepOutString);
    $isDirectory = str_ends_with($toThis, $separator);
    return ($isDirectory ? buildDirPath(...$vectorArr) : buildFilePath(...$vectorArr));
}

/**
 * Calculates the relative paths from the source path to the target path, both serverside and clientside. It is faster than makeRelativePathFrom.
 *
 * @param string|null $here the starting path if null will use the directory of $_SERVER['SCRIPT_FILENAME']
 * @param string $toThis The absolute target path.
 * @param string|null $base The document root to use (optional, defaults to $_SERVER['DOCUMENT_ROOT']).
 * @param string $separator The directory separator to use (optional, defaults to DIRECTORY_SEPARATOR).
 * @return array An array containing the serverside and clientside relative paths.
 */
function makeRelativePathsFrom(?string $here, string $toThis, string $base = null, string $separator = DIRECTORY_SEPARATOR): array
{
    $base ??= $_SERVER['DOCUMENT_ROOT'];
    $here ??= dirname($_SERVER['SCRIPT_FILENAME']);

    // Resolve symbolic links and remove trailing slashes
    $base = rtrim($base, $separator);
    $base = realpath($base) ?: $base;
    $here = rtrim($here, $separator);
    $here = realpath($here) ?: $here;
    $toThis = rtrim($toThis, $separator);
    $toThis = realpath($toThis) ?: $toThis;

    $paths = [];
    $paths['clientside'] = [
        'here' => $here,
        'toThis' => $toThis,
    ];

    // loop through the stacked paths and calculate the common path components
    foreach ($paths as $side => $from_to) {
        // Split the paths into arrays of their individual components.
        $paths[$side]['hereParts'] = explode($separator, $from_to['here']);
        $paths[$side]['toThisParts'] = explode($separator, $from_to['toThis']);

        // Find the number of common path components.
        $commonPathComponents = 0;
        while (isset($paths[$side]['hereParts'][$commonPathComponents], $paths[$side]['toThisParts'][$commonPathComponents]) &&
            $paths[$side]['hereParts'][$commonPathComponents] === $paths[$side]['toThisParts'][$commonPathComponents]) {
            $commonPathComponents++;
        }
        $paths[$side]['commonPathComponents'] = $commonPathComponents;
    }

    // Calculate the clientside relative path.
    $clientsideRelativePathComponents = count(explode($separator, $base));
    $commonPathComponents = min($clientsideRelativePathComponents, $paths['clientside']['commonPathComponents']);
    $amount = $clientsideRelativePathComponents - $paths['clientside']['commonPathComponents'];
    $levels = $amount > 0 ? str_repeat('..'.$separator, $amount) : '/';
    $clientsideRelativePath = $levels.implode($separator, array_slice($paths['clientside']['toThisParts'], $commonPathComponents));

    // Calculate the serverside relative path.
    if (!array_key_exists('serverside', $paths)) {
        $paths['serverside'] = $paths['clientside'];
    }
    $serversideRelativePath = str_repeat("..$separator", count($paths['serverside']['hereParts']) - $paths['serverside']['commonPathComponents']).
        implode($separator, array_slice($paths['serverside']['toThisParts'], $paths['serverside']['commonPathComponents']));

    // Return the relative paths.
    return [
        'serverside' => $serversideRelativePath,
        'clientside' => $clientsideRelativePath,
    ];
}

/**
 * Resolves symbolic links into real path.
 *
 * @param string $path
 * @return string
 */
function getRealFile(string $path): string
{
    return is_link($path) ? realpath($path) : $path;
}

/**Determine if a path is absolute
 *
 * @param string $path the path to check
 * @return string|false the absolute prefix e.g. '/' or 'C:\' or 'https://
 */
function isAbsolutePath(string $path): bool|string
{
    $regex = "/^((\w*:)?[\/\\\\]{1,2})/";
    //grep a potential match
    return preg_match($regex, $path, $matches) ? $matches[1] : false;
}

/**
 * Liest ein Verzeichnis rekursiv aus. Dabei kann man per regulärem Ausdruck auf Datei- oder Verzeichnisebene filtern. Die Ergebnisse werden absolut oder relativ zum
 * übergebenen Pfad zurück gegeben.
 *
 * @param string $path Stammpfad
 * @param bool $absolute Datei mit absolutem Pfad
 * @param string $filePattern Dateifilter
 * @param string $dirPattern Verzeichnisfilter
 * @param string $subdir auszulesendes Unterverzeichnis
 * @param Closure|null $callback
 * @return array
 * @throws Exception
 */
function readFilesRecursive(string $path, bool $absolute = true, string $filePattern = '', string $dirPattern = '/^[^\.].*$/', string $subdir = '', Closure $callback = null): array
{
    $files = [];

    $root = $path;
    $path = addEndingSlash($path).addEndingSlash($subdir);
    $res = @opendir($path);
    if (!$res) {
        throw new Exception('Pfad '.$path.' existiert nicht oder ist kein Verzeichnis oder hat keine Zugriffsberechtigung!');
    }
    while (($filename = readdir($res)) !== false) {
        $file = $path.$filename;
        $fileRelative = addEndingSlash($subdir).$filename;

        $filetyp = filetype($file);
        switch ($filetyp) {
            case 'dir':
                $dir = $filename;
                if ($dirPattern) {
                    if (!preg_match($dirPattern, $dir)) {
                        continue 2;
                    }
                }
                $subdirectory = $fileRelative;
                $files = array_merge(readFilesRecursive($root, $absolute, $filePattern, $dirPattern, $subdirectory, $callback), $files);
                // Doppelte gleichnamige Dateien gibt es nicht. Aber aufgrund der Callbackfunktion implementiert (u.a. basename):
                // $files = array_unique($files, SORT_STRING);
                break;

            case 'file':
                if ($filePattern) {
                    if (!preg_match($filePattern, $filename)) {
                        continue 2;
                    }
                }
                $file = ($absolute) ? $file : $fileRelative;
                if ($callback != null) {
                    $file = call_user_func($callback, $file);
                }
                if ($file != '') {
                    $files[] = $file;
                }
                break;
        }
    }
    closedir($res);

    return $files;
}

/**
 * @param string $path
 * @return array|false
 */
function readDirs(string $path): false|array
{
    return glob(addEndingSlash($path).'*', GLOB_ONLYDIR);
}

/**
 * Checks the Error code of the PCRE (RegEx engine) and logs any Errors
 *
 * @param string $regEX the executed expression for logging
 * @param string $content the content that was processed
 * @return bool Outcome is ok
 */
function checkRegExOutcome(string $regEX, string $content): bool
{
    if (($lastErrorCode = preg_last_error()) === PREG_NO_ERROR) {
        return true;
    }
    $errormessage = preg_last_error_message($lastErrorCode);
    $errormessage = "RegularExpression $regEX failed with error code $lastErrorCode :$errormessage";
    $detailsFile = '';
    try {
        $detailsFile = Log::makeDetailsFile(
            "$errormessage\nParsed string:\n$content",
        );
    } catch (Exception) {
    }
    if (!empty($detailsFile))
        $errormessage .= ' Details have been saved to: '.$detailsFile;
    error_log($errormessage);
    return false;
}

/**
 * Produces a key-value array of variables given as parameters
 *
 * @param Closure $closure fn()=>[varX.varY...]
 * @param mixed ...$namedValues optional named Arguments that get merged with the variables
 * @return array The resulting array keyed by the variable/parameter names
 * @throws ReflectionException
 */
function packArray(Closure $closure, ...$namedValues): array
{
    return array_merge(
        (new ReflectionFunction($closure))->getClosureUsedVariables(),
        $namedValues,
    );
}

/**
 * Sortiert mehrere oder multidimensionale Arrays
 *
 * @param array $hauptArray Zu sortierendes Array
 * @param string $columnName Spaltenname im multidimensionalen Array
 * @param int $sorttype PHP Konstante
 * @param int $sortorder PHP Konstante
 * @return array
 */
function multisort(array $hauptArray, string $columnName, int $sorttype = SORT_STRING, int $sortorder = SORT_ASC): array
{
    $sortarr = [];
    foreach ($hauptArray as $row) {
        $sortarr[] = $row[$columnName];
    }

    if ($sorttype == SORT_STRING) {
        $sortarr = array_map('strtolower', $sortarr);
    }
    array_multisort($sortarr, $sortorder, $sorttype, $hauptArray);

    return $hauptArray;
}

/**
 * Checks if it is an Ajax call (XmlHttpRequest)
 * More specifically, whether the $_SERVER['HTTP_X_REQUESTED_WITH'] variable is set to XMLHttpRequest.
 *
 * @return boolean
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

/**
 * Umrechnung DTP-Punkt in Millimeter (Desktop-Publishing Wobla);
 *
 * @link http://de.wikipedia.org/wiki/Pica_%28Typografie%29
 * @param float $pt
 * @return float
 */
function pt2mm(float $pt): float
{
    return $pt * 0.35277;
}

/**
 * Erzwingt einen Download im Browser
 *
 * @param string $file Datei (mit absolutem Pfad)
 * @param string $mimetype Mimetype z.b. application/octet-stream
 */
#[NoReturn]
function forceFileDownload(string $file, string $mimetype = ''): void
{
    if (empty($mimetype)) $mimetype = mime_content_type($file);
    if (empty($mimetype)) $mimetype = 'application/octet-stream';
    $filesize = filesize($file);

    // Start sending headers
    header('Pragma: public'); // required
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false); // required for certain browsers
    header('Content-Transfer-Encoding: binary');
    header('Content-Type: '.$mimetype);
    header('Content-Length: '.(string)$filesize);
    header('Content-Disposition: attachment; filename="'.basename($file).'";');

    readfile($file);
    exit;
}

/**
 * Laedt eine Datei aus dem Web auf den lokalen Rechner
 *
 * @param string $sourceFile Quelldatei aus dem Web/Intranet
 * @param string $destFile Lokale Zieldatei
 * @return int|false gibt die Anzahl geschriebener Bytes oder False zurueck
 */
function downloadFile(string $sourceFile, string $destFile)
{
    return file_put_contents($destFile, fopen($sourceFile, 'r'), LOCK_EX);
}

/**
 * Die Funktion prueft mit Shell-Kommandos, ob ein entferntes Verzeichnis gemountet ist.
 *
 * @param string $mountPoint (der exakte Mount-Point(so wie er in der /etc/fstab steht.))
 * @return int [0|1]
 */
function isMounted(string $mountPoint): int
{
    if (is_dir($mountPoint)) { # man kann nur in ein Verzeichnis rein-mounten
        $mountPoint = removeEndingSlash($mountPoint);
        $cmd = 'mount | grep "'.$mountPoint.'" | wc -l | tr -d " "';
        $isMounted = intval(shell_exec($cmd));

        return $isMounted;
    }

    return 0;
}

/**
 * Errechnet die beste lesbare Farbe auf beliebiger Hintergrundfarbe hexcolor.
 *
 * @param string $hexcolor Hintergrundfarbe
 * @param string $dark Dunkle Farbe
 * @param string $light Helle Farbe
 * @return string Helle oder dunkle Farbe
 */
function legibleColor(string $hexcolor, string $dark = '#000000', string $light = '#FFFFFF'): string
{
    return (hexdec($hexcolor) > 0xffffff / 2) ? $dark : $light;
}

/**
 * Erzeugt einen zuf�lligen Farbcode
 *
 * @return string
 */
function randColor(): string
{
    $red = dechex(mt_rand(0, 255));
    $green = dechex(mt_rand(0, 255));
    $blue = dechex(mt_rand(0, 255));

    $rgb = $red.$green.$blue;

    if ($red == $green && $green == $blue) $rgb = substr($rgb, 0, 3);

    return '#'.$rgb;
}

/**
 * Wandelt UTF-8 in RTF Text um
 *
 * @param string $utf8_text
 * @return string
 * @author Kyle Gibson
 * @see http://spin.atomicobject.com/2010/08/25/rendering-utf8-characters-in-rich-text-format-with-php/
 */
function utf8_to_rtf(string $utf8_text): string
{
    $utf8_patterns = [
        "[\xC2-\xDF][\x80-\xBF]",
        "[\xE0-\xEF][\x80-\xBF]{2}",
        "[\xF0-\xF4][\x80-\xBF]{3}",
    ];
    $new_str = $utf8_text;
    foreach ($utf8_patterns as $pattern) {
        $new_str = preg_replace("/($pattern)/e", "'\u'.hexdec(bin2hex(iconv('UTF-8', 'UTF-16BE', '$1'))).'?'", $new_str);
    }

    return $new_str;
}

/**
 * Fuehrt ein Kommando auf der Shell im Hintergrund aus und gibt die PID zurueck
 *
 * @param string $cmd
 * @param integer $priority
 * @return string
 */
function shell_exec_background(string $cmd, int $priority = 0): string
{
    if ($priority) {
        $PID = shell_exec('nohup nice -n '.$priority.' '.$cmd.' >/dev/null 2>&1 & echo $!');
    } else {
        $PID = shell_exec('nohup '.$cmd.' >/dev/null 2>&1 & echo $!');
    }

    return trim($PID);
}

/**
 * Prueft, ob der uebergebene Prozess (PID) noch laeuft
 *
 * @param int $PID
 * @return boolean
 */
function is_process_running(int $PID): bool
{
    exec('ps '.$PID, $state);

    return (count($state) >= 2);
}

/**
 * Berechne Alter
 *
 * @param string $from Datum im englischen Format
 * @param string $to Datum im englischen Format
 * @return int Alter
 */
function calcAge(string $from, string $to = 'now'): int
{
    // funktioniert leider erst ab PHP 5.3
    //    if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
    //        $age = date_diff(date_create($from), date_create($to))->y;
    //      funktioniert nicht mit Schaltjahren und to = 2020-02-29
    //    }
    //    else {
    if ($to == 'now') {
        $to = date('%Y-%m-%d');
    }

    $from = strtotime($from); // von (eventl. Geburtsdatum)
    $to = strtotime($to); // bis

    $age = (intval(date('Y', $to)) - intval(date('Y', $from)));
    $from_month = date('m', $from);
    $to_month = date('m', $to);
    if ($from_month > $to_month) {
        $age -= 1;
    } elseif ($from_month == $to_month) {
        if (date('d', $from) > date('d', $to)) {
            $age -= 1;
        }
    }

    return $age;
}

/**
 * Formatiere Minuten um als Stunde-Minuten Text
 *
 * @param int $min Minuten
 * @return string
 */
function formatStdMin(int $min): string
{
    $val = intval($min);

    return floor($val / 60).' Std. '.($val % 60).' Min.';
}

/**
 * Formatiere Minuten in 24h Format um
 *
 * @param mixed $min
 * @return string
 */
function format24h($min): string
{
    $val = intval($min);

    return str_pad((($val < 0) ? ceil($val / 60) : floor($val / 60)), 2, '0', STR_PAD_LEFT).':'.str_pad((($val % 60) * (($val < 0) ? -1 : 1)), 2, '0', STR_PAD_LEFT);
}

/**
 * @param string $code Passwort oder Coupon
 * @param string $pepper zusätzliche Verschlüsselung mit einem serverseitigen Schlüssel (= Pfeffer). Mit pepper ist der zurückgegebene Hash 108 Zeichen lang, ohne 60 Zeichen!
 * @param array $options individuelles Salt,
 * @return string Hash
 * @throws Exception, InvalidArgumentException
 * @deprecated
 */
function pool_hash_code(string $code, string $pepper = '', array $options = []): string
{
    if (!defined('CRYPT_BLOWFISH')) {
        throw new Exception('The CRYPT_BLOWFISH algorithm is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (empty($code)) {
        throw new InvalidArgumentException('Cannot hash an empty code.');
    }

    $length = 22;
    $binaryLength = ($length * 3 / 4 + 1);

    $options += [
        'salt' => substr(strtr(base64_encode(mcrypt_create_iv($binaryLength, MCRYPT_DEV_URANDOM)), '+', '.'), 0, $length),
        'cost' => 10,
    ];

    if (version_compare(PHP_VERSION, '5.3.7') >= 0) {
        $algorithm = '2y'; // BCrypt, mit korrigiertem Unicode Problem
    } else {
        $algorithm = '2a'; // BCrypt
    }

    $cryptParams = sprintf('$%s$%02d$%s', $algorithm, $options['cost'], $options['salt']);
    $hash = crypt($code, $cryptParams);

    if ($pepper) {
        $encryptedHash = encryptTwofish($hash, $pepper);
        $hash = base64_encode($encryptedHash);
    }

    return $hash;
}

/**
 * Prüft, ob das Password einem gegebenen Hashwert entspricht. Damit kann ein
 * vom Benutzer eingegebenes Passwort, mit dem in der Datenbank gespeicherten
 * Hashwert verglichen werden.
 *
 * @param string $code Zu prüfendes Passwort.
 * @param string $existingHash Gespeicherter Hashwert aus der Datenbank.
 * @param string $pepper Übergeben Sie denselben key, welcher zur Verschlüsselung des Hashwertes benutzt wurde, oder lassen Sie den Parameter weg wenn kein key angegeben wurde.
 * @return bool Gibt true zurück, wenn das Passwort mit dem Hashwert übereinstimmt, sonst false.
 * @throws Exception
 */
function pool_verify_hash($code, $existingHash, $pepper = '')
{
    if (!defined('CRYPT_BLOWFISH')) {
        throw new Exception('The CRYPT_BLOWFISH algorithm is required (PHP 5.3).');
    }

    if (empty($code)) {
        return false;
    }

    // Hashwert mit dem serverseitigem Key entschlüsseln
    if ($pepper != '') {
        $encryptedHash = base64_decode($existingHash);
        $existingHash = decryptTwofish($encryptedHash, $pepper);
    }

    // Die Parameter, die urspruenglich zum Erstellen von $existingHash verwendet wurden,
    // werden automatisch aus den ersten 29 Zeichen von $existingHash extrahiert.
    $newHash = crypt($code, $existingHash);

    return $newHash === $existingHash;
}

/**
 * Verschlüsselt Daten mit dem TWOFISH Algorithmus. Der IV Vektor wird
 * Bestandteil des resultierenden binären Strings.
 *
 * @param string $data Zu verschlüsselnde Daten. \0 Zeichen am Schluss gehen verloren.
 * @param string $key Mit diesem Schlüssel werden die Daten verschlüsselt.
 * @return string Gibt die verschlüsselten Daten in Form eines binären Strings zurück.
 * @throws Exception
 */
function encryptTwofish($data, $key)
{
    if (!defined('MCRYPT_DEV_URANDOM')) {
        throw new Exception('The MCRYPT_DEV_URANDOM source is required (PHP 5.3).');
    }
    if (!defined('MCRYPT_TWOFISH')) {
        throw new Exception('The MCRYPT_TWOFISH algorithm is required (PHP 5.3).');
    }

    // Der cbc mode ist dem ecb mode vorzuziehen
    $td = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_CBC, '');

    // Twofish akzeptiert einen Schlüssel von 32 Bytes. Da in der Regel längere Strings
    // mit nur lesbaren Zeichen übergeben werden, wird ein binärer String erzeugt.
    $binaryKey = hash('sha256', $key, true);

    // Erstelle Initialisierungsvektor mit 16 Bytes
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $encryptedData = mcrypt_generic($td, $data);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Kombiniere iv und verschlüsselten Text
    return $iv.$encryptedData;
}

/**
 * Entschlüsselt Daten, welche vorher mit @param string $encryptedData Binärer string mit verschlüsselten Daten.
 *
 * @param string $key Dieser Schlüssel wird verwendet um die Daten zu entschlüsseln.
 * @return string Gibt die originalen entschlüsselten Daten zurück.
 * @throws Exception
 * @see encryptTwofish verschlüsselt wurden.
 */
function decryptTwofish($encryptedData, $key)
{
    if (!defined('MCRYPT_TWOFISH')) {
        throw new Exception('The MCRYPT_TWOFISH algorithm is required (PHP 5.3).');
    }

    $td = mcrypt_module_open(MCRYPT_TWOFISH, '', MCRYPT_MODE_CBC, '');

    // Extrahiere Initialisierungsvektor
    $ivSize = mcrypt_enc_get_iv_size($td);
    $iv = substr($encryptedData, 0, $ivSize);
    $encryptedData = substr($encryptedData, $ivSize);

    $binaryKey = hash('sha256', $key, true);

    mcrypt_generic_init($td, $binaryKey, $iv);
    $decryptedData = mdecrypt_generic($td, $encryptedData);
    mcrypt_generic_deinit($td);
    mcrypt_module_close($td);

    // Originaldaten wurden ergänzt mit 0-Zeichen bis zur Blockgrösse
    return rtrim($decryptedData, "\0");
}

/**
 * Generiere Code, Coupon, Serial...
 *
 * @param int $bytes Anzahl Zeichen
 * @param int $parts Anzahl Blöcke
 * @param array $options
 * @return string
 */
function pool_generate_code(int $bytes = 10, int $parts = 1, array $options = []): string
{
    $ascii = [
        0 => [48, 57], // 0-9
        1 => [97, 122], // a-z
    ];

    $options += [
        'uppercase' => 1,
        'numbers' => 50,
        'delimiter' => '-',
    ];

    $code = '';
    for ($p = 0; $p < $parts; $p++) {
        if ($p > 0) {
            $code .= $options['delimiter'];
        }
        for ($b = 0; $b < $bytes; $b++) {
            $key = (mt_rand(1, 100) <= $options['numbers'] ? 0 : 1);
            $byte = chr(mt_rand($ascii[$key][0], $ascii[$key][1]));
            $code .= $byte;
        }
    }

    if ($options['uppercase']) {
        $code = strtoupper($code);
    }

    return $code;
}

function getFieldData($array, $column): array
{
    return array_filter($array, function ($key) use ($column) {
        return ($key == $column);
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * creates path from last alphanumeric characters
 *('abcde', 3) => 'c/d/e/'
 *
 * @param $chars
 * @param int $numberOfDirectories
 * @return string
 */
function createPathFromLastChars($chars, int $numberOfDirectories = 4): string
{
    $result = '';
    for ($i = (-1 * $numberOfDirectories); $i < 0; $i++) {
        $result .= addEndingSlash(substr($chars, $i, 1));
    }
    return $result;
}

/**
 * Liefert einen Filenamen, der noch nicht im Ordner existiert.
 * Existiert die Datei bereits, wird durchnummeriert:
 * meinDokument.pdf
 * meinDokument-01.pdf
 * meinDokument-02.pdf
 */
function nextFreeFilename(string $dir, string $filename, string $delimiter = '-'): string
{
    if ($filename == '') {
        return '';
    }
    $filepath = addEndingSlash($dir).$filename;
    if (file_exists($filepath)) {
        $info = pathinfo($filepath);
        // echo pray($info);

        $filenameNoExtension = $info['filename'];
        $extension = $info['extension'];

        $pos = strrpos($filenameNoExtension, $delimiter);
        if ($pos === false) {
            $nr = 1;
            $newFilename = $filenameNoExtension.$delimiter.sprintf('%02d', $nr).'.'.$extension;
        } else {
            $filenameNoNumber = mb_substr($filenameNoExtension, 0, $pos);
            $nr = mb_substr($filenameNoExtension, $pos + 1);
            if (is_numeric($nr)) {
                $nr = intval($nr) + 1;
                $newFilename = $filenameNoNumber.$delimiter.sprintf('%02d', $nr).'.'.$extension;
            } else {
                $nr = 1;
                $newFilename = $filenameNoExtension.$delimiter.sprintf('%02d', $nr).'.'.$extension;
            }
        }
        return nextFreeFilename($dir, $newFilename, $delimiter);
    } else {
        return $filename;
    }
}

/**
 * Pendant to the .NET API HttpServerUtility.UrlTokenEncode.
 * Encodes a string into its base 64 digit equivalent string representation suitable for transmission in the URL.
 *
 * @param $data
 * @return string
 */
function base64url_encode($data): string
{
    $data = base64_encode($data);

    $length = strlen($data);
    if ($length == 0) return '';

    $numPaddingChars = 0;
    while ($data[$length - 1] == '=') {
        $numPaddingChars++;
        $data = substr($data, 0, -1);
        $length--;
    }

    $data = $data.$numPaddingChars;
    return strtr($data, '+/', '-_');
}

/**
 * Pendant to the .NET API HttpServerUtility.UrlTokenDecode.
 * Decodes a base64 URL token back into a string.
 *
 * @param $token
 * @return false|string
 */
function base64url_decode($token): bool|string
{
    $length = strlen($token);
    if ($length == 0) return false;

    $numPaddingChars = (int)$token[$length - 1];
    $token = substr($token, 0, -1);
    $token = strtr($token, '-_', '+/');
    $token .= str_repeat('=', $numPaddingChars);
    return base64_decode($token, true);
}

/**
 * determines http status from response headers
 *
 * @param string $responseLine
 * @return int
 */
function getHttpStatusCode(string $responseLine): int
{
    $httpResponseCode = 0;
    if (preg_match("#HTTP/[0-9\.]+\s+([0-9]+)#", $responseLine, $out)) {
        $httpResponseCode = intval($out[1]);
    }
    return $httpResponseCode;
}

/**
 * get errormessage from
 *
 * @param int $lastErrorCode
 * @return string
 */
function preg_last_error_message(int $lastErrorCode): string
{
    $errormessage = '';
    switch ($lastErrorCode) {
        case PREG_INTERNAL_ERROR: // 1
            $errormessage = 'Internal PCRE error';
            break;

        case PREG_BACKTRACK_LIMIT_ERROR: // 2
            $errormessage = 'PCRE regex backtrack limit '.ini_get('pcre.backtrack_limit').' was exhausted';
            break;

        case PREG_RECURSION_LIMIT_ERROR: // 3
            $errormessage = 'PCRE regex recursion limit '.ini_get('pcre.recursion_limit').' was exhausted';
            break;

        case PREG_BAD_UTF8_ERROR: // 4
            $errormessage = 'PCRE regex malformed UTF-8 data';
            break;

        case PREG_BAD_UTF8_OFFSET_ERROR: // 5
            $errormessage = 'PCRE regex the offset didn\'t correspond to the begin of a valid UTF-8 code point';
            break;

        case PREG_JIT_STACKLIMIT_ERROR: // 6
            $errormessage = 'Last PCRE function failed due to limited JIT stack space';
            break;
    }
    return $errormessage;
}

/**
 * Simple test if string is HTML
 *
 * @param string $string
 * @return bool
 */
function isHTML(string $string): bool
{
    return $string !== strip_tags($string);
}

/**
 * Simple test if string is JSON
 *
 * @param string $string
 * @return bool
 */
function isValidJSON(string $string): bool
{
    if ($string !== '' && $string[0] !== '{' && $string[0] !== '[') {
        return false;
    }
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * convert dash style (or another separator) into camelCase style
 *
 * @param string $string text
 * @param bool $capitalizeFirstCharacter default false
 * @param string $separator default dash
 * @return string
 */
function camelize(string $string, bool $capitalizeFirstCharacter = false, string $separator = '-'): string
{
    $result = str_replace($separator, '', ucwords($string, $separator));
    if (!$capitalizeFirstCharacter) {
        $result = lcfirst($result);
    }
    return $result;
}

/**
 * convert camelCase style into dash style (or another separator)
 *
 * @param string $string
 * @param string $separator
 * @return string
 */
function decamelize(string $string, string $separator = '-'): string
{
    return preg_replace('/\B([A-Z])/', $separator.'$0', $string);
    // alternate (todo: test speed) return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1'.$separator.'$2', $string));
}

/**
 * array to html attributes
 *
 * @param array $attributes
 * @return string
 */
function htmlAttributes(array $attributes): string
{
    $attributes = implode(' ', array_map(function ($k, $v) {
        if (is_array($v)) $v = implode(' ', $v);
        return ((is_int($k)) ? $v : $k.'="'.htmlspecialchars($v).'"');
    }, array_keys($attributes), $attributes));
    if ($attributes) $attributes = ' '.$attributes;
    return $attributes;
}

/**
 * calls the system command pdfunite
 *
 * @param array $pdfSourceFiles
 * @param string $pdfOut
 * @return bool
 */
function pdfunite(array $pdfSourceFiles, string $pdfOut): bool
{
    $pdfSourceFiles = implode(' ', array_map(escapeshellarg(...), $pdfSourceFiles));
    $pdfDestFile = escapeshellarg($pdfOut);
    $cmd = escapeshellcmd("pdfunite $pdfSourceFiles $pdfDestFile");
    exec($cmd, result_code: $return_var);
    return $return_var === 0 && file_exists($pdfOut);
}

/**
 * validate date
 *
 * @param string $date
 * @param string $format
 * @return bool
 */
function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * calculates the next day of the week based on a day of the week and an operand (subtrahend or summand).
 *
 * @param int $weekday
 * @param int $operand
 * @return int
 */
function calcNextWeekday(int $weekday, int $operand = 0): int
{
    $result = $weekday + $operand;
    return $result <= 0 ? $result + 7 : ($result > 7 ? $result - 7 : $result);
}

/**
 * calculates the next working day of the week based on a day of the week and an operand (subtrahend or summand).
 *
 * @param int $weekday
 * @param int $operand
 * @return int
 */
function calcNextWorkingDay(int $weekday, int $operand = 0): int
{
    $result = $weekday + $operand;
    $result = $result <= 0 ? $result + 7 : ($result > 7 ? $result - 7 : $result);
    if ($result >= 6) {
        if ($operand < 0) {
            $result = 5;
        } else {
            $result = 1;
        }
    }
    return $result;
}

/**
 * Calculates the difference in days of two dates (from DateTimeInterface)
 *
 * @param DateTimeInterface $StartDateTime
 * @param DateTimeInterface $EndDateTime
 * @param bool $absolute without sign
 * @return int
 */
function diffNumberOfDays(DateTimeInterface $StartDateTime, DateTimeInterface $EndDateTime, bool $absolute = true): int
{
    $DateTime1 = (clone $StartDateTime)->setTime(0, 0);
    $DateTime2 = (clone $EndDateTime)->setTime(0, 0);
    $days = $DateTime1->diff($DateTime2)->days ?: 0;
    if (!$absolute && $DateTime1 > $DateTime2) $days *= -1;
    return $days;
}

/**
 * determines if the current content-type is text/html
 *
 * @return bool
 */
function isHtmlContentTypeHeaderSet(): bool
{
    $headers = headers_list();

    $i = count($headers);
    while ($i) {
        if (str_starts_with($headers[--$i], 'content-type: text/html')) {
            return true;
        }
    }
    //    foreach ($headers as $header) {
    //        if (str_starts_with($header, 'content-type: text/html')) {
    //            return true;
    //        }
    //    }

    return false;
}

/**
 * Convert umlauts to long form and vice versa
 */
function umlauts(string $string, bool $reverse = false): string
{
    $umlauts = [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
        'ß' => 'ss',
    ];

    if ($reverse) {
        $umlauts = array_flip($umlauts);
    }
    return strtr($string, $umlauts);
}

/**
 * Check if a process is already running and abort if so. Needs a writable directory for the PID file.
 *
 * @param string $pidDir
 * @param string $jobName
 * @return void
 */
function checkRunningProcess(string $pidDir, string $jobName): void
{
    // get my process id
    $pid = getmypid();

    if (!$pid) {
        echo 'Process ID could not be detected.';
        exit(1);
    }

    if (!mkdirs($pidDir)) {
        echo "Could not create PID directory $pidDir. Please check permissions.";
        exit(1);
    }
    $pidFile = $pidDir.$jobName.'.pid';
    if (file_exists($pidFile)) {
        $pid = file_get_contents($pidFile);

        // check if process is still running
        if (posix_kill($pid, 0)) {
            echo "Job $jobName is already running with PID $pid. Aborting.";
            exit(1);
        }
    }
    file_put_contents($pidFile, $pid);
}

/**
 * Determine whether a string contains substrings from an array
 *
 * @param string $haystack
 * @param array $needles
 * @param bool $case_sensitive
 * @return bool
 */
function str_contains_any(string $haystack, array $needles, bool $case_sensitive = true): bool
{
    if (!$case_sensitive) {
        $haystack = strtolower($haystack);
    }

    foreach ($needles as $needle) {
        if (str_contains($haystack, $case_sensitive ? $needle : strtolower($needle))) {
            return true;
        }
    }

    return false;
}

/**
 * Currying is the process of transforming a function that takes multiple arguments into a sequence of functions.
 * You can use it as replacement for partial binding.
 */
function curry(Closure $closure, ...$args): Closure
{
    return fn(...$more) => $closure(...$args, ...$more);
}

function containThrowable(Closure $closure, Closure $fallbackHandler = null): Closure
{
    return function (...$args) use ($closure, $fallbackHandler) {
        try {
            return $closure(...$args);
        } catch (Throwable $error) {
            return $fallbackHandler instanceof Closure ? $fallbackHandler($error) : $fallbackHandler;
        }
    };
}

function containException(Closure $closure, $fallbackHandler = null): Closure
{
    return function (...$args) use ($closure, $fallbackHandler) {
        try {
            return $closure(...$args);
        } catch (Exception $error) {
            return $fallbackHandler instanceof Closure ? $fallbackHandler($error) : $fallbackHandler;
        }
    };
}

class Pointer implements JsonSerializable
{
    public mixed $val;

    public static function Pointer(mixed $val = null): Pointer
    {
        return new Pointer($val);
    }

    public function __construct($val)
    {
        $this->setVal($val);
    }

    public function deref(): mixed
    {
        return $this->val;
    }

    public function setVal(mixed $val): void
    {
        $this->val = $val;
    }

    public function jsonSerialize(): mixed
    {
        return $this->deref();
    }
}

#[Pure]
function array_promoteColumnToKey(array $array, string|int $column): array
{
    $columnValues = array_column($array, $column);
    return array_combine($columnValues, $array);
}

/**
 * Check if an object has a specific trait
 */
function hasTrait(object $object, string $trait, bool $recursive = true): bool
{
    return in_array($trait, $recursive ? class_uses_recursive($object) : class_uses($object));
}