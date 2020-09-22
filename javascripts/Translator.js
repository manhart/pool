/*
 * g7system.local
 *
 * Translator.js created at 22.09.20, 08:12
 *
 * @author c.schmidseder <c.schmidseder@group-7.de>, a.manhart <a.manhart@group-7.de>
 * @copyright Copyright (c) 2020, GROUP7 AG
 */
class Translator {
    /**
     *
     */
    setResourceDir(directory) {
        // Todo
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

}

