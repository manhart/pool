/**
 * -= array.js =-
 *
 * Fuer alle Browser, die keine Array Funktionen shift, unshift, push und pop unterstuetzen (< IE 5.5 / Mac)
 * Plus erweiterte Funktionen, die man aus PHP kennt.
 *
 * $Log: array.js,v $
 * Revision 1.4  2004/09/23 14:08:02  manhart
 * Log Message included
 *
 *
 * @version $Id: array.js,v 1.4 2004/09/23 14:08:02 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

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

/*		var vorne = this.slice(0, n);
		var hinten = this.slice(n+1, this.length);

		return vorne.concat(hinten);
*/
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