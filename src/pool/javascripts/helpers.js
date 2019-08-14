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
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 *
 */

function reloadOpener()
{
	if (window.opener) {
		window.opener.document.location.reload();
	}
}

//function changeImage(img, src)
//{
//	img.src = src;
//}

function TextAttributes(ObjName,Object,NewLine)
{
	if(!NewLine) NewLine='\n';
	if(ObjName.length) {
		var Result="Attributes of the object \""+ObjName+"\" :\n\n";
	}
	var Attribute;
	for (Attribute in Object) {
		Result=Result+ObjName+"."+String(Attribute)+NewLine;
	}
	return Result;
}

function pray(value, newline) {
	if(pray.arguments.length==1) var newline='\n';
	return TextAttributes('', value, newline);
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

function cancelEvent(e)
{
	var e = e || window.event;
	if (is.ie) {
		e.cancelBubble = true;
        e.returnValue = false;
	}
	else {
        e.preventDefault();
        if(e.stopPropagation) {
            e.stopPropagation();
        }
	}
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

//function ClearOnFocus(elem, value)
//{
//	if (elem.value == value || value == '') {
//		elem.value = '';
//	}
//}

function setValueById(elem_id, value)
{
	var elem = document.getElementById(elem_id);
	if (typeof elem == 'object') {
		elem.value = value;
	}
}

function Hex2Num (hex)
{
	var num;
	var total;
	var i;

	total = 0;
	for (i=0;i<hex.length;i++) {
		letter = hex.substr(i,1);
		num = MakeNum(letter);
		exponent = ((hex.length-1)-i);
		total += num * Math.pow(16,exponent);
	}

	return total;
}

function Num2Hex (num)
{
	s=(num.toString(16));
	s=s.toUpperCase();
	return(s);
}

function Hex2String (hexstring)
{
	var str;
	var num;
	var hexval;

	totalnum = 0;
	str = "";
	for (i=0;i<hexstring.length;i=i+2) {
		hexval = hexstring.substr(i,2);
		num = Hex2Num(hexval);
		str += String.fromCharCode(num);
	}
	return str;
}

function String2Hex (str)
{
	var hex = "";
	for (i=0;i<str.length;i=i+1) {
		num = str.charCodeAt(i);
		hex += Num2Hex(num);
	}

	return hex;
}

function hexval(c) {
	if (String('0').charCodeAt(0) <= c && c <= String('9').charCodeAt(0))
		return c - String('0').charCodeAt(0);
	if (String('A').charCodeAt(0) <= c && c <= String('F').charCodeAt(0))
		return c - String('A').charCodeAt(0) + 10;
	if (String('a').charCodeAt(0) <= c && c <= String('f').charCodeAt(0))
		return c - String('a').charCodeAt(0) + 10;
	return 0;
}

function urlEncode(str) {
	var result = "";
	var i = 0;

	for (i=0; i < str.length; i++) {
		result = result + "%";
		result = result + "0123456789ABCDEF".charAt((str.charCodeAt(i)/16)&0x0F);
		result = result + "0123456789ABCDEF".charAt((str.charCodeAt(i)/1)&0x0F);
	}
	return result;
}

/**
 * a JavaScript equivalent of PHP's urlencode
 */
//function php_urlencode(str) {
//	str = (str + '').toString();
//
//	return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
//}

function urlDecode(str) {
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

// wahr, wenn das Zeichen eine Zahl ist
function isDigit( ch )
{
	if ( (ch >= '0') && (ch <= '9') )
		return true;
	else
		return false;
}

// wahr, wenn der String eine Zahl ist
function isNumeric(value)
{
	var len=value.length;
	for(var i=0; i<len; i++) {
		if(i==0 && (value.charAt(i)=='-' || value.charAt(i)=='+')) continue;
		if(!isDigit(value.charAt(i))) return false;
	}
	return true;
}

// wahr, wenn der String eine Fliesskommazahl ist
function isGermanFloat(value)
{
	if(value == undefined) return false;
	value = value.replace('.', '').replace(',', '.');
	var len = value.length
	for(var i=0; i<len; i++) {
		if(i==0 && (value.charAt(i)=='-' || value.charAt(i)=='+')) continue;
		if(value.charAt(i) == '.') continue;
		if(!isDigit(value.charAt(i))) {
			return false;
		}
	}
	return true;
}

// wahr, wenn das Zeichen ein Buchstabe ist
function isAlpha( ch )
{
	if ( ((ch >= 'a') && (ch <= 'z')) || ((ch >= 'A') && (ch <= 'Z')) )
		return true;
	else
		return false;
}

// wahr, wenn das Zeichen alphanumerisch ist
function isAlnum(ch)
{
	if ( isAlpha( ch ) || isDigit( ch ) )
		return true;
	else
		return false;
}

// wahr, wenn kein Zeichen aus str2 in str1 vorkommt
function notIn(str1, str2)
{
	var i = 0;
	var j = str2.length;
	for( ; i<j; i++ ) {
		var str3 =  str2.charAt(i);
		if( str1.indexOf( str3 ) != -1 )
        	return false;
		}
	return true;
}

// wahr, wenn eine Zifferfolge vorliegt
function checkNr ( nr )
{
	var i=0;
	var j=nr.length;

	if( j < 1 )
		return false;

	for( ; i<j; i++ )
		if( ( nr.charAt(i) < '0' ) || ( nr.charAt(i) > '9' ) )
			return false;

	return true;
}

// wahr, wenn IP-Adresse als g�tig eingestuft wurde
function checkIpnr( ipnr )
{
	var iL=0;
	var iC=0;
	var i=0;
	var sNr = "";

	for( ; i< ipnr.length; i++ ) {
		if ( ipnr.charAt(i) == '.' ) {
			if ( !iL || (iL> 3) || parseInt( sNr,10 ) > 255 )
				return false;
			iC++;
			iL = 0;
			sNr = "";
			continue;
		}
		if (isDigit ( ipnr.charAt(i) )) {
			iL++;
			sNr = sNr + ipnr.charAt(i);
			continue;
		}
		return false;
	}

	if ( parseInt( sNr,10 ) > 255 )
		return false;
	if ( ( (iC==3) && (iL>=1) && (iL<=3) ) || ( (iC==4) && (!iL) )  )
		return true;
	else
		return false;
}

// wahr, wenn der Fully Qualified Domain Name als g�tig eingestuft wurde
function checkFqdn(fqdn)
{
	var iL=0;
	var iC=0;
	var i=fqdn.length-1;

	if ( (fqdn.charAt(0) == '.') || (fqdn.charAt(0) == '-') )
		return false;
	if ( fqdn.charAt(i) == '.' )
		i=i-1;

	for( ; i>=0; i-- ) {
		if ( fqdn.charAt(i) == '.' ) {
			if ( iL < 2 && iC < 2 )
				return false;
			if ( fqdn.charAt(i-1) == '-' )
				return false;
			iC++;
			iL = 0;
			continue;
		}
		if ( isAlnum ( fqdn.charAt(i) ) ) {
			iL++;
			continue;
		}
		if ( fqdn.charAt(i) == '-' ) {
			if ( !iL )
				return false;
			iL++;
			continue;
		}
		return false;
	}

	if ( !iC || ( iL == 1 && iC < 2 ) || ( !iL && iC==1 ) ) {
		return false;
	}
	return true;
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

// wahr, wenn der Hostname als g�tig eingestuft wurde
function checkHostname( hostname )
{
	if ( hostname.charAt(0) == '[' ) {
		if ( hostname.charAt(hostname.length-1) != ']' )
			return false;
		var ipnr = hostname.substring( 1, hostname.length -1 );
		return checkIpnr( ipnr );
	}

	if ( hostname.charAt(0) == '#' ) {
		var nr = hostname.substring( 1, hostname.length );
		return checkNr( nr );
	}

	return checkFqdn( hostname );
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

function findPosX(obj)
{
	var curleft = 0;
	if (obj.offsetParent) {
		while (obj.offsetParent) {
			curleft += obj.offsetLeft;
			curleft -= obj.scrollLeft;
			obj = obj.offsetParent;
		}
	}
	else if (obj.x) {
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
	}
	else if (obj.y) {
		curtop += obj.y;
	}
	return curtop;
}

function prepareUrl(url)
{
	if (url.substr(url.length-1, 1) == '?' || url.substr(url.length-1, 1) == '&') {
	}
	else {
		if (url.search(/\?/) != -1) {
			url = url + '&';
		}
		else {
			url = url + '?';
		}
	}
	return url;
}

function getNextElement(field)
{
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
		var index=e % (form.elements.length);
		while(form.elements[index].type == 'hidden') {
			index++;
			if (form.elements.length==index) break;
		}
		return form.elements[index];
	}
	else {
		return false;
	}
}

function tabOnEnter(e)
{
	var e = e || window.event;
	var key = (window.event) ? e.keyCode : e.which;
	var activeElement = (window.event) ? e.srcElement : e.target;

	if (key == 13) {
		if (activeElement && activeElement.type != 'textarea') {
			var nextElement = getNextElement(activeElement);
			if (nextElement) {
				while(!nextElement.focus && activeElement!=nextElement) {
					var nextElement = getNextElement(nextElement);
				}
				focusCtrl(nextElement);
				return false;
			}
		}
	}
	return true;
}

function focusCtrl(elem)
{
	if(elem) {
		if(elem.focus) {
			try {
				elem.focus();
			}
			catch(e) {
			}
		}
		if(elem.select) {
			elem.select();
		}
	}
}

function tabOnEnterByField (field, e)
{
	var e = e || window.event;
	var key = (window.Event) ? e.which : e.keyCode;

	if (key != 13) {
	}
	else {
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

function cloneObject(what) {
    for (i in what) {
        if (typeof what[i] == 'object') {
            this[i] = new cloneObject(what[i]);
        }
        else
            this[i] = what[i];
    }
}

function ThousandSeparator(number) {
	number = '' + number;
	if (number.length > 3) {
		var mod = number.length % 3;
    	var output = (mod > 0 ? (number.substring(0,mod)) : '');
    	for (i=0 ; i < Math.floor(number.length / 3); i++) {
    		if ((mod == 0) && (i == 0))
    			output += number.substring(mod+ 3 * i, mod + 3 * i + 3);
    		else
    			// hier wird das Trennzeichen festgelegt mit '.'
    			output+= '.' + number.substring(mod + 3 * i, mod + 3 * i + 3);
    	}
    	return (output);
	}
	else return number;
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
    var i, v;
    if (isObject(o)) {
        for (i in o) {
            v = o[i];
            if (isUndefined(v) && isFunction(v)) {
                return false;
            }
        }
    }
    else {
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

function isInt(n)
{
    return n != "" && !isNaN(n) && Math.round(n) == n;
}

function isFloat(n){
    return n != "" && !isNaN(n) && Math.round(n) != n;
}

function popupFullscreen(url)
{
	var myWin=window.open(url, '_blank', 'scrollbars=auto,fullscreen=1,resizeable=1,channelmode=1');
	myWin.focus();
}

function popupBlank(url)
{
	var myWin=window.open(url, '_blank');
	myWin.focus();
}

function array2string(array) {
	var result = '';
	for(var key in array) {
		if(!isUndefined(key) && !isFunction(array[key]) && !isUndefined(array[key])) {
			if(result.length != 0) result += '&';
			result += key + '=' + escape(array[key]);
		}
	}
	return result;
}

function form2array(form) {
	var result = new Array();

	for(var i=0; i<form.elements.length; i++) {
		var element = form.elements[i];

		switch(element.type) {
			case 'radio':
			case 'checkbox':
				if(element.checked) {
					if(element.name.charAt(element.name.length-1)==']') {
						if(!result[element.name]) result[element.name] = new Array();
						result[element.name].push(element.value);
					}
					else {
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

	for(var i=0; i<form.elements.length; i++) {
		var element = form.elements[i];

		if(result.length != 0) {
			result += '&';
		}

		var name = element.name;
		var value = element.value;
		if(!isUndefined(name) && !isFunction(value) && !isUndefined(value)) {
			switch(element.type) {
				case 'radio':
				case 'checkbox':
					if(element.checked) {
						result += name + '=' + escape(value);
					}
					break;

				default:
					result += name + '=' + escape(value);
			}
		}
	}

	return result;
}

function dateDEtoUSA(dateDE) {
	var datum = /\b(0?[1-9]|[12][0-9]|3[01])\.(0?[1-9]|1[0-2])\.(\d?\d?\d\d)\b/
	if (datum.test(dateDE))
		return dateDE.replace(datum, "$3-$2-$1");
	else
		return dateDE;
}

function number_format(number, decimals, dec_point, thousands_sep)
{
  var exponent = "";
  var numberstr = number.toString ();
  var eindex = numberstr.indexOf ("e");
  if (eindex > -1) {
    exponent = numberstr.substring (eindex);
    number = parseFloat (numberstr.substring (0, eindex));
  }

  if (decimals != null) {
    var temp = Math.pow (10, decimals);
    number = Math.round (number * temp) / temp;
  }
  var sign = number < 0 ? "-" : "";
  var integer = (number > 0 ?
      Math.floor (number) : Math.abs (Math.ceil (number))).toString ();

  var fractional = number.toString ().substring (integer.length + sign.length);
  dec_point = dec_point != null ? dec_point : ".";
  fractional = decimals != null && decimals > 0 || fractional.length > 1 ?
               (dec_point + fractional.substring (1)) : "";
  if (decimals != null && decimals > 0) {
    for (i = fractional.length - 1, z = decimals; i < z; ++i)
      fractional += "0";
  }

  thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ?
                  thousands_sep : null;
  if (thousands_sep != null && thousands_sep != "") {
	for (i = integer.length - 3; i > 0; i -= 3)
      integer = integer.substring (0 , i) + thousands_sep + integer.substring (i);
  }

  return sign + integer + fractional + exponent;
}

/**
 * Extrahiert den Namen einer Datei aus einer vollständigen Pfadangabe
 */
function basename (path) {
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
	if(String(px).substr(-2) == 'px') {
		px = px.substring(0, px.length-2);
	}
	return parseInt(px, 10);
}

function int2pix(px) {
	if(String(px).substr(-2) != 'px') {
		px = px + 'px';
	}
	return px;
}

function unixtime2date(unixtime) {
	var theDate = new Date(unixtime * 1000);
	return theDate.toGMTString();
}

function date2unixtime(day, month, year, hours, minutes, seconds) {
	var humDate = new Date(Date.UTC(year, month, day, hours, minutes, seconds));
	return (humDate.getTime()/1000.0);
}

function dateobject2unixtime(dateObject) {
	return date2unixtime(dateObject.getDate(), dateObject.getMonth(), dateObject.getFullYear(), dateObject.getHours(), dateObject.getMinutes(), dateObject.getSeconds());
}

/* DropDown, Select */
function addOption(selectElement, caption, value) {
	var optn = document.createElement('OPTION');
	optn.text = caption;
	optn.value = value;

	try {
		selectElement.add(optn, null);
	}
	catch (e) {
		selectElement.add(optn);
	}
	return optn;
}

function clearSelect(selectElement) {
	for(var i=selectElement.options.length-1; i>=0; i--) {
		selectElement.options[i] = null;
	}
}

function php_serialize(obj)
{
    var string = '';

    if (typeof(obj) == 'object') {
        if (obj instanceof Array) {
            string = 'a:';
            tmpstring = '';
            count = 0;
            for (var key in obj) {
                tmpstring += php_serialize(key);
                tmpstring += php_serialize(obj[key]);
                count++;
            }
            string += count + ':{';
            string += tmpstring;
            string += '}';
        } else if (obj instanceof Object) {
            classname = obj.toString();

            if (classname == '[object Object]') {
                classname = 'StdClass';
            }

            string = 'O:' + classname.length + ':"' + classname + '":';
            tmpstring = '';
            count = 0;
            for (var key in obj) {
                tmpstring += php_serialize(key);
                if (obj[key]) {
                    tmpstring += php_serialize(obj[key]);
                } else {
                    tmpstring += php_serialize('');
                }
                count++;
            }
            string += count + ':{' + tmpstring + '}';
        }
    }
    else {
        switch (typeof(obj)) {
            case 'number':
                if (obj - Math.floor(obj) != 0) {
                    string += 'd:' + obj + ';';
                } else {
                    string += 'i:' + obj + ';';
                }
                break;
            case 'string':
                string += 's:' + obj.length + ':"' + obj + '";';
                break;
            case 'boolean':
                if (obj) {
                    string += 'b:1;';
                } else {
                    string += 'b:0;';
                }
                break;
        }
    }

    return string;
}

function getWeekdayDE(DE_date)
{
	var datumParts = DE_date.split('.');
	var ts = new Date(datumParts[2], parseInt(datumParts[1], 10)-1, parseInt(datumParts[0], 10));
	var wd = ts.getDay();
	if(wd == 0) wd=7;
	return wd;
}

/**
 * Prueft ein beliebiges Datum auf Gueltigkeit
 */
function isValidDate(d, m, y)
{
	if(y.length==2) {
		var now = new Date();
		y = now.getFullYear().toString().substring(0,2)+y.toString();
	}
	var dateEN = m + '/' + d + '/' + y;
	var ts = new Date(dateEN);

	if(ts.getDate() != d) {
		return false;
	}
	else if(ts.getMonth() != m-1) {
    	//this is for the purpose JavaScript starts the month from 0
		return false;
	}
    else if(ts.getFullYear() != y) {
		return false;
	}

    return true;
}

/**
 * Prueft ein deutsches Datum auf Gueltigkeit
 */
function isValidDateDE(DE_date)
{
	var datumParts = DE_date.split('.');
	if(datumParts.length==3) {
		return isValidDate(datumParts[0], datumParts[1], datumParts[2]);
	}
	else return false;
}

/**
 * Prueft ein deutsches Datum auf Gueltigkeit
 */
function isValidDateUS(US_date)
{
    var datumParts = US_date.split('-');
    if(datumParts.length==3) {
        return isValidDate(datumParts[2], datumParts[1], datumParts[0]);
    }
    else return false;
}

/**
 * Veraltet bitte date.js verwenden!
 * @deprecated
 */
function getWeekNumber(day, month, year) {
	// Sommerzeit ber�cksichtigen
	var h =( (new Date(year, month-1, day).getTimezoneOffset())-(new Date(year, 0, 1).getTimezoneOffset()) )/ 60;
	h = h*-1;

	var date = new Date(year, month-1, day, h, 0, 1); // z.B. 16.6.2008 + Sommerzeit u. 1 sec
	var date_dayOfWeek = ((date.getDay()+6)%7)+1; // z.B.1 f�r Montag

	if(date >= new Date(year, 11, 29)) {
		var weekNumber=1;
	}
	else {
		var firstDayOfYear = new Date(year, 0, 1, 0, 0, 0); // 01.01.2008
		var wochentag = ((firstDayOfYear.getDay()+6)%7)+1; // 1-7, z.B. 2 f�r Dienstag

		var firstDayOfWeek = new Date(year, 0, 1-wochentag+1);
		//var firstDayOfWeek = new Date(Number(firstDayOfYear) + 86400000*(8-firstDayOfYear.getDay()));
		//var wochentag = ((firstDayOfWeek.getDay()+6)%7)+1; // 1-7, z.B. 2 f�r Dienstag

		if(wochentag > 4) {
			// Workaround
			if(day<=3 && month==1 && date_dayOfWeek > 4) {
				var kw = getWeekNumber(28, 12, (firstDayOfWeek.getFullYear()));
				return kw;
			}
			firstDayOfWeek.setTime(parseInt(firstDayOfWeek.getTime())+(7*24*60*60*1000), 10);
		}
		//if(firstDayOfWeek.getDate() > 4) firstDayOfWeek.setTime(parseInt(firstDayOfWeek.getTime())-604800000);
		var weekNumber = Math.ceil((date.getTime() - firstDayOfWeek.getTime())/(1000*60*60*24*7));
	}
	return weekNumber;
}

	/**
	 * Deutschen Fliesskommawert in Float umwandeln (kann statt dem Wert auch NaN liefern!!)
	 */
	function GermanValueToFloat(value) {
		if(value == '') value = '0';
        if(value.indexOf(',')) value = value.replace(',', '.');
		return parseFloat(value);
	}

	/**
	 * Entfernt Whitespaces am Anfang und Ende des Strings
	 */
	function trim(str) {
		return ltrim(rtrim(str));
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
			else
				if (xD > yD)
					retval = 1;
			if(isDesc) retval = retval*-1;
			return retval;
		}

		// log('sortierung: a="'+a+'" b="'+b+'" Reihenfolge: '+order);

		// natural sorting through split numeric strings and default strings
		for(var cLoc = 0, numS = Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
			oFxNcL = parseFloat(xN[cLoc]) || xN[cLoc];
			oFyNcL = parseFloat(yN[cLoc]) || yN[cLoc];
			// log('oFxNcL: '+oFxNcL+' oFyNcL: '+oFyNcL);
			if (oFxNcL < oFyNcL) {
				retval = -1;
				// log('retval: '+retval);
				break;
			}
			else if (oFxNcL > oFyNcL) {
				retval = 1;
				// log('retval: '+retval);
				break;
			}
			// log('retval: '+retval);
		}
		if(isDesc) retval = retval * -1;
		// log('ergebnis retval: '+retval);

		return retval;

//		aa = a.split(/(\d+)/);
//		bb = b.split(/(\d+)/);
//
//		var retval = 0;
//		for(var x = 0; x < Math.max(aa.length, bb.length); x++) {
//			if(aa[x] != bb[x]) {
//				var cmp1 = (isNaN(parseInt(aa[x],10)))? aa[x] : parseInt(aa[x],10);
//				var cmp2 = (isNaN(parseInt(bb[x],10)))? bb[x] : parseInt(bb[x],10);
//				if(cmp1 == undefined || cmp2 == undefined)
//					retval = aa.length - bb.length;
//				else
//					retval = (cmp1 < cmp2) ? -1 : 1;
//			}
//		}
//		if(order == 'desc' || order == 'des') retval = retval*-1;
//		return retval;

//		var retval = 0;
//	    var re = /(^-?[0-9]+(\?[0-9]*)[df]?e?[0-9]?$|^0x[0-9a-f]+$|[0-9]+)/gi,
//	        sre = /(^[ ]*|[ ]*$)/g,
//	        dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
//	        hre = /^0x[0-9a-f]+$/i,
//	        ore = /^0/,
//	        i = function(s) { return str_natsort.insensitive && (''+s).toLowerCase() || ''+s },
//	        // convert all to strings strip whitespace
//	        x = i(a).replace(sre, '') || '',
//	        y = i(b).replace(sre, '') || '',
//	        // chunk/tokenize
//	        xN = x.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
//	        yN = y.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
//	        // numeric, hex or date detection
//	        xD = parseInt(x.match(hre)) || (xN.length != 1 && x.match(dre) && Date.parse(x)),
//	        yD = parseInt(y.match(hre)) || xD && y.match(dre) && Date.parse(y) || null,
//	        oFxNcL, oFyNcL;
//
//	    // first try and sort Hex codes or Dates
//	    if (yD) {
//	    	var retval = 0;
//	        if ( xD < yD ) retval = -1;
//	        else if ( xD > yD ) retval = 1;
//	        if(order == 'desc' || order == 'des') retval = retval*-1;
//	        return retval;
//	    }
//
//	    // natural sorting through split numeric strings and default strings
//	    for(var cLoc=0, numS=Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
//	        // find floats not starting with '0', string or 0 if not defined (Clint Priest)
//	        oFxNcL = !(xN[cLoc] || '').match(ore) && parseFloat(xN[cLoc]) || xN[cLoc] || 0;
//	        oFyNcL = !(yN[cLoc] || '').match(ore) && parseFloat(yN[cLoc]) || yN[cLoc] || 0;
//	        // handle numeric vs string comparison - number < string - (Kyle Adams)
//	        if (isNaN(oFxNcL) !== isNaN(oFyNcL)) { return (isNaN(oFxNcL)) ? 1 : -1; }
//	        // rely on string comparison if different types - i.e. '02' < 2 != '02' < '2'
//	        else if (typeof oFxNcL !== typeof oFyNcL) {
//	            oFxNcL += '';
//	            oFyNcL += '';
//	        }
//	        if (oFxNcL < oFyNcL) retval = -1;
//	        if (oFxNcL > oFyNcL) retval = 1;
//	    }
//	    if(order == 'desc' || order == 'des') retval = retval*-1;
//	    return retval;
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
			else
				if (xD > yD)
					retval = 1;
			if(isDesc) retval = retval*-1;
			return retval;
		}

		// log('sortierung: a="'+a+'" b="'+b+'" Reihenfolge: '+order);

		// natural sorting through split numeric strings and default strings
		for(var cLoc = 0, numS = Math.max(xN.length, yN.length); cLoc < numS; cLoc++) {
			if(isGermanFloat(xN[cLoc])) {
				xN[cLoc] = xN[cLoc].replace('.', '').replace(',', '.'); // tausender Trenner entfernen
			}
			if(isGermanFloat(yN[cLoc])) {
				yN[cLoc] = yN[cLoc].replace('.', '').replace(',', '.'); // tausender Trenner entfernen
			}
			oFxNcL = parseFloat(xN[cLoc]) || xN[cLoc];
			oFyNcL = parseFloat(yN[cLoc]) || yN[cLoc];
			// log('oFxNcL: '+oFxNcL+' oFyNcL: '+oFyNcL);
			if (oFxNcL < oFyNcL) {
				retval = -1;
				// log('retval: '+retval);
				break;
			}
			else if (oFxNcL > oFyNcL) {
				retval = 1;
				// log('retval: '+retval);
				break;
			}
			// log('retval: '+retval);
		}
		if(isDesc) retval = retval * -1;
		// log('ergebnis retval: '+retval);

		return retval;
	}

	function date_de_sort(a,b,order){
		a=a.split('.')
	    b=b.split('.')
	    if (a[2]==b[2]){
	        if (a[1]==b[1])
	            return (a[0]>b[0]?1:-1)*(order=='asc'?1:-1);
	        else
	            return (a[1]>b[1]?1:-1)*(order=='asc'?1:-1);
	    } else
			return (a[2]>b[2]?1:-1)*(order=='asc'?1:-1);
	}

	function stopBrowserLoading()
	{
		if(window.stop !== undefined) {
			window.stop();
		}
		else document.execCommand('Stop', false);
	}

	/**
	 * Wandelt das erste Zeichen von str in einen Gro�buchstaben um
	 */
	function ucfirst(str)
	{
		if(str.length > 0) {
			str = str.charAt(0).toUpperCase() + str.substr(1);
		}
		return str;
	}

	/**
	 * Ruft eine Funktion auf und bewirkt dass eventuelle Ereignisse/Nachrichten vorher abgearbeitet werden.
	 */
	function processFunction(func, callback, interval)
	{
		if(typeof interval != 'number') interval = 0;

		window.setTimeout(function() {
			var result = false;
			try {
				if(typeof func == 'string') {
					eval('result='+func);
				}
				else if(typeof func == 'function') {
					var f = func;
					result = f();
				}
			}
			finally {
				if(typeof callback == 'function') {
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

// NIX MEHR MIT PROTOTYPE'ING
//if (!Function.prototype.bind) { // check if native implementation available
//	Function.prototype.bind = function() {
//		var fn = this, args = Array.prototype.slice.call(arguments), object = args.shift();
//		return function() {
//			return fn.apply(object, args.concat(Array.prototype.slice.call(arguments)));
//		};
//	};
//}

	/**
	 * Programmverzögerung
	 */
	function sleep(milliseconds) {
		var start = new Date().getTime();
		while((new Date().getTime() - start) < milliseconds) {
		}
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
		if(a > b) {
			retval = 1;
		}
		else if(a < b) {
			retval = -1;
		}
		if(order != 'asc') retval = retval * -1;
		return retval;
	}
	
	/** 
	 * Sortierfunktion Float/Waehrung
	 * 
	 * @param a
	 * @param b
	 * @param order
	 * @returns {Number}
	 */
	function float_de_sort(a, b, order)
	{
		a = GermanValueToFloat(a);
		b = GermanValueToFloat(b);
		
		var retval = 0;
		if(a > b) {
			retval = 1;
		}
		else if(a < b) {
			retval = -1;
		}
		if(order != 'asc') retval = retval * -1;
		return retval;
	}

	/**
	 * console.log Ersatz
	 * 
	 * @param text
	 * @returns {Boolean}
	 */
	function log(text) {
		if(window.console && (location.host == 'develop1' || location.host == 'develop01')) {
			var time = new Date().strftime('%H:%M:%S ');
			var type = typeof text;
			switch(type) {
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
	 * Vererbung von Klassen/Objekten
	 */
	Function.prototype.inheritsFrom = function(parentClassOrObject) 
	{
		if (parentClassOrObject.constructor == Function) {
			// Normal Inheritance
			this.prototype = Object.create(parentClassOrObject.prototype);
			this.prototype.constructor = this;
			this.prototype.superclass = parentClassOrObject.prototype;
		}
		else {
			// Pure Virtual Inheritance
			this.prototype = parentClassOrObject;
			this.prototype.constructor = this;
			this.prototype.superclass = parentClassOrObject;
		}
		return this;
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

    /**
     * Ersatz fuer unescape, welches in JS in modernen Browsern nicht mehr verwendet werden darf.
     *
     * @param result
     * @returns {string}
     */
    function unescapeResult(result)
    {
        return decodeURIComponent(result.replace(/\+/g, ' '));
    }

    /**
     * Nur im IE moeglich! Fuer die Intranetzone "ActiveX-Steuerelemente initialisieren und ausführen, die nicht als 'sicher für Skripting' markiert sind" aktivieren.
     *
     * @param url
     */
    function startChrome(url) {
        // Parameter zusammenbauen
        //        var Parameter = "-foo -bar " + Parameter

        // Erzeugen des ActiveX Objekts
        var Shell = new ActiveXObject("WScript.Shell");

        // gewünschtes Programm starten
        Shell.Run('chrome.exe --no-proxy-server ' + url);
    }

    /**
    * Formatiere Minuten um als Stunde-Minuten Text
    *
    * @param int Minuten
    * @return string
    */
    function formatStdMin(value)
    {
        var value = parseInt(value);
        return Math.floor(value/60) + ' Std. ' + (value%60) + ' Min.';
    }

    /**
     * Formatiere Minuten in 24 Stunden Format
     *
     * @param int Minuten
     * @returns string
     */
    function format24h(min)
    {
        var value = parseInt(min);
        return Math.floor(value/60) + ':' + (value%60);
    }

    /**
     * A JavaScript equivalent of PHP’s utf8_decode
     * @param str_data
     * @returns {string}
     */
    function utf8_decode(str_data) {
        //  discuss at: http://phpjs.org/functions/utf8_decode/
        // original by: Webtoolkit.info (http://www.webtoolkit.info/)
        //    input by: Aman Gupta
        //    input by: Brett Zamir (http://brett-zamir.me)
        // improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // improved by: Norman "zEh" Fuchs
        // bugfixed by: hitwork
        // bugfixed by: Onno Marsman
        // bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
        // bugfixed by: kirilloid
        //   example 1: utf8_decode('Kevin van Zonneveld');
        //   returns 1: 'Kevin van Zonneveld'

        var tmp_arr = [],
            i = 0,
            ac = 0,
            c1 = 0,
            c2 = 0,
            c3 = 0,
            c4 = 0;

        str_data += '';

        while (i < str_data.length) {
            c1 = str_data.charCodeAt(i);
            if (c1 <= 191) {
                tmp_arr[ac++] = String.fromCharCode(c1);
                i++;
            } else if (c1 <= 223) {
                c2 = str_data.charCodeAt(i + 1);
                tmp_arr[ac++] = String.fromCharCode(((c1 & 31) << 6) | (c2 & 63));
                i += 2;
            } else if (c1 <= 239) {
                // http://en.wikipedia.org/wiki/UTF-8#Codepage_layout
                c2 = str_data.charCodeAt(i + 1);
                c3 = str_data.charCodeAt(i + 2);
                tmp_arr[ac++] = String.fromCharCode(((c1 & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
                i += 3;
            } else {
                c2 = str_data.charCodeAt(i + 1);
                c3 = str_data.charCodeAt(i + 2);
                c4 = str_data.charCodeAt(i + 3);
                c1 = ((c1 & 7) << 18) | ((c2 & 63) << 12) | ((c3 & 63) << 6) | (c4 & 63);
                c1 -= 0x10000;
                tmp_arr[ac++] = String.fromCharCode(0xD800 | ((c1 >> 10) & 0x3FF));
                tmp_arr[ac++] = String.fromCharCode(0xDC00 | (c1 & 0x3FF));
                i += 4;
            }
        }

        return tmp_arr.join('');
    }

/**
 * Umwandlung von String zum Datentyp Boolean
 */
function string2bool(val)
{
	return (val == '0') ? false : true;
}

/**
 * Laedt Datei herunter (IE8 kompatibel!)
 */
function downloadFile(file)
{
	var ts = new Date().getTime(); // Timestamp
	
	var link = document.createElement('a');

	link.setAttribute('id', 'tmpdownload' + ts);
	link.setAttribute('type', 'application/octet-stream');
	
	var isIE = /*@cc_on!@*/false;
	if(isIE) {
		link.target = '_blank';
		document.body.appendChild(link);
	}
	
	link.href = file + '?' + ts;
	
	// HTML 5
	link.download = basename(file);

	// alle modernen Browser (IE ab 9er)
	if(document.createEvent) {
		// funktioniert leider nicht im Safari unter Windows
		var clickEvent = document.createEvent('MouseEvent');
		clickEvent.initEvent('click', true, true);
		
		link.target = '_blank';
		link.dispatchEvent(clickEvent);
	}
	else if(link.click) {
		link.click();
	}
	
	if(isIE) {
		document.body.removeChild(link);
	}
}

/**
 * Wandelt base64 in Blob um
 *
 * @param data base64 kodierte Daten
 * @param contentType z.B. 'application/pdf';
 */
function base64ToBlob(data, contentType)
{
	if(!window.atob) {
		// benoetigt base64.js
		var byteChars = Base64.decode(data);
	}
	else {
		var byteChars = window.atob(data);
	}
	
	byteNumbers = new Array(byteChars.length);
	
	for (var i = 0; i < byteChars.length; i++) {
		byteNumbers[i] = byteChars.charCodeAt(i);
	}
	
	// < IE 10 benoetigt typedarray.js
	var byteArray = new Uint8Array(byteNumbers);
	
	try{
		// < IE 10 benoetigt Blob.js
		var blob = new Blob([byteArray], {type : contentType});
	}
	catch(e){
		// TypeError old chrome and FF
		window.BlobBuilder = window.BlobBuilder ||
			window.WebKitBlobBuilder ||
			window.MozBlobBuilder ||
			window.MSBlobBuilder;
		if(e.name == 'TypeError' && window.BlobBuilder){
			var bb = new BlobBuilder();
			bb.append(byteArray);
			var blob = bb.getBlob(contentType);
		}
		else if(e.name == "InvalidStateError"){
			// InvalidStateError (tested on FF13 WinXP)
			var blob = new Blob([byteArray], {type : contentType});
		}
		else{
			// We're screwed, blob constructor unsupported entirely
		}
	}
	
	return blob;
}

/*
 var blob = base64ToBlob(pdf_b64, 'application/pdf');
 
 if (window.navigator && window.navigator.msSaveOrOpenBlob) {
 window.navigator.msSaveOrOpenBlob(blob); // for IE
 }
 else {
 var fileURL = window.URL.createObjectURL(blob);
 window.open(fileURL);
 window.URL.revokeObjectURL(fileURL);
 }
 */

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
 * @returns {string}
 */
function what(obj) {
    if(obj == null) return '';
	return obj.toString().match(/ (\w+)/)[1];
}

/**
 * Camelize a string, cutting the string by multiple separators like
 * hyphens, underscores and spaces.
 *
 * @param {text} string Text to camelize
 * @return string Camelized text
 */
function camelize(text) {
    return text.replace(/^([A-Z])|[\s-_]+(\w)/g, function(match, p1, p2, offset) {
        if (p2) return p2.toUpperCase();
        return p1.toLowerCase();
    });
}

/**
 * Decamelizes a string with/without a custom separator (underscore by default).
 *
 * @param str String in camelcase
 * @param separator Separator for the new decamelized string.
 */
function decamelize(str, separator){
    separator = typeof separator === 'undefined' ? '_' : separator;
    
    return str
    .replace(/([a-z\d])([A-Z])/g, '$1' + separator + '$2')
    .replace(/([A-Z]+)([A-Z][a-z\d]+)/g, '$1' + separator + '$2')
    .toLowerCase();
}