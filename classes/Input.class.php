<?php
/**
* -= Rapid Module Library (RML) =-
*
* Input.class.php
*
* Eine Klasse fuer die PHP "Superglobals". Die Klasse Input sammelt alle verfuegbaren Superglobals-Variablen, die von PHP geliefert werden.
* Damit wird die Verwendung von "register_globals" unterbunden (Sicherheit geht vor)!
* Ausserdem sorgt sich die Klasse automatisch um magic_quotes und vereinfacht den Zugriff auf Variablen.
*
* Weitere Features:
* Xor Verschluesslung
* Byte Streams
* Referenz-Container
*
* Falls die PHP Entwickler auf den Gedanken kommen, $_GET $_POST etc. umzubenennen, hat man ein Leichtes, denn wir definieren folgende Konstanten:
* INPUT_EMPTY, INPUT_COOKIE, INPUT_GET, INPUT_POST, INPUT_FILES, INPUT_ENV, INPUT_SERVER, INPUT_SESSION, INPUT_REQUEST, INPUT_ALL
*
* Zudem leiten wir von Input extra fuer jede Superglobale eine Klasse ab:
* ICookie, IGet, IPost, IFiles, IEnv, IServer, ISession, IRequest
*
* @date $Date: 2007/08/06 12:18:23 $
* @version $Id: Input.class.php,v 1.14 2007/08/06 12:18:23 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-07-10
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
*/

// define PHP Superglobals
define ('I_EMPTY', 0);
define ('I_COOKIE', 1);
define ('I_GET', 2);
define ('I_POST', 4);
define ('I_FILES', 8);
define ('I_ENV', 16);
define ('I_SERVER', 32);
define ('I_SESSION', 64);
define ('I_REQUEST', 128);
define ('I_ALL', 255);


/**
 * Basisklasse fuer alle Inputs. Input kapselt den Zugriff auf die PHP Superglobals wie $_GET, $_POST etc.
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Input.class.php,v 1.14 2007/08/06 12:18:23 manhart Exp $
 * @access public
 **/
class Input extends PoolObject
{
    /**
     * Variablen Container
     *
     * @var array
     * @access public
     */
    var $Vars = array();

    /**
     * @var int Superglobals
     * @see https://www.php.net/manual/de/language.variables.superglobals.php
     */
    private int $superglobals = I_EMPTY;

    /**
     * Input constructor. Initialization of the superglobals.
     * @param int $superglobals Select a predefined constant: INPUT_GET, INPUT_POST, INPUT_REQUEST, INPUT_SERVER, INPUT_FILES, INPUT_COOKIE
     * @see https://www.php.net/manual/de/language.variables.superglobals.php
     */
    public function __construct($superglobals = I_EMPTY)
    {
        $this->Vars = Array();

        $this->init($superglobals);
    }

    /**
    * Initialisiert gewaehlte Superglobals und schreibt die Variablen in den internen Variablen Container.
    * Falls Magic Quotes eingestellt sind, werden bei den $_GET und $_POST Superglobals alle Escape Zeichen entfernt.
    * Aussnahme: Session! Die Superglobale Variable $_SESSION wird zum internen Container referenziert!
    *
    * @access public
    * @param integer $superglobals Einzulesende Superglobals (siehe Konstanten)
    */
    function init($superglobals = I_EMPTY)
    {
        if ($superglobals == 0) {
            return;
        }
        $this -> superglobals = $superglobals;

        if ($superglobals & I_ENV) { // I_ENV
            $this->addVar($_ENV);
        }

        if ($superglobals & I_SERVER) { // I_SERVER
            $this->addVar($_SERVER);
        }

        if ($superglobals & I_REQUEST) {
            $this->addVar($_REQUEST);
        }

        if ($superglobals & I_FILES) {
            $this->addVar($_FILES);
        }

        if ($superglobals & I_POST) {
            $this->addVar($_POST);
        }

        if ($superglobals & I_GET) {
            $this->addVar($_GET);
        }

        if ($superglobals & I_COOKIE) {
            $this->addVar($_COOKIE);
        }

        if ($superglobals != I_ALL and $superglobals & I_SESSION) {
            $this->Vars = &$_SESSION; // PHP Session Handling (see php manual)
            //$this -> addVar($_SESSION);
        }
    }

