<?php
declare(strict_types = 1);
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

class TranslationProviderFactory_ResourceFile extends TranslationProviderFactory
{
    /**
     * @throws Exception
     */
    static function create(string $resourceDir = null): static
    {
        $new = new static();
        $new->setResourceDir(addEndingSlash($resourceDir));
        return $new;
    }

    /**
     * resources directory with the language files
     *
     * @var string|null
     */
    private ?string $directory = null;

    /**
     * @var string
     */
    private string $extension = '.php';

    /**
     * holds the translations
     *
     * @var array
     */
    protected array $translation = [];

    /**
     * sets the resources directory
     *
     * @param string $directory
     * @return $this
     * @throws Exception
     */
    public function setResourceDir(string $directory): static
    {
        //ignore empty
        if (!$directory) {
            return $this;
        }
        if (!is_dir($directory)) {
            throw new Exception('Resource directory '.$directory.' not found.');
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
        return buildFilePath($this->directory, $language.$this->extension);
    }

    function hasLang(string $language, float &$quality = -1): bool
    {
        return file_exists($this->resourceFileName($language));
    }

    /**
     * @param string $providerName
     * @param string $locale
     * @return TranslationProvider
     * @throws Exception
     */
    protected function getProviderInternal(string $providerName, string $locale): TranslationProvider
    {
        if (!$this->directory) throw new Exception("Factory has not been initialized");
        $resourceFileName = $this->resourceFileName($providerName);
        return new TranslationProvider_ResourceFile($this, $providerName, $locale, $resourceFileName);
    }

    function identity(): string
    {
        return md5(self::class.$this->directory.$this->extension);
    }
}