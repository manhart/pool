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

namespace pool\classes\translator;

class Translation
{
    private TranslationProvider $provider;

    private string $message;

    private string $key;

    /**
     * @param TranslationProvider $provider
     * @param string $message
     * @param string $key
     */
    public function __construct(TranslationProvider $provider, string $message, string $key)
    {
        $this->provider = $provider;
        $this->message = $message;
        $this->key = $key;
    }

    /**
     * @return TranslationProvider
     */
    public function getProvider(): TranslationProvider
    {
        return $this->provider;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}