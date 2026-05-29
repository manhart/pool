<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

namespace pool\classes\Core\Reflection;
use pool\classes\GUI\GUI_Module;
use ReflectionException;
use ReflectionFunctionAbstract;

/**
 * Abstracted variant of a Closure. Allows implementors to create generated callable Objects that can also expose metadata via reflection datastructures
 * @see \Closure
 * @see Object::__invoke
 * @see GUI_Module::registerAjaxMethod()
 * @see reflectFunction()
 * @see ReflectionFunctionAbstract
 */
interface ReflectableFunction
{
    public function __invoke(...$args): mixed;

    /** @throws ReflectionException */
    public function reflect(): ReflectionFunctionAbstract;
}
