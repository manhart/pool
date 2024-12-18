<?php
declare(strict_types = 1);
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
use stdClass;
use function dirname;
use function error_reporting;
use function get_parent_class;
use function realpath;
use function strlen;
use function substr_compare;
use function trigger_error;
use const E_USER_NOTICE;

/**
 * Core class for all POOL objects. Provides basic functionality for all POOL objects.
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class PoolObject extends stdClass
{

    /**
     * @var bool|null determines whether the class is the POOL base library
     */
    private bool $isPOOL;

    /**
     * Determines the full name of the class of the object, stores it temporarily and returns it. Also contains namespaces.
     * @deprecated
     */
    public function getClass(): string
    {
        return self::theClass();
    }

    /**
     * Returns the fully qualified class name
     *
     * @return string fully qualified class name
     * @deprecated
     */
    public static function theClass(): string
    {
        return static::class;
    }

    /**
     * Retrieves the parent class name for the object
     *
     * @return string name of the parent class
     * @deprecated
     */
    public function getParentClass(): string
    {
        return get_parent_class($this);
    }

    /**
     * Returns the absolute directory of the class
     */
    public function getClassDirectory(): string
    {
        return dirname($this->getClassFile());
    }

    /**
     * Gets the file in which the class has been defined
     */
    public function getClassFile(): string
    {
        return $this->getReflectionClass()->getFileName();
    }

    /**
     * Instantiates a ReflectionClass object and returns it
     */
    public function getReflectionClass(): ReflectionClass
    {
        return new ReflectionClass($this);
    }

    /**
     * Determines whether the class is inside the POOL (base library)
     *
     * @return bool
     */
    protected function isPOOL(): bool
    {
        if (!isset($this->isPOOL)) {
            $poolRealpath = realpath(DIR_POOL_ROOT);
            $this->isPOOL = substr_compare($this->getClassFile(), $poolRealpath, 0, strlen($poolRealpath)) === 0;
        }
        return $this->isPOOL;
    }

    /**
     * Raises a PHP error
     *
     * @param string $file Use __FILE__ if you want to use the file where the error occurred.
     * @param int $line Use __LINE__, if you want to use the line where the error occurred.
     * @param string $msg The error message
     * @deprecated
     */
    protected function raiseError(string $file, int $line, string $msg, $error_level = E_USER_NOTICE): void
    {
        if (error_reporting() === 0) {
            return;
        }
        $error = $msg;
        $error .= ' in class '.$this->getClassName().', file '.$file.', line '.$line;

        trigger_error($error, $error_level);
    }

    /**
     * Determines the short name of the class of the object, stores it temporarily and returns it.
     */
    public function getClassName(): string
    {
        return $this->getReflectionClass()->getShortName();
    }

    /**
     * Adds an explicit branch to a fluent interface
     * @link https://wiki.php.net/rfc/nullsafe_operator How to use optional chaining
     */
    public function if(bool $continue): ?static {
        return $continue ? $this : null;
    }
}
