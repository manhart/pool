<?php
/**
* RSS
*
* RSS.class.php
*
* Erstellt ein RSS 0.91 Feed.
*
* Follows the RSS 0.91 Spec, revision 3
* http://my.netscape.com/publish/formats/rss-spec-0.91.html
*
* @date $Date: 2006/08/07 11:34:51 $
* @version $Id: RSS.class.php,v 1.2 2006/08/07 11:34:51 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-07-12
* @author Alexander Manhart <alexander@manhart.bayern>
* @link https://alexander-manhart.de
*/

define("RSS_PARSE_SINGLE",                             "single");
define("RSS_PARSE_MULTI",                               "multi");
define("RSS_PARSE_ARRAY",                               "array");

// RSS 0.91 compliant data
define("RSS_PARSED_COMPLIANT",        "RSS 0.91 compliant data");
// Almost compliant RSS : Ex: Devshed
define("RSS_PARSED_ALMOST_COMPLIANT", "*Almost* compliant data");
// RDF files, RSS-0.9-simple and non-compliant RSS
define("RSS_PARSED_SIMPLE",                       "Simple data");
// NOT AN RSS/RDF FILE : no <item> found
define("RSS_PARSED_NOT_RSS",                   "*Not* RSS data");

/**
 * RSS
 *
 * @package pool
 * @author manhart
 * @copyright Copyright (c) 2004
 * @version $Id: RSS.class.php,v 1.2 2006/08/07 11:34:51 manhart Exp $
 * @access public
 **/
class RSS extends PoolObject
{
    //@var array Chanels
    var $channels = array();

    //@var string RSS Typ
    var $rss_type = "";

    //@var array Elemente und deren verfuegbaren Eigenschaften
    var $multiple = array(
        "textinput" => array("title" => "", "description" => "", "name" => "", "link" => ""),
        "image"     => array("title" => "", "url" => "", "link" => "", "width" => "?", "height" => "?", "description" => "?"),
        "item"      => array("title" => "", "link" => "", "description" => "?"),
        "skipHours" => array("hour" => "+"),
        "skipDays"  => array("day" => "+")
    );

    //@var array Channel Attribute
    var $channelsElements1 = array(
        "title" => "",
        "description" => "",
        "link" => "",
        "language" => "",
        "rating" => "?",
        "copyright" => "?",
        "pubDate" => "?",
        "lastBuildDate" => "?",
        "docs" => "?",
        "managingEditor" => "?",
        "webMaster" => "?"
    );

    //@var array Channel Attribute
    var $channelsElements2 = array(
        "image" => "?",
        "item" => "?",
        "textinput" => "?"
    );

    //@var array Channel Attribute
    var $channelsElements3 = array(
        "skipHours" => "?",
        "skipDays" => "?"
    );

    var $error = false;

    function clearChannels()
    {
        while (!is_null(array_shift($this -> channels)));
    }

    /**
     * RSS::RSS()
     *
     * Constructor
     *
     * @access public
     * @param string $data XML (RSS feed) Daten (kein Pflichtparameter)
     **/
    function __construct($data = "")
    {
        if (empty($data)) {
            $this -> clearChannels();
        }
        else {
            $error = $this -> _parseRSS($data);
            if (! PoolObject::isError($error)) {
                $this->rss_type = RSS_PARSED_COMPLIANT;
            } else {
                $this->channelsElements1["language"]="?";
                $this->clearChannels();
                $error=$this->_parseRSS($data);

                if (! PoolObject::isError($error)) {
                    $this->rss_type = RSS_PARSED_ALMOST_COMPLIANT;
                } else {
                    $this->clearChannels();
                    $error=$this->_parseElement($data, "item", "+", RSS_PARSE_MULTI);

                    if (! PoolObject::isError($error)) {
                        $this->channels[0]["item"] = $error;
                        $this->rss_type = RSS_PARSED_SIMPLE;
                    } else {
                        $this->clearChannels();
                        $this->rss_type = RSS_PARSED_NOT_RSS;
                    }
                }
            }
        }
    }

