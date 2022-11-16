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

final class Translator extends \PoolObject
{
    /**@deprecated
     * @var string
     * TODO extract to Resource-file based Provider
     */
    private string $extension = '.php';

    /**@deprecated
     * language
     *
     * @var string
     */
    private string $language = '';

    /**@deprecated
     * default language
     *
     * @var string
     */
    private string $defaultLanguage = '';

    /**
     * @var array holds the TranslationProviderFactory's to use for loading a language
     */
    private array $translationResources = [];

    /**
     * @var array holds the currently available languages and their associated translation-providers<br>
     * in the format (lang/locale => provider)
     */
    private array $loadedLanguages = [];

    /**
     * @var array Stores a list of languages for use in translations.<br>
     * Intended to hold a subset of $loadedLanguages which will be used to look up translation-keys
     */
    private array $activeLanguages =  [];

    /**
     * @var string The intl locale for formatting
     */
    private string $locale;
    /**@deprecated
     * resources directory with the language files
     *TODO extract to Resource-file based Provider
     * @var string|null
     */
    private ?string $directory = null;

    /**@deprecated
     * holds the translations
     *TODO extract to Resource-file based Provider
     * @var array
     */
    protected array $translation = array();


    /**
     * @deprecated?
     * @var string[]
     */
    public static array $LOCALES = [
        'AD' => 'ca_AD',
        'AE' => 'ar_AE',
        'AF' => 'fa_AF',
        'AG' => 'en_AG',
        'AI' => 'en_AI',
        'AL' => 'sq_AL',
        'AM' => 'hy_AM',
        'AN' => 'pap_AN',
        'AO' => 'pt_AO',
        'AQ' => 'und_AQ',
        'AR' => 'es_AR',
        'AS' => 'sm_AS',
        'AT' => 'de_AT',
        'AU' => 'en_AU',
        'AW' => 'nl_AW',
        'AX' => 'sv_AX',
        'AZ' => 'az_Latn_AZ',
        'BA' => 'bs_BA',
        'BB' => 'en_BB',
        'BD' => 'bn_BD',
        'BE' => 'nl_BE',
        'BF' => 'mos_BF',
        'BG' => 'bg_BG',
        'BH' => 'ar_BH',
        'BI' => 'rn_BI',
        'BJ' => 'fr_BJ',
        'BL' => 'fr_BL',
        'BM' => 'en_BM',
        'BN' => 'ms_BN',
        'BO' => 'es_BO',
        'BR' => 'pt_BR',
        'BS' => 'en_BS',
        'BT' => 'dz_BT',
        'BV' => 'und_BV',
        'BW' => 'en_BW',
        'BY' => 'be_BY',
        'BZ' => 'en_BZ',
        'CA' => 'en_CA',
        'CC' => 'ms_CC',
        'CD' => 'sw_CD',
        'CF' => 'fr_CF',
        'CG' => 'fr_CG',
        'CH' => 'de_CH',
        'CI' => 'fr_CI',
        'CK' => 'en_CK',
        'CL' => 'es_CL',
        'CM' => 'fr_CM',
        'CN' => 'zh_Hans_CN',
        'CO' => 'es_CO',
        'CR' => 'es_CR',
        'CU' => 'es_CU',
        'CV' => 'kea_CV',
        'CX' => 'en_CX',
        'CY' => 'el_CY',
        'CZ' => 'cs_CZ',
        'DE' => 'de_DE',
        'DJ' => 'aa_DJ',
        'DK' => 'da_DK',
        'DM' => 'en_DM',
        'DO' => 'es_DO',
        'DZ' => 'ar_DZ',
        'EC' => 'es_EC',
        'EE' => 'et_EE',
        'EG' => 'ar_EG',
        'EH' => 'ar_EH',
        'ER' => 'ti_ER',
        'ES' => 'es_ES',
        'ET' => 'en_ET',
        'FI' => 'fi_FI',
        'FJ' => 'hi_FJ',
        'FK' => 'en_FK',
        'FM' => 'chk_FM',
        'FO' => 'fo_FO',
        'FR' => 'fr_FR',
        'GA' => 'fr_GA',
        'GB' => 'en_GB',
        'GD' => 'en_GD',
        'GE' => 'ka_GE',
        'GF' => 'fr_GF',
        'GG' => 'en_GG',
        'GH' => 'ak_GH',
        'GI' => 'en_GI',
        'GL' => 'iu_GL',
        'GM' => 'en_GM',
        'GN' => 'fr_GN',
        'GP' => 'fr_GP',
        'GQ' => 'fan_GQ',
        'GR' => 'el_GR',
        'GS' => 'und_GS',
        'GT' => 'es_GT',
        'GU' => 'en_GU',
        'GW' => 'pt_GW',
        'GY' => 'en_GY',
        'HK' => 'zh_Hant_HK',
        'HM' => 'und_HM',
        'HN' => 'es_HN',
        'HR' => 'hr_HR',
        'HT' => 'ht_HT',
        'HU' => 'hu_HU',
        'ID' => 'id_ID',
        'IE' => 'en_IE',
        'IL' => 'he_IL',
        'IM' => 'en_IM',
        'IN' => 'hi_IN',
        'IO' => 'und_IO',
        'IQ' => 'ar_IQ',
        'IR' => 'fa_IR',
        'IS' => 'is_IS',
        'IT' => 'it_IT',
        'JE' => 'en_JE',
        'JM' => 'en_JM',
        'JO' => 'ar_JO',
        'JP' => 'ja_JP',
        'KE' => 'en_KE',
        'KG' => 'ky_Cyrl_KG',
        'KH' => 'km_KH',
        'KI' => 'en_KI',
        'KM' => 'ar_KM',
        'KN' => 'en_KN',
        'KP' => 'ko_KP',
        'KR' => 'ko_KR',
        'KW' => 'ar_KW',
        'KY' => 'en_KY',
        'KZ' => 'ru_KZ',
        'LA' => 'lo_LA',
        'LB' => 'ar_LB',
        'LC' => 'en_LC',
        'LI' => 'de_LI',
        'LK' => 'si_LK',
        'LR' => 'en_LR',
        'LS' => 'st_LS',
        'LT' => 'lt_LT',
        'LU' => 'fr_LU',
        'LV' => 'lv_LV',
        'LY' => 'ar_LY',
        'MA' => 'ar_MA',
        'MC' => 'fr_MC',
        'MD' => 'ro_MD',
        'ME' => 'sr_Latn_ME',
        'MF' => 'fr_MF',
        'MG' => 'mg_MG',
        'MH' => 'mh_MH',
        'MK' => 'mk_MK',
        'ML' => 'bm_ML',
        'MM' => 'my_MM',
        'MN' => 'mn_Cyrl_MN',
        'MO' => 'zh_Hant_MO',
        'MP' => 'en_MP',
        'MQ' => 'fr_MQ',
        'MR' => 'ar_MR',
        'MS' => 'en_MS',
        'MT' => 'mt_MT',
        'MU' => 'mfe_MU',
        'MV' => 'dv_MV',
        'MW' => 'ny_MW',
        'MX' => 'es_MX',
        'MY' => 'ms_MY',
        'MZ' => 'pt_MZ',
        'NA' => 'kj_NA',
        'NC' => 'fr_NC',
        'NE' => 'ha_Latn_NE',
        'NF' => 'en_NF',
        'NG' => 'en_NG',
        'NI' => 'es_NI',
        'NL' => 'nl_NL',
        'NO' => 'nb_NO',
        'NP' => 'ne_NP',
        'NR' => 'en_NR',
        'NU' => 'niu_NU',
        'NZ' => 'en_NZ',
        'OM' => 'ar_OM',
        'PA' => 'es_PA',
        'PE' => 'es_PE',
        'PF' => 'fr_PF',
        'PG' => 'tpi_PG',
        'PH' => 'fil_PH',
        'PK' => 'ur_PK',
        'PL' => 'pl_PL',
        'PM' => 'fr_PM',
        'PN' => 'en_PN',
        'PR' => 'es_PR',
        'PS' => 'ar_PS',
        'PT' => 'pt_PT',
        'PW' => 'pau_PW',
        'PY' => 'gn_PY',
        'QA' => 'ar_QA',
        'RE' => 'fr_RE',
        'RO' => 'ro_RO',
        'RS' => 'sr_Cyrl_RS',
        'RU' => 'ru_RU',
        'RW' => 'rw_RW',
        'SA' => 'ar_SA',
        'SB' => 'en_SB',
        'SC' => 'crs_SC',
        'SD' => 'ar_SD',
        'SE' => 'sv_SE',
        'SG' => 'en_SG',
        'SH' => 'en_SH',
        'SI' => 'sl_SI',
        'SJ' => 'nb_SJ',
        'SK' => 'sk_SK',
        'SL' => 'kri_SL',
        'SM' => 'it_SM',
        'SN' => 'fr_SN',
        'SO' => 'sw_SO',
        'SR' => 'srn_SR',
        'ST' => 'pt_ST',
        'SV' => 'es_SV',
        'SY' => 'ar_SY',
        'SZ' => 'en_SZ',
        'TC' => 'en_TC',
        'TD' => 'fr_TD',
        'TF' => 'und_TF',
        'TG' => 'fr_TG',
        'TH' => 'th_TH',
        'TJ' => 'tg_Cyrl_TJ',
        'TK' => 'tkl_TK',
        'TL' => 'pt_TL',
        'TM' => 'tk_TM',
        'TN' => 'ar_TN',
        'TO' => 'to_TO',
        'TR' => 'tr_TR',
        'TT' => 'en_TT',
        'TV' => 'tvl_TV',
        'TW' => 'zh_Hant_TW',
        'TZ' => 'sw_TZ',
        'UA' => 'uk_UA',
        'UG' => 'sw_UG',
        'UM' => 'en_UM',
        'US' => 'en_US',
        'UY' => 'es_UY',
        'UZ' => 'uz_Cyrl_UZ',
        'VA' => 'it_VA',
        'VC' => 'en_VC',
        'VE' => 'es_VE',
        'VG' => 'en_VG',
        'VI' => 'en_VI',
        'VN' => 'vn_VN',
        'VU' => 'bi_VU',
        'WF' => 'wls_WF',
        'WS' => 'sm_WS',
        'YE' => 'ar_YE',
        'YT' => 'swb_YT',
        'ZA' => 'en_ZA',
        'ZM' => 'en_ZM',
        'ZW' => 'sn_ZW'
    ];

