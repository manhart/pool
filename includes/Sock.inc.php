<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 * $HeadURL$
 * Erweiterung zur Utils.inc.php: Sockets
 *
 * @version $Id$
 * @version $Revision$
 * @version $Author$
 * @version $Date$
 * @since 2007-09-19
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 **/

/**
 * Externer POST von Daten an einen Webserver (ueber fsockopen).
 * Bsp.:
 * $data = "pid=14&poll_vote_number=2";
 * $x = PostToHost(
 *               "www.linux.com",
 *               "/polls/index.phtml",
 *               "http://www.linux.com/polls/index.phtml?pid=14",
 *               $data
 *
 * @param string $host Hostname
 * @param integer $port Port
 * @param string $path Pfad
 * @param string $referer komplette Url (HTTP  Referer)
 * @param string $data_to_send Zu sendende Parameter (z.B. userid=5&action=list)
 * @param integer $timeout 30 Sekunden
 * @return string|boolean
 **/
function PostToHost($host, $port = 80, $path, $referer, $data_to_send, $timeout = 30)
{
    $res = '';

    $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        echo $errno.'-'.$errstr;
        return false;
    } else {
        $out = "POST $path HTTP/1.1\r\n";
        $out .= "Host: $host\r\n";
        if ($referer != '') $out .= "Referer: $referer\r\n";
        $out .= "Content-type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-length: ".strlen($data_to_send)."\r\n";
        $out .= "Connection: close\r\n\r\n";
        fwrite($fp, $out);
        fwrite($fp, $data_to_send);

        while (!feof($fp)) {
            $res .= fgets($fp, 128);
        }
        fclose($fp);
    }

    return $res;
}

/**
 * Sendet eine Anfrage an einen entfernten Rechner via der Methode GET
 *
 * @param $host
 * @param int $port
 * @param $path
 * @param $errno Fehlernummer Socket
 * @param $errstr Fehlertext Socket
 * @param string $extra
 * @param int $timeout
 * @return bool|string
 */
function getFromHost($host, $port = 80, $path, &$errno, &$errstr, $extra = '', $timeout = 45)
{
    $res = '';

    $fp = fsockopen($host, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        // echo $errno.'-'.$errstr;
        return false;
    } else {
        $out = "GET $path HTTP/1.1\r\n";
        $out .= "Host: $host:$port\r\n";
        $out .= $extra;
        $out .= "Connection: close\r\n\r\n";
        fwrite($fp, $out);

        while (!feof($fp)) {
            $res .= fgets($fp, 1024);
        }

        fclose($fp);
    }

    return $res;
}

/**
 * Sendet eine Anfrage an einen entfernten Rechner via der Methode GET
 *
 * @param string $host Zielrechner
 * @param string $path Quellpfad
 * @return string
 */
//	function GetFromHost($host, $path, $extra='')
//	{
//	    $fp = fsockopen($host, 80, $errno, $errstr, 30);
//	    if(!$fp) {
//	    	die ("$errstr ($errno)<br />\n");
//	    }
//	    $headtosend = "GET $path HTTP/1.1\r\n";
//	    $headtosend .= "Host: $host\r\n";
//	    $headtosend .= "Connection: Close\r\n";
//	    $headtosend .= $extra;
//		#$headtosend .= "Referer: http://www.softidea.de\r\n";
//		#$headtosend .= "Accept: image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/vnd.ms-excel, application/vnd.ms-powerpoint, application/msword, application/x-ms-application, application/x-ms-xbap, application/vnd.ms-xpsdocument, application/xaml+xml, application/x-silverlight, */*\r\n";
//	    $headtosend .= "Accept-Language: de\r\n";
//		#$headtosend .= "User-Agent: Softidea/1.0.0 (+http://www.softidea.de)\r\n";
//	    $headtosend .= "\r\n";
//
//		# echo nl2br($headtosend).'<hr>';
//
//	    fwrite($fp, $headtosend);
//	    $res = '';
//	    while(!feof($fp)) {
//			$res .= fgets($fp, 1024);
//	    }
//	    fclose($fp);
//
//	    return $res;
//	}


