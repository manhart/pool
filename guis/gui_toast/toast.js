/*
 * g7system.local
 *
 * toast.js created at 18.11.20, 19:13
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2020, GROUP7 AG
 */
'use strict';

/* Styles */
const TOAST_DEFAULT = 'toast',
    TOAST_ERROR = 'error',
    TOAST_INFO = 'info',
    TOAST_SUCCESS = 'success',
    TOAST_WARNING = 'warning'
;

class Toast {
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

    /**
     * Defaults
     *
     * @constructor
     */
    constructor()
    {
    }

    static get DEFAULT_DELAY() {
        return 3000;
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

    setAutohide(autohide = true)
    {
        this.autohide = autohide
        return this;
    }

    setDelay(delay = Toast.DEFAULT_DELAY)
    {
        this.delay = delay;
        this.setAutohide(delay > 0);
        return this;
    }

    setPosition(position = 'bottom-right')
    {
        this.position = position;
        return this;
    }

    static create(style, name, title, subtitle, message, autohide, delay, position)
    {
        this.count = ++this.count || 1;
        let id = name + this.count;
        let hideAfter = Math.floor(Date.now() / 1000) + (delay / 1000);

        let toast = `<div id="${id}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-autohide=${autohide} data-delay="${delay}" data-hide-after="${hideAfter}">
            <div class="toast-header">
                <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice" focusable="false" role="img">
                    <rect width="100%" height="100%" class="${style}"></rect>
                </svg>
                <strong class="mr-auto">${title}</strong>
                <small class="text-muted">${subtitle}</small>
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

    show = (type, title, message) =>
    {
        let id = Toast.create(type, Toast.name, title, 'just now', message, (this.pauseOnHover ? false : this.autohide), this.delay, this.position);
        this.Toast = $('#'+id);

        this.Toast.on('hidden.bs.toast', function () {
            $(this).remove();
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
        let $Toast = new Toast().setDelay(delay);
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
        let $Toast = new Toast().setDelay(delay);
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
        let $Toast = new Toast().setDelay(delay);
        $Toast.show(Toast.TOAST_WARNING, title, message);
    }
}


ready(function () {

    // $('.toast').click(function(e) {
    //     console.debug('toast clicked');
    //     $(this).toast({autohide: false})
    //     $(this).toast('show');
    // })

    Toast.showWarning('Speichern', 'Du hast erfolgreich gespeichert', 0);

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

    // $('body').on('hidden.bs.toast', '.toast', function () {
    //     $(this).remove();
    // });
    // $('.toast').toast('show');
});