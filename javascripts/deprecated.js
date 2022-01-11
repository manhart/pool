/*
 * g7system.local
 *
 * deprecated.js created at 11.01.22, 14:08
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

/**
 * @param selector
 * @deprecated
 */
function closeFormError(selector)
{
    var FormError = null;
    if(new String(typeof selector).toLowerCase() == 'string') {
        FormError = jQuery(selector+'FormError');
    }
    else {
        FormError = jQuery('#'+selector.attr('id')+'FormError');
    }
    if(FormError) FormError.click();
}

/**
 *
 * @deprecated
 * @param selector
 * @param message
 * @param options
 * @returns {*|jQuery}
 */
function showFormError(selector, message, options)
{
    // jQuery('#legendid').validationEngine('showPrompt', 'This is a blub', 'load');
//		alert(document.getElementById('nachnameFormError'));
//			if(jQuery(selector+'FormError')) return jQuery(selector+'FormError');
    closeFormError(selector);
//			if(document.getElementById(selector.substr(1)+'FormError')) return jQuery(selector+'FormError');

    if(new String(typeof selector).toLowerCase() == 'string') {
        var Control = jQuery(selector);
    }
    else var Control = selector;
    var promptText = message;

    if(!options) {
        var options = new Object();
        options.showArrow = 1;
        options.position = 'topRight';
    }

    var formErrorId = Control.attr('id')+'FormError';
    var prompt = jQuery('<div id="'+formErrorId+'">').addClass('formError');
    prompt.click(function() {
        jQuery(this).fadeOut(150, function() {
            jQuery(this).remove();
        });
    })

    // create the prompt content
    var promptContent = jQuery('<div>').addClass("formErrorContent").html(promptText).appendTo(prompt);
    // create the css arrow pointing at the field
    // note that there is no triangle on max-checkbox and radio
    if (options.showArrow) {
        var arrow = jQuery('<div>').addClass("formErrorArrow");

        switch (options.position) {
            case "bottomLeft":
            case "bottomRight":
                prompt.find(".formErrorContent").before(arrow);
                arrow.addClass("formErrorArrowBottom").html('<div class="line1"><!-- --></div><div class="line2"><!-- --></div><div class="line3"><!-- --></div><div class="line4"><!-- --></div><div class="line5"><!-- --></div><div class="line6"><!-- --></div><div class="line7"><!-- --></div><div class="line8"><!-- --></div><div class="line9"><!-- --></div><div class="line10"><!-- --></div>');
                break;
            case "topLeft":
            case "topRight":
                arrow.html('<div class="line10"><!-- --></div><div class="line9"><!-- --></div><div class="line8"><!-- --></div><div class="line7"><!-- --></div><div class="line6"><!-- --></div><div class="line5"><!-- --></div><div class="line4"><!-- --></div><div class="line3"><!-- --></div><div class="line2"><!-- --></div><div class="line1"><!-- --></div>');
                prompt.append(arrow);
                break;
        }
    }

    Control.before(prompt);

    var pos = _calculatePosition(Control, prompt, options.position);

    prompt.css({
        "top": pos.callerTopPosition,
        "left": pos.callerleftPosition,
        "opacity": 0
    });

    prompt.animate({
        "opacity": 0.87
    });
    return prompt;
}

/**
 * @deprecated
 * @param field
 * @param promptElmt
 * @param promptPosition
 * @returns {{callerleftPosition: string, callerTopPosition: string}}
 * @private
 */
function _calculatePosition(field, promptElmt, promptPosition) {

    var promptTopPosition, promptleftPosition;

    var fieldWidth = field.width();

    var promptHeight = promptElmt.height();

    var pos = field.position();
    promptTopPosition = pos.top;
//            alert('jpos:'+promptTopPosition);
//            alert('mypos:'+findPosY(document.getElementById('nachname')));
    promptleftPosition = pos.left;

    switch (promptPosition) {

        default:
        case "topRight":
            promptleftPosition += fieldWidth - 30;
            promptTopPosition += -promptHeight;
            break;
        case "topLeft":
            promptTopPosition += -promptHeight;
            break;
        case "centerRight":
            promptleftPosition += fieldWidth + 13;
            break;
        case "bottomLeft":
            promptTopPosition = promptTopPosition + field.height() + 15;
            break;
        case "bottomRight":
            promptleftPosition += fieldWidth - 30;
            promptTopPosition += field.height() + 5;
    }

    return {
        "callerTopPosition": promptTopPosition + "px",
        "callerleftPosition": promptleftPosition + "px"
    };
}

/**
 * Zeigt den Query.UI Calendar am Eingabefeld (uebergebener ID/Name) an
 * @deprecated
 */
function showCalendarDlg(name)
{
    jQuery('#'+name).datepicker('show');
}

/**
 * Wandelt Daten innerhalb des Selektors/Formulars z.B. #Formular :input in JSON um
 * @deprecated
 */
var toJSON = function(selector) {
    var o = {};
    jQuery.map(jQuery(selector), function(n, i) {
        o[n.name] = jQuery(n).val();
    });
    return o;
}

/**
 * Unterschiede von Jason Objekten werden ermittelt und zurück gegeben
 * @deprecated
 */
var diffJSON = function(j1, j2) {
    var o = {};
    for(key in j1) {
        if(j1[key] != j2[key]) o[key] = j2[key];
    }
    return o;
}

/**
 * Fehler Style entfernen (jQuery-Version)
 * @deprecated
 */
function j_clearErrorStyle(elem, className) {
    if(elem) {
        if(elem.hasClass(className)) {
            elem.toggleClass(className);
        }
        return true;
    }
    return false;
}

/**
 * Fehler Style setzen (jQuery-Version)
 * @deprecated
 */
function j_setErrorStyle(elem, className)
{
    if(elem) {
        if(!elem.hasClass(className)) {
            elem.toggleClass(className, true);
        }
        return true;
    }
    return false;
}