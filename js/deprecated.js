/*
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * deprecated.js created at 11.01.22, 14:08
 *
 * @author A.Manhart <alexander@manhart-it.de>
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

// wahr, wenn die E-mail ohne Realname als gütig eingestuft wurde
// @deprecated
function checkEmailAdr(address)
{
    var status = true;
    var username = "";
    var hostname = "";

    if (address.length < 8)
        return false;

    var seperate = address.lastIndexOf("@");
    if (seperate == -1)
        return false;

    username = address.substring(0, seperate );
    if (!checkUsername(username, "<>()[],;:@\" " ))
        return false;

    hostname = address.substring(seperate+1, address.length);
    if (!checkHostname(hostname))
        return false;

    return true;
}

// wahr, wenn die E-Mail Adresse als gütig eingestuft wurde, wobei der zweite Parameter festlegt, ob Realname akzeptiert werden oder nicht
// @deprecated
function checkEmail(email, allowFullname)
{
    var existFullname = false;
    var status = true;
    var fullname = "";
    var adress = "";
    if (email.length < 8)
        return false;
    var emailBegin = email.indexOf("<");
    var emailEnd = email.lastIndexOf(">");

    if ((emailBegin == -1) && (emailEnd == -1))
        return checkEmailAdr( email );

    if ( ( (emailBegin == -1) && (emailEnd != -1) )
        || ( (emailBegin != -1) && (emailEnd == -1) ) )
        return false;

    adress = email.substring(emailBegin+1, emailEnd);

    if (! checkEmailAdr( adress ))
        return false;

    if ( email.length == adress.length + 2 )
        return true;
    else
    if (!allowFullname)
        return false;

    if (emailEnd == email.length - 1) {
        if ( emailBegin == 0 )
            return true;
        if ( email.charAt( emailBegin -1 ) != ' ' )
            return false;
        fullname = email.substring( 0, emailBegin-1 );
        return checkUsername ( fullname, "<>()[],;:@\"" );
    }

    return false ;
}

function submitOnEnterkey(myfield, e)
{
    var e = e || window.event;
    var key = (e.keyCode) ? e.keyCode : e.which;
    //var key = (window.Event) ? e.which : e.keyCode;

    if (key == 13) {
        //alert('enter');
        //alert(myfield.form.name);
        myfield.form.submit(e);
        return false;
    }
    else {
        return true;
    }
}

function submitOnEnterkeyByForm(formname, e)
{
    var e = e || window.event;
    var key = (e.keyCode) ? e.keyCode : e.which;
    //var key = (window.Event) ? e.which : e.keyCode;

    if (key == 13) {
        document.forms[formname].submit(e);
        return false;
    }
    else {
        return true;
    }
}

function TextAttributesAndValues(ObjName,Object,NewLine)
{
    if(!NewLine) NewLine='\n';
    var Result="Attributes of the object \""+ObjName+"\" :\n\n";
    var Attribute;
    for (Attribute in Object) {
        if(isArray(Object)) {
            var wert=Object[Attribute];
        }
        else {
            eval("var wert=Object."+Attribute);
        }
        Result=Result+ObjName+"."+Attribute+"="+wert+NewLine;
    }
    return Result;
}

function submitForm(formname)
{
    if (document.forms[formname]) {
        document.forms[formname].submit();
    }
}


function callbackOnEnterKey(sender, callback, e)
{
    var e = e || window.event;
    var key = (e.keyCode) ? e.keyCode : e.which;

    if (key == 13) {
        switch(typeof callback) {
            case 'function': callback(e, sender);
                break;

            default:
                eval(callback);
        }
    }
}

// wahr, wenn der Username einer Mail g�ltig ist
function checkUsername( username, mustBeQuoted )
{
    var i = 0;
    var j = username.length;
    if ( username.charAt(0) != '"' ) {
        if ( (username.charAt(0) <  ' ') || (username.charAt(0) >  '~')
            || !notIn( mustBeQuoted, username.charAt(0) ) )
            return false;
        for( i=1; i<j; i++ ) {
            if ( ( (username.charAt(i) < ' ') || (username.charAt(i) >  '~')
                    || !notIn ( mustBeQuoted, username.charAt(i) ) )
                && ( username.charAt(i-1) != '\\' ) )
                return false;
        }
    }
    else {
        if ( username.charAt( j-1 ) != '"' )
            return false;
        for( i=1; i<j-1; i++ ) {
            if ( ( (username.charAt(i) == '\n') || (username.charAt(i) == '\r')
                    || (username.charAt(i) == '\"') )
                && (username.charAt(i-1) != '\\') )
                return false;
        }
    }
    return true;
}

function dateDEtoUSA(dateDE) {
    var datum = /\b(0?[1-9]|[12][0-9]|3[01])\.(0?[1-9]|1[0-2])\.(\d?\d?\d\d)\b/
    if (datum.test(dateDE))
        return dateDE.replace(datum, "$3-$2-$1");
    else
        return dateDE;
}

function getWeekdayDE(DE_date)
{
    var datumParts = DE_date.split('.');
    var ts = new Date(datumParts[2], parseInt(datumParts[1], 10)-1, parseInt(datumParts[0], 10));
    var wd = ts.getDay();
    if(wd == 0) wd=7;
    return wd;
}

/** Test for date.js **/
/*	alert(datetime_de_sort('07.06.2010 16:00', '06.06.2010 15:00', 'asc'));

//var start=new Date();
// var t = new Date();
// alert(t.getMonday());

var d=16, m=7, j=2008;

var date = new Date(0);
var summertime_in_h = (((new Date(j, m-1, d).getTimezoneOffset())-(new Date(j, 0, 1).getTimezoneOffset())))*-1/60;
var date = new Date(j, m-1, d, date.getHours()+summertime_in_h, 0, 0);

			var h = new Date(0); h=h.getHours();




//alert();



var calc = ((Number(date)+(3*24*60*60*1000))%7)+1;
var new_date = new Date(Number(date)-(date.getDay()*24*60*60*1000)+24*60*60*1000);
//var calc = ((Number(date)-(3*24*60*60*1000))%7)+1;
alert(date.toString()+' Timestamp:'+Number(date)+' '+calc + ' getDay:'+date.getDay()+' Mo.getDay:'+new_date.getDay());
//alert(weekOfMonth(date));



function weekOfMonth(date)
{
  if(!date) return null;
  return parseInt((date.getDate() - 1) / 7 + 1);
}


// Test getWeekNumber
for(var j=1965; j<=2008; j++) {
	document.write('<b>'+j+'</b><br>');
	for(var m=0; m<=4; m++) {
		for(var i=1; i<=31; i++) {
			var date = new Date(j, m-1, i);
			document.write('<font color="navy">'+date.strftime('%d.%m.%Y %H:%M')+'</font><br>');
			document.write('Montag: '+date.getMonday().strftime('%d.%m.%Y %H:%M')+' ');
			document.write('Donnerstag: '+new Date(date.getFullYear(), 0, 1).getThursday().strftime('%d.%m.%Y %H:%M')+' ');
			var kw = date.getWeekNumber();
			document.write('KW: '+kw.toPaddedString(2)+' ('+kw+')<br>');
		}
		document.write('<br>');
	}
	document.write('<hr style="height:1px">');
}


for(var j=1965; j<=2008; j++) {
	document.write('<b>'+j+'</b><br>');
	var d=22;
	var m=6;
	var date = new Date(j, m-1, d);
	document.write('Die KW1 beginnt am '+date.getFirstWeek().strftime('%d.%m.%Y'));
	document.write('<br>');
}


for(var j=1965; j<=2008; j++) {
	document.write('<b>'+j+'</b><br>');
	var d=22;
	var m=6;
	var date = new Date(j, m-1, d);
	document.write('Die KW'+date.getLastWeek().strftime('%V')+' ended am '+date.getLastWeek().getSunday().strftime('%d.%m.%Y'));
	document.write('<br>');
	document.write('getNumberOfDaysOfYear:'+date.getNumberOfDaysOfYear());
	document.write('<br>');
}


var ende=new Date();
document.write('<hr>Zeit f�r Berechnung: '+(Number(ende)-Number(start))+' ms');

//var date = new Date(1970, 0, 1);
//document.write('Tage:'+date.getDays());
*/

