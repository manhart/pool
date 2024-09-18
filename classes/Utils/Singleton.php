<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\classes\Utils;

/**
 * Class Singleton
 * Helper function that can simulate an object as a singleton.
 *
 * @since 2004/03/30
 * @package pool\classes\Utils
 */
final class Singleton
{
    /**
     * Stores the instances of the classes
     *
     * @var array
     */
    private static array $instances = [];

    /**
     * Returns a unique instance of an object (singleton helper function).
     *
     * @param string $class class name
     * @return object|null instance of the class
     */
    public static function get(string $class): ?object
    {
        if (Singleton::$instances[$class] ?? false) {
            return Singleton::$instances[$class];
        }

        $args = [];
        if (func_num_args() > 1) {
            $args = func_get_args();
            array_shift($args);
        }

        Singleton::$instances[$class] = new $class(...$args);
        return Singleton::$instances[$class];
    }
}