    /**
     * @var Translator|null
     */
    private static ?Translator $Instance = null;

    /**
     * It is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        parent::__construct();
    }

    /**
     * gets the instance via lazy initialization (created on first usage)
     */
    public static function getInstance(): Translator
    {
        Translator::$Instance ??= new Translator();
        return Translator::$Instance;
    }

    /**
     * sets the resources directory
     * TODO extract to Resource-file based Provider
     * @param string $directory
     * @return $this
     * @throws Exception
     */
    public function setResourceDir(string $directory)
    {
        if (!is_dir($directory)) {
            throw new Exception('Resource directory ' . $directory . ' not found.');
        }
        $this->translation = [];
        $this->directory = $directory;
        return $this;
    }

    /**TODO change to provider
     * @return bool
     */
    public function wasInitialized(): bool
    {
        return $this->directory != '';
    }

    /**TODO replace with Language-list
     * sets default language
     *
     * @param string $language
     * @return $this
     */
    public function setDefaultLanguage(string $language)
    {
        $this->defaultLanguage = $language;
        $this->language = $language;
        return $this;
    }

    /**TODO replace with Language-list
     * change language
     *
     * @param string $language
     * @return Translator
     */
    public function changeLanguage(string $language): Translator
    {
        $this->language = $language;
        return $this;
    }

