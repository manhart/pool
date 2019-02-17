/**
 * -= scrollbox.js =-
 *
 * Für das Module GUI_CustomScrollbox!
 *
 * @version $Id: scrollbox.js,v 1.5 2007/01/05 09:26:32 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-08-21
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */


// Variables for Interval
var adjustControllerInterval;
var adjustDataInterval;


// ****************
// ScrollboxManager (verwaltet alle initialisierten Scrollboxen)
// ****************
ScrollboxManager = function()
{
	this.list = new Array();
	this.active = null;
}
ScrollboxManager.prototype.add = function(sb) {
	this.list.push(sb);
}
ScrollboxManager.prototype.get = function(name) {
	with (this) {
		for (var i=0; i<list.length; i++) {
			if (list[i].name == name) {
				return list[i];
			}
		}
	}
	return false;
}
ScrollboxManager.prototype.switchdirection = function(obj, cmd)
{
	if (cmd == 'in') {
		mouseover = true;
		if (obj.getAttribute('id') == "sb_scrollup") {
			scrolldirection = -1;
		}
		else {
			scrolldirection = 1;
		}
	}
	else {
		mouseover = false;
	}
}
ScrollboxManager.prototype.activate = function(sb) {
	this.active = sb;
}
ScrollboxManager.prototype.deactivate = function() {
	this.active = null;
}
ScrollboxManager.prototype.reInitialize = function(name) {
	var sbox = this.get(name);
	if (sbox) {
		sbox.initialize();
	}
}
SbManager = new ScrollboxManager();



// ****************
// Scrollbox (verwaltet alle relevanten Daten der Scrollbox)
// ****************
Scrollbox = function(name)
{
	this.name = name;

	// get Elements from Html
	this.ctrlframe =  document.getElementById('sb_ctrlframe' + this.name);
	this.controlbox = document.getElementById('sb_scrollbox' + this.name);
	this.controller = document.getElementById('sb_controller' + this.name);
	this.content =    document.getElementById('sb_content' + this.name);
	this.data =       document.getElementById('sb_data' + this.name);

	this.initialize();

	this.controller.sb = this;
	this.controller.onmousedown = this.doctrlmousedown;
	this.content.sb = this;
	this.content.onmousewheel = this.doContentMouseWheel;

}
Scrollbox.prototype.initialize = function() {
	this.ratio = 0;
	this.direction = 0;
	var contentinc;
	var scrollerinc;

	// added 16.02.2006 Alexander M.
	this.SetControllerTop(0);
		if (is.ns) {
			this.data.style.top = '0px';
		}
		else {
			this.data.style.pixelTop = 0;
		}

	this.contentHeight = (is.ie || is.opera) ? this.content.style.pixelHeight : parseInt(this.content.style.height);
	this.controlboxHeight = parseInt(this.controlbox.style.height);

	if (this.contentHeight < this.data.offsetHeight) {
		this.contentinc = this.contentHeight / this.data.offsetHeight;
		this.scrollerinc = Math.round(this.controlboxHeight * this.contentinc);
		this.ratio = this.data.offsetHeight / this.controlboxHeight;
	}
	else {
		this.scrollerinc = this.controlboxHeight;
		this.contentinc = 1;
	}

	this.setControllerHeight(this.scrollerinc);
	// unnötige Scrollcontroller ausblenden
	if (this.scrollerinc == this.contentHeight) {
		this.controller.style.visibility='hidden';
	}
	else {
		this.controller.style.visibility='visible';
	}
	this.ctrlframe.style.height = this.scrollerinc + 'px';

	this.hover = false;
	this.currentY = this.currentX = 0;
}
Scrollbox.prototype.doctrlmousedown = function(e)
{
	var e = e || window.event;
	var mbutton = (e.button) ? e.button : e.which;
	var key = (e.keyCode) ? e.keyCode : e.which;

	if (mbutton == 1) {
		if (this.sb.hover) {
			adjustControllerInterval = window.setInterval("Scrollbox_AdjustController('"+this.sb.name+"');", 1);
		}
		else {
			SbManager.activate(this.sb);

			this.sb.currentY = (is.ns) ? e.pageY : (e.clientY + document.body.scrollTop);
			if (is.mac && is.ie5) {
    			this.sb.currentY += parseInt('0' + document.body.currentStyle.marginTop, 10);
			}
		}
		adjustDataInterval = window.setInterval("Scrollbox_AdjustData('"+this.sb.name+"')", 1);
	}
};
Scrollbox.prototype.doContentMouseWheel = function()
{

	var e = e || window.event;
	var mbutton = (e.button) ? e.button : e.which;
	var key = (e.keyCode) ? e.keyCode : e.which;

	delta=0;
    if (e.wheelDelta >= 120)
        delta = -60; // * this.sb.contentinc;
    else if (e.wheelDelta <= -120)
        delta = +60; // * this.sb.contentinc;

	if (is.ns) {
		//this.sb.data.style.top = (parseInt(this.sb.data.style.top) + delta) + 'px';
	}
	else {
		var dataPixelTop = this.sb.data.style.pixelTop - delta;
		if(delta > 0) {
			var max = (this.sb.data.offsetHeight - this.sb.contentHeight);
			// alert(dataPixelTop + ' : ' + max);
			if(-dataPixelTop < max) {
				this.sb.data.style.pixelTop = dataPixelTop;
			}
			else {
				this.sb.data.style.pixelTop = -max+1;
				delta=delta+1;

			}

		}
		else {
			if(dataPixelTop < 0) {
				this.sb.data.style.pixelTop = dataPixelTop;
			}
			else {
				this.sb.data.style.pixelTop = 0;
			}

		}
		delta = Math.round(delta/this.sb.ratio);
		Scrollbox_MoveControllerByWheel(this.sb, delta);

		//	alert(new_pos);
		//	alert(this.sb.contentinc);
/*		if(new_pos <= 0) {
			this.sb.data.style.pixelTop = new_pos;
			delta = -delta/this.sb.ratio;

			Scrollbox_MoveControllerByWheel(this.sb, delta);
		}
		else {
			this.sb.data.style.pixelTop = 0;
			sb.SetControllerTop(sb.getControllerTop());
		}*/
	}
	return false;
}
Scrollbox.prototype.GetMenuOffsetTop = function()
{
	if (is.ns) {
		return parseInt(this.controlbox.style.top);
	}
	else {
		return this.controlbox.style.pixelTop;
	}
}
Scrollbox.prototype.GetMenuMaxHeight = function()
{
	if (is.ns) {
		return parseInt(this.controlbox.style.height);
	}
	else {
		return this.controlbox.style.pixelHeight;
	}
}
Scrollbox.prototype.GetControllerTop = function()
{
	if (is.ns) {
		return parseInt(this.controller.style.top);
	}
	else {
		return this.controller.style.pixelTop;
	}
}
Scrollbox.prototype.SetControllerTop = function(value)
{
	if (is.ns) {
		this.controller.style.top = value + 'px';
	}
	else {
		this.controller.style.pixelTop = value;
	}
}
Scrollbox.prototype.GetControllerHeight = function()
{
	if (is.ns) {
		return parseInt(this.controller.style.height);
	}
	else {
		return this.controller.style.pixelHeight;
	}
}
Scrollbox.prototype.setControllerHeight = function(value)
{
	if (is.ns) {
		this.controller.style.height = value + 'px';
	}
	else {
		this.controller.style.pixelHeight = value;
	}
}


