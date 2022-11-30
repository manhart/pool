<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes\translator;

abstract Class TranslationProviderFactory
{
    protected static TranslationProviderFactory $instance;
    static function create():static{
        return static::$instance ?? static::$instance= new static();
    }
    protected function __construct(){}
    abstract function hasLang(string $language, float &$quality = 0):bool;
    function getBestLang(string $proposed, float &$fitness = 0):string|false{
        if ($this->hasLang($proposed))
            return $proposed;
        $generic = Translator::getPrimaryLanguage($proposed);
        if ($this->hasLang($generic))
            return $generic;
        return false;
    }
    abstract function getProvider(string $language, string $locale):TranslationProvider;
}