/**
 * -= jquery.date.js =-
 *
 * Erweitert die Funktionalität des JavaScript Objekts JQuery um Validierung von Formularen und Feldern.
 *
 * $Log$
 *
 *
 * @version $Id: jquery.validation.js 22667 2012-08-23 14:39:13Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2011/04/28
 * @author Alexander Manhart <alexander(dot)manhart(at)gmx(dot)de>
 * @link http://www.manhart.la
 */

(function($) {
	/**
	 * Validierungsregeln
	 */
	var Validation = function(settings) {
		// privat/public : module pattern
		// siehe http://yuiblog.com/blog/2007/06/12/module-pattern/

		// private object
		var rules = {
			required: {
				check: function(value) {
					return !isEmpty(value);
				},
				msg: '* Dieses Feld ist ein Pflichtfeld'
			},
			email: {
				check: function(value) {
					if(!isEmpty(value)) {
						return isValidEmailAddress(value);
					}
					return true;
				},
				msg: '* Ungültige E-Mail Adresse'
			},
			date: {
				check: function(value) {
					return !(/Invalid|NaN/.test(new Date(value)));
				},
				msg: '* Ungültiges Datumsformat, erwartet wird das Format YYYY-MM-DD'
			},
			germanDate: {
				check: function(value) {
					if(!isEmpty(value)) {
						return !!(new Date().setGermanDate(value));
					}
					return true;
				},
				msg: '* Ungültiges Datumsformat, erwartet wird das Format TT.MM.JJJJ'
			},
			minAge: {
				check: function(value, Field) {
					var minAge = parseInt(Field.Element.attr('minAge'));
					var FieldDate = new Date().setGermanDate(value); // dt. Datumumwandlung in Date Object
					if(minAge > 0 && FieldDate && (calcAge(FieldDate) < minAge)) {
						this.msg = this.msg.replace(/%s/g, minAge);
						return false;
					}
					else {
						return true;
					}
				},
				msg: '* Das Mindestalter von %s Jahren wurde unterschritten!'
			},
			ajax: {
				// TODO
			}
		}

		// private method
		var testPattern = function(value, pattern) {
			var regExp = new RegExp(pattern, '');
			return regExp.test(value);
		}

		// public methods
		return {
			settings: settings,
			addRule: function(name, rule) {
				rules[name] = rule;
			},
			getRule: function(name) {
				return rules[name];
			}
		}
	}

	/**
	 * Feld
	 */
	var Field = function(Element) {
		this.Element = Element;
		this.valid = true;
		this.validateOnKeyUp = true;
		this.attach('change');
	}

	Field.prototype = {
		attach: function(eventName) {
			var Self = this;
			Self.Element.bind(eventName, function() {
				return Self.validate();
			});
		},
		validate: function() {
			var Self = this, Elem = Self.Element, errors = [];
			var validate = Elem.attr('class').match(/validate\[(.*)\]/);

			if(validate != null && validate[1]) {
				var ruleNames = validate[1].split(',');

				for(var i in ruleNames) {
					var rule = $.Validation.getRule(ruleNames[i]);
					if(rule != undefined) {
						if(!rule.check(Elem.val(), Self)) {
							errors.push(rule.msg);
						}
					}
				}

				// Fehler vorhanden
				if(errors.length) {
					Elem.unbind('keyup');
					if(this.bindKeyUp) Self.attach('keyup');

					// Fehlermeldungen anzeigen
					var onFieldError = $.Validation.settings['onFieldError'];
					if(onFieldError) {
						onFieldError.apply(Self, [errors]);
//						onError(Self, errors);
					}

					Self.valid = false;
				}
				else {
					// Fehlermeldungen ausblenden
					var onFieldSuccess = $.Validation.settings['onFieldSuccess'];
					if(onFieldSuccess) {
						onFieldSuccess.apply(Self);
					}
					Self.valid = true;
				}
			}
			return Self.valid;
		}
	}

	/**
	 * Formular
	 */
	var Form = function(form) {
		this.Form = form;

		// Lese alle Felder des Formulars ein
		var fields = [];
		// var fieldsValidate = [];
		form.find(':input').each(function() {
			var Element = $(this);
			fields.push(new Field(Element));
		});
		this.fields = fields;
	}

	Form.prototype = {
		validate: function() {
			// Überprüfe alle Felder des Formulars
			for(field in this.fields) {

				// Firefox schmeißt eine Fehlermeldung beim Aufruf von match(), wenns das Attribut class nicht gibt
				if (typeof this.fields[field].Element.attr('class') == 'undefined') continue;


				var result = this.fields[field].Element.attr('class').match(/validate\[.*\]/);
				if(result != null) {
					this.fields[field].validate();
				}
			}
		},
		isValid: function() {
			for(field in this.fields) {

				// Firefox schmeißt eine Fehlermeldung beim Aufruf von match(), wenns das Attribut class nicht gibt
				if (typeof this.fields[field].Element.attr('class') == 'undefined') continue;


				var result = this.fields[field].Element.attr('class').match(/validate\[.*\]/);
				if(result != null) {
	                if(!this.fields[field].valid) {
	                    this.fields[field].Element.focus();
	                    return false;
	                }
				}
			}
			return true;
		}
	}

    /**
     * validation Plugin fuer jQuery prototype
     */
    $.extend($.fn, {
        validation: function(options) {

        	$.Validation.settings = {
        		'onFieldError': null,
        		'onFieldSuccess': null
        	};

        	if(options) {
        		$.extend($.Validation.settings, options);
        	}

            var validator = new Form(this);
            $.data(this[0], 'validator', validator);

            this.bind('submit', function(event) {
                validator.validate();
                if(!validator.isValid()) {
                    event.preventDefault();
                }
            });
            return this;
        },
        validate: function() {
            var validator = $.data(this[0], 'validator');
            validator.validate();
            return validator.isValid();
        }
    });
    // eine neue Instanz des Objekts im jQuery Namespace einbauen
    $.Validation = new Validation();

})(jQuery);

// Beispiele:
//
// Beispiel fuer eigen defenierte Regel
//	jQuery.Validation.addRule('test', {
//		check: function(value) {
//			return (value == 'test');
//		},
//		msg: '* Das Feld muss "test" enthalten.'
//	});
//