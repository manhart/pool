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

use Exception;

/**
 * A base decorator for creating derived decorators which add functionality to TranslationProvider's
 */
abstract class TranslationProvider_BaseDecorator implements TranslationProvider
{
    protected TranslationProvider $provider;

    public function __construct(TranslationProvider $provider)
    {
        $this->provider = $provider;
    }

    function getLang(): string
    {
        return $this->provider->getLang();
    }

    function getLocale(): string
    {
        return $this->provider->getLocale();
    }

    function getResult(): ?Translation
    {
        return $this->provider->getResult();
    }

    function increaseMissCounter(?string $key): int
    {
        return $this->provider->increaseMissCounter($key);
    }

    function query(string $key): int
    {
        return $this->provider->query($key);
    }

    function alterTranslation(int $status, ?string $value, string $key): int
    {
        return $this->provider->alterTranslation($status, $value, $key);
    }

    function getError(): ?Exception
    {
        return $this->provider->getError();
    }

    function clearError(): void
    {
        $this->provider->clearError();
    }

    function getAllTranslations(): array
    {
        return $this->provider->getAllTranslations();
    }

    function getFactory(): TranslationProviderFactory
    {
        return $this->provider->getFactory();
    }
}