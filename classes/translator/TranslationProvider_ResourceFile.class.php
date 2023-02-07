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

class TranslationProvider_ResourceFile implements TranslationProvider
{
    private string $lang;

    private string $locale;

    private string $resourceFile;

    private array $translations;

    private ?Exception $error = null;

    private ?string $lastResult = null;
    private TranslationProviderFactory_ResourceFile $factory;
    private ?string $lastKey;

    /**
     * @throws Exception
     */
    public function __construct(TranslationProviderFactory_ResourceFile $factory, string $lang, string $locale, string $resourceFileName)
    {
        $this->factory = $factory;
        $this->lang = $lang;
        $this->locale = $locale;
        $this->resourceFile = $resourceFileName;
        try {
            $this->translations = include($resourceFileName);
        }
        catch(Exception) {
            throw new Exception("Failed to load Translation-Ressource for $lang");
        }
    }

    function getLang(): string
    {
        return $this->lang;
    }

    function getLocale(): string
    {
        return $this->locale;
    }

    function getResult(): ?Translation
    {
        return new Translation($this, $this->lastResult, $this->lastKey);
    }

    /**
     * @inheritDoc
     */
    function increaseMissCounter(?string $key): int
    {
        return self::NotImplemented;
    }

    /**
     * @inheritDoc
     */
    function query(string $key): int
    {
        $this->lastKey = $key;
        if(!isset($this->translations[$key]))
            return self::TranslationNotExistent;
        $result = $this->translations[$key];
        $this->lastResult = $result;
        if($result === null)
            return self::TranslationKnownMissing;
        else
            return self::OK;
    }

    function alterTranslation(int $status, ?string $value, string $key): int
    {
        if (!preg_match('/^[A-Za-z.]*$/', $key)){
            //invalid key
            $this->error = new Exception("Invalid Key $key");
            return self::Error;
        }
        try {
            //refresh
            $this->translations = include($this->resourceFile);
            //manipulate Translations
            $this->translations[$key] = $value;
            $code = var_export($this->translations, true);
            $newContent = "<?php return $code;";
            //write generated resource to disk
            file_put_contents($this->resourceFile, $newContent);
        }
        catch(Exception $e) {
            //failed
            $this->error = $e;
            return self::Error;
        }
        return self::OK;
    }

    function getError(): ?Exception
    {
        return $this->error;
    }

    function clearError(): void
    {
        $this->error = null;
    }

    function getAllTranslations():array
    {
        return $this->translations;
    }


    function getFactory(): TranslationProviderFactory_ResourceFile
    {
        return $this->factory;
    }
}