    /**
    * Enternt das Escape Zeichen \ (Backslash).
    *
    * @access public
    * @param string $value Wert
    * @return string Wert ohne Escape Zeichen
    */
    function stripSlashes($value)
    {
        if (is_array($value)) {
            $buf = array();

            reset($value);
            while(list($key, $val) = each($value)) {
                if (is_array($val)) {
                    $buf[$key] = $this -> stripSlashes($val);
                }
                else {
                    $buf[$key] = stripslashes($val);
                }
            }

            return $buf;
        }
        else {
            return stripslashes($value);
        }
    }

    /**
    * Reinitialisiert Superglobals.
    *
    * @access public
    */
    function reInit()
    {
        /*$this->clear(); vermeiden, da clear sich auch in ISession beim Leeren der Session auswirkt */
        $this->Vars = array();
        $this->init($this->superglobals);
    }

    /**
     * Prueft, ob eine Variable ueberhaupt gesetzt wurde.
     *
     * @param string $key Name der Variable
     * @return boolean True=ja; False=nein
     **/
    function exists($key)
    {
        return array_key_exists($key, $this->Vars);
    }

    /**
     * Prueft, ob eine Variable einen Wert enthaelt.
     * Diese Funktion liefert TRUE, wenn eine Variable nicht definiert, leer oder gleich 0 ist, ansonsten FALSE
     *
     * @access public
     * @param string $key Name der Variable
     * @return boolean True=ja; False=nein
     **/
    function emptyVar($key)
    {
        if(is_array($key)) {
            if(sizeof($key) == 0) return true;
            foreach ($key as $k => $v) {
                if(empty($key[$k])) {
                    return true;
                }
            }
            return false;
        }
        else {
            return (!isset($this->Vars[$key]) or empty($this->Vars[$key]));
        }
    }

    /**
     * Liefert einen Boolean zurück, ob alle Daten innerhalb des Inputs leer sind
     *
     * @access public
     * @return boolean
     */
    function emptyVars()
    {
        return $this->emptyVar(array_keys($this->Vars));
    }

    /**
    * Liefert den Wert fuer den uebergebenen Schluessel.
    *
    * @access public
    * @param string $key Name der Variable
    * @param mixed|null $default return default value, if key is not set
    * @return string Wert der Variable oder NULL, wenn die Variable nicht existiert
    */
    function getVar($key, $default=null)
    {
        return isset($this->Vars[$key]) ? $this->Vars[$key] : $default;
    }

    /**
    * Liefert die Referenz fuer den uebergebenen Schluessel.
    *
    * @access public
    * @param string $key Name der Variable
    * @param mixed|null $default return default value, if key is not set
    * @return object Referenz auf das Objekt oder NULL, wenn das Objekt nicht existiert
    */
    function &getRef($key, $default=null)
    {
        $ref = $default;
        if(isset($this -> Vars[$key])) {
            $ref = &$this -> Vars[$key];
        }
        return $ref;
    }

    /**
    * Setzt eine Variable im internen Container.
    * Im Unterschied zu Input::addVar ueberschreibt Input::setVar alle Variablen.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $value Wert der Variable
    */
    function setVar($key, $value = '')
    {
//			if (!is_array($key)) {
        if((array)$key !== $key) { // 20.05.2015, AM, is_array ist langsamer als den Datentyp zu casten und auf exakte Gleichheit zu ueberpruefen
            $this->Vars[$key] = $value;
        }
        else {
            //$this -> Varsarray_merge($key, $this -> Vars);
            $this->Vars = $key + $this->Vars;
        }
    }

    /**
    * Legt eine Referenz eines Objekts im internen Container ab.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $value Referenz auf die Variable (oder Objekt)
    */
    function setRef($key, & $value)
    {
        $this->Vars[$key] = &$value;
    }

    /**
    * Setzt eine Variable im internen Container.
    * Im Unterschied zu Input::setVar ueberschreibt Input::addVar keine bereits vorhanden Variablen.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $value Wert der Variable
    * @return boolean Erfolgsstatus
    */
    function addVar($key, $value = '')
    {
        if (!is_array($key)) {
            if (!isset($this -> Vars[$key])) {
                $this->Vars[$key] = $value;
            }
        }
        else {
            $this->Vars = $this->Vars + $key;
        }
        return true;
    }

