<?php
/**
 * Utils.inc.php
 *
 * @version $Id: Utils.inc.php,v 1.65 2007/07/17 13:49:10 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 07/28/2003
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 */


/**
 * Gibt den aktuellen UNIX-Timestamp/Zeitstempel in Mikrosekunden zurueck
 *
 * @return float Zeitstempel in Mikrosekunden
 **/
function getMicrotime($seed = 1)
{
    list($usec, $sec) = explode(' ', microtime());

    return ((float)$usec + ((float)$sec * $seed));
}

/**
 * pray ()
 * Erweiterte var_dump Funktion mit formatierter Ausgabe
 * Durchlaeuft die Argumente (Arrays/Objects) rekursiv und gibt eine formatierte Liste aus.
 * Optional werden Funktionsnamen von Objekten ausgegeben .
 * Es zeigt alle Variablen an, die es finden kann.
 * @method static
 *
 * @access public
 * @param mixed $data Variable jeden Datentyps
 * @param boolean|int $functions Zeige Funktionsnamen der Objekte (Standard = 0)
 * @return string
 */
function pray($data, $functions=0)
{
    $result = "";
    if($functions != 0) {
        $sf = 1;
    }
    else {
        $sf = 0;
    }

    if (isset ($data)) {
        if ((is_array($data) and count($data)) || (is_object($data) and !isEmptyObject($data))) {
            $result .= "<OL>\n";
            foreach($data as $key => $value) {
	            // while (list ($key, $value) = each ($data)) {
                $type = gettype($value);

                if ($type == "array" || $type == "object") {
                    $result .= sprintf("<li>(%s) <b>%s</b>:\n", $type, $key);

                    if (strtolower($key) != 'owner' and (strtolower($key) != 'weblication')
                        and strtolower($key) != 'parent' and strtolower($key) != 'backtrace') { // prevent recursion
                        $result .= pray($value, $sf);
                    }
                    else {
                        $result .= 'no follow, infinite loop';
                    }
                }
                elseif (stripos($type, 'function') !== false) {
                    if ($sf) {
                        $result .= sprintf("<li>(%s) <b>%s</b> </LI>\n", $type, $key, $value);
                        // There doesn't seem to be anything traversable inside functions.
                    }
                }
                else {
                    /*	if (!$value){
                        $value = "(none)";
                    }*/
                    $result .= sprintf("<li>(%s) <b>%s</b> = %s</LI>\n", $type, $key, $value);
                }
                unset($key, $value);
            }
            $result .= "</OL>end.\n";
        }
        else {
            $result .= "(empty)";
        }
    }

    return $result;
}

/**
 * formatBytes()
 *
 * @param integer $bytes Anzahl der Bytes
 * @param bool $shortVersion Abgekuerzt
 * @param int $decimals
 * @param string $blank
 * @return string Formatierter String z.B.  33,44 MBytes
 */
function formatBytes($bytes, $shortVersion = false, $decimals = 2, $blank = ' ')
{
    // Bytes
    if ($bytes < 1024) {
        return (number_format($bytes, $decimals, ',', '.').$blank.(($shortVersion) ? 'b' : 'Bytes'));
    }

    // KBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, $decimals, ',', '.').$blank.(($shortVersion) ? 'KB' : 'KBytes'));
    }

    // MBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, $decimals, ',', '.').$blank.(($shortVersion) ? 'MB' : 'MBytes'));
    }

    // GBytes
    $bytes = $bytes / 1024;
    if ($bytes < 1024) {
        return (number_format($bytes, $decimals, ',', '.').$blank.(($shortVersion) ? 'GB' : 'GBytes'));
    }

    // TBytes
    $bytes = $bytes / 1024;
    return (number_format($bytes, $decimals, ',', '.').$blank.(($shortVersion) ? 'TB' : 'TBytes'));
}

/**
 * @param $val
 * @return int
 */
function returnBytes($val)
{
    if(empty($val))return 0;

    $val = trim($val);

    preg_match('#([0-9]+)[\s]*([a-z]+)#i', $val, $matches);

    $last = '';
    if(isset($matches[2])){
        $last = $matches[2];
    }

    if(isset($matches[1])){
        $val = (int) $matches[1];
    }

    switch (strtolower($last))
    {
        case 'g':
        case 'gb':
            $val *= 1024;
        case 'm':
        case 'mb':
            $val *= 1024;
        case 'k':
        case 'kb':
            $val *= 1024;
    }

    return (int) $val;
}

/**
 * Errechnet eine Terminserie (ber�cksichtigt Sommer- & Winterzeit)
 *
 * @param int $von Startzeitpunkt
 * @param int $bis Endzeitpunkt
 * @param int $intervall Intervall in Tage (Standard 7 f�r eine Woche)
 * @param int $step Schritte (schrittweise) bedeutet zu jedem $step 'ten Intervall. Z.B. bei 2 wird jeder 2. Termin �bersprungen (bzw. 2 Wochen Intervall bei 7 Tagen erreicht)
 * @return array
 */
