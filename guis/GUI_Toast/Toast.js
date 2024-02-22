/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

/* Styles */
const
    TOAST_DEFAULT = 'toast',
    TOAST_ERROR = 'error',
    TOAST_INFO = 'info',
    TOAST_SUCCESS = 'success',
    TOAST_WARNING = 'warning';

/**
 * @class Toast
 */
class Toast
{
/**
  ab ES7
    static const STYLE_DEFAULT = 'toast';
    static const STYLE_ERROR = 'error';
    static const STYLE_INFO = 'info';
    static const STYLE_SUCCESS = 'success';
    static const STYLE_WARNING = 'warning';
*/
    autohide = true;
    delay = Toast.DEFAULT_DELAY;
    onHideCallback = null;
    position = 'bottom-right';
    Toast = null;
    pauseOnHover = true;

    constructor(name = 'Toast')
    {
        this.name = name;
    }

    static get DEFAULT_DELAY() {
        return 5000;
    }

    /**
     * non-writeable property TOAST_DEFAULT
     *
     * @returns {string}
     */
    static get TOAST_DEFAULT() {
        return TOAST_DEFAULT;
    }

    /**
     * non-writeable property TOAST_ERROR
     *
     * @returns {string}
     */
    static get TOAST_ERROR() {
        return TOAST_ERROR;
    }

    /**
     * non-writeable property TOAST_INFO
     *
     * @returns {string}
     */
    static get TOAST_INFO() {
        return TOAST_INFO;
    }

    /**
     * non-writeable property TOAST_SUCCESS
     *
     * @returns {string}
     */
    static get TOAST_SUCCESS() {
        return TOAST_SUCCESS;
    }

    /**
     * non-writeable property TOAST_WARNING
     *
     * @returns {string}
     */
    static get TOAST_WARNING() {
        return TOAST_WARNING;
    }

    /**
     * overwrite for translation
     *
     * @param key
     * @param n
     * @returns {*}
     */
    gettext(key, n)
    {
        if(n > 1) {
            return {
                'secondsBehind': n+' seconds ago',
                'minutesBehind': n+' minutes ago',
                'hoursBehind': n+' hours ago'
            }[key];
        }
        else {
            return {
                'justNow': 'just now',
                'secondsBehind': n+' second ago',
                'minutesBehind': n+' minute ago',
                'hoursBehind': n+' hours ago',
            }[key];
        }
    }

    /**
     * Controlls autohide
     *
     * @param autohide
     * @returns {Toast}
     */
    setAutohide(autohide = true)
    {
        this.autohide = autohide
        return this;
    }

    /**
     * Set delay for autohide. Activates  autonomously autohide, if delay is present!
     *
     * @param delay
     * @returns {Toast}
     */
    setDelay(delay = Toast.DEFAULT_DELAY)
    {
        this.delay = delay;
        this.setAutohide(delay > 0);
        return this;
    }

    /**
     * Set callback for onHide event
     *
     * @param callback
     * @return {null}
     */
    onHide(callback)
    {
        this.onHideCallback = callback;
        return this;
    }

    /**
     * Set position of the toasts. See style classes.
     *
     * @param position
     * @returns Toast
     */
    setPosition(position = 'bottom-right')
    {
        this.position = position;
        return this;
    }

    /**
     * Creates toast html element and adds it to the toast-container
     *
     * @param style
     * @param name
     * @param title
     * @param subtitle
     * @param message
     * @param autohide
     * @param delay
     * @param position
     * @static
     * @returns string id of the toast
     */
    static create(style, name, title, subtitle, message, autohide, delay, position)
    {
        this.count = ++this.count || 1;
        let id = name + this.count;
        let hideAfter = Math.floor(Date.now() / 1000) + (delay / 1000);
        let now = Date.now();

        let toast = `<div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-autohide=${autohide}
data-delay="${delay}" data-hide-after="${hideAfter}" data-created="${now}">
            <div class="toast-header">
                <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img">
                    <rect width="100%" height="100%" class="${style}"></rect>
                </svg>
                <strong class="mr-auto">${title}</strong>
                <small class="text-muted text-nowrap ml-1">${subtitle}</small>
                <button type="button" class="ms-2 mb-1 btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>`;

        let container = document.querySelector('.toast-container');
        container.classList.add(position);
        container.insertAdjacentHTML('beforeend', toast);
        return id;
    }

