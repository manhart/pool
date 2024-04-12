<?php declare(strict_types=1);
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    private string $identity;

    public function __construct(TranslationProvider $provider)
    {
        parent::__construct($provider);
        $this->identity = substr($provider->getFactory()->identity(),0,8);
        //$this->identity = $provider->getFactory()->identity();
    }

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
        if (!($id = $_REQUEST[self::KEYWORD]??false)) {
            self::$postbox = false;
            return false;
        } else {
            self::$requestTime = strval((int)(microtime(true)*1000));
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
        if (preg_match("/^\w*$/", $id)) {
            $dirPath = buildDirPath(sys_get_temp_dir(), 'postbox');
            if (!is_dir($dirPath))
                mkdir($dirPath);
            return $dirPath.$id;
        }
        else
            throw new Exception('Invalid sessionID');
    }

    public function query(string $key): int
    {
        $this->lastKey = $key;
        $queryResult = parent::query($key);
        $this->writeToPostbox($this->provider, $key);
        return $queryResult;
    }

    public function getResult(): ?Translation
    {
        $translation = parent::getResult();
        if ($translation == null) return null;
        $lang = $this->provider->getLang();
        $keyWord = self::KEYWORD;
        $identifier = "$keyWord.$this->identity.$this->lastKey";
        $message = "<a href='#$identifier' id='$identifier' class='$keyWord' lang='$lang'>{$translation->getMessage()}</a>";
        return new Translation($translation->getProvider(),
            $message, $translation->getKey());
    }

    static public function decorate(string $message): string
    {
        if(str_starts_with($message, '<a '))
            return $message;
        $keyWord = self::KEYWORD;
        return "<a class='$keyWord'>$message</a>";
    }

    /**
     * @throws Exception
     */
    public static function writePostbox(string $id = null): void
    {
        $file = self::getPostboxPath($id);

        file_put_contents($file, '<?php return '.var_export(self::$postbox, true).';?>');
    }

    /**
     * @throws Exception
     */
    public static function readPostbox(string $id = null): void
    {
        $file = self::getPostboxPath($id);
        @$postbox = include $file;
        if (!is_array($postbox))
            $postbox = [];
        self::$postbox = $postbox;
    }

    /**Creates an entry for the key-request in the postbox
     * @param TranslationProvider $provider
     * @param string $key
     * @return void
     */
    public static function writeToPostbox(TranslationProvider $provider, string $key): void
    {
        $resourceIdentity = $provider->getFactory()->identity();
        self::$postbox[$resourceIdentity] ??= [];
        self::$postbox[$resourceIdentity][self::$requestTime] ??= [];
        self::$postbox[$resourceIdentity][self::$requestTime][$key] ??= [];

    }

    /**Called by Translator after querying a Translation and adds more Data about each translation request
     * @param TranslationProviderFactory[] $resources
     * @param string $key
     * @param ...$details
     * @return void
     */
    public static function writeQueryToPostbox(array $resources, string $key, ...$details): void
    {
        foreach ($resources as $resource) {
            $resourceIdentity = $resource->identity();
            //check that resource has been queried
            if (!isset(self::$postbox[$resourceIdentity][self::$requestTime][$key]))
                continue;
            self::$postbox[$resourceIdentity][self::$requestTime][$key][] = $details;
        }
    }
}