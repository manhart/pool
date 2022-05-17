<?php
/**
 * Set the mbstring internal encoding to a binary safe encoding when func_overload
 * is enabled.
 * When mbstring.func_overload is in use for multi-byte encodings, the results from
 * strlen() and similar functions respect the utf8 characters, causing binary data
 * to return incorrect lengths.
 * This function overrides the mbstring encoding to a binary-safe encoding, and
 * resets it to the users expected encoding afterwards through the
 * `reset_mbstring_encoding` function.
 * It is safe to recursively call this function, however each
 * `mbstring_binary_safe_encoding()` call must be followed up with an equal number
 * of `reset_mbstring_encoding()` calls.
 *
 * @param bool $reset Optional. Whether to reset the encoding back to a previously-set encoding.
 *                    Default false.
 * @see reset_mbstring_encoding()
 * @staticvar array $encodings
 * @staticvar bool  $overloaded
 * @since 3.7.0
 */
function mbstring_binary_safe_encoding($reset = false)
{
    static $encodings = array();
    static $overloaded = null;

    if (is_null($overloaded)) {
        $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2);
    }

    if (false === $overloaded) {
        return;
    }

    if (!$reset) {
        $encoding = mb_internal_encoding();
        array_push($encodings, $encoding);
        mb_internal_encoding('ISO-8859-1');
    }

    if ($reset && $encodings) {
        $encoding = array_pop($encodings);
        mb_internal_encoding($encoding);
    }
}

/**
 * Reset the mbstring internal encoding to a users previously set encoding.
 *
 * @see mbstring_binary_safe_encoding()
 * @since 3.7.0
 */
function reset_mbstring_encoding()
{
    mbstring_binary_safe_encoding(true);
}

/**
 * Checks to see if a string is utf8 encoded.
 * NOTE: This function checks for 5-Byte sequences, UTF8
 *       has Bytes Sequences with a maximum length of 4.
 *
 * @param string $str The string to be checked
 * @return bool True if $str fits a UTF-8 model, false otherwise.
 * @author bmorel at ssi dot fr (modified)
 * @since 1.2.1
 */
function seems_utf8($str)
{
    mbstring_binary_safe_encoding();
    $length = strlen($str);
    reset_mbstring_encoding();
    for ($i = 0; $i < $length; $i++) {
        $c = ord($str[$i]);
        if ($c < 0x80) {
            $n = 0;
        } // 0bbbbbbb
        elseif (($c & 0xE0) == 0xC0) $n = 1; // 110bbbbb
        elseif (($c & 0xF0) == 0xE0) $n = 2; // 1110bbbb
        elseif (($c & 0xF8) == 0xF0) $n = 3; // 11110bbb
        elseif (($c & 0xFC) == 0xF8) $n = 4; // 111110bb
        elseif (($c & 0xFE) == 0xFC) $n = 5; // 1111110b
        else return false; // Does not match any model
        for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
            if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * splitcsv()
 *
 * @access public
 * @param string $line Text / Zeile
 * @param string $delim Trennzeichen (the delimiter to split by)
 * @param boolean $removeQuotes Sollen Quotes (") vom Ergebnis entfernt werden
 * @return array Aufgeteilte Felder
 **/
function splitcsv($line, $delim=',', $removeQuotes=true, $quote='"')
{
    $fields = array();
    $fldCount = 0;
    $inQuotes = false;
    for ($i = 0; $i < strlen($line); $i++) {
        if (!isset($fields[$fldCount])) $fields[$fldCount] = '';
        $tmp = substr($line, $i, strlen($delim));
        if ($tmp === $delim && !$inQuotes) {
            $fldCount++;
            $i += strlen($delim)-1;
        }
        else if ($fields[$fldCount] == '' && $line[$i] == $quote && !$inQuotes) {
            if (!$removeQuotes) $fields[$fldCount] .= $line[$i];
            $inQuotes = true;
        }
        else if ($line[$i] == $quote) {
            if ($line[$i+1] == $quote) {
                $i++;
                $fields[$fldCount] .= $line[$i];
            }
            else {
                if (!$removeQuotes) $fields[$fldCount] .= $line[$i];
                $inQuotes = false;
            }
        }
        else {
            $fields[$fldCount] .= $line[$i];
        }
    }

    return $fields;
}

/**
 * Dröselt nach Trennzeichen einen String $data auf. Dabei werden Steuerzeichen #10 und #13 berücksichtigt, sowie umschlossener (enclosure) Text
 *
 * @param string $data Inhalt (z.B. einer Datei)
 * @param string $delim Trennzeichen (Standard = ';')
 * @param string $enclosure Umklammerung [optional]
 * @return array mehrdimensional (Zeilen, Felder)
 */
function splitcsvByContent(&$data, $delim=';', $enclosure='"')
{
    $ret_array = array();
    $enclosed = false;
    $fldcount = 0;
    $linecount = 0;
    $fldval = '';
    for ($i = 0; $i < strlen($data); $i++) {
        $chr = $data[$i];
        switch ($chr) {
            case $enclosure:
                if ($enclosed && $data[$i + 1] == $enclosure) {
                    $fldval .= $chr;
                    ++$i; //skip next char
                }
                else $enclosed = !$enclosed;
                break;

            case $delim:
                if (!$enclosed) {
                    $ret_array[$linecount][$fldcount++] = $fldval;
                    $fldval = '';
                }
                else $fldval .= $chr;
                break;

            case "\r":
                if (!$enclosed && $data[$i + 1] == "\n") {
                    continue 2;
                }

            case "\n":
                if (!$enclosed) {
                    $ret_array[$linecount++][$fldcount] = $fldval;
                    $fldcount = 0;
                    $fldval = '';
                }
                else $fldval .= $chr;
                break;

            default:
                $fldval .= $chr;
        }
    }
    if ($fldval) $ret_array[$linecount][$fldcount] = $fldval;
    unset($fldval);

    return $ret_array;
}