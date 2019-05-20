/*
* Apparantly RC4™ 
* Bei Ron's Code oder der Rivest Cipher No. 4 handelt es sich um ein Verfahren zur Stromchiffrierung. 
* Es wurde bereits 1987 von Ronald L. Rivest für RSA Data Security Inc. (heute RSA Security Inc.) entwickelt und lange Jahre geheimgehalten. 
* Im September 1994 veröffentlichte eine anonyme Person einen Algorithmus, der zu RC4 identische Ergebnisse erzeugte und daher 
* "apparantly RC4" ist, in einer Mailing-Liste. Dieser Quelltext verbreitete sich schnell wurde ausgiebig diskutiert und analysiert. 
* Im Gegensatz zu DES ist die Schlüssellänge variabel und kann bis zu 2048 Bit (256 Zeichen) betragen, wobei bereits 128 Bit als sicher gelten. 
* Es wird immer ein Byte (ein Zeichen) auf einmal verschlüsselt. Der Algorithmus ist sowohl einfach wie auch sicher und kann besonders einfach 
* und effizient programmiert werden. 
* Mit der Veröffentlichung ist der Algorithmus nun kein Firmengeheimnis mehr und ließe sich rechtlich gesehen frei verwenden. 
* Allerdings muss sich jeder der RC4 in seine kommerzielle Software einbaut, ohne es zu lizenzieren, auf einen Rechtsstreit mit RSA einrichten. 
* (Dabei dürfte RSA diesen Prozess wahrscheinlich verlieren.) Die 
* hier dargestellte Umsetzung in JavaScript basiert auf Informationen aus dem Internet. Es ist daher nicht sicher, 
* ob es sich wirklich um den Algorithmus der RSA handelt. 
*/

var RC4_sbox = new Array (256);

function RC4_crypt (key, text) {
	var i, j, k = 0;
	var temp    = 0;
	var t       = 0;
	var rtext   = "";

	for (j = 0; j < 256; j++)
		RC4_sbox[j] = j;
	j = 0;
	for (i=0; i < 256; i++) {
		j = (j + RC4_sbox[i] + key.charCodeAt(i % key.length)) % 256;
		temp        = RC4_sbox[i];
		RC4_sbox[i] = RC4_sbox[j];
		RC4_sbox[j] = temp;
	}

	for (k=0; k < text.length; k++) {
		i = (i + 1) % 256;
		j = (j + RC4_sbox[i]) % 256;
		temp        = RC4_sbox[i];
		RC4_sbox[i] = RC4_sbox[j];
		RC4_sbox[j] = temp;
		t = (RC4_sbox[i] + RC4_sbox[j]) % 256;
		rtext =  retxt + String.fromCharCode(text.charCodeAt(k) ^ RC4_sbox[t]);
	}
	return rtext;
} 