<?php
/*
 * g7system.local
 *
 * TranslationProvider_ToolDecorator.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
declare(strict_types=1);
namespace pool\classes\translator;
/**
 * A decorator that adds functionality for interacting with the Translator tool
 */
class TranslationProvider_ToolDecorator extends TranslationProvider_BaseDecorator
{
    static ?array $postbox = null;

    static function isActive(): bool
    {
        if (self::$postbox === false)
            return false;
        elseif (is_array(self::$postbox))
            return true;
        else
            return self::startSession();
    }
    static function startSession():bool{
        return false;//TODO
        //read postbox
        //decide
        //(clear last Request)
        //Disable Translation cache

    }

    public function __construct(TranslationProvider $provider)
    {
        parent::__construct($provider);
        //TODO save modifications?
    }

    public function query(string $key): int
    {
        $queryResult = parent::query($key);
        if ($queryResult != self::OK){
            //$this->provider->getLang()
            // TODO: add to postbox (key, provider lang, result)
        }
        return $queryResult;
    }

    public function getResult(): ?Translation
    {
        $translation = parent::getResult();
        // TODO: wrap in markes (html tags?) and add to postbox (key, identifier?, provider lang, result)
        return $translation;
    }
    //TODO Translator Tool
}