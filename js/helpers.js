/**
 * -= helpers.js =-
 *
 * Ermittelt Informationen �ber den Browser: Name, Version, Plattform, DOM kompatibel
 *
 * @version $Id: helpers.js,v 1.16 2007/07/11 07:57:20 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 *
 */

/**
 *
 * @param value
 * @param newline
 * @returns {string}
 */
function pray(value, newline = '\n') {
    let TextAttributes = function (ObjName, Object, NewLine) {
        if (!NewLine) NewLine = '\n';
        if (ObjName.length) {
            var Result = "Attributes of the object \"" + ObjName + "\" :\n\n";
        }
        var Attribute;
        for (Attribute in Object) {
            Result = Result + ObjName + "." + String(Attribute) + NewLine;
        }
        return Result;
    }

    return TextAttributes('', value, newline);
}

/**
 * cancels event
 *
 * @param evt
 * @returns {boolean}
 */
function cancelEvent(evt) {
    if (!evt instanceof Event) {
        return false;
    }
    evt.preventDefault();
    evt.stopPropagation();
}

function reloadOpener() {
    if (window.opener) {
        window.opener.document.location.reload();
    }
}

function prepareUrl(url) {
    let end = url.substr(url.length - 1, 1);
    if (end == '&') return url;
    if (end == '?') return url;

    if (url.search(/\?/) != -1) {
        url = url + '&';
    } else {
        url = url + '?';
    }
    return url;
}

// wahr, wenn das Zeichen eine Zahl ist
function isDigit(ch) {
    if ((ch >= '0') && (ch <= '9'))
        return true;
    else
        return false;
}

// wahr, wenn der String eine Zahl ist
function isNumeric(value) {
    var len = value.length;
    for (var i = 0; i < len; i++) {
        if (i == 0 && (value.charAt(i) == '-' || value.charAt(i) == '+')) continue;
        if (!isDigit(value.charAt(i))) return false;
    }
    return true;
}

// wahr, wenn der String eine Fliesskommazahl ist
function isGermanFloat(value) {
    if (value == undefined) return false;
    value = value.replace('.', '').replace(',', '.');
    var len = value.length
    for (var i = 0; i < len; i++) {
        if (i == 0 && (value.charAt(i) == '-' || value.charAt(i) == '+')) continue;
        if (value.charAt(i) == '.') continue;
        if (!isDigit(value.charAt(i))) {
            return false;
        }
    }
    return true;
}

// wahr, wenn das Zeichen ein Buchstabe ist
function isAlpha(ch) {
    if (((ch >= 'a') && (ch <= 'z')) || ((ch >= 'A') && (ch <= 'Z')))
        return true;
    else
        return false;
}

// wahr, wenn das Zeichen alphanumerisch ist
function isAlnum(ch) {
    if (isAlpha(ch) || isDigit(ch))
        return true;
    else
        return false;
}

// wahr, wenn kein Zeichen aus str2 in str1 vorkommt
function notIn(str1, str2) {
    var i = 0;
    var j = str2.length;
    for (; i < j; i++) {
        var str3 = str2.charAt(i);
        if (str1.indexOf(str3) != -1)
            return false;
    }
    return true;
}

// wahr, wenn eine Ziffernfolge vorliegt
function checkNumberSequence(nr) {
    var i = 0;
    var j = nr.length;

    if (j < 1)
        return false;

    for (; i < j; i++)
        if ((nr.charAt(i) < '0') || (nr.charAt(i) > '9'))
            return false;

    return true;
}

function findPosX(obj)
{
    var curleft = 0;
    if (obj.offsetParent) {
        while (obj.offsetParent) {
            curleft += obj.offsetLeft;
            curleft -= obj.scrollLeft;
            obj = obj.offsetParent;
        }
    } else if (obj.x) {
        curleft += obj.x;
    }
    return curleft;
}

function findPosY(obj)
{
    var curtop = 0;
    if (obj.offsetParent) {
        while (obj.offsetParent) {
            curtop += obj.offsetTop;
            curtop -= obj.scrollTop;
            obj = obj.offsetParent;
        }
    } else if (obj.y) {
        curtop += obj.y;
    }
    return curtop;
}

function focusCtrl(elem) {
    // console.debug('focusCtrl', elem);
    if (elem) {
        if (elem.focus) {
            try {
                elem.focus();
            } catch (e) {
            }
        }
        if (elem.select) {
            elem.select();
        }
    }
}

function isAlien(a) {
    return isObject(a) && typeof a.constructor != 'function';
}

function isArray(a) {
    return isObject(a) && a.constructor == Array;
}

function isBoolean(a) {
    return typeof a == 'boolean';
}

function isEmpty(o) {
    if (isObject(o)) {
        for (let prop in o) {
            if (o.hasOwnProperty(prop)) {
                return false;
            }
            // v = o[i];
            // if (isUndefined(v) && isFunction(v)) {
            //     return false;
            // }
        }
    } else {
        return (o == '' || o == 0 || o == null);
    }
    return true;
}

function isFunction(a) {
    return typeof a == 'function';
}

function isNull(a) {
    return typeof a == 'object' && !a;
}

function isNumber(a) {
    return typeof a == 'number' && isFinite(a);
}

function isObject(a) {
    return (a && typeof a == 'object') || isFunction(a);
}

