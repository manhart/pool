<?php
/*
 * g7system.local
 *
 * Translator.class.php created at 14.09.20, 14:01
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2020, GROUP7 AG
 * @see https://www.i18next.com/
 */

namespace pool\classes;


use Exception;
use function explode;

class Translator extends \PoolObject
{
    /**
     *This perfectly readable regex Matches Blocks like <code>{TRANSL key[(args)][??<default>]}</code><br>
     *Intended for use with ::translateWithRegEx<br>
     *The Content should avoid constructs like )?? )} in args and >} in default to avoid failure of the RegEx<br>
     *key and args are retrieved as handle in group 2<br>
     */
    const CURLY_TAG_REGEX = '/\{(TRANSL) +([^\s(?]+(?>\((?>[^)]*(?>\)(?!\?\?|}))?)+\))?)(?>\?\?<((?>[^>]*(?>>(?!}))?)*)>)?}/su';
    /**
     *Matches Blocks like <code><!-- TRANSL handle -->freeform-text<!-- END handle --></code>
     */
    const COMMENT_TAG_REGEX = '/<!-- (TRANSL) ([^>]+) -->(.*?)<!-- END \1 -->/s';

    /**
     * @var bool Determines whether text-translations with args should be resolved<br>
     * Used in caching of template translations
     */
    private bool $formatMessages = true;

    /**
     * @var array<string, TranslationProviderFactory> holds the TranslationProviderFactory's to use for loading a language
     */
    private array $translationResources = [];

    /**
     * @var array<string, TranslationProvider> holds the currently available languages and their associated translation-providers<br>
     * in the format (lang => provider)
     */
    private array $loadedLanguages = [];

    /**
     * @var array<string, TranslationProvider> Stores a list of languages for use in translations.<br>
     * Intended to hold a subset of $loadedLanguages which will be used to look up translation-keys
     */
    private array $activeLanguages =  [];

    /**
     * @var TranslationProvider|null the default Provider for lang0<br>Used to override fallback behavior
     */
    private ?TranslationProvider $defaultProvider = null;
    private TranslationProviderFactory $translationResource;

    /**
     * @param TranslationProviderFactory|null $translationResource
     */
    public function __construct(TranslationProviderFactory $translationResource = null)
    {
        parent::__construct();
        if ($translationResource)
            $this->addTranslationResource($translationResource);
    }

    /**
     * @return TranslationProvider
     */
    public function getDefaultProvider(): TranslationProvider
    {
        return $this->defaultProvider;
    }

    /**
     * @param TranslationProvider $defaultProvider
     * @return Translator
     */
    public function setDefaultProvider(TranslationProvider $defaultProvider): static
    {
        $this->defaultProvider = $defaultProvider;
        return $this;
    }

    /**
     * @return array
     */
    public function getTranslationResources(): array
    {
        return $this->translationResources;
    }



    /**
     * @param TranslationProviderFactory $translationResource
     * @return Translator
     */
    public function addTranslationResource(TranslationProviderFactory $translationResource): self
    {
        if (!in_array($translationResource, $this->translationResources))
            $this->translationResources[] = $translationResource;
        return $this;
    }


    /**
     * @param TranslationProviderFactory $translationResource
     * @return bool
     */
    public function removeTranslationResource(TranslationProviderFactory $translationResource): bool
    {
        $resources = $this->translationResources;
        if (($key = array_search($translationResource, $resources)) !== false) {
            unset($resources[$key]);
            return true;
        }else
            return false;
    }

    /**
     * @deprecated
     * gets the instance <s>via lazy initialization (created on first usage)</s> stored by the Weblication
     */
    public static function getInstance(): Translator
    {
        return \Weblication::getInstance()->getTranslator();
    }

    /**
     * @return bool
     * @throws Exception
     * @deprecated
     */
    public function wasInitialized(): bool
    {
        throw new Exception("wasInitialized is deprecated");
    }



    /**@deprecated merge with get
     * @throws Exception
     */
    public function nget(string $key, int $n, ...$args)
    {
        throw new Exception("nget is deprecated");
    }

    /**@deprecated
     * @return array|null
     */
    public function getParseErrors(): ?array
    {
        return null;
    }

    public function getPrimaryLocale(): string
    {
        foreach ($this->activeLanguages as $provider){
            return $provider->getLocale();
        }
        return '';
    }