/**
 * Entfernt Whitespaces am Anfang und Ende des Strings
 */
function trim(str) {
    return ltrim(rtrim(str));
}


function stopBrowserLoading()
{
    if(window.stop !== undefined) {
        window.stop();
    }
    else document.execCommand('Stop', false);
}


/**
 * Programmverzögerung
 */
function sleep(milliseconds)
{
    var start = new Date().getTime();
    while((new Date().getTime() - start) < milliseconds) {
    }
}

/**
 * Falls Object.create nicht existiert wie im IE8 (Pseudo-Funktion)
 */
if (!Object.create) {
    Object.create = (function(){
        function F(){}

        return function(o){
            if (arguments.length != 1) {
                throw new Error('Object.create implementation only accepts one parameter.');
            }
            F.prototype = o;
            return new F()
        }
    })()
}

/**
 * Falls Object.keys nicht exisstiert wie im IE8/9
 */
// From https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
if (!Object.keys) {
    Object.keys = (function () {
        'use strict';
        var hasOwnProperty = Object.prototype.hasOwnProperty,
            hasDontEnumBug = !({toString: null}).propertyIsEnumerable('toString'),
            dontEnums = [
                'toString',
                'toLocaleString',
                'valueOf',
                'hasOwnProperty',
                'isPrototypeOf',
                'propertyIsEnumerable',
                'constructor'
            ],
            dontEnumsLength = dontEnums.length;

        return function (obj) {
            if (typeof obj !== 'object' && (typeof obj !== 'function' || obj === null)) {
                throw new TypeError('Object.keys called on non-object');
            }

            var result = [], prop, i;

            for (prop in obj) {
                if (hasOwnProperty.call(obj, prop)) {
                    result.push(prop);
                }
            }

            if (hasDontEnumBug) {
                for (i = 0; i < dontEnumsLength; i++) {
                    if (hasOwnProperty.call(obj, dontEnums[i])) {
                        result.push(dontEnums[i]);
                    }
                }
            }
            return result;
        };
    }());
}