// ****************
// HELPERS!!!
// ****************
function Scrollbox_MoveController(e)
{
	if (!SbManager || SbManager.active == null) {
		return false;
	}

	var sb = SbManager.active;
	var ctrl = sb.controller;

	var newY = (is.ns) ? e.pageY : (e.clientY + document.body.scrollTop);
	if (is.mac && is.ie5) {
		this.sb.newY += parseInt('0' + document.body.currentStyle.marginTop, 10);
	}
	distanceY = (newY - sb.currentY);
	sb.currentY = newY;

	var ctrltop = sb.GetControllerTop();
	var ctrlheight = sb.GetControllerHeight();
	var offsettop = sb.GetMenuOffsetTop();
	var maxheight = sb.GetMenuMaxHeight();

	if (((ctrltop + offsettop + distanceY) >= offsettop) && ((ctrltop + offsettop + distanceY) <= (offsettop + maxheight - ctrlheight))) {
		sb.SetControllerTop(ctrltop + distanceY);
	}
	return false;
}

function Scrollbox_MoveControllerByWheel(sb, delta)
{

	var ctrl = sb.controller;

	var newY = sb.currentY + delta;
	if (is.mac && is.ie5) {
		this.sb.newY += parseInt('0' + document.body.currentStyle.marginTop, 10);
	}
	distanceY = (newY - sb.currentY);
	sb.currentY = newY;

	var ctrltop = sb.GetControllerTop();
	var ctrlheight = sb.GetControllerHeight();
	var offsettop = sb.GetMenuOffsetTop();
	var maxheight = sb.GetMenuMaxHeight();

	if (((ctrltop + offsettop + distanceY) >= offsettop) && ((ctrltop + offsettop + distanceY) <= (offsettop + maxheight - ctrlheight))) {
		sb.SetControllerTop(ctrltop + distanceY);
	}
	else {
		if(delta > 0) {
			//alert(offsettop);
			sb.SetControllerTop(sb.controlboxHeight-ctrlheight);
		}
		else {
			sb.SetControllerTop(0);
		}
	}
	return false;
}

function Scrollbox_DropController(e)
{
	clearInterval(adjustControllerInterval);
	clearInterval(adjustDataInterval);
	SbManager.deactivate();
}

// Intervals:
function Scrollbox_AdjustData(name)
{
	var sb = SbManager.get(name);
	if (is.ns) {
		sb.data.style.top = -1 * sb.ratio * (sb.GetControllerTop()) + 'px';
	}
	else {
		sb.data.style.pixelTop = -1 * sb.ratio * (sb.GetControllerTop());
	}
}

function Scrollbox_AdjustController(name)
{
	var sb = SbManager.get(name);
	var ctrl = sb.controller;
	var offsettop = sb.GetMenuOffsetTop();
	var maxheight = sb.GetMenuMaxHeight();

	if (((ctrl.style.pixelTop + offsettop + sb.direction) >= offsettop) &&
	  ((ctrl.style.pixelTop + offsettop + sb.direction) <= offsettop)) {
		ctrl.style.pixelTop += sb.direction;
	}
}


// Init Procedure (use with body onload event)
function InitScrollbox(name)
{
	var sb = new Scrollbox(name);
	SbManager.add(sb);
}