    /**Gets the first language that is loaded or offered by a registered TranslationProvider
     * @param string $lang langauge to look for
     * @return array languageLoaded => translation Provider
     */
    private function fetchLanguage(string $lang): array
    {
        //look in languages that are already loaded
        $provider = $this->loadedLanguages[$lang]??null;
        if ($provider)
            return [$lang => $provider];
        //move through the list of translation-sources
        foreach ($this->translationResources as $factory) {
            if ($altLang = $factory->getBestLang($lang)){
                //see if language offered by the source is already loaded
                $provider = $this->loadedLanguages[$altLang]??
                    //if not loaded take this first offer ignoring its quality
                    $factory->getProvider($altLang, $lang);
                //put the new thing on the list of loaded languages
                $this->loadedLanguages[$altLang] = $provider;
                return [$altLang => $provider];
            }
        }
        return [];
    }


    /** Sets a new active language list from the $language parameter and
     * returns the active language list for later restore using this function again
     * @param array|string|null $language A language list with language names as keys and optionally TranslationProviders as value<br>
     * or the name of the Language to use
     * @return array|null
     * @throws Exception missing TranslationProvider
     */
    public function swapLangList(array|string|null $language): ?array
    {
        if ($language ===null)
            return null;
        $newActiveLanguages = (array)$language;
        foreach ($newActiveLanguages as $lang => &$provider){
            if (!$provider){
                $loaded = $this->fetchLanguage($lang);
                //get value of the language
                $provider = reset($loaded)?:
                    //handle empty array
                    throw new Exception("Language $lang could not be loaded");
            }
        }
        if ($this->defaultProvider != null)
            $newActiveLanguages['lang0']= $this->defaultProvider;
        $oldActiveLanguages = $this->activeLanguages;
        $this->activeLanguages = $newActiveLanguages;
        return $oldActiveLanguages;
    }

    public function suppressFormatting(bool $suppressed = true):bool{
        $oldValue = $this->formatMessages;
        $this->formatMessages = !$suppressed;
        return !$oldValue;
    }

    /**
     * @param string $sourceFile
     * @param string $lang
     * @return string
     * @throws Exception missing TranslationProvider
     */
    public function translateFile(string $sourceFile, string $lang):string{
        //get variables
        $translatedDir = buildDirPath(dirname($sourceFile), $lang);
        if (!is_dir($translatedDir))
            mkdir($translatedDir);
        $filename = basename($sourceFile);
        $translatedFile = $translatedDir . $filename;
        $manualPreTranslatedFile = buildFilePath($translatedDir, 'man', $filename);
        //manual Translation exists
        if (file_exists($manualPreTranslatedFile))
        //override source
        $sourceFile = $manualPreTranslatedFile;
        $sourceContent = file_get_contents($sourceFile);
        //parse static tags
        $defaultFormatDirective =  $this->suppressFormatting();
        $countChanges = 0;
        $translatedContent = $this->parse($sourceContent, $lang, $countChanges);
        $this->suppressFormatting($defaultFormatDirective);
        //save translation
        unlink($translatedFile);
        if ($countChanges)
            //save translation
            file_put_contents($translatedFile, $translatedContent);
        else //hardlink unchanged file
            link($sourceFile, $translatedFile);
        return $translatedFile;
    }

    /** Parses TRANSL Tokens in a string and replaces them with their translation, non-translatable tokens will be ignored
     * @param string $templateContent the string to check for tokens
     * @param string|array|null $language One-of langauge list that overrides the current setting for this translation
     * @param int $countChanges The number of unique tags that have been translated. 0 means that no replacements were made
     * @return string the result of translation
     * @throws Exception missing TranslationProvider
     */
    public function parse(string $templateContent, string|array|null $language = null, int &$countChanges = 0): string
    {
        //Backup settings and apply the language defined by the caller
        $defaultLangList = $this->swapLangList($language);
        //A buffered replace
        $changes = array();
        //collect replacements
        $this->translateWithRegEx($templateContent,self::COMMENT_TAG_REGEX, $changes);
        $this->translateWithRegEx($templateContent, self::CURLY_TAG_REGEX, $changes);
        //Do replacement
        $translatedContent = strtr($templateContent, $changes);
        $countChanges = count($changes);
        unset($changes);
        //Restore active languages
        $this->swapLangList($defaultLangList);
        return $translatedContent;
    }

    /** Performs the parsing of the content using a specific RegularExpression for finding and analyzing the translation tags<br>
     * Uses translate without language parameter so set activeLangauges accordingly
     * @param string $content the string to check for tokens
     * @param string $regEX A pattern that matches the tag to replace and captures Keyword, handle/key and the default value / tag content
     * @param array $changes An array to store the translations in (tag => translation) for use with strtr(). New tags will be inserted present tags won't be fetched again
     * @return bool false on RegEx-failure
     */
    public function translateWithRegEx(string $content, string $regEX, array &$changes): bool
    {
        //More specific copy of code from \TempCoreHandle::findPattern
        preg_match_all($regEX, $content, $matches, PREG_SET_ORDER);
        $outcome = \checkRegExOutcome($regEX, $content);
        foreach($matches as $match) {
            //the entire Comment Block
            $fullMatchText = $match[0];
            //the handle part -> the key
            $handle = ($match[2]);
            //the freeform-text part -> the default value
            $tagContent = ($match[3]);
            //skip repeating tags
            if (!isset($changes[$fullMatchText])) {
                $value = $this->translateTag($handle, $tagContent);
                if ($value !== $fullMatchText)
                    $changes[$fullMatchText] = $value;
            }
        }
        unset($matches);
        return $outcome;
    }

