/**
 * -= mouseposition.js =-
 *
 * Ermittelt die korrekte Mausposition!
 *
 * @require browser.js
 *
 * @version $Id: mouseposition.js,v 1.1.1.1 2004/09/21 07:49:30 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 *
 */

MousePosition = function() {
	this.mousePosY = 0;
	this.mousePosX = 0;
}
MousePosition.prototype.getBody = function(e) {
	return (document.compatMode && document.compatMode != "BackCompat") ? document.documentElement : document.body;
}
MousePosition.prototype.detect = function(e) {
	mousePosY = (is.ns) ? (e.pageY) : (event.clientY + this.getBody().scrollTop);
	mousePosX = (is.ns) ? (e.pageX) : (event.clientX + this.getBody().scrollLeft);

	if (is.mac && is.ie5) {
		mousePosY += parseInt('0' + document.getTrueBody().currentStyle.marginTop, 10);
	}
	this.mousePosY = mousePosY;
	this.mousePosX = mousePosX;
	//window.status = 'Y: ' + mousePosY + ' X:' + mousePosX;
}

MousePosition = new MousePosition();