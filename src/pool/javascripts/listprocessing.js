/*
	List-processing functions

	implode 		Verbindet Array Elemente zu einem String
	explode:		Zerteilt einen String anhand eines Trennzeichens

	$Log: listprocessing.js,v $
	Revision 1.3  2004/12/03 07:42:14  manhart
	fix Javascript error in var named "char"

	Revision 1.2  2004/09/27 16:12:55  manhart
	Fix: explode

	Revision 1.1  2004/09/23 13:24:33  manhart
	Initial Import (includes Javascript implode and explode)


	@version $Id: listprocessing.js,v 1.3 2004/12/03 07:42:14 manhart Exp $
	@version $Revision: 1.3 $
	@version

	@since 2003-08-21
	@author Alexander Manhart <alexander.manhart@freenet.de>
	@link http://www.misterelsa.de
*/

function implode(arr, separator) {
	fixedImplode = "";
	separator = new String(separator);
	if (separator == "undefined") {
		separator = " ";
	}
	for (x = 0; x < arr.length; x++) {
		fixedImplode += (separator + String(arr[x]));
	}

	fixedImplode = fixedImplode.substring(separator.length, fixedImplode.length);
	return fixedImplode;
}

function explode(inputstring, separators, includeEmpties) {
	inputstring = new String(inputstring);
	separators = new String(separators);
	if (separators == "undefined") {
		separators = " :;";
	}
	fixedExplode = new Array();
	currentElement = "";
	count = 0;
	for(x = 0; x < inputstring.length; x++) {
		var charX = inputstring.charAt(x);
		if (separators.indexOf(charX) != -1) {
			if (((includeEmpties <= 0) || (includeEmpties == false)) && (currentElement == "")) {
			}
			else {
				fixedExplode[count] = currentElement;
				count++;
				currentElement = "";
			}
		}
		else {
			currentElement += charX;
		}
	}
	if ((!(includeEmpties <= 0) && (includeEmpties != false)) || (currentElement != "")) {
		fixedExplode[count] = currentElement;
	}
	return fixedExplode;
}