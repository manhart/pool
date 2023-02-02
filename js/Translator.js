/*
 * g7system.local
 *
 * Translator.js created at 22.09.20, 08:12
 *
 * @author c.schmidseder <c.schmidseder@group-7.de>, a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2020, GROUP7 AG
 */
class Translator {
    constructor() {
        this.extension = 'json';
    }

    /**
     * sets the resources directory
     *
     * @param directory
     * @returns {Translator}
     */
    setResourceDir(directory) {
        this.translation = [];
        this.directory = directory;
        return this;
    }

    /**
     * sets default language
     *
     * @param language
     * @returns {Translator}
     */
    setDefaultLanguage(language) {
        this.language = language;
        this.defaultLanguage = language;
        return this;
    }

    /**
     * change language
     *
     * @param language
     * @returns {Translator}
     */
    changeLanguage(language) {
        this.language = language;
        return this;
    }

    /**
     * get active language
     *
     * @returns {*}
     */
    getLanguage() {
        let language = this.defaultLanguage;
        if (this.defaultLanguage != this.language) {
            language = (this.hasOwnProperty('language'))  ? this.language : this.defaultLanguage;
        }
        return language;
    }

    /**
     * get translation
     *
     * @param key
     * @param args
     * @returns {*}
     */
    get(key, args) {
        let translationArray = this.getTranslation(this.getLanguage());
        let message = translationArray[key];
        if (Array.isArray(message)) {//really anything except a String is problematic e.g. undefined, null, array, int...
            //we could have an undefined key be looked up and added to the 'dictionary' (ajaxCall to translate.php) -> and then reload the dictionary async...
            //null shows us that it isn't set and defaults or handling has to be used
            //arrays are currently not supposed to be a leaf of the 'dictionary', but handling could be more graceful
            throw ('Exception was thrown because an array was accessed instead of a string. Please correct the translation.');
        }
        //formatting required
        if (typeof args != 'undefined') {
            let params;
            if (typeof args == 'object') {
                //normalize to an array
                params = (Array.isArray(args)) ? args : Object.values(args) ;
            } else {//"variadic" signature
                //grab all arguments (magic) except the key (first)
                params = Object.values(arguments);
                params.shift();
            }
            //format message.... TODO message-formatter
            params.forEach(function (param, i) {
                let searchValue = null;
                switch (typeof param) {
                    case 'string': searchValue = '%s';
                        break;
                    case 'number': searchValue = '%d';
                        break;
                }
                message = message.replace(searchValue, param);
            });//end format message
        }//END formatting required
        return message;
    }

    /**
     * get plural translation
     * @deprecated just use get with arguments and adapt the translation
     * @param key
     * @param n
     * @param args
     * @returns {*}
     */
    nget(key, n, args) {
        let language = this.getLanguage();
        let translation = this.getTranslation(language);

        let params = [];
        if (typeof args == 'undefined') {
            params = [];
        }

        if (typeof args == 'object') {
            params = (Array.isArray(args)) ? args : Object.values(args) ;
        }

        if (typeof args == 'string' || typeof args == 'number') {
            params = Object.values(arguments);
            params = params.slice(2)
        }

        let index = this.pluralRule(language, n);

        let str = key in translation ? translation[key][index] : '';
        params.unshift(n);
        params.forEach(function (param, i) {
            let searchValue = null;
            switch (typeof param) {
                case 'string': searchValue = '%s';
                    break;
                case 'number': searchValue = '%d';
                    break;
            }
            str = str.replace(searchValue, param);
        });

        return str;
    }

    /**
     * translation exists?
     *
     * @param key
     * @returns {boolean}
     */
    exists(key) {
        return (typeof this.getTranslation(this.getLanguage())[key] != 'undefined');
    }

    /**
     * checks if translation for a specific language is available
     *
     * @param language
     * @returns {boolean|boolean}
     */
    hasTranslation(language) {
        return (typeof this.translation != 'undefined' && this.translation.hasOwnProperty(language));
    }

    /**
     * set translations for a language
     *
     * @param language
     * @param trans
     */
    setTranslation(language, trans) {
        this.translation[language] = trans;
    }

    /**
     * get translations for a language
     *
     * @param language language code/country code
     * @return array
     * @throws String
     */
    getTranslation(language)
    {
        if (!this.hasTranslation(language)) {
            // error handling
            if (typeof this['directory'] == 'undefined') {
                throw 'No directory was specified for the resources.';
            }
            if (typeof language == 'undefined') {

                throw 'No language was specified.';
            }
            let translationFile = this.directory + '/' + language + '.' + this.extension;
            this.setTranslation(language, loadJSON(translationFile));
        }
        return this.translation[language];
    }

    /**
     * The plural rules are derived from code of the Zend Framework (2010-09-25),
     * which is subject to the new BSD license
     * (http://framework.zend.com/license/new-bsd).
     * Copyright (c) 2005-2010 Zend Technologies USA Inc.
     * (http://www.zend.com)
     * https://github.com/zendframework/zf1/blob/master/library/Zend/Translate/Plural.php
     *
     * @param language language code/country code
     * @param x plural variable
     * @returns {number} index of plural form rule.
     */
    pluralRule(language, x)
    {
        let index;
        switch (language) {
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
                index = (x == 1) ? 0 : 1;
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
                index = ((x == 0) || (x == 1)) ? 0 : 1;
                break;

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                index = ((x % 10 == 1) && (x % 100 != 11)) ? (0) : (((x % 10 >= 2) && (x % 10 <= 4) && ((x % 100 < 10) || (x % 100 >= 20))) ? 1 : 2);
                break;

            case 'cs':
            case 'sk':
                index = (x == 1) ? 0 : (((x >= 2) && (x <= 4)) ? 1 : 2);
                break;

            case 'ga':
                index = (x == 1) ? 0 : ((x == 2) ? 1 : 2);
                break;

            case 'lt':
                index = ((x % 10 == 1) && (x % 100 != 11)) ? (0) : (((x % 10 >= 2) && ((x % 100 < 10) || (x % 100 >= 20))) ? 1 : 2);
                break;

            case 'sl':
                index = (x % 100 == 1) ? (0) : ((x % 100 == 2) ? 1 : (((x % 100 == 3) || (x % 100 == 4)) ? 2 : 3));
                break;

            case 'mk':
                index = (x % 10 == 1) ? 0 : 1;
                break;

            case 'mt':
                index = (x == 1) ? (0) : (((x == 0) || ((x % 100 > 1) && (x % 100 < 11))) ? (1) : (((x % 100 > 10) && (x % 100 < 20)) ? 2 : 3));
                break;

            case 'lv':
                index = (x == 0) ? 0 : (((x % 10 == 1) && (x % 100 != 11)) ? 1 : 2);
                break;

            case 'pl':
                index = (x == 1) ? (0) : (((x % 10 >= 2) && (x % 10 <= 4) && ((x % 100 < 12) || (x % 100 > 14))) ? 1 : 2);
                break;

            case 'cy':
                index = (x == 1) ? (0) : ((x == 2) ? 1 : (((x == 8) || (x == 11)) ? 2 : 3));
                break;

            case 'ro':
                index = (x == 1) ? (0) : (((x == 0) || ((x % 100 > 0) && (x % 100 < 20))) ? 1 : 2);
                break;

            case 'ar':
                index = (x == 0) ? (0) : ((x == 1) ? 1 : ((x == 2) ? 2 : (((x >= 3) && (x <= 10)) ? (3) : (((x >= 11) && (x <= 99)) ? 4 : 5))));
                break;

            default:
                index = 0;
                break;
            }
            return index;
    }
}