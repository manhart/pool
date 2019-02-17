/**
 * -= browser.js =-
 *
 * Ermittelt Informationen über den Browser: Name, Version, Plattform, DOM kompatibel
 *
 * @version $Id: browser.js,v 1.3 2007/06/25 08:44:32 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 *
 * Example:
 * if (is.ns) {
 *   //do ns related code
 * }
 */

function Browser()
{
	var b=navigator.appName;
	if (b.indexOf('Netscape')!=-1)
		this.b="ns";
	else
		if ((b=="Opera") || (navigator.userAgent.indexOf("Opera")>0))
			this.b = "opera";
		else
			if (b=="Microsoft Internet Explorer")
				this.b="ie";
	if (!b) alert('Unidentified browser.\nThis browser is not supported,');

	this.version=navigator.appVersion;
	this.v=parseInt(this.version);
	this.ns=(this.b=="ns" && this.v>=4);
	this.ns4=(this.b=="ns" && this.v==4);
	this.ns6=(this.b=="ns" && this.v==5);
	this.ie=(this.b=="ie" && this.v>=4);
	this.ie4=(this.version.indexOf('MSIE 4')>0);
	this.ie5=(this.version.indexOf('MSIE 5')>0);
	this.ie55=(this.version.indexOf('MSIE 5.5')>0);
	this.ie6=(this.version.indexOf('MSIE 6.0')>0);
	this.opera=(this.b=='opera');
    this.chrome=(this.version.indexOf('Chrome')>0);
	var dom=false;
	if(document.createElement)
		if(document.appendChild)
			if(document.getElementsByTagName) dom=true;
	this.dom=dom;
	this.def=(this.ie||this.dom); // most used browsers, for faster if loops
	this.moz = (this.dom && navigator.userAgent.indexOf('Gecko')>0);
	var ua=navigator.userAgent.toLowerCase();
	if (ua.indexOf("win")>-1)
		this.platform="win32";
	else
		if (ua.indexOf("mac")>-1)
			this.platform="mac";
		else
			this.platform="other";
}
is = new Browser();