function isString(a) {
    return typeof a == 'string';
}

function isUndefined(a) {
    return typeof a == 'undefined';
}

function isInt(n) {
    return n != "" && !isNaN(n) && Math.round(n) == n;
}

function isFloat(n) {
    return n != "" && !isNaN(n) && Math.round(n) != n;
}

function popupFullscreen(url) {
    var myWin = window.open(url, '_blank', 'scrollbars=auto,fullscreen=1,resizeable=1,channelmode=1');
    myWin.focus();
}

function popupBlank(url) {
    var myWin = window.open(url, '_blank');
    myWin.focus();
}

function number_format(number, decimals, dec_point, thousands_sep) {
    var exponent = "";
    var numberstr = number.toString();
    var eindex = numberstr.indexOf("e");
    if (eindex > -1) {
        exponent = numberstr.substring(eindex);
        number = parseFloat(numberstr.substring(0, eindex));
    }

    if (decimals != null) {
        var temp = Math.pow(10, decimals);
        number = Math.round(number * temp) / temp;
    }
    var sign = number < 0 ? "-" : "";
    var integer = (number > 0 ?
        Math.floor(number) : Math.abs(Math.ceil(number))).toString();

    var fractional = number.toString().substring(integer.length + sign.length);
    dec_point = dec_point != null ? dec_point : ".";
    fractional = decimals != null && decimals > 0 || fractional.length > 1 ?
        (dec_point + fractional.substring(1)) : "";
    if (decimals != null && decimals > 0) {
        for (i = fractional.length - 1, z = decimals; i < z; ++i)
            fractional += "0";
    }

    thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ?
        thousands_sep : null;
    if (thousands_sep != null && thousands_sep != "") {
        for (i = integer.length - 3; i > 0; i -= 3)
            integer = integer.substring(0, i) + thousands_sep + integer.substring(i);
    }

    return sign + integer + fractional + exponent;
}

/**
 * Extrahiert den Namen einer Datei aus einer vollständigen Pfadangabe
 */
function basename(path) {
    return path.replace(/.*(\/|\\)/, '');
}

/**
 * Ermittelt die Dateiendung
 */
function file_extension(fileName) {
    return (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '') : '';
}

function isImageLoaded(img) {
    // W�hrend des onload Ereignis kann im IE gepr�ft werden, ob das
    // Laden des Bildes abgeschlossen wurde. Andere Browser k�nnen
    // das auch.
    // Gecko-basierte Browser liefern wie NS4 falsche Daten:
    // sie liefern immer true
    if (!img.complete) {
        return false;
    }

    // Wie auch immer, es gibt zwei sehr n�tzliche Eigenschaften: naturalWidth
    // und naturalHeight. Diese geben die tats�chliche Gr��e des Bildes wieder.
    // Wenn das Bild nicht geladen werden konnte, bleiben die Werte auf 0
    if (typeof img.naturalWidth != "undefined" && img.naturalWidth == 0) {
        return false;
    }

    // sollte passen
    return true;
}

function pix2int(px) {
    if (String(px).substr(-2) == 'px') {
        px = px.substring(0, px.length - 2);
    }
    return parseInt(px, 10);
}

function int2pix(px) {
    if (String(px).substr(-2) != 'px') {
        px = px + 'px';
    }
    return px;
}

/* DropDown, Select */
function addOption(selectElement, caption, value) {
    var optn = document.createElement('OPTION');
    optn.text = caption;
    optn.value = value;

    if (arguments[3]) {
        optn.selected = arguments[3];
    }

    try {
        selectElement.add(optn, null);
    } catch (e) {
        selectElement.add(optn);
    }
    return optn;
}

function clearSelect(selectElement) {
    for (let i = selectElement.options.length - 1; i >= 0; i--) {
        selectElement.options[i] = null;
    }
}

/**
 * Entfernt Leerraum vom Anfang eines Strings
 */
function ltrim(str) {
    return str.replace(new RegExp('^\\s+', ''), '');
}

/**
 * Entfernt Leerraum vom Ende eines Strings
 */
function rtrim(str) {
    return str.replace(new RegExp('\\s+$', ''), '');
    // oder so return str.replace(/\s+$/, '');
}

/**
 * Natural Sort algorithm for Javascript
 *
 * @version 0.3
 * @author Author: Jim Palmer (based on chunking idea from Dave Koelle)
 * @modified Alexander Manhart, damit sie mit dhtmlx und anderen Sortfunktionen prototype funktioniert
 * @link http://www.overset.com/2008/09/01/javascript-natural-sort-algorithm/
 */