    /**
     * RSS::_parseRSS()
     *
     * @access private
     * @param string $data Zu parsende XML Daten
     **/
    function _parseRSS($data = "")
    {
        $ereg="/<rss[ \n\r\t]+version=\"0.91\"[^>]*>(.+?)<\/rss>/is";
        preg_match_all($ereg,$data,$rss,PREG_PATTERN_ORDER);
        switch(count($rss[1])) {
        case 0:
            $this->error = new RSS_Error("No RSS element found in incoming data");
            return $this->error;
            break;
        case 1:
            $ereg="/<channel>(.+?)<\/channel>/is";
            preg_match_all($ereg,$rss[1][0],$channels,PREG_PATTERN_ORDER);

            if(count($channels[1])==0) {
                $this->error = new RSS_Error("No channels in RSS data");
                return $this->error;
            }
            $c=0;
            foreach($channels[1] as $data) {
                $info = preg_replace ("/<item>(.+?)<\/item>/s","",$data);
                $info = preg_replace ("/<image>(.+?)<\/image>/s","",$info);
                $info = preg_replace ("/<textinput>(.+?)<\/textinput>/s","",$info);
                $a = array();
                foreach($this->channelsElements1 AS $key=>$val) {
                    $elem = $this->_parseElement($info, $key, $val, RSS_PARSE_SINGLE);
                    if (PoolObject::isError($elem)) {
                        return $elem;
                    } else {
                        $a[$key] = $elem;
                    }
                }
                foreach($this->channelsElements2 AS $key=>$val) {
                    $elem = $this->_parseElement($data, $key, $val, RSS_PARSE_MULTI);
                    if (PoolObject::isError($elem)) {
                        return $elem;
                    } else {
                        $a[$key] = $elem;
                    }
                }
                foreach($this->channelsElements3 AS $key=>$val) {
                    $elem = $this->_parseElement($info, $key, $val, RSS_PARSE_ARRAY);
                    if (PoolObject::isError($elem)) {
                        return $elem;
                    } else {
                        $a[$key] = $elem;
                    }
                }
                $this->channels[$c] = $a;
                $c++;
            }
            break;
        default:
            $this->error = new RSS_Error("More than one RSS element found in  incoming data");
            return $this->error;
            break;
        }
        return 0;
    }

    /**
     * RSS::_parseElement()
     *
     * Parst ein Element.
     *
     * @access private
     * @param string $data
     * @param string $element
     * @param string $statut
     * @param $parseMode
     * @return
     **/
    function _parseElement($data = "", $element = "", $statut = "", $parseMode)
    {
        $error=0;
        $ereg="/<".$element.">(.*?)<\/".$element.">/is";
        preg_match_all($ereg,$data,$matchElement,PREG_PATTERN_ORDER);
        switch ($parseMode) {
            case RSS_PARSE_SINGLE:
                $returnValue = "";
                break;
            case RSS_PARSE_MULTI:
            case RSS_PARSE_ARRAY:
                $returnValue = array();
                break;
            default:
                $this->error = new RSS_Error("Unknown parse mode : ".$parseMode);
                return $this->error;
                break;
        }
        $count=count($matchElement[1]);
        switch($count) {
        case 0:
            switch ($statut) {
                case "?":
                    return $returnValue;
                    break;
                case "+":
                    $this->error = new RSS_Error("Element missing : &lt;".$element."&gt;");
                    return $this->error;
                    break;
                default:
                    $this->error = new RSS_Error("Element missing : &lt;".$element."&gt;");
                    return $this->error;
                    break;
            }
            break;
        default:
            if ($parseMode==RSS_PARSE_SINGLE) {
                if ($count==1) {
                    return $matchElement[1][0];
                } else {
                    if ($statut=="+") {
                        return $matchElement[1];
                    } else {
                        $this->error = new RSS_Error("more than one element : &lt;".$element."&gt; found");
                        return $this->error;
                    }
                }
            } else {
                $c=0;
                foreach($matchElement[1] AS $value) {
                    $elements=$this->multiple[$element];
                    foreach($elements AS $key=>$val) {
                        if ($parseMode==RSS_PARSE_MULTI) {
                            $elem = $this->_parseElement($value,$key,$val,RSS_PARSE_SINGLE);
                            if (PoolObject::isError($elem)) {
                                return $elem;
                            } else {
                                $returnValue[$c][$key]=$elem;
                            }
                        } else {
                            $elem = $this->_parseElement($value,$key,$val,RSS_PARSE_SINGLE);
                            if (PoolObject::isError($elem)) {
                                return $elem;
                            } else {
                                $returnValue=$elem;
                            }
                        }
                    }
                    $c++;
                }
                return $returnValue;
            }
            break;
        }
    }

    /**
     * RSS::getCount()
     *
     * Anzahl Channels
     *
     * @return Anzahl Kanaele
     **/
    function getCount()
    {
        return count($this -> channels);
    }

    /**
     * RSS::getType()
     *
     * Liefert RSS Typ
     *
     * @return
     **/
    function getType()
    {
        return $this -> rss_type;
    }

    /**
     * RSS::getAllItems()
     *
     * @return
     **/
    function getAllItems()
    {
        $a = array();
        for ($i=0 ; $i < $this -> getCount(); $i++) {
            $a = array_merge($a, $this -> channels[$i]["item"]);
        }
        return $a;
    }

    /**
     * RSS::createChannel()
     *
     * Erzeugt einen Channel
     *
     * @access public
     * @param array $array Channel Daten (title, description, etc.)
     * @return Anzahl Channels
     **/
    function createChannel($array)
    {
        $count = count($this -> channels);
        foreach($this -> channelsElements1 AS $key => $value) {
            $this -> channels[$count][$key] = "";
        }
        foreach($this -> channelsElements2 AS $key => $value) {
            $this -> channels[$count][$key] = array();
        }
        foreach($this -> channelsElements3 AS $key => $value) {
            $this -> channels[$count][$key] = array();
        }
        foreach($array AS $key => $value) {
            if (isset($this -> channels[$count][$key]) and is_string($this -> channels[$count][$key])) {
                $this -> channels[$count][$key] = $value;
            }
        }
        return $count;
    }