    /**
     * Schedule a Toast to be displayed
     * @param type The category of the Message e.g. Error determines the look of the Created Toast
     * @param title The name of this Toast (Optional). Expects a String or an array [translationKey, default] <br>
     * Defaults based on  the type are Provided.
     * @param message  The content of this Toast. Expects a String or an array [translationKey, default]
     * @param subtitle Additional information displayed instead of the Toasts age
     * @returns void
     */
    show = (type, title, message, subtitle = null) =>
    {
        this.showAsync(type, title, message, subtitle).catch(
            (e)  => {
                if (e instanceof Error)
                    console.error(e);
                    window.alert('Displaying Notification failed: '+ message +'\n---------\n'+e.message );
            }
        );
    }

    /**
     * Schedule a Toast to be displayed
     * @param type The category of the Message e.g. Error determines the look of the Created Toast
     * @param title The name of this Toast (Optional). Expects a String or an array [translationKey, default] <br>
     * Defaults based on  the type are Provided.
     * @param message  The content of this Toast. Expects a String or an array [translationKey, default]
     * @param subtitle Additional information displayed instead of the Toasts age
     * @param onHide A function to be called when the Toast is hidden
     * @returns {Promise<Toast>}
     */
    showAsync = async (type, title, message, subtitle) => {
        let translationError = null;
        let fallbackAvailable = 1;
        do {//this may run a second time
            if (isArray(title)) {//Translatable Set
                try {
                    title = Transl.get(title[0]);//fetch this key
                }
                catch (error) {
                    translationError = error;
                    title = title[1] ?? null;//use default provided by caller or fallback
                }
            }
            if (isString(title)) break;//work with the valid Title
            switch (type){//Decide wich fallback to use based on type of the Toast
                case Toast.TOAST_ERROR:
                    title = ['global.errorMessage', 'Achtung'];
                    break;
                case Toast.TOAST_WARNING:
                    title = ['global.warningMessage', 'Warnung'];
                    break;
                case Toast.TOAST_INFO:
                    title = ['global.statusMessage', 'Info'];
                    break;
                case Toast.TOAST_SUCCESS:
                    title = ['global.message', 'Erfolg'];
                    break;
                case Toast.TOAST_DEFAULT:
                    title = ['global.hint', 'Hinweis'];
                    break;
                default:
                    title = null;
            }
        }while (0 < fallbackAvailable--)//prevent infinite loops alt. fallbackAvailable && !(fallbackAvailable = false);
        //Check whether we can continue
        if (!isString(title)) throw new Error("Unable to get titel for Toast-type: " + type, translationError)
        //start creation
        let id = Toast.create(type, Toast.name, title, (subtitle) ? subtitle : this.gettext('justNow'),
            message, (this.pauseOnHover ? false : this.autohide), this.delay, this.position);
        //get newly created Object from the DOM
        this.Toast = $('#'+id);
        //Update handler...
        if(!subtitle) {//Only necessary when the time isn't overridden by a subtitle
            //Create update intervall of 1s
            let dateUpdateIntervall = setInterval(() => {
                //produce a message...
                let secondsBehind, minutesBehind = null;

                let created = parseInt(this.Toast.data('created'), 10);
                secondsBehind = Math.round((Date.now() - created) / 1000);

                if(secondsBehind > 59) {
                    minutesBehind = Math.round(secondsBehind / 60);
                }

                subtitle = (minutesBehind ?
                        this.gettext('minutesBehind', minutesBehind) :
                        this.gettext('secondsBehind', secondsBehind)
                );
                //inject new value directly into the DOM
                document.querySelector('#'+id+' .toast-header .text-muted').innerHTML = subtitle;
            }, 1000);
            //add a handler to stop the intervall when the Toast is hidden
            this.Toast.on('hidden.bs.toast', () => clearInterval(dateUpdateIntervall));
        }
        //add a handler that removes the Toast from the DOM when it is hidden
        this.Toast.on('hidden.bs.toast', () => {
            $('#'+id).remove();
            if (typeof this.onHideCallback === 'function') {
                // execute callback
                this.onHideCallback();
            }
        });
        //start displaying
        this.Toast.toast('show');
        //check whether handlers for auto-hiding are required
        if(this.autohide === false) {
            return this;//No handlers to attach
        }
        //start countdown
        this.paused = false;
        setTimeout(() => {
            if(this.paused === false) {
                if(this.Toast.length > 0) {
                    this.Toast.toast('hide');
                }
            }
        }, this.delay)
        //add more Handlers
        // pause on hover
        this.Toast.on('mouseover', () => {
            this.paused = true;
        });
        //resume on mouseleave
        this.Toast.on('mouseleave', () => {
            const current = Math.floor(Date.now() / 1000),
                future = parseInt(this.Toast.data('hideAfter'));

            this.paused = false;

            if (current >= future) {
                this.Toast.toast('hide');
            }
        });
        return this;
    }