function str_natsort(a, b, order) {
    var retval = 0;

    var isDesc = (order == 'desc' || order == 'des');
    // die einfache Version reichte nicht aus.
    // return (a.toLowerCase()>b.toLowerCase()?1:-1)*(order=="asc"?1:-1);

    // setup temp-scope variables for comparison evauluation
    var re = /(-?[0-9\.]+)/g,
        x = a.toString().toLowerCase() || '',
        y = b.toString().toLowerCase() || '',
        nC = String.fromCharCode(0),
        xN = x.replace(re, nC + '$1' + nC).split(nC),
        yN = y.replace(re, nC + '$1' + nC).split(nC),
        xD = (new Date(x)).getTime(),
        yD = xD ? (new Date(y)).getTime() : null;

    // natural sorting of dates
    if (yD) {
        if (xD < yD)
            retval = -1;
        else if (xD > yD)
            retval = 1;
        if (isDesc) retval = retval * -1;
        return retval;
    }

    // log('sortierung: a="'+a+'" b="'+b+'" Reihenfolge: '+order);

    // natural sorting through split numeric strings and default strings
    for (var cLoc = 0, numS = Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
        oFxNcL = parseFloat(xN[cLoc]) || xN[cLoc];
        oFyNcL = parseFloat(yN[cLoc]) || yN[cLoc];
        // log('oFxNcL: '+oFxNcL+' oFyNcL: '+oFyNcL);
        if (oFxNcL < oFyNcL) {
            retval = -1;
            // log('retval: '+retval);
            break;
        } else if (oFxNcL > oFyNcL) {
            retval = 1;
            // log('retval: '+retval);
            break;
        }
        // log('retval: '+retval);
    }
    if (isDesc) retval = retval * -1;
    // log('ergebnis retval: '+retval);

    return retval;
}

/**
 * Natural Sort algorithm for Javascript - Deutsche Version
 *
 * @deprecated
 * @version 0.3
 * @author Author: Jim Palmer (based on chunking idea from Dave Koelle)
 * @modified Alexander Manhart, damit sie mit dhtmlx und anderen Sortfunktionen prototype funktioniert
 * @link http://www.overset.com/2008/09/01/javascript-natural-sort-algorithm-with-unicode-support/
 */
function str_de_natsort(a, b, order) {
    var retval = 0;

    var isDesc = (order == 'desc' || order == 'des');
    // die einfache Version reichte nicht aus.
    // return (a.toLowerCase()>b.toLowerCase()?1:-1)*(order=="asc"?1:-1);

    // setup temp-scope variables for comparison evauluation
    var re = /(-?[0-9\.]+)/g,
        x = a.toString().toLowerCase() || '',
        y = b.toString().toLowerCase() || '',
        nC = String.fromCharCode(0),
        xN = x.replace(re, nC + '$1' + nC).split(nC),
        yN = y.replace(re, nC + '$1' + nC).split(nC),
        xD = (new Date(x)).getTime(),
        yD = xD ? (new Date(y)).getTime() : null;

    // natural sorting of dates
    if (yD) {
        if (xD < yD)
            retval = -1;
        else if (xD > yD)
            retval = 1;
        if (isDesc) retval = retval * -1;
        return retval;
    }

    // log('sortierung: a="'+a+'" b="'+b+'" Reihenfolge: '+order);

    // natural sorting through split numeric strings and default strings
    for (var cLoc = 0, numS = Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
        if (isGermanFloat(xN[cLoc])) {
            xN[cLoc] = xN[cLoc].replace('.', '').replace(',', '.'); // tausender Trenner entfernen
        }
        if (isGermanFloat(yN[cLoc])) {
            yN[cLoc] = yN[cLoc].replace('.', '').replace(',', '.'); // tausender Trenner entfernen
        }
        oFxNcL = parseFloat(xN[cLoc]) || xN[cLoc];
        oFyNcL = parseFloat(yN[cLoc]) || yN[cLoc];
        // log('oFxNcL: '+oFxNcL+' oFyNcL: '+oFyNcL);
        if (oFxNcL < oFyNcL) {
            retval = -1;
            // log('retval: '+retval);
            break;
        } else if (oFxNcL > oFyNcL) {
            retval = 1;
            // log('retval: '+retval);
            break;
        }
        // log('retval: '+retval);
    }
    if (isDesc) retval = retval * -1;
    // log('ergebnis retval: '+retval);

    return retval;
}

/**
 * Wandelt das erste Zeichen von str in einen Gro�buchstaben um
 */
function ucfirst(str) {
    if (str.length > 0) {
        str = str.charAt(0).toUpperCase() + str.substr(1);
    }
    return str;
}

/**
 * Ruft eine Funktion auf und bewirkt dass eventuelle Ereignisse/Nachrichten vorher abgearbeitet werden.
 */
function processFunction(func, callback, interval) {
    if (typeof interval != 'number') interval = 0;

    window.setTimeout(function () {
        var result = false;
        try {
            if (typeof func == 'string') {
                eval('result=' + func);
            } else if (typeof func == 'function') {
                var f = func;
                result = f();
            }
        } finally {
            if (typeof callback == 'function') {
                var f = callback;
                f(result);
            }
        }
    }, interval);
}

/**
 * E-Mail Validierung nach RFC 2822
 */
function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[öäßüa-z0-9](?:[öäßüa-z0-9-]*[öäßüa-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/);
    return pattern.test(emailAddress);
}

/**
 * Sortierfunktion Integer vorzeichenlos
 */
function int_unsigned_sort(a, b, order) {
    a = Math.abs(parseInt(a, 10));
    if (isNaN(a)) a = 0;
    b = Math.abs(parseInt(b, 10));
    if (isNaN(b)) b = 0;

    var retval = 0;
    if (a > b) {
        retval = 1;
    } else if (a < b) {
        retval = -1;
    }
    if (order != 'asc') retval = retval * -1;
    return retval;
}

/**
 * Vererbung von Klassen/Objekten
 */
