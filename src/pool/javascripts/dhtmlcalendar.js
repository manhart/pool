/*
 * $Log: dhtmlcalendar.js,v $
 * Revision 1.6  2004/12/07 12:21:09  manhart
 * Fix Datum Zeitstempel uebertragen bei user_function!!!
 *
 * Revision 1.5  2004/11/30 15:59:35  manhart
 * added userclick for callback function
 *
 * Revision 1.4  2004/11/23 10:57:11  manhart
 * -/-
 *
 * Revision 1.3  2004/11/23 08:26:18  manhart
 * Fix in getNextMonat_Jahr(ab 2 Jahre Berechnung falsch gelaufen)
 *
 * Revision 1.2  2004/11/23 07:27:44  manhart
 * dhtmlcalendar refaktoring code
 *
 * Revision 1.1.1.1  2004/09/21 07:49:30  manhart
 * initial import
 *
 * Revision 1.1  2004/09/21 07:18:00  manhart
 * k
 *
 * Revision 1.24  2003/06/06 11:55:37  alex
 * Funktionsverschiebungen
 *
 * Revision 1.23  2003/04/30 16:38:56  alex
 * fix in showTag
 *
 * Revision 1.22  2003/04/29 10:59:28  alex
 * reservierte
 *
 * Revision 1.21  2003/04/14 16:58:13  alex
 * no msg
 *
 * Revision 1.20  2003/04/10 16:23:44  alex
 * verbessert
 *
 * Revision 1.19  2003/03/13 10:55:56  alex
 * function aelterByDate added
 *
 * Revision 1.18  2003/03/12 13:48:51  alex
 * Fehlermeldung für startKalender added
 *
 * Revision 1.17  2003/03/10 17:59:12  alex
 * alterEx
 *
 * Revision 1.16  2003/03/10 13:13:48  alex
 * Fixes, showTag
 *
 * Revision 1.15  2003/03/05 17:23:00  alex
 * Update Kalender (Heute wird beim Selektieren markiert)
 * Update Zeitplaner (Hover Effekte)
 *
 * Revision 1.14  2003/03/04 12:42:54  alex
 * Zeitplaner & Kalender update
 *
 * Revision 1.13  2003/03/04 11:56:23  alex
 * Zeitplaner Implementierung
 *
 * Revision 1.12  2003/03/04 10:27:50  alex
 * kleine Nachbesserungen
 *
 * Revision 1.10  2003/03/04 10:06:45  alex
 * Hintergrundbild Style "no-repeat"
 *
 * Revision 1.8  2003/03/03 17:22:03  alex
 * Termine im Kalender anzeigen (noch mit Bugs)
 *
 * Revision 1.7  2003/03/03 10:33:45  alex
 * Bug fixed
 *
 * Revision 1.6  2003/02/28 17:53:35  alex
 * Fix: Jahreswechsel
 *
 * Revision 1.5  2003/02/28 17:46:42  alex
 * kalender mit hover funktion,
 * termine (nur strukturen)
 *
 * Revision 1.4  2003/02/28 12:57:44  alex
 * update kalender
 *
 * Revision 1.3  2003/02/28 09:37:38  alex
 * update kalender
 *
 * Revision 1.2  2003/02/27 13:30:27  alex
 * kalender
 *
 * Revision 1.1  2003/02/26 17:56:38  alex
 * kalender
 *
 * Revision 1.0  2003/02/25 18:07:08  alex
 * Initial Import
 */


// Heute (span, td)
Now = new Date();
TodayItem = null;

// Selektiertes Datum (span, td)
Selected = null;
SelectedItem = null;


// constants
Monate = new Array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August",
	"September", "Oktober", "November", "Dezember");

Tage = new Array("Mo", "Di", "Mi", "Do", "Fr", "Sa", "So");


