/**
 * -= ajax.js =-
 *
 * Simpler AJAX Wrapper fuer den POOL
 *
 * $Log$
 *
 *
 * @version $Id: ajax.js,v 1.4 2004/09/23 14:08:02 manhart Exp $
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
var REQUEST_CONTENTTYPE = 'application/x-www-form-urlencoded';
var REQUEST_PARAM_MODULENAME = 'requestModule';

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
		alert('Variable SCRIPT_NAME wird nicht gesetzt! Siehe {SCRIPT_NAME} im Frame. Einfach in [GUI_Url(eliminate=schema)] abaendern.');
		return false;
	}

	if(typeof REQUEST_METHOD == 'undefined') {
		REQUEST_METHOD = 'get';
	}

	if(typeof params == 'undefined') {
		 params = [];
	}

	if(typeof async == 'undefined') {
        async = false;
	}

	// var parameters = '';

	var RequestUrl = new Url();
	RequestUrl.setScript(SCRIPT_NAME);
	if(module != null) RequestUrl.setParam('module', module);
	RequestUrl.setParam('method', method);

	// 15.12.2017, AM, Parameter in die URL zu stecken ist nicht sinnvoll! Fehler muss im Programm behoben werden, nicht hier.
	//if(typeof params == 'object' || what(params) != 'FormData') {
	//	for (var paramName in params) {
	//		RequestUrl.setParam(paramName, encodeURIComponent(params[paramName]));
	//	}
	//}
	//else {
	//	parameters = params;
	//}

	var parameters = params;

	// Weitere Parameter
	if(arguments.length > 4) {
		var onRequestSuccess = arguments[4];
	}

	if(arguments.length > 5) {
		var onRequestComplete = arguments[5];
	}

	if(arguments.length > 6) {
		var onPhpFailure = arguments[6];
	}

	if(arguments.length > 7) {
		var onRequestFailure = arguments[7];
	}

	php_RESULT = null;
	new Ajax.Request(RequestUrl.getUrl(), {
		method: REQUEST_METHOD,
		asynchronous: async,
		parameters: parameters, // todo evalJS
		postBody: (what(params) === 'FormData') ? parameters : false,
		encoding: 'UTF-8',
		contentType: REQUEST_CONTENTTYPE,
		onFailure: function() {
			showRequestAlert('Unbekannter Fehler bei der Anfrage der URL: '+RequestUrl.getUrl()+'!', '');
		},
		onSuccess: function(transport) {
			var isPhpError = false;
			var json = {};
			try {
				if(RequestPOOL_DEBUG) alert(transport.responseText);
				try {
					if(transport.responseText.isJSON()) {
						json = transport.responseText.evalJSON();
					}
					else {
						return showRequestAlert(transport.responseText, '');
					}
				}
				catch(e) {
					if(e.name.toString() === 'SyntaxError') {
						isPhpError = true;
						throw e;
					}
					else throw e;
				}
				var Result = json['Result'];
				var ErrorMsg = json['Error'];

				if(ErrorMsg.length>0) {
					isPhpError = true;
//					var e = new Error();
					ErrorMsg = unescape(ErrorMsg);
					showRequestAlert(ErrorMsg, 'PHP Error');
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
									f(Result, json);
								}
								break;

							case 'object':
								if(typeof onRequestSuccess == 'string') {
									var Result = $H(Result).toJSON();
									eval(onRequestSuccess+'('+Result+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(Result, json);
								}
								break;

							case 'number':
							case 'boolean':
								if(typeof onRequestSuccess == 'string') {
									eval(onRequestSuccess+'('+Result+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(Result, json);
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
				php_RESULT = Result;
			}
			catch(e) {
				var ErrorMsg = '';
				var isHTML = (typeof showRequestAlert == 'function');
				var nl = (isHTML?'<br>':String.fromCharCode(10));

				ErrorMsg = e.message;
				if(!isPhpError) {
					ErrorMsg = 'Fehler im JavaScript-Kontext: '+ErrorMsg;
				}
				else {
					ErrorMsg = 'Fehler im PHP Skript: '+unescape(ErrorMsg)+nl+nl+'URL der Anfrage: '+RequestUrl.getUrl();
				}

//				if(isHTML) {
				showRequestAlert(ErrorMsg, '');
//						alert(e.message+String.fromCharCode(10)+String.fromCharCode(10)+'Ursprungsfehler: '+ErrorMsg);

			}
			finally {
			}
		},
		onComplete: function(transport) {
			var fct_typeof = typeof onRequestComplete;
			if(fct_typeof != 'undefined') {
				if(fct_typeof == 'function') {
					var f = onRequestComplete;
					f(transport);
				}
			}
		}
	});
	return true;
}

/**
 * Standard Fehlermeldungsdialog
 */
function showRequestAlert(message, info)
{
	if(typeof REQUEST_ALERT_WINDOW_CLASSNAME == 'undefined') {
		REQUEST_ALERT_WINDOW_CLASSNAME = 'dialog';
	}
	if(typeof REQUEST_ALERT_WINDOW_STATUSBAR == 'undefined') {
		REQUEST_ALERT_WINDOW_STATUSBAR = true;
	}
	if(typeof Window != 'undefined') { // window.js eingebunden
		try {
			var win = new Window({
				className: REQUEST_ALERT_WINDOW_CLASSNAME,
				width: 350,
				height: 400,
				zIndex: 100,
				resizable: true,
				title: 'Ajax request failed',
				showEffect: Element.show, //Effect.BlindDown,
				hideEffect: Element.hide, //Effect.SwitchOff,
				draggable: true,
				wiredDrag: true
			});

			win.getContent().innerHTML= "<div style='padding:10px'>"+message+"</div>";
			if(REQUEST_ALERT_WINDOW_STATUSBAR) {
				if(isEmpty(info)) info = '&nbsp;';
				win.setStatusBar(info);
			}
			win.showCenter();
		}
		catch(e) {
			alert(message);
		}

		return true;
	}

	alert(message);
	return true;
}