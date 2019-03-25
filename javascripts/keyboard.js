/**
 * -= keyboard.js =-
 *
 * Ermittelt Informationen zu gedrueckten Tasten
 *
 * @version $Id: helpers.js,v 1.16 2007/07/11 07:57:20 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2009-05-12
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 */

POOL_CTRL_DOWN = false;

Event.observe(document, 'keydown', function(e) {
	var e = e || window.event;
	if(e.keyCode == 17) POOL_CTRL_DOWN = true;
});

Event.observe(document, 'keyup', function(e) {
	var e = e || window.event;
	if(e.keyCode == 17) POOL_CTRL_DOWN = false;
});