<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory_nop.php created at 15.11.22, 12:41
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes;

/**
 * A compliant implementation that works for every language and does nothing<br>
 *All queries will return Omitted and deliver a null result
 */
class TranslationProviderFactory_nop extends TranslationProviderFactory implements TranslationProvider
{
    private string $lang;

    function getLang(): string
    {
        return $this->lang;
    }

    function getResult(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    function query(string $key): int
    {
        return 3;
    }

    function increaseMissCounter(?string $key = null): int
    {
        return -1;
    }

    function addTranslation(int $status, ?string $value, ?string $key = null): int
    {
        return -1;
    }

    function getErrorMessage(): string
    {
        return "";
    }

    function clearError(): void{}

    function hasLang(string $language, float &$quality = 0): bool
    {
        $quality = 0;
        return true;
    }

    function getBestLang(string $proposed, float &$fitness = 0): string|false
    {
        $fitness = 0;
        return $proposed;
    }

    function getProvider(string $language): TranslationProvider|null
    {
        $provider = new self();
        $provider->lang = $language;
        return $provider;
    }
}