/**
* Calendar class
*/
function Calendar(skipMonth, name, silbling, Options) {

	// Properties
	this.jahr = Now.getFullYear();
	this.monat = Now.getMonth();
	if (skipMonth != 0) {
		arr = getNextMonat_Jahr(this.monat, this.jahr, skipMonth);
		this.monat = arr[0];
		this.jahr = arr[1];
	}
	this.tag = Now.getDate();
	this.name = name;
	this.silbling = silbling;		// weiterer Kalender
	this.termine = new Array();
	this.reservierte = new Array();
	this.Options = Options;
	this.Selected = null;
	this.SelectedItem = null;

	this.getDayItems(name)
	this.keepSelectDate = null;
}

	/**
	* Adds SPAN HTML tags
	*/
	Calendar.prototype.getDayItems = function (calendarName)
	{
		var elemParent = getElem("id", 'Span'+calendarName, null);

		this.childs = new Array();
		var spanchilds = new Array();

		findChildTags(spanchilds, elemParent, "SPAN");
		for (var i=0; i < spanchilds.length; i++) {
			this.childs[i] = new CalendarItem(this);
			this.childs[i].assignSpan(spanchilds[i]);
		}
	}


	/**
	* Initialize Calendar (displays days)
	*/
	Calendar.prototype.init = function ()
	{
		// Selected (vorausgewaehlter Tag; z.B. ueber PHP gesetzt):
		var day = parseInt(this.Options['SELECTED_DAY']);
		var month = parseInt(this.Options['SELECTED_MONTH']);
		var year = this.Options['SELECTED_YEAR'];
		if(day > 0 && month >= 0 && year) {
			if(this.silbling == null) {
				this.Selected = new Date(year, month-1, day);
			}
			else {
				Selected = new Date(year, month-1, day);
			}
		}

		updateKalender(this);
		updateControl(this);
	}

	/**
	* Click on Calendar Cell (=Day)
	*/
	Calendar.prototype.cellClick = function (self) {
		// self = TD tag
		if (!this.Options['DISALLOW_MOVING_KALENDER']) {

			var clickable=self.getAttribute('clickable');
			if (clickable==1) {
				// add Style, zu neuer Selection
				var CalItem = new CalendarItem(this);
				CalItem.assignTd(self);
				setSelected(CalItem, true);

				//callZeitplaner(self);
			}

		}
		else {
			alert('Tag ist nicht auswaehlbar!');
		}
	}

	/**
	* MouseOver on Calendar Cell (=Day)
	*/
	Calendar.prototype.cellMouseOver = function (self) {
		// Jahr, Monat und Tag muessen gesetzt sein
		if (self.jahr && self.monat >= 0 && self.tag) {

			// Tag klickbar?
			var clickable=self.getAttribute('clickable');
			if (clickable==1) {
				// Hintergrund
				setBackgroundColor(self, this.Options['SELECTED_BGCOLOR']);

				// Font
				if(this.Options['CSS_CLASS']) {
					setCalendarFontClass(self, this.Options['SELECTED_FONT']);
				}
				else {
					setFontID(self, this.Options['SELECTED_FONT']);
				}

				// Cursor
				setCursor(self, this.Options['CR_HANDPOINT']);
			}
			else {
				setCursor(self, this.Options['CR_DEFAULT']);
			}

		}
		else {
			setBackgroundColor(self, "");
			setCursor(self, this.Options['CR_DEFAULT']);
		}
	}

	/**
	* MouseOut on Calendar Cell (=Day)
	*/
	Calendar.prototype.cellMouseOut = function (self) {
		// alle Backgrounds zurücksetzen (Hover), außnahme selected und today
		var clickable=self.getAttribute('clickable');
		var today=self.getAttribute('today');

		if(clickable == 1) {
			if (!this.getSelectedItem() || self != this.getSelectedItem().td) {
				var font = this.Options['ACTIVE_FONT'];
				if(new Date(self.jahr, self.monat, self.tag).getDay() == 0 && this.Options['SUNDAY_ACTIVE_FONT']) {
					font = this.Options['SUNDAY_ACTIVE_FONT'];
				}

				if(this.Options['CSS_CLASS']) {
					setCalendarFontClass(self, font);
				}
				else {
					setFontID(self, font);
				}

				if(today != 1) {
					setBackgroundColor(self, "");
				}
				else {
					setBackgroundColor(self, this.Options['TODAY_BGCOLOR']);
				}
			}
		}
	}

	/**
	* addCalendarDate, fuegt einen Termin im Kalender ein.
	*/
	Calendar.prototype.addCalendarDate = function(CalDate) {
		this.termine.push(CalDate);
	}

	/**
	 * not yet implemented
	 */
	Calendar.prototype.delCalendarDate = function(CalDate) {
		this.termine.without(CalDate);
/*		for(var i=0; i<this.termine.length; i++) {
			var CurCalDate = this.termine[i];
			if(CurCalDate.isEqual(CalDate)) {
				this.termine.
			}
		}*/
	}

	/**
	 * Selektiert uebergebenes Datum
	 */
	Calendar.prototype.selectDate = function(date) {
		var found = false;
		for(var i=0; i<this.childs.length; i++) {
			var child = this.childs[i];
			var jahr = (child.td && child.td.jahr) ? parseInt(child.td.jahr) : null;
			var monat = (child.td && child.td.monat) ? parseInt(child.td.monat) : null;
			var tag = (child.td && child.td.tag) ? parseInt(child.td.tag) : null;

			if(jahr != null && monat != null && tag != null) {
				if(Number(date) == Number(new Date(jahr, monat, tag))) {
					setSelected(child);
					found = true;
					break;
				}
			}
		}
		if(!found) {
			this.deselect();
			this.keepSelectDate = date;
		}
	}

	/**
	 * Deselektieren des markierten Elements
	 */
	Calendar.prototype.deselect = function() {
		var SelectedItem = this.getSelectedItem();
		if(SelectedItem != null){
			setSelected(SelectedItem, 0, null);
		}
		else {
			SelectedDate = this.getSelectedDate();
			SelectedDate = null;
			this.keepSelectDate = null;
			SelectedItem = null;
		}
	}

	/**
	* Liefert ein Date Object des selektierten Elements
	*/
	Calendar.prototype.getSelectedDate = function() {
		if(this.silbling == null) {
			return this.Selected;
		}
		else {
			return Selected;
		}
	}

	/**
	 * Liefert das Item für den den markierten Tag
	 *
	 * @return CalendarItem
	 */
	Calendar.prototype.getSelectedItem = function() {
		return (this.silbling == null) ? this.SelectedItem : SelectedItem;
	}

	/**
	 * Setzt das Item für den markierten Tag
	 */
	Calendar.prototype.setSelectedItem = function(aCalendarItem) {
		if(this.silbling == null) {
			this.SelectedItem = aCalendarItem;
			if(aCalendarItem) {
				this.Selected = new Date(aCalendarItem.td.jahr, aCalendarItem.td.monat, aCalendarItem.td.tag);
			}
		}
		else {
			SelectedItem = aCalendarItem;
			if(aCalendarItem) {
				Selected = new Date(aCalendarItem.td.jahr, aCalendarItem.td.monat, aCalendarItem.td.tag);
			}
		}
		if(!aCalendarItem) {
			this.Selected = null;
			Selected = null;
		}
	}

/**
* CalendarItem class
*/
function CalendarItem(Calendar) {
	this.Calendar = Calendar;
	this.span = null;
	this.td = null;
}
	/*
	* assignSpan, zuweisen eines Span
	*/
	CalendarItem.prototype.assignSpan = function(obj, check)
	{
		if(typeof(check) == 'undefined') check=true;

		if(obj.tagName.toUpperCase()=='SPAN') {
			if (check==true) {
				var td = getTDfromSPAN(obj);
				if (!td) {
					alert('Sorry, but no TD found.');
					return;
				}
				this.td = td;
			}
			this.span = obj;
		}
	}

	/**
	* assignTd, zuweisen eines Td
	*/
	CalendarItem.prototype.assignTd = function(obj, check)
	{
		if(typeof(check) == 'undefined') check=true;

		if (obj.tagName.toUpperCase()=='TD') {
			if (check==true) {
				var span = getSPANfromTD(obj);
				if (!span) {
					alert('Sorry, but no SPAN found.');
					return;
				}
				this.span = span;
			}

			this.td = obj;
		}
	}

	/**
	* cloneItem
	*/
	CalendarItem.prototype.cloneItem = function() {
		// return cloneObject(this);

		// cloneCalendar = this.Calendar.cloneNode();
		var cloneSpan = this.span.cloneNode();
		var cloneTd = this.td.cloneNode();

		var CalItem = new CalendarItem(this.Calendar);
		CalItem.assignSpan(cloneSpan, false);
		CalItem.assignTd(cloneTd, false);
		return CalItem;
	}

	/**
	 * Wechselt den Font.
	 */
	CalendarItem.prototype.changeFont = function(fontIDorClass) {
		if(this.Calendar.Options['CSS_CLASS']) {
			setCalendarFontClass(this.span, fontIDorClass);
		}
		else {
			setFontID(this.span, fontIDorClass);
		}
	}

	/**
	 * Handelt es sich um einen Sonntag
	 */
	CalendarItem.prototype.isSunday = function() {
		var d = new Date(this.td.jahr, this.td.monat, this.td.tag);
		return (d.getDay()==0);
	}


