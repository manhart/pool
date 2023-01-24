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
    translationMarker = 'trnsl';
    languageStyles  = [];
    constructor() {
        if (document.referrer != '') {
            const origin = new URL(window.document.referrer).origin;
            //check that origin is whitelisted
            //setup receiver
            window.addEventListener('message', (event) => {
                    if (event.origin !== origin)
                        return false;
                    let command = event.data.substring(0, event.data.indexOf(':'));
                    let args = event.data.substring(command.length + 1);
                    switch (command) {
                        case 'keySwitch':
                            this.moveToKey(args);
                            break;
                        case 'setup':
                            let langColours = args.split(',');
                            let i = 0;
                            for (const langColour of langColours) {
                                if (this.languageStyles.length === i) {
                                    const htmlStyleElement = document.createElement('style');
                                    this.languageStyles.push(htmlStyleElement);
                                    document.head.appendChild(htmlStyleElement);
                                }
                                const htmlStyleElement = this.languageStyles[i];
                                const language = langColour.substring(0, langColour.indexOf(' '));
                                const colour = langColour.substring(language.length + 1);
                                const marker = this.translationMarker;
                                //set CSS style
                                htmlStyleElement.innerHTML = `a.${marker}:lang(${language}):not(#fakeID){background-color:${colour}}`;
                                i++;
                            }
                            //clear style elements that aren't needed
                            for (; i < this.languageStyles.length; i++) {
                                this.languageStyles[i].innerHTML = '';
                            }
                            break;
                    }
                }
            );
        }
        //request setup
        window.opener?.postMessage('setup', origin);
        window.addEventListener('hashchange', () => {
                if (location.hash.startsWith(this.translationMarkerHash)){
                    let key = location.hash.substring(this.translationMarkerHash.length);
                    //send key-switch-request
                    window.opener?.postMessage('keySwitch:'+key, origin);
                    // this.editorSetKey(key);//handle refusal?
                    const newTarget = window.document.getElementById(location.hash.substring(1));
                    if (newTarget)
                        newTarget.focus();
                    else
                        window.opener?.postMessage('keyNotOnPage:'+key, origin);
                }
            }
            , false);
        //deal with event-stealing
        for (const element of document.getElementsByClassName(this.translationMarker))
            element.onclick = () => document.location.hash = '#'+element.id;
    }

    moveToKey(key){
        document.location.hash = this.translationMarkerHash +key;
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