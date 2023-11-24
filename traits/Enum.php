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

namespace pool\traits;

trait Enum
{
    abstract public static function cases(): array;
    public static function fromString(string $value): static
    {
        foreach (static::cases() as $enumItem) {
            if ($enumItem->name === $value) {
                return $enumItem;
            }
        }

        throw new \InvalidArgumentException("Invalid value for enum type " . self::class . ": $value");
    }
}