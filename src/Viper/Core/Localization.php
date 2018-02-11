<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 11.02.2018
 * Time: 15:30
 */

namespace Viper\Core;

use Viper\Core\Config;
use Viper\Core\Routing\App;
use Viper\Support\Libs\Util;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RecursiveRegexIterator;
use Viper\Support\ValidationException;


class Localization
{
    // From LaravelLocalization
    private const LOCALES = [
        'ace'         => ['name' => 'Achinese',                'native' => 'Aceh', 'regional' => ''],
        'af'          => ['name' => 'Afrikaans',               'native' => 'Afrikaans', 'regional' => 'af_ZA'],
        'agq'         => ['name' => 'Aghem',                   'native' => 'Aghem', 'regional' => ''],
        'ak'          => ['name' => 'Akan',                    'native' => 'Akan', 'regional' => 'ak_GH'],
        'an'          => ['name' => 'Aragonese',               'native' => 'aragonés', 'regional' => 'an_ES'],
        'cch'         => ['name' => 'Atsam',                   'native' => 'Atsam', 'regional' => ''],
        'gn'          => ['name' => 'Guaraní',                 'native' => 'Avañe’ẽ', 'regional' => ''],
        'ae'          => ['name' => 'Avestan',                 'native' => 'avesta', 'regional' => ''],
        'ay'          => ['name' => 'Aymara',                  'native' => 'aymar aru', 'regional' => 'ay_PE'],
        'az'          => ['name' => 'Azerbaijani (Latin)',     'native' => 'azərbaycanca', 'regional' => 'az_AZ'],
        'id'          => ['name' => 'Indonesian',              'native' => 'Bahasa Indonesia', 'regional' => 'id_ID'],
        'ms'          => ['name' => 'Malay',                   'native' => 'Bahasa Melayu', 'regional' => 'ms_MY'],
        'bm'          => ['name' => 'Bambara',                 'native' => 'bamanakan', 'regional' => ''],
        'jv'          => ['name' => 'Javanese (Latin)',        'native' => 'Basa Jawa', 'regional' => ''],
        'su'          => ['name' => 'Sundanese',               'native' => 'Basa Sunda', 'regional' => ''],
        'bh'          => ['name' => 'Bihari',                  'native' => 'Bihari', 'regional' => ''],
        'bi'          => ['name' => 'Bislama',                 'native' => 'Bislama', 'regional' => ''],
        'nb'          => ['name' => 'Norwegian Bokmål',        'native' => 'Bokmål', 'regional' => 'nb_NO'],
        'bs'          => ['name' => 'Bosnian',                 'native' => 'bosanski', 'regional' => 'bs_BA'],
        'br'          => ['name' => 'Breton',                  'native' => 'brezhoneg', 'regional' => 'br_FR'],
        'ca'          => ['name' => 'Catalan',                 'native' => 'català', 'regional' => 'ca_ES'],
        'ch'          => ['name' => 'Chamorro',                'native' => 'Chamoru', 'regional' => ''],
        'ny'          => ['name' => 'Chewa',                   'native' => 'chiCheŵa', 'regional' => ''],
        'kde'         => ['name' => 'Makonde',                 'native' => 'Chimakonde', 'regional' => ''],
        'sn'          => ['name' => 'Shona',                   'native' => 'chiShona', 'regional' => ''],
        'co'          => ['name' => 'Corsican',                'native' => 'corsu', 'regional' => ''],
        'cy'          => ['name' => 'Welsh',                   'native' => 'Cymraeg', 'regional' => 'cy_GB'],
        'da'          => ['name' => 'Danish',                  'native' => 'dansk', 'regional' => 'da_DK'],
        'se'          => ['name' => 'Northern Sami',           'native' => 'davvisámegiella', 'regional' => 'se_NO'],
        'de'          => ['name' => 'German',                  'native' => 'Deutsch', 'regional' => 'de_DE'],
        'luo'         => ['name' => 'Luo',                     'native' => 'Dholuo', 'regional' => ''],
        'nv'          => ['name' => 'Navajo',                  'native' => 'Diné bizaad', 'regional' => ''],
        'dua'         => ['name' => 'Duala',                   'native' => 'duálá', 'regional' => ''],
        'et'          => ['name' => 'Estonian',                'native' => 'eesti', 'regional' => 'et_EE'],
        'na'          => ['name' => 'Nauru',                   'native' => 'Ekakairũ Naoero', 'regional' => ''],
        'guz'         => ['name' => 'Ekegusii',                'native' => 'Ekegusii', 'regional' => ''],
        'en'          => ['name' => 'English',                 'native' => 'English', 'regional' => 'en_GB'],
        'en-AU'       => ['name' => 'Australian English',      'native' => 'Australian English', 'regional' => 'en_AU'],
        'en-GB'       => ['name' => 'British English',         'native' => 'British English', 'regional' => 'en_GB'],
        'en-US'       => ['name' => 'U.S. English',            'native' => 'U.S. English', 'regional' => 'en_US'],
        'es'          => ['name' => 'Spanish',                 'native' => 'español', 'regional' => 'es_ES'],
        'eo'          => ['name' => 'Esperanto',               'native' => 'esperanto', 'regional' => ''],
        'eu'          => ['name' => 'Basque',                  'native' => 'euskara', 'regional' => 'eu_ES'],
        'ewo'         => ['name' => 'Ewondo',                  'native' => 'ewondo', 'regional' => ''],
        'ee'          => ['name' => 'Ewe',                     'native' => 'eʋegbe', 'regional' => ''],
        'fil'         => ['name' => 'Filipino',                'native' => 'Filipino', 'regional' => 'fil_PH'],
        'fr'          => ['name' => 'French',                  'native' => 'français', 'regional' => 'fr_FR'],
        'fr-CA'       => ['name' => 'Canadian French',         'native' => 'français canadien', 'regional' => 'fr_CA'],
        'fy'          => ['name' => 'Western Frisian',         'native' => 'frysk', 'regional' => 'fy_DE'],
        'fur'         => ['name' => 'Friulian',                'native' => 'furlan', 'regional' => 'fur_IT'],
        'fo'          => ['name' => 'Faroese',                 'native' => 'føroyskt', 'regional' => 'fo_FO'],
        'gaa'         => ['name' => 'Ga',                      'native' => 'Ga', 'regional' => ''],
        'ga'          => ['name' => 'Irish',                   'native' => 'Gaeilge', 'regional' => 'ga_IE'],
        'gv'          => ['name' => 'Manx',                    'native' => 'Gaelg', 'regional' => 'gv_GB'],
        'sm'          => ['name' => 'Samoan',                  'native' => 'Gagana fa’a Sāmoa', 'regional' => ''],
        'gl'          => ['name' => 'Galician',                'native' => 'galego', 'regional' => 'gl_ES'],
        'ki'          => ['name' => 'Kikuyu',                  'native' => 'Gikuyu', 'regional' => ''],
        'gd'          => ['name' => 'Scottish Gaelic',         'native' => 'Gàidhlig', 'regional' => 'gd_GB'],
        'ha'          => ['name' => 'Hausa',                   'native' => 'Hausa', 'regional' => 'ha_NG'],
        'bez'         => ['name' => 'Bena',                    'native' => 'Hibena', 'regional' => ''],
        'ho'          => ['name' => 'Hiri Motu',               'native' => 'Hiri Motu', 'regional' => ''],
        'hr'          => ['name' => 'Croatian',                'native' => 'hrvatski', 'regional' => 'hr_HR'],
        'bem'         => ['name' => 'Bemba',                   'native' => 'Ichibemba', 'regional' => 'bem_ZM'],
        'io'          => ['name' => 'Ido',                     'native' => 'Ido', 'regional' => ''],
        'ig'          => ['name' => 'Igbo',                    'native' => 'Igbo', 'regional' => 'ig_NG'],
        'rn'          => ['name' => 'Rundi',                   'native' => 'Ikirundi', 'regional' => ''],
        'ia'          => ['name' => 'Interlingua',             'native' => 'interlingua', 'regional' => 'ia_FR'],
        'iu-Latn'     => ['name' => 'Inuktitut (Latin)',       'native' => 'Inuktitut', 'regional' => 'iu_CA'],
        'sbp'         => ['name' => 'Sileibi',                 'native' => 'Ishisangu', 'regional' => ''],
        'nd'          => ['name' => 'North Ndebele',           'native' => 'isiNdebele', 'regional' => ''],
        'nr'          => ['name' => 'South Ndebele',           'native' => 'isiNdebele', 'regional' => 'nr_ZA'],
        'xh'          => ['name' => 'Xhosa',                   'native' => 'isiXhosa', 'regional' => 'xh_ZA'],
        'zu'          => ['name' => 'Zulu',                    'native' => 'isiZulu', 'regional' => 'zu_ZA'],
        'it'          => ['name' => 'Italian',                 'native' => 'italiano', 'regional' => 'it_IT'],
        'ik'          => ['name' => 'Inupiaq',                 'native' => 'Iñupiaq', 'regional' => 'ik_CA'],
        'dyo'         => ['name' => 'Jola-Fonyi',              'native' => 'joola', 'regional' => ''],
        'kea'         => ['name' => 'Kabuverdianu',            'native' => 'kabuverdianu', 'regional' => ''],
        'kaj'         => ['name' => 'Jju',                     'native' => 'Kaje', 'regional' => ''],
        'mh'          => ['name' => 'Marshallese',             'native' => 'Kajin M̧ajeļ', 'regional' => 'mh_MH'],
        'kl'          => ['name' => 'Kalaallisut',             'native' => 'kalaallisut', 'regional' => 'kl_GL'],
        'kln'         => ['name' => 'Kalenjin',                'native' => 'Kalenjin', 'regional' => ''],
        'kr'          => ['name' => 'Kanuri',                  'native' => 'Kanuri', 'regional' => ''],
        'kcg'         => ['name' => 'Tyap',                    'native' => 'Katab', 'regional' => ''],
        'kw'          => ['name' => 'Cornish',                 'native' => 'kernewek', 'regional' => 'kw_GB'],
        'naq'         => ['name' => 'Nama',                    'native' => 'Khoekhoegowab', 'regional' => ''],
        'rof'         => ['name' => 'Rombo',                   'native' => 'Kihorombo', 'regional' => ''],
        'kam'         => ['name' => 'Kamba',                   'native' => 'Kikamba', 'regional' => ''],
        'kg'          => ['name' => 'Kongo',                   'native' => 'Kikongo', 'regional' => ''],
        'jmc'         => ['name' => 'Machame',                 'native' => 'Kimachame', 'regional' => ''],
        'rw'          => ['name' => 'Kinyarwanda',             'native' => 'Kinyarwanda', 'regional' => 'rw_RW'],
        'asa'         => ['name' => 'Kipare',                  'native' => 'Kipare', 'regional' => ''],
        'rwk'         => ['name' => 'Rwa',                     'native' => 'Kiruwa', 'regional' => ''],
        'saq'         => ['name' => 'Samburu',                 'native' => 'Kisampur', 'regional' => ''],
        'ksb'         => ['name' => 'Shambala',                'native' => 'Kishambaa', 'regional' => ''],
        'swc'         => ['name' => 'Congo Swahili',           'native' => 'Kiswahili ya Kongo', 'regional' => ''],
        'sw'          => ['name' => 'Swahili',                 'native' => 'Kiswahili', 'regional' => 'sw_KE'],
        'dav'         => ['name' => 'Dawida',                  'native' => 'Kitaita', 'regional' => ''],
        'teo'         => ['name' => 'Teso',                    'native' => 'Kiteso', 'regional' => ''],
        'khq'         => ['name' => 'Koyra Chiini',            'native' => 'Koyra ciini', 'regional' => ''],
        'ses'         => ['name' => 'Songhay',                 'native' => 'Koyraboro senni', 'regional' => ''],
        'mfe'         => ['name' => 'Morisyen',                'native' => 'kreol morisien', 'regional' => ''],
        'ht'          => ['name' => 'Haitian',                 'native' => 'Kreyòl ayisyen', 'regional' => 'ht_HT'],
        'kj'          => ['name' => 'Kuanyama',                'native' => 'Kwanyama', 'regional' => ''],
        'ksh'         => ['name' => 'Kölsch',                  'native' => 'Kölsch', 'regional' => ''],
        'ebu'         => ['name' => 'Kiembu',                  'native' => 'Kĩembu', 'regional' => ''],
        'mer'         => ['name' => 'Kimîîru',                 'native' => 'Kĩmĩrũ', 'regional' => ''],
        'lag'         => ['name' => 'Langi',                   'native' => 'Kɨlaangi', 'regional' => ''],
        'lah'         => ['name' => 'Lahnda',                  'native' => 'Lahnda', 'regional' => ''],
        'la'          => ['name' => 'Latin',                   'native' => 'latine', 'regional' => ''],
        'lv'          => ['name' => 'Latvian',                 'native' => 'latviešu', 'regional' => 'lv_LV'],
        'to'          => ['name' => 'Tongan',                  'native' => 'lea fakatonga', 'regional' => ''],
        'lt'          => ['name' => 'Lithuanian',              'native' => 'lietuvių', 'regional' => 'lt_LT'],
        'li'          => ['name' => 'Limburgish',              'native' => 'Limburgs', 'regional' => 'li_BE'],
        'ln'          => ['name' => 'Lingala',                 'native' => 'lingála', 'regional' => ''],
        'lg'          => ['name' => 'Ganda',                   'native' => 'Luganda', 'regional' => 'lg_UG'],
        'luy'         => ['name' => 'Oluluyia',                'native' => 'Luluhia', 'regional' => ''],
        'lb'          => ['name' => 'Luxembourgish',           'native' => 'Lëtzebuergesch', 'regional' => 'lb_LU'],
        'hu'          => ['name' => 'Hungarian',               'native' => 'magyar', 'regional' => 'hu_HU'],
        'mgh'         => ['name' => 'Makhuwa-Meetto',          'native' => 'Makua', 'regional' => ''],
        'mg'          => ['name' => 'Malagasy',                'native' => 'Malagasy', 'regional' => 'mg_MG'],
        'mt'          => ['name' => 'Maltese',                 'native' => 'Malti', 'regional' => 'mt_MT'],
        'mtr'         => ['name' => 'Mewari',                  'native' => 'Mewari', 'regional' => ''],
        'mua'         => ['name' => 'Mundang',                 'native' => 'Mundang', 'regional' => ''],
        'mi'          => ['name' => 'Māori',                   'native' => 'Māori', 'regional' => 'mi_NZ'],
        'nl'          => ['name' => 'Dutch',                   'native' => 'Nederlands', 'regional' => 'nl_NL'],
        'nmg'         => ['name' => 'Kwasio',                  'native' => 'ngumba', 'regional' => ''],
        'yav'         => ['name' => 'Yangben',                 'native' => 'nuasue', 'regional' => ''],
        'nn'          => ['name' => 'Norwegian Nynorsk',       'native' => 'nynorsk', 'regional' => 'nn_NO'],
        'oc'          => ['name' => 'Occitan',                 'native' => 'occitan', 'regional' => 'oc_FR'],
        'ang'         => ['name' => 'Old English',             'native' => 'Old English', 'regional' => ''],
        'xog'         => ['name' => 'Soga',                    'native' => 'Olusoga', 'regional' => ''],
        'om'          => ['name' => 'Oromo',                   'native' => 'Oromoo', 'regional' => 'om_ET'],
        'ng'          => ['name' => 'Ndonga',                  'native' => 'OshiNdonga', 'regional' => ''],
        'hz'          => ['name' => 'Herero',                  'native' => 'Otjiherero', 'regional' => ''],
        'uz-Latn'     => ['name' => 'Uzbek (Latin)',           'native' => 'oʼzbekcha', 'regional' => 'uz_UZ'],
        'nds'         => ['name' => 'Low German',              'native' => 'Plattdüütsch', 'regional' => 'nds_DE'],
        'pl'          => ['name' => 'Polish',                  'native' => 'polski', 'regional' => 'pl_PL'],
        'pt'          => ['name' => 'Portuguese',              'native' => 'português', 'regional' => 'pt_PT'],
        'pt-BR'       => ['name' => 'Brazilian Portuguese',    'native' => 'português do Brasil', 'regional' => 'pt_BR'],
        'ff'          => ['name' => 'Fulah',                   'native' => 'Pulaar', 'regional' => 'ff_SN'],
        'pi'          => ['name' => 'Pahari-Potwari',          'native' => 'Pāli', 'regional' => ''],
        'aa'          => ['name' => 'Afar',                    'native' => 'Qafar', 'regional' => 'aa_ER'],
        'ty'          => ['name' => 'Tahitian',                'native' => 'Reo Māohi', 'regional' => ''],
        'ksf'         => ['name' => 'Bafia',                   'native' => 'rikpa', 'regional' => ''],
        'ro'          => ['name' => 'Romanian',                'native' => 'română', 'regional' => 'ro_RO'],
        'cgg'         => ['name' => 'Chiga',                   'native' => 'Rukiga', 'regional' => ''],
        'rm'          => ['name' => 'Romansh',                 'native' => 'rumantsch', 'regional' => ''],
        'qu'          => ['name' => 'Quechua',                 'native' => 'Runa Simi', 'regional' => ''],
        'nyn'         => ['name' => 'Nyankole',                'native' => 'Runyankore', 'regional' => ''],
        'ssy'         => ['name' => 'Saho',                    'native' => 'Saho', 'regional' => ''],
        'sc'          => ['name' => 'Sardinian',               'native' => 'sardu', 'regional' => 'sc_IT'],
        'de-CH'       => ['name' => 'Swiss High German',       'native' => 'Schweizer Hochdeutsch', 'regional' => 'de_CH'],
        'gsw'         => ['name' => 'Swiss German',            'native' => 'Schwiizertüütsch', 'regional' => ''],
        'trv'         => ['name' => 'Taroko',                  'native' => 'Seediq', 'regional' => ''],
        'seh'         => ['name' => 'Sena',                    'native' => 'sena', 'regional' => ''],
        'nso'         => ['name' => 'Northern Sotho',          'native' => 'Sesotho sa Leboa', 'regional' => 'nso_ZA'],
        'st'          => ['name' => 'Southern Sotho',          'native' => 'Sesotho', 'regional' => 'st_ZA'],
        'tn'          => ['name' => 'Tswana',                  'native' => 'Setswana', 'regional' => 'tn_ZA'],
        'sq'          => ['name' => 'Albanian',                'native' => 'shqip', 'regional' => 'sq_AL'],
        'sid'         => ['name' => 'Sidamo',                  'native' => 'Sidaamu Afo', 'regional' => 'sid_ET'],
        'ss'          => ['name' => 'Swati',                   'native' => 'Siswati', 'regional' => 'ss_ZA'],
        'sk'          => ['name' => 'Slovak',                  'native' => 'slovenčina', 'regional' => 'sk_SK'],
        'sl'          => ['name' => 'Slovene',                 'native' => 'slovenščina', 'regional' => 'sl_SI'],
        'so'          => ['name' => 'Somali',                  'native' => 'Soomaali', 'regional' => 'so_SO'],
        'sr-Latn'     => ['name' => 'Serbian (Latin)',         'native' => 'Srpski', 'regional' => 'sr_RS'],
        'sh'          => ['name' => 'Serbo-Croatian',          'native' => 'srpskohrvatski', 'regional' => ''],
        'fi'          => ['name' => 'Finnish',                 'native' => 'suomi', 'regional' => 'fi_FI'],
        'sv'          => ['name' => 'Swedish',                 'native' => 'svenska', 'regional' => 'sv_SE'],
        'sg'          => ['name' => 'Sango',                   'native' => 'Sängö', 'regional' => ''],
        'tl'          => ['name' => 'Tagalog',                 'native' => 'Tagalog', 'regional' => 'tl_PH'],
        'tzm-Latn'    => ['name' => 'Central Atlas Tamazight (Latin)',  'native' => 'Tamazight', 'regional' => ''],
        'kab'         => ['name' => 'Kabyle',                  'native' => 'Taqbaylit', 'regional' => 'kab_DZ'],
        'twq'         => ['name' => 'Tasawaq',                 'native' => 'Tasawaq senni', 'regional' => ''],
        'shi'         => ['name' => 'Tachelhit (Latin)',       'native' => 'Tashelhit', 'regional' => ''],
        'nus'         => ['name' => 'Nuer',                    'native' => 'Thok Nath', 'regional' => ''],
        'vi'          => ['name' => 'Vietnamese',              'native' => 'Tiếng Việt', 'regional' => 'vi_VN'],
        'tg-Latn'     => ['name' => 'Tajik (Latin)',           'native' => 'tojikī', 'regional' => 'tg_TJ'],
        'lu'          => ['name' => 'Luba-Katanga',            'native' => 'Tshiluba', 'regional' => 've_ZA'],
        've'          => ['name' => 'Venda',                   'native' => 'Tshivenḓa', 'regional' => ''],
        'tw'          => ['name' => 'Twi',                     'native' => 'Twi', 'regional' => ''],
        'tr'          => ['name' => 'Turkish',                 'native' => 'Türkçe', 'regional' => 'tr_TR'],
        'ale'         => ['name' => 'Aleut',                   'native' => 'Unangax tunuu', 'regional' => ''],
        'ca-valencia' => ['name' => 'Valencian',               'native' => 'valencià', 'regional' => ''],
        'vai-Latn'    => ['name' => 'Vai (Latin)',             'native' => 'Viyamíĩ', 'regional' => ''],
        'vo'          => ['name' => 'Volapük',                 'native' => 'Volapük', 'regional' => ''],
        'fj'          => ['name' => 'Fijian',                  'native' => 'vosa Vakaviti', 'regional' => ''],
        'wa'          => ['name' => 'Walloon',                 'native' => 'Walon', 'regional' => 'wa_BE'],
        'wae'         => ['name' => 'Walser',                  'native' => 'Walser', 'regional' => 'wae_CH'],
        'wen'         => ['name' => 'Sorbian',                 'native' => 'Wendic', 'regional' => ''],
        'wo'          => ['name' => 'Wolof',                   'native' => 'Wolof', 'regional' => 'wo_SN'],
        'ts'          => ['name' => 'Tsonga',                  'native' => 'Xitsonga', 'regional' => 'ts_ZA'],
        'dje'         => ['name' => 'Zarma',                   'native' => 'Zarmaciine', 'regional' => ''],
        'yo'          => ['name' => 'Yoruba',                  'native' => 'Èdè Yorùbá', 'regional' => 'yo_NG'],
        'de-AT'       => ['name' => 'Austrian German',         'native' => 'Österreichisches Deutsch', 'regional' => 'de_AT'],
        'is'          => ['name' => 'Icelandic',               'native' => 'íslenska', 'regional' => 'is_IS'],
        'cs'          => ['name' => 'Czech',                   'native' => 'čeština', 'regional' => 'cs_CZ'],
        'bas'         => ['name' => 'Basa',                    'native' => 'Ɓàsàa', 'regional' => ''],
        'mas'         => ['name' => 'Masai',                   'native' => 'ɔl-Maa', 'regional' => ''],
        'haw'         => ['name' => 'Hawaiian',                'native' => 'ʻŌlelo Hawaiʻi', 'regional' => ''],
        'el'          => ['name' => 'Greek',                   'native' => 'Ελληνικά', 'regional' => 'el_GR'],
        'uz'          => ['name' => 'Uzbek (Cyrillic)',        'native' => 'Ўзбек', 'regional' => 'uz_UZ'],
        'az-Cyrl'     => ['name' => 'Azerbaijani (Cyrillic)',  'native' => 'Азәрбајҹан', 'regional' => 'uz_UZ'],
        'ab'          => ['name' => 'Abkhazian',               'native' => 'Аҧсуа', 'regional' => ''],
        'os'          => ['name' => 'Ossetic',                 'native' => 'Ирон', 'regional' => 'os_RU'],
        'ky'          => ['name' => 'Kyrgyz',                  'native' => 'Кыргыз', 'regional' => 'ky_KG'],
        'sr'          => ['name' => 'Serbian (Cyrillic)',      'native' => 'Српски', 'regional' => 'sr_RS'],
        'av'          => ['name' => 'Avaric',                  'native' => 'авар мацӀ', 'regional' => ''],
        'ady'         => ['name' => 'Adyghe',                  'native' => 'адыгэбзэ', 'regional' => ''],
        'ba'          => ['name' => 'Bashkir',                 'native' => 'башҡорт теле', 'regional' => ''],
        'be'          => ['name' => 'Belarusian',              'native' => 'беларуская', 'regional' => 'be_BY'],
        'bg'          => ['name' => 'Bulgarian',               'native' => 'български', 'regional' => 'bg_BG'],
        'kv'          => ['name' => 'Komi',                    'native' => 'коми кыв', 'regional' => ''],
        'mk'          => ['name' => 'Macedonian',              'native' => 'македонски', 'regional' => 'mk_MK'],
        'mn'          => ['name' => 'Mongolian (Cyrillic)',    'native' => 'монгол', 'regional' => 'mn_MN'],
        'ce'          => ['name' => 'Chechen',                 'native' => 'нохчийн мотт', 'regional' => 'ce_RU'],
        'ru'          => ['name' => 'Russian',                 'native' => 'русский', 'regional' => 'ru_RU'],
        'sah'         => ['name' => 'Yakut',                   'native' => 'саха тыла', 'regional' => ''],
        'tt'          => ['name' => 'Tatar',                   'native' => 'татар теле', 'regional' => 'tt_RU'],
        'tg'          => ['name' => 'Tajik (Cyrillic)',        'native' => 'тоҷикӣ', 'regional' => 'tg_TJ'],
        'tk'          => ['name' => 'Turkmen',                 'native' => 'түркменче', 'regional' => 'tk_TM'],
        'ua'          => ['name' => 'Ukrainian',               'native' => 'українська', 'regional' => 'uk_UA'],
        'cv'          => ['name' => 'Chuvash',                 'native' => 'чӑваш чӗлхи', 'regional' => 'cv_RU'],
        'cu'          => ['name' => 'Church Slavic',           'native' => 'ѩзыкъ словѣньскъ', 'regional' => ''],
        'kk'          => ['name' => 'Kazakh',                  'native' => 'қазақ тілі', 'regional' => 'kk_KZ'],
        'hy'          => ['name' => 'Armenian',                'native' => 'Հայերեն', 'regional' => 'hy_AM'],
        'yi'          => ['name' => 'Yiddish',                 'native' => 'ייִדיש', 'regional' => 'yi_US'],
        'he'          => ['name' => 'Hebrew',                  'native' => 'עברית', 'regional' => 'he_IL'],
        'ug'          => ['name' => 'Uyghur',                  'native' => 'ئۇيغۇرچە', 'regional' => 'ug_CN'],
        'ur'          => ['name' => 'Urdu',                    'native' => 'اردو', 'regional' => 'ur_PK'],
        'ar'          => ['name' => 'Arabic',                  'native' => 'العربية', 'regional' => 'ar_AE'],
        'uz-Arab'     => ['name' => 'Uzbek (Arabic)',          'native' => 'اۉزبېک', 'regional' => ''],
        'tg-Arab'     => ['name' => 'Tajik (Arabic)',          'native' => 'تاجیکی', 'regional' => 'tg_TJ'],
        'sd'          => ['name' => 'Sindhi',                  'native' => 'سنڌي', 'regional' => 'sd_IN'],
        'fa'          => ['name' => 'Persian',                 'native' => 'فارسی', 'regional' => 'fa_IR'],
        'pa-Arab'     => ['name' => 'Punjabi (Arabic)',        'native' => 'پنجاب', 'regional' => 'pa_IN'],
        'ps'          => ['name' => 'Pashto',                  'native' => 'پښتو', 'regional' => 'ps_AF'],
        'ks'          => ['name' => 'Kashmiri (Arabic)',       'native' => 'کأشُر', 'regional' => 'ks_IN'],
        'ku'          => ['name' => 'Kurdish',                 'native' => 'کوردی', 'regional' => 'ku_TR'],
        'dv'          => ['name' => 'Divehi',                  'native' => 'ދިވެހިބަސް', 'regional' => 'dv_MV'],
        'ks-Deva'     => ['name' => 'Kashmiri (Devaganari)',   'native' => 'कॉशुर', 'regional' => 'ks_IN'],
        'kok'         => ['name' => 'Konkani',                 'native' => 'कोंकणी', 'regional' => 'kok_IN'],
        'doi'         => ['name' => 'Dogri',                   'native' => 'डोगरी', 'regional' => 'doi_IN'],
        'ne'          => ['name' => 'Nepali',                  'native' => 'नेपाली', 'regional' => ''],
        'pra'         => ['name' => 'Prakrit',                 'native' => 'प्राकृत', 'regional' => ''],
        'brx'         => ['name' => 'Bodo',                    'native' => 'बड़ो', 'regional' => 'brx_IN'],
        'bra'         => ['name' => 'Braj',                    'native' => 'ब्रज भाषा', 'regional' => ''],
        'mr'          => ['name' => 'Marathi',                 'native' => 'मराठी', 'regional' => 'mr_IN'],
        'mai'         => ['name' => 'Maithili',                'native' => 'मैथिली', 'regional' => 'mai_IN'],
        'raj'         => ['name' => 'Rajasthani',              'native' => 'राजस्थानी', 'regional' => ''],
        'sa'          => ['name' => 'Sanskrit',                'native' => 'संस्कृतम्', 'regional' => 'sa_IN'],
        'hi'          => ['name' => 'Hindi',                   'native' => 'हिन्दी', 'regional' => 'hi_IN'],
        'as'          => ['name' => 'Assamese',                'native' => 'অসমীয়া', 'regional' => 'as_IN'],
        'bn'          => ['name' => 'Bengali',                 'native' => 'বাংলা', 'regional' => 'bn_BD'],
        'mni'         => ['name' => 'Manipuri',                'native' => 'মৈতৈ', 'regional' => 'mni_IN'],
        'pa'          => ['name' => 'Punjabi (Gurmukhi)',      'native' => 'ਪੰਜਾਬੀ', 'regional' => 'pa_IN'],
        'gu'          => ['name' => 'Gujarati',                'native' => 'ગુજરાતી', 'regional' => 'gu_IN'],
        'or'          => ['name' => 'Oriya',                   'native' => 'ଓଡ଼ିଆ', 'regional' => 'or_IN'],
        'ta'          => ['name' => 'Tamil',                   'native' => 'தமிழ்', 'regional' => 'ta_IN'],
        'te'          => ['name' => 'Telugu',                  'native' => 'తెలుగు', 'regional' => 'te_IN'],
        'kn'          => ['name' => 'Kannada',                 'native' => 'ಕನ್ನಡ', 'regional' => 'kn_IN'],
        'ml'          => ['name' => 'Malayalam',               'native' => 'മലയാളം', 'regional' => 'ml_IN'],
        'si'          => ['name' => 'Sinhala',                 'native' => 'සිංහල', 'regional' => 'si_LK'],
        'th'          => ['name' => 'Thai',                    'native' => 'ไทย', 'regional' => 'th_TH'],
        'lo'          => ['name' => 'Lao',                     'native' => 'ລາວ', 'regional' => 'lo_LA'],
        'bo'          => ['name' => 'Tibetan',                 'native' => 'པོད་སྐད་', 'regional' => 'bo_IN'],
        'dz'          => ['name' => 'Dzongkha',                'native' => 'རྫོང་ཁ', 'regional' => 'dz_BT'],
        'my'          => ['name' => 'Burmese',                 'native' => 'မြန်မာဘာသာ', 'regional' => 'my_MM'],
        'ka'          => ['name' => 'Georgian',                'native' => 'ქართული', 'regional' => 'ka_GE'],
        'byn'         => ['name' => 'Blin',                    'native' => 'ብሊን', 'regional' => 'byn_ER'],
        'tig'         => ['name' => 'Tigre',                   'native' => 'ትግረ', 'regional' => 'tig_ER'],
        'ti'          => ['name' => 'Tigrinya',                'native' => 'ትግርኛ', 'regional' => 'ti_ET'],
        'am'          => ['name' => 'Amharic',                 'native' => 'አማርኛ', 'regional' => 'am_ET'],
        'wal'         => ['name' => 'Wolaytta',                'native' => 'ወላይታቱ', 'regional' => 'wal_ET'],
        'chr'         => ['name' => 'Cherokee',                'native' => 'ᏣᎳᎩ', 'regional' => ''],
        'iu'          => ['name' => 'Inuktitut',               'native' =>  'ᐃᓄᒃᑎᑐᑦ', 'regional' => 'iu_CA'],
        'oj'          => ['name' => 'Ojibwa',                  'native' => 'ᐊᓂᔑᓈᐯᒧᐎᓐ', 'regional' => ''],
        'cr'          => ['name' => 'Cree',                    'native' => 'ᓀᐦᐃᔭᐍᐏᐣ', 'regional' => ''],
        'km'          => ['name' => 'Khmer',                   'native' => 'ភាសាខ្មែរ', 'regional' => 'km_KH'],
        'mn-Mong'     => ['name' => 'Mongolian (Mongolian)',   'native' => 'ᠮᠣᠨᠭᠭᠣᠯ ᠬᠡᠯᠡ', 'regional' => 'mn_MN'],
        'shi-Tfng'    => ['name' => 'Tachelhit (Tifinagh)',    'native' => 'ⵜⴰⵎⴰⵣⵉⵖⵜ', 'regional' => ''],
        'tzm'         => ['name' => 'Central Atlas Tamazight', 'native' => 'ⵜⴰⵎⴰⵣⵉⵖⵜ', 'regional' => ''],
        'yue'         => ['name' => 'Yue',                     'native' => '廣州話', 'regional' => 'yue_HK'],
        'ja'          => ['name' => 'Japanese',                'native' => '日本語', 'regional' => 'ja_JP'],
        'zh'          => ['name' => 'Chinese (Simplified)',    'native' => '简体中文', 'regional' => 'zh_CN'],
        'zh-Hant'     => ['name' => 'Chinese (Traditional)',   'native' => '繁體中文', 'regional' => 'zh_CN'],
        'ii'          => ['name' => 'Sichuan Yi',              'native' => 'ꆈꌠꉙ', 'regional' => ''],
        'vai'         => ['name' => 'Vai (Vai)',               'native' => 'ꕙꔤ', 'regional' => ''],
        'jv-Java'     => ['name' => 'Javanese (Javanese)',     'native' => 'ꦧꦱꦗꦮ', 'regional' => ''],
        'ko'          => ['name' => 'Korean',                  'native' => '한국어', 'regional' => 'ko_KR'],
    ];


