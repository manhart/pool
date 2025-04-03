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
 * @since 07/28/2003
 */

use JetBrains\PhpStorm\NoReturn;
use JetBrains\PhpStorm\Pure;

/**
 * Returns the current Unix timestamp with microseconds.
 *
 * @param int $seed A multiplier for the current Unix timestamp seconds part.
 * @return float The current timestamp with microseconds, optionally multiplied by the seed.
 */
function getMicroTime(int $seed = 1): float
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
    if ($bytes < 0) return '-'; elseif ($bytes == 0) return '0 Bytes';
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
 * Converts a storage unit string to the equivalent byte value.
 *
 * @param string $value The storage unit string (e.g., '10MB', '5GB') to convert.
 * @return int The byte value equivalent of the provided storage unit string.
 * @noinspection PhpMissingBreakStatementInspection
 */
function storageUnitToBytes(string $value): int
{
    if (empty($value)) return 0;

    $trimmedValue = trim($value);

    if (!preg_match('#([0-9]+)\s*([a-z]+)#i', $trimmedValue, $matches)) {
        return 0;
    }

    $numericValue = (int)$matches[1];
    $unit = isset($matches[2]) ? strtolower($matches[2]) : '';

    switch ($unit) {
        case 't':
        case 'tb':
        case 'tib':
            $numericValue *= 1024;
        case 'g':
        case 'gb':
        case 'gib':
            $numericValue *= 1024;
        case 'm':
        case 'mb':
        case 'mib':
            $numericValue *= 1024;
        case 'k':
        case 'kb':
        case 'kib':
            $numericValue *= 1024;
    }
    return $numericValue;
}

/**
 * Calculates a series of appointment dates based on a start and end time.
 *
 * @param int $from The starting timestamp.
 * @param int|null $to The ending timestamp, or null for a single appointment.
 * @param int $intervall The interval in days between appointments (default is 7).
 * @param int $step The step increment for generating appointments (default is 1).
 * @return array An array of appointments, each containing a 'timestamp', 'date', and 'step'.
 */
function calculateAppointmentSeries(int $from, ?int $to, int $intervall = 7, int $step = 1): array
{
    $dates = [];

    if ($to === null) {
        return [0 => ['timestamp' => $from, 'date' => date('d.m.Y', $from), 'step' => $step]];
    }

    if ($intervall > 0 && $step > 0 && $to >= $from) {
        $secDay = 86400;
        $rhythmus = $intervall * $secDay;
        $diff = ($to + ($secDay - 1)) - $from;
        $numAppointments = ceil(($diff / $rhythmus) / $step);
        for ($i = 0; $i < $numAppointments; $i++) {
            $time = ($from + ($i * $step * $rhythmus));
            if (date('I', $from) < date('I', $time)) {
                $time -= 3600;
            } elseif (date('I', $from) > date('I', $time)) {
                $time += 3600;
            }
            $new_date = ['timestamp' => $time, 'date' => date('d.m.Y', $time), 'step' => $step];

            if ($time <= $to) {
                $dates[] = $new_date;
            }
        }
    }

    return $dates;
}

/**
 * Determines if a given year is a leap year.
 */
function isLeapYear(string $year = ''): bool
{
    $year = empty($year) ? date('Y') : $year;

    if (preg_match('/\D/', $year)) {
        return false;
    }

    $year = (int)$year;
    if ($year < 1000) { // before four digit year
        return false;
    }

    if ($year < 1582) { // Gregorian Calendar start year
        // before Gregorio XIII - 1582
        return ($year % 4 == 0);
    } else {
        // after Gregorio XIII - 1582
        return ((($year % 4 == 0) and ($year % 100 != 0)) or ($year % 400 == 0));
    }
}

/**
 * Adds a possibly missing ending slash to a string. Empty strings are not changed.
 */
function addEndingSlash(string $value): string
{
    if ($value != '') {
        if ($value[-1] != '/' && $value[-1] != '\\') {
            $value .= '/';
        }
    }

    return $value;
}

