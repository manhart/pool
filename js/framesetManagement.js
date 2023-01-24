/*
 * framesetManagement.js
 *
 * Die Frameset Funktionstionsbibliothek unterstuetzen den Entwickler bei
 * der Programmierung von Framesets. Ein uebliches Problem bei der
 * Verwendung von JavaScript ist die Ansteuerung anderer Frames. Man
 * weiß nie genau, ob ein Frame tatsaechlich vollstaendig geladen wurde ...
 * wann ein Frame neu geladen wurde ... und dass bestimmte Events nach
 * dem Laden eines Frames ausgeloest werden.
 *
 * Alle diese Probleme loesen folgende Funktionen im Hauptframeset!
 *
 * Einbauanleitung:
 * GUI_Frame :: prepare:
 * $this -> addBodyLoad('parent.registerFrame(self.name)');
 * $this -> addBodyUnload('parent.unregisterFrame(self.name)');
 *
 * GUI_Frameset :: prepare oder
 * $jsFile = $this -> Weblication -> findJavaScript('framesetManagement.js', null, true);
 * $this -> Headerdata -> addJavaScript($jsFile);
 * $this -> addBodyLoad('loaded=true');
 * $this -> addBodyLoad('fireFramesetLoaded()');
 * $this -> addBodyUnload('loaded=false');
 *
 * @version $Id: framesetManagement.js,v 1.3 2005/10/06 14:21:29 schmidseder Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2004-09-10
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

	var loaded				= false;
	var registeredFrames	= new Array();
	var listenFrames		= new Array();
	var existsListenFrames	= new Array();

	function isFramesetLoaded()
	{
		return loaded && (registeredFrames.length == self.frames.length);
	}


	function registerFrame(namespace)
	{
		if(isRegisteredFrame(namespace) == 0) {
			registeredFrames.push(namespace);

			for (var i=0, len=listenFrames.length; i<len; i++) {
				if (listenFrames[i]['listenedframe'] == namespace) {
					// alert('fire:'+listenFrames[i]['targetframe']+':'+listenFrames[i]['targeteventname']);
					fireFrameEvent(listenFrames[i]['targetframe'], listenFrames[i]['targeteventname']);
				}
			}
		}
		// fireFramesetLoaded();
	}

	function unregisterFrame(namespace)
	{
		var index = isRegisteredFrame(namespace);
		if (index > 0) {
			index = parseInt(index) - 1;
			registeredFrames = registeredFrames.del(index);
		}
		removeEventListenerFrameLoaded(namespace);
	}

	function isFrameLoaded(framename)
	{
		return (isRegisteredFrame(framename) > 0);
	}

	function isRegisteredFrame(namespace)
	{
		var len = registeredFrames.length;
		for (var i=len; i >= 0; i--) {
			if (registeredFrames[i] == namespace) {
				return (i+1);
			}
		}
		return 0;
	}

	function addEventListenerFrameLoaded(listenedframe, targetframe, targeteventname)
	{
		if (existsListenFrames[listenedframe + targetframe + targeteventname] != 1) {
			var index = listenFrames.length;
			listenFrames[index] = new Array();

			listenFrames[index]['listenedframe'] = listenedframe;
			listenFrames[index]['targetframe'] = targetframe;
			listenFrames[index]['targeteventname'] = targeteventname;

			existsListenFrames[listenedframe + targetframe + targeteventname] = 1;
		}
	}

	function removeEventListenerFrameLoaded(framename)
	{
		for (var i=listenFrames.length-1; i>=0; i--) {
			if (listenFrames[i]['targetframe'] == framename) {
				existsListenFrames[listenFrames[i]['listenedframe'] + listenFrames[i]['targetframe'] + listenFrames[i]['targeteventname']] = 0;
				listenFrames = listenFrames.del(i);
			}
		}
	}

	function removeEventListenerFrameLoadedByTargetEventname(targeteventname)
	{
		for (var i=listenFrames.length-1; i>=0; i--) {
			if (listenFrames[i]['targeteventname'] == targeteventname) {
				existsListenFrames[listenFrames[i]['listenedframe'] + listenFrames[i]['targetframe'] + listenFrames[i]['targeteventname']] = 0;
				listenFrames = listenFrames.del(i);
			}
		}
	}

	function fireFramesetLoaded()
	{
		if (isFramesetLoaded()) {
			for (var i=0; i<registeredFrames.length; i++) {
				fireFrameEvent(registeredFrames[i], 'onFramesetLoaded');
			}
		}
	}

	function fireAllFrameLoaded()
	{
		for (var i=0; i<listenFrames.length; i++) {
			fireFrameEvent(listenFrames[i]['targetframe'], listenFrames[i]['targeteventname']);
		}
	}

	function fireAllFrameEvent(eventname, param)
	{
		var result=1;
		for (var i=0; i<listenFrames.length; i++) {
			if(fireFrameEvent(listenFrames[i]['targetframe'], eventname, param)==0) {
				result=0;
			}
		}
		return result;
	}

	function fireFrameEvent(framename, eventname, param)
	{
		var result=1;
		var existent=0;

		if(framename == self.name) {
			var frame = 'self.';
		}
		else {
			var frame = 'self.frames[\"" + framename + "\"].';
		}
		eval("existent=" + frame +eventname+";");
		if (existent) {
			if (typeof param != 'undefined') {
				eval("result="+frame+eventname+"('"+param+"');");
			}
			else {
				eval("result="+frame+eventname+"();");
			}
		}

		return result;
	}