Function.prototype.inheritsFrom = function (parentClassOrObject) {
    if (parentClassOrObject.constructor == Function) {
        // Normal Inheritance
        this.prototype = Object.create(parentClassOrObject.prototype);
        this.prototype.constructor = this;
        this.prototype.superclass = parentClassOrObject.prototype;
    } else {
        // Pure Virtual Inheritance
        this.prototype = parentClassOrObject;
        this.prototype.constructor = this;
        this.prototype.superclass = parentClassOrObject;
    }
    return this;
}

/**
 * Formatiere Minuten um als Stunde-Minuten Text
 *
 * @param int Minuten
 * @return string
 */
function formatStdMin(value) {
    var value = parseInt(value);
    return Math.floor(value / 60) + ' Std. ' + (value % 60) + ' Min.';
}

/**
 * Formatiere Minuten in 24 Stunden Format
 *
 * @param int Minuten
 * @returns string
 */
function format24h(min) {
    var value = parseInt(min);
    return Math.floor(value / 60) + ':' + (value % 60);
}


/**
 * Converts string literal true and 1 to boolean true. Everything else becomes false
 */
function string2bool(val) {
    return (String(val) === 'true' || val === '1');
}

/**
 * Laedt Datei herunter (IE8 kompatibel!)
 */
function downloadFile(file) {
    let ts = new Date().getTime(); // Timestamp

    let link = document.createElement('a');

    link.setAttribute('id', 'tmpdownload' + ts);
    link.setAttribute('type', 'application/octet-stream');

    let isIE = /*@cc_on!@*/false;
    if (isIE) {
        link.target = '_blank';
        document.body.appendChild(link);
    }

    link.href = file + '?' + ts;

    // HTML 5
    link.download = basename(file);

    // alle modernen Browser (IE ab 9er)
    try {
        // funktioniert leider nicht im Safari unter Windows
        let ClickEvent = new MouseEvent('click', {
            view: window,
            bubbles: true,
            cancelable: false
        });

        link.target = '_blank';
        link.dispatchEvent(ClickEvent);
    }
    catch(e) {
        if (link.click) {
            link.click();
        }
    }

    if (isIE) {
        document.body.removeChild(link);
    }
}

/**
 * Wandelt base64 in Blob um
 *
 * @param data base64 kodierte Daten
 * @param contentType z.B. 'application/pdf';
 */
function base64ToBlob(data, contentType) {
    if (!window.atob) {
        // benoetigt base64.js
        var byteChars = Base64.decode(data);
    } else {
        var byteChars = window.atob(data);
    }

    byteNumbers = new Array(byteChars.length);

    for (var i = 0; i < byteChars.length; i++) {
        byteNumbers[i] = byteChars.charCodeAt(i);
    }

    // < IE 10 benoetigt typedarray.js
    var byteArray = new Uint8Array(byteNumbers);

    try {
        // < IE 10 benoetigt Blob.js
        var blob = new Blob([byteArray], {type: contentType});
    }
    catch (e) {
        // TypeError old chrome and FF
        window.BlobBuilder = window.BlobBuilder ||
            window.WebKitBlobBuilder ||
            window.MozBlobBuilder ||
            window.MSBlobBuilder;
        if (e.name == 'TypeError' && window.BlobBuilder) {
            var bb = new BlobBuilder();
            bb.append(byteArray);
            var blob = bb.getBlob(contentType);
        }
        else if (e.name == "InvalidStateError") {
            // InvalidStateError (tested on FF13 WinXP)
            var blob = new Blob([byteArray], {type: contentType});
        }
        else {
            // We're screwed, blob constructor unsupported entirely
        }
    }

    return blob;
}

/**
 * Extrahiere Domain einer URL
 */
function getDomainFromUrl(url) {
    var arr = url.split("/");
    return arr[0] + "//" + arr[2];
}

/**
 * Returns classname
 *
 * @param obj
 * @returns string
 */
function what(obj) {
    /*
     *  for browsers which have name property in the constructor
     *  of the object,such as chrome
     */
    if (obj && typeof obj === 'object' && obj.constructor) {
        if (obj.constructor.name) {
            return obj.constructor.name;
        }

        var str = obj.constructor.toString();

        /*
         * executed if the return of object.constructor.toString() is
         * "[object objectClass]"
         */
        if (str.charAt(0) == '[') {
            var arr = str.match(/\[\w+\s*(\w+)\]/);
        } else {
            /*
             * executed if the return of object.constructor.toString() is
             * "function objectClass () {}"
             * for IE Firefox
             */
            var arr = str.match(/function\s*(\w+)/);
        }
        if (arr && arr.length == 2) {
            return arr[1];
        }
    }
    return undefined;
}

/**
 * Camelize a string, cutting the string by multiple separators like
 * hyphens, underscores and spaces.
 * @see https://ourcodeworld.com/articles/read/608/how-to-camelize-and-decamelize-strings-in-javascript
 *
 * @param {text} string Text to camelize
 * @return string Camelized text
 */
function camelize(text) {
    return text.replace(/^([A-Z])|[\s-_]+(\w)/g, function (match, p1, p2, offset) {
        if (p2) return p2.toUpperCase();
        return p1.toLowerCase();
    });
}

/**
 * decamelize a string with/without a custom separator (underscore by default).
 * @see https://ourcodeworld.com/articles/read/608/how-to-camelize-and-decamelize-strings-in-javascript
 *
 * @param str String in camelcase
 * @param separator Separator for the new decamelized string.
 */
