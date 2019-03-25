/**
 * -= ajax.js =-
 *
 * Simpler AJAX Wrapper fuer den POOL
 *
 * $Log$
 *
 *
 * @version $Id: jquery.ajax.js 34024 2017-04-05 07:45:38Z aziz $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2009-07-15
 * @author Alexander Manhart <alexander.manhart@wochenblatt.de>
 * @link http://www.softidea.de
 */

var php_RESULT = null;
var RequestPOOL_DEBUG = false;
var REQUEST_METHOD;
var REQUEST_PARAM_MODULENAME = 'requestModule';
var REQUEST_CONTENTTYPE = 'application/x-www-form-urlencoded; charset=UTF-8';
var REQUEST_PROCESSDATA = true;
var REQUEST_DATATYPE = '';

/**
 * Sendet Daten an PHP bzw. an ein POOL GUI
 */
function RequestPOOL(module, method, params, async)
{
	if(typeof SCRIPT_NAME == 'undefined') {
		alert('Variable SCRIPT_NAME is missing! Please define that variable in your tpl_frame.html as: SCRIPT_NAME = \'[GUI_Url(eliminate=schema)]\';');
		return false;
	}
	if(SCRIPT_NAME == '{SCRIPT_NAME}') {
		alert('Variable SCRIPT_NAME wird nicht gesetzt! Siehe {SCRIPT_NAME} im Frame. Einfach in [GUI_Url(eliminate=schema)] abï¿½ndern.');
		return false;
	}

	if(typeof REQUEST_METHOD == 'undefined') {
		REQUEST_METHOD = 'get';
	}

	if(typeof params == 'undefined') {
		var params = new Array();
	}

	if(typeof async == 'undefined') {
		var async = false;
	}

	var parameters = '';

	var RequestUrl = new Url();
	RequestUrl.setScript(SCRIPT_NAME);
	if(module != null) RequestUrl.setParam('module', module);
	RequestUrl.setParam('method', method);

/*	if(typeof params == 'object') {
		for (var paramName in params) {
			RequestUrl.setParam(paramName, encodeURIComponent(params[paramName]));
		}
	}
	else {
		parameters = params;
	}*/

	// Weitere Parameter
	if(RequestPOOL.arguments.length > 4) {
		var onRequestSuccess = RequestPOOL.arguments[4];
	}

	if(RequestPOOL.arguments.length > 5) {
		var onRequestComplete = RequestPOOL.arguments[5];
	}

	if(RequestPOOL.arguments.length > 6) {
		var onPhpFailure = RequestPOOL.arguments[6];
	}

	if(RequestPOOL.arguments.length > 7) {
		var onRequestFailure = RequestPOOL.arguments[7];
	}

	if(RequestPOOL.arguments.length > 8) {
		var onBeforeSend = RequestPOOL.arguments[8];
	}

	if(RequestPOOL.arguments.length > 9) {
		var onJavascriptFailure = RequestPOOL.arguments[9];
	}

//	jQuery.ajaxSetup({
//		url: RequestUrl.getUrl(),
//		type: 'GET', // jquery default
//		contentType: 'application/x-www-form-urlencoded', // jquery default
//		async: async
//	});
//	php_RESULT = null;

	// log('jQuery.ajax: type='+REQUEST_METHOD+' contentType='+REQUEST_CONTENTTYPE+' processData'+REQUEST_PROCESSDATA)
	var jqxhr = jQuery.ajax(RequestUrl.getUrl(), {
			type: REQUEST_METHOD,
			data: params,
			async: async,
			cache: false,
			timeout: 0,
			contentType: REQUEST_CONTENTTYPE,
			processData: REQUEST_PROCESSDATA,
			dataType: REQUEST_DATATYPE,
			error: function(jqXHR, textStatus, errorThrown) {
				if(onRequestFailure != undefined) {
					onRequestFailure(jqXHR, textStatus, errorThrown);
				}
				else {
					switch(textStatus) {
						case 'error_message': // Fehlermeldung vom Entwickler
							var message = 'RequestPOOL ajaxError handler: "'+textStatus+'".'+String.fromCharCode(10)+errorThrown+', status: '+jqXHR.status+String.fromCharCode(10)+String.fromCharCode(10);
							break;

						default:
							var message = 'RequestPOOL ajaxError handler: "'+textStatus+'".'+String.fromCharCode(10)+errorThrown+', status: '+jqXHR.status+String.fromCharCode(10)+String.fromCharCode(10)+'Server-Response: '+jqXHR.responseText;
					}

					if(typeof USE_CONSOLE != 'undefined' && USE_CONSOLE) {
						log(message);
					}
					else {
						// wenn die alert box verfuegbar ist, zeigen wir die Fehlermeldung darin an
						if(typeof alert_box == 'function') {
							alert_box(message);
						}
						else {
							alert(message);
						}
					}
				}
			},
			complete: function(jqXHR, textStatus) {
//				alert('completed - textStatus: '+textStatus);
				if(onRequestComplete != undefined) {
					onRequestComplete(jqXHR, textStatus);
				}
			},
			beforeSend: function(jqXHR) {
				if(onBeforeSend != undefined) {
					onBeforeSend(jqXHR);
				}
			},
			success: function(data, textStatus, jqXHR) {
				// log('jQuery.ajax: success');
				// log(data);
				// jQuery 1.7.1 u. jQuery 1.7.2 scheinen beim Abbruch eines PHP Scripts trotzdem in die success Methode zu gehen
				
				if(!data) {
					this.error(jqXHR, 0, 'Apache/PHP Script died: unkown error');
					return false;
				}

				if(jQuery.type(data) == 'string') {
					this.error(jqXHR, 0, 'PHP Script Warning/Error: '+data);
					return false;
				}

				var Result = data['Result'];
				var Error = data['Error'];

				if(Error && Error.length > 0) {
					this.error(jqXHR, 'error_message', unescape(Error));
					return false;
				}

				if(typeof onRequestSuccess != 'undefined') {
					try {
						switch(typeof Result) {
							case 'string':
								if(typeof onRequestSuccess == 'string') {
									eval(onRequestSuccess+'(\''+Result+'\');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(Result, textStatus, jqXHR);
								}
								break;

							case 'object':
								if(typeof onRequestSuccess == 'string') {
									var Result = $H(Result).toJSON();
									eval(onRequestSuccess+'('+Result+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f = onRequestSuccess;
									f(Result, textStatus, jqXHR);
								}
								break;

							case 'number':
							case 'boolean':
								if(typeof onRequestSuccess == 'string') {
									eval(onRequestSuccess+'('+Result+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(Result, textStatus, jqXHR);
								}
								break;

							default:
								alert('Type of Result not defined in RequestPOOL: '+typeof Result);
						}
					}
					catch(e) {
						alert('Fehler in der uebergebenen onRequestSuccess-Funktion aufgetreten: '+e.message+' (Funktion: '+onRequestSuccess+')');
					}
				}
			}
		}
	);
	return jqxhr;
}

/**
 * Datei-Upload ueber Ajax
 * 
 * @param formName Formular Name (name="xyz")
 * @param guiName Name des GUI's
 * @param method Name der Methode, die im GUI aufgerufen werden soll
 * @param onServerResponse Ereignis Server Antwort
 */
function file_upload(formName, guiName, method, onServerResponse)
{
	var Form = $('form[name="'+formName+'"]');
	
	if(window.FormData != undefined) { // window.FormData != undefined
		log('file_upload HTML 5');
		REQUEST_METHOD = 'post';
		REQUEST_CONTENTTYPE = false;
		REQUEST_PROCESSDATA = false;
		REQUEST_DATATYPE = 'json';

		var params = new FormData(Form.get(0)); /* HTML 5 Object FormData */
		RequestPOOL(guiName, method, params, true, function(Result) {
			log('file_upload result:');
			log(Result);
			if(onServerResponse) {
				onServerResponse(Result);
			}
		});
		
		// reset request
		REQUEST_METHOD = 'get';
		REQUEST_CONTENTTYPE = 'application/x-www-form-urlencoded; charset=UTF-8';
		REQUEST_PROCESSDATA = true;
		REQUEST_DATATYPE = '';
	}
	else {
		// alte Browser laden Daten ueber einen iframe hoch
		log('file_upload IFRAME (alter Browser (IE8/9))');
		
		// [GUI_Url(params=module:GUI_Liste;method:hochladen;HTTP_X_REQUESTED_WITH:XMLHttpRequest)]
		var FormUrl = new Url();
		FormUrl.setScript(SCRIPT_NAME);
		FormUrl.setParam('module', guiName);
		FormUrl.setParam('method', method);
		FormUrl.setParam('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
		Form.attr('action', FormUrl.getUrl());

		var iframeId = 'uploadIframe' + (new Date().getTime());
		
		// form target auf den iframe setzen
		Form.attr('target', iframeId);

		var Iframe = $('<iframe name="'+iframeId+'" src="javascript:false" />');

		// Iframe.width(500);
		// Iframe.height(300);

		// iframe verstecken
		Iframe.hide();

		Iframe.appendTo('body');
		Iframe.load(function() {
			log('file_upload iframe result:')
			var data = $.parseJSON($(this).contents().text());
			log(data);
			var Result = data['Result'];
			var Error = data['Error'];

			if(Error.length > 0) {
				alert(unescape(Error));
				return false;
			}

			if(onServerResponse) {
				onServerResponse(Result);
			}
			Iframe.remove();
		});

		Form.submit();
	}
}