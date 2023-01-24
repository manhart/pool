function emoticon(elemName, text) {
	var elem = document.getElementById(elemName);
	
	text = ' ' + text + ' ';
	if (elem.createTextRange && elem.caretPos) {
		var caretPos = elem.caretPos;
		caretPos.text = caretPos.text.charAt(caretPos.text.length - 1) == ' ' ? text + ' ' : text;

		elem.focus();
	} 
	else {
		elem.value  += text;
		elem.focus();
	}
}