    /**TODO replace with Language-list
     * change back to default language
     *
     * @return Translator
     */
    public function changeBackToDefaultLanguage(): Translator
    {
        $this->changeLanguage($this->defaultLanguage);
        return $this;
    }

    /**TODO replace with Language-list
     * get active language
     *
     * @return string
     */
    private function getLanguage(): string
    {
        return ($this->language ?: $this->defaultLanguage);
    }

    /**@param int $weekday 0-6 = Sunday - Saturday
     * @return string
     * @throws Exception
     * @deprecated
     * gets the weekday expression according to the weekday number (strftime('%w'))
     *
     */
    public function getWeekday(int $weekday): string
    {
        $translationKeys = [
              0 => 'global.Sunday',
              1 => 'global.Monday',
              2 => 'global.Tuesday',
              3 => 'global.Wednesday',
              4 => 'global.Thursday',
              5 => 'global.Friday',
              6 => 'global.Saturday'
        ];

        if (isset($translationKeys[$weekday])) {
            return $this->get($translationKeys[$weekday]);
        }
        return '';
    }

    /**@deprecated merge with get
     * get plural translation
     *
     * @param string $key
     * @param int $n
     * @param mixed|null ...$args
     * @return mixed|string
     * @throws Exception
     */
    public function nget(string $key, int $n, ...$args)
    {
        $language = $this->getLanguage();
        $translation = $this->getTranslation($language);

        if (!$args) {
            $args = [$n];
        }

        $index = $this->pluralRule($language, $n);

        $string = $translation[$key][$index] ?? '';
        if ($args) {
            $string = vsprintf($string, $args);
        }
        return $string;
    }

