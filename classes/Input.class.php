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

use pool\classes\Core\PoolObject;

/**
 * base class for incoming data at the server
 */
class Input extends PoolObject implements Countable
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
     * @var array variables internal container
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
            $this->addVars($_ENV);
        }

        // @see https://www.php.net/manual/en/reserved.variables.server.php
        if($superglobals & self::INPUT_SERVER) { // I_SERVER
            $this->addVars($_SERVER);
        }

        // @see https://www.php.net/manual/en/reserved.variables.files.php
        if($superglobals & self::INPUT_FILES) {
            $this->addVars($_FILES);
        }

        // @see https://www.php.net/manual/en/reserved.variables.request.php
        if($superglobals & self::INPUT_REQUEST) {
            $this->addVars($_REQUEST);
        }

        // @see https://www.php.net/manual/en/reserved.variables.post.php
        if($superglobals & self::INPUT_POST) {
            $this->addVars($_POST);
        }

        // @see https://www.php.net/manual/en/reserved.variables.get.php
        if($superglobals & self::INPUT_GET) {
            $this->addVars($_GET);
        }

        // @see https://www.php.net/manual/en/reserved.variables.cookies.php
        if($superglobals & self::INPUT_COOKIE) {
            $this->addVars($_COOKIE);
        }

        // @see https://www.php.net/manual/en/reserved.variables.session.php
        if($superglobals != self::INPUT_ALL and $superglobals & self::INPUT_SESSION) { // only $_SESSION assigned directly (not combinable)
            $this->vars = &$_SESSION; // PHP Session Handling (see php manual)
        }
    }

    /**
     * reinitialize superglobals.
     */
    public function reInit(): void
    {
        $this->clear()->init($this->superglobals);
    }

    /**
     * @return void
     */
    public static function processJsonPostRequest(): void
    {
        // decode POST requests with JSON-Data
        if(($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' and ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
            $json = file_get_contents('php://input');
            if(isValidJSON($json)) {
                $_POST = json_decode($json, true);
                $_REQUEST = $_REQUEST + $_POST;
            }
        }
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
     * Check if a variable exists
     *
     * @param string $key Name der Variable
     * @return boolean True=ja; False=nein
     **/
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->vars);
    }

    /**
     * Check if a variable exists and is not an empty string and not null
     */
    public function has(string $key): bool
    {
        return $this->exists($key) && $this->vars[$key] !== '' && $this->vars[$key] !== null;
    }

    /**
     * Checks if a variable exists and is not empty
     *
     * @param string $key name of the variable
     * @return boolean True=ja; False=nein
     */
    public function emptyVar(string $key): bool
    {
        return (!isset($this->vars[$key]) or empty($this->vars[$key]));
    }

    /**
     * Returns if all variables are empty
     *
     * @return boolean
     */
    public function allEmpty(): bool
    {
        if($this->count() == 0) {
            return true;
        }
        foreach($this->vars as $value) {
            if(!empty($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * filter a variable
     *
     * @param string $key
     * @return void
     */
    private function filterVar(string $key): void
    {
        if(!isset($this->filterRules[$key])) {
            return;
        }
        $filteredVar = filter_var($this->vars[$key], $this->filterRules[$key][0], $this->filterRules[$key][1]);
        if($filteredVar !== false)
            $this->vars[$key] = $filteredVar;
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
     * Returns the value of the given key as integer.
     * @param string $key
     * @param int $default
     * @return int
     */
    public function getAsInt(string $key, int $default = 0): int
    {
        return (int)$this->getVar($key, $default);
    }

    /**
     * Returns the value of the given key as string.
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getAsString(string $key, string $default = ''): string
    {
        return (string)$this->getVar($key, $default);
    }

    /**
     * Returns the value of the given key as boolean.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getAsBool(string $key, bool $default = false): bool
    {
        return boolval($this->getVar($key, $default));
    }

    /**
     * Liefert die Referenz fuer den uebergebenen Schluessel.
     *
     * @access public
     * @param string $key Name der Variable
     * @param mixed $default return default value, if key is not set
     * @return mixed Referenz auf das Objekt oder NULL, wenn das Objekt nicht existiert
     */
    public function &getRef(string $key, mixed $default = null): mixed
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
    public function setRef(string $key, mixed &$value): static
    {
        $this->vars[$key] = &$value;
        return $this;
    }

    /**
     * adds a default value/data to a variable if it does not exist. We can also add a filter on an incoming variable.
     * At the moment filtering does not work with array passes on the key!
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @param mixed $value Wert der Variable
     * @param int $filter
     * @param mixed $filterOptions
     * @return Input
     */
    public function addVar(string $key, mixed $value = '', int $filter = FILTER_FLAG_NONE, array|int $filterOptions = 0): static
    {
        if(!isset($this->vars[$key])) {
            $this->vars[$key] = $value;
        }
        if($filter) {
            $this->filterRules[$key] = [$filter, $filterOptions];
        }
        return $this;
    }

    /**
     * merge array with vars but don't override existing vars
     *
     * @param array $vars
     * @return Input
     */
    public function addVars(array $vars): static
    {
        $this->vars = $this->vars + $vars;
        return $this;
    }

    /**
     * Deletes a variable from the internal container.
     *
     * @param string $key name of the variable
     */
    public function delVar(string $key): static
    {
        unset($this->vars[$key]);
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
     * Diese Funktion lieferten den Typ der Variablen mit dem uebergebenen Schluesselnamen $key.
     *
     * @param string $key Schluessel (bzw. Name der Variable)
     * @return string Typen (set of "integer", "double", "string", "array", "object", "unknown type") oder false, wenn die Variable nicht gesetzt ist.
     */
    public function getType(string $key): string
    {
        return $this->exists($key) ? gettype($this->vars[$key]) : '';
    }

    /**
     * sets the data type of variable
     *
     * @param string $key variable name
     * @param string $type data type
     * @see Input::getType()
     */
    public function setType(string $key, string $type): static
    {
        if($this->exists($key)) {
            settype($this->vars[$key], $type);
        }
        return $this;
    }

    /**
     * Simple XOR encryption/decryption, based on a given key. Returns the encrypted/decrypted data.
     *
     * @param string $name variable name
     * @return string $secretKey key for encryption
     */
    public function getXOREncrypted(string $name, string $secretKey): string
    {
        return $this->xorEnDecryption(base64_decode($this->getVar($name)), $secretKey);
    }

    /**
     * Setzt eine Variable und verschluesselt deren Wert anhand eines Schluessels.
     * Abschliessend wird der verschluesselte Wert kodiert (MIME base64).
     *
     * @param string $name name of the variable
     * @param string $value value to encrypt
     * @param string $secretKey key for encryption
     */
    public function setXOREncrypted(string $name, string $value, string $secretKey): static
    {
        $this->setVar($name, base64_encode($this->xorEnDecryption($value, $secretKey)));
        return $this;
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
     * Overrides all variables (internal container) with the given array
     *
     * @param array $data associative array of data
     */
    public function setData(array $data): static
    {
        $this->vars = $data;
        return $this;
    }

    /**
     * Returns all variables as array
     *
     * @return array Daten
     **/
    public function getData(): array
    {
        return $this->vars;
    }

    /**
     * Renames a variable
     *
     * @param string $oldKeyName Old key name
     * @param string $newKeyName New key name
     */
    public function rename(string $oldKeyName, string $newKeyName): Input
    {
        if($this->exists($oldKeyName)) {
            $this->setVar($newKeyName, $this->vars[$oldKeyName]);
            $this->delVar($oldKeyName);
        }
        return $this;
    }

    /**
     * Renames multiple variables
     *
     * @param array $keyNames
     * @return Input
     */
    function renameKeys(array $keyNames): Input
    {
        foreach($keyNames as $oldKeyName => $newKeyName) {
            $this->rename($oldKeyName, $newKeyName);
        }
        return $this;
    }

    /**
     * Computes the difference between the internal variables and the given array
     *
     * @param array $array
     * @return array
     */
    public function diff(array $array): array
    {
        return array_diff($this->vars, $array);
    }

    /**
     * Computes the difference between the internal variables and the given array with additional index check
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
     * @param string $value value to encrypt
     * @param string $secretKey key for encryption
     * @return string
     */
    public function xorEnDecryption(string $value, string $secretKey): string
    {
        if(empty($value) || empty($secretKey)) {
            return $value;
        }

        $key_len = strlen($secretKey);
        $value_len = strlen($value);

        $new_value = '';
        for ($v = 0; $v < $value_len; $v++) {
            $k = $v % $key_len;
            $new_value .= chr(ord($value[$v]) ^ ord($secretKey[$k]));
        }
        return $new_value;
    }

    /**
     * Gibt in einer Zeichenkette (String) einen Byte-Stream aller Variablen zurueck.
     * Hinweis: serialize() kann mit den Typen integer, double, string, array (mehrdimensional) und object umgehen.
     * Beim Objekt werden die Eigenschaften serialisiert, die Methoden gehen aber verloren.
     *
     * @return string Byte-Stream
     */
    public function getByteStream(): string
    {
        return serialize($this->vars);
    }

    /**
     * Importiert einen Byte-Stream im internen Container.
     *
     * @param string $data
     * @return Input
     */
    public function setByteStream(string $data): static
    {
        return $this->addVars(unserialize($data));
    }

    /**
     * Prints or returns one or all variables in the internal container (for debugging)
     *
     * @param boolean $print Ausgabe auf dem Schirm (Standard true)
     * @param string $key Schluessel (bzw. Name einer Variable). Wird kein Name angegeben, werden alle Variablen des internen Containers ausgegeben.
     * @return string Dump aller Variablen im internen Container
     * @see pray()
     */
    public function dump(bool $print = true, string $key = ''): string
    {
        $output = pray($key ? $this->getVar($key) : $this->vars);

        if($print) {
            print ($output);
        }
        return $output;
    }

    /**
     * Adds parameters (e.g. from a URL) to the internal container. Format: key=value&key=value.
     *
     * @param string $params Siehe oben Beschreibung
     * @see htmlspecialchars_decode()
     */
    public function setParams(string $params): static
    {
        if(strlen($params) == 0) {
            return $this;
        }
        parse_str($params, $parsedParams);
        foreach($parsedParams as $key => $value) {
            $this->setVar($key, $value);
        }
        return $this;
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
     * @return Input
     */
    public function mergeVarsIfNotSet(Input $Input): static
    {
        if($Input->count() == 0) {
            return $this;
        }
        $this->filterRules = $Input->getFilterRules();

        foreach($Input->vars as $key => $value) {
            if(!isset($this->vars[$key])) {
                $this->setVar($key, $value);
            }
            $this->filterVar($key);
        }
        return $this;
    }

    /**
     * resets the variable container
     */
    public function clear(): static
    {
        $this->vars = [];
        return $this;
    }

    /**
     * Destruktor
     *
     * Speicherfreigabe des Containers (wir ueberschreiben die Methode Object::destroy()).
     */
    public function destroy(): static
    {
        $this->clear();
        return $this;
    }
}