function setValueById(elem_id, value)
{
    let elem = document.getElementById(elem_id);
    if (typeof elem == 'object') {
        elem.value = value;
    }
}

function hexval(c)
{
    if (String('0').charCodeAt(0) <= c && c <= String('9').charCodeAt(0))
        return c - String('0').charCodeAt(0);
    if (String('A').charCodeAt(0) <= c && c <= String('F').charCodeAt(0))
        return c - String('A').charCodeAt(0) + 10;
    if (String('a').charCodeAt(0) <= c && c <= String('f').charCodeAt(0))
        return c - String('a').charCodeAt(0) + 10;
    return 0;
}

function urlEncode(str)
{
    var result = "";
    var i = 0;

    for (i=0; i < str.length; i++) {
        result = result + "%";
        result = result + "0123456789ABCDEF".charAt((str.charCodeAt(i)/16)&0x0F);
        result = result + "0123456789ABCDEF".charAt((str.charCodeAt(i)/1)&0x0F);
    }
    return result;
}

function urlDecode(str)
{
    var result = new String("");
    var i=0;

    for (i=0; i < str.length; i++) {
        if (str.charAt(i) == '%' && i+3 <= str.length) {
            var foo = 0;
            i++;
            result = result +
                String.fromCharCode(hexval(str.charCodeAt(i)) * 16 + hexval(str.charCodeAt(i+1)));
            i++;

        }
        else {
            result  = result + str.charAt(i);
        }
    }
    return result;
}


function cloneObject(what) {
    for (i in what) {
        if (typeof what[i] == 'object') {
            this[i] = new cloneObject(what[i]);
        } else
            this[i] = what[i];
    }
}

