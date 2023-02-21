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
        $this->loadTranslations();
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
        $result = $this->translations[$key] ?? null;
        $this->lastResult = print_r($result, true);
        if (!array_key_exists($key, $this->translations))
            return self::TranslationNotExistent;
        elseif (is_string($result))
            return self::OK;
        elseif ($result === null)
            return self::TranslationKnownMissing;
        elseif ($result === false)
            return self::TranslationOmitted;
        else
            return self::TranslationInadequate;
    }

    function alterTranslation(int $status, ?string $value, string $key): int
    {
        //Validate key format
        //pattern xxx.xyz a period with text left and right
        if (!preg_match('/^[A-Za-z0-9-_]+\.[A-Za-z.0-9-_]+$/', $key)) {
            //invalid key
            if ($value != null) {
                $value = htmlspecialchars(print_r($value, true));
                $this->error = new Exception("Invalid Key '$key' trying to write {$value}");
            } else
                $this->error = new Exception("Invalid Key '$key' trying to insert empty translation");
            return self::Error;
        }
        try {
            //refresh
            $this->loadTranslations();
            //manipulate Translations
            $this->translations[$key] = $value;
            $code = var_export($this->translations, true);
            $newContent = "<?php return $code;";
            //write generated resource to disk
            file_put_contents($this->resourceFile, $newContent);
        } catch (Exception $e) {
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

    function getAllTranslations(): array
    {
        return $this->translations;
    }

    /**recursively flattens out an array
     * @param array $nested
     * @param array $flat
     * @param array $keyStack
     * @return void
     */
    function flattenArray(array &$nested, array &$flat, array &$keyStack = []): void
    {
        foreach ($nested as $subKey => &$subValue) {
            $keyStack[] = $subKey;
            if (is_array($subValue))
                $this->flattenArray($subValue, $flat, $keyStack);
            else
                $flat[implode('.', $keyStack)] = $subValue;
            array_pop($keyStack);
        }
    }

    function getFactory(): TranslationProviderFactory_ResourceFile
    {
        return $this->factory;
    }

    /**
     * @return void
     * @throws Exception
     */
    public function loadTranslations(): void
    {
        try {
            $this->translations = include($this->resourceFile);
        } catch (Exception $e) {
            throw new Exception("Failed to load Translation-Ressource for {$this->lang}", previous: $e);
        } catch (\TypeError) {
            if (IS_DEVELOP)
                $this->translations = [];//Initialize the resource. It's likely a new file
            else//Let Upstream know this resource is bad to not make it harder than necessary to trace the problem back
                throw new Exception("Failed to load Translation-Ressource for {$this->lang}. Bad Format");
        }
    }
}