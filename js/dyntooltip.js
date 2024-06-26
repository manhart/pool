/*
 Pleas leave this notice.
 DHTML tip message version 1.2 copyright Essam Gamal 2003 (http://migoicons.tripod.com, migoicons@hotmail.com)
 All modifications are done in the style.js you should not modify this file.  Created on : 06/03/2003
 Script featured on and can be found at Dynamic Drive (http://www.dynamicdrive.com)

 modified 25.09.2003
 by Alexander Manhart (www.misterelsa.de)

 Script now needs browser.js
*/

var ps = navigator.productSub;
var sNav = (is.ns4 || is.ns6 || is.ie || is.opera) ? 1 : 0;
var cssFilters = ((is.ie55 || is.ie6) && !is.opera) ? 1 : 0;
var DynToolTipStyle=[], DynToolTipText=[], Count=0, sbw=0, move=0, hs="", mx, my, scl, sct, ww, wh, obj, sl, st, ih, iw, vl, hl, sv, evlh, evlw, tbody;
var HideTip = "eval(obj + sv + hl + ';' + obj + sl + '=0;' + obj + st + '=-800')";
var doc_root = (is.ie && document.compatMode=="CSS1Compat") ? "document.documentElement" : "document.body"
var PX = (is.ns6) ? "px" : "";

if(sNav) {
	window.onresize = ReloadTip
	document.onmousemove = MoveTip
	if(is.ns4) document.captureEvents(Event.MOUSEMOVE)
}

if(is.ns4 || is.ns6) {
	mx = "e.pageX"
	my = "e.pageY"
	scl = "window.pageXOffset"
	sct = "window.pageYOffset"
	if(is.ns4) {
		obj = "document.ToolTipLayer."
		sl = "left"
		st = "top"
		ih = "clip.height"
		iw = "clip.width"
		vl = "'show'"
		hl = "'hide'"
		sv = "visibility="
	}
	else obj = "document.getElementById('ToolTipLayer')."
}

if(is.ie || is.opera) {
	obj = "ToolTipLayer."
	mx = "event.clientX"
	my = "event.clientY"
	scl = "eval(doc_root).scrollLeft"
	sct = "eval(doc_root).scrollTop"
	if(is.dom) {
		mx = mx+"+"+scl
		my = my+"+"+sct
	}
}

if(is.def) {
	sl = "style.left"
	st = "style.top"
	ih = "offsetHeight"
	iw = "offsetWidth"
	vl = "'visible'"
	hl = "'hidden'"
	sv = "style.visibility="
}

if(is.ie || is.opera || ps >= 20020823) {
	ww = "eval(doc_root).clientWidth"
	wh = "eval(doc_root).clientHeight"
}
else {
	ww = "window.innerWidth"
	wh = "window.innerHeight"
	evlh = eval(wh)
	evlw = eval(ww)
	sbw=15
}

function applyCssFilter() {
	if(cssFilters && FiltersEnabled) {
		var dx = " progid:DXImageTransform.Microsoft.";
		ToolTipLayer.style.filter = "revealTrans()"+dx+"Fade(Overlap=1.00 enabled=0)"+dx+"Inset(enabled=0)"+dx+"Iris(irisstyle=PLUS,motion=in enabled=0)"+dx+"Iris(irisstyle=PLUS,motion=out enabled=0)"+dx+"Iris(irisstyle=DIAMOND,motion=in enabled=0)"+dx+"Iris(irisstyle=DIAMOND,motion=out enabled=0)"+dx+"Iris(irisstyle=CROSS,motion=in enabled=0)"+dx+"Iris(irisstyle=CROSS,motion=out enabled=0)"+dx+"Iris(irisstyle=STAR,motion=in enabled=0)"+dx+"Iris(irisstyle=STAR,motion=out enabled=0)"+dx+"RadialWipe(wipestyle=CLOCK enabled=0)"+dx+"RadialWipe(wipestyle=WEDGE enabled=0)"+dx+"RadialWipe(wipestyle=RADIAL enabled=0)"+dx+"Pixelate(MaxSquare=35,enabled=0)"+dx+"Slide(slidestyle=HIDE,Bands=25 enabled=0)"+dx+"Slide(slidestyle=PUSH,Bands=25 enabled=0)"+dx+"Slide(slidestyle=SWAP,Bands=25 enabled=0)"+dx+"Spiral(GridSizeX=16,GridSizeY=16 enabled=0)"+dx+"Stretch(stretchstyle=HIDE enabled=0)"+dx+"Stretch(stretchstyle=PUSH enabled=0)"+dx+"Stretch(stretchstyle=SPIN enabled=0)"+dx+"Wheel(spokes=16 enabled=0)"+dx+"GradientWipe(GradientSize=1.00,wipestyle=0,motion=forward enabled=0)"+dx+"GradientWipe(GradientSize=1.00,wipestyle=0,motion=reverse enabled=0)"+dx+"GradientWipe(GradientSize=1.00,wipestyle=1,motion=forward enabled=0)"+dx+"GradientWipe(GradientSize=1.00,wipestyle=1,motion=reverse enabled=0)"+dx+"Zigzag(GridSizeX=8,GridSizeY=8 enabled=0)"+dx+"Alpha(enabled=0)"+dx+"Dropshadow(OffX=3,OffY=3,Positive=true,enabled=0)"+dx+"Shadow(strength=3,direction=135,enabled=0)"
	}
}