    /**
     * RSS::addElement()
     *
     * Fuegt ein Element bei. Z.B. image, textinput oder item.
     *
     * @access public
     * @param string $element "image", "textinput" oder "item"
     * @param array $array Eigenschaften zum Element z.B. title, description, link, etc.
     * @param string $chan Channel Index
     **/
    function addElement($element, $array, $chan="0")
    {
        switch($element) {
            case "image":
            case "textinput":
            case "item":
                $this -> channels[$chan][$element][] = $array;
                break;

            case "skipHours":
            case "skipDays":
                $this -> channels[$chan][$element] = $array;
                break;

            default:
                break;
        }
    }

    /**
     * RSS::export()
     *
     * Exportiert RSS Datei
     *
     * @access public
     * @return XML (RSS feed)
     **/
    function export()
    {
        $xml=<<<EOS
<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE rss PUBLIC "-//Netscape Communications//DTD RSS 0.91//EN" "http://my.netscape.com/publish/formats/rss-0.91.dtd">
<rss version="0.91">\n
EOS;

        for ($chan=0 ; $chan < $this -> getCount(); $chan++) {
            $xml .= "<channel>\n";
            foreach($this -> channelsElements1 AS $key => $val) {
                if ($val == "" or ($val == "?" and !empty($this -> channels[$chan][$key]))) {
                    $xml .= "<".$key.">";
                    $xml .= htmlentities($this -> channels[$chan][$key], ENT_QUOTES);
                    $xml .= "</".$key.">\n";
                }
            }
            foreach($this -> channelsElements2 AS $key => $val) {
                if ($val=="+" or ($val == "?" and count($this -> channels[$chan][$key]) > 0)) {
                    foreach($this -> channels[$chan][$key] AS $item) {
                        $xml .= "<".$key.">\n";
                        foreach($item AS $eltkey => $eltval) {
                            $xml.= "  <".$eltkey.">";
                            $xml.= htmlentities($eltval, ENT_QUOTES);
                            $xml.= "</".$eltkey.">\n";
                        }
                        $xml .= "</".$key.">\n";
                    }
                }
            }
            foreach($this -> channelsElements3 AS $key=>$val) {
                switch($key) {
                    case "skipHours": $data="hour"; break;
                    case "skipDays":  $data="day";  break;
                    default:          $data="";     break;
                }
                if ($val == "?" and count($this -> channels[$chan][$key]) > 0) {
                    $xml .= "<".$key.">\n";
                    $c=0;
                    foreach($this -> channels[$chan][$key] AS $eltval) {
                        $xml .= "  <".$data.">";
                        $xml .= htmlentities($eltval, ENT_QUOTES);
                        $xml .= "</".$data.">\n";
                        $c++;
                    }
                    $xml.="</".$key.">\n";
                }
            }
            $xml .= "</channel>\n";
        }
        $xml .= "</rss>";
        return $xml;
    }


    /**
     * RSS::encodeXmlEntities()
     *
     * Escape XML entities
     *
     * @access public
     * @param string $xml Text string to escape
     * @return string xml
     **/
    function encodeXmlEntities($xml)
    {
        $xml = str_replace(array('�', '�', '�',
                                 '�', '�', '�',
                                 '�', '<', '>',
                                 '"', '\''
                                ),
                           array('&#252;', '&#220;', '&#246;',
                                 '&#214;', '&#228;', '&#196;',
                                  '&#223;', '&lt;', '&gt;',
                                  '&quot;', '&apos;'
                                ),
                           $xml
                          );

        $xml = preg_replace(array("/\&([a-z\d\#]+)\;/i",
                                  "/\&/",
                                  "/\#\|\|([a-z\d\#]+)\|\|\#/i",
                                  "/([^a-zA-Z\d\s\<\>\&\;\.\:\=\"\-\/\%\?\!\'\(\)\[\]\{\}\$\#\+\,\@_])/e"
                                 ),
                            array("#||\\1||#",
                                  "&amp;",
                                  "&\\1;",
                                  "'&#'.ord('\\1').';'"
                                 ),
                            $xml
                           );

        return $xml;
    }

    /**
     * RSS::decodeXmlEntities()
     *
     * Decode XML entities in a text string.
     *
     * @access public
     * @param string $xml Text to decode
     * @return string Decoded text
     **/
    function decodeXmlEntities($xml)
    {
        static $xml_trans_tbl = null;
        if (!$xml_trans_tbl) {
            $xml_trans_tbl = get_html_translation_table(HTML_ENTITIES);
            $xml_trans_tbl = array_flip($xml_trans_tbl);
        }
        for ($i = 1; $i <= 255; $i++) {
            $ent = sprintf("&#%03d;", $i);
            $ch = chr($i);
            $xml = str_replace($ent, $ch, $xml);
        }

        return strtr($xml, $trans_tbl);
    }
}