function decamelize(str, separator = '-') {
    return str.split(/(?=[A-Z])/).map(s => s.toLowerCase()).join(separator);
    // return str
    //     .replace(/([a-z\d])([A-Z])/g, '$1' + separator + '$2')
    //     .replace(/([A-Z]+)([A-Z][a-z\d]+)/g, '$1' + separator + '$2')
    //     .toLowerCase();
}

/**
 * UnknownClassException
 *
 * @param message
 * @constructor
 */
function UnknowClassException(message) {
    this.message = message;
    this.name = 'UnknownClassException';
}

/**
 * shorthand of document.getElementById
 * @param elementId
 */
getByID = document.getElementById.bind(document);

/**
 * Calls function (fn) after DOM content is loaded
 *
 * @param function fn
 * @see http://youmightnotneedjquery.com/#ready
 */
function ready(fn) {
    if (document.readyState === 'complete' ||
        (document.readyState !== 'loading' && !document.documentElement.doScroll)) {
        fn();
    } else if (document.addEventListener) {
        // IE9+
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        // older IE versions
        document.attachEvent('onreadystatechange', function () {
            if (document.readyState != 'loading') {
                // remove the listener, to make sure it isn't fired in future
                document.detachEvent("onreadystatechange", arguments.callee);
                fn();
            }
        });
    }
}

/**
 *
 * @param glue
 * @param pieces
 * @returns {string}
 */
function implode(glue, pieces) {
    let fixedImplode = '';

    for (x = 0; x < pieces.length; x++) {
        fixedImplode += (glue + String(pieces[x]));
    }

    fixedImplode = fixedImplode.substring(glue.length, fixedImplode.length);
    return fixedImplode;
}

/**
 *
 * @param separators
 * @param inputstring
 * @param includeEmpties
 * @returns {*[]}
 */
function explode(separators, inputstring, includeEmpties) {
    inputstring = new String(inputstring);
    separators = new String(separators);
    if (separators == "undefined") {
        separators = " :;";
    }
    fixedExplode = new Array();
    currentElement = "";
    count = 0;
    for(x = 0; x < inputstring.length; x++) {
        var charX = inputstring.charAt(x);
        if (separators.indexOf(charX) != -1) {
            if (((includeEmpties <= 0) || (includeEmpties == false)) && (currentElement == "")) {
            }
            else {
                fixedExplode[count] = currentElement;
                count++;
                currentElement = "";
            }
        }
        else {
            currentElement += charX;
        }
    }
    if ((!(includeEmpties <= 0) && (includeEmpties != false)) || (currentElement != "")) {
        fixedExplode[count] = currentElement;
    }
    return fixedExplode;
}

/**
 * fill controls (input fields) with content
 *
 * @param {string|object|array} containerSelector
 * @param {array|object} rowSet record which should be filled into controls
 * @param {boolean} autoSearchControlsWithinContainer should be set to true, if only a container is passed
 * @returns {array}
 */
