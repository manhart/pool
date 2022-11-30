<?php
/*
 * g7system.local
 *
 * TranslationProvider_ToolDecorator.php created at 16.11.22, 14:56
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes;

use Exception;

/**
 * A base decorator for creating derived decorators which add functionality to TranslationProvider's
 */
abstract class TranslationProvider_BaseDecorator implements TranslationProvider
{

    private TranslationProvider $provider;

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


    function getResult(): ?string
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
}