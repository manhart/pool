<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class PoolObject
{
    /**
     * @constant string the extension of the class files
     */
    const CLASS_EXTENSION = '.class.php';

    /**
     * @constant int unknown operating system
     */
    const OS_UNKNOWN = 1;
    /**
     * @constant int Linux operating system
     */
    const OS_LINUX = 2;
    /**
     * @constant int MacOS operating system
     */
    const OS_MACOS = 3;
    /**
     * @constant int Windows operating system
     */
    const OS_WINDOWS = 4;

    /**
     * @var string the full name of the class of the object
     */
    private string $class = '';

    /**
     * @var string the short name of the class of the object
     */
    private string $className = '';

    /**
     * @var string the filename of the file in which the class has been defined
     */
    private string $classFilename = '';

    /**
     * @var bool|null determines whether the class is the POOL base library
     */
    private ?bool $isPOOL = null;

    /**
     * get server-side operating system
     *
     * @return int
     */
    public static function getSystemOS(): int
    {
        return match (PHP_OS) {
            'Linux' => self::OS_LINUX,
            'Darwin' => self::OS_MACOS,
            'WINNT', 'WIN32', 'Windows' => self::OS_WINDOWS,
            default => self::OS_UNKNOWN,
        };
    }

    /**
     * Determines the full name of the class of the object, stores it temporarily and returns it. Also contains namespaces.
     *
     * @return string name of the class
     */
    public function getClass(): string
    {
        if($this->class == '') {
            $this->class = get_class($this);
        }

        return $this->class;
    }

    /**
     * Determines the short name of the class of the object, stores it temporarily and returns it.
     *
     * @return string
     */
    public function getClassName(): string
    {
        if($this->className == '') {
            $this->className = (new \ReflectionClass($this))->getShortName();
        }
        return $this->className;
    }

    /**
     * Gets the filename of the file in which the class has been defined
     *
     * @return string
     */
    public function getClassFilename(): string
    {
        if($this->classFilename == '') {
            $this->classFilename = (new \ReflectionClass($this))->getFileName();
        }
        return $this->classFilename;
    }

    /**
     * determines whether the class is inside the POOL (base library)
     *
     * @return bool
     */
    protected function isPOOL(): bool
    {
        if(is_null($this->isPOOL)) {
            $poolRealpath = realpath(DIR_POOL_ROOT);
            $this->isPOOL = substr_compare($this->getClassFilename(), $poolRealpath, 0, strlen($poolRealpath)) === 0;
        }
        return $this->isPOOL;
    }

    /**
     * Gibt den Namen der Elternklasse (von dem das Objekt abgeleitet wurde) zurueck.
     *
     * @return string Name der Elternklasse
     */
    public function getParentClass(): string
    {
        return get_parent_class($this);
    }

    /**
     * Autoloader for POOL Classes
     *
     * @param string $className Klasse
     * @return bool
     */
    public static function autoloadClass(string $className): bool
    {
        $hasNamespace = str_contains($className, '\\');

        if($hasNamespace) {
            $classRootDirs = [
                DIR_DOCUMENT_ROOT
            ];

            $className = str_replace('\\', '/', $className);
        }
        else {
            $classRootDirs = [
                getcwd() . '/' . PWD_TILL_CLASSES
            ];
            if(defined('DIR_POOL_ROOT')) {
                $classRootDirs[] = DIR_POOL_ROOT . '/' . PWD_TILL_CLASSES;
            }
            if(defined('DIR_COMMON_ROOT')) {
                $classRootDirs[] = DIR_COMMON_ROOT . '/' . PWD_TILL_CLASSES;
            }
        }

        foreach($classRootDirs as $classRootDir) {
            $classRootDir = addEndingSlash($classRootDir);

            $filename = $classRootDir . $className . PoolObject::CLASS_EXTENSION;

            if(file_exists($filename)) {
                require_once $filename;
                return true;
            }
        }
        return false;
    }

    /**
     * Loest eine PHP Fehlermeldung vom Typ E_USER_NOTICE aus.
     * Ab PHP 4.3.0 stehen noch __CLASS__ und __FUNCTION__ zur Verfuegung. Da diese Objekt Version jedoch unter 4.1.6 entwickelt wird, stehen nur __FILE__ und __LINE__ als Parameter bereit.
     * Die Funktion raiseError arbeitet mit der PHP Funktion trigger_error(). Wird der PHP Error Handler ueberschrieben, koennen individuelle Fehlerprotokolle erstellt werden.
     *
     * @param string $file Datei (in der, der Fehler aufgetreten ist)
     * @param int $line Zeilennummer
     * @param string $msg Fehlermeldung (Setzt sich zusammen aus: Fehler in der Klasse -Platzhalter-, Datei -Platzhalter-, Zeile -Platzhalter- Meldung: -Fehlermeldung-
     */
    protected function raiseError(string $file, int $line, string $msg): void
    {
        if(error_reporting() == 0) {
            return;
        }
        $error = $msg;
        $error .= ' in class ' . $this->getClassName() . ', file ' . $file . ', line ' . $line;

        trigger_error($error);
    }

    /**
     * Wirft eine Xception am Bildschirm aus oder schreibt sie in das PHP Logfile aus.
     *
     * Wohin die Xception Ihre Fehlermeldung sendet (Bildschirm, Logfile, Mail), wird in der Xception Klasse festgelegt.
     *
     * @deprecated
     * @param Xception $Xception Die Xception oder null, falls es sich bei diesem Objekt selbst um eine Xception handelt.
     */
    public function throwException($Xception = null)
    {
        if(is_null($Xception) and $this instanceof Xception) {
            $Xception = &$this;
        }
        if($Xception) {
            /* @var $Xception Xception */
            $Xception->raiseError();
        }
        else {
            echo 'Fatal exception error in Object::throwException!';
            die('Script terminated');
        }
    }

    /**
     * Ueberprueft Parameter $data, ob es sich um eine Xception handelt.
     *
     * @deprecated
     * @param Xception $data Wert, der ueberprueft wird, ob es sich um eine Xception handelt.
     * @param int $code Wenn $data eine Xception ist, wird true zurueckgeben; aber wenn der Parameter $code ein String ist und $obj->getMessage() == $code oder $code ist ein integer und $obj->getCode() == $code
     * @return bool true wenn es sich bei dem Parameter um eine Xception handelt.
     **/
    public function isError($data, $code = null): bool
    {
        if($data instanceof Xception) {
            if(is_null($code)) {
                return true;
            }
            elseif(is_string($code)) {
                return ($data->getMessage() == $code);
            }
            else {
                return ($data->getCode() == $code);
            }
        }
        return false;
    }
}