function fillControls(containerSelector, rowSet, autoSearchControlsWithinContainer = true) {
    if (!Array.isArray(rowSet) && !isObject(rowSet)) {
        return [];
    }

    if (rowSet[0] === undefined) {
        rowSet = [rowSet];
    }

    let selectors;
    let controls = [];
    let hasControls = false;
    if (isString(containerSelector)) {
        if (autoSearchControlsWithinContainer) {
            // automatically search controls within container and rowset fields (very old method)
            selectors = explode(',', containerSelector, false);
        }
        else {
            // controls selector
            controls = jQuery(containerSelector);
        }
    }
    else if (Array.isArray(containerSelector) || isObject(containerSelector)) {
        controls = jQuery(containerSelector);
        hasControls = true;
    }

    let row, field, value, attr, descriptor;
    let r = 0;

    // Zeile fuer Zeile durch das Rowset
    for (r = 0; r < rowSet.length; r++) {
        row = rowSet[r];

        // Feld fuer Feld ermitteln wir die HTML-Elemente
        for (field in row) {

            // Felder mit dem Namen/Suffix _class dienen zur Zuweisung von Styles und sind keine Felder
            if (field.substr(field.length - 6) === '_class') continue;
            // Felder mit dem Namen/Suffix _title dienen zur Zuweisung von Titles/ToolTips und sind keine Felder
            if (field.substr(field.length - 6) === '_title') continue;

            // Wert
            value = row[field];

            // set attributes of an HTML element
            attr = null;
            if(field.includes(':')) {
                let fieldArray = field.split(':')
                field = fieldArray[0];
                attr = fieldArray[1];
                if(fieldArray.length > 2) {
                    descriptor = fieldArray[2];
                }
            }

            // 28.01.2022, AM, special case: use formatted value if exists
            if(field+'_pool_use_formatted' in row && row[field+'_pool_use_formatted']) {
                value = row[field + '_pool_formatted']; // we prefer the formatted value of bs-table @see GUI_Table::strftime
            }


            // 21.01.2013, AM, Acceleration of the selection via unique IDs (ID-Selector)
            // 07.07.2021, AM, Group-Selector added
            if (autoSearchControlsWithinContainer) {
                let name_selector = '', id_selector = '', group_selector = '';
                for (let s = 0; s < selectors.length; s++) {
                    if (name_selector != '') name_selector += ',';
                    name_selector += selectors[s] + ' [name=' + field + ']';
                    if (id_selector != '') id_selector += ',';
                    id_selector += selectors[s] + ' #' + field;
                    if (group_selector != '') group_selector += ',';
                    group_selector += selectors[s] + ' [name="' + field + '[]"][value="' + escape(value) + '"]';
                }

                controls = jQuery(name_selector).add(group_selector).add(id_selector);
            }

            controls.each(function () {
                let Ctrl = jQuery(this);

                // todo 20.12.21, AM, rework fillControls, first loop over controls!
                if (autoSearchControlsWithinContainer == false || hasControls) {
                    if (Ctrl.attr('name') != field && Ctrl.attr('id') != field && !(Ctrl.attr('name') == field + '[]' && Ctrl.val() == value)) return;
                }

                //log('HTMLElement: '+Ctrl.attr('id')+'='+value);
                if (row[field + '_class']) Ctrl.addClass(row[field + '_class']);
                if (row[field + '_title']) Ctrl.attr('title', row[field + '_title']);

                // console.debug('fillControls loop over', Ctrl[0].tagName, Ctrl.attr('id'), Ctrl.attr('name'), value);


                if (r == rowSet.length - 1) {
                    if (attr) {
                        // console.debug(field, attr, descriptor, value);
                        switch (attr) {
                            case 'data':
                                // console.debug(Ctrl, 'data', descriptor, value);
                                descriptor = decamelize(descriptor);
                                Ctrl.get(0).setAttribute('data-'+descriptor, value);
                                break;

                            default:
                                Ctrl.attr(attr, value);
                        }
                    }
                    else {
                        main_switch:
                            switch (Ctrl[0].tagName) {
                                case 'TEXTAREA':
                                    Ctrl.val(value);
                                    break;

                                case 'SPAN':
                                case 'DIV':
                                    Ctrl.html(value);
                                    break;

                                case 'IMG':
                                    if(isEmpty(value)) {
                                        Ctrl.hide();
                                    }
                                    else {
                                        Ctrl.attr('src', value);
                                        Ctrl.show();
                                    }
                                    break;

                                case 'INPUT':
                                    // Checkbox mit 3 Statis
                                    if(Ctrl.data('tri-state-checkbox')) {
                                        // TODO implementierung in jquery
                                        var possibleValues = explode(',', Ctrl.data('possible-values'));
                                        var img = jQuery('#tri-state-checkbox-' + Ctrl.attr('id'));
                                        switch(value) {
                                            case null:
                                            case possibleValues[0]:
                                                img.removeClass().addClass('tri-state-checkbox').addClass('checked-partial');
                                                break;

                                            case possibleValues[1]:
                                                img.removeClass().addClass('tri-state-checkbox').addClass('checked-full');
                                                break;

                                            case possibleValues[2]:
                                                img.removeClass().addClass('tri-state-checkbox').addClass('checked-none');
                                                break;
                                        }
                                        Ctrl.val(value);
                                        break;
                                    }

                                    switch(Ctrl.attr('type')) {
                                        case 'checkbox':
                                        case 'radio':
                                            Ctrl.prop('checked', (value == Ctrl.val()));
                                            break main_switch;

                                        // default:
                                        //     Ctrl.val(value);
                                    }

                                default:
                                    Ctrl.val(value);
                                    if(Ctrl.data('initialValue') == undefined) {
                                        Ctrl.data('initialValue', value);
                                    }

                                    // bootstrap-select support
                                    if(Ctrl.hasClass('selectpicker')) {
                                        Ctrl.selectpicker('refresh');
                                    }

                                    // bootstrap-datetimepicker support v5
                                    // if (Ctrl.hasClass('datetimepicker-input')) {
                                    //     console.debug('trigger(change)');
                                    //     Ctrl.trigger('change');
                                    // }

                                    // bootstrap-datetimepicker support v6 (maybe it works automatically
                                    // if(Ctrl.data('data-td-target')) {
                                    //     Ctrl.trigger('change');
                                    // }

                                    break;
                          }
                    }
                }
                // else {
                // if(value != rowSet[rowSet.length-1][field]) {
                //     // Werte werden aufaddiert; TODO String u. Int/Double Unterscheidung
                //     if(Ctrl.data('fill-sum')) {
                //         var shorten = Ctrl.data('fill-sum-shorten');
                //     }
                //     else {
                //         rowSet[rowSet.length-1][field] = null;
                //     }
                // }
                // }

                Ctrl[0].classList.remove('is-invalid');
                Ctrl[0].classList.remove('is-valid');
                if (Ctrl[0].closest('.needs-validation')) {
                    Ctrl[0].closest('.needs-validation').classList.remove('was-validated');
                }
            });
        }
    }
    return controls;
}

/**
 * Empties the contents of the elements
 *
 * @param {array|object|string} array of elements (input fields) or a selector
 */
