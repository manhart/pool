/*
 * g7system.local
 *
 * translatorToolInline.js created at 16.12.22, 09:54
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

class translatorToolInline{
    translationMarkerHash = '#trnsl.';
    translationMarker = 'trnsl.';

    paragraph;
    constructor() {
        console.log('attempting to take control');
        try {
            this.paragraph = window.opener.document.getElementById('test');
        } catch (e) {
            console.error('failed to take control')
        }
        window.addEventListener('hashchange', () => {
                if (location.hash.startsWith(this.translationMarkerHash)){
                    let key = location.hash.substring(this.translationMarkerHash.length);
                    this.editorSetKey(key);//handle refusal?
                    window.document.getElementById(location.hash.substring(1)).focus();
                }
            }
            , false);
    }

    moveToKey(key){
        document.location.hash = this.translationMarkerHash +key;
    }

    editorSetKey(key) {
        this.paragraph.innerHTML = key;
        console.log(`Set Editor Key to: ${key}`);
    }
}
ready(() => {window.translatorToolInline = new translatorToolInline();
    console.log('translatorToolInline.js loaded')});
//taken from helper.js
function ready(fn) {
    if (document.readyState === 'complete' ||
        (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
        fn();
    } else
        document.addEventListener('DOMContentLoaded', fn);
}