    /**
    * Setzt eine Variable im internen Container. Symlink auf Input::setRef().
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable/Objekt)
    * @param string $value Referenz auf die Variable (oder Objekt)
    */
    function addRef($key, & $value)
    {
        $this->setRef($key, $value);
    }

    /**
    * Loescht eine Variable aus dem internen Container.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    */
    function delVar($key)
    {
        if (!is_array($key)) {
            unset($this->Vars[$key]);
        }
        else {
            foreach($key as $kname) {
                unset($this->Vars[$kname]);
            }
        }
    }

    /**
    * Loescht eine Referenz aus dem internen Container. SymLink auf Input::delVar().
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    */
    function delRef($key)
    {
        $this->delVar($key);
    }

    /**
    * Diese Funktion lieferten den Typ der Variablen mit dem uebergebenen Schluesselnamen $key.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @return string Typen (set of "integer", "double", "string", "array", "object", "unknown type") oder false, wenn die Variable nicht gesetzt ist.
    */
    function getType($key)
    {
        return isset($this -> Vars[$key]) ? gettype($this -> Vars[$key]) : false;
    }

    /**
    * Aendert den Typ einer Variable im internen Container.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $type Datentyp (siehe getType)
    * @see Input::getType()
    * @return boolean Erfolgsstatus
    */
    function setType($key, $type)
    {
        $result = false;
        if (isset($this -> Vars[$key])) {
            $result = settype($this->Vars[$key], $type);
        }
        return $result;
    }

    /**
    * Liefert eine verschluesselte Variable entschluesselt zurueck.
    * Dekodiert vor der Entschluesslung den Wert (MIME base64).
    *
    * @param string Name der Variable
    * @param string $name Schluessel
    * @return string $securekey Wert der Variable (entschluesselt)
    */
    function getDecryptedVar($name, $securekey)
    {
        // Call Xor Algo.
        $decoded_data = base64_decode($this -> getVar($name));
        $decrypted_data = $this -> xorEnDecryption($decoded_data, $securekey);
        return $decrypted_data;
    }

    /**
    * Setzt eine Variable und verschluesselt deren Wert anhand eines Schluessels.
    * Abschliessend wird der verschluesselte Wert kodiert (MIME base64).
    *
    * @access public
    * @param string $name Name der Variable
    * @param string $value Wert der Variable
    * @param string $securekey Schluessel
    */
    function setEncryptedVar($name, $value, $securekey)
    {
        // Call Xor Algo.
        $encrypted_data = $this->xorEnDecryption($value, $securekey);
        $encoded_data = base64_encode($encrypted_data);
        $this->setVar($name, $encoded_data);
    }

    /**
     * Filtert die Daten. Dabei bleiben nur die Felder (Schluessel) uebrig, die uebergeben wurden.
     * Sehr praktisch beim Einfuegen von Daten in eine Datenbank. Man kann so unnuetze Daten entfernen.
     *
     * @param array $keys_must_exists Felder, die bestehen bleiben muessen
     * @param string $prefix fieldnames with prefix
     * @param boolean $removePrefix removes prefix
     * @return Input Neues Objekt vom Typ Input (enthaelt die gefilterten Daten)
     **/
    function &filter($keys_must_exists, $prefix='', $removePrefix=false)
    {
        $Input = new Input(I_EMPTY);
        if (is_array($keys_must_exists)) {
            $new_prefix = ($removePrefix) ? '' : $prefix;
            foreach($keys_must_exists as $key) {
                // AM, 22.04.09, modified (isset nimmt kein NULL)
                if(array_key_exists($prefix.$key, $this->Vars)) {
                    $Input->setVar($new_prefix.$key, $this->Vars[$prefix.$key]);
                }
            }
        }
        return $Input;
    }

    /**
     * Setzt die Daten f�r Input.
     *
     * @access public
     * @param array $data Indexiertes Array, enth�lt je Satz ein assoziatives Array
     **/
    function setData($data)
    {
        $this->Vars = $data;
    }

    /**
     * Liefert ein assoziatives Array mit allen Daten des Input Objekts zureck
     *
     * @return array Daten
     **/
    function getData()
    {
        return $this->Vars;
    }

