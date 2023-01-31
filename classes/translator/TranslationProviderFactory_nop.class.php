<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory_nop.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
declare(strict_types=1);
namespace pool\classes\translator;

use Exception;

/**
 * A compliant implementation that works for every language and does nothing<br>
 *All queries will return Omitted and deliver a null result
 */
class TranslationProviderFactory_nop extends TranslationProviderFactory implements TranslationProvider
{
    private string $lang;
    private string $locale;

    function getLang(): string
    {
        return $this->lang;
    }

    function getLocale(): string
    {
        return $this->locale;
    }


    function getResult(): ?Translation
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    function query(string $key): int
    {
        return self::TranslationOmitted;
    }

    function increaseMissCounter(?string $key): int
    {
        return self::NotImplemented;
    }

    function alterTranslation(int $status, ?string $value, string $key): int
    {
        return -self::NotImplemented;
    }

    function getError(): ?Exception
    {
        return null;
    }

    function clearError(): void{}

    function getAllTranslations(): array
    {
        return [];
    }


    function getFactory(): TranslationProviderFactory
    {
        return self::$instance;
    }

    function hasLang(string $language, float &$quality = 0): bool
    {
        $quality = 0;
        return true;
    }

    public function getBestProvider(string $proposed, float &$fitness = 0): string
    {
        $fitness = 0;
        return $proposed;
    }

    protected function getProviderInternal(string $providerName, string $locale): TranslationProvider
    {
        $provider = new self();
        $provider->locale = $locale;
        $provider->lang = $providerName;
        return $provider;
    }

    function identity(): string
    {
        return md5(self::class);
    }
}