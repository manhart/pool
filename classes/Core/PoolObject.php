<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes\Core;

use ReflectionClass;

class PoolObject
{
    /**
     * @constant string the extension of the class files
     */
    public const CLASS_EXTENSION = '.class.php';

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
    private bool $isPOOL;

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
            $this->className = (new ReflectionClass($this))->getShortName();
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
            $this->classFilename = (new ReflectionClass($this))->getFileName();
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
        if(!isset($this->isPOOL)) {
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
                defined('BASE_NAMESPACE_PATH') ? constant('BASE_NAMESPACE_PATH') : DIR_DOCUMENT_ROOT
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

            // old style
            $filename = $classRootDir . $className . PoolObject::CLASS_EXTENSION;
            if(file_exists($filename)) {
                require_once $filename;
                return true;
            }

            // PSR-4 style
            $filename = $classRootDir . $className . '.php';
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
    protected function raiseError(string $file, int $line, string $msg, $error_level = E_USER_NOTICE): void
    {
        if(error_reporting() == 0) {
            return;
        }
        $error = $msg;
        $error .= ' in class ' . $this->getClassName() . ', file ' . $file . ', line ' . $line;

        trigger_error($error, $error_level);
    }
}