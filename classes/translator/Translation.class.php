<?php
/*
 * g7system.local
 *
 * Translation.class.php created at 06.12.22, 15:47
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
declare(strict_types=1);
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

    public function getKey():string
    {
        return $this->key;
    }

}