/**
* CalendarDate class
*/
function CalendarDate(tag, monat, jahr, startzeit, endzeit, imageFull, imageHalf, value, hint)
{
	this.tag = tag;
	this.monat = monat;
	this.jahr = jahr;
	this.startzeit = startzeit;
	this.endzeit = endzeit;
	this.imageFull = imageFull;
	this.imageHalf = imageHalf;
	this.value = value;
	this.hint = hint;
}

	/**
	* getType: full oder half (full=kompletter Tag, half=innerhalb dieses Tages ist ein Termin)
	*/
	CalendarDate.prototype.getType = function()	{
		if (this.startzeit == 0 && this.endzeit == 0) {
			return 'full';
		}
		else {
			return 'half';
		}
	}

	/**
	* getImage: liefer das Termin-Bildchen abhaengig vom Typ "full" oder "half"
	*/
	CalendarDate.prototype.getImage = function() {
		var type=this.getType();
		if(type=='full') return this.imageFull;
		if(type=='half') return this.imageHalf;
		return '';
	}

	/**
	* getValue: zusaetzlicher Wert fuer Termin, z.b. ID (benoetigt bei Formularen mit Kalenderauswahl)
	*/
	CalendarDate.prototype.getValue = function() {
		return this.value;
	}

	/**
	* getHint: Hinweis
	*/
	CalendarDate.prototype.getHint = function() {
		return this.hint;
	}

function getMonat(monat) {
	return Monate[monat];
}

