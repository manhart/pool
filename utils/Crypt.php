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

namespace pool\utils;

use JetBrains\PhpStorm\Pure;
use Random\RandomException;
use SensitiveParameter;
use SodiumException;

final class Crypt
{
    /** * @throws SodiumException */
    #[Pure]
    public static function decrypt(string $pass, string $key): string
    {
        $nonce = substr($pass, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $pass = substr($pass, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        //decrypt
        return sodium_crypto_secretbox_open(
            $pass,
            $nonce,
            $key,
        );
    }

    /** @throws SodiumException
     * @throws RandomException
     */
    #[Pure]
    public static function encrypt(#[SensitiveParameter] string $secret_pass, string $key): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return $nonce.
            sodium_crypto_secretbox(
                $secret_pass,
                $nonce,
                $key,
            );
    }
}