    /**
     * Liefert alle Werte als kompletten String zurück
     *
     * @param string $delimiter Trenner
     * @return string
     */
    function getValuesAsString($delimiter)
    {
        $result = '';
        foreach ($this->Vars as $key => $val) {
            if($result != '') $result .= $delimiter;
            $result .= $val;
        }
        return $result;
    }

    /**
     * Benennt eine Variable um
     *
     * @param string $keyname Schluesselname
     * @param string $new_keyname Neuer Schluesselname
     */
    function rename($keyname, $new_keyname)
    {
        if($this->exists($keyname)) {
            $this->setVar($new_keyname, $this->Vars[$keyname]);
            $this->delVar($keyname);
        }
    }

    /**
     * Bennent mehrere Variablen um
     *
     * @param array $keynames
     */
    function renameKeys($keynames)
    {
        foreach ($keynames as $key => $value) {
            $this->rename($key, $value);
        }
    }

    /**
     * Ermittelt die Unterschiede von Input zu einem Array
     *
     * @param array $array
     * @return array
     */
    function diff($array)
    {
        return array_diff($this->Vars, $array);
    }

    /**
     * Berechnet den Unterschied zwischen Arrays mit zus�tzlicher Indexpr�fung
     *
     * @param unknown_type $array
     * @return unknown
     */
    function diff_assoc($array)
    {
        return array_diff_assoc($this->Vars, $array);
    }

    /**
    * Bekannte, einfache, veraltete bitweise XOR Verschluesslung.
    * Die Funktion dient lediglich zum Verschleiern von Variablenwerten.
    * Fuer sicherheitsrelevante Daten nicht geeignet!
    *
    * @access public
    * @param string $value Zu verschluesselnder Wert
    * @param string $securekey Schluessel
    * @return string Verschluesselter Wert
    */
    function xorEnDecryption($value, $securekey)
    {
        if ($value == '' or $securekey == '') {
            return $value;
        }

        $new_value = '';

        $skey_len = strlen($securekey);
        $value_len = strlen($value);

        $v = 0;
        $k = 0;
        while($v < $value_len){
            $k = $v % $skey_len;
            $new_value .= chr(ord($value[$v]) ^ ord($securekey[$k]));
            $v++;
        }
        return $new_value;
    }

    /**
    * Gibt in einer Zeichenkette (String) einen Byte-Stream aller Variablen zurueck.
    * Hinweis: serialize() kann mit den Typen integer, double, string, array (mehrdimensional) und object umgehen.
    * Beim Objekt werden die Eigenschaften serialisiert, die Methoden gehen aber verloren.
    *
    * @access public
    * @return string Byte-Stream
    */
    function getByteStream()
    {
        return serialize($this->Vars);
    }

    /**
    * Importiert einen Byte-Stream im internen Container.
    *
    * @access public
    * @return boolean Ergebnis
    */
    function setByteStream($bytestream)
    {
        $buf = unserialize($bytestream);
        return $this->addVar($buf);
    }

    /**
    * Die Funktion dumpVars verwendet eine globale Funktion "pray" (Utils.inc.php).
    *
    * @access public
    * @param boolean $print Ausgabe auf dem Schirm (Standard true)
    * @param string $key Schluessel (bzw. Name einer Variable). Wird kein Name angegeben, werden alle Variablen des internen Containers ausgegeben.
    * @return string Dump aller Variablen im internen Container
    * @see pray()
    */
    function dumpVars($print = true, $key = '')
    {
        $output = '';
        if (!empty($key)) {
            $output = pray ($this -> getVar($key));
        }
        else {
            $output = pray($this -> Vars);
        }

        if ($print) {
            print ($output);
        }
        return $output;
    }