    /** Translates a TRANSL Tag
     * @param string $handle The tag handle with the Key
     * @param string|null $tagContent The content with the default translation
     * @return string The translated value
     */
    public function translateTag(string $handle, ?string $tagContent):string{
        $key = strtok($handle, '(');
        $args = trim(strtok(''), ' ()');
        //TODO decode args
        return $this->getTranslation($key, $tagContent, $args);
    }

    /**
     * @throws Exception
     */
    public function getWithLanguage(string $key, string|array $lang, ?string $defaultMessage = null, ?array $args = null, ?string $locale = null): string
    {
        $backupLangList = $this->swapLangList($lang);
        $translatedMessage = $this->getTranslation($key, $defaultMessage, $args, $locale );
        $this->swapLangList($backupLangList);
        return $translatedMessage;
    }

    /**@param string $key
     * @param mixed|null ...$args
     * @return string
     * @deprecated update Code to use ::getTranslation
     * Compatibility wrapper for getTranslation
     */
    public function get(string $key, ...$args):string
    {
        return $this->getTranslation($key, null, $args);
    }

    /**
     * @param string $key
     * @param string|null $defaultMessage
     * @param array|null $args
     * @param string|null $locale
     * @return string
     */
    public function getTranslation(string $key, ?string $defaultMessage = null, ?array $args = null, ?string $locale = null): string
    {
        $translationProvider = null;
        foreach ($this->activeLanguages as $provider){
            switch ($provider->query($key)){
                /** @noinspection PhpMissingBreakStatementInspection Stuff that finishes this Lookup */
                case $provider::TranslationInadequate:
                    $provider->increaseMissCounter($key);
                case $provider::OK:
                    $translationProvider = $provider;
                    //leave
                    break 2;
                    /** @noinspection PhpMissingBreakStatementInspection Things that require trying the next language on the list*/
                case $provider::TranslationNotExistent:
                    //add Translation with missing-status
                    $provider->alterTranslation($provider::TranslationKnownMissing, null, $key);
                    //go on with handling the missing Translation
                    /** @noinspection PhpMissingBreakStatementInspection */
                case $provider::TranslationKnownMissing:
                    $provider->increaseMissCounter($key);
                case $provider::TranslationOmitted:
                    //move on
                    continue 2;
                default:
                    //"Errorhandling"
                    //$exception = $provider->getError();
                    continue 2;
            }
        }
        $message = $translationProvider?->getResult() ?? $defaultMessage;
        if ($message == null)
            return "String $key not found";
        if (!$args) {
            return $message;
        }else {
            $locale ??= $translationProvider?->getLocale() ?? self::getPrimaryLocale();
            $formatter = \MessageFormatter::create($locale, $message);
            $formattedTranslation = $formatter->format($args);
            if ($formattedTranslation===false)
                return $formatter->getErrorMessage();
            else
                return $formattedTranslation;
        }
    }


    /**TODO
     * detects locale from browser
     *
     * @param bool $all
     * @param string $defaultLocale
     * @return array locale
     */
    public function parseLangHeader(bool $all = false, string $defaultLocale = 'en_US', ): array
    {
        $header = [$defaultLocale => 0.0];
        // Try detecting better locales from browser headers
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $el) {
                $temp = explode(';q=', $el);
                $l = trim($temp[0]);
                $l = str_replace('-', '_', $l);
                $q = (float)($temp[1] ?? 1);
                $header[$l] = $q;
            };
        }
        arsort($header);
        $languages = [];
        foreach ($header as $locale => $value) {
            //load language
            $fetched = $this->fetchLanguage($locale);
            if ($all || sizeof($fetched) != 0) {
                //get alias and save language
                $lang = array_key_first($fetched) ?? static::getPrimaryLanguage($locale);
                //add to the list of languages if not defined yet
                if (!isset($languages[$lang])) {
                    $languages[$lang] = $locale;
                }
            }
        }
        return $languages;
    }

    /**
     * get the primary language of the locale
     *
     * @param string $locale
     * @return string language code
     */
    public static function getPrimaryLanguage(string $locale): string
    {
        if(function_exists('locale_get_primary_language'))
            $language = locale_get_primary_language($locale);
        else
            list($language,) = explode('_', $locale);
        return $language;
    }

}