/**
 * Link überprüfen
 *
 * @param string $url
 * @param int $timeout
 * @param boolean $onlyStatusCode
 * @param string $err
 * @return false|array|int
 */
function linkCheck($url, $timeout = 30, $onlyStatusCode = false, $err = '')
{
    $url = trim($url);

    if (strpos($url, '://') === false) $url = 'http://'.$url;

    $url = parse_url($url);

    if (!isset($url['port'])) $url['port'] = 80;
    if (!isset($url['path'])) $url['path'] = '/';

    $errno = 0;
    $errstr = '';
    $fp = @fsockopen($url['host'], $url['port'], $errno, $errstr, $timeout);
    if (!$fp) {
        $err = $errstr.' ('.$errno.')<br>'."\n";
        return false;
    } else {
        $head = '';
        //socket_set_timeout($fp, $timeout, 0);

        /* HEAD is the same as GET but returns only HTTP headers and no document body.  */
        $httpRequest = "HEAD ".$url['path']." HTTP/1.1\r\nHost: ".$url['host']."\r\nConnection: close\r\n\r\n";
        // echo 'request: ' . $httpRequest . '<br>';
        fputs($fp, $httpRequest);
        while (!feof($fp)) {
            $head .= fgets($fp, 1024);
        }
        fclose($fp);

        preg_match("=^(HTTP/\d+\.\d+) (\d{3}) ([^\r\n]*)=", $head, $matches);
        $http['Status-Line'] = $matches[0];
        $http['HTTP-Version'] = $matches[1];
        $http['Status-Code'] = $matches[2];
        $http['Reason-Phrase'] = $matches[3];
    }
    /*
          Status-Code    = "100"   ; Continue
                         | "101"   ; Switching Protocols
                         | "200"   ; OK
                         | "201"   ; Created
                         | "202"   ; Accepted
                         | "203"   ; Non-Authoritative Information
                         | "204"   ; No Content
                         | "205"   ; Reset Content
                         | "206"   ; Partial Content
                         | "300"   ; Multiple Choices
                         | "301"   ; Moved Permanently
                         | "302"   ; Moved Temporarily

                         | "303"   ; See Other
                         | "304"   ; Not Modified
                         | "305"   ; Use Proxy
                         | "400"   ; Bad Request
                         | "401"   ; Unauthorized
                         | "402"   ; Payment Required
                         | "403"   ; Forbidden
                         | "404"   ; Not Found
                         | "405"   ; Method Not Allowed
                         | "406"   ; Not Acceptable
                         | "407"   ; Proxy Authentication Required
                         | "408"   ; Request Time-out
                         | "409"   ; Conflict
                         | "410"   ; Gone
                         | "411"   ; Length Required
                         | "412"   ; Precondition Failed
                         | "413"   ; Request Entity Too Large
                         | "414"   ; Request-URI Too Large
                         | "415"   ; Unsupported Media Type
                         | "500"   ; Internal Server Error
                         | "501"   ; Not Implemented
                         | "502"   ; Bad Gateway
                         | "503"   ; Service Unavailable
                         | "504"   ; Gateway Time-out
                         | "505"   ; HTTP Version not supported
                         | extension-code
    */

    if ($onlyStatusCode) return $http['Status-Code'];

    $rclass = [
        'Informational',    /* 1xx */
        'Success',            /* 2xx */
        'Redirection',        /* 3xx */
        'Client Error',        /* 4xx */
        'Server Error',        /* 5xx */
    ];
    $http['Response-Class'] = $rclass[$http['Status-Code'][0] - 1];

    preg_match_all("=^(.+): ([^\r\n]*)=m", $head, $matches, PREG_SET_ORDER);
    foreach ($matches as $line) {
        $http[$line[1]] = $line[2];
    }

    if ($http['Status-Code'][0] == 3) {
        if (!isset($http['Location'])) $http['Location'] = $http['location'];
        if ($http['Status-Code'] == 302) {
            //echo $http['Status-Code'];
            $http['Location'] = $url['host'].'/'.$http['Location'];
        }
        $http['Location-Status-Code'] = linkCheck($http['Location'], $timeout, true, $err);
    }

    return $http;
}