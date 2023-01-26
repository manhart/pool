/*
 * POOL
 *
 * toast.js created at 18.11.20, 19:13
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
 */

/* Styles */
const
    TOAST_DEFAULT = 'toast',
    TOAST_ERROR = 'error',
    TOAST_INFO = 'info',
    TOAST_SUCCESS = 'success',
    TOAST_WARNING = 'warning'
;

class Toast
{
    /* > ES7
    static const STYLE_DEFAULT = 'toast';
    static const STYLE_ERROR = 'error';
    static const STYLE_INFO = 'info';
    static const STYLE_SUCCESS = 'success';
    static const STYLE_WARNING = 'warning';
    */
    autohide = true;
    delay = Toast.DEFAULT_DELAY;
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
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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
     * Show toast notification
     *
     * @param type
     * @param title
     * @param message
     * @param subtitle
     * @returns Toast
     */
    show = (type, title, message, subtitle = null) =>
    {
        let id = Toast.create(type, Toast.name, title, (subtitle) ? subtitle : this.gettext('justNow'),
            message, (this.pauseOnHover ? false : this.autohide), this.delay, this.position);
        this.Toast = $('#'+id);

        let dateUpdateHandler = null;
        if(!subtitle) {
            dateUpdateHandler = setInterval(() => {
                let secondsbehind, minutesbehind = null;

                let created = parseInt(this.Toast.data('created'), 10);
                secondsbehind = Math.round((Date.now() - created) / 1000);

                if(secondsbehind > 59) {
                    minutesbehind = Math.round(secondsbehind / 60);
                }

                let subtitle = (minutesbehind ?
                    this.gettext('minutesBehind', minutesbehind) :
                    this.gettext('secondsBehind', secondsbehind)
                );
                document.querySelector('#'+id+' .toast-header .text-muted').innerHTML = subtitle;
            }, 1000);
        }

        this.Toast.on('hidden.bs.toast', () => {
            $('#'+id).remove();
            if(dateUpdateHandler) {
                clearInterval(dateUpdateHandler);
            }

        });

        this.Toast.toast('show');

        if(this.autohide == false) {
            return this;
        }

        // pause on hover
        this.paused = false;
        setTimeout(() => {
            if(this.paused == false) {
                if(this.Toast.length > 0) {
                    this.Toast.toast('hide');
                }
            }
        }, this.delay)

        this.Toast.on('mouseover', () => {
            this.paused = true;
        });

        this.Toast.on('mouseleave', () => {
            const current = Math.floor(Date.now() / 1000),
                future = parseInt(this.Toast.data('hideAfter'));

            this.paused = false;

            // console.debug('mouseleave', current, future);
            if (current >= future) {
                this.Toast.toast('hide');
            }
        })
        return this;
    }

    /**
     * Show error
     *
     * @param title
     * @param message
     * @param delay [optional]
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
     * @param delay [optional]
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
     * @param delay [optional]
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
     * @param delay [optional]
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
 * */
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