<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

//namespace pool\classes;

// 05.01.22, AM, POST Requests with JSON-Data
use pool\classes\Core\PoolObject;

$REQUEST_METHOD = $_SERVER['REQUEST_METHOD'] ?? '';
$CONTENT_TYPE = $_SERVER['CONTENT_TYPE'] ?? '';
if($REQUEST_METHOD == 'POST' and $CONTENT_TYPE == 'application/json') {
    $json = file_get_contents('php://input');
    if(isValidJSON($json)) {
        $_POST = json_decode($json, true);
        $_REQUEST = $_REQUEST + $_POST;
    }
}

/**
 * base class for incoming data at the server
 */
class Input extends PoolObject
{
    /**
     * @constant int INPUT_EMPTY no superglobals
     */
    public const INPUT_EMPTY = 0;
    /**
     * @constant int INPUT_COOKIE $_COOKIE (php equivalent INPUT_COOKIE 2)
     */
    public const INPUT_COOKIE = 1;
    /**
     * @constant int INPUT_GET $_GET (php equivalent INPUT_GET 1)
     */
    public const INPUT_GET = 2;
    /**
     * @constant int INPUT_POST $_POST (php equivalent INPUT_POST 0)
     */
    public const INPUT_POST = 4;
    /**
     * @constant int INPUT_FILES $_FILES (php equivalent INPUT_FILES 3)
     */
    public const INPUT_FILES = 8;
    /**
     * @constant int INPUT_ENV $_ENV (php equivalent INPUT_ENV 4)
     */
    public const INPUT_ENV = 16;
    /**
     * @constant int INPUT_SERVER $_SERVER (php equivalent INPUT_SERVER 5)
     */
    public const INPUT_SERVER = 32;
    /**
     * @constant int INPUT_SESSION $_SESSION (php equivalent INPUT_SESSION 6)
     */
    public const INPUT_SESSION = 64;
    /**
     * @constant int INPUT_REQUEST $_REQUEST (php equivalent INPUT_REQUEST 99)
     */
    public const INPUT_REQUEST = 128;
    /**
     * @constant int I_ALL all superglobals
     */
    public const INPUT_ALL = 255;

    /**
     * @var array variables container
     */
    protected array $vars = [];

    /**
     * @var int Superglobals
     * @see https://www.php.net/manual/de/language.variables.superglobals.php
     */
    private int $superglobals = self::INPUT_EMPTY;

    /**
     * @var array
     */
    private array $filterRules = [];

    /**
     * Input constructor. Initialization of the superglobals.
     *
     * @param int $superglobals Select a predefined constant: INPUT_GET, INPUT_POST, INPUT_REQUEST, INPUT_SERVER, INPUT_FILES, INPUT_COOKIE
     * @see https://www.php.net/manual/de/language.variables.superglobals.php
     */
    public function __construct(int $superglobals = self::INPUT_EMPTY)
    {
        $this->init($superglobals);
    }

    /**
     * get superglobals
     *
     * @return int
     */
    public function getSuperglobals(): int
    {
        return $this->superglobals;
    }

    /**
     * Initialisiert gewaehlte Superglobals und schreibt die Variablen in den internen Variablen Container.
     * Falls Magic Quotes eingestellt sind, werden bei den $_GET und $_POST Superglobals alle Escape Zeichen entfernt.
     * Aussnahme: Session! Die Superglobale Variable $_SESSION wird zum internen Container referenziert!
     *
     * @param int $superglobals Einzulesende Superglobals (siehe Konstanten)
     */
    protected function init(int $superglobals = self::INPUT_EMPTY): void
    {
        if($superglobals == 0) {
            return;
        }
        $this->superglobals = $superglobals;

        // @see https://www.php.net/manual/en/reserved.variables.environment.php
        if($superglobals & self::INPUT_ENV) { // I_ENV
            $this->addVar($_ENV);
        }

        // @see https://www.php.net/manual/en/reserved.variables.server.php
        if($superglobals & self::INPUT_SERVER) { // I_SERVER
            $this->addVar($_SERVER);
        }

        // @see https://www.php.net/manual/en/reserved.variables.files.php
        if($superglobals & self::INPUT_FILES) {
            $this->addVar($_FILES);
        }

        // @see https://www.php.net/manual/en/reserved.variables.request.php
        if($superglobals & self::INPUT_REQUEST) {
            $this->addVar($_REQUEST);
        }

        // @see https://www.php.net/manual/en/reserved.variables.post.php
        if($superglobals & self::INPUT_POST) {
            $this->addVar($_POST);
        }

        // @see https://www.php.net/manual/en/reserved.variables.get.php
        if($superglobals & self::INPUT_GET) {
            $this->addVar($_GET);
        }

        // @see https://www.php.net/manual/en/reserved.variables.cookies.php
        if($superglobals & self::INPUT_COOKIE) {
            $this->addVar($_COOKIE);
        }

        // @see https://www.php.net/manual/en/reserved.variables.session.php
        if($superglobals != self::INPUT_ALL and $superglobals & self::INPUT_SESSION) { // only $_SESSION assigned directly (not combinable)
            $this->vars = &$_SESSION; // PHP Session Handling (see php manual)
        }
    }