    /**
     * translation exists?
     *
     * @param string $key
     * @return bool
     * @throws Exception
     */
    public function exists(string $key)
    {
        return isset($this->getTranslation($this->getLanguage())[$key]);
    }

    /**@deprecated
     * @return array|null
     */
    public function getParseErrors(): ?array
    {
        return null;
    }


    /**
     * @param string $lang
     * @param bool $tryHard
     * @return TranslationProvider|Exception
     * @throws Exception
     */
    private function autoloadLanguage(string $lang, bool $tryHard = false): ?TranslationProvider
    {
        foreach ($this->translationResources as $factory){
            /**@var TranslationProviderFactory $factory*/
            if ($factory->hasLang($lang))
                return $factory->getProvider($lang);
        }
        if ($tryHard){
            foreach ($this->translationResources as $factory){
                /**@var TranslationProviderFactory $factory*/
                if ($bestLang = $factory->getBestLang($lang))
                    return $factory->getProvider($bestLang);
            }
        }
        throw new Exception("Language $lang could not be loaded");
    }

    /** Sets a new active language list from the $language parameter and
     * returns the active language list for later restore using this function again
     * @param array|string|null $language A language list with language names as keys and optionally TranslationProviders as value<br>
     * or the name of the Language to use
     * @return array|null
     * @throws Exception missing TranslationProvider
     */
    private function swapLangList(array|string|null $language): ?array
    {
        if ($language ===null)
            return null;
        $newActiveLanguages = (array)$language;
        foreach ($newActiveLanguages as $lang => &$provider){
                $provider ??= $this->loadedLanguages[$lang] ??
                    $this->autoloadLanguage($lang);
        }
        $oldActiveLanguages = $this->activeLanguages;
        $this->activeLanguages = $newActiveLanguages;
        return $oldActiveLanguages;
    }

    /**
     * @param string $sourceFile
     * @param string $lang
     * @return string
     * @throws Exception missing TranslationProvider, File*
     */
    public function translateFile(string $sourceFile, string $lang):string{
        $translatedDir = buildDirPath(dirname($sourceFile), $lang);
        if (!is_dir($translatedDir))
            mkdir($translatedDir);
        $filename = basename($sourceFile);
        $manualPreTranslatedFile = buildFilePath($translatedDir, 'man', $filename);
        //manual Translation exists
        if (file_exists($manualPreTranslatedFile))
            //override source
            $sourceFile = $manualPreTranslatedFile;
        $translatedFile = $translatedDir . $filename;
        $sourceContent = file_get_contents($sourceFile);
        $countChanges = 0;
        $translatedContent = $this->parse($sourceContent, $lang, $countChanges);
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
    public function parse(string $templateContent, string|array $language = null, int &$countChanges = 0): string
    {
        $defaultLangList = $this->swapLangList($language);
        //More specific copy of code from \TempCoreHandle::findPattern
        $changes = array();
        // Matches Blocks like <!-- TRANSL handle -->freeform-text<!-- END handle -->
        $reg = '/<!-- (TRANSL) ([^>]+) -->(.*?)<!-- END \1 -->/s';
        $this->translateWithRegEx($templateContent,$reg, $changes);
        //this perfectly readable regex Matches Blocks like {TRANSL key[(args)][??<default>]}
        //key and args are retrieved as handle in group 2
        //avoid constructs like )?? )} in args and >} in default
        $reg = '/\{(TRANSL) +([^\s(?]+(?>\((?>[^)]*(?>\)(?!\?\?|}))?)+\))?)(?>\?\?<((?>[^>]*(?>>(?!}))?)*)>)?}/su';
        $this->translateWithRegEx($templateContent,$reg, $changes);
        $translatedContent = strtr($templateContent, $changes);
        $countChanges = count($changes);
        unset($changes);
        //END of copy region
        $this->swapLangList($defaultLangList);
        return $translatedContent;
    }

    /** Performs the parsing of the content using a specific RegularExpression for finding and analyzing the translation tags<br>
     * uses TODO translate without language parameter so set activeLangauges accordingly
     * @param string $content the string to check for tokens
     * @param string $regEX A pattern that matches the tag to replace and captures Keyword, handle/key and the default value / tag content
     * @param array $changes An array to store the translations in (tag => translation) for use with strtr(). New tags will be inserted present tags won't be fetched again
     * @return bool false on RegEx-failure
     */
    private function translateWithRegEx(string $content, string $regEX, array &$changes): bool
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
        return $this->get($key, $args, $tagContent);
    }

