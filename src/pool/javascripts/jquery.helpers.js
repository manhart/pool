/**
 * -= jhelpers.js =-
 *
 * jQuery Helpers
 *
 * @version $Id: jquery.helpers.js 36241 2018-07-05 11:25:18Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2011-04-08
 * @author Alexander Manhart <alexander.manhart@wochenblatt.de>
 * @link http://www.wochenblatt.de
 *
 */

/**
 * Zeigt den Query.UI Calendar am Eingabefeld (uebergebener ID/Name) an
 */
function showCalendarDlg(name)
{
	jQuery('#'+name).datepicker('show');
}

/**
 * Wandelt Daten innerhalb des Selektors/Formulars z.B. #Formular :input in JSON um
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

jQuery.maxZIndex = jQuery.fn.maxZIndex = function(opt) {
    /// <summary>
    /// Returns the max zOrder in the document (no parameter)
    /// Sets max zOrder by passing a non-zero number
    /// which gets added to the highest zOrder.
    /// </summary>
    /// <param name="opt" type="object">
    /// inc: increment value,
    /// group: selector for zIndex elements to find max for
    /// </param>
    /// <returns type="jQuery" />
    var def = { inc: 10, group: "*" };
    jQuery.extend(def, opt);
    var zmax = 0;
    jQuery(def.group).each(function() {
        var cur = parseInt(jQuery(this).css('z-index'));
        zmax = cur > zmax ? cur : zmax;
    });
    if (!this.jquery)
        return zmax;

    return this.each(function() {
        zmax += def.inc;
        jQuery(this).css("z-index", zmax);
    });
}

/*
var diffControlsJSON = function(j1, j2) {
	var o = {}, ctrl = null;
	var tag = '', tag_type = '';;
	for(key in j1) {
		ctrl = jQuery('#'+key);
		tag = ctrl.tagName.toUpperCase();
		if(tag == 'INPUT') {
			tag_type = ctrl.type.toUpperCase();

		}
		if(j1[key] != j2[key]) o[key] = j2[key];
	}
	return o;
}
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
 * String Erweiterungen
 */

if(typeof String.prototype.interpret != 'function') {
    jQuery.extend(String, {
        interpret: function(value) {
            return value == null ? '' : String(value);
        },
        specialChar: {
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '\\': '\\\\'
        }
    });
}

if(typeof String.prototype.gsub != 'function') {
    jQuery.extend(String.prototype, {
        gsub: function(pattern, replacement) {
            var result = '', source = this, match;
            
            while(source.length > 0) {
                if(match = source.match(pattern)) {
                    result += source.slice(0, match.index);
                    result += String.interpret(replacement(match));
                    source = source.slice(match.index + match[0].length);
                }
                else {
                    result += source, source = '';
                }
            }
            return result;
        }
    });
}
    
if(typeof String.prototype.pad != 'function') {
    jQuery.extend(String.prototype, {
        pad: function(length, radix, val) {
            var str = '' + this.toString(radix || 10);
            if(!val) val = '0';
            while(str.length < length) {
                str = val + str;
            }
            return str;
        }
    });
}

/**
 * ermittelt die Groesse eines assoziativen Arrays oder die Anzahl Elemente eines Objekts
 */
jQuery.sizeOfAssoc = function(obj) {
	var size = 0, key;
	for (key in obj) {
		if (obj.hasOwnProperty(key)) size++;
	}
	return size;
};


/*jQuery.extend({
	shove: function(fn, object) {
		return function() {
			return fn.apply(object, arguments);
		}
	}
});*/