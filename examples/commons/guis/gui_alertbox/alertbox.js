var alertbox_template = (function() {	
	var name = '';
	var callback = '';
	
	// Functions 
	this.init = function(paramName) {
		name = paramName;
	};
	
	this.setTitle = function(title) {
		$('#' + name + 'Title').html(title);
	};
	
	this.setText = function(text) {
		$('#' + name + 'Text').html(text);
	};
	
	this.setCallback = function(paramCallback) {
		callback = paramCallback;
		this.registerCallback();
	};
	
	this.hallo = function() {
		alert('Hallo ' + name)
	};
	
	this.open = function(title, text, callback) {
		if (typeof title !== 'undefined') {
			this.setTitle(title);
		}
		
		if (typeof text !== 'undefined') {
			this.setText(text);
		}
				
		if (typeof callback === 'function') {
			this.setCallback(callback);
		}
		
		$('#'+ name).modal('show');
	};
	
	// Events
	this.registerCallback = function() {
		if (typeof callback === 'function') {
			var elem = $('#' + name);
			elem.off('hidden.bs.modal')
			    .on('hidden.bs.modal', callback);
		}	
	};
		
});