function updateKalender(kalender) {
	var kaldatum = new Date(kalender.jahr, kalender.monat, 1);
	var wochentag = getWochentag(kaldatum);

	// Navigation Elements
	setCont("id", kalender.name + "_jahr", null, kalender.jahr);
	setCont("id", kalender.name + "_monat", null, getMonat(kalender.monat));

	var tagheute = Now.getDate();
	var monatheute = Now.getMonth();
	var jahrheute = Now.getFullYear();

	//if (Termine) {
		//kalender.termine = getAnzeigelisteByMonat(Termine, kalender.jahr, kalender.monat);
	//}
	//if (Reservierte) {
		//kalender.reservierte = getAnzeigelisteByMonat(Reservierte, kalender.jahr, kalender.monat);
	//}
	var rows4cw = new Array();

	var d = 1;
	for (var i=0; i < kalender.childs.length; i++) {
		var child = kalender.childs[i];

		// Hintergrund entfernen
		removeCalendarBackgroundImage(child);
		setBackgroundColor(kalender.childs[i].td, (kalender.Options['DEFAULT_BGCOLOR'])?kalender.Options['DEFAULT_BGCOLOR']:'');

		// KW
		var td_cw = document.getElementById(kalender.name+'_cw'+(Math.ceil((i+1)/7)));

		var rowNr = Math.ceil(((i)-6)/7);

		var childName = kalender.name + "_day" + i;
		var datum = new Date(kalender.jahr, kalender.monat, d);
		if (i >= wochentag && datum.getMonth() == kalender.monat) {
			var day_string = d+'.';
			if(kalender.Options['FORMAT_DAY_CALLBACK']) {
				eval('day_string='+kalender.Options['FORMAT_DAY_CALLBACK']+'('+d+');');
			}
			setContByElem(child.span, day_string);

			//alert(d);
			if(!rows4cw.search(rowNr)) {
				// wenn im Template {NAME}_cw1 ... bis 6 existieren, dann werden KW eingezeichnet

				var date = new Date(kalender.jahr, kalender.monat, d);
				var kw = date.strftime('%V');

				//var kw = getWeekNumber(d, (kalender.monat+1), kalender.jahr);
				setContByElem(td_cw, kw);
				rows4cw.push(rowNr);
			}

			child.td.setAttribute('clickable', 0);
			child.td.setAttribute('today', 0);

			setElemAttr(child.span, kalender.jahr, kalender.monat, d);
			setElemAttr(child.td, kalender.jahr, kalender.monat, d);
			child.span.kalender = kalender;

			// Termine
			var isTermin = false;
			for(var z=0; z<kalender.termine.length; z++) {

				var CalDate = kalender.termine[z];
				if(CalDate.tag == d && (CalDate.monat-1) == kalender.monat && CalDate.jahr == kalender.jahr) {
					var image=CalDate.getImage();
					setBackgroundImage(kalender.childs[i].td, image);
					setBackgroundRepeat(kalender.childs[i].td, "no-repeat");
					setBackgroundPosition(kalender.childs[i].td, (kalender.Options['BG_POSITION'])?kalender.Options['BG_POSITION']:'');
					kalender.childs[i].td.CalDate = CalDate;
					isTermin = true;
					break;
				}

			}
			/*var hasTermin = lookforTermin(kalender.termine, d);
			if (hasTermin) {
				changeBackground(kalender.childs[i], hasTermin);
			}*/

			//var hasReservierung = lookforTermin(kalender.reservierte, d);
			//if (!hasTermin && hasReservierung) {
			//	changeBackground(kalender.childs[i], hasReservierung);
			//}
			var clickable = kalender.Options['CLICKABLE'];

			switch(clickable) {
				case 1: // 1=alle Tage klickbar
					// Tag Heute (klickbar)
					child.td.setAttribute('clickable', 1);

					if (kalender.jahr==jahrheute && kalender.monat==monatheute && tagheute==d) {
						setToday(kalender.childs[i]);
					}
					else {
						// Selected
						var Selected = kalender.getSelectedDate();
						if (Selected && kalender.jahr==Selected.getFullYear() && kalender.monat==Selected.getMonth() &&
								Selected.getDate()==d) {
							setSelected(child);
						}
						else {
							if(child.isSunday() && kalender.Options['SUNDAY_ACTIVE_FONT']) {
								child.changeFont(kalender.Options['SUNDAY_ACTIVE_FONT']);
							}
							else {
								child.changeFont(kalender.Options['ACTIVE_FONT']);
							}
						}
					}
					if(isTermin==true) {
						if (typeof CalDate != 'undefined') child.td.setAttribute('title', CalDate.getHint());
					}
					break;

				case 2: // 2=vergangene Tage nicht klickbar, heutiger Tag und zukuenftige klickbar
					// Tag Heute (klickbar)
					if (kalender.jahr==jahrheute && kalender.monat==monatheute && tagheute==d) {
						child.td.setAttribute('clickable', 1);
						setToday(kalender.childs[i]);
					}
					else {
						// Selected
						var Selected = kalender.getSelectedDate();
						if (Selected && kalender.jahr==Selected.getFullYear() && kalender.monat==Selected.getMonth() &&
								Selected.getDate()==d) {
							setSelected(kalender.childs[i]);
							child.td.setAttribute('clickable', 1);
						}
						else {
							// Zukunft (klickbar)
							if (isZukunft(kalender.jahr, kalender.monat, d)) {
								child.changeFont(kalender.Options['ACTIVE_FONT']);
								child.td.setAttribute('clickable', 1);
							}
							// Vergangenheit (nicht klickbar)
							else {
								child.changeFont(kalender.Options['INACTIVE_FONT']);
							}
						}
					}
					break;

				case 3: // 3=nur Termine klickbar
					// Tag Heute (klickbar)
					if (kalender.jahr==jahrheute && kalender.monat==monatheute && tagheute==d) {
						if(isTermin==true) {
							child.td.setAttribute('isTermin', 1);
							child.td.setAttribute('clickable', 1);
							if (typeof CalDate != 'undefined') child.td.setAttribute('title', CalDate.getHint());
						}
						setToday(child);
					}
					else {
						// Selected
						var Selected = kalender.getSelectedDate();
						if (Selected && kalender.jahr==Selected.getFullYear() && kalender.monat==Selected.getMonth() &&
								Selected.getDate()==d) {
							setSelected(child);
							child.td.setAttribute('isTermin', 1);
							child.td.setAttribute('clickable', 1);
							if (typeof CalDate != 'undefined')
								child.td.setAttribute('title', CalDate.getHint());
						}
						else {
							if (isTermin==true) {
								child.changeFont(kalender.Options['ACTIVE_FONT']);
								child.td.setAttribute('isTermin', 1);
								child.td.setAttribute('clickable', 1);
								if (typeof CalDate != 'undefined')
									child.td.setAttribute('title', CalDate.getHint());
							}
							else {
								child.changeFont(kalender.Options['INACTIVE_FONT']);
								child.td.setAttribute('isTermin', 0);
							}
						}
					}
					break;
			}

			d++;
		}
		else {
			// keine Tage
			setContByElem(kalender.childs[i].span, " ");
			// jahr, monat, tag = 0
			setElemAttr(kalender.childs[i].span, 0, 0, 0);
			setElemAttr(kalender.childs[i].td, 0, 0, 0);

			// kein Hintergrund
			setBackgroundColor(kalender.childs[i].td, "");

			if(!rows4cw.search(rowNr) && td_cw) {
				setContByElem(td_cw, '');
			}
		}
	}
}

function setSelected(child, userclick) {
	var Calendar = child.Calendar;
	var SelectedItem = Calendar.getSelectedItem();

	if (SelectedItem) {
/*		if(child.isSunday() && child.Calendar.Options['SUNDAY_ACTIVE_FONT']) {
			//alert('TEST');
			SelectedItem.changeFont(child.Calendar.Options['SUNDAY_ACTIVE_FONT']);
		}
		else {*/
			SelectedItem.changeFont(child.Calendar.Options['ACTIVE_FONT']);
//		}

		// Revert Heute
		if (TodayItem &&
			(TodayItem.jahr==SelectedItem.span.jahr) &&
			(TodayItem.monat==SelectedItem.span.monat) &&
			(TodayItem.tag==SelectedItem.span.tag)) {

			// setze Hintergrund für Heute
			setBackgroundColor(SelectedItem.td, child.Calendar.Options['TODAY_BGCOLOR']);
		}
		else {
			// entferne Hintergrund
			setBackgroundColor(SelectedItem.td, "");
		}
	}

	if(setSelected.arguments.length == 3) {
		child = setSelected.arguments[2];
	}

	SelectedItem = child;
	Calendar.setSelectedItem(child);

	if (SelectedItem) {
		setBackgroundColor(SelectedItem.td, child.Calendar.Options['SELECTED_BGCOLOR']);
		SelectedItem.changeFont(child.Calendar.Options['SELECTED_FONT']);

		if(setSelected.arguments.length == 1) {
			var userclick=false;
		}

		var user_function=child.Calendar.Options['USER_FUNCTION_CELLCLICK'];
		if(user_function.length>0) {
			var valueCalDate = '';
			CalDate = child.td.CalDate;
			if(CalDate) valueCalDate=CalDate.getValue();
			if(valueCalDate.length==0)	var valueCalDate=child.Calendar.getSelectedDate().getTime();
			eval(user_function+"("+parseInt(valueCalDate)+", "+userclick+")");
		}
	}
}