function getSeriesOfAppointments($from, $to, $intervall = 7, $step = 1)
{
    $dates = array();

    if (empty($to)) {
        return array(0 => array('timestamp' => $from, 'date' => date('d.m.Y', $from), 'step' => $step));
    }

    if ($intervall > 0 and $step > 0 and $to >= $from) {
        $secDay = 86400; // Sekunden eines Tages
        $rhythmus = $intervall * $secDay;

        $diff = ($to + ($secDay - 1)) - $from;
        $numAppointments = ceil(($diff / $rhythmus) / $step);
        if (@constant('DEBUG')) echo 'Anzahl generierter Termine: '.$numAppointments."\n";
        for ($i = 0; $i < $numAppointments; $i++) {
            $new_date = array();
            $time = ($from + ($i * $step * $rhythmus));
            if (date('I', $from) < date('I', $time)) {
                $time -= 3600;
            }
            elseif (date('I', $from) > date('I', $time)) {
                $time += 3600;
            }
            $new_date['timestamp'] = $time;
            $new_date['date'] = date('d.m.Y', $time);
            $new_date['step'] = $step;
            if ($time <= $to) {
                array_push($dates, $new_date);
            }
            unset($new_date);
        }
    }

    return $dates;
}

/**
 * Gibt true zurueck fuer ein Schaltjahr, andernfalls false
 *
 * @param string Jahr im Format CCYY
 * @access public
 * @return boolean true/false
 */
function isLeapYear($year = '')
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
    }
    else {
        // nach Gregorio XIII - 1582
        return ((($year % 4 == 0) and ($year % 100 != 0)) or ($year % 400 == 0));
    }
} // end func isLeapYear

/**
 * Gibt den Wochentag in deutscher und englischer Sprache zurueck.
 * Wird kein Dezimal-Wert uebergeben, gibt er den aktuellen Wochentag aus.
 * Der zweite Parameter bestimmt die Sprache. Wird er nicht angegeben,
 * liefert die Funktion ein Array mit allen Sprachen zurueck.
 *
 * @param integer $decimal_value Dezimal Wert fuer Wochentag 1-7 (Mo-So)
 * @param string $locale Internationales Format fuer Laenderlokale
 * @return array or string Wochentag
 **/
function getWeekday($decimal_value = 0, $locale = 'de_DE')
{
    if ($decimal_value == null) {
        $decimal_value = date('w');
    }
    switch ($decimal_value) {
        case 1:
            $result['de_DE'] = 'montag';
            $result['en_US'] = 'monday';
            break;

        case 2:
            $result['de_DE'] = 'dienstag';
            $result['en_US'] = 'tuesday';
            break;

        case 3:
            $result['de_DE'] = 'mittwoch';
            $result['en_US'] = 'wednesday';
            break;

        case 4:
            $result['de_DE'] = 'donnerstag';
            $result['en_US'] = 'thursday';
            break;

        case 5:
            $result['de_DE'] = 'freitag';
            $result['en_US'] = 'friday';
            break;

        case 6:
            $result['de_DE'] = 'samstag';
            $result['en_US'] = 'saturday';
            break;

        case 0:
        case 7:
            $result['de_DE'] = 'sonntag';
            $result['en_US'] = 'sunday';
            break;

        default:
            trigger_error('Unknown Weekday "'.$decimal_value.'" in '.__FUNCTION__);
    }

    if (!is_null($locale)) {
        return $result[$locale];
    }
    else {
        return $result;
    }
}

