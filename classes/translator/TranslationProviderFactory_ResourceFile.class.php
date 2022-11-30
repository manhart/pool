<?php
/*
 * g7system.local
 *
 * TranslationProviderFactory_ResourceFile.php created at 30.11.22, 13:21
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\classes\translator;

use Exception;

class TranslationProviderFactory_ResourceFile extends TranslationProviderFactory
{
    /**
     * @throws Exception
     */
    static function create(string $resourceDir = null):static{
        $new =  new static();
        $new->setResourceDir($resourceDir);
        return $new;
    }
    /**
     * resources directory with the language files
     * @var string|null
     */
    private ?string $directory = null;
    /**
     * @var string
     */
    private string $extension = '.php';

    /**
      * holds the translations
     * @var array
     */
    protected array $translation = array();

    /**
     * sets the resources directory
     * @param string $directory
     * @return $this
     * @throws Exception
     */
    public function setResourceDir(string $directory): static
    {
        //ignore empty
        if (!$directory){
            return $this;
        }
        if (!is_dir($directory)) {
            throw new Exception('Resource directory ' . $directory . ' not found.');
        }
        $this->translation = [];
        $this->directory = $directory;
        return $this;
    }


    /**
     * @param string $language
     * @return string
     */
    public function resourceFileName(string $language): string
    {
        return $this->directory . '/' . $language . $this->extension;
    }

    function hasLang(string $language, float &$quality = -1): bool
    {
        return file_exists($this->resourceFileName($language));
    }

    /**
     * @param string $language
     * @param string $locale
     * @return TranslationProvider
     * @throws Exception
     */
    function getProvider(string $language, string $locale): TranslationProvider
    {
        if(!$this->directory) throw new Exception("Provider has not been initialized");
        $resourceFileName = $this->resourceFileName($language);
        return new TranslationProvider_ResourceFile($language, $locale, $resourceFileName);
    }
}