/**
 * -= windowmanager.js =-
 * 
 * kleine Javascript Fensterverwaltung
 *
 * @version $Id: windowmanager.js,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * 
 * @since 2003-09-16
 * @author Alexander Manhart <alexander.manhart@freenet.de> 
 * @link http://www.misterelsa.de
 *
 */

CWindowManager = function()
{
	this.windows = new Array();
}
CWindowManager.prototype.add = function(fenster) {
	try {
		if (!fenster) return;
		
		//alert(TextAttributes('window', fenster));
		//alert(fenster.getAttribute('name'));
		if (this.windows.length == 0) {
			this.windows.push(fenster);
		}
		else {
			for (var i=0; i<this.windows.length; i++) {
				if (this.windows[i] != fenster) {
					this.windows.push(fenster);
					break;
				}
			}
		}
	}
	catch(e) {
		alert('Fehler abgefangen: ' + e);
	}
}
CWindowManager.prototype.find = function(fenstername) {
//	alert(this.windows.length);
	for (var i=0; i<this.windows.length; i++) {
//		alert(this.windows[i].closed);
		if (this.windows[i] && !this.windows[i].closed) {
			if (this.windows[i].name == fenstername) {
//				alert('found');
				return this.windows[i];
			}
		}
	}
	return false;
}

WindowManager = new CWindowManager();