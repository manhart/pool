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

/**
 * @typedef {GUI_Module} GUI_Prompt
 */
class GUI_Prompt extends GUI_Module
{
    /** @type {HTMLDialogElement} */
    dialog;
    /** @type {HTMLLabelElement} */
    label;
    /** @type {HTMLInputElement} */
    input;
    confirmCallback;
    isDragging = false;
    dragOffset = {x: 0, y: 0};

    init(options = {})
    {
        this.dialog = this.element();
        this.label = this.element('label');
        this.input = this.element('input');
        this.element('button.confirm').addEventListener('click', this.#confirm);
        this.element('button.cancel').addEventListener('click', this.#cancel);
        this.element('.prompt-close-button').addEventListener('click', this.#cancel);
        this.input.addEventListener('keydown', this.#handleKeydown);
        this.dialog.addEventListener('keydown', this.#handleKeydown);
        this.dialog.addEventListener('mousedown', this.#startDrag);
        document.addEventListener('mousemove', this.#drag);
        document.addEventListener('mouseup', this.#stopDrag);
    }

    #handleKeydown = (evt) =>
    {
        if(evt.key === 'Enter') {
            cancelEvent(evt);
            this.#confirm();
        }
        else if(evt.key === 'Escape') {
            this.#cancel();
        }
    }

    #startDrag = (evt) =>
    {
        this.isDragging = true;
        this.dragOffset.x = evt.clientX - this.dialog.offsetLeft;
        this.dragOffset.y = evt.clientY - this.dialog.offsetTop;
        this.dialog.style.cursor = 'grabbing';
    }

    #drag = (evt) =>
    {
        if(!this.isDragging) {
            return;
        }
        this.dialog.style.left = evt.clientX - this.dragOffset.x + 'px';
        this.dialog.style.top = evt.clientY - this.dragOffset.y + 'px';
    }

    #stopDrag = (evt) =>
    {
        this.isDragging = false;
        this.dialog.style.cursor = 'default';

        // Close dialog if click outside of dialog
        if(this.dialog.style.display === 'block' && !this.dialog.contains(evt.target)) {
            this.#cancel();
        }
    }

    show(label = null, value = '', confirmCallback = null)
    {
        this.dialog.style.top = Weblication.getMousePosition().y + 'px';
        this.dialog.style.left = Weblication.getMousePosition().x + 'px';

        this.confirmCallback = confirmCallback;
        if(label) {
            this.label.textContent = label;
            this.input.placeholder = label;
        }
        this.input.value = value;
        this.dialog.style.display = 'block'; // show
        focusCtrl(this.input);
    }

    showAtPos(label = null, y, x, confirmCallback = null)
    {
        this.dialog.style.top = y + 'px';
        this.dialog.style.left = x + 'px';

        this.show(label, confirmCallback);
    }

    hide()
    {
        this.dialog.style.display = 'none';
    }

    #confirm = () =>
    {
        this.hide();
        if(this.confirmCallback)
            this.confirmCallback(this.input.value);
    }

    #cancel = () =>
    {
        if(this.dialog.computedStyleMap().get('position').value === 'static') {
            return;
        }

        this.hide();
    }
}
Weblication.registerClass(GUI_Prompt);