    /**
    * Fuegt Parameter (z.B. von einer Url) in den internen Container ein. Uebergabeformat: key=value&key=value (dabei k�nnen = und & auch durch \ maskiert werden)
    *
    * @access public
    * @param string $params Siehe oben Beschreibung
    * @param boolean $translate_specialchars Konvertiert HTML-Code (besondere Zeichen) in standardmaessigen Zeichensatz.
    */
    function setParams($params, $translate_specialchars = true)
    {
        if (strlen($params) > 0) {
            if ($translate_specialchars) {
                # &amp; => &
                $trans = get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES);
                $trans = array_flip($trans);
                $params = strtr($params, $trans);
            }

            $arrParams = preg_split('/(?<!\\\)&/', $params);
            //$arrParams = explode('&', $params);
            for ($i=0; $i < count($arrParams); $i++) {
                $arrParams[$i] = str_replace('\&', '&', $arrParams[$i]);
                $param = preg_split('/(?<!\\\)=/', $arrParams[$i]); // explode('=', $arrParams[$i]);
                $param = str_replace('\=', '=', $param);
                if (is_array($param) and isset($param[1])) {
                    $this->setVar($param[0], str_replace('\n', "\n", $param[1]));
                }
                unset($param);
            }
        }
    }

    /**
     * Vereinigt die Variablen Container von zwei Input Objekten. Vorhandene Keys werden nicht ueberschrieben.
     *
     * @access public
     * @param object $Input Objekt vom Typ Input
     * @param boolean $flip Fuegt die Daten in umgekehrter Reihenfolge zusammen (true), Standard ist false (Parameter nicht erforderlich)
     * @return bool Erfolgsstatus
     **/
    function mergeVars($Input, $flip = false)
    {
        $bResult=false;
        if (is_a($Input, 'Input')) {
            if ($flip) {
                $this->Vars = array_merge($Input->Vars, $this->Vars);
            }
            else {
                $this->Vars = array_merge($this->Vars, $Input->Vars);
            }
            $bResult = true;
        }
        return $bResult;
    }

    /**
     * Fuehrt die Variablen Container von zwei Input Objekten zusammen, ueberspringt dabei aber leere und nicht gesetzte Variablen (unset)!
     *
     * @access public
     * @param object $inp Erwartet ein Objekt vom Typ Input
     **/
    function mergeVarsSkipEmpty($inp)
    {
        $keys = array_keys($inp->Vars);
        $c = count($keys);
        for($i=0; $i < $c; $i++) {
            if (!isset($this->Vars[$keys[$i]]) /*or is_null($this -> Vars[$keys[$i]])*/) {
                $this->setVar($keys[$i], $inp->Vars[$keys[$i]]);
            }
        }
    }

    /**
    * Loescht den kompletten internen Variablen Container.
    *
    * @access public
    */
    function clear()
    {
        $this->Vars = Array();
    }

    /**
    * Destruktor
    *
    * Speicherfreigabe des Containers (wir ueberschreiben die Methode Object::destroy()).
    *
    * @access public
    * @see PoolObject::destroy()
    */
    function destroy()
    {
        unset($this->Vars);

        parent :: destroy();
    }
}



/* --------------------- */
######### ICookie #########
/* --------------------- */

/**
 * ICookie
 *
 * Abgeleitet von der Klasse Input. ICookie verwendet standardmaessig zur Initialisierung die Superglobale Variable INPUT_COOKIE.
 *
 * @package Rapid Module Library
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Input.class.php,v 1.14 2007/08/06 12:18:23 manhart Exp $
 * @access public
 **/
class ICookie extends Input
{
    /**
    * ICookie
    *
    * Konstruktor
    *
    * Ruft den Parent Konstruktor von Input mit der Konstante INPUT_COOKIE auf.
    * Damit stehen in dieser Klasse nur die Werte der Superglobals $_COOKIE zur Verfuegung.
    * Als Parameter kann jedoch die Initialisierung der Superglobals beeintraechtigt werden.
    *
    * @access public
    * @param integer $superglobals Konstante, Standard: INPUT_COOKIE
    */
    function __construct($superglobals = I_COOKIE)
    {
        parent::__construct($superglobals);
    }

    /**
    * Setzt ein fluechtiges Cookie, dass nur solange wie die Session existiert (d.h. verfaellt nach Schliessen des Browsers).
    * Hinweis: der Wertebereich des Cookies wird automatisch URL-konform codiert (urlencoded) und beim Lesen automatisch URL-konform decodiert.
    *
    * @access public
    * @param string $cookiename Name des Cookies
    * @param string $value Wert des Cookies
    * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
    * @param string $domain Die Domain, der das Cookie zur Verf�gung steht
    * @param integer $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
    * @return boolean Erfolgsstatus
    */
    function setTransientCookie($cookiename, $value = '', $path = '/', $domain = '', $secure = 0)
    {
        // verf�llt nach Schlie�en des Browsers
        $this -> setVar($cookiename, $value);
        return setcookie($cookiename, $value, null, $path, $domain, $secure);
    }