    /**TODO
     * get translation
     *
     * @param string $key
     * @param mixed|null ...$args
     * @return string
     */
    public function get(string $key, ...$args):string
    {
        //TODO transform args
        if (!$args) {
            $args = [];
        }
        $translation = '';
        $formattedTranslation = \MessageFormatter::formatMessage('TODO', $translation, $args);
        return $formattedTranslation;
    }


    /**TODO
     * checks if translation for a specific language is available
     *
     * @param string $language language code/country code
     * @return bool
     */
    private function hasTranslation(string $language): bool
    {
        return isset($this->translation[$language]);
    }

    /**TODO extract to Resource-file based Provider
     * set translations for a language
     *
     * @param string $language language code/country code
     * @param array $trans
     */
    public function setTranslation(string $language, array $trans = array())
    {
        $this->translation[$language] = $trans;
    }

    /**TODO extract to Resource-file based Provider
     * get translations for a language
     *
     * @param string $language language code/country code
     * @return array
     * @throws Exception
     */
    public function getTranslation(string $language): array
    {
        if (!$this->hasTranslation($language)) {
            $translationFile = $this->directory . '/' . $language . $this->extension;

            // file cannot be loaded, error handling:
            if(!file_exists($translationFile)) {
                if(!$this->directory) throw new Exception('No directory was specified for the resources.');
                if(!$language) throw new Exception('No language was specified.');
                throw new \TranslatorMissingFileException('Translation file '.$translationFile.' couldn\'t be found.');
            }
            $this->setTranslation($language, include($translationFile));
        }
        return $this->translation[$language];
    }

    /**TODO extract to Resource-file based Provider
     * @param string $language
     * @return bool
     */
    public function resourceFile_exists(string $language): bool
    {
        return file_exists($this->directory . '/' . $language . $this->extension);
    }

