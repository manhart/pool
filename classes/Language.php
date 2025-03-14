<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace pool\classes;

/**
 * Class Language
 * I have not found a source that includes countries by number of native speakers.
 * So that you can choose the best country based on the number of native speakers.
 * The list comes from chatGPT. Ask 'Create me a PHP array of the best matching locales for each language abbreviation in ISO-639-1 sorted by the keys
 * (ISO-639-1 language codes), pre-filtered by the ISO-639-1 codes available in the PHP intl extension. And write the ISO-639-1 codes with underscore _
 * instead of hyphen -.'
 *
 * @package pool\classes
 * @since 2022-12-30
 */
final class Language
{
    private static array $bestMatchingLocale = [
        'af' => 'af_ZA',
        'am' => 'am_ET',
        'ar' => 'ar_SA',
        'az' => 'az_AZ',
        'be' => 'be_BY',
        'bg' => 'bg_BG',
        'bn' => 'bn_BD',
        'bo' => 'bo_CN',
        'br' => 'br_FR',
        'bs' => 'bs_BA',
        'ca' => 'ca_ES',
        'ce' => 'ce_RU',
        'ceb' => 'ceb_PH',
        'ch' => 'ch_GU',
        'chr' => 'chr_US',
        'co' => 'co_FR',
        'cs' => 'cs_CZ',
        'cy' => 'cy_GB',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'dz' => 'dz_BT',
        'el' => 'el_GR',
        'en' => 'en_US',
        'eo' => 'eo_EO',
        'es' => 'es_ES',
        'et' => 'et_EE',
        'eu' => 'eu_ES',
        'fa' => 'fa_IR',
        'ff' => 'ff_SN',
        'fi' => 'fi_FI',
        'fil' => 'fil_PH',
        'fj' => 'fj_FJ',
        'fo' => 'fo_FO',
        'fr' => 'fr_FR',
        'fy' => 'fy_NL',
        'ga' => 'ga_IE',
        'gd' => 'gd_GB',
        'gl' => 'gl_ES',
        'gn' => 'gn_PY',
        'gu' => 'gu_IN',
        'gv' => 'gv_GB',
        'ha' => 'ha_NG',
        'haw' => 'haw_US',
        'he' => 'he_IL',
        'hi' => 'hi_IN',
        'ho' => 'ho_PG',
        'hr' => 'hr_HR',
        'ht' => 'ht_HT',
        'hu' => 'hu_HU',
        'hy' => 'hy_AM',
        'hz' => 'hz_NA',
        'ia' => 'ia_FR',
        'id' => 'id_ID',
        'ie' => 'ie_FR',
        'ig' => 'ig_NG',
        'ii' => 'ii_CN',
        'ik' => 'ik_US',
        'io' => 'io_FR',
        'is' => 'is_IS',
        'it' => 'it_IT',
        'iu' => 'iu_CA',
        'ja' => 'ja_JP',
        'jv' => 'jv_ID',
        'ka' => 'ka_GE',
        'kg' => 'kg_CD',
        'ki' => 'ki_KE',
        'kj' => 'kj_NA',
        'kk' => 'kk_KZ',
        'kl' => 'kl_GL',
        'km' => 'km_KH',
        'kn' => 'kn_IN',
        'ko' => 'ko_KR',
        'kr' => 'kr_NG',
        'ks' => 'ks_IN',
        'ku' => 'ku_TR',
        'kv' => 'kv_RU',
        'kw' => 'kw_GB',
        'ky' => 'ky_KG',
        'la' => 'la_LA',
        'lb' => 'lb_LU',
        'lg' => 'lg_UG',
        'li' => 'li_BE',
        'ln' => 'ln_CD',
        'lo' => 'lo_LA',
        'lt' => 'lt_LT',
        'lu' => 'lu_CD',
        'lv' => 'lv_LV',
        'mg' => 'mg_MG',
        'mh' => 'mh_MH',
        'mi' => 'mi_NZ',
        'mk' => 'mk_MK',
        'ml' => 'ml_IN',
        'mn' => 'mn_MN',
        'mo' => 'mo_MD',
        'mr' => 'mr_IN',
        'ms' => 'ms_MY',
        'mt' => 'mt_MT',
        'my' => 'my_MM',
        'na' => 'na_NR',
        'nb' => 'nb_NO',
        'nd' => 'nd_ZW',
        'ne' => 'ne_NP',
        'ng' => 'ng_NA',
        'nl' => 'nl_NL',
        'nn' => 'nn_NO',
        'no' => 'no_NO',
        'nr' => 'nr_ZA',
        'nv' => 'nv_US',
        'ny' => 'ny_MW',
        'oc' => 'oc_FR',
        'oj' => 'oj_CA',
        'om' => 'om_ET',
        'or' => 'or_IN',
        'os' => 'os_GE',
        'pa' => 'pa_IN',
        'pi' => 'pi_IN',
        'pl' => 'pl_PL',
        'ps' => 'ps_AF',
        'pt' => 'pt_PT',
        'qu' => 'qu_PE',
        'rm' => 'rm_CH',
        'rn' => 'rn_BI',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'rw' => 'rw_RW',
        'sa' => 'sa_IN',
        'sc' => 'sc_IT',
        'sd' => 'sd_IN',
        'se' => 'se_NO',
        'sg' => 'sg_CF',
        'si' => 'si_LK',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sm' => 'sm_WS',
        'sn' => 'sn_ZW',
        'so' => 'so_SO',
        'sq' => 'sq_AL',
        'sr' => 'sr_RS',
        'ss' => 'ss_ZA',
        'st' => 'st_ZA',
        'su' => 'su_ID',
        'sv' => 'sv_SE',
        'sw' => 'sw_TZ',
        'ta' => 'ta_IN',
        'te' => 'te_IN',
        'tg' => 'tg_TJ',
        'th' => 'th_TH',
        'ti' => 'ti_ET',
        'tk' => 'tk_TM',
        'tl' => 'tl_PH',
        'tn' => 'tn_ZA',
        'to' => 'to_TO',
        'tr' => 'tr_TR',
        'ts' => 'ts_ZA',
        'tt' => 'tt_RU',
        'tw' => 'tw_GH',
        'ty' => 'ty_PF',
        'ug' => 'ug_CN',
        'uk' => 'uk_UA',
        'ur' => 'ur_PK',
        'uz' => 'uz_UZ',
        've' => 've_ZA',
        'vi' => 'vi_VN',
        'vo' => 'vo_001',
        'wa' => 'wa_BE',
        'wo' => 'wo_SN',
        'xh' => 'xh_ZA',
        'yi' => 'yi_001',
        'yo' => 'yo_NG',
        'za' => 'za_CN',
        'zh' => 'zh_CN',
        'zu' => 'zu_ZA',
    ];

    /**
     * Provides the best matching locale.
     *
     * @param string $languageCode
     * @param string $defaultLocale If nothing was found, the default locale is returned.
     * @return string
     */
    public static function getBestLocale(string $languageCode, string $defaultLocale = ''): string
    {
        if (isset(self::$bestMatchingLocale[$languageCode])) {
            return self::$bestMatchingLocale[$languageCode];
        }
        return $defaultLocale;
    }
}