function setToday(child) {
	// remove style
	if (TodayItem) {
		setBackgroundColor(TodayItem.td, "");
	}

	if (child != null) {
		TodayItem = child;
		// no reference, td wird mitveraendert.
		TodayItem.tag = TodayItem.td.tag;
		TodayItem.monat =TodayItem.td.monat;
		TodayItem.jahr = TodayItem.td.jahr;
	}
	else {
		TodayItem = null;
	}

	// add style
	if (TodayItem && TodayItem.Calendar.monat == TodayItem.monat && TodayItem.Calendar.jahr == TodayItem.jahr) {
		var clickable=TodayItem.td.getAttribute('clickable');
		if(TodayItem.Calendar.Options['TODAY_CALLBACK_ONPAINT']) {
			var callback_onpaint = TodayItem.Calendar.Options['TODAY_CALLBACK_ONPAINT'];
			eval('var result_continue='+callback_onpaint+'(TodayItem, '+clickable+');');
			if(!result_continue) return;
		}
		if(clickable==1) {
			var new_font = TodayItem.Calendar.Options['ACTIVE_FONT'];
		}
		else {
			var new_font = TodayItem.Calendar.Options['INACTIVE_FONT'];
		}
		TodayItem.changeFont(new_font);
		setBackgroundColor(TodayItem.td, child.Calendar.Options['TODAY_BGCOLOR']);
		TodayItem.td.setAttribute('today', 1);
	}
}

function setElemAttr(elem, jahr, monat, tag) {
	if (elem) {
		if (DOM || MS) {
			elem.jahr = jahr;
			elem.monat = monat;
			elem.tag = tag;
		}
	}
}

