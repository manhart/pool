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

use Exception;
use Template;

/**
 * A decorator that adds functionality for interacting with the Translator tool
 */
class TranslationProvider_ToolDecorator extends TranslationProvider_BaseDecorator
{
    const KEYWORD = "trnsl";
    static array|false|null $postbox = null;
    static string $sessionID = '';
    static string $requestTime = '';
    private string $lastKey;

    static function isActive(): bool
    {
        if (self::$postbox === false)
            return false;
        elseif (is_array(self::$postbox))
            return true;
        else
            return self::startSession();
    }

    static function startSession(): bool
    {
        //decide
        if (!($id = $_REQUEST[self::KEYWORD])) {
            self::$postbox = false;
            return false;
        } else {
            self::$requestTime = microtime();
            self::$sessionID = $id;
            self::readPostbox();
            register_shutdown_function(self::writePostbox(...));
            //Disable Translation cache
            @Template::setCacheTranslations(false);
            return true;
        }
    }

    /**
     * @param string|null $id
     * @return string
     * @throws Exception
     */
    private static function getPostboxPath(?string $id): string
    {
        $id ??= self::$sessionID;
        if (preg_match("/\w*/", $id))
            return buildDirPath(sys_get_temp_dir(), 'postbox', $id);
        else
            throw new Exception('Invalid sessionID');
    }

    public function query(string $key): int
    {
        $this->lastKey = $key;
        $queryResult = parent::query($key);
        if ($queryResult != self::OK) {
            //$this->provider->getLang()
            // TODO: add to postbox (key, provider lang, result)
            $this->writeToPostbox($this->provider, $key, status:$queryResult);
        }
        return $queryResult;
    }

    public function getResult(): ?Translation
    {
        $translation = parent::getResult();
        if ($translation == null) return null;
        $key = $translation->getKey();
        $this->writeToPostbox($this->provider, $key, message:$translation->getMessage());
        $lang = $this->provider->getLang();
        $keyWord = self::KEYWORD;
        $identifier = "$keyWord.$this->lastKey";
        $message = "<a href='#$identifier' id='$identifier' class='$keyWord' lang='$lang'>{$translation->getMessage()}</a>";
        return new Translation($translation->getProvider(),
            $message, $translation->getKey());
    }

    /**
     * @throws Exception
     */
    public static function writePostbox(string $id = null): void
    {
        $file = self::getPostboxPath($id);
        file_put_contents($file, var_export(self::$postbox));
    }

    /**
     * @throws Exception
     */
    public static function readPostbox(string $id = null): void
    {
        $file = self::getPostboxPath($id);
        self::$postbox = include $file;
    }

    /**
     * @param TranslationProvider $provider
     * @param string $key
     * @param mixed ...$content
     * @return void
     */
    public static function writeToPostbox(TranslationProvider $provider, string $key, ...$content): void
    {
        $content['lang'] = $provider->getLang();
        $content['locale'] = $provider->getLocale();
        $resourceIdentity = $provider->getFactory()->identity();
        $resourcePostbox = self::$postbox[$resourceIdentity] ?? [];
        $resourceRequestPostbox = $resourcePostbox[self::$requestTime] ?? [];
        $translationPostbox = $resourceRequestPostbox[$key] ?? [];
        $translationPostbox = array_merge($translationPostbox, $content);
        self::$postbox[$resourceIdentity][self::$requestTime][$key] = $translationPostbox;
    }
    public static function writeQueryToPostbox(string $key, ...$vars): void
    {
        $queryPostbox = self::$postbox['query'] ?? [];
        $queryRequestPostbox = $queryPostbox[self::$requestTime] ?? [];
        $keyQueryPostbox = $queryRequestPostbox[$key] ?? [];
        $keyQueryPostbox[] = $vars;
        self::$postbox['query'][self::$requestTime][$key] = $keyQueryPostbox;
    }
}