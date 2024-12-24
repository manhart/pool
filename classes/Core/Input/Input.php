<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core\Input
{

    use Countable;
    use InvalidArgumentException;
    use JsonException;
    use pool\classes\Core\Input\Filter\DataType;
    use pool\classes\Core\PoolObject;

    use function array_diff;
    use function array_diff_assoc;
    use function array_flip;
    use function array_intersect_key;
    use function array_key_exists;
    use function array_merge;
    use function base64_decode;
    use function base64_encode;
    use function chr;
    use function count;
    use function file_get_contents;
    use function filter_var;
    use function gettype;
    use function http_build_query;
    use function is_array;
    use function is_object;
    use function json_decode;
    use function ord;
    use function parse_str;
    use function pray;
    use function serialize;
    use function session_name;
    use function settype;
    use function strlen;
    use function unserialize;

    use const JSON_THROW_ON_ERROR;
    use const PHP_QUERY_RFC3986;

    /**
     * Core class for incoming data on the server
     *
     * @package pool\classes\Core\Input
     * @since 2003-07-10
     */
    class Input extends PoolObject implements Countable
    {
        /**
         * @constant int EMPTY no superglobals
         */
        public const EMPTY = 0;
        /**
         * @constant int COOKIE $_COOKIE (php equivalent INPUT_COOKIE (2))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const COOKIE = 1;
        /**
         * @constant int GET $_GET (php equivalent INPUT_GET (1))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const GET = 2;
        /**
         * @constant int POST $_POST (php equivalent INPUT_POST (0))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const POST = 4;
        /**
         * @constant int FILES $_FILES (php equivalent INPUT_FILES (3))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const FILES = 8;
        /**
         * @constant int ENV $_ENV (php equivalent INPUT_ENV (4))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const ENV = 16;
        /**
         * @constant int SERVER $_SERVER (php equivalent INPUT_SERVER (5))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const SERVER = 32;
        /**
         * @constant int SESSION $_SESSION (php equivalent INPUT_SESSION (6))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const SESSION = 64;
        /**
         * @constant int REQUEST $_REQUEST (php equivalent INPUT_REQUEST (99))
         * @see https://www.php.net/manual/de/filter.constants.php
         */
        public const REQUEST = 128;
        /**
         * @constant int I_ALL all superglobals
         */
        public const ALL = 255;

        /**
         * @var array variables internal container
         */
        protected array $vars = [];

        /**
         * @var int Superglobals
         * @see https://www.php.net/manual/de/language.variables.superglobals.php
         */
        private int $superglobals = self::EMPTY;

        private array $filterRules = [];

        private array $filter;

        /**
         * Initialization of the superglobals. Attention: SESSION is a reference to the internal container and cannot be combined with other superglobals!
         *
         * @param int $superglobals Select or combine predefined constants: EMPTY, GET, POST, REQUEST, SERVER, FILES, COOKIE, SESSION, ALL
         * @see https://www.php.net/manual/de/language.variables.superglobals.php
         */
        public function __construct(int $superglobals = self::EMPTY, array $filter = [])
        {
            $this->setFilter($filter);
            $this->init($superglobals);
        }

        /**
         * Set filter rules for filtering incoming variables
         */
        public function setFilter(array $filter): static
        {
            $this->filter = $filter;
            return $this;
        }

        /**
         * Initializes selected superglobals and writes the variables into the internal variable container.
         * Except: SESSION: The super global variable $_SESSION is referenced to the internal container!
         *
         * @param int $superglobals Einzulesende Superglobals (siehe Konstanten)
         */
        protected function init(int $superglobals = self::EMPTY): void
        {
            if ($superglobals === 0) {
                return;
            }
            $this->superglobals = $superglobals;

            // @see https://www.php.net/manual/en/reserved.variables.environment.php
            if ($superglobals & self::ENV) { // I_ENV
                $this->addVars($_ENV);
            }

            // @see https://www.php.net/manual/en/reserved.variables.server.php
            if ($superglobals & self::SERVER) { // I_SERVER
                $this->addVars($_SERVER);
            }

            // @see https://www.php.net/manual/en/reserved.variables.files.php
            if ($superglobals & self::FILES) {
                $this->addVars($_FILES);
            }

            // @see https://www.php.net/manual/en/reserved.variables.request.php
            if ($superglobals & self::REQUEST) {
                $this->addVars($_REQUEST);
            }

            // @see https://www.php.net/manual/en/reserved.variables.post.php
            if ($superglobals & self::POST) {
                $this->addVars($_POST);
            }

            // @see https://www.php.net/manual/en/reserved.variables.get.php
            if ($superglobals & self::GET) {
                $this->addVars($_GET);
            }

            // @see https://www.php.net/manual/en/reserved.variables.cookies.php
            if ($superglobals & self::COOKIE) {
                $this->addVars($_COOKIE);
            }

            // @see https://www.php.net/manual/en/reserved.variables.session.php
            if ($superglobals !== self::ALL && $superglobals & self::SESSION) { // only $_SESSION assigned directly (not combinable)
                $this->vars = &$_SESSION; // PHP Session Handling (see php manual)
            }
        }

        /**
         * Merge array with vars but don't override existing vars
         *
         * @deprecated for filtering with Defaults in GUI_Module::init()
         */
        public function addVars(array $vars): static
        {
            foreach ($vars as $key => $value) {
                $this->addVar($key, $value);
            }
            return $this;
        }

        /**
         * Adds a default value/data to a variable if it does not exist. It does not override existing values! We can also add a filter on an incoming
         * variable.
         *
         * @param string $key name of the variable
         * @param mixed $value value of the variable
         * @param int $filter filter type
         * @param mixed $filterOptions
         * @deprecated for filtering with Defaults in GUI_Module::init()
         * @see https://www.php.net/manual/de/filter.filters.php
         */
        public function addVar(string $key, mixed $value = '', int $filter = 0, array|int $filterOptions = 0): static
        {
            if (!array_key_exists($key, $this->vars)) {
                $this->setVar($key, $value, true);
            }
            if ($filter) {
                $this->filterRules[$key] = [$filter, $filterOptions];
            }
            return $this;
        }

        /**
         * Assign data to a variable. If the module has an input filter, the data is filtered and can throw an InvalidArgumentException.
         *
         * @param string $key variable name
         * @param mixed $value value
         */
        public function setVar(string $key, mixed $value = '', bool $suppressException = false): static
        {
            try {
                $filter = $this->filter[$key] ?? null;
                if ($filter) {
                    $value = $this->runFilter($filter, $value);
                }
            } catch (InvalidArgumentException $e) {
                if (!array_key_exists($key, $this->vars) && array_key_exists(1, $filter)) {
                    $this->vars[$key] = $filter[1]; // new value
                }
                return $suppressException ? $this : throw $e;
            }
            $this->vars[$key] = $value;
            return $this;
        }

        /**
         * Runs a filter on a value
         *
         * @throws InvalidArgumentException
         */
        private function runFilter(array $filter, mixed $value): mixed
        {
            $pipeline = is_array($filter[0]) ? $filter[0] : [$filter[0]];
            foreach ($pipeline as $dataType) {
                $filterFunction = DataType::getFilter($dataType);
                $value = $filterFunction($value);
            }
            return $value;
        }

        public static function processJsonPostRequest(): void
        {
            // decode POST requests with JSON-Data
            if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json') {
                $json = file_get_contents('php://input');
                if (str_starts_with($json, '[')) { // JSON Array not supported
                    return;
                }
                try {
                    $_POST = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
                    $_REQUEST += $_POST;
                } catch (JsonException) {
                }
            }
        }

        /**
         * Get superglobals
         */
        public function getSuperglobals(): int
        {
            return $this->superglobals;
        }

        /**
         * Reinitialize superglobals.
         */
        public function reInit(): void
        {
            $this->clear()->init($this->superglobals);
        }

        /**
         * Resets the variable container
         */
        public function clear(): static
        {
            $this->vars = [];
            return $this;
        }

        /**
         * Check if a variable exists and is not an empty string and not null
         */
        public function has(string $key): bool
        {
            return $this->exists($key) && $this->vars[$key] !== '' && $this->vars[$key] !== null;
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
         */
        public function allEmpty(): bool
        {
            if ($this->count() === 0) {
                return true;
            }
            foreach ($this->vars as $value) {
                if (!empty($value)) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Returns number of variables
         */
        public function count(): int
        {
            return count($this->vars);
        }

        /**
         * Returns the value of the given key as integer.
         */
        public function getAsInt(string $key, int $default = 0): int
        {
            return (int)$this->getVar($key, $default);
        }

        /**
         * Returns the value for the given key.
         *
         * @param string $key Name of the variable
         * @param mixed $default Returns this value as default, if key is not set
         * @return mixed Value of the variable or $default/NULL if the variable does not exist
         */
        public function getVar(string $key, mixed $default = null): mixed
        {
            return $this->vars[$key] ?? $default;
        }

        /**
         * Returns the value of the given key as string.
         */
        public function getAsString(string $key, string $default = ''): string
        {
            return (string)$this->getVar($key, $default);
        }

        /**
         * Returns the value of the given key as boolean.
         */
        public function getAsBool(string $key, bool $default = false): bool
        {
            return (bool)$this->getVar($key, $default);
        }

        /**
         * Returns the reference for the given key.
         *
         * @param string $key Name of the variable
         * @param mixed $default Return this value as default, if key is not set
         * @return mixed Reference to the object, or $default/NULL if the object does not exist
         */
        public function &getRef(string $key, mixed $default = null): mixed
        {
            $ref = $default;
            if (isset($this->vars[$key])) {
                $ref = &$this->vars[$key];
            }
            return $ref;
        }

        /**
         * Assign data as array
         */
        public function setVars(array $assoc, bool $suppressException = false): static
        {
            foreach ($assoc as $key => $value) {
                $this->setVar($key, $value, $suppressException);
            }
            return $this;
        }

        public function applyDefaults(): static
        {
            foreach ($this->filter as $key => $filter) {
                if (array_key_exists(1, $filter)) {
                    $this->addVar($key, $filter[1]);
                }
            }
            return $this;
        }

        /**
         * Places a reference of an object in the internal container.
         *
         * @param string $key Name of the variable
         * @param mixed $value Reference to the object
         */
        public function setRef(string $key, mixed &$value): static
        {
            $this->vars[$key] = &$value;
            return $this;
        }

        public function delVars(array $assoc): Input
        {
            foreach ($assoc as $key) {
                unset($this->vars[$key]);
            }
            return $this;
        }

        /**
         * Return the type of the variable with the passed key name $key.
         *
         * @param string $key variable name
         * @return string types (set of "integer", "double", "string", "array", "object", "unknown type", "NULL", "resource") or an empty string if the
         *     variable is not set.
         * @see https://www.php.net/manual/de/function.gettype.php
         */
        public function getType(string $key): string
        {
            return $this->exists($key) ? gettype($this->vars[$key]) : '';
        }

        /**
         * Sets the data type of variable
         *
         * @param string $key variable name
         * @param string $type data type
         * @see Input::getType()
         */
        public function setType(string $key, string $type): static
        {
            if ($this->exists($key)) {
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
         * Well-known, simple, obsolete bitwise XOR encryption. Obfuscates the value of variables.
         * Not suitable for security-related data!
         *
         * @param string $value value to encrypt
         * @param string $secretKey key for encryption
         */
        public function xorEnDecryption(string $value, string $secretKey): string
        {
            if (empty($value) || empty($secretKey)) {
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
         * Sets a variable and encrypts its value using a key. The value is encoded using MIME base64 and a simple XOR encryption.
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
         * Filters variables based on a callable function.
         * Very handy when inserting data into a database. You can remove unnecessary data.
         *
         * @param array $requiredKeys Variables that must remain
         * @param string $prefix use only variable names with this prefix
         * @param boolean $removePrefix removes the variables with prefix from the returned object
         * @return Input The result in a new input object
         */
        public function filter(array $requiredKeys, ?callable $filterFn = null, string $prefix = '', bool $removePrefix = false): Input
        {
            $NewInput = new Input();
            $new_prefix = $removePrefix ? '' : $prefix;
            foreach ($requiredKeys as $key) {
                $prefixedKey = $prefix.$key;
                // AM, 22.04.09, modified (isset nimmt kein NULL)
                if (array_key_exists($prefixedKey, $this->vars)) {
                    $value = $this->vars[$prefixedKey];
                    if ($filterFn && $filterFn($value, $prefixedKey)) {
                        continue;
                    }
                    $NewInput->setVar($new_prefix.$key, $value);
                }
            }
            return $NewInput;
        }

        /**
         * Filters the internal variable storage to only include entries with specified keys.
         * This method performs an efficient key-based filtering operation, equivalent to using
         * array_intersect_key() on the internal $this->vars array. After this operation, $this->vars
         * will only contain entries whose keys are present in the provided $keys array.
         *
         * @param array $keys An array of keys to retain in the internal variable storage.
         */
        public function filterByKeys(array $keys): void
        {
            $this->vars = array_intersect_key($this->vars, array_flip($keys));
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
         * Renames multiple variables
         */
        public function renameKeys(array $keyNames): Input
        {
            foreach ($keyNames as $oldKeyName => $newKeyName) {
                $this->rename($oldKeyName, $newKeyName);
            }
            return $this;
        }

        /**
         * Rename a variable
         *
         * @param string $oldKeyName Old key name
         * @param string $newKeyName New key name
         */
        public function rename(string $oldKeyName, string $newKeyName): Input
        {
            if ($this->exists($oldKeyName)) {
                $this->setVar($newKeyName, $this->vars[$oldKeyName]);
                $this->delVar($oldKeyName);
            }
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
         * Computes the difference between the internal variables and the given array
         */
        public function diff(array $array): array
        {
            return array_diff($this->vars, $array);
        }

        /**
         * Computes the difference between the internal variables and the given array with additional index check
         */
        public function diff_assoc(array $array): array
        {
            return array_diff_assoc($this->vars, $array);
        }

        /**
         * Returns a byte stream of all variables in a string.
         *
         * @return string Byte-Stream
         * @see setByteStream()
         * @see http://php.net/manual/de/function.serialize.php
         */
        public function getByteStream(): string
        {
            return serialize($this->vars);
        }

        /**
         * Importiert einen Byte-Stream im internen Container.
         *
         * @see getByteStream()
         * @see http://php.net/manual/de/function.unserialize.php
         */
        public function setByteStream(string $data): static
        {
            return $this->addVars(unserialize($data, ['allowed_classes' => false]));
        }

        /**
         * Prints or returns one or all variables in the internal container (for debugging)
         *
         * @param boolean $print optional print the output
         * @param string $key optional only one variable
         * @return string output
         * @see pray()
         */
        public function dump(bool $print = true, string $key = ''): string
        {
            $output = pray($key ? $this->getVar($key) : $this->vars);

            if ($print) {
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
            if (!$params) {
                return $this;
            }
            parse_str($params, $parsedParams);
            foreach ($parsedParams as $key => $value) {
                $this->setVar($key, $value);
            }
            return $this;
        }

        /**
         * Joins the variable containers of two input objects. Existing keys are not overwritten.
         *
         * @param Input $Input other input object
         * @param boolean $flip if true, the variables of the other input object are merged into the internal container (affects the order of merge)
         **/
        public function mergeVars(Input $Input, bool $flip = false): Input
        {
            if ($flip) {
                $this->vars = array_merge($Input->vars, $this->vars);
            } else {
                $this->vars = array_merge($this->vars, $Input->vars);
            }
            return $this;
        }

        /**
         * Merges variables into their own container (Vars). But only if they are not yet set.
         */
        public function mergeVarsIfNotSet(Input $Input): static
        {
            if (!$Input->count()) {
                return $this;
            }
            $this->filterRules = $Input->getFilterRules();

            foreach ($Input->vars as $key => $value) {
                if (!isset($this->vars[$key])) {
                    $this->setVar($key, $value);
                }
                $this->filterVar($key);
            }
            return $this;
        }

        /**
         * returns filter rules for filtering incoming variables
         */
        public function getFilterRules(): array
        {
            return $this->filterRules;
        }

        /**
         * Filter a variable
         */
        private function filterVar(string $key): void
        {
            if (!isset($this->filterRules[$key])) {
                return;
            }
            $filteredVar = filter_var($this->vars[$key], $this->filterRules[$key][0], $this->filterRules[$key][1]);
            if ($filteredVar !== false)
                $this->vars[$key] = $filteredVar;
        }

        /**
         * Generate URL-encoded query string
         *
         * @param string $numeric_prefix If numeric indices are used in the base array and this parameter is provided, it will be prepended to the numeric
         *     index for elements in the base array only. This is meant to allow for legal variable names when the data is decoded by PHP or another CGI
         *     application later on.
         * @param string|null $arg_separator The argument separator. If not set or null, arg_separator.output is used to separate arguments.
         * @param int $encoding_type By default, PHP_QUERY_RFC1738.
         * If encoding_type is PHP_QUERY_RFC1738, then encoding is performed per » RFC 1738 and the application/x-www-form-urlencoded media type, which
         *     implies that spaces are encoded as plus (+) signs. If encoding_type is PHP_QUERY_RFC3986, then encoding is performed according to » RFC
         *     3986, and spaces will be percent encoded (%20).
         * @return string Url-conform query string
         */
        public function buildQuery(
            string $numeric_prefix = '',
            ?string $arg_separator = null,
            int $encoding_type = PHP_QUERY_RFC3986,
        ): string {
            $query = '';
            $session_name = session_name();
            foreach ($this->vars as $key => $value) {
                if ($key === $session_name || is_object($value)) {
                    continue;
                }

                $query .= http_build_query([$key => $value], $numeric_prefix, $arg_separator, $encoding_type);
            }
            return $query;
        }
    }
}