function setFontID(elem, ID) {
	if (elem) {
		if (elem.nodeName.toUpperCase() == "TD") {
			var elem = getSPANfromTD(elem);
			if (!elem) return false;
		}

		if (elem.parentNode) {
			if (elem.parentNode.nodeName.toUpperCase() == "B" ||
				elem.parentNode.nodeName.toUpperCase() == "FONT") {

				var font = elem.parentNode;
				if ((DOM || MS) && font.id) {
					font.id = ID;
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Alex added on 29.07.2008, 11:14
 * Setzt SPAN ClassName
 */
function setCalendarFontClass(elem, className) {
	if(elem) {
		if (elem.nodeName.toUpperCase() == "TD") {
			var elem = getSPANfromTD(elem);
			if (!elem) return false;
		}

		if(elem.nodeName.toUpperCase()=='SPAN') {
			// kein B und FONT, d.h. SPAN direkt mit Font setzen
			Element.extend(elem);
			elem.removeClassName(elem.classNames());
			elem.addClassName(className);
			return true;
		}
	}
	return false;
}

function getTDfromSPAN(elem) {
	// netscape, opera, ie
	var node = null;
	if (elem && elem.parentNode && elem.parentNode.parentNode) {
		if (elem.parentNode.nodeName.toUpperCase() == "TD") {
			var node = elem.parentNode;
		}
		// falls erster Node font oder b
		if (elem.parentNode.parentNode.nodeName.toUpperCase() == "TD") {
			var node = elem.parentNode.parentNode;
		}
	}
	return node;
}

function getSPANfromTD(elem) {
	var child = null;
	if (elem) {
		if (elem.childNodes) {
			for(var i=0; i<elem.childNodes.length; i++) {
				if (elem.childNodes[i].nodeName.toUpperCase() == "SPAN") {
					var child = elem.childNodes[i];
					return child;
				}
				else {
					var child=getSPANfromTD(elem.childNodes[i]);
				}
			}
		}
	}
	return child;
}

function getWochentag(datetime) {
	// netscape, opera, ie
	wochentag = datetime.getDay() - 1;

	if (wochentag < 0) {
		wochentag = 6;
	}
	return wochentag;
}

function getDefaultKalender() {
	// netscape, opera, ie
	if (!kalender0) {
		alert("Kalender " + "\"kalender0\" nicht gefunden!");
	}
	return kalender0;
}

function updateControl(kal) {
	var control_monat = getElemById(kal.name + "control_monat");
	var control_jahr = getElemById(kal.name + "control_jahr");

	if (kal && control_monat && control_jahr) {
		setContByElem(control_monat, getMonat(kal.monat));
		setContByElem(control_jahr, kal.jahr);
	}
}

function changeMonat(value, kal) {
	if (kal == null) {
		var kal = getDefaultKalender();
		//setToday(null); // setToday ganz am Anfang!!!
		//setSelected(null); // setSelected null
	}

	if (kal) {
		var monat = kal.monat;
		var jahr = kal.jahr;

		arr = getNextMonat_Jahr(monat, jahr, value);

		kal.monat = arr[0];
		kal.jahr = arr[1];

		updateKalender(kal);

		// wird bei silblings unnötig aufgerufen
		updateControl(kal);

		// zu selektierendes versuchtes Datum erneut probieren zu selektieren (im neuen Monat - Change)
		if(kal.keepSelectDate) {
			kal.selectDate(kal.keepSelectDate);
		}
	}

	// changedateDeps
	if (kal.silbling != null) {
		changeMonat(value, kal.silbling);
	}
}

/* value = jump over months */
function getNextMonat_Jahr(monat, jahr, value) {
	monat = monat + value;

	if(value >= 0) {
		buf = parseInt(monat / 12);

		jahr = jahr + buf;
		monat = (monat % 12);
	}
	else {

		buf = parseInt((monat-11) / 12);
		jahr = jahr + buf;

		monat = (monat % 12);
		if(monat < 0) monat += 12;
	}

	if(jahr < 999) jahr += 1900;

	return new Array(monat, jahr);
}

// Diese Funktion sucht alle Span Tags unterhalb eines Elements
function findChildTags(childs, elem, TagName) {
	if (elem && elem.childNodes) {
		for (var i=0; i<elem.childNodes.length; i++) {
			if (elem.childNodes[i]) {
				if ((elem.childNodes[i].nodeType == 1) &&
				  (elem.childNodes[i].nodeName.toUpperCase() == TagName.toUpperCase())) {
					// check attribute jump; überspringe tags mit jump==1
					if (DOM || MS) {
						var jump = elem.childNodes[i].getAttribute("jump");
					}
					else if (NS) {
						if (typeof elem.childNodes[i][0] == "object") {
							var jump = elem.childNodes[0]["jump"];
						}
						else {
							var jump = elem.childNodes["jump"];
						}
					}
					if (jump==1) {
						continue;
					}
					childs.push(elem.childNodes[i]);
				}
				else {
					findChildTags(childs, elem.childNodes[i], TagName);
				}
			}
		}
	}
	else {
		alert("Error occured. JS: Param elem undefined! @see findChildTags()");
	}
}

// akzeptiert Parameter elem oder Kalender; solange die Attr jahr, monat, tag bestehen!
function isHeuteByElem(elem) {
	if (elem) {
		return isHeute(elem.jahr, elem.monat, elem.tag);
	}
	else {
		alert ('isHeute() elem undefiniert!');
		return false;
	}
}

function isHeute(jahr, monat, tag) {
	return (jahr==Now.getFullYear() && monat==Now.getMonth() && tag == Now.getDate())
}

// akzeptiert Parameter SPAN oder Kalender; solange die Attr jahr, monat, tag bestehen!
function isZukunftByElem(elem) {
	if (elem) {
		if (elem.jahr > Now.getFullYear()) {
			return true;
		}
		if (elem.jahr >= Now.getFullYear() && elem.monat > Now.getMonth()) {
			return true;
		}
		if (elem.jahr >= Now.getFullYear() && elem.monat >= Now.getMonth() && elem.tag > Now.getDate()) {
			return true;
		}
		return false;
	}
	else {
		alert ('isZukunftByElem() elem undefiniert!');
		return false;
	}
}

function isZukunft(jahr, monat, tag) {
	var fullyear = Now.getFullYear();
	var monatheute = Now.getMonth();

	if (jahr > fullyear) {
		return true;
	}
	if (jahr >= fullyear && monat > monatheute) {
		return true;
	}
	if (jahr >= fullyear && monat >= monatheute && tag > Now.getDate()) {
		return true;
	}
	return false;
}

function removeCalendarBackgroundImage(child) {
	if(child.Calendar.Options['DAY_CALLBACK_ONRELEASE']) {
		var clickable = child.td.getAttribute('clickable');
		var callback_onrelease = child.Calendar.Options['DAY_CALLBACK_ONRELEASE'];
		eval('var result_continue='+callback_onrelease+'(child, '+clickable+');');
		if(!result_continue) return;
	}

	if (window.netscape) {
		setBackgroundImage(child.td, "images/pl.gif");
	}
	else {
		setBackgroundImage(child.td, "");

	}
}

/*
function lookforTermin(list, tag) {
	var value = false;

	for (var i=0; i<list.length; i++) {
		var tagStruct = findeTag(list[i].tage, tag);
		if (tagStruct) {
			if (tagStruct.startzeit == 0 && tagStruct.endzeit == 0) {
				return 'full';
			}
			else {
				value = 'half';
			}
		}
	}
	return value;
}


function changeBackground(child, type) {
	if (child) {
		if (child.td != null) {
			if (type=='full') {
				setBackgroundImage(child.td, child.Calendar.Options['BG_SRC_FULL']);
			}
			if (type=='half') {
				setBackgroundImage(child.td, child.Calendar.Options['BG_SRC_HALF']);
			}
			if (type=='del') {
				if (window.netscape) {
					setBackgroundImage(child.td, "images/pl.gif");
				}
				else {
					setBackgroundImage(child.td, "");
				}
			}

			setBackgroundRepeat(child.td, "no-repeat");
			return true;
		}
	}
	return false;
}
*/

function getKalender(kal, jahr, monat) {
	if (kal == null) {
		var kal = getDefaultKalender();
	}
	if (kal) {
		if (kal.jahr == jahr && kal.monat == monat) {
			return kal;
		}
		else if (kal.silbling != null) {
			return getKalender(kal.silbling, jahr, monat);
		}
		else {
			return false;
		}
	}
	else {
		alert("Fehler aufgetreten! Kalender nicht gefunden in getKalender()");
	}
}


function moveKalender(kal, datum) {
	var dkal = getDefaultKalender();
	if (dkal) {
		// falls
		if ((dkal.jahr > datum.getFullYear) || (dkal.jahr == datum.getFullYear() && dkal.monat > datum.getMonth())) {
			changeMonat(-1);
		}
		else {
			changeMonat(+1);
		}
	}
	showTag(datum);
}

function showTag(datum) {
	var kal = getKalender(null, datum.getFullYear(), datum.getMonth());

	if (!kal) {
		moveKalender(null, datum);
	}
	else {
		for (var i=0; i<kal.childs.length; i++) {
			var child = kal.childs[i];
			if (child.span.jahr == datum.getFullYear() && child.span.monat == datum.getMonth() &&
				child.span.tag == datum.getDate()) {

				setSelected(child);

				break;
			}
		}
	}
}

// Schaut, ob das 2. übergebene Datum älter ist.
function aelter(jahr, monat, tag, jahr2, monat2, tag2) {
	var datum1 = new Date(jahr, monat, tag+1);
	var datum2 = new Date(jahr2, monat2, tag2);

	return (datum2 >= datum1);
}

function aelterByDate(datum1, datum2) {
	return (datum2 < datum1);
}

// Schaut, ob das 2. übergebene Datum älter ist.
function aelterEx(jahr, monat, tag, stunde, minute, sekunde,
  jahr2, monat2, tag2, stunde2, minute2, sekunde2) {
	var datum1 = new Date(jahr, monat, tag, stunde, minute, sekunde);
	var datum2 = new Date(jahr2, monat2, tag2, stunde2, minute2, sekunde2);

	return (datum2 > datum1);
}


function setBackgroundColor(elem, color) {
	if (elem.style.backgroundColor != color) {
		elem.style.backgroundColor = color;
	}
}

function setBackgroundImage(elem, url) {
	if (elem.style.backgroundImage != "url(" + url + ")") {
		elem.style.backgroundImage = "url(" + url + ")";
	}
}

function setBackgroundRepeat(elem, value) {
	if (elem.style.backgroundRepeat != value) {
		elem.style.backgroundRepeat = value;
	}
}

function setBackgroundPosition(elem, value) {
	if (elem.style.backgroundPosition != value && value != '') {
		elem.style.backgroundPosition = value;
	}
}

function setCursor(elem, cursor) {
	if (elem.style.cursor != cursor) {
		elem.style.cursor = cursor;
	}
}


			/* TOOLS */
// @not used
function getStyleObj(elem, parent) {
	if (document.layers) {
		if (parent) {
			return "document."+parent+".document."+elem;
		}
	    else {
			return "document."+elem + ".style";
		}

	  }
	    else if (document.all) {
		return "document.all."+elem + ".style";
	  }
	    else if (document.getElementById) {
		return "document.getElementById('"+elem+"').style";

	}
}


/* DHTML-Bibliothek */

var DHTML = 0, DOM = 0, MS = 0, NS = 0, OP = 0;

function DHTML_init() {

 if (window.opera) {
     OP = 1;
 }
 if(document.getElementById) {
   DHTML = 1;
   DOM = 1;
 }
 if(document.all && !OP) {
   DHTML = 1;
   MS = 1;
 }
if (window.netscape && window.screen && !DOM && !OP) {
   DHTML = 1;
   NS = 1;
 }
}

function getElem(p1,p2,p3) {
 var Elem;
 if(DOM) {
   if(p1.toLowerCase()=="id") {
     if (typeof document.getElementById(p2) == "object")
     Elem = document.getElementById(p2);
     else Elem = void(0);
     return(Elem);
   }
   else if(p1.toLowerCase()=="name") {
     if (typeof document.getElementsByName(p2) == "object")
     Elem = document.getElementsByName(p2)[p3];
     else Elem = void(0);
     return(Elem);
   }
   else if(p1.toLowerCase()=="tagname") {
     if (typeof document.getElementsByTagName(p2) == "object" || (OP && typeof document.getElementsByTagName(p2) == "function"))
     Elem = document.getElementsByTagName(p2)[p3];
     else Elem = void(0);
     return(Elem);
   }
   else return void(0);
 }
 else if(MS) {
   if(p1.toLowerCase()=="id") {
     if (typeof document.all[p2] == "object")
     Elem = document.all[p2];
     else Elem = void(0);
     return(Elem);
   }
   else if(p1.toLowerCase()=="tagname") {
     if (typeof document.all.tags(p2) == "object")
     Elem = document.all.tags(p2)[p3];
     else Elem = void(0);
     return(Elem);
   }
   else if(p1.toLowerCase()=="name") {
     if (typeof document[p2] == "object")
     Elem = document[p2];
     else Elem = void(0);
     return(Elem);
   }
   else return void(0);
 }
 else if(NS) {
   if(p1.toLowerCase()=="id" || p1.toLowerCase()=="name") {
   if (typeof document[p2] == "object")
     Elem = document[p2];
     else Elem = void(0);
     return(Elem);
   }
   else if(p1.toLowerCase()=="index") {
    if (typeof document.layers[p2] == "object")
     Elem = document.layers[p2];
    else Elem = void(0);
     return(Elem);
   }
   else return void(0);
 }
}

function getElemById(name) {
	return getElem("id", name, null);
}

function getCont(p1,p2,p3) {
   var Cont;
   if(DOM && getElem(p1,p2,p3) && getElem(p1,p2,p3).firstChild) {
     if(getElem(p1,p2,p3).firstChild.nodeType == 3)
       Cont = getElem(p1,p2,p3).firstChild.nodeValue;
     else
       Cont = "";
     return(Cont);
   }
   else if(MS && getElem(p1,p2,p3)) {
     Cont = getElem(p1,p2,p3).innerText;
     return(Cont);
   }
   else return void(0);
}

function getAttr(p1,p2,p3,p4) {
	var Attr;
	if((DOM || MS) && getElem(p1,p2,p3)) {
		Attr = getElem(p1,p2,p3).getAttribute(p4);
		return(Attr);
	}
	else if (NS && getElem(p1,p2)) {
		if (typeof getElem(p1,p2)[p3] == "object")
			Attr=getElem(p1,p2)[p3][p4]
		else
			Attr=getElem(p1,p2)[p4]
		return Attr;
	}
	else return void(0);
}

function setCont(p1,p2,p3,p4) {
	var elem = getElem(p1,p2,p3);
	if (elem) {
		if(DOM && elem.firstChild) {
			elem.firstChild.nodeValue = p4;
		}
		else if(MS) {
			elem.innerText = p4;
		}
		else if(NS) {
			elem.document.open();
			elem.document.write(p4);
			elem.document.close();
		}
	}
}

function setContByElem(elem, value) {
	if (elem) {
		if(DOM) {
			elem.innerHTML = value;
		}
		else if(MS) {
			elem.innerText = value;
		}
		else if(NS) {
			elem.document.open();
			elem.document.write(value);
			elem.document.close();
		}
	}
}

function TextRezult (textB, textA, textS, pos) {
	this.textBefore=textB;
	this.textAfter=textA;
	this.textSearch=textS;
	this.textPos = pos;
}

function TextAttribute( textA, textV ) {
	this.textName = textA;
	this.textValue = textV;
}

function splitText(p4,searchStr) {
	var rez = new TextRezult;
	rez.textPos=p4.indexOf(searchStr);
	if ( rez.textPos == -1 ) {
		rez.textBefore = "";
		rez.textAfter = p4;
	}
	else {
		rez.textBefore = p4.substr(0,rez.textPos);
		rez.textAfter = p4.substr(rez.textPos+searchStr.length,p4.length-(rez.textPos+searchStr.length));
	}
	rez.textSearch = searchStr;
	//alert("splitText:= "+rez.textPos+" , '"+rez.textBefore+"' , '"+rez.textSearch+"' , '"+rez.textAfter+"'");
	return rez;
};

function makeAttribute( text ) {
	var rez = new TextAttribute("","");
	var splitrez = new TextRezult("","","",-1);
   	splitrez = splitText(text,"=");
	if ( splitrez.textPos!=-1 ) {
	   	rez.textName = splitrez.textBefore;
		rez.textValue = splitrez.textAfter.substr(1,splitrez.textAfter.length-2);
	}
	else {
		rez.textName = splitrez.textAfter;
		rez.textValue = "tag";
	}
	return rez;
}

////////////////////////////////////////////////////////////////////
// function createDOMfromHTML(p4) ver. 1.0
// Copyright (c) 2002 by Andre Mueller.
// This JavaScript-function is open source. You can redistribute it and/or modify
// it under the terms of the Universal General Public License (UGPL).
// http://home.t-online.de/home/aam_int/de/impressum/ugpl.html

function createDOMfromHTML(p4) {
	var rez = document.createElement("span");
	var i=0, tagflag=0;
	var myElements = new Array ();
	var myValues = new Array ();
	var myAttributes = new Array ();
	var myTextRezult = new TextRezult("","","",-1);
	var myAttribRezult = new TextRezult("","","",-1);
	var myAttribute = new TextAttribute("","");
	var Attr = new TextAttribute("","");
	myTextRezult.textAfter = p4;
	while(1==1) {
		if ( tagflag==0 ) {
			// ### suche den ersten HTML-Tag
			myTextRezult = splitText(myTextRezult.textAfter,"<");
			//alert("Tag / Rezult== "+tagflag+","+myTextRezult.textPos+" , '"+myTextRezult.textBefore+"' , '"+myTextRezult.textSearch+"' , '"+myTextRezult.textAfter+"'");
			if ( myTextRezult.textPos ==-1 ) {
				//alert("span-Element created := "+"span");
				myElements.push(document.createElement("span"));
				//alert("TextNode created:='"+myTextRezult.textAfter+"'");
				myValues.push(document.createTextNode(myTextRezult.textAfter));
				break;
			}
			else {
				tagflag=1;
				//alert("span-Element created := "+"span");
				myElements.push(document.createElement("span"));
				//alert("TextNode created:='"+myTextRezult.textBefore+"'");
				myValues.push(document.createTextNode(myTextRezult.textBefore));
			}
		}
		else {
			// ### jetzt das Tag-Ende suchen
			myTextRezult = splitText(myTextRezult.textAfter,">");
			//alert("Tag / Rezult== "+tagflag+","+myTextRezult.textPos+" , '"+myTextRezult.textBefore+"' , '"+myTextRezult.textSearch+"' , '"+myTextRezult.textAfter+"'");
			if ( myTextRezult.textPos==-1 ) {
				//alert("span-Element created := "+"span");
				myElements.push(document.createElement("span"));
				myValues.push(document.createTextNode("Fehler : Tagname nicht zu ende!"));
				break;
			}
			else {
				// erst mal das Array mit den attributen löschen
				while(myAttributes.length > 0) myAttributes.pop();
				// jetzt müssen noch die Attribute aus dem gesamten Tag extrahiert werden !
				myAttribRezult.textAfter = myTextRezult.textBefore;
				myAttribRezult = splitText(myAttribRezult.textAfter," ");
				while (1==1) {
					if ( myAttribRezult.textPos!=-1 ) {
						myAttributes.push(makeAttribute(myAttribRezult.textBefore));
						myAttribRezult = splitText(myAttribRezult.textAfter," ");
					}
					else {
						myAttributes.push(makeAttribute(myAttribRezult.textAfter));
						break;
					}
				}
				tagflag=0;
				// setzt voraus, dass der Tag an Anfang steht und die Attribute erst danach kommen
				//alert("Element created:='"+myAttributes[0].textName+"'");
				myElements.push(document.createElement(myAttributes[0].textName));
				for(i=1; i<myAttributes.length; i++) {
					//alert("Attribut created:='"+myAttributes[i].textName+"'");
	 				myElements[myElements.length-1].setAttribute(myAttributes[i].textName, myAttributes[i].textValue);
				}
				myTextRezult = splitText(myTextRezult.textAfter,"</"+myAttributes[0].textName+">",null);
				//alert("Tag / Rezult== "+tagflag+","+myTextRezult.textPos+" , '"+myTextRezult.textBefore+"' , '"+myTextRezult.textSearch+"' , '"+myTextRezult.textAfter+"'");
				if ( myTextRezult.textPos==-1 ) {
					myValues.push(document.createTextNode("Fehler : Tag nicht geschlossen!"));
					break;
				}
				else {
					// ### alt, ohne rekursion ###
					//alert("TextNode created:='"+myTextRezult.textBefore+"'");
					// myValues.push(document.createTextNode(myTextRezult.textBefore));
					// ### NEU, mit rekursion ###
					myValues.push(createDOMfromHTML(myTextRezult.textBefore));
				}
			}
		}
	}
	// fill in the DOM-tree
	for( i=0; i<myElements.length; i++ ) {
		myElements[i].appendChild(myValues[i]);
		rez.appendChild(myElements[i]);
	}
	return rez;
}

// End of UGPL-Object
////////////////////////////////////////////////////////////////////////

function setHtml(p1,p2,p3,p4) {
	if(DOM && getElem(p1,p2,p3) && getElem(p1,p2,p3).firstChild) {
		//alert("DOM-action");
		while( getElem(p1,p2,p3).hasChildNodes()==true ) {
			var myNode = getElem(p1,p2,p3).firstChild
			getElem(p1,p2,p3).removeChild(myNode);
		};
		// ### write new content from the tree
		getElem(p1,p2,p3).appendChild(createDOMfromHTML(p4));
		// !!! alt !!! getElem(p1,p2,p3).firstChild.nodeValue = p4;
	}
	else if(MS && getElem(p1,p2,p3)) {
		//alert("MS-Document.all-Action");
		getElem(p1,p2,p3).innerHTML = p4;
	}
	else if(NS && getElem(p1,p2,p3)) {
		//alert("NS-Layer-Action");
		getElem(p1,p2,p3).document.open();
		getElem(p1,p2,p3).document.write(p4);
		getElem(p1,p2,p3).document.close();
	}
}

DHTML_init();