<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory.php created at 15.11.22, 12:05
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes;

abstract Class TranslationProviderFactory
{
    protected static TranslationProviderFactory $instance;
    static function getInstance():TranslationProviderFactory{
        return static::$instance ?? static::$instance= new static();
    }
    protected function __construct(){}
    abstract function hasLang(string $language, float &$quality = 0):bool;
    abstract function getBestLang(string $proposed, float &$fitness = 0):string|false;
    abstract function getProvider(string $language):TranslationProvider|null;
}