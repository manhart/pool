function DayBar(name) {
	this.elemDayBar = document.getElementById('dayBar' + name);
	this.gifExtension = '.gif';
	this.onclick = false;
	this.onbeforeclick = false;
	this.name = name;
}
DayBar.prototype.getName = function()
{
	return this.name;
}
DayBar.prototype.unselectDayImage = function(element)
{
	var posUnderscore = element.src.lastIndexOf('_');
	if(posUnderscore != false && element.getAttribute('selected') == 1) {
		var new_src = new String();
		new_src = element.src.substr(0, posUnderscore);
		element.src=new_src + this.gifExtension;
		element.setAttribute('selected', 0);
	}
}
DayBar.prototype.selectDayImage = function(element)
{
	var posDot = element.src.lastIndexOf('.');
	if(posDot != false && element.getAttribute('selected') == 0) {
		var new_src = new String();
		new_src = element.src.substr(0, posDot);
		element.src=new_src + '_choosen' + this.gifExtension;
		element.setAttribute('selected', 1);
	}
}
DayBar.prototype.clickDayBar = function(clickedElement)
{
	var result = 1;
	if(this.onbeforeclick) {
		eval("result="+this.onbeforeclick);
	}

	if(result) {
		var isSelected = (clickedElement.getAttribute('selected') == 1);

		if(isNaN(parseInt(this.elemDayBar.value))) {
			alert('Fehler in dem Value der DayBar. Es wurde keine Integer Zahl für DayBarValue übergeben! Bug im Programmcode...');
			return;
		}

		if(isSelected == 1) {
			this.elemDayBar.value = parseInt(this.elemDayBar.value) - parseInt(clickedElement.getAttribute('value'));
			this.unselectDayImage(clickedElement);
		}
		else {
			this.elemDayBar.value = parseInt(this.elemDayBar.value) + parseInt(clickedElement.getAttribute('value'));
			this.selectDayImage(clickedElement);
		}

		if(this.onclick) {
			eval(this.onclick);
		}
	}
}
DayBar.prototype.prepareDayBar = function()
{
	var dayBarValue = this.elemDayBar.value;
	var dayImages = document.getElementsByName('days' + this.getName());
	for(var i=0; i<dayImages.length; i++) {
		if(parseInt(dayImages[i].getAttribute('value')) & dayBarValue) {
			this.selectDayImage(dayImages[i]);
		}
		else {
			this.unselectDayImage(dayImages[i]);
		}
	}
}
DayBar.prototype.isSelected = function() {
	return (this.elemDayBar.value > 0);
}
DayBar.prototype.addEventOnClick = function(onclick) {
	this.onclick = onclick;
}
DayBar.prototype.addEventOnBeforeClick = function(onbeforeclick) {
	this.onbeforeclick = onbeforeclick;
}
DayBar.prototype.reset = function() {
	this.elemDayBar.value = 0;
	this.prepareDayBar();
}