function showDynToolTip(t, s) {
  if(sNav) {
  	if(t.length<2||s.length<25) {
		var ErrorNotice = "DYNTOOLTIP MESSAGE VERSION 1.3 ERROR NOTICE.\n";
		if (t.length < 2 && s.length < 25) {
			alert (ErrorNotice + "It looks like you removed an entry or more from the Style Array and Text Array of this tip.\nTheir should be 25 entries in every Style Array even though empty and 2 in every Text Array. You defined only " + s.length + " entries in the Style Array and "+t.length+" entry in the Text Array. This tip wont be viewed to avoid errors");
		}
		else if(t.length<2) {
			alert(ErrorNotice+"It looks like you removed an entry or more from the Text Array of this tip.\nTheir should be 2 entries in every Text Array. You defined only "+t.length+" entry. This tip wont be viewed to avoid errors.");
		}
		else if(s.length<25) {
			alert(ErrorNotice+"It looks like you removed an entry or more from the Style Array of this tip.\nTheir should be 25 entries in every Style Array even though empty. You defined only "+s.length+" entries. This tip wont be viewed to avoid errors.")
		}
 	}
  	else {
		var ab = ""; var ap = ""
		var titCol = (s[0])? "COLOR='"+s[0]+"'" : ""
		var txtCol = (s[1])? "COLOR='"+s[1]+"'" : ""
		var titBgCol = (s[2])? "BGCOLOR='"+s[2]+"'" : ""
		var txtBgCol = (s[3])? "BGCOLOR='"+s[3]+"'" : ""
		var titBgImg = (s[4])? "BACKGROUND='"+s[4]+"'" : ""
		var txtBgImg = (s[5])? "BACKGROUND='"+s[5]+"'" : ""
		var titTxtAli = (s[6] && s[6].toLowerCase()!="left")? "ALIGN='"+s[6]+"'" : ""
		var txtTxtAli = (s[7] && s[7].toLowerCase()!="left")? "ALIGN='"+s[7]+"'" : ""
		var add_height = (s[15])? "HEIGHT='"+s[15]+"'" : ""
		if(!s[8])  s[8] = "Verdana,Arial,Helvetica"
		if(!s[9])  s[9] = "Verdana,Arial,Helvetica"
		if(!s[12]) s[12] = 1
		if(!s[13]) s[13] = 1
		if(!s[14]) s[14] = 200
		if(!s[16]) s[16] = 0
		if(!s[17]) s[17] = 0
		if(!s[18]) s[18] = 10
		if(!s[19]) s[19] = 10
		hs = s[11].toLowerCase()
		if(ps==20001108) {
			if(s[2]) ab="STYLE='border:"+s[16]+"px solid"+" "+s[2]+"'"
			ap="STYLE='padding:"+s[17]+"px "+s[17]+"px "+s[17]+"px "+s[17]+"px'";
		}
		var closeLink = (hs=="sticky")? "<TD ALIGN='right'><FONT SIZE='"+s[12]+"' FACE='"+s[8]+"'><A HREF='javascript:void(0)' ONCLICK='stickyhide()' STYLE='text-decoration:none;color:"+s[0]+"'><B>Schlie&szlig;en</B></A></FONT></TD>":""
		var title = (t[0] || hs=="sticky")? "<TABLE WIDTH='100%' BORDER='0' CELLPADDING='0' CELLSPACING='0'><TR><TD "+titTxtAli+"><FONT SIZE='"+s[12]+"' FACE='"+s[8]+"' "+titCol+"><B>"+t[0]+"</B></FONT></TD>"+closeLink+"</TR></TABLE>" : ""
		var txt = "<TABLE "+titBgImg+" "+ab+" WIDTH='"+s[14]+"' BORDER='0' CELLPADDING='"+s[16]+"' CELLSPACING='0' "+titBgCol+" ><TR><TD>"+title+"<TABLE WIDTH='100%' "+add_height+" BORDER='0' CELLPADDING='"+s[17]+"' CELLSPACING='0' "+txtBgCol+" "+txtBgImg+"><TR><TD "+txtTxtAli+" "+ap+" VALIGN='top'><FONT SIZE='"+s[13]+"' FACE='"+s[9]+"' "+txtCol +">"+t[1]+"</FONT></TD></TR></TABLE></TD></TR></TABLE>"
		if(is.ns4) {
			with(eval(obj+"document")) {
				open()
				write(txt)
				close()
			}
		}
		else eval(obj+"innerHTML=txt");
		tbody = {
			Pos:s[10].toLowerCase(),
			Xpos:s[18],
			Ypos:s[19],
			Transition:s[20],
			Duration:s[21],
			Alpha:s[22],
			ShadowType:s[23].toLowerCase(),
			ShadowColor:s[24],
			Width:parseInt(eval(obj+iw)+3+sbw)
		}
		if(is.ie4) {
			ToolTipLayer.style.width = s[14]
	 		tbody.Width = s[14]
		}
		Count=0
		move=1
 	 }
  }
}