    private static $strings = [];
    private static $current = 'en_US';
    private static $supported = [];


    private $app;

    /**
     * Localization constructor.
     * @param App $app
     */
    public function __construct (App $app)
    {
        $this->app = $app;
    }


    final public function init() {
        self::$supported = explode(',', Config::get('APP_LOCALES'));
        foreach (self::$supported as &$item) {
            $item = trim($item);
            if (!in_array($item, array_keys(self::LOCALES)))
                throw new ValidationException('Locale not supported: '.$item);
        }

        self::$current = self::$supported[0];
        if (Config::get('AUTO_LOCALE'))
            $this -> autoLocalize();

        $this -> setupStrings();
    }


    private function autoLocalize(): void {
        $locale = $this -> app -> routeSegment(0);
        if ($locale && in_array($locale, self::$supported)) {
            self::setLocale($locale);
            $this -> app -> routeShift();
        }
    }


    private function setupStrings(): void {
        if (!is_dir($dir = ROOT.DIRECTORY_SEPARATOR.'strings'))
            Util::recursiveMkdir($dir);

        $dirIterator = new RecursiveDirectoryIterator($dir);
        $iteratorIterator = new RecursiveIteratorIterator($dirIterator);
        $regexIterator = new RegexIterator($iteratorIterator, '/^.+\.yaml$/i', RecursiveRegexIterator::GET_MATCH);
        foreach ($regexIterator as $file) {
            $yaml = Util::fromYaml($file[0]);

            $fileName = str_replace($dir.DIRECTORY_SEPARATOR, '', $file[0]);
            $chunks = explode(DIRECTORY_SEPARATOR, $fileName);

            $chunks[count($chunks) - 1] = str_replace('.yaml', '', $chunks[count($chunks) - 1]);
            array_push($chunks, $yaml);

            self::$strings = $this -> recursiveMerge($chunks, self::$strings);
        }
    }


