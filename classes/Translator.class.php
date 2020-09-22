<?php
/*
 * g7system.local
 *
 * Translator.class.php created at 14.09.20, 14:01
 *
 * @author a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2020, GROUP7 AG
 */

namespace pool\classes;


class Translator extends \PoolObject
{
    /**
     * @var string
     */
    private string $extension = '.php';

    /**
     * language
     *
     * @var string
     */
    private string $language = '';

    /**
     * default language
     *
     * @var string
     */
    private string $defaultLanguage = '';

    /**
     * resources directory with the language files
     *
     * @var string|null
     */
    private ?string $directory = null;

    /**
     * holds the translations
     *
     * @var array
     */
    protected array $translation = array();

    /**
     * @var array
     */
    private array $parseErrors = array();

    /**
     * sets the resources directory
     *
     * @param string $directory
     * @return $this
     * @throws \Exception
     */
    public function setResourceDir(string $directory)
    {
        if (!is_dir($directory)) {
            throw new \Exception('Resource directory ' . $directory . ' not found.');
        }
        $this->translation = array();
        $this->directory = $directory;
        return $this;
    }

    /**
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

    /**
     * change language
     *
     * @param string $language
     * @return $this
     */
    public function changeLanguage(string $language)
    {
        $this->language = $language;
        return $this;
    }

    /**
     * get active language
     *
     * @return string
     */
    private function getLanguage(): string
    {
        $language = $this->defaultLanguage;
        if ($this->defaultLanguage != $this->language) {
            $language = $this->language ?: $this->defaultLanguage;
        }
        return $language;
    }

    /**
     * get translation
     *
     * @param string $key
     * @param array|null ...$args
     * @return mixed|string
     * @throws \Exception
     */
    public function get(string $key, ?array ...$args)
    {
        $translation = $this->getTranslation($this->getLanguage());

        if (!$args) {
            $args = [];
        }

        $string = $translation[$key] ?? '';
        if ($args) {
            $string = vsprintf($string, $args);
        }
        return $string;
    }

    /**
     * get plural translation
     *
     * @param string $key
     * @param int $n
     * @param array|null ...$args
     * @return mixed|string
     * @throws \Exception
     */
    public function nget(string $key, int $n, ?array ...$args)
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
     * @throws \Exception
     */
    public function exists(string $key)
    {
        return isset($this->getTranslation($this->getLanguage())[$key]);
    }


    /**
     * @return array|null
     */
    public function getParseErrors(): ?array
    {
        if(count($this->parseErrors) > 0) {
            return $this->parseErrors;
        }
        return null;
    }

    /**
     * @param string $content
     * @return string
     */
    public function parse(string $content): string
    {
        $this->parseErrors = array();
        $symbols = 'LANG|TRANSL';
        $reg = '/\<\!\-\- *(?>LANG|TRANSL) +(.+) *\-\-\>([\s\S]*)\<\!\-\- *END +\1 *\-\-\>/Uu';

        preg_match_all($reg, $content, $matches, PREG_SET_ORDER);

        if (($lastErrorCode = preg_last_error()) != PREG_NO_ERROR) {
            $errormessage = preg_last_error_message($lastErrorCode);
            throw new \Exception($errormessage, $lastErrorCode);
        }

        foreach ($matches as $match) {
            $key = $match[1];
            $translation = $this->get($key);

            if ($translation == '') {
                $this->parseErrors[] = $key;
                $translation = $match[2]; // hold original
            }

            $reg = '/\<\!\-\- *(?>LANG|TRANSL) +'.$key.' *\-\-\>([\s\S]*)\<\!\-\- *END +'.$key.' *\-\-\>/Uu';
            $content = preg_replace($reg, $translation, $content, 1);
            if (($lastErrorCode = preg_last_error()) != PREG_NO_ERROR) {
                $errormessage = preg_last_error_message($lastErrorCode);
                throw new \Exception($errormessage, $lastErrorCode);
            }
        }

        $reg = '/(?>\{(?>LANG|TRANSL) +)(.*)\}/sUu';
        preg_match_all($reg, $content, $matches, PREG_SET_ORDER);
        if (($lastErrorCode = preg_last_error()) != PREG_NO_ERROR) {
            $errormessage = preg_last_error_message($lastErrorCode);
            throw new \Exception($errormessage, $lastErrorCode);
        }

        foreach($matches as $match) {
            $key = $match[1];

            $translation = $this->get($key);
            if ($translation == '') {
                $this->parseErrors[] = $key;
                $translation = $match[0]; // hold original
            }

            $reg = '/(?>\{(?>LANG|TRANSL) +)'.$key.'\}/sUu';
            $content = preg_replace($reg, $translation, $content, 1);
            if (($lastErrorCode = preg_last_error()) != PREG_NO_ERROR) {
                $errormessage = preg_last_error_message($lastErrorCode);
                throw new \Exception($errormessage, $lastErrorCode);
            }

        }

        return $content;
    }

    /**
     * checks if translation for a specific language is available
     *
     * @param string $language language code/country code
     * @return bool
     */
    private function hasTranslation(string $language): bool
    {
        return isset($this->translation[$language]);
    }

    /**
     * set translations for a language
     *
     * @param string $language language code/country code
     * @param array $trans
     */
    public function setTranslation(string $language, array $trans = array())
    {
        $this->translation[$language] = $trans;
    }

    /**
     * get translations for a language
     *
     * @param string $language language code/country code
     * @return array
     * @throws \Exception
     */
    public function getTranslation(string $language): array
    {
        if (!$this->hasTranslation($language)) {
            $translationFile = $this->directory . '/' . $language . $this->extension;

            // file cannot be loaded, error handling:
            if(file_exists($translationFile) == false) {
                if(!$this->directory) throw new \Exception('No directory was specified for the resources.');
                if(!$language) throw new \Exception('No language was specified.');
                throw new \Exception('Translation file '.$translationFile.' couldn\'t be found.');
            }
            $this->setTranslation($language, include($translationFile));
        }
        return $this->translation[$language];
    }

    /**
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

    /**
     * @param string $defaultLocale
     * @return false|mixed|string|void
     */
    public static function detectLanguage(string $defaultLocale)
    {
        $locale = false;

        // GeoIP
        if (function_exists('geoip_country_code_by_name') and ($clientIP = getClientIP())) {
            $country = geoip_country_code_by_name($clientIP);
            if ($country) {
                $locale = self::countryCodeToLocale($country) ?: false;
            }
        }

        // Try detecting locale from browser headers
        if (!$locale and isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

            foreach ($languages as $lang) {
                $lang = str_replace('-', '_', trim($lang));

                if (strpos($lang, '_') === false) {
                    if (isset($countryToLocale[strtoupper($lang)])) {
                        $locale = $countryToLocale[strtoupper($lang)];
                    }
                }
                else {
                    $lang = explode('_', $lang);

                    if (count($lang) == 3) {
                        // language_Encoding_COUNTRY
                        $locale = strtolower($lang[0]) . ucfirst($lang[1]) . strtoupper($lang[2]);
                    }
                    else {
                        // language_COUNTRY
                        $locale = strtolower($lang[0]) . strtoupper($lang[1]);
                    }
                }
            }
        }

        // Resort to default locale specified in config file
        if (!$locale) {
            $locale = $defaultLocale;
        }
        return $locale;
    }

    /**
     * Get locale from country code
     *
     * @param string $countryCode
     * @return string
     */
    public static function countryCodeToLocale(string $countryCode): string
    {
        $countryToLocale = [
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

        return $countryToLocale[strtoupper($countryCode)] ?? '';
    }
}