function getNextElement(field) {
    var fieldFound = false;
    if (field.form) {
        var form = field.form;
        for (var e = 0; e < form.elements.length; e++) {
            if (fieldFound && form.elements[e].type != 'hidden') {
                break;
            }
            if (field == form.elements[e]) {
                fieldFound = true;
            }
        }
        var index = e % (form.elements.length);
        while (form.elements[index].type == 'hidden') {
            index++;
            if (form.elements.length == index) break;
        }
        return form.elements[index];
    } else {
        return false;
    }
}

function tabOnEnter(e) {
    var e = e || window.event;
    var key = (window.event) ? e.keyCode : e.which;
    var activeElement = (window.event) ? e.srcElement : e.target;

    if (key == 13) {
        if (activeElement && activeElement.type != 'textarea') {
            var nextElement = getNextElement(activeElement);
            if (nextElement) {
                while (!nextElement.focus && activeElement != nextElement) {
                    var nextElement = getNextElement(nextElement);
                }
                focusCtrl(nextElement);
                return false;
            }
        }
    }
    return true;
}

function tabOnEnterByField(field, e) {
    var e = e || window.event;
    var key = (window.Event) ? e.which : e.keyCode;

    if (key != 13) {
    } else {
        var nextElement = getNextElement(field);
        if (nextElement) {
            nextElement.focus();
            if (nextElement.select) {
                nextElement.select();
            }
            return false;
        }
    }
    return true;
}

function ThousandSeparator(number) {
    number = '' + number;
    if (number.length > 3) {
        var mod = number.length % 3;
        var output = (mod > 0 ? (number.substring(0, mod)) : '');
        for (i = 0; i < Math.floor(number.length / 3); i++) {
            if ((mod == 0) && (i == 0))
                output += number.substring(mod + 3 * i, mod + 3 * i + 3);
            else
                // hier wird das Trennzeichen festgelegt mit '.'
                output += '.' + number.substring(mod + 3 * i, mod + 3 * i + 3);
        }
        return (output);
    } else return number;
}

function form2array(form) {
    var result = new Array();

    for (var i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];

        switch (element.type) {
            case 'radio':
            case 'checkbox':
                if (element.checked) {
                    if (element.name.charAt(element.name.length - 1) == ']') {
                        if (!result[element.name]) result[element.name] = new Array();
                        result[element.name].push(element.value);
                    } else {
                        result[element.name] = element.value;
                    }
                }
                break;

            default:
                result[element.name] = element.value;
            //alert(element.type + ' ' + element.name + '='+element.value);
        }
    }

    return result;
}

function form2string(form) {
    var result = '';

    for (var i = 0; i < form.elements.length; i++) {
        var element = form.elements[i];

        if (result.length != 0) {
            result += '&';
        }

        var name = element.name;
        var value = element.value;
        if (!isUndefined(name) && !isFunction(value) && !isUndefined(value)) {
            switch (element.type) {
                case 'radio':
                case 'checkbox':
                    if (element.checked) {
                        result += name + '=' + encodeURIComponent(value);
                    }
                    break;

                default:
                    result += name + '=' + encodeURIComponent(value);
            }
        }
    }

    return result;
}

/**
 * Veraltet bitte date.js verwenden!
 * @deprecated
 */
