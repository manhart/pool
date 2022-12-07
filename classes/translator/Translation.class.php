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
    private string $providerLocale;
    private string $message;

    /**
     * @param string $providerLocale
     * @param string $message
     */
    public function __construct(string $providerLocale, string $message)
    {
        $this->providerLocale = $providerLocale;
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getProviderLocale(): string
    {
        return $this->providerLocale;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

}