if (!function_exists('addEndingSlash')) {
    /**
     * Fuegt bei Verzeichnissen endenden Slash hinzu.
     *
     * @access public
     * @param string $value Wert (Ordner, Verzeichnis)
     * @return Wert mit endenden Slash
     **/
    function addEndingSlash($value)
    {
        if ($value != '') {
            if ($value[strlen($value) - 1] != '/') {
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
     * @access public
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeEndingSlash($value)
    {
        if (!empty($value)) {
            $len = strlen($value) - 1;
            if ($value[$len] == '/') {
                $value = substr($value, 0, $len);
            }
        }

        return $value;
    }
}

if (!function_exists('removeBeginningSlash')) {
    /**
     * Entfernt endenden Slash im String
     *
     * @access public
     * @param string $value String (z.B. Verzeichnis)
     * @return string String ohne Slash am Ende
     */
    function removeBeginningSlash($value)
    {
        if (!empty($value)) {
            if ($value[0] == '/') {
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
 **/
function mkdirs(string $strPath, $mode = 0777)
{
    if (@is_dir($strPath)) {
        return true;
    }
    else {
        $pStrPath = dirname($strPath);
        if (!mkdirs($pStrPath, $mode)) {
            return false;
        }

        return @mkdir($strPath, $mode);
    }
}

/**
 * hex_encode()
 * Maskiert z.B. URIs:
 * Die Maskierung besteht darin, ein Prozentzeichen % zu notieren, gefolgt von dem hexadezimal ausgedrueckten Zeichenwert des gewuenschten Zeichens.
 *
 * @param string $text Bliebiger Text, URI, E-Mail, etc.
 * @return maskierter / codierter Text
 * @link http://selfhtml.teamone.de/html/verweise/email.htm
 **/
function hex_encode($text)
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
 * getJSEMailLink()
 * Gibt einen klickbaren JavaScript HEX kodierten E-Mail Link zurueck.
 * Vor allem gegen Spam Bots interessant!
 *
 * @param string $email E-Mail Adresse
 * @return string JavaScript E-Mail Link
 **/
function getJSEMailLink($email, $caption = null)
{
    if (strpos($email, '@') === false) {
        return '';
    }
    $email = explode('@', $email);
    $en_caption = hex_encode($email[0]);
    $en_at = hex_encode('@');
    $en_ext = hex_encode($email[1]);
    $js = '<script type="text/javascript" language="javascript">
				<!--
					var caption = "'.$en_caption.'";
					var at = "'.$en_at.'";
					var ext = "'.$en_ext.'";';

    $js .= 'document.write(\'<a href="mailto:\' + caption + at + ext + \'">\');';
    if ($caption) {
        $js .= '	document.write(\''.$caption.'\');';
    }
    else {
        $js .= '	document.write(urlDecode("'.$en_caption.'") + urlDecode("'.$en_at.'") + urlDecode("'.$en_ext.'"));';
    }
    $js .= '	document.write(\'</a>\');';

    $js .= '
				//-->
				</script>';

    return $js;
}

/**
 * deleteDir()
 * Loescht kompletten Inhalt  inkl. Unterverzeichnisse eines Verzeichnis
 *
 * @access public
 * @param string $dir Verzeichnis
 * @return boolean Erfolgsstatus
 **/
function deleteDir($dir, $rmSelf = true)
{
    if (!$opendir = opendir($dir)) {
        return false;
    }
    $dir = addEndingSlash($dir);
    while (false !== ($readdir = readdir($opendir))) {
        if ($readdir !== '..' && $readdir !== '.') {
            $readdir = trim($readdir);
            if (is_file($dir.$readdir)) {
                if (!unlink($dir.$readdir)) {
                    return false;
                }
            }
            elseif (is_dir($dir.$readdir)) {
                // Calls itself to clear subdirectories
                if (!deleteDir($dir.$readdir)) {
                    return false;
                }
            }
        }
    }
    closedir($opendir);
    if ($rmSelf) {
        if (!rmdir($dir)) {
            return false;
        }
    }

    return true;
}

/**
 * determines the extension of the file without the dot
 * see also PHP function pathinfo since version 4.0.3
 *
 * @param string $file filename
 * @return string file extension
 **/
function file_extension(string $file = ""): string
{
    return substr($file, (strrpos($file, ".") ? strrpos($file, ".") + 1 : strlen($file)), strlen($file));
}

/**
 * removes the extension from the file name
 *
 * @param string $file filename
 * @return string filename without extension
 **/
function remove_extension(string $file = ''): string
{
    return substr($file, 0, (strrpos($file, '.') ? strrpos($file, '.') : strlen($file)));
}


/**
 * Verkuerzt einen Text auf eine bestimme Laenge. Beim Abschneiden geht die Funktion jedoch bis zum letzten Leerzeichen zurueck, damit
 * er ein Wort nicht in der Mitte teilt.
 * Wenn der Text laenger ist als der Ausschnitt, kann mittels dem Parameter more == 1 die Zeichenfolge '...' angehaengt werden.
 *
 * @access public
 * @param string $str Text
 * @param integer $len Maximale Laenge
 * @param integer $more Fuege '...' hinzu, falls der Text gekuerzt wurde
 * @param bool $backtrack True bedeutet, die Funktion schneidet nicht innerhalb eines Wortes durch, sondern liefert nur vollst�ndige W�rter
 * @return string gekuerzte Version
 **/
function shorten($str = '', $len = 150, $more = 1, $backtrack = true)
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
    }
    else {
        $str = mb_substr($str, 0, $len, $encoding);
    }

    // backtrack to the end of a word
    if ($str != '') {
        // check to see if there are any spaces left
        if (!substr_count($str, ' ')) {
            if ($more) $str .= (($more == 1) ? '...' : $more);

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
function removeEmptyLines($line)
{
    return trim($line) != '';
}

/**
 * formatDateTime()
 *
 * @param $datetime
 * @param $format
 * @return
 **/
function formatDateTime($datetime, $format)
{
    if (is_numeric($datetime) == false) {
        $timestamp = strtotime($datetime);
        if ($timestamp !== -1) {
            $datetime = $timestamp;
        }
    }

    return strftime($format, $datetime);
}

/**
 * formatDEDateToEN()
 * Arbeitet etwas anders als formatDateTime, da es deutsches Format (01.01.2004) in
 * englisches Format (2004-01-01) umwandelt.
 *
 * @param $datetime
 * @param $format
 * @return
 **@author Andreas Horvath
 * @see formatDateTime
 */
function formatDEDateToEN($strDate, $delimiter = '.')
{
    $arrDate = explode($delimiter, $strDate);
    return strftime("%Y-%m-%d", strtotime($arrDate[2]."-".$arrDate[1]."-".$arrDate[0]));
}



/**
 * replaces the html tag <br> by a new line
 *
 * @param string $subject text
 * @return string replaced text
 **/
function br2nl($subject)
{
    return preg_replace('=<br(>|([\s/][^>]*)>)\r?\n?=i', chr(10), $subject);
}

/**
 * replaces all linebreaks to <br />
 *
 * @param $string
 * @return string|string[]
 */
function nl2br2(string $string): string
{
    $string = str_replace(array("\r\n", "\r", "\n"), '<br>', $string);
    return $string;
}

/**
 * strips body from html page.
 * html, head and body tags will be dropped.
 *
 * @param string $file_content Datei
 * @return string Datei ohne Html, Head und Body Tags
 **/
function strip_body($file_content)
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
function strip_head($html): string
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
function getClientBrowser()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    if (($pos = strpos($userAgent, 'MSIE')) !== false) {
        list($version) = sscanf(substr($userAgent, $pos), 'MSIE %f; ');
        $browser = 'IE';
    }
    else if (strpos($userAgent, 'Opera')) {
        $browser = 'Opera';
    }
    else if (strpos($userAgent, 'Mozilla/([0-9].[0-9]{1,2})')) {
        $browser = 'Mozilla';
    }
    else {
        $browser = 'Other';
    }

    return array(
        'name' => $browser,
        'version' => $version
    );
}

/**
 * Liefert die IP des Clients (kann jedoch durch proxy oder anonymizer verfaelscht werden)
 *
 * @return string Remote/Client IP Adresse
 **/
function getClientIP()
{
    foreach (
        array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
    ) as $key) {
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
function getBrowserFingerprint($withClientIP=true)
{
    $data = ($withClientIP ? getClientIp() : '');
    $data .= $_SERVER['HTTP_USER_AGENT'];
    $data .= $_SERVER['HTTP_ACCEPT'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $data .= $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $hash = md5($data);
    return $hash;
}




/**
 * Holt sich den Inhalt von PHP Skripten und gibt ihn per return Wert zurueck.
 *
 * @param string $includeFile Absoluter Dateipfad
 * @return string
 */
function getContentFromInclude($includeFile)
{
    ob_start();
    include($includeFile);
    $content = ob_get_contents();
    ob_end_clean();

    return $content;
}

/**
 * Formatiert eine Zahl als W�hrung.
 *
 * @param string $value Wert
 * @param string $num_decimal_places Dezimalstellen
 * @param string $currency W�hrungssymbol
 * @return string Zahl formatiert als W�hrung
 */
function formatCurrency($value, $num_decimal_places = 2, string $currency = '&#8364;')
{
    return number_format(floatval($value), $num_decimal_places, ',', '.').$currency;
}

/**
 * Formatiert Datenbank Timestamp (z.B. bei MySQL Feldtyp:timestamp) in ein beliebiges Datumsformat.
 *
 * @param int $datetime Datenbank Timestamp im Format YYYYMMDDhhmmss
 * @param string $format
 * @return string formatiertes Datum
 */
function formatDBTimestampAsDatetime($datetime, $format = '%d.%m.%Y %H:%M')
{
    $year = substr($datetime, 0, 4);
    $mon = substr($datetime, 4, 2);
    $day = substr($datetime, 6, 2);
    $hour = substr($datetime, 8, 2);
    $min = substr($datetime, 10, 2);
    $sec = substr($datetime, 12, 2);

    return formatDateTime(mktime($hour, $min, $sec, $mon, $day, $year), $format);
}

/**
 * Wandelt ein Array in das HTML Attribute Format um: name="Manhart" vorname="Alexander"
 *
 * @param array $array Array
 * @return string
 */
function arrayToAttr($array)
{
    $strHtmlTagAttr = '';
    if (is_array($array)) {
        foreach ($array as $key => $value) {
            if ($strHtmlTagAttr != '') $strHtmlTagAttr .= ' ';
            $strHtmlTagAttr .= $key.'="'.$value.'"';
        }
    }

    return $strHtmlTagAttr;
}

/**
 * Gibt 0 zurueck (ganz n�tzlich im Zusammenhang mit Array Initialisierung array_map('zero', $arr)).
 *
 * @return int 0
 */
function zero()
{
    return 0;
}

/**
 * Gibt einen Leerstring zurueck (ganz n�tzlich im Zusammenhang mit Array Initialisierung array_map('emptyString', $arr)).
 *
 * @return int 0
 */
function emptyString()
{
    return '';
}

/**
 * Simple Filename Sanitizer
 *
 * strtolower() guarantees the filename is lowercase (since case does not matter inside the URL, but in the NTFS filename)
 * [^a-z0-9]+ will ensure, the filename only keeps letters and numbers
 * Substitute invalid characters with '-' keeps the filename readable
 *
 * @see https://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
 * @param string $filename $file without path
 * @return string
 */
function sanitizeFilename(string $filename): string
{
    // lowercase for windows/unix interoperability http://support.microsoft.com/kb/100625
    $filename = mb_strtolower($filename, mb_detect_encoding($filename));
    $filename = preg_replace( '/[^a-z0-9\-\. _]+/', '-', $filename);
    $filename = preg_replace(
        array(
            // "file   name.zip" becomes "file-name.zip"
            '/ +/',
            // "file___name.zip" becomes "file-name.zip"
            '/_+/',
            // "file---name.zip" becomes "file-name.zip"
            '/-+/'
        ), '-', $filename);
    $filename = preg_replace(array(
        // "file--.--.-.--name.zip" becomes "file.name.zip"
        '/-*\.-*/',
        // "file...name..zip" becomes "file.name.zip"
        '/\.{2,}/'
    ), '.', $filename);
    return trim($filename, '.-');
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
    foreach($obj as $prop) {
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
function isNotEmpty($var)
{
    return !empty($var);
}

/**
 * Liefert einen Wahrheitswert, wenn die Variable nicht NULL ist.
 *
 * @param mixed $var
 * @return bool
 */
function isNotNull($var)
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
function printFiles($printer, $files)
{
    $files = array_map('escapeshellarg', $files);
    $command = 'lp -d '.$printer.' '.implode(' ', $files);
    exec($command, $output, $return_value);

    return ($return_value == 0);
}

/**
 * �berpr�ft, ob die Datei lokal existiert (das PHP file_exists erkennt neu angelegte Dateien auf der Shell/NFS Laufwerke nicht)
 *
 * @param string $file
 * @param string $remote z.B. rsh root@blub.de
 * @return boolean
 */
function shellFileExists($file, $remote = '')
{
    $cmd = 'test -e '.$file.' && echo 1 || echo 0';
    if ($remote != '') $cmd = $remote.' "'.$cmd.'"';
    exec($cmd, $arrOutFileExists);
    if (!isset($arrOutFileExists[0])) return false;
    $file_exists = (trim($arrOutFileExists[0]) === '1') ? true : false;

    return $file_exists;
}

/**
 * Erstellt Suchmuster f�r SQL-Statement. Sehr hilfreich, wenn man Textfeldsuche ben�tigt.
 * Z.B. Eingabe von A listet alle mit A beginnenden Treffer auf. Eingabe > Parameter "$min" listet
 * alle Treffer die die Eingabe enthalten auf.
 *
 * @param string $wert
 * @param int $min
 * @return string
 */
function getSearchPattern4SQL($wert, $min = 2)
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
function setGlobal($key, &$value)
{
    $GLOBALS[$key] = &$value;
}

/**
 * Liefert Wert einer globalen Variable
 *
 * @param string $key
 * @return mixed
 */
function &getGlobal($key)
{
    return $GLOBALS[$key];
}

/**
 * Abfrage ob die globale Variable existiert
 *
 * @param string $key
 * @return bool
 */
function global_exists($key)
{
    return isset($GLOBALS[$key]);
}

/**
 * Magische PHP Konstanten in ein Array zusammenf�hren
 *
 * @param mixed $file __FILE__
 * @param mixed $line __LINE__
 * @param mixed $function __FUNCTION__ ab PHP 4.3
 * @param mixed $class __CLASS__ ab PHP 4.3
 * @param mixed $method erst ab PHP 5
 * @return array
 */
function magicInfo($file, $line, $function, $class, $specific = array())
{
    if (!is_array($specific)) {
        if (!is_null($specific)) {
            $specific = array($specific);
        }
    else $specific = array();
    }

    return array_merge(array(
        'file' => $file,
        'line' => $line,
        'function' => $function,
        'class' => $class
    ), $specific);
}

if (!function_exists('mime_content_type')) {
    /**
     * Ermittelt den Mime Content Type f�r eine Datei
     *
     * @param string $f Datei
     * @return string
     */
    function mime_content_type($f)
    {
        return trim(exec('file -bi '.escapeshellarg($f)));
    }
}

/**
 * Silbentrennung
 *
 * @param string $word Zu trennendes Wort
 * @return array M�gliche Trennpositionen
 */
function hyphenation($word)
{
    $hyphenationPositions = array();

    $wordLen = strlen($word);
    if ($wordLen > 2) {
        $allowHyphenation = false;
        $vowels = array('a', 'e', 'i', 'o', 'u', 'ä', 'ü', 'ö');
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
        $splices = array('sch', 'ch', 'ph', 'ck', 'pf', 'br', 'pl', 'tr', 'st', 'gr');
        $divider = array('-', '/', '\\', '*', '#', ';', '.', '+', '=', ')', '(', '&', '!', '?', '<', '>', ':', ' ', '_', '~');

        for ($i = 2; $i < $wordLen - 1; $i++) {
            $c0 = $word[$i - 1];
            if ($allowHyphenation == false and in_array($c0, $vowels)) {
                $allowHyphenation = true;
            }
            if ($allowHyphenation) {
                $c = $word[$i];
                $c1 = $word[$i + 1];
                $v = $c0 + $c;
                if ($v == 'ch' and $i > 2 and $word[$i - 2] == 's') {
                    $v = 'sch';
                }
                if (in_array($c1, $vowels) and (in_array($c, $vowels) == false) and (in_array($c, $divider) == false) and
                                                                                    (in_array($c0, $divider) == false)) {
                    if (in_array($v, $splices)) {
                        array_push($hyphenationPositions, ($i - strlen($v) + 1));
                    }
                    else {
                        array_push($hyphenationPositions, $i);
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
function HTTPStatus($num)
{
    static $http = array(
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
        504 => "HTTP/1.1 504 Gateway Time-out"
    );

    header($http[$num]);
}

/**
 * L�dt das Dokument neu.
 *
 * @param $params array
 */
function reloadUrl($params = array())
{
    if (class_exists('Url')) {
        $Url = new Url();
        foreach ($params as $key => $val) {
            $Url->setParam($key, $val);
        }
        $Url->reloadUrl();
    }
    else {
        $http = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $http.$host.$uri;
        header('Location: '.$url);
        exit;
    }
}

/**
 * Verschiebt eine Datei
 *
 * @param string $source Quelle
 * @param string $dest Ziel
 */
function move_file($source, $dest)
{
    $res_copy = copy($source, $dest);
    if ($res_copy) $res_unlink = unlink($source);

    return ($res_copy and $res_unlink);
}

/**
 * Verzeichnis auslesen: erstellt Dateiliste
 *
 * @param string $path Stammverzeichnis
 * @param boolean $absolute Datei mit absolutem Pfad zurückgeben
 * @param string $filePattern Dateifilter
 * @param string $subdir auszulesendes Unterverzeichnis
 * @return array Dateiliste
 */
function readFiles($path, $absolute = true, $filePattern = '/.JPG/i', $subdir = '')
{
    $files = array();

    $path = addEndingSlash($path).addEndingSlash($subdir);
    if ($res = opendir($path)) {
        while (($filename = readdir($res)) !== false) {
            $file = $path.$filename;
            if (is_file($file) and preg_match($filePattern, $filename)) {
                $fileRelative = addEndingSlash($subdir).$filename;
                array_push($files, ($absolute) ? $file : $fileRelative);
            }
        }
        closedir($res);
    }

    return $files;
}

/**
 * Liest ein Verzeichnis rekursiv aus. Dabei kann man per regulärem Ausdruck auf Datei- oder Verzeichnisebene filtern. Die Ergebnisse werden absolut oder relativ zum
 * übergebenen Pfad zurück gegeben.
 *
 * @param $path Stammpfad
 * @param bool $absolute Datei mit absolutem Pfad
 * @param string $filePattern Dateifilter
 * @param string $dirPattern Verzeichnisfilter
 * @param string $subdir auszulesendes Unterverzeichnis
 * @return array
 * @throws Exception
 */
function readFilesRecursive($path, $absolute = true, $filePattern = '', $dirPattern = '/^[^\.].*$/', $subdir = '', $callback = null)
{
    $files = array();

    $root = $path;
    $path = addEndingSlash($path).addEndingSlash($subdir);
    $res = @opendir($path);
    if (!$res) {
        throw new \Exception('Pfad '.$path.' existiert nicht oder ist kein Verzeichnis oder hat keine Zugriffsberechtigung!');
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
                // Doppelte gleichnamige Dateien gibt es nicht. Aber afufgrund der Callback Funktion implementiert (u.a. basename):
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
                    array_push($files, $file);
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
function readDirs(string $path)
{
    return glob(addEndingSlash($path).'*', GLOB_ONLYDIR);
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
function multisort($hauptArray, $columnName, $sorttype = SORT_STRING, $sortorder = SORT_ASC)
{
    $sortarr = array();
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
 * Schaut, ob die Anfrage per Ajax kommt.
 * Genauer gesagt, ob die Variable $_SERVER['HTTP_X_REQUESTED_WITH'] auf XMLHttpRequest gesetzt ist.
 * Dies macht z.B. das Javascript-Framework Prototype. (Ajax.Request)
 *
 * @return boolean
 */
function isAjax(): bool
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
           || (isset($_REQUEST['HTTP_X_REQUESTED_WITH']) && $_REQUEST['HTTP_X_REQUESTED_WITH']);
}


/**
 * Umrechnung DTP-Punkt in Millimeter (Desktop Publishing Wobla);
 *
 * @link http://de.wikipedia.org/wiki/Pica_%28Typografie%29
 * @param float $pp
 * @return float
 */
function pt2mm($pt)
{
    return $pt * 0.35277;
}

/**
 * Erzwingt einen Download im Browser
 *
 * @param string $file Datei (mit absolutem Pfad)
 * @param string $mimetype Mimetype z.b. application/octet-stream
 */
function forceFileDownload($file, $mimetype = '')
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
function downloadFile($sourceFile, $destFile)
{
    return file_put_contents($destFile, fopen($sourceFile, 'r'), LOCK_EX);
}

/**
 * Die Funktion pr�ft mit Shell-Komandos, ob ein entferntes Verzeichnis gemountet ist.
 *
 * @param string $mountPoint (der exakte Mount-Point(so wie er in der /etc/fstab steht.))
 * @return int [0|1]
 */
function isMounted($mountPoint)
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
function legibleColor($hexcolor, $dark = '#000000', $light = '#FFFFFF')
{
    return (hexdec($hexcolor) > 0xffffff / 2) ? $dark : $light;
}

/**
 * Erzeugt einen zuf�lligen Farbcode
 *
 * @return string
 */
function randColor()
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
function utf8_to_rtf($utf8_text)
{
    $utf8_patterns = array(
        "[\xC2-\xDF][\x80-\xBF]",
        "[\xE0-\xEF][\x80-\xBF]{2}",
        "[\xF0-\xF4][\x80-\xBF]{3}",
    );
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
function shell_exec_background($cmd, $priority = 0)
{
    if ($priority) {
        $PID = shell_exec('nohup nice -n '.$priority.' '.$cmd.' >/dev/null 2>&1 & echo $!');
    }
    else {
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
function is_process_running($PID)
{
    exec('ps '.$PID, $state);

    return (count($state) >= 2);
}

/**
 * Berechne Alter
 *
 * @param date $from Datum im englischen Format
 * @param date $to Datum im englischen Format
 * @return int Alter
 */
function calcAge($from, $to = 'now')
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
        }
        elseif ($from_month == $to_month) {
            if (date('d', $from) > date('d', $to)) {
                $age -= 1;
            }
        }

    return $age;
}

/**
 * Formatiere Minuten um als Stunde-Minuten Text
 *
 * @param int Minuten
 * @return string
 */
function formatStdMin($min)
{
    $val = intval($min);

    return floor($val / 60).' Std. '.($val % 60).' Min.';
}

/**
 * Formatiere Minuten in 24h Format um
 *
 * @param $min
 * @return string
 */
function format24h($min)
{
    $val = intval($min);

    return str_pad((($val < 0) ? ceil($val / 60) : floor($val / 60)), 2, '0', STR_PAD_LEFT).':'.str_pad((($val % 60) * (($val < 0) ? -1 : 1)), 2, '0', STR_PAD_LEFT);
}

/**
 * @param $code Passwort oder Coupon
 * @param string $pepper zusätzliche Verschlüsselung mit einem serverseitigen Schlüssel (= Pfeffer). Mit pepper ist der zurückgegebene Hash 108 Zeichen lang, ohne 60 Zeichen!
 * @param array $options individuelles Salt,
 * @return string Hash
 * @deprecated
 * @throws Exception, InvalidArgumentException
 */
function pool_hash_code($code, $pepper = '', array $options = [])
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
        'cost' => 10
    ];

    if (version_compare(PHP_VERSION, '5.3.7') >= 0) {
        $algorithm = '2y'; // BCrypt, mit korrigiertem Unicode Problem
    }
    else {
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
function pool_generate_code($bytes = 10, $parts = 1, array $options = [])
{
    $ascii = array(
        0 => array(48, 57), // 0-9
        1 => array(97, 122) // a-z
    );

    $options += [
        'uppercase' => 1,
        'numbers' => 50,
        'delimiter' => '-'
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

/**
 * @param $pdf Quelle (PDF)
 * @param $jpg Ziel (JPEG)
 * @param $output GS Ausgabe
 * @param int $resolution dpi
 * @param boolean $sudo bei NFS notwendig
 * @return bool Erfolgsstatus
 */
function pdf2jpg($pdf, $jpg, &$output, $resolution = 72, $sudo = false)
{
    # Setzen der Fontmap
    //    GS_FONTMAP=/opt/AVE_Javaserver/EDV_ORG/gs/Fontmap

    # Setzen des Fontdirectories
    //    GS_LIB=/opt/AVE_Javaserver/EDV_ORG/gs/wobla_fonts:/opt/AVE_Javaserver/EDV_ORG/ghostscript-9.06/share/ghostscript/9.06/lib
    //    export GS_LIB

    //    $GS_BIN/gs -dSAFER -dNOPAUSE -dBATCH -dNOPROMPT -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -dAlignToPixels=0 -dGridFitTT=2 -sFONTMAP=$GS_FONTMAP -sDEVICE=jpeg -dNumRenderingThreads=4 -dBufferSpace=300000000 -sOutputFile=$OUTPUT.${RESOLUTION}dpi.jpg -r$RESOLUTION -dUseCropBox $INPUT


    $cmd = ($sudo ? 'sudo ' : '').GHOSTSCRIPT_BIN.' -q  -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -dMaxBitmap=500000000 -dAlignToPixels=0 -dGridFitTT=2 "-sDEVICE=jpeg" -dTextAlphaBits=4 '.
           '-dGraphicsAlphaBits=4 "-r'.$resolution.'x'.$resolution.'" -dUseCropBox "-sOutputFile='.$jpg.'" "-f'.$pdf.'"';
    exec($cmd, $output, $return_var);
    $result = ($return_var == 0 and file_exists($jpg));
    if (!$result and count($output) == 0 and $sudo) {
        $output[] = 'Sudo ist für Ghostscript auf dem Rechner '.$_SERVER['SERVER_NAME'].' nicht konfiguriert!';
    }

    return $result;
}

function getFieldData($array, $column): array
{
    return array_filter($array, function($key) use ($column) {
        return ($key == $column);
    }, ARRAY_FILTER_USE_KEY);
}

/**
 * creates path from last alphanumeric characters
 *
 * @param $chars
 * @param int $numberOfDirectories
 * @return string
 */
function createPathFromLastChars($chars, $numberOfDirectories=4)
{
    $result = '';
    for($i=(-1*$numberOfDirectories); $i<0; $i++) {
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
 *
 */
function nextFreeFilename($dir, $filename, $delimiter='-') {
    if($filename == '') {
        return '';
    }
	$filepath = addEndingSlash($dir) . $filename;
	if (file_exists($filepath)) {

		$info = pathinfo($filepath);
		// echo pray($info);

		$filenameNoExtension = $info['filename'];
		$extension           = $info['extension'];

		$pos = strrpos($filenameNoExtension, $delimiter);
		if ($pos === false) {
			$nr = 1;
			$newFilename = $filenameNoExtension . $delimiter . sprintf('%02d', $nr) . '.' . $extension;
		}
		else {
			$filenameNoNumber =  mb_substr($filenameNoExtension, 0, $pos);
			$nr               =  mb_substr($filenameNoExtension, $pos+1);
			if (is_numeric($nr)) {
				$nr =  intval($nr) + 1;
				$newFilename = $filenameNoNumber . $delimiter . sprintf('%02d', $nr) . '.' . $extension;
			}
			else {
				$nr = 1;
				$newFilename = $filenameNoExtension . $delimiter  . sprintf('%02d', $nr) . '.' . $extension;
			}

		}
		return nextFreeFilename($dir, $newFilename, $delimiter);
	}
	else {
		return $filename;
	}
}

/**
 * Pendant to the .NET API HttpServerUtility.UrlTokenEncode.
 * Encodes a string into its base 64 digit equivalent string representation suitable for transmission in the URL.
 *
 * @param $data
 * @return bool|false|string
 */
function base64url_encode($data)
{
    $data = base64_encode($data);

    $length = strlen($data);
    if($length == 0) return false;

    $numPaddingChars = 0;
    while($data[$length-1] == '=') {
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
 * @return bool|false|string
 */
function base64url_decode($token)
{
    $length = strlen($token);
    if($length == 0) return false;

    $numPaddingChars = (int)$token[$length-1];
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
 * @param int $lastErrorCode
 * @return string
 */
function preg_last_error_message(int $lastErrorCode): string
{
    $errormessage = '';
    switch($lastErrorCode) {
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
    return $string != strip_tags($string);
}

/**
 * Simple test if string is JSON
 *
 * @param string $string
 * @return bool
 */
function isValidJSON(string $string): bool
{
    $literal = substr($string, 0, 1);
    if($literal != '{' and $literal != '[') {
        return false;
    }
    json_decode($string);
    return json_last_error() == JSON_ERROR_NONE;
}

/**
 * convert dash style (or another separator) into camelCase style
 *
 * @param string $string text
 * @param bool $capitalizeFirstCharacter default false
 * @param string $separator default dash
 * @return string
 */
function camelize(string $string, $capitalizeFirstCharacter = false, $separator = '-'): string
{
    $result = str_replace($separator, '', ucwords($string, $separator));
    if($capitalizeFirstCharacter == false) {
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
function decamelize(string $string, $separator = '-'): string
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
        if(is_array($v)) $v = implode(' ', $v);
        return ((is_int($k)) ? $v : $k .'="'. htmlspecialchars($v) .'"');
    }, array_keys($attributes), $attributes));
    if($attributes) $attributes = ' '.$attributes;
    return $attributes;
}

/**
 * calls the system command pdfunite
 *
 * @param array $pdfFiles
 * @param string $pdfOut
 * @return bool
 */
function pdfunite(array $pdfFiles, string $pdfOut): bool
{
    $cmd = 'pdfunite '.implode(' ', $pdfFiles).' '.$pdfOut;
    passthru($cmd);
    return file_exists($pdfOut);
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
    return $d && $d->format($format) == $date;
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
    if($result >= 6) {
        if($operand < 0) {
            $result = 5;
        }
        else {
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
 * @param bool $unsigned without sign
 * @return int
 */
function calcNumberOfDaysInBetween(DateTimeInterface $StartDateTime, DateTimeInterface $EndDateTime, bool $unsigned = true): int
{
    $DateTime1 = clone $StartDateTime;
    $DateTime2 = clone $EndDateTime;
    $DateTime1->setTime(0, 0, 0);
    $DateTime2->setTime(0, 0, 0);
    $days = ($DateTime1->diff($DateTime2)->days ?: 0);
    if($unsigned) {
        return $days;
    }
    if($DateTime1 < $DateTime2) $days *= -1;
    return $days;
}