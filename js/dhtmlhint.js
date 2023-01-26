DHtmlHintObject = function()
{
	this.enableDhtmlHint = false;
	this.dhtmlHintType = 'normal';
	this.dhtmlhint_offsetxpoint = 0;
	this.dhtmlhint_offsetypoint = 0;
	this.error = false;
}
DHtmlHintObject.prototype.show = function(thetext, thecolor, thewidth, offsetxpoint, offsetypoint)
{
		this.dhtmlHintType = 'normal';
		if (is.dom || is.ie){
			this.setParams(thetext, thecolor, thewidth, offsetxpoint, offsetypoint);
		}
}
DHtmlHintObject.prototype.showAtObject = function(obj, thetext, thecolor, thewidth, offsetxpoint, offsetypoint)
{
		this.dhtmlHintType = 'atobject';
		if (is.dom || is.ie){
			this.dhtmlHintPosX = findPosX(obj);
			this.dhtmlHintPosY = findPosY(obj);
			this.setParams(thetext, thecolor, thewidth, offsetxpoint, offsetypoint);
		}
}
//private
DHtmlHintObject.prototype.setParams = function(thetext, thecolor, thewidth, offsetxpoint, offsetypoint)
{
	if (typeof thewidth!="undefined" && thewidth!="") {
		objDhtmlHint.style.width = thewidth+"px";
	}
	if (typeof thecolor != "undefined" && thecolor!="") {
		objDhtmlHint.style.backgroundColor = thecolor;
	}
	if (typeof offsetxpoint == 'undefined' || (offsetxpoint == '' && isNaN(offsetxpoint))) {
		this.dhtmlhint_offsetxpoint = -60;
	}
	else {
		this.dhtmlhint_offsetxpoint = offsetxpoint;
	}

	if (typeof offsetypoint == 'undefined' || (offsetypoint == '' && isNaN(offsetypoint))) {
		this.dhtmlhint_offsetypoint = 20;
	}
	else {
		this.dhtmlhint_offsetypoint = offsetypoint;
	}
	objDhtmlHint.innerHTML = thetext;
	this.enableDhtmlHint = true;
	//return false;
}
DHtmlHintObject.prototype.hide = function()
{
	if ((is.dom || is.ie) && this.enableDhtmlHint) {
		this.enableDhtmlHint = false;
		objDhtmlHint.style.visibility = "hidden";
		objDhtmlHint.style.left = "-1000px";
		objDhtmlHint.style.backgroundColor = '';
		objDhtmlHint.style.width='';
	}
}
DHtmlHintObject.prototype.doMouseMove = function(e)
{
	if (this.enableDhtmlHint) {
		if (!MousePosition && !this.error) {
			alert('Fehler! Objekt MousePosition nicht vorhanden!');
			this.error = true;
			return;
		}

		if (this.dhtmlHintType == 'atobject') {
			var calcCorner = false;
			var curX = this.dhtmlHintPosX;
			var curY = this.dhtmlHintPosY;
		}
		else {
			var calcCorner = true;
			var curX = MousePosition.mousePosX;
			var curY = MousePosition.mousePosY;
		}

		if (calcCorner) {
			//Find out how close the mouse is to the corner of the window
			var rightedge=is.ie && !is.opera ? MousePosition.getBody().clientWidth - event.clientX-this.dhtmlhint_offsetxpoint : window.innerWidth - e.clientX-this.dhtmlhint_offsetxpoint-20;
			var bottomedge=is.ie && !is.opera ? MousePosition.getBody().clientHeight - event.clientY-this.dhtmlhint_offsetypoint : window.innerHeight - e.clientY-this.dhtmlhint_offsetypoint-20;

			var leftedge=(this.dhtmlhint_offsetxpoint<0)? this.dhtmlhint_offsetxpoint*(-1) : -1000

			//if the horizontal distance isn't enough to accomodate the width of the context menu
			if (rightedge < objDhtmlHint.offsetWidth) {
				//move the horizontal position of the menu to the left by it's width
				objDhtmlHint.style.left=is.ie ? MousePosition.getBody().scrollLeft+event.clientX-objDhtmlHint.offsetWidth+"px" : window.pageXOffset+e.clientX-objDhtmlHint.offsetWidth+"px"
			}
			else if (curX < leftedge) {
				objDhtmlHint.style.left = "5px"
			}
			else {
				//position the horizontal position of the menu where the mouse is positioned
				objDhtmlHint.style.left = curX + this.dhtmlhint_offsetxpoint + "px"
			}

			//same concept with the vertical position
			if (bottomedge<objDhtmlHint.offsetHeight) {
				objDhtmlHint.style.top=is.ie ? MousePosition.getBody().scrollTop+event.clientY-objDhtmlHint.offsetHeight-this.dhtmlhint_offsetypoint+"px" : window.pageYOffset+e.clientY-objDhtmlHint.offsetHeight-this.dhtmlhint_offsetypoint+"px"
			}
			else {
				objDhtmlHint.style.top = curY + this.dhtmlhint_offsetypoint+"px"
			}
		}
		else {
			objDhtmlHint.style.left = curX + this.dhtmlhint_offsetxpoint + "px"
			objDhtmlHint.style.top = curY + this.dhtmlhint_offsetypoint+"px"
		}

		objDhtmlHint.style.visibility = "visible"
	}
}

DHtmlHintObject = new DHtmlHintObject();