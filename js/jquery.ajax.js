/**
 * -= jquery.ajax.js =-
 *
 * Simpler AJAX Wrapper for the POOL
 *
 * @deprecated
 * @since 2009-07-15
 * @author Alexander Manhart <alexander.manhart@gmx.de>
 */
'use strict';

let REQUEST_METHOD;
let REQUEST_SCHEMA;
let REQUEST_CONTENTTYPE;
let REQUEST_PROCESSDATA;
let REQUEST_DATATYPE;
resetGlobalsOfRequestPOOL();

function resetGlobalsOfRequestPOOL()
{
    REQUEST_METHOD = undefined;
    REQUEST_SCHEMA = null;
    REQUEST_CONTENTTYPE = 'application/x-www-form-urlencoded; charset=UTF-8';
    REQUEST_PROCESSDATA = true;
    REQUEST_DATATYPE = '';
}

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
		alert('Variable SCRIPT_NAME wird nicht gesetzt! Siehe {SCRIPT_NAME} im Frame. Einfach in [GUI_Url(eliminate=schema)] abändern.');
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

	const RequestUrl = new Url();
	RequestUrl.setScript(SCRIPT_NAME);
    if(REQUEST_SCHEMA != null) RequestUrl.setParam('schema', REQUEST_SCHEMA);
	if(module != null) RequestUrl.setParam('module', module);
	RequestUrl.setParam('method', method);


	// Weitere Parameter
	if(arguments.length > 4) {
		var onRequestSuccess = arguments[4];
	}

	if(arguments.length > 5) {
		var onRequestComplete = arguments[5];
	}

	// if(RequestPOOL.arguments.length > 6) {
	// 	var onPhpFailure = RequestPOOL.arguments[6];
	// }

	if(arguments.length > 6) {
		var onRequestFailure = arguments[6];
	}

	if(arguments.length > 7) {
		var onBeforeSend = arguments[7];
	}

	// if(RequestPOOL.arguments.length > 8) {
	// 	var onJavascriptFailure = RequestPOOL.arguments[8];
	// }

    let contentType = REQUEST_CONTENTTYPE;
	let processData = REQUEST_PROCESSDATA;
	let dataType = REQUEST_DATATYPE;
	let type = REQUEST_METHOD;

	if(params instanceof FormData) {
	    contentType = false;
	    processData = false;
	    type = 'POST';
	    // dataType = 'json';
    }

	let jqxhr = jQuery.ajax(RequestUrl.getUrl(), {
			type: type,
			data: params,
			async: async,
			cache: false,
			timeout: 0,
			contentType: contentType,
			processData: processData,
			dataType: dataType,
			error: function(jqXHR, textStatus, errorThrown) {
                let message = 'Unknown Error';
                switch(jqXHR.readyState) {
                    case 0: // Network error
                        message = 'Network unreachable.';
                        break;

                    case 4: // HTTP error (Request was successfully, but there are other errors from JQuery or POOL)
                        // textStatus could be timeout, error, abort, parsererror.
                        let isPoolError = textStatus == 'pool_error_message';

                        // When an HTTP error occurs, errorThrown receives the textual portion of the HTTP status, such as "Not Found" or "Internal Server Error."
                        if(errorThrown instanceof Error) {
                            message = errorThrown.message;
                        }
                        else {
                            message = errorThrown;
                        }

                        if(jqXHR.status != 200) {
                            message = 'HTTP Status: ' + jqXHR.status + ' ' + message;
                        }
                        if(!isPoolError && textStatus) {
                            message = 'Status: ' + textStatus + ' ' + message;
                        }

                        // unspecified server response
                        if(!isPoolError && jqXHR.responseText) {
                            message += ' (Server-Response: ' + jqXHR.responseText + ')';
                        }
                        break;
                    //
                    // case 4: // HTTP error
                    //     message = 'RequestPOOL ajaxError handler: "'+textStatus+'".'+String.fromCharCode(10)+errorThrown+', status: '+jqXHR.status;
                    //     if(textStatus != 'error_message') { // Error comes not from the developer
                    //         message = message + String.fromCharCode(10)+String.fromCharCode(10);
                    //         message = message + 'Server-Response: '+jqXHR.responseText;
                    //     }
                    //     break;
                }

                if(window.console) {
                    window.console.error('jqxhr.error:', textStatus, 'message:', message, 'errorThrown:', errorThrown, 'responseText:', jqXHR.responseText);
                }

                if(typeof onRequestFailure == 'function') {
					if(onRequestFailure(jqXHR, textStatus, errorThrown, message) === true) {
                        return;
                    }
				}

                alert(message);
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
					this.error(jqXHR, 0, 'Apache/PHP Script died: unknown error');
					return false;
				}

                // debugger;
                // const contentType = jqXHR.getResponseHeader('Content-Type') || '';

				if(jQuery.type(data) == 'string') {
					this.error(jqXHR, 0, 'PHP Script Warning/Error: ' + data);
					return false;
				}

                // expect POOL object with the properties "Result" and optionally "Error". If not, take it as it is.
                let error = data.error ?? false;
                data = data.data ?? data;

				if(error && error.length > 0) {
					this.error(jqXHR, 'pool_error_message', window.decodeURI(error));
					return false;
				}

				if(typeof onRequestSuccess != 'undefined') {
					try {
						switch(typeof data) {
							case 'string':
								if(typeof onRequestSuccess == 'string') {
									eval(onRequestSuccess+'(\''+data+'\');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(data, textStatus, jqXHR);
								}
								break;

							case 'object':
								if(typeof onRequestSuccess == 'string') {
                                    data = $H(data).toJSON();
                                    eval(onRequestSuccess+'('+data+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f = onRequestSuccess;
									f(data, textStatus, jqXHR);
								}
								break;

							case 'number':
							case 'boolean':
								if(typeof onRequestSuccess == 'string') {
									eval(onRequestSuccess+'('+data+');');
								}
								else if(typeof onRequestSuccess == 'function') {
									var f=onRequestSuccess;
									f(data, textStatus, jqXHR);
								}
								break;

							default:
								alert('Type of data not defined in RequestPOOL: '+typeof data);
						}
					}
					catch(e) {
						alert('Fehler in der uebergebenen onRequestSuccess-Funktion aufgetreten: '+e.message+' (Funktion: '+onRequestSuccess+')');
					}
				}
			}
		}
	);
    resetGlobalsOfRequestPOOL();
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
		// console.debug('file_upload HTML 5');
		REQUEST_METHOD = 'post';
		REQUEST_CONTENTTYPE = false;
		REQUEST_PROCESSDATA = false;
		REQUEST_DATATYPE = 'json';

		let params = new FormData(Form.get(0)); /* HTML 5 Object FormData */
		RequestPOOL(guiName, method, params, true, function(Result) {
			// console.debug('file_upload result:');
			// console.debug(Result);
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
		// console.debug('file_upload IFRAME (alter Browser (IE8/9))');

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
			// console.debug('file_upload iframe result:')
			var data = $.parseJSON($(this).contents().text());
			// console.debug(data);
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