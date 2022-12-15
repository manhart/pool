<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
declare(strict_types=1);
namespace pool\classes\translator;

abstract Class TranslationProviderFactory
{
    protected static TranslationProviderFactory $instance;
    static function create():static{
        return static::$instance ?? static::$instance= new static();
    }
    protected function __construct(){}
    abstract function hasLang(string $language, float &$quality = 0):bool;
    public function getBestProvider(string $proposed, float &$fitness = 0): ?string
    {
        if ($this->hasLang($proposed))
           return $proposed;
        $generic = Translator::getPrimaryLanguage($proposed);
        if ($generic != $proposed) {
            if ($this->hasLang($generic))
                return $generic;
        }
        return null;
    }
    protected abstract function getProviderInternal(string $providerName, string $locale):TranslationProvider;

    /**Get a Provider offered by this Factory and assigns it a locale that should be used when a translation,
     * taken from the returned Provider, gets formatted.<br>
     * To get the name of Providers to use one may employ the getProviderList() method. Although this work is best left to the Translator class.<br>
     * Wraps the call to the child implementing the actual function and adds Decorators as Requested by the Client
     * @param string $providerName Name of the Provider to get
     * @param string $locale Locale to use for formatting
     * @return TranslationProvider
     *@see  TranslationProviderFactory::getBestProvider() Provides the Names of Providers
     * @see Translator::swapLangList() End Users should proabably use this function instead of getting providers themselve
     */
    public final function getProvider(string $providerName, string $locale):TranslationProvider{
        $translationProvider = $this->getProviderInternal($providerName, $locale);
        //decorate
        if (@TranslationProvider_ToolDecorator::isActive())
            $translationProvider = new TranslationProvider_ToolDecorator($translationProvider);
        return $translationProvider;
    }
}