/**
 * Removes the ending slash or backslash from the input string if present.
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

/**
 * Removes any slash at the beginning of the passed string
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

/**
 * Creates directories recursively e.g. /var/log/prog/main/ups.log if prog and main don't exist, they are created.
 *
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
        $encoded = bin2hex($text);
        $encoded = chunk_split($encoded, 2, '%');
        $encoded = '%'.substr($encoded, 0, strlen($encoded) - 1);
    }

    return $encoded;
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

    while (($readdir = readdir($handle)) !== false) {
        if ($readdir === '..' || $readdir === '.') {
            continue;
        }
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
    closedir($handle);

    if ($rmSelf && !rmdir($dir)) {
        return false;
    }

    return true;
}

/**
 * Determines the extension of the file without the dot
 *
 * @see pathinfo() builtin function since PHP 4.0.3
 */
function file_extension(string $file = ""): string
{
    return substr($file, (strrpos($file, '.') ? strrpos($file, '.') + 1 : strlen($file)), strlen($file));
}

/**
 * Removes the extension from the file name
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
function shorten(string $str = '', int $len = 150, string|int $more = 1, bool $backtrack = true): string
{
    if ($str == '') return $str;
    if (is_array($str)) return '';
    $str = trim($str);

    // if it's les than the size given, then return it
    if (strlen($str) <= $len) return $str;

    // else get that size of text
    $encoding = @mb_detect_encoding($str);
    if ($encoding === false) {
        $str = substr($str, 0, $len);
        $encoding = ini_get('default_charset');
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
 */
function removeEmptyLines(string $line): bool
{
    return trim($line) !== '';
}

/**
 * Formats a given datetime string or timestamp into a specified format.
 */
function formatDateTime(int|string $datetime, string $format): string
{
    if (!is_numeric($datetime)) {
        $timestamp = strtotime($datetime);
        if ($timestamp !== -1) $datetime = $timestamp;
    }
    return (new DateTime("@$datetime"))->format($format);
}

/**
 * Replaces the html tag <br> by a new line
 *
 * @param string $subject text
 * @return string replaced text
 **/
function br2nl(string $subject): string
{
    return preg_replace('=<br(>|([\s/][^>]*)>)\r?\n?=i', chr(10), $subject);
}

/**
 * strips body from html page.
 * html, head and body tags will be dropped.
 */
function strip_body(string $file_content): string
{
    return preg_match('#<body[^>]*?>(.*?)</body>#si', $file_content, $matches) ? $matches[1] : '';
}

/**
 * Strips head from html page
 */
function strip_head(string $html): string
{
    return preg_match('#<head[^>]*?>(.*?)</head>#si', $html, $matches) ? $matches[1] : '';
}