    private function recursiveMerge(array $chunks, array $subject) {
        if (count($chunks) == 0)
            return [];
        $leaf = array_shift($chunks);

        if (is_string($leaf)) {
            if (!isset($subject[$leaf]))
                $subject[$leaf] = [];
            $subject[$leaf] = array_merge($subject[$leaf], $this -> recursiveMerge($chunks, $subject[$leaf]));
            return $subject;
        } elseif (count($chunks) == 0) {
            return array_merge_recursive($leaf, $subject);
        }
    }






    public static function setLocale(string $locale): void {
        if (!in_array($locale, self::$supported))
            throw new ValidationException('Locale not supported. Check out global.yaml config file');
        self::$current = $locale;
        if ($regional = self::LOCALES[self::$current]['regional'])
            $locale = $regional;
        setlocale(LC_ALL, $locale);
    }

    public static function getLocale(): string {
        return self::$current;
    }

    public static function getLocaleName(): string {
        return self::LOCALES[self::$current]['name'];
    }

    public static function getLocaleNativeName(): string {
        return self::LOCALES[self::$current]['native'];
    }


    private static function findByLang(string $str, string $lang): string {
        $leaves = array_merge([$lang], explode('/', $str));
        $node = self::$strings;
        $passedLeaves = [];
        foreach ($leaves as $leaf) {
            if (!isset($node[$leaf])) {
                array_shift($passedLeaves);
                $passed = count($passedLeaves) > 0 ? implode('/', $passedLeaves) : '/';
                throw new ValidationException('Could not find string "'.$str.'" in '.$passed);
            }

            $node = $node[$leaf];
            $passedLeaves[] = $leaf;
        }

        // Choose random string from array
        if (is_array($node))
            $val = array_values($node)[rand(0, count($node) - 1)];
        else $val = $node;
        if (!is_string($val))
            throw new ValidationException("$str is not string");
        return $val;
    }


    public static function lang(string $str, int $locale = -1): string {
        if ($locale == -1)
            $loc = self::getLocale();
        else {
            if (!isset(self::$supported[$locale]))
                return FALSE;
            $loc = self::$supported[$locale];
        }

        try {
            return self::findByLang($str, $loc);
        } catch (ValidationException $e) {
            $dat = self::lang($str, ++$locale);
            if ($dat)
                return $dat;
            else throw $e;
        }
    }
}