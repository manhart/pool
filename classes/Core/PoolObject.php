<?php declare(strict_types = 1);
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

/**
 * Core class for all POOL objects. Provides basic functionality for all POOL objects.
 *
 * @package pool\classes\Core
 * @since 2003-07-10
 */
class PoolObject extends stdClass
{
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
        if($this->class === '') {
            $this->class = get_class($this);
        }

        return $this->class;
    }

    /**
     * Retrieves the parent class name for the object
     *
     * @return string name of the parent class
     */
    public function getParentClass(): string
    {
        return get_parent_class($this);
    }

    /**
     * Determines whether the class is inside the POOL (base library)
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
     * Gets the filename of the file in which the class has been defined
     *
     * @return string
     */
    public function getClassFilename(): string
    {
        if($this->classFilename === '') {
            $this->classFilename = (new ReflectionClass($this))->getFileName();
        }
        return $this->classFilename;
    }

    /**
     * Raises a PHP error
     *
     * @param string $file Use __FILE__ if you want to use the file where the error occurred.
     * @param int $line Use __LINE__, if you want to use the line where the error occurred.
     * @param string $msg The error message
     */
    protected function raiseError(string $file, int $line, string $msg, $error_level = E_USER_NOTICE): void
    {
        if(error_reporting() === 0) {
            return;
        }
        $error = $msg;
        $error .= ' in class '.$this->getClassName().', file '.$file.', line '.$line;

        trigger_error($error, $error_level);
    }

    /**
     * Determines the short name of the class of the object, stores it temporarily and returns it.
     *
     * @return string
     */
    public function getClassName(): string
    {
        if($this->className === '') {
            $this->className = (new ReflectionClass($this))->getShortName();
        }
        return $this->className;
    }
}