function clearControls(elements) {
    if (isString(elements)) {
        elements = explode(',', elements, false);
        elements = document.querySelectorAll(elements);
    }

    for (let z = 0; z < elements.length; z++) {
        let elem = elements[z];
        // console.debug('clearControls', elem.name);

        let tagName = elem.tagName.toUpperCase();
        let elemType = (elem.type) ? elem.type.toUpperCase() : '';
        // console.debug(tagName, elemType, elem.name);
        if (tagName == 'SPAN') {
            elem.innerHTML = (elem.getAttribute('data-default-value') != null) ? elem.getAttribute('data-default-value') : '';
        }
        else if (elemType == 'CHECKBOX' || elemType == 'RADIO') {
            // console.debug('checked', elem.dataset.defaultChecked);
            if (elem.getAttribute('data-default-checked') != null) {
                // console.debug('element checked');
                elem.checked = string2bool(elem.dataset.defaultChecked);
            }
            else elem.checked = false;
        }
        else if (elemType == 'SELECT-ONE' || elemType == 'SELECT-MULTIPLE') {

            if (elem.hasAttribute('data-default-value')) {
                // https://developer.mozilla.org/en-US/docs/Web/API/Element/getAttribute#non-existing_attributes
                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Nullish_coalescing_operator
                elem.options.selectedIndex = -1;
                elem.value = elem.getAttribute('data-default-value') ?? '';

                // 04.01.22, AM, selectpicker support
                if (elem.classList.contains('selectpicker')) {
                    jQuery(elem).selectpicker('refresh');
                }

            }
            else {
                elem.options.selectedIndex = 0;
            }
        }
        else {
            if (elem.getAttribute('data-empty-default-value')) {
                elem.setAttribute('data-default-value', null);
            }
            elem.value = (elem.getAttribute('data-default-value') != null) ? elem.getAttribute('data-default-value') : '';
        }

        elem.classList.remove('is-invalid');
        elem.classList.remove('is-valid');
        if (elem.closest('.needs-validation')) {
            elem.closest('.needs-validation').classList.remove('was-validated');
        }
    }
}

/**
 * Modifies the provided hidden input so value changes to trigger events.
 *
 * After this method is called, any changes to the 'value' property of the
 * specified input will trigger a 'change' event, just like would happen
 * if the input was a text field.
 *
 * As explained in the following SO post, hidden inputs don't normally
 * trigger on-change events because the 'blur' event is responsible for
 * triggering a change event, and hidden inputs aren't focusable by virtue
 * of being hidden elements:
 * https://stackoverflow.com/a/17695525/4342230
 *
 * @param {HTMLInputElement} inputElement
 *   The DOM element for the hidden input element.
 */
function setupHiddenInputChangeListener(inputElement) {
    const propertyName = 'value';

    const {get: originalGetter, set: originalSetter} =
        findPropertyDescriptor(inputElement, propertyName);

    // We wrap this in a function factory to bind the getter and setter values
    // so later callbacks refer to the correct object, in case we use this
    // method on more than one hidden input element.
    const newPropertyDescriptor = ((_originalGetter, _originalSetter) => {
        return {
            set: function (value) {
                const currentValue = originalGetter.call(inputElement);

                // Delegate the call to the original property setter
                _originalSetter.call(inputElement, value);

                // Only fire change if the value actually changed.
                if (currentValue !== value) {
                    inputElement.dispatchEvent(new Event('change'));
                }
            },

            get: function () {
                // Delegate the call to the original property getter
                return _originalGetter.call(inputElement);
            }
        }
    })(originalGetter, originalSetter);

    Object.defineProperty(inputElement, propertyName, newPropertyDescriptor);
};

/**
 * Search the inheritance tree of an object for a property descriptor.
 *
 * The property descriptor defined nearest in the inheritance hierarchy to
 * the class of the given object is returned first.
 *
 * Credit for this approach:
 * https://stackoverflow.com/a/38802602/4342230
 *
 * @param {Object} object
 * @param {String} propertyName
 *   The name of the property for which a descriptor is desired.
 *
 * @returns {PropertyDescriptor, null}
 */
function findPropertyDescriptor(object, propertyName) {
    if (object === null) {
        return null;
    }

    if (object.hasOwnProperty(propertyName)) {
        return Object.getOwnPropertyDescriptor(object, propertyName);
    } else {
        const parentClass = Object.getPrototypeOf(object);

        return findPropertyDescriptor(parentClass, propertyName);
    }
}

/**
 * Trigger an event
 *
 * @param element Element
 * @param type Event Type
 */
function triggerEvent(element, type) {
    if ('createEvent' in document) {
        // modern browsers, IE9+
        let Event = document.createEvent('HTMLEvents');
        Event.initEvent(type, false, true);
        element.dispatchEvent(Event);
    } else {
        // IE 8
        let Event = document.createEventObject();
        Event.eventType = type;
        element.fireEvent('on' + Event.eventType, Event);
    }
}

/**
 * Load json file synchronously
 *
 * @param string url
 * @param object opts e.g. headers
 * @returns {null|any}
 */
function loadJSON(url, opts = {}) {
    let xhr = new XMLHttpRequest();
    xhr.overrideMimeType('application/json');
    xhr.open('GET', url, false);

    if (opts.headers) {
        for (let key in opts.headers) {
            xhr.setRequestHeader(key, opts.headers[key]);
        }
    }

    xhr.send();
    if (xhr.status == 200) {
        return JSON.parse(xhr.responseText);
    } else {
        return null;
    }
}

/**
 * check if string is a json
 *
 * @param str
 * @return {boolean}
 */