    /**
    * Setzt ein langlebiges Cookie, dass solange, bis die gesetze Zeit abgelaufen ist, existiert.
    * Hinweis: der Wertebereich des Cookies automatisch URL-konform codiert (urlencoded) und beim Lesen automatisch URL-konform decodiert.
    *
    * @access public
    * @param string $cookiename Name des Cookies
    * @param string $value Wert des Cookies
    * @param integer $expire Lebenszeit des Cookies in Sekunden
    * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
    * @param string $domain Die Domain, der das Cookie zur Verf�gung steht
    * @param integer $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
    * @return boolean Erfolgsstatus
    */
    function setPersistentCookie($cookiename, $value = '', $expire, $path = '/', $domain = '', $secure = 0)
    {
        $this -> setVar($cookiename, $value);
        return setcookie($cookiename, $value, time()+$expire, $path, $domain, $secure);
    }

    /**
    * Loescht ein Cookie.
    * Hinweis: Cookies m�ssen mit den selben Parametern geloescht werden, mit denen sie gesetzt wurden.
    *
    * @access public
    * @param string $cookiename Name des Cookies
    * @param string $path Der Pfad zu dem Server, auf welchem das Cookie verfuegbar sein wird
    * @param string $domain Die Domain, der das Cookie zur Verfuegung steht
    * @param integer $secure Gibt an, dass das Cookie nur ueber eine sichere HTTPS - Verbindung uebertragen werden soll. Ist es auf 1 gesetzt, wird das Cookie nur gesendet, wenn eine sichere Verbindung besteht. Der Standardwert ist 0.
    * @return boolean Erfolgsstatus
    */
    function delCookie($cookiename, $path = '/', $domain = '', $secure = 0)
    {
        if (isset($this -> Vars[$cookiename])) {
            $this -> delVar($cookiename);
        }
        return setcookie ($cookiename, '', time() - 3600, $path, $domain, $secure);
    }
}



/* --------------------- */
######### IGet #########
/* --------------------- */

/**
 * IGet
 *
 * Abgeleitet von der Klasse Input. IGet verwendet standardmaessig zur Initialisierung die Superglobale Variable INPUT_GET.
 *
 * TODO:keyword!!!
 *
 * @package Rapid Module Library
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Input.class.php,v 1.14 2007/08/06 12:18:23 manhart Exp $
 * @access public
 **/
class IGet extends Input
{
    var $shrouded = 0;	//verschleiert (mom. nicht benutzt! TODO)

    /**
     * IGet::IGet()
     *
     * Konstruktor.
     *
     * Initialisiert standardmaessig INPUT_GET.
     *
     * @access public
     * @param integer $superglobals Standard: INPUT_GET
     **/
    function __construct($superglobals = I_GET)
    {
        parent::__construct($superglobals);
    }

    /**
    * Die Funktion liefert eine Url-konforme Parameter Liste (auch query genannt). In der Standardeinstellung werden Objekte und Arrays uebersprungen.
    *
    * @access public
    * @return string Query (Url-konforme Parameter Liste)
    */
    function getQuery($query='', $ampersand='&')
    {
        $session_name = session_name();
        foreach($this->Vars as $key => $value) {
            if (isset($this->Vars[$key])) {
                if (is_object($this->Vars[$key])) {
                    continue;
                }
                if ($key == $session_name) {
                    continue;
                }

                // Array als Value
                if(is_array($value)) {
                    foreach($value as $val) {
                        if (!empty($query)) {
                            $query .= $ampersand;
                        }
                        $query .= urlencode($key.'[]').'='.urlencode($val);
                    }
                    continue;
                }

                if (!empty($query)) {
                    $query .= $ampersand;
                }
                $query .= $key.'='.urlencode($value);
            }
        }
        return $query;
    }
}



/* --------------------- */
######### IPost   #########
/* --------------------- */

class IPost extends Input
{
    function __construct($superglobals = I_POST)
    {
        parent::__construct($superglobals);
    }
}



/* --------------------- */
######### IFiles  #########
/* --------------------- */

class IFiles extends Input
{
    function __construct($superglobals = I_FILES)
    {
        parent::__construct($superglobals);
    }
}