function getWeekNumber(day, month, year) {
    // Sommerzeit ber�cksichtigen
    var h = ((new Date(year, month - 1, day).getTimezoneOffset()) - (new Date(year, 0, 1).getTimezoneOffset())) / 60;
    h = h * -1;

    var date = new Date(year, month - 1, day, h, 0, 1); // z.B. 16.6.2008 + Sommerzeit u. 1 sec
    var date_dayOfWeek = ((date.getDay() + 6) % 7) + 1; // z.B.1 f�r Montag

    if (date >= new Date(year, 11, 29)) {
        var weekNumber = 1;
    } else {
        var firstDayOfYear = new Date(year, 0, 1, 0, 0, 0); // 01.01.2008
        var wochentag = ((firstDayOfYear.getDay() + 6) % 7) + 1; // 1-7, z.B. 2 f�r Dienstag

        var firstDayOfWeek = new Date(year, 0, 1 - wochentag + 1);
        //var firstDayOfWeek = new Date(Number(firstDayOfYear) + 86400000*(8-firstDayOfYear.getDay()));
        //var wochentag = ((firstDayOfWeek.getDay()+6)%7)+1; // 1-7, z.B. 2 f�r Dienstag

        if (wochentag > 4) {
            // Workaround
            if (day <= 3 && month == 1 && date_dayOfWeek > 4) {
                var kw = getWeekNumber(28, 12, (firstDayOfWeek.getFullYear()));
                return kw;
            }
            firstDayOfWeek.setTime(parseInt(firstDayOfWeek.getTime()) + (7 * 24 * 60 * 60 * 1000), 10);
        }
        //if(firstDayOfWeek.getDate() > 4) firstDayOfWeek.setTime(parseInt(firstDayOfWeek.getTime())-604800000);
        var weekNumber = Math.ceil((date.getTime() - firstDayOfWeek.getTime()) / (1000 * 60 * 60 * 24 * 7));
    }
    return weekNumber;
}

/**
 * Deutschen Fliesskommawert in Float umwandeln (kann statt dem Wert auch NaN liefern!!)
 */
function GermanValueToFloat(value) {
    if (value == '') value = '0';
    if (value.indexOf(',')) value = value.replace(',', '.');
    return parseFloat(value);
}

/**
 * Sortierfunktion Float/Waehrung
 *
 * @param a
 * @param b
 * @param order
 * @returns {Number}
 */