function MoveTip(e) {
	if(move) {
		var X,Y,MouseX = eval(mx),MouseY = eval(my); tbody.Height = parseInt(eval(obj+ih)+3)
//		alert(MouseX);
		tbody.wiw = parseInt(eval(ww+"+"+scl)); tbody.wih = parseInt(eval(wh+"+"+sct))
		switch(tbody.Pos) {
			case "left" :
				X=MouseX-tbody.Width-tbody.Xpos; Y=MouseY+tbody.Ypos;
				break;

			case "center":
				X=MouseX-(tbody.Width/2); Y=MouseY+tbody.Ypos;
				break;

			case "float":
				X=tbody.Xpos+eval(scl); Y=tbody.Ypos+eval(sct);
				break;

			case "fixed":
				X=tbody.Xpos; Y=tbody.Ypos;
				break;

			default:
				X=MouseX+tbody.Xpos; Y=MouseY+tbody.Ypos
		}

		if(tbody.wiw<tbody.Width+X) X = tbody.wiw-tbody.Width
		if(tbody.wih<tbody.Height+Y+sbw) {
			if(tbody.Pos=="float"||tbody.Pos=="fixed") Y = tbody.wih-tbody.Height-sbw
			else Y = MouseY-tbody.Height
		}
		if(X<0) X=0
		eval(obj+sl+"=X+PX;"+obj+st+"=Y+PX")
//		alert(X + 'vs' + Y);
		ViewTip()
	}
}

function ViewTip() {
  	Count++
	if(Count == 1) {
		if(cssFilters && FiltersEnabled) {
			for(Index=28; Index<31; Index++) { ToolTipLayer.filters[Index].enabled = 0 }
			for(s=0; s<28; s++) { if(ToolTipLayer.filters[s].status == 2) ToolTipLayer.filters[s].stop() }
			if(tbody.Transition == 51) tbody.Transition = parseInt(Math.random()*50)
			var applyTrans = (tbody.Transition>-1&&tbody.Transition<24&&tbody.Duration>0)? 1:0
			var advFilters = (tbody.Transition>23&&tbody.Transition<51&&tbody.Duration>0)? 1:0
			var which = (applyTrans)?0:(advFilters)? tbody.Transition-23:0
			if(tbody.Alpha>0&&tbody.Alpha<100) {
	  			ToolTipLayer.filters[28].enabled = 1
	  			ToolTipLayer.filters[28].opacity = tbody.Alpha
			}
			if(tbody.ShadowColor&&tbody.ShadowType == "simple") {
	  			ToolTipLayer.filters[29].enabled = 1
	  			ToolTipLayer.filters[29].color = tbody.ShadowColor
			}
			else if(tbody.ShadowColor&&tbody.ShadowType == "complex") {
	  			ToolTipLayer.filters[30].enabled = 1
	  			ToolTipLayer.filters[30].color = tbody.ShadowColor
			}
			if(applyTrans||advFilters) {
				eval(obj+sv+hl)
	  			if(applyTrans) ToolTipLayer.filters[0].transition = tbody.Transition
	  			ToolTipLayer.filters[which].duration = tbody.Duration
	  			ToolTipLayer.filters[which].apply()
			}
		}
 		eval(obj+sv+vl)
		if(cssFilters&&FiltersEnabled&&(applyTrans||advFilters)) ToolTipLayer.filters[which].play()
		if(hs == "sticky") move=0
  	}
}

function stickyhide() {
	eval(HideTip)
}

function ReloadTip() {
	if(is.ns4 && (evlw!=eval(ww)||evlh!=eval(wh))) {
	 	location.reload()
	}
	else if(hs == "sticky") eval(HideTip)
}

function hideDynToolTip() {
	if(sNav) {
		if(hs!="keep") {
			move=0;
			if(hs!="sticky") eval(HideTip)
		}
	}
}