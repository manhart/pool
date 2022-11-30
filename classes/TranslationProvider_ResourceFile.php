<?php
/*
 * g7system.local
 *
 * TranslationProvider_ResourceFile.php created at 25.11.22, 11:29
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes;

use Exception;

class TranslationProvider_ResourceFile implements TranslationProvider
{
    private string $lang;
    private string $locale;
    private string $resourceFile;
    private array $translations;
    private ?Exception $error = null;
    private ?string $lastResult = null;

    /**
     * @throws Exception
     */
    public function __construct(string $lang, string $locale, string $resourceFileName)
    {
        $this->lang = $lang;
        $this->locale = $locale;
        $this->resourceFile = $resourceFileName;
        try {
            $this->translations = include($resourceFileName);
        } catch (Exception){
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

    function getResult(): ?string
    {
        return $this->lastResult;
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
        if (!isset($this->translations[$key]))
            return self::TranslationNotExistent;
        $result = $this->translations[$key];
        $this->lastResult = $result;
        if ($result === null)
            return self::TranslationKnownMissing;
        else
            return self::OK;
    }

    function alterTranslation(int $status, ?string $value, string $key): int
    {
        try {
            //refresh
            $this->translations = include($this->resourceFile);
            //manipulate Translations
            $this->translations[$key] = $value;
            //format as PHP code
            $phpArray = [];
            foreach ($this->translations as $key => $value){
                $phpArray[]= "'$key' => '$value',\n";
            }
            $phpArray = implode($phpArray);
            $newContent = <<<PHP
<?php \n\n return[ \n
$phpArray
];
PHP;
            //write generated resource to disk
            file_put_contents($this->resourceFile, $newContent);
        }catch (Exception $e){
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
}