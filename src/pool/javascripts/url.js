/**
 * Url-Klasse
 * @author christian Schmidseder
 */
function Url() {
	this.params = new Object();
	//
	// Fix POOL clone function (not needed in this context)
	delete(this.params.clone);
	this.path = null;
}
Url.prototype.setScript = function(script) {
	this.init(script);
}
Url.prototype.init = function(u) {
	if (typeof(u) == 'object') {
		u = u.toString();
	}

    var urlPieces = u.split('?');
    this.path     = urlPieces[0];

    if(urlPieces[1]) {
	    var paramStr  = urlPieces[1];

    	var pairs = paramStr.split('&');
		var anz = pairs.length;

	    for (var i = 0; i < anz; i++) {
			var pair = pairs[i].split('=');
			var key = pair[0];
	        var val = pair[1];
	        this.params[key] = val;
	    }
    }
}
Url.prototype.getUrl = function() {
        var u = this.path;
        u += '?';
        for (k in this.params) {
           if (k != 'clone') {
           		u = prepareUrl(u);
	       		u += k + '=' + this.params[k];
           }
        }
        return u;
}
Url.prototype.getParam = function(key) {
        return this.params[key];
}
/**
 * Setzt die URL-Parameter
 *
 * @param string|array key String oder Array
 * @param mixed val Wert
 */
Url.prototype.setParam = function(key, val) {
	if(typeof key == 'object') {
		for(var keyname in key) {
			this.params[keyname] = key[keyname];
		}
	}
	else {
		this.params[key] = val;
	}
}
Url.prototype.addParam = function(key, val) {
   this.setParam(key, val);
}
Url.prototype.removeParam = function(key) {
   delete(this.params[key]) ;
}
Url.prototype.restartUrl = function() {
	location.href = this.getUrl();
}