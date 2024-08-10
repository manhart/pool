/**
 * -= jhelpers.js =-
 *
 * jQuery Helpers
 *
 * @deprecated
 * @version $Id: jquery.helpers.js 36241 2018-07-05 11:25:18Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2011-04-08
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 *
 */

/**
 *
 * @type {(function(*): (number))|*}
 */
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

/**
 * ermittelt die Groesse eines assoziativen Arrays oder die Anzahl Elemente eines Objekts
 */
jQuery.sizeOfAssoc = function(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
}

/**
 * String extends
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

/*jQuery.extend({
	shove: function(fn, object) {
		return function() {
			return fn.apply(object, arguments);
		}
	}
});*/