function float_de_sort(a, b, order) {
    a = GermanValueToFloat(a);
    b = GermanValueToFloat(b);

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
 * console.log Ersatz
 *
 * @param text
 * @returns {Boolean}
 */
function log(text) {
    if (window.console) {
        let time = new Date().strftime('%H:%M:%S ');
        let type = typeof text;
        switch (type) {
            case 'object':
                console.log(time + ' ' + type + ':');
                console.log(text);
                break;

            default:
                console.log(time + ' ' + text);
        }
        return true;
    }
    return false;
}


/**
 * Ersatz fuer unescape, welches in JS in modernen Browsern nicht mehr verwendet werden darf.
 *
 * @param result
 * @returns {string}
 */
function unescapeResult(result) {
    return decodeURIComponent(result.replace(/\+/g, ' '));
}

if(!Array.prototype.shift) {
	function array_shift() {
		firstElement = this[0];
		this.reverse();
		this.length = Math.max(this.length - 1, 0);
		this.reverse();
		return firstElement;
	}
	Array.prototype.shift = array_shift;
}

if(!Array.prototype.unshift) {
	function array_unshift() {
		this.reverse();
		for (var i = arguments.length-1; i>=0; i--){
			this[this.length] = arguments[i]
		}
		this.reverse();
		return this.length
	}
	Array.prototype.unshift = array_unshift;
}

if (!Array.prototype.push) {
	function array_push() {
		for(var i = 0; i < arguments.length; i++) {
			this[this.length] = arguments[i]
		};
		return this.length;
	}
	Array.prototype.push = array_push;
}

if(!Array.prototype.pop) {
	function array_pop() {
		lastElement = this[this.length-1];
		this.length = Math.max(this.length-1,0);
		return lastElement;
	}
	Array.prototype.pop = array_pop;
}

if(!Array.prototype.del) {
	// Funktion zum Loeschen eines Arrayelementes z.B. new_arr=old_arr.del(1);
	function array_del(n) {
	    for (var i=n; n<this.length-1; n++){
			this[n] = this[(parseInt(n)+1)];
		}
		this.length=this.length-1;
		return this; // abwaertskompatibel zum Alten
	}
	Array.prototype.del = array_del;
}

if(!Array.prototype.key_exists) {
	// Funktion zum Suchen eines Arrayelements
	Array.prototype.key_exists = function(key) {
		for(i in this) {
			if (i == key) {
				return true;
			}
		}
		return false;
	}
}

if(!Array.prototype.value_exists) {
	// Prueft Existenz eines Werts
	Array.prototype.value_exists = function(needle) {
		for(var i in this) {
			if (this[i] == needle)  {
				return true;
			}
		}
		return false;
	}
}

if(!Array.prototype.search) {
	// Funktion zum Suchen eines Arrayelements
	Array.prototype.search = function(needle) {
		for(var i in this) {
			if (this[i] == needle)
				return i;
		}
		return false;
	}
}

if(!Array.prototype.copy) {
	// Funktion zum Kopieren eines Arrays
    Array.prototype.copy=function(a) {
		var i=0, b=[];
		for(i;i<this.length;i++)
			b[i]=(typeof this[i].copy!='undefined') ? this[i].copy() : this[i];
        return b
    }
}

if(!Array.prototype.concat) {
	// Arrays verketten
    Array.prototype.concat=function(a) {
        var i=0, b=this.copy();
        for(i;i<a.length;i++) b[b.length]=a[i];
        return b
    }
}

if(!Array.prototype.slice) {
	// Extrahiert einen Ausschnitt eines Arrays
	Array.prototype.slice=function(a,c) {
		var i=0, b, d=[];
		if(!c)
		    c=this.length;
		if(c<0)
		    c=this.length+c;
		if(a<0)
		    a=this.length-a;
		if(c<a) {
		    b=a;
		    a=c;
		    c=b
		}
		for(i;i<c-a;i++)
		    d[i]=this[a+i];
		return d
	}
}

if(!Array.prototype.splice) {
	// Entfernt einen Teil eines Arrays und ersetzt ihn durch etwas anderes
    Array.prototype.splice=function(a,c) {
        var i=0, e=arguments, d=this.copy(), f=a;
        if(!c)
            c=this.length-a;
        for(i;i<e.length-2;i++)
            this[a+i]=e[i+2];
        for(a;a<this.length-c;a++)
            this[a+e.length-2]=d[a-c];
        this.length-=c-e.length+2;
        return d.slice(f,f+c)
    }
}

// Production steps of ECMA-262, Edition 5, 15.4.4.21
// Reference: http://es5.github.io/#x15.4.4.21
if (!Array.prototype.reduce) {
    Array.prototype.reduce = function(callback /*, initialValue*/) {
        'use strict';
        if (this == null) {
            throw new TypeError('Array.prototype.reduce called on null or undefined');
        }
        if (typeof callback !== 'function') {
            throw new TypeError(callback + ' is not a function');
        }
        var t = Object(this), len = t.length >>> 0, k = 0, value;
        if (arguments.length == 2) {
            value = arguments[1];
        } else {
            while (k < len && ! k in t) {
                k++;
            }
            if (k >= len) {
                throw new TypeError('Reduce of empty array with no initial value');
            }
            value = t[k++];
        }
        for (; k < len; k++) {
            if (k in t) {
                value = callback(value, t[k], k, t);
            }
        }
        return value;
    };
}

if (!Array.prototype.remove) {
    //This prototype function allows you to remove even array from array
    Array.prototype.remove = function(x) {
        var i;
        for(i in this) {
            if(this[i].toString() == x.toString()) {
                this.splice(i, 1)
            }
        }
        return this;
    }
}

// 	array_unique:	Entfernt doppelte Werte aus einem Array
/* todo
function array_unique(inputUnique) {
	checkArray = new Array(inputUnique.length);
	matches = 0;
	for (x = 0; x < inputUnique.length; x++) {
		if (checkArray[x] != 1) {
			for (y = x + 1; y < inputUnique.length; y++) {
				if (inputUnique[x] == inputUnique[y]) {
					foundMatch = true;
					if (checkArray[y] != 1) {
						matches++;
						checkArray[y] = 1;
					}
				}
			}
		}
	}
	returnArray = new Array(inputUnique.length - matches);
	returnArrayPos = 0;
	for (x = 0; x < checkArray.length; x++) {
	   if (checkArray[x] != 1) {
	      returnArray[returnArrayPos] = inputUnique[x];
	      returnArrayPos++;
	   }
	}
	return returnArray;
}
*/