function isJsonString(str)
{
    try {
        let json = JSON.parse(str);
        return (typeof json === 'object');
    }
    catch (e) {
        return false;
    }
}

/**
 * remove an item (e.g. object) from array
 *
 * @param items
 * @param rejectedItem
 * @returns {*[]}
 */
function without(items = [], rejectedItem) {
    return items.filter(function (item) {
        return item !== rejectedItem;
    }).map(function (item) {
        return item;
    });
}

/**
 * Equivalent to PHP's htmlspecialchars_decode
 *
 * @see https://locutus.io/php/htmlspecialchars_decode/
 * @param string
 * @param quoteStyle
 * @returns {string}
 */
function htmlspecialchars_decode(string, quoteStyle) {
    let optTemp = 0
    let i = 0
    let noquotes = false
    if (typeof quoteStyle === 'undefined') {
        quoteStyle = 2
    }
    string = string.toString()
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
    const OPTS = {
        ENT_NOQUOTES: 0,
        ENT_HTML_QUOTE_SINGLE: 1,
        ENT_HTML_QUOTE_DOUBLE: 2,
        ENT_COMPAT: 2,
        ENT_QUOTES: 3,
        ENT_IGNORE: 4
    }
    if (quoteStyle === 0) {
        noquotes = true
    }
    if (typeof quoteStyle !== 'number') {
        // Allow for a single string or an array of string flags
        quoteStyle = [].concat(quoteStyle)
        for (i = 0; i < quoteStyle.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quoteStyle[i]] === 0) {
                noquotes = true
            } else if (OPTS[quoteStyle[i]]) {
                optTemp = optTemp | OPTS[quoteStyle[i]]
            }
        }
        quoteStyle = optTemp
    }
    if (quoteStyle & OPTS.ENT_HTML_QUOTE_SINGLE) {
        // PHP doesn't currently escape if more than one 0, but it should:
        string = string.replace(/&#0*39;/g, "'")
        // This would also be useful here, but not a part of PHP:
        // string = string.replace(/&apos;|&#x0*27;/g, "'");
    }
    if (!noquotes) {
        string = string.replace(/&quot;/g, '"')
    }
    // Put this in last place to avoid escape being double-decoded
    string = string.replace(/&amp;/g, '&')
    return string
}

/**
 * Equivalent to PHP's htmlspecialchars
 *
 * @see https://locutus.io/php/htmlspecialchars/
 * @param string
 * @param quoteStyle
 * @param charset
 * @param doubleEncode
 * @returns {string}
 */
function htmlspecialchars(string, quoteStyle, charset, doubleEncode) {
    let optTemp = 0
    let i = 0
    let noquotes = false
    if (typeof quoteStyle === 'undefined' || quoteStyle === null) {
        quoteStyle = 2
    }
    string = string || ''
    string = string.toString()
    if (doubleEncode !== false) {
        // Put this first to avoid double-encoding
        string = string.replace(/&/g, '&amp;')
    }
    string = string
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
    const OPTS = {
        ENT_NOQUOTES: 0,
        ENT_HTML_QUOTE_SINGLE: 1,
        ENT_HTML_QUOTE_DOUBLE: 2,
        ENT_COMPAT: 2,
        ENT_QUOTES: 3,
        ENT_IGNORE: 4
    }
    if (quoteStyle === 0) {
        noquotes = true
    }
    if (typeof quoteStyle !== 'number') {
        // Allow for a single string or an array of string flags
        quoteStyle = [].concat(quoteStyle)
        for (i = 0; i < quoteStyle.length; i++) {
            // Resolve string input to bitwise e.g. 'ENT_IGNORE' becomes 4
            if (OPTS[quoteStyle[i]] === 0) {
                noquotes = true
            } else if (OPTS[quoteStyle[i]]) {
                optTemp = optTemp | OPTS[quoteStyle[i]]
            }
        }
        quoteStyle = optTemp
    }
    if (quoteStyle & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, '&#039;')
    }
    if (!noquotes) {
        string = string.replace(/"/g, '&quot;')
    }
    return string
}

/**
 * check if an element is visible in the viewport
 *
 * @see https://www.javascripttutorial.net/dom/css/check-if-an-element-is-visible-in-the-viewport/
 * @param element
 * @returns {boolean}
 */
function isElementInViewport(element)
{
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * compares arrays if they are equal without considering the order
 *
 * @param a
 * @param b
 * @return {boolean}
 */
const areArraysEqual = (a, b) =>
{
    if (a.length !== b.length) return false;
    const uniqueValues = new Set([...a, ...b]);
    for (const v of uniqueValues) {
        const aCount = a.filter(e => e === v).length;
        const bCount = b.filter(e => e === v).length;
        if (aCount !== bCount) return false;
    }
    return true;
}

/**
 * compares objects if they are equal
 * @param obj1
 * @param obj2
 * @return {boolean}
 */
const areObjectsEqual = (obj1, obj2) =>
{
    return JSON.stringify(obj1) === JSON.stringify(obj2);
}

/**
 * @see https://stackoverflow.com/questions/30106476/using-javascripts-atob-to-decode-base64-doesnt-properly-decode-utf-8-strings
 * @param str
 * @return {string}
 */
const b64DecodeUnicode = (str) =>
{
    // Going backwards: from bytestream, to percent-encoding, to original string.
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}