    /**
     * Show a Toast with individual type and dynamic content
     *
     * @param type @see Toast.TOAST_* constants
     * @param title
     * @param message
     * @param {string} delay optional
     * @param {function|null} onHide
     */
    static show = (type, title, message, delay = Toast.DEFAULT_DELAY, onHide = null) =>
    {
        let $Toast = new Toast();
        $Toast.setDelay(delay);
        $Toast.onHide(onHide);
        $Toast.show(type, title, message);
    }

    /**
     * Show error
     *
     * @param title
     * @param message
     * @param {string} delay optional
     */
    static showError(title, message, delay = Toast.DEFAULT_DELAY)
    {
        let $Toast = new Toast()
        $Toast.setDelay(delay);
        $Toast.show(Toast.TOAST_ERROR, title, message);
    }

    /**
     * Show info
     *
     * @param title
     * @param message
     * @param {string} delay optional
     */
    static showInfo(title, message, delay = Toast.DEFAULT_DELAY)
    {
        let $Toast = (new Toast()).setDelay(delay);
        $Toast.show(Toast.TOAST_INFO, title, message);
    }

    /**
     * Show success
     *
     * @param title
     * @param message
     * @param {string} delay optional
     */
    static showSuccess(title, message, delay = Toast.DEFAULT_DELAY)
    {
        let $Toast = (new Toast()).setDelay(delay);
        $Toast.show(Toast.TOAST_SUCCESS, title, message);
    }

    /**
     * Show warning
     *
     * @param title
     * @param message
     * @param {string} delay optional
     */
    static showWarning(title, message, delay = Toast.DEFAULT_DELAY)
    {
        let $Toast = (new Toast()).setDelay(delay);
        $Toast.show(Toast.TOAST_WARNING, title, message);
    }
}
// Weblication.registerClass(Toast);

/**
 * Example e.g. for testing:
 *
 */
// ready(function () {
    // Toast.showSuccess('Speichern', 'Die Daten wurden erfolgreich gespeichert', 3000);
    // Toast.showInfo('Info', 'Sie haben eine neue Benachrichtigung', 2000);
    // Toast.showError('Fehler', 'Es ist ein schwerwiegender Fehler aufgetreten!', 0);
    // Toast.showWarning('Warnung', 'Sie werden in 5 Minuten automatisch ausgeloggt.', 0);

    // let $Toast = new Toast().setDelay(0).setPosition('bottom-center');
    // $Toast.show(Toast.TOAST_INFO, 'Message', 'Welcome message');

/*
    window.setTimeout(function () {
        for(let i=0; i<6; i++) {
            switch(i%3) {
                case 0:
                    window.setTimeout(function() {
                        Toast.showError('Fehler '+i, 'Das ist nur ein Test!', 10000);
                    }, i*100);

                    break;
                case 1:
                    window.setTimeout(function() {
                        Toast.showInfo('Test '+i, 'Das ist ein Test');
                    }, i*150);
                    break;
                case 2:
                    window.setTimeout(function() {
                        Toast.showSuccess('Test '+i, 'Das ist ein Test. Das ist ein Test. Das ist ein Test.' +
                            'Das ist ein Test. Das ist ein Test.');
                    }, i+120);
                    break;
            }
        }
    }, 200)
    */

    // $('body').on('hidden.bs.toast', '.toast', function () {
    //     $(this).remove();
    // });
    // $('.toast').toast('show');
// });