function getXMLHttp() {
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
		try {
			xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
		}
		catch (e) {
			try {
				xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
			}
			catch (E) {
				xmlhttp = false;
			}
		}
	@end @*/

	if (!xmlhttp && typeof XMLHttpRequest!='undefined') {
		xmlhttp = new XMLHttpRequest();
	}
	return xmlhttp;
}

function loadFragmentInToElement(fragment_url, element) {
	fragment_url = prepareUrl(fragment_url);
	fragment_url += 'requestMethod=ajax';

	var xmlhttp = getXMLHttp();
	xmlhttp.open('GET', fragment_url);
	xmlhttp.onreadystatechange = function() {
		/*
			0 (UNINITIALIZED)
			 The object has been created, but not initialized (the open method has not been called).

			(1) LOADING
			 The object has been created, but the send method has not been called.

			(2) LOADED
			 The send method has been called, but the status and headers are not yet available.

			(3) INTERACTIVE
			 Some data has been received. Calling the responseBody and responseText properties at this state to obtain partial results will return an error, because status and response headers are not fully available.

			(4) COMPLETED
			 All the data has been received, and the complete data is available in the responseBody and responseText properties.

		*/
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			element.innerHTML = unescape(xmlhttp.responseText);
			delete(xmlhttp);
		}
	}
	xmlhttp.send(null);
}

function loadFragment(fragment_url, callback) {
	fragment_url = prepareUrl(fragment_url);
	fragment_url += 'requestMethod=ajax';

	var xmlhttp = getXMLHttp();
	xmlhttp.open('GET', fragment_url);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			var retValue = 0;
			eval("retValue=" + callback + "(xmlhttp.responseText)");
			delete(xmlhttp);
			return retValue;
		}
	}
	xmlhttp.send(null);
}

function postDataWaitResponse(url, arrData) {
	var xmlhttp = getXMLHttp();
	arrData['requestMethod'] = 'ajax';

	xmlhttp.open('POST', url, false);
    //xmlhttp.setRequestHeader('X-POOL-Version', '0.1');
	xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    // xmlhttp.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); wegen Abwaertskompatibilität nicht möglich
    xmlhttp.setRequestHeader('Accept', 'text/javascript, text/html, application/xml, text/xml, */*');

	var data = array2string(arrData, true);
	xmlhttp.send(data);

	reValue = xmlhttp.responseText;
	delete(xmlhttp);
	return reValue;
}

function ajaxSend(url) {
	url = prepareUrl(url);
	url += 'requestMethod=ajax';

	var xmlhttp = getXMLHttp();
	xmlhttp.open('GET', url);
	xmlhttp.send(null);
}

function ajaxSendWithPageReload(url) {
	url = prepareUrl(url);
	url += 'requestMethod=ajax';

	var xmlhttp = getXMLHttp();
	xmlhttp.open('GET', url);
	xmlhttp.onreadystatechange = function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			delete(xmlhttp);
			document.location.reload();
		}
	}
	xmlhttp.send(null);
}

function ajaxPostWaitResponse(url, data) {
	if(data.length > 0) data += '&';
	data += 'requestMethod=ajax';

	var xmlhttp = getXMLHttp();
	xmlhttp.open('POST', url, false);
	xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
	xmlhttp.send(data);

	reValue = xmlhttp.responseText;
	delete(xmlhttp);
	return reValue;
}

/*
 xmlhttp.status:
 100
 Continue

101
 Switching protocols

200
 OK

201
 Created

202
 Accepted

203
 Non-Authoritative Information

204
 No Content

205
 Reset Content

206
 Partial Content

300
 Multiple Choices

301
 Moved Permanently

302
 Found

303
 See Other

304
 Not Modified

305
 Use Proxy

307
 Temporary Redirect

400
 Bad Request

401
 Unauthorized

402
 Payment Required

403
 Forbidden

404
 Not Found

405
 Method Not Allowed

406
 Not Acceptable

407
 Proxy Authentication Required

408
 Request Timeout

409
 Conflict

410
 Gone

411
 Length Required

412
 Precondition Failed

413
 Request Entity Too Large

414
 Request-URI Too Long

415
 Unsupported Media Type

416
 Requested Range Not Suitable

417
 Expectation Failed

500
 Internal Server Error

501
 Not Implemented

502
 Bad Gateway

503
 Service Unavailable

504
 Gateway Timeout

505
 HTTP Version Not Supported


*/