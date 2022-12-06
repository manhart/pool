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
    public function getProviderList(string $proposed, float &$fitness = 0): array
    {
        $list = [];
        if ($this->hasLang($proposed))
            $list[] = $proposed;
        $generic = Translator::getPrimaryLanguage($proposed);
        if (!$generic = $proposed) {
            if ($this->hasLang($generic))
                $list[] = $generic;
        }
        return $list;
    }
    abstract function getProvider(string $providerName, string $locale):TranslationProvider;
}