function parseForwardedHeader(string $header): ?string
{
    foreach (explode(',', $header) as $segment) {
        $segment = trim($segment);
        foreach (explode(';', $segment) as $kv) {
            [$key, $val] = array_map('trim', explode('=', $kv, 2) + [1 => '']);
            if (strtolower($key) === 'for') {
                $val = trim($val, '"');

                // IPv6 with brackets + port → e.g. [2001:db8::1]:4711
                if (str_starts_with($val, '[')) {
                    $closing = strpos($val, ']');
                    if ($closing !== false) {
                        $ip = substr($val, 1, $closing - 1);
                    } else {
                        $ip = $val; // fallback
                    }
                } else {
                    // remove optional port from IPv4 or unbracketed IPv6
                    $colon = strpos($val, ':');
                    $ip = $colon !== false ? substr($val, 0, $colon) : $val;
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
    }
    return null;
}

/**
 * Delivers the client's IP
 *
 * @return string|null Remote/Client IP Adresse
 */
function getClientIP(): ?string
{
    foreach (
        [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',// RFC 7239
            'REMOTE_ADDR',
        ] as $key
    ) {
        if(empty($_SERVER[$key])) {
            continue;
        }

        $raw = $_SERVER[$key];

        // Check if RFC 7239 Forwarded header is present
        if ($key === 'HTTP_FORWARDED') {
            // Forwarded: for=192.0.2.43, for=198.51.100.17 or for=2001:db8::1 or for="[2001:db8::1]:4711"
            $ip = parseForwardedHeader($raw);
            if ($ip !== null) {
                return $ip;
            }
        }
        else {
            foreach (explode(',', $raw) as $entry) {
                $ip = trim($entry);
                if (str_contains($ip, ':')) {
                    $ip = explode(':', $ip)[0]; // strip port
                }
                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    return $ip;
                }
            }
        }
    }
    return null;
}

/**
 * Creates a browser fingerprint
 */
function getBrowserFingerprint(bool $withClientIP = true): string
{
    $data = $withClientIP && ($clientIP = getClientIP()) ? $clientIP : '';
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
 */
function getContentFromPHPFile(string $includeFile): string
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
 */
function sanitizeFilename(string $filename, bool $lowerCase = true): string
{
    $filename = umlauts($filename);
    $pattern = '/[^a-z0-9\-. _]+/';

    // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
    if ($lowerCase) {
        $filename = mb_strtolower($filename, mb_detect_encoding($filename));
    } else {
        $pattern = '/[^a-zA-Z0-9\-. _]+/';
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
 * Removes non-alphanumeric characters, dashes, and underscores from the
 * provided identifier after converting umlauts.
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
 */
function bool2string(bool $bool): string
{
    return $bool ? 'true' : 'false';
}

/**
 * Converts a given string to a boolean value.
 * Returns true if the string is '1' or 'true', otherwise returns false.
 */
function string2bool(?string $string): bool
{
    return ($string === '1' or $string === 'true');
}

/**
 * Checks if the object is empty.
 */
function isEmptyObject($obj): bool
{
    /** @noinspection PhpLoopNeverIteratesInspection */
    foreach ($obj as $ignored) {
        return false;
    }

    return true;
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht leer ist z.B. $arr = array_filter($arr, 'isNotEmpty').
 */
function isNotEmpty(mixed $var): bool
{
    return !empty($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht NULL ist.
 */
function isNotNull(mixed $var): bool
{
    return !is_null($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable kein leerer String ist.
 */
function isNotEmptyString(mixed $var): bool
{
    return !($var === '');
}

/**
 * Returns true if value is empty
 */
function isEmptyString(string $value): bool
{
    return ($value === '');
}

/**
 * Sends a list of files to the specified printer.
 */
function printFiles(string $printer, array $files): bool
{
    $files = array_map(escapeshellarg(...), $files);
    $command = 'lp -d '.escapeshellarg($printer).' '.implode(' ', $files);
    exec($command, $output, $return_value);
    return ($return_value === 0);
}

/**
 * ueberprueft, ob die Datei lokal existiert (das PHP file_exists erkennt neu angelegte Dateien auf der Shell/NFS Laufwerke nicht)
 *
 * @param string $remote e.g. rsh root@blub.de
 */
function shellFileExists(string $file, string $remote = ''): bool
{
    $file = escapeshellarg($file);
    $cmd = 'test -e '.$file.' && echo 1 || echo 0';
    if ($remote != '') $cmd = "$remote \"$cmd\"";
    exec($cmd, $arrOutFileExists);
    if (!isset($arrOutFileExists[0]))
        return false;
    return trim($arrOutFileExists[0]) === '1';
}

/**
 * Sets a global variable
 */
function setGlobal(string $key, mixed &$value): void
{
    $GLOBALS[$key] = &$value;
}

/**
 * Returns the value of a global variable
 */
function &getGlobal(string $key): mixed
{
    return $GLOBALS[$key];
}

/**
 * Trims the value if it is a string. Designed to be used with array_map.
 */
function safeTrim(mixed $value): mixed
{
    return is_string($value) ? trim($value) : $value;
}

/**
 * Query whether the global variable exists
 */
function global_exists(string $key): bool
{
    return isset($GLOBALS[$key]);
}

/**
 * Identifies potential hyphenation positions in a given word.
 *
 * @param string $word The word to evaluate for hyphenation.
 * @return array An array containing integer positions where hyphenation is possible.
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
 * Moves a file
 */
function moveFile(string $source, string $dest): bool
{
    if (!file_exists($source)) return false;
    $destDir = dirname($dest);
    if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) return false;
    if (!copy($source, $dest)) return false;
    if (!unlink($source)) return false;
    return true;
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
            throw new Exception("Tried to access subtree of value: '$arr' accessing: $subtree @$lastKey");
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
    $vectorArr = array_udiff_assoc(
        $toThisArr,
        $hereArr,
        function ($a, $b) use (&$tripped) { return $tripped != 0 ? $tripped : $tripped = strcmp($a, $b); },
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
 */
function getRealFile(string $path): string
{
    return is_link($path) ? realpath($path) : $path;
}

/**
 * Determine if a path is absolute
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
 * Reads directories at the specified path.
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
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

/**
 * Umrechnung DTP-Punkt in Millimeter (Desktop-Publishing Wobla);
 *
 * @link http://de.wikipedia.org/wiki/Pica_%28Typografie%29
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
 * Downloads a file from a specified source URL to a local destination.
 */
function downloadFile(string $sourceFile, string $destFile): false|int
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
        $cmd = "mount | grep \"$mountPoint\" | wc -l | tr -d \" \"";
        return intval(shell_exec($cmd));
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
 * Executes a shell command in the background.
 *
 * @param string $cmd The command to be executed.
 * @param int $priority The priority of the command (0 for default priority).
 * @return int The process ID (PID) of the background command.
 */
function shell_exec_background(string $cmd, array $args = [], int $priority = 0, string $logFile = '/dev/null'): int
{
    $cmd = escapeshellcmd($cmd);
    $args = implode(' ', array_map(escapeshellarg(...), $args));

    // Ensure priority is within valid range (-20 to 19)
    if ($priority < -20 || $priority > 19) {
        throw new \InvalidArgumentException('Priority must be between -20 and 19.');
    }
    $niceCmd = $priority ? "nice -n $priority" : '';
    $finalCmd = "nohup $niceCmd $cmd $args > $logFile 2>&1 & echo \$!";

    $PID = shell_exec($finalCmd);
    if (!$PID || !is_numeric($PID)) {
        throw new \pool\classes\Exception\RuntimeException("The shell command $cmd could not be executed in the background.");
    }

    return (int)rtrim($PID);
}

/**
 * Checks if a process is running by its PID.
 */
function isProcessAlive(int $pid): bool
{
    if (!function_exists('posix_kill')) {
        throw new RuntimeException('POSIX functions not available');
    }
    return posix_kill($pid, 0);
}

/**
 * @throws DateMalformedStringException
 */
function getAge(string $birthdate, string $asOf = 'now'): int
{
    $fromDate = new DateTime($birthdate);
    $toDate = new DateTime($asOf);
    return $fromDate->diff($toDate)->y;
}

function formatDuration(int $minutes): string
{
    $hours = intdiv($minutes, 60);
    return sprintf('%dh %02dm', $hours, ($minutes % 60));
}

function format24hTime(int $minutes): string
{
    $minutes = $minutes % 1440; // 1440 Minuten pro Tag
    if ($minutes < 0) {
        $minutes += 1440;
    }

    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;

    return sprintf('%02d:%02d', $hours, $mins);
}

/**
 * Generiere Code, Coupon, Serial...
 *
 * @param int $bytes Anzahl Zeichen
 * @param int $parts Anzahl Blöcke
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
 */
function base64url_encode(string $data): string
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
 */
function base64url_decode($token): string|false
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
 * Determines http status from response headers
 */
function getHttpStatusCode(string $responseLine): ?int
{
    return preg_match("#HTTP/\d(?:\.\d)?\s+(\d{3,})#", $responseLine, $matches) ? (int)($matches[1]) : null;
}

/**
 * Get errormessage from
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
 */
function isHTML(string $string): bool
{
    return $string !== strip_tags($string);
}

/**
 * Simple test if string is JSON
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
 * Convert camelCase style into dash style (or another separator)
 */
function decamelize(string $string, string $separator = '-'): string
{
    return preg_replace('/\B([A-Z])/', $separator.'$0', $string);
    // alternate (todo: test speed) return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1'.$separator.'$2', $string));
}

/**
 * Array to html attributes
 */
function buildHtmlAttributes(array $attributes): string
{
    $attributes = implode(' ', array_map(function ($k, $v) {
        if (is_array($v)) $v = implode(' ', $v);
        return ((is_int($k)) ? $v : $k.'="'.htmlspecialchars($v).'"');
    }, array_keys($attributes), $attributes));
    if ($attributes) $attributes = ' '.$attributes;
    return $attributes;
}

/**
 * Calls the system command pdfunite
 */
function pdfunite(array $pdfSourceFiles, string $pdfOut): bool
{
    $pdfSourceFiles = implode(' ', array_map(escapeshellarg(...), $pdfSourceFiles));
    $pdfDestFile = escapeshellarg($pdfOut);
    $cmd = escapeshellcmd("pdfunite $pdfSourceFiles $pdfDestFile");
    exec($cmd, result_code: $resultCode);
    return ($resultCode === 0) && file_exists($pdfOut);
}


/**
 * Validates a given date string against a specified format.
 */
function validateDate(string $date, string $format = 'Y-m-d H:i:s'): bool
{
    $dateTime = DateTime::createFromFormat($format, $date);
    return $dateTime && $dateTime->format($format) === $date;
}

/**
 * Validates a given time string
 */
function validateTime(string $time): bool
{
    $timeParts = explode(':', $time);
    $countTimeParts = count($timeParts);
    if ($countTimeParts === 0 || $countTimeParts > 3) return false;

    $formatParts = explode(':', 'H:i:s');
    $format = implode(':', array_slice($formatParts, 0, $countTimeParts));

    $dateTime = DateTime::createFromFormat($format, $time);
    return $dateTime && $dateTime->format($format) === $time;
}

/**
 * Calculates the next day of the week based on a day of the week and an operand (subtrahend or summand).
 */
function calcNextWeekday(int $weekday, int $daysToAdd = 0): int
{
    $newDay = $weekday + $daysToAdd;
    return normalizeWeekday($newDay);
}

/**
 * Normalizes a day of the week to ensure it falls within the range of 1 to 7.
 */
function normalizeWeekday(int $day): int
{
    return $day <= 0 ? $day + 7 : ($day > 7 ? $day - 7 : $day);
}

/**
 * Calculates the next working day of the week based on a day of the week and an operand (subtrahend or summand).
 */
function calcNextWorkingDay(int $weekday, int $offset = 0): int
{
    $adjustedDay = $weekday + $offset;
    $adjustedDay = $adjustedDay <= 0 ? $adjustedDay + 7 : ($adjustedDay > 7 ? $adjustedDay - 7 : $adjustedDay);
    if ($adjustedDay >= 6) {
        $adjustedDay = $offset < 0 ? 5 : 1;
    }
    return $adjustedDay;
}

/**
 * Calculates the difference in days of two dates (from DateTimeInterface)
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
 * Determines if the current content-type is text/html
 */
function hasHtmlContentType(): bool
{
    $headers = headers_list();
    $i = count($headers);
    while ($i) {
        if (str_starts_with($headers[--$i], 'Content-Type: text/html')) {
            return true;
        }
    }
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

    $umlauts = $reverse ? array_flip($umlauts) : $umlauts;
    return strtr($string, $umlauts);
}

/**
 * Check if a process is already running and abort if so. Needs a writable directory for the PID file.
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

/**
 * Removes consecutive duplicate values from an array.
 */
function removeConsecutiveDuplicates(array $input): array
{
    return array_reduce($input, function ($carry, $item) {
        if (empty($carry) || end($carry) !== $item) {
            $carry[] = $item;
        }
        return $carry;
    }, []);
}