/* --------------------- */
######### IEnv    #########
/* --------------------- */

class IEnv extends Input
{
    function __construct($superglobals = I_ENV)
    {
        parent::__construct($superglobals);
    }
}



/* --------------------- */
######### IServer #########
/* --------------------- */

class IServer extends Input
{
    function __construct($superglobals = I_SERVER)
    {
        parent::__construct($superglobals);
    }
}



/* --------------------- */
######### ISession ########
/* --------------------- */

class ISession extends Input
{
    /**
     * Flag, ob Session initiiert wurde.
     *
     * @var boolean
     * @access private
     */
    var $session_started = false;

    /**
     * @var bool Schreibe u. entsperre Session
     */
    var $autoClose = true;

    function __construct($autoClose=true)
    {
        $this->setAutoClose($autoClose);

        $this->start();
        parent::__construct(I_SESSION);
        $this->write_close();
    }

    /**
     * @param $autoClose
     */
    function setAutoClose($autoClose)
    {
        $this->autoClose = $autoClose;
    }

    /**
     * Session wird initiiert
     */
    function start()
    {
        if(!$this->session_started) {
            $this->session_started = session_start();
        }
        elseif($this->autoClose) {
            @session_start(); // reopen session
            $this->reInit();
        }
    }

    /**
    * Setzt eine Variable im internen Container.
    * Im Unterschied zu Input::addVar ueberschreibt Input::setVar alle Variablen.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param mixed $value Wert der Variable
    */
    function setVar($key, $value=0)
    {
        $this->start();
        parent::setVar($key, $value);
        $this->write_close();
    }

    /**
    * Setzt eine Variable im internen Container.
    * Im Unterschied zu Input::setVar ueberschreibt Input::addVar keine bereits vorhanden Variablen.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $value Wert der Variable
    * @return boolean Erfolgsstatus
    */
    function addVar($key, $value='')
    {
        $this->start();
        parent::addVar($key, $value);
        $this->write_close();
    }

    /**
    * Loescht eine Variable aus dem internen Container.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    */
    function delVar($key)
    {
        $this->start();
        parent::delVar($key);
        $this->write_close();
    }

    /**
    * Setzt die Daten f�r Input.
    *
    * @access public
    * @param array $data Indexiertes Array, enth�lt je Satz ein assoziatives Array
    */
    function setData($data)
    {
        $this->start();
        parent::setData($data);
        $this->write_close();
    }

    /**
     * Vereinigt die Variablen Container von zwei Input Objekten. Vorhandene Keys werden nicht ueberschrieben.
     *
     * @access public
     * @param object $Input Objekt vom Typ Input
     * @param boolean $flip Fuegt die Daten in umgekehrter Reihenfolge zusammen (true), Standard ist false (Parameter nicht erforderlich)
     * @return bool Erfolgsstatus
     **/
    function mergeVars($Input, $flip = false)
    {
        $this->start();
        parent::mergeVars($Input, $flip);
        $this->write_close();
    }

    /**
    * Aendert den Typ einer Variable im internen Container.
    *
    * @access public
    * @param string $key Schluessel (bzw. Name der Variable)
    * @param string $type Datentyp (siehe getType)
    * @see Input::getType()
    * @return boolean Erfolgsstatus
    */
    function setType($key, $type)
    {
        $this->start();
        parent::setType($key, $type);
        $this->write_close();
    }

    /**
     * Gibt die maximale Lebenszeit der Session zur�ck
     *
     * @return int Maximale Lebenszeit in Sekunden
     */
    function getMaxLifetime()
    {
        return get_cfg_var('session.gc_maxlifetime');
    }

    /**
     * Schliesst Session. Zu empfehlen bei lang laufenden Programmen, damit andere Scripte nicht gesperrt werden
     *
     */
    function write_close()
    {
        if($this->autoClose) {
            session_write_close();
        }
        //$this->session_started = 0;
    }

    /**
     * Loescht die Session
     */
    function destroy()
    {
        $this->start();
        session_destroy();
    }
}



/* --------------------- */
######### IRequest #########
/* --------------------- */

class IRequest extends Input
{
    function __construct($superglobals = I_REQUEST)
    {
        parent::__construct($superglobals);
    }
}