    /**@deprecated use intl MessageFormatter
     * The plural rules are derived from code of the Zend Framework (2010-09-25),
     * which is subject to the new BSD license
     * (http://framework.zend.com/license/new-bsd).
     * Copyright (c) 2005-2010 Zend Technologies USA Inc.
     * (http://www.zend.com)
     * https://github.com/zendframework/zf1/blob/master/library/Zend/Translate/Plural.php
     *
     * @param string $language language code/country code
     * @param int $x plural variable
     *
     * @return integer index of plural form rule.
     * @see https://github.com/delfimov/Translate/blob/master/src/Translate.php
     */
    protected function pluralRule(string $language, int $x): int
    {
        switch ($language) {
            case 'af':
            case 'bn':
            case 'bg':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fa':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hu':
            case 'is':
            case 'it':
            case 'ku':
            case 'lb':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'nn':
            case 'no':
            case 'om':
            case 'or':
            case 'pa':
            case 'pap':
            case 'ps':
            case 'pt':
            case 'so':
            case 'sq':
            case 'sv':
            case 'sw':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'zu':
                $index = ($x == 1) ? 0 : 1;
                break;

            case 'am':
            case 'bh':
            case 'fil':
            case 'fr':
            case 'gun':
            case 'hi':
            case 'ln':
            case 'mg':
            case 'nso':
            case 'xbr':
            case 'ti':
            case 'wa':
                $index = (($x == 0) || ($x == 1)) ? 0 : 1;
                break;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                $index = (($x % 10 == 1) && ($x % 100 != 11)) ? (0) : ((($x % 10 >= 2) && ($x % 10 <= 4) && (($x % 100 < 10) || ($x % 100 >= 20))) ? 1 : 2);
                break;

            case 'cs':
            case 'sk':
                $index = ($x == 1) ? 0 : ((($x >= 2) && ($x <= 4)) ? 1 : 2);
                break;

            case 'ga':
                $index = ($x == 1) ? 0 : (($x == 2) ? 1 : 2);
                break;

            case 'lt':
                $index = (($x % 10 == 1) && ($x % 100 != 11)) ? (0) : ((($x % 10 >= 2) && (($x % 100 < 10) || ($x % 100 >= 20))) ? 1 : 2);
                break;

            case 'sl':
                $index = ($x % 100 == 1) ? (0) : (($x % 100 == 2) ? 1 : ((($x % 100 == 3) || ($x % 100 == 4)) ? 2 : 3));
                break;

            case 'mk':
                $index = ($x % 10 == 1) ? 0 : 1;
                break;

            case 'mt':
                $index = ($x == 1) ? (0) : ((($x == 0) || (($x % 100 > 1) && ($x % 100 < 11))) ? (1) : ((($x % 100 > 10) && ($x % 100 < 20)) ? 2 : 3));
                break;

            case 'lv':
                $index = ($x == 0) ? 0 : ((($x % 10 == 1) && ($x % 100 != 11)) ? 1 : 2);
                break;

            case 'pl':
                $index = ($x == 1) ? (0) : ((($x % 10 >= 2) && ($x % 10 <= 4) && (($x % 100 < 12) || ($x % 100 > 14))) ? 1 : 2);
                break;

            case 'cy':
                $index = ($x == 1) ? (0) : (($x == 2) ? 1 : ((($x == 8) || ($x == 11)) ? 2 : 3));
                break;

            case 'ro':
                $index = ($x == 1) ? (0) : ((($x == 0) || (($x % 100 > 0) && ($x % 100 < 20))) ? 1 : 2);
                break;

            case 'ar':
                $index = ($x == 0) ? (0) : (($x == 1) ? 1 : (($x == 2) ? 2 : ((($x >= 3) && ($x <= 10)) ? (3) : ((($x >= 11) && ($x <= 99)) ? 4 : 5))));
                break;

            default:
                $index = 0;
                break;
        }
        return $index;
    }

    /**TODO
     * detects locale from browser
     *
     * @param string $defaultLocale
     * @param bool $useGeoIP
     * @return string locale
     */
    public static function detectLocale(string $defaultLocale = 'en_US', bool $useGeoIP = true): string
    {
        $locale = false;

        // Try detecting locale from browser headers
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            if(function_exists('locale_accept_from_http')) {
                $locale = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            }
            else {
                $prefLocales = array_reduce(
                    explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']),
                    function ($res, $el) {
                        list($l, $q) = array_merge(explode(';q=', $el), [1]);
                        if (strpos($l, '-') !== false) $l = str_replace('-', '_', trim($l));
                        $res[$l] = (float)$q;
                        return $res;
                    }, []);
                arsort($prefLocales);
                $locale = key($prefLocales);
            }
        }

        // GeoIP
        if (!$locale and $useGeoIP and function_exists('geoip_country_code_by_name') and
                ($clientIP = getClientIP())) {
            // for testing: $clientIP = '91.40.45.237';
            $countryCode = geoip_country_code_by_name($clientIP);
            $locale = self::countryCodeToLocale($countryCode) ?: false;
        }



        // Resort to default locale specified in config file
        if (!$locale) {
            $locale = $defaultLocale;
        }
        return $locale;
    }

    /**
     * get the primary language of the locale
     *
     * @param string $locale
     * @return string language code
     */
    public static function getPrimaryLanguage(string $locale): string
    {
        if(function_exists('locale_get_primary_language')) {
            $language = locale_get_primary_language($locale);
        }
        else {
            $language = self::localeToLanguageCode($locale);
        }
        return $language;
    }

    /**
     * Get locale from country code
     *
     * @param string $countryCode
     * @return string locale
     */
    public static function countryCodeToLocale(string $countryCode): string
    {
        return self::$LOCALES[strtoupper($countryCode)] ?? '';
    }

    /**
     * Get language code from locale
     *
     * @param string $locale
     * @return string language code
     */
    public static function localeToLanguageCode(string $locale): string
    {
        list($languageCode,) = explode('_', $locale);
        return $languageCode;
    }

}