    /**
     * reinitialize superglobals.
     */
    public function reInit()
    {
        /*$this->clear(); vermeiden, da clear sich auch in ISession beim Leeren der Session auswirkt */
        $this->vars = [];
        $this->init($this->superglobals);
    }

    /**
     * returns number of variables
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->vars);
    }

    /**
     * Prueft, ob eine Variable ueberhaupt gesetzt wurde.
     *
     * @param string $key Name der Variable
     * @return boolean True=ja; False=nein
     **/
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->vars);
    }

    /**
     * Prueft, ob eine Variable einen Wert enthaelt.
     * Diese Funktion liefert TRUE, wenn eine Variable nicht definiert, leer oder gleich 0 ist, ansonsten FALSE
     *
     * @param string|array $key Name der Variable
     * @return boolean True=ja; False=nein
     */
    public function emptyVar(string|array $key): bool
    {
        if(is_array($key)) {
            if(sizeof($key) == 0) return true;
            foreach($key as $k => $v) {
                if(empty($v)) {
                    return true;
                }
            }
            return false;
        }
        else {
            return (!isset($this->vars[$key]) or empty($this->vars[$key]));
        }
    }

    /**
     * Liefert einen Boolean zurück, ob alle Daten innerhalb des Inputs leer sind
     *
     * @return boolean
     */
    function isEmpty(): bool
    {
        return $this->emptyVar(array_keys($this->vars));
    }

    /**
     * filter a variable
     *
     * @param string $key
     * @return void
     * @throws Exception
     */
    private function filterVar(string $key): void
    {
        if(isset($this->filterRules[$key])) {
            //            $filter = $this->filterRules[$key][0];

            //            switch($filter) {
            //                case Input::FILTER_SANITIZE_STRIP_TAGS:
            //                    $val = strip_tags($this->Vars[$key], ($this->filterRules[$key][1] ?: null));
            //                    break;
            //
            //                default:
            //                    $val = filter_var($this->Vars[$key], $filter, $this->filterRules[$key][1]);
            //            }

            // todo filter_var returns also false, if there is an error
            $filteredVar = filter_var($this->vars[$key], $this->filterRules[$key][0], $this->filterRules[$key][1]);
            if($filteredVar === false) {
                throw new Exception('Incoming data with the key ' . $key . ' did not pass the filter.');
            }
            $this->vars[$key] = $filteredVar;
        }
    }

    /**
     * returns filter rules for filtering incoming variables
     *
     * @return array
     */
    public function getFilterRules(): array
    {
        return $this->filterRules;
    }

    /**
     * Liefert den Wert fuer den uebergebenen Schluessel.
     *
     * @param string $key Name der Variable
     * @param mixed $default return default value, if key is not set
     * @return mixed Wert der Variable oder NULL, wenn die Variable nicht existiert
     */
    public function getVar(string $key, mixed $default = null): mixed
    {
        return $this->vars[$key] ?? $default;
    }

    /**
     * Liefert die Referenz fuer den uebergebenen Schluessel.
     *
     * @access public
     * @param string $key Name der Variable
     * @param mixed|null $default return default value, if key is not set
     * @return mixed Referenz auf das Objekt oder NULL, wenn das Objekt nicht existiert
     */
    public function &getRef(string $key, $default = null)
    {
        $ref = $default;
        if(isset($this->vars[$key])) {
            $ref = &$this->vars[$key];
        }
        return $ref;
    }

    /**
     * assign data to a variable
     *
     * @param string $key variable name
     * @param mixed $value value
     * @return Input
     */
    public function setVar(string $key, mixed $value = ''): Input
    {
        $this->vars[$key] = $value;
        return $this;
    }

    /**
     * assign data as array
     *
     * @param array $assoc
     * @return Input
     */
    public function setVars(array $assoc): Input
    {
        $this->vars = $assoc + $this->vars;
        return $this;
    }

    /**
     * Legt eine Referenz eines Objekts im internen Container ab.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @param mixed $value Referenz auf die Variable (oder Objekt)
     */
    function setRef(string $key, &$value)
    {
        $this->vars[$key] = &$value;
    }

    /**
     * adds a default value/data to a variable if it does not exist. We can also add a filter on an incoming variable.
     * At the moment filtering does not work with array passes on the key!
     *
     * @param string|array $key Schluessel (bzw. Name der Variable)
     * @param mixed $value Wert der Variable
     * @param int $filter
     * @param mixed $filterOptions
     * @return Input
     */
    public function addVar($key, mixed $value = '', int $filter = FILTER_FLAG_NONE, $filterOptions = 0): Input
    {
        if(!is_array($key)) {
            if(!isset($this->vars[$key])) {
                $this->vars[$key] = $value;
            }
            if($filter) {
                $this->filterRules[$key] = [$filter, $filterOptions];
            }
        }
        else {
            // @deprecated
            $this->addVars($key);
        }
        return $this;
    }

    /**
     * merge array with vars but don't override existing vars
     *
     * @param array $vars
     * @return Input
     */
    public function addVars(array $vars): self
    {
        $this->vars = $this->vars + $vars;
        return $this;
    }

    /**
     * Setzt eine Variable im internen Container. Symlink auf Input::setRef().
     *
     * @param string $key Schluessel (bzw. Name der Variable/Objekt)
     * @param mixed $value Referenz auf die Variable (oder Objekt)
     */
    function addRef(string $key, &$value)
    {
        $this->setRef($key, $value);
    }

    /**
     * Loescht eine Variable aus dem internen Container.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     */
    public function delVar($key): self
    {
        if(!is_array($key)) {
            unset($this->vars[$key]);
        }
        else {
            // @deprecated
            $this->delVars($key);
        }
        return $this;
    }

    /**
     * @param array $assoc
     * @return $this
     */
    public function delVars(array $assoc): Input
    {
        foreach($assoc as $key) {
            unset($this->vars[$key]);
        }
        return $this;
    }

    /**
     * Loescht eine Referenz aus dem internen Container. SymLink auf Input::delVar().
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     */
    function delRef($key)
    {
        $this->delVar($key);
    }

    /**
     * Diese Funktion lieferten den Typ der Variablen mit dem uebergebenen Schluesselnamen $key.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @return string Typen (set of "integer", "double", "string", "array", "object", "unknown type") oder false, wenn die Variable nicht gesetzt ist.
     */
    public function getType(string $key): string
    {
        return isset($this->vars[$key]) ? gettype($this->vars[$key]) : '';
    }

    /**
     * sets the data type of variable
     *
     * @param string $key variable name
     * @param string $type data type
     * @see Input::getType()
     */
    public function setType(string $key, string $type): bool
    {
        $result = false;
        if(isset($this->vars[$key])) {
            $result = settype($this->vars[$key], $type);
        }
        return $result;
    }

    /**
     * Liefert eine verschluesselte Variable entschluesselt zurueck.
     * Dekodiert vor der Entschluesslung den Wert (MIME base64).
     *
     * @param string $name variable name
     * @return string $securekey Wert der Variable (entschluesselt)
     */
    function getDecryptedVar($name, $securekey)
    {
        // Call Xor Algo.
        $decoded_data = base64_decode($this->getVar($name));
        return $this->xorEnDecryption($decoded_data, $securekey);
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
    public function filter(array $keys_must_exists, ?callable $filter = null, string $prefix = '', bool $removePrefix = false): Input
    {
        $Input = new Input(self::INPUT_EMPTY);
        $new_prefix = ($removePrefix) ? '' : $prefix;
        foreach($keys_must_exists as $key) {
            // AM, 22.04.09, modified (isset nimmt kein NULL)
            if(array_key_exists($prefix . $key, $this->vars)) {
                if($filter) {
                    $remove = call_user_func($filter, $this->vars[$prefix . $key], $prefix . $key);
                    if($remove) continue;
                }
                $Input->setVar($new_prefix . $key, $this->vars[$prefix . $key]);
            }
        }
        return $Input;
    }

    /**
     * Overrides variables
     *
     * @param array $data associative array
     **/
    public function setData(array $data)
    {
        $this->vars = $data;
    }

    /**
     * Liefert ein assoziatives Array mit allen Daten des Input Objekts zureck
     *
     * @return array Daten
     **/
    public function getData(): array
    {
        return $this->vars;
    }

    /**
     * Liefert alle Werte als kompletten String zurück
     *
     * @param string $delimiter Trenner
     * @return string
     * @deprecated
     */
    function getValuesAsString(string $delimiter): string
    {
        $result = '';
        foreach($this->vars as $key => $val) {
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
            $this->setVar($new_keyname, $this->vars[$keyname]);
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
        foreach($keynames as $key => $value) {
            $this->rename($key, $value);
        }
    }

    /**
     * Ermittelt die Unterschiede von Input zu einem Array
     *
     * @param array $array
     * @return array
     */
    public function diff(array $array): array
    {
        return array_diff($this->vars, $array);
    }

    /**
     * Berechnet den Unterschied zwischen Arrays mit zus�tzlicher Indexpr�fung
     *
     * @param array $array
     * @return array
     */
    public function diff_assoc(array $array): array
    {
        return array_diff_assoc($this->vars, $array);
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
        if($value == '' or $securekey == '') {
            return $value;
        }

        $new_value = '';

        $skey_len = strlen($securekey);
        $value_len = strlen($value);

        $v = 0;
        $k = 0;
        while($v < $value_len) {
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
        return serialize($this->vars);
    }

    /**
     * Importiert einen Byte-Stream im internen Container.
     *
     * @param string $data
     * @return Input
     */
    function setByteStream(string $data): Input
    {
        $buf = unserialize($data);
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
        if(!empty($key)) {
            $output = pray($this->getVar($key));
        }
        else {
            $output = pray($this->vars);
        }

        if($print) {
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
    function setParams(string $params, bool $translate_specialchars = true)
    {
        $params = ltrim($params);
        if(strlen($params) > 0) {
            if($translate_specialchars) {
                # &amp; => &
                $trans = get_html_translation_table(HTML_SPECIALCHARS, ENT_QUOTES);
                $trans = array_flip($trans);
                $params = strtr($params, $trans);
            }

            $arrParams = preg_split('/(?<!\\\)&/', $params);
            $arrParams = str_replace('\&', '&', $arrParams);
            foreach($arrParams as $paramPair) {
                $paramPairArray = preg_split('/(?<!\\\)=/', $paramPair); // explode('=', $arrParams[$i]);
                $paramPairArray = str_replace('\=', '=', $paramPairArray);
                if(is_array($paramPairArray) && isset($paramPairArray[1])) {
                    $this->setVar($paramPairArray[0], str_replace('\n', "\n", $paramPairArray[1]));
                }
                unset($paramPairArray);
            }
        }
    }

    /**
     * Vereinigt die Variablen Container von zwei Input Objekten. Vorhandene Keys werden nicht ueberschrieben.
     *
     * @param Input $Input Objekt vom Typ Input
     * @param boolean $flip Fuegt die Daten in umgekehrter Reihenfolge zusammen (true), Standard ist false (Parameter nicht erforderlich)
     **/
    public function mergeVars(Input $Input, bool $flip = false): Input
    {
        if($flip) {
            $this->vars = array_merge($Input->vars, $this->vars);
        }
        else {
            $this->vars = array_merge($this->vars, $Input->vars);
        }
        return $this;
    }

    /**
     * Merges variables into their own container (Vars). But only if they are not yet set.
     *
     * @param Input $Input
     * @throws Exception
     */
    public function mergeVarsIfNotSet(Input $Input): void
    {
        if($Input->count() == 0) {
            return;
        }
        $this->filterRules = $Input->getFilterRules();

        $keys = array_keys($Input->vars);
        $c = count($keys);
        for($i = 0; $i < $c; $i++) {
            $key = $keys[$i];
            if(!isset($this->vars[$key])) {
                $this->setVar($key, $Input->vars[$key]);
            }

            $this->filterVar($key);
        }
    }

    /**
     * resets the variable container
     */
    public function clear()
    {
        $this->vars = [];
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
        $this->clear();
    }
}