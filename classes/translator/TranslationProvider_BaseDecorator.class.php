<?php
/*
 * g7system.local
 *
 * TranslationProvider_BaseDecorator.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
declare(strict_types=1);
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

    function getFactory(): TranslationProviderFactory
    {
        return $this->provider->getFactory();
    }

}
