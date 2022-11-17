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

/**
 * A decorator that adds functionality for interacting with the Translator tool
 */
class TranslationProvider_ToolDecorator implements TranslationProvider
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

    function getResult(): ?string
    {
        //TODO
        return $this->provider->getResult();
    }

    /**
     * @inheritDoc
     */
    function increaseMissCounter(?string $key = null): int
    {
        //TODO
        return $this->provider->increaseMissCounter($key);
    }

    /**
     * @inheritDoc
     */
    function query(string $key): int
    {
        //TODO
        return $this->provider->query($key);
    }

    function addTranslation(int $status, ?string $value, ?string $key = null): int
    {
        return $this->provider->addTranslation($status, $value, $key);
    }

    function getErrorMessage(): string
    {
        return $this->provider->getErrorMessage();
    }

    function clearError(): void
    {
        $this->provider->clearError();
    }
}