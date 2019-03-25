<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class Kontocheck ueberprueft deutsche Kontonummern anhand der
* ueber 100 existiereden Verfahren der Banken.
*
* BLZ siehe www.deutschebank.de
*
* $Log: Kontocheck.class.php,v $
* Revision 1.19  2007/07/10 15:01:27  schmidseder
* verfahren 85
*
* Revision 1.18  2007/05/07 08:17:24  schmidseder
* Verfahren B1 codiert.
*
* Revision 1.17  2006/10/20 11:29:21  schmidseder
* Verfahren 52
*
* Revision 1.16  2006/09/28 13:06:03  schmidseder
* Verfahren B2, B8, A5
*
* Revision 1.15  2006/08/30 09:52:09  schmidseder
* Verfahren 86 implementiert
*
* Revision 1.14  2006/08/07 11:37:14  manhart
* Exception -> Xception (PHP5 kompatibel)
*
* Revision 1.13  2006/08/01 15:12:13  schmidseder
* verfahren 90 und B6 hinzugef�gt
*
* Revision 1.12  2006/07/25 14:27:43  schmidseder
* verfahren 96 hinzugef�gt.
*
* Revision 1.11  2006/06/29 07:29:56  manhart
* Fix verfahren_95
*
* Revision 1.10  2006/06/26 09:16:09  manhart
* neue verfahren A7, 91
*
* Revision 1.9  2006/06/26 07:06:59  manhart
* Fix Verfahren 95
*
* Revision 1.8  2006/06/20 10:38:21  schmidseder
* verfahren A8, C1, 92, B7
*
* Revision 1.7  2006/06/13 09:28:20  schmidseder
* verfahren 87 codiert
*
* Revision 1.6  2006/06/12 13:59:43  schmidseder
* verfahren A3 und B3 eingebaut
*
* Revision 1.5  2006/06/08 13:36:18  schmidseder
* verfahren A2 hinzugef�gt Variante 2 codiert
*
* Revision 1.4  2006/06/08 11:43:17  schmidseder
* verfahren A2 hinzugef�gt
*
* Revision 1.3  2006/05/31 09:59:10  manhart
* Fehler: E_USER_WARNING (Default)
* Kontocheck: neues Verfahren 57
*
* Revision 1.2  2006/05/17 09:37:02  manhart
* verfahren 84 entwickelt
*
* Revision 1.1.1.1  2004/09/21 07:49:25  manhart
* initial import
*
* Revision 1.2  2004/04/15 09:59:13  manhart
* Status -1, falls Verfahren nicht implementiert
*
*
* @version $Id: Kontocheck.class.php,v 1.19 2007/07/10 15:01:27 schmidseder Exp $
* @version $Revision: 1.19 $
*
* @since 2004/04/14
* @author Alexander Manhart <alexander@manhart-it.de>
* @link https://alexander-manhart.de
*/

if(!defined('KONTOCHECK_CLASS'))
{
	#### Prevent multiple loading
	define('KONTOCHECK_CLASS', '1');

	/**
	 * Kontocheck
	 *
	 * Siehe Datei fuer ausfuehrliche Beschreibung!
	 *
	 * @package pool
	 * @author Alexander Manhart <alexander.manhart@freenet.de>
	 * @version $Id: Kontocheck.class.php,v 1.19 2007/07/10 15:01:27 schmidseder Exp $
	 * @access public
	 **/
	class Kontocheck
	{
		//@var array Hash Tabelle mit allen Bankdaten
		//@access private
		var $bankHash;

		//@var string Pfad zur blz.txt bzw. blz.hash
		//@access private
		var $pfad = './';


		/**
		 * Bankleitzahl, der zu pr�fenden Kontonummer
		 *
		 * @var int
		 */
		var $blz = 0;

		/**
		 * Kontocheck::Kontocheck()
		 *
		 * Konstruktor
		 *
		 * @access public
		 **/
		function __construct($blz=0)
		{
			$this -> blz = $blz;
		}

		function initBankHash()
		{
			//Hash laden
			if (!file_exists ($this -> pfad . 'blz.hash')) {
				$this -> getBankHash ($this -> pfad);
			}
			$fp = fopen ($this -> pfad . 'blz.hash', 'r');
			$this -> bankHash = unserialize (stripslashes (fread ($fp, filesize ($this->pfad . 'blz.hash'))));
			fclose ($fp);
		}

		/**
		 * Kontocheck::getBankHash()
		 *
		 * Erzeugt blz.hash aus blz.txt
		 *
		 * @access public
		 * @param string $pfad Pfad zur blz.txt / blz.hash
		 **/
		function getBankHash ($pfad)
		{
			$fp = fopen ($pfad . 'blz.txt', 'r');
			while (!feof ($fp)) {
				$zeile = explode (';', fgets ($fp, 65536));
				$bankHash["$zeile[0]"]['bezeichnung'] = $zeile[1];
				$bankHash["$zeile[0]"]['plz'] = $zeile[2];
				$bankHash["$zeile[0]"]['ort'] = $zeile[3];
				$bankHash["$zeile[0]"]['verfahren'] = $zeile[4];
			}
			fclose ($fp);
			$bankHashSerialized = addslashes (serialize ($bankHash));
			$fp = fopen ($pfad . 'blz.hash', 'w');
			fputs ($fp, $bankHashSerialized);
			fclose ($fp);
			chmod ($pfad . 'blz.hash', 0755);
		}

		function quersumme ($zahl)
		{
			$zahlStr = strval ($zahl);
			$zahl = '';
			for ($i=0; $i <= strlen ($zahlStr) - 1; $i++) {
				$zahl += intval ($zahlStr[$i]);
			}
			return $zahl;
		}

		function getEinerStelle($zahl) {
			$strZahl = strval($zahl);
			$anzStellen = strlen($strZahl);
			$strEinerStelle = substr($strZahl, $anzStellen - 1);
			return intval($strEinerStelle);
		}

		function getDir ()
		{
			$pfad = '';
			$scriptName = realpath ('./Kontocheck.class.php');
			$subDir = explode ('/', $scriptName);
			for ($i = 0; $i <= sizeof ($subDir) - 1; $i++) {
				if ($i != 0 && $i != sizeof ($subDir) - 1) {
					$pfad .= '/' . $subDir[$i];
				}
			}
			$pfad .= '/';
			return $pfad;
		}

		function getBankInfo ($blz)
		{
			$blzZeile = $this -> bankHash[$blz];
			return $blzZeile;
		}

		function validate($kontonummer, $verfahren)
		{
			$returnValue = 0;
			if (is_numeric($kontonummer)) {
				$verfahren = strval($verfahren);

				$kontonummer = str_pad($kontonummer, 10, '0', STR_PAD_LEFT);

				$kontonummer = strval($kontonummer);
				if ($kontonummer <= 9) {
					$returnValue = 0;
				}
				else {
					$functionName = 'verfahren_' . $verfahren;
					if (method_exists ($this, $functionName)) {
						$returnValue = $this -> $functionName ($kontonummer);
					}
					else {
						$returnValue = -1;
					}
				}
			}
			return $returnValue;
		}

		function validateAccountByBankHash ($kontonummer, $blz)
		{
			$bankHash = & $this->bankHash;
			$kontonummer = preg_replace ("/ /", "", $kontonummer);
			$blz = preg_replace ("/ /", "", $blz);
			$kontonummer = preg_replace ("/-/", "", $kontonummer);
			$blz = preg_replace ("/-/", "", $blz);
			$kontonummer = preg_replace ("/\,/", "", $kontonummer);
			$blz = preg_replace ("/\,/", "", $blz);
			$kontonummer = preg_replace ("/\./", "", $kontonummer);
			$blz = preg_replace ("/\./", "", $blz);
			if (!is_numeric ($kontonummer)) {
				$returnValue = 0;
			}
			else {
				if (isset ($bankHash)) {
					$blz = intval ($blz);
					$blzZeile = $bankHash["$blz"];
					$verfahren = strval (chop ($blzZeile["verfahren"]));
					// Testcode
					// $verfahren = 77;
					$laenge = strlen ($kontonummer);
					if ($laenge < 10) {
						$zerosToAdd = 10 - $laenge;
						$i = 1;
						while ($i <= $zerosToAdd) {
							$kontonummer = "0". $kontonummer;
							$i++;
						}
					}
					$kontonummer = strval($kontonummer);
					if ($kontonummer <= 9 || $blz <= 1000000) {
						$returnValue = 0;
					}
					else {
					// Class extension
						$functionName = "verfahren_" . $verfahren;
						if (method_exists ($this,$functionName)) {
							$returnValue = $this->$functionName ($kontonummer);
						}
						else {
							if (sizeof ($blzZeile) > 0) {
								$returnValue = 3;
							}
							else {
								$returnValue = 0;
							}
						}
					}
				}
				else {
					$returnValue = 0;
				}
			}
			return $returnValue;
		}

		function verfahren_00 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) {
				$pruef=0;
			}
			else {
				$pruef=$rest;
			}
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 00

		function verfahren_01 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*7);
			$val7=intval(intval($ktonr[6])*1);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*7);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*3);
			$val2=intval(intval($ktonr[1])*7);
			$val1=intval(intval($ktonr[0])*1);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=10- intval($ges % 10);
			if ($rest=="10") {
				$pruef=0;
			}
			else {
				$pruef=$rest;
			}
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 01

		function verfahren_02 ($kontonummer)
		{
			$overall = 0;
			$iteration = 1;
			$j = 8;
			$k = 1;
			while ($iteration <= 8) {
				if ($k == 1) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 2) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 3) {
					$produkte[$iteration] = $kontonummer[$j] * 4;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 4) {
					$produkte[$iteration] = $kontonummer[$j] * 5;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 5) {
					$produkte[$iteration] = $kontonummer[$j] * 6;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 6) {
					$produkte[$iteration] = $kontonummer[$j] * 7;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 7) {
					$produkte[$iteration] = $kontonummer[$j] * 8;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 8) {
					$produkte[$iteration] = $kontonummer[$j] * 9;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 9) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
					$k = 1;
				}
				$overall += $this->quersumme[$iteration];
				$iteration++;
				$j--;
				$k++;
			}
			$stelle = strlen ($overall);
			$overall = $overall % 11;
			$overallcast = "$overall";
			$pruefziffer = 11 - $overallcast;
			if ($pruefziffer == 11) {
				$pruefziffer = 0;
			}
			if ($pruefziffer == 10) {
				$return_value = 0;
			}
			else {
				if ($pruefziffer == $kontonummer[9]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
			}
			return $return_value;
		}

		function verfahren_03 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=10-intval($ges % 10);
			if ($rest=="10") {
			}
			else {
				$pruef=$rest;
			}

			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		}

		function verfahren_04 ($kontonummer)
		{
			$overall = 0;
			$iteration = 1;
			$j = 8;
			$k = 1;
			while ($iteration <= 8) {
				if ($k == 1) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 2) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 3) {
					$produkte[$iteration] = $kontonummer[$j] * 4;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 4) {
					$produkte[$iteration] = $kontonummer[$j] * 5;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 5) {
					$produkte[$iteration] = $kontonummer[$j] * 6;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 6) {
					$produkte[$iteration] = $kontonummer[$j] * 7;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 7) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 8) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 9) {
					$produkte[$iteration] = $kontonummer[$j] * 4;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
					$k = 1;
				}
				$overall += $this->quersumme[$iteration];
				$iteration++;
				$j--;
				$k++;
			}
			$stelle = strlen ($overall);
			$overall = $overall % 11;
			$overallcast = "$overall";
			$pruefziffer = 11 - $overallcast;
			if ($pruefziffer == 11) {
			$pruefziffer = 0;
			}
			if ($pruefziffer == 10) {
			$return_value = 0;
			} else {
			if ($pruefziffer == $kontonummer[9]) {
			$return_value = 1;
			} else {
			$return_value = 0;
			}
			}
			return $return_value;
		}

		function verfahren_05 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*7);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*1);
			$val6=intval(intval($ktonr[5])*7);
			$val5=intval(intval($ktonr[4])*3);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*7);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*1);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=10 - intval($ges % 10);
			if ($rest=="10") { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		}

		function verfahren_06 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 06

		function verfahren_07 ($kontonummer)
		{
			$overall = 0;
			$iteration = 1;
			$j = 8;
			$k = 1;
			while ($iteration <= 8) {
				if ($k == 1) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 2) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 3) {
					$produkte[$iteration] = $kontonummer[$j] * 4;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 4) {
					$produkte[$iteration] = $kontonummer[$j] * 5;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 5) {
					$produkte[$iteration] = $kontonummer[$j] * 6;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 6) {
					$produkte[$iteration] = $kontonummer[$j] * 7;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 7) {
					$produkte[$iteration] = $kontonummer[$j] * 8;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 8) {
					$produkte[$iteration] = $kontonummer[$j] * 9;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 9) {
					$produkte[$iteration] = $kontonummer[$j] * 10;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
					$k = 1;
				}
				$overall += $this->quersumme[$iteration];
				$iteration++;
				$j--;
				$k++;
			}
			$stelle = strlen ($overall);
			$overall = $overall % 11;
			$overallcast = "$overall";
			$pruefziffer = 11 - $overallcast;
			if ($pruefziffer == 1) {
				$return_value = 0;
			}
			else {
				if ($pruefziffer == $kontonummer[9]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
			}
			return $return_value;
		} // Ende 07

		function verfahren_08 ($kontonummer)
		{
			if ($kontonummer > 60000) {
				$overall = 0;
				$iteration = 1;
				$j = 8;
				while ($iteration <= 9) {
					if (($iteration % 2) == 0) {
						$produkte[$iteration] = $kontonummer[$j] * 1;
						$produktcast = "$produkte[$iteration]";
						$this->quersumme[$iteration] = $produktcast;
					}
					else {
						$produkte[$iteration] = $kontonummer[$j] * 2;
						if ($produkte[$iteration] > 9) {
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast[0] + $produktcast[1];
						}
						else {
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast;
						}
					}
					$overall += $this->quersumme[$iteration];
					$iteration++;
					$j--;
				}
				$stelle = strlen ($overall);
				$overallcast = "$overall";
				if ($stelle == 1) {
					$pruefziffer = 10 - $overallcast;
				}
				else {
					$pruefziffer = 10 - $overallcast [$stelle - 1];
				}
				if ($pruefziffer == $kontonummer[9]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
				return $return_value;
			}
			else {
				$return_value = 0;
			}
		} // Ende 08

		function verfahren_09 ($ktonr)
		{
			return 1; // Alle Kontonummern fuer Verfahren 9 gelten als gueltig
		}  // Ende 09

		function verfahren_10 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*10);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		}  // Ende 10

		function verfahren_11 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*10);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { if ($rest==1) { $pruef=9; } else { $pruef=0; } }

			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		}  // Ende 11

		function verfahren_12 ($kontonummer)
		{
			$overall = 0;
			$iteration = 1;
			$j = 8;
			$k = 1;
			while ($iteration <= 8) {
				if ($k == 1) {
					$produkte[$iteration] = $kontonummer[$j] * 1;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 2) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 3) {
					$produkte[$iteration] = $kontonummer[$j] * 7;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
					$k = 1;
				}
				$overall += $this->quersumme[$iteration];
				$iteration++;
				$j--;
				$k++;
			}
			$stelle = strlen ($overall);
			$overallcast = "$overall";
			if ($stelle == 1) {
				$pruefziffer = 10 - $overallcast;
			}
			else {
				$pruefziffer = 10 - $overallcast [$stelle - 1];
			}
			if ($pruefziffer == $kontonummer[9]) {
				$return_value = 1;
			}
			else {
				$return_value = 0;
			}
			return $return_value;
		} // Ende 12

		function verfahren_13 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
			if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
			if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
			if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
			if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
			if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
			$ges=intval($val2+$val3+$val4+$val5+$val6+$val7);
			$ges = "$ges";
			if ((strlen ($ges)) > 1) {
				$pruef= 10 - $ges[1];
				// Wenn 10 - Pruefziffer == 0, setze $pruef = 0
				if ($pruef == 10) {
					$pruef = 0;
				}
			}
			else {
				$pruef = 10 - $ges;
				// Wenn 10 - Pruefziffer == 0, setze $pruef = 0
				if ($pruef == 10) { $pruef = 0; }
			}
			if (intval($pruef)==intval($ktonr[7])) {
				return 1;
			}
			else {
				if (($ktonr[8]+$ktonr[9]) != 0) {
					$cache[0]=$ktonr[2];
					$cache[1]=$ktonr[3];
					$cache[2]=$ktonr[4];
					$cache[3]=$ktonr[5];
					$cache[4]=$ktonr[6];
					$cache[5]=$ktonr[7];
					$cache[6]=$ktonr[8];
					$cache[7]=$ktonr[9];
					$cache[8]=0;
					$cache[9]=0;
					$ktonr="";
					for ($i=0; $i<=9; $i++) {
						$ktonr.=$cache[$i];
					}
					if (($this->verfahren_13 ($ktonr) == 1)) {
						return 1;
					}
					else {
						return 0;
					}
				}
				else {
					return 0;
				}
			}
		} // Ende 13

		function verfahren_14 ($kontonummer)
		{
			$overall = 0;
			$iteration = 1;
			$j = 8;
			$k = 4;
			while ($iteration <= 8) {
				if ($k == 4) {
					$produkte[$iteration] = $kontonummer[$j] * 2;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 5) {
					$produkte[$iteration] = $kontonummer[$j] * 3;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 6) {
					$produkte[$iteration] = $kontonummer[$j] * 4;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 7) {
					$produkte[$iteration] = $kontonummer[$j] * 5;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 8) {
					$produkte[$iteration] = $kontonummer[$j] * 6;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				if ($k == 9) {
					$produkte[$iteration] = $kontonummer[$j] * 7;
					$produktcast = "$produkte[$iteration]";
					$this->quersumme[$iteration] = $produktcast;
				}
				$overall += $this->quersumme[$iteration];
				$iteration++;
				$j--;
				$k++;
			}
			$stelle = strlen ($overall);
			$overall = $overall % 11;
			$overallcast = "$overall";
			$pruefziffer = 11 - $overallcast;
			if ($pruefziffer == 11) {
				$pruefziffer = 0;
			}
			if ($pruefziffer == 10) {
				$return_value = 0;
			}
			else {
				if ($pruefziffer == $kontonummer[9]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
			}
			return $return_value;
		} // Ende 14

		function verfahren_15 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);

			$ges=intval($val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		}  // Ende 15

		function verfahren_16 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if ($rest == 1) {
				if ($ktonr[8] == $ktonr[9]) {
					return 1;
					}
					else {
						return 0;
					}
				}
				else {
					if (intval($pruef)==intval($ktonr[9])) {
						return 1;
					}
				else {
					return 0;
				}
			}
		} // Ende 16

		function verfahren_17 ($ktonr)
		{
			$summe = 0;
			for ($i = 1; $i <= 6; $i++) {
				switch ($i % 2) {
				case 1 :
					$summe += $this->quersumme ($ktonr[$i] * 1);
					break;

				case 0 :
					$summe += $this->quersumme ($ktonr[$i] * 2);
					break;
				}
			}
			$summe -= 1;
			$pruefziffer = 10 - $summe % 11;
			if ($pruefziffer == 10) {
				$pruefziffer = 0;
			}
			if ($pruefziffer == $ktonr[$i]) {
				$returnValue = 1;
			}
			else {
				$returnValue = 0;
			}
			return $returnValue;
		} // Ende 17

		function verfahren_18 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*9);
			$val7=intval(intval($ktonr[6])*7);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*3);
			$val4=intval(intval($ktonr[3])*9);
			$val3=intval(intval($ktonr[2])*7);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*3);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=10- intval($ges % 10);
			if ($rest=="10") { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 18

		function verfahren_19 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*1);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		}  // Ende 19

		function verfahren_20 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*3);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		}  // Ende 20

		function verfahren_21 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			while ($ges > 9) {
				$ges = $this->quersumme ($ges);
			}
			$rest=10-intval($ges % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 21

		function verfahren_22 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*3);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*3);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*3);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*3);
			for ($i = 1; $i <= 9; $i++) {
				$varToProcess = "val$i";
				$varEinerToProcess = "valEiner$i";
				$$varToProcess = strval ($$varToProcess);
				$varLength = strlen ($$varToProcess);
				if ($varLength > 1) {
					for ($j = 1; $j < $varLength; $j++) {
					$$varEinerToProcess = $$varToProcess[$j];
					}
				}
				else {
					$$varEinerToProcess = $$varToProcess;
				}
			}
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$rest=10-intval($ges % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 22

		function verfahren_23 ($ktonr)
		{
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if ($rest == 1) {
				if ($ktonr[5] == $ktonr[6]) {
					return 1;
				}
				else {
					return 0;
				}
			}
			else {
				if (intval($pruef)==intval($ktonr[6])) {
					return 1;
				}
				else {
					return 0;
				}
			}
		} // Ende 23

		function verfahren_24 ($ktonr)
		{
			$summe = 0;
			if ($ktonr[0] == 3 || $ktonr[0] == 4 || $ktonr[0] == 5 || $ktonr[0] == 6) {
				$ktonr[0] = 0;
			}
			if ($ktonr[0] == 9) {
				$ktonr[0] = 0;
				$ktonr[1] = 0;
				$ktonr[2] = 0;
			}
			$ktonr = strval (doubleval ($ktonr));
			for ($i = 0; $i <= strlen ($ktonr) - 2; $i++) {
				switch ($i % 3) {
					case 0 :
					$summe += ($ktonr[$i] * 1 + 1) % 11;
					break;
					case 1 :
					$summe += ($ktonr[$i] * 2 + 2) % 11;
					break;
					case 2 :
					$summe += ($ktonr[$i] * 3 + 3) % 11;
					break;
				}
			}
			$summe = strval ($summe);
			$summeLength = strlen ($summe);
			if ($summeLength > 1) {
				for ($j = 1; $j < $summeLength; $j++) {
					$pruefziffer = $summe[$j];
				}
			}
			else {
				$pruefziffer = $summe;
			}
			if ($pruefziffer == $ktonr[$i]) {
				$returnValue = 1;
			}
			else {
				$returnValue = 0;
			}
			return $returnValue;
		} // Ende 24

		function verfahren_25 ($ktonr)
		{
			$val8=intval(intval($ktonr[8])*2);
			$val7=intval(intval($ktonr[7])*3);
			$val6=intval(intval($ktonr[6])*4);
			$val5=intval(intval($ktonr[5])*5);
			$val4=intval(intval($ktonr[4])*6);
			$val3=intval(intval($ktonr[3])*7);
			$val2=intval(intval($ktonr[2])*8);
			$val1=intval(intval($ktonr[1])*9);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		}  // Ende 25

		function verfahren_26 ($ktonr)
		{
			if ($ktonr[0] == 0 && $ktonr[1] == 0) {
			 $ktonr = substr ($ktonr, 2) . "00";
			}
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*6);
			$val2=intval(intval($ktonr[1])*7);
			$val1=intval(intval($ktonr[0])*2);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		} // Ende 26

		function verfahren_27 ($ktonr)
		{
			if (intval ($ktonr) >=1 && intval ($ktonr) <= 999999999) {
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
				if (intval($pruef)==intval($ktonr[9])) {
					$returnValue = 1;
				}
				else {
					$returnValue = 0;
				}
			} else {
			// Transformationsmatrix (Verfahren M10H)
				$summe = 0;
				$zeile1 = array (0, 1, 5, 9, 3, 7, 4, 8, 2, 6);
				$zeile2 = array (0, 1, 7, 6, 9, 8, 3, 2, 5, 4);
				$zeile3 = array (0, 1, 8, 4, 6, 2, 9, 5, 7, 3);
				$zeile4 = array (0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
				$length = strlen ($ktonr) - 1;
				for ($i = 0; $i < $length ; $i++) {
					switch ($i % 4) {
					case 0:
						$summe += $zeile1[$ktonr[$length - 1 - $i]];
						break;
					case 1:
						$summe += $zeile2[$ktonr[$length - 1 - $i]];
						break;
					case 2:
						$summe += $zeile3[$ktonr[$length - 1 - $i]];
						break;
					case 3:
						$summe += $zeile4[$ktonr[$length - 1 - $i]];
						break;
					}
				}
				$summe = strval ($summe);
				$summeLength = strlen ($summe);
				if ($summeLength > 1) {
					for ($j = 1; $j < $summeLength; $j++) {
						$summeEiner = $summe[$j];
					}
				}
				else {
					$summeEiner = $summe;
				}
				$pruefziffer = 10 - $summeEiner;
				if ($pruefziffer == $ktonr[$i]) {
					$returnValue = 1;
				}
				else {
					$returnValue = 0;
				}
			}
			return $returnValue;
		} // Ende 27

		function verfahren_28 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*6);
			$val2=intval(intval($ktonr[1])*7);
			$val1=intval(intval($ktonr[0])*8);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		}  // Ende 28

		function verfahren_29 ($ktonr)
		{
			// Transformationsmatrix
			$summe = 0;
			$zeile1 = array (0, 1, 5, 9, 3, 7, 4, 8, 2, 6);
			$zeile2 = array (0, 1, 7, 6, 9, 8, 3, 2, 5, 4);
			$zeile3 = array (0, 1, 8, 4, 6, 2, 9, 5, 7, 3);
			$zeile4 = array (0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
			$length = strlen ($ktonr) - 1;
			for ($i = 0; $i < $length ; $i++) {
				switch ($i % 4) {
				case 0:
					$summe += $zeile1[$ktonr[$length - 1 - $i]];
					break;
				case 1:
					$summe += $zeile2[$ktonr[$length - 1 - $i]];
					break;
				case 2:
					$summe += $zeile3[$ktonr[$length - 1 - $i]];
					break;
				case 3:
					$summe += $zeile4[$ktonr[$length - 1 - $i]];
					break;
				}
			}
			$summe = strval ($summe);
			$summeLength = strlen ($summe);
			if ($summeLength > 1) {
				for ($j = 1; $j < $summeLength; $j++) {
					$summeEiner = $summe[$j];
				}
			} else {
				$summeEiner = $summe;
			}
			$pruefziffer = 10 - $summeEiner;
			if ($pruefziffer == $ktonr[$i]) {
				$returnValue = 1;
			}
			else {
				$returnValue = 0;
			}
			return $returnValue;
		} // Ende 29

		function verfahren_30 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*0);
			$val4=intval(intval($ktonr[3])*0);
			$val3=intval(intval($ktonr[2])*0);
			$val2=intval(intval($ktonr[1])*0);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 30

		function verfahren_31 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*9);
			$val8=intval(intval($ktonr[7])*8);
			$val7=intval(intval($ktonr[6])*7);
			$val6=intval(intval($ktonr[5])*6);
			$val5=intval(intval($ktonr[4])*5);
			$val4=intval(intval($ktonr[3])*4);
			$val3=intval(intval($ktonr[2])*3);
			$val2=intval(intval($ktonr[1])*2);
			$val1=intval(intval($ktonr[0])*1);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$pruef = $ges % 11;
			if (intval($pruef)==intval($ktonr[9]) && $pruef != 10) { return 1; }
			else { return 0; }
		} // Ende 31

		function verfahren_32 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);

			$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 32

		function verfahren_33 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);

			$ges=intval($val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			}
			else {
				$pruef=0;
			}

			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 33

		function verfahren_34 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*4);
			$val5=intval(intval($ktonr[4])*8);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*10);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*7);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		}  // Ende 34

		function verfahren_35 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*10);
			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$pruef = $ges % 11;
			if (intval($pruef)==intval($ktonr[9]) || ($pruef == 10 && $ktonr[9] == $ktonr[8])) { return 1; }
			else { return 0; }
		} // Ende 35

		function verfahren_36 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);

			$ges=intval($val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 36

		function verfahren_37 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*10);

			$ges=intval($val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 37

		function verfahren_38 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*10);
			$val4=intval(intval($ktonr[3])*9);

			$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 38

		function verfahren_39 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*10);
			$val4=intval(intval($ktonr[3])*9);
			$val3=intval(intval($ktonr[2])*7);

			$ges=intval($val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 39

		function verfahren_40 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*10);
			$val4=intval(intval($ktonr[3])*9);
			$val3=intval(intval($ktonr[2])*7);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*6);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 40

		function verfahren_41 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			if ($ktonr[3] != 9) {
				$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			}
			else {
				$ges=intval($this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			}
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 41

		function verfahren_42 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);

			$ges=intval($val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 42

		function verfahren_43 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*1);
			$val8=intval(intval($ktonr[7])*2);
			$val7=intval(intval($ktonr[6])*3);
			$val6=intval(intval($ktonr[5])*4);
			$val5=intval(intval($ktonr[4])*5);
			$val4=intval(intval($ktonr[3])*6);
			$val3=intval(intval($ktonr[2])*7);
			$val2=intval(intval($ktonr[1])*8);
			$val1=intval(intval($ktonr[0])*9);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 10;
			$pruef=intval(10 - $rest);

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 43

		function verfahren_44 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*4);
			$val7=intval(intval($ktonr[6])*8);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*10);

			$ges=intval($val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 44

		function verfahren_45 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9]) || $ktonr[0] == 0 || $ktonr[4] == 1) { return 1; }
			else { return 0; }
		} // Ende 45

		function verfahren_46 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*6);

			$ges=intval($val3+$val4+$val5+$val6+$val7);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		} // Ende 46

		function verfahren_47 ($ktonr)
		{
			$val8=intval(intval($ktonr[7])*2);
			$val7=intval(intval($ktonr[6])*3);
			$val6=intval(intval($ktonr[5])*4);
			$val5=intval(intval($ktonr[4])*5);
			$val4=intval(intval($ktonr[3])*6);

			$ges=intval($val4+$val5+$val6+$val7+$val8);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[8])) { return 1; }
			else { return 0; }
		} // Ende 47

		function verfahren_48 ($ktonr)
		{
			$val8=intval(intval($ktonr[7])*2);
			$val7=intval(intval($ktonr[6])*3);
			$val6=intval(intval($ktonr[5])*4);
			$val5=intval(intval($ktonr[4])*5);
			$val4=intval(intval($ktonr[3])*6);
			$val3=intval(intval($ktonr[2])*7);

			$ges=intval($val3+$val4+$val5+$val6+$val7+$val8);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[8])) { return 1; }
			else { return 0; }
		} // Ende 48

		function verfahren_49 ($ktonr)
		{
			$returnValue = $this->verfahren_00 ($ktonr);
			if ($returnValue == 0) {
				$returnValue = $this->verfahren_01 ($ktonr);
			}
			return $returnValue;
		} // Ende 49

		function verfahren_50 ($ktonr)
		{
			$val6=intval(intval($ktonr[5])*2);
			$val5=intval(intval($ktonr[4])*3);
			$val4=intval(intval($ktonr[3])*4);
			$val3=intval(intval($ktonr[2])*5);
			$val2=intval(intval($ktonr[1])*6);
			$val1=intval(intval($ktonr[0])*7);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);
			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			} else {
				 $pruef=0;
			}
			if (intval($pruef)==intval($ktonr[6])) {
				return 1;
			}
			else {
				if (substr ($ktonr, 7, 3) != "000") {
					return $this->verfahren_50 (substr ($ktonr, 3, 7) . "000");
				}
				else {
					return 0;
				}
			}
		} // Ende 50

		/* Old version of procedure 51
		function verfahren_51 ($ktonr) {
		//AUSNAHME
		if($ktonr[2].$ktonr[3] == 99)
		{
		$val9=intval(intval($ktonr[8])*2);
		$val8=intval(intval($ktonr[7])*3);
		$val7=intval(intval($ktonr[6])*4);
		$val6=intval(intval($ktonr[5])*5);
		$val5=intval(intval($ktonr[4])*6);
		$val4=intval(intval($ktonr[3])*7);
		$val3=intval(intval($ktonr[2])*8);
		$val2=intval(intval($ktonr[1])*9);
		$val1=intval(intval($ktonr[0])*10);

		$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
		$rest=$ges % 11;
		if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

		if (intval($pruef)==intval($ktonr[9])) { return 1; }
		else { return 0; }
		}
		//METHODE A
		$val9=intval(intval($ktonr[8])*2);
		$val8=intval(intval($ktonr[7])*3);
		$val7=intval(intval($ktonr[6])*4);
		$val6=intval(intval($ktonr[5])*5);
		$val5=intval(intval($ktonr[4])*6);
		$val4=intval(intval($ktonr[3])*7);
		$val3=intval(intval($ktonr[2])*2);
		$val2=intval(intval($ktonr[1])*3);
		$val1=intval(intval($ktonr[0])*4);

		$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
		$rest=$ges % 11;
		if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

		if (intval($pruef)==intval($ktonr[9]))
		return 1;
		else
		{
		//METHODE B
		$val9=intval(intval($ktonr[8])*2);
		$val8=intval(intval($ktonr[7])*3);
		$val7=intval(intval($ktonr[6])*4);
		$val6=intval(intval($ktonr[5])*5);
		$val5=intval(intval($ktonr[4])*6);

		$ges=intval($val5+$val6+$val7+$val8+$val9);
		$rest=$ges % 11;
		if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

		if (intval($pruef)==intval($ktonr[9]))
		return 1;
		else
		{
		 //METHODE C
		 if($ktonr[9] > 6) return 0;
		 $val9=intval(intval($ktonr[8])*2);
		 $val8=intval(intval($ktonr[7])*3);
		 $val7=intval(intval($ktonr[6])*4);
		 $val6=intval(intval($ktonr[5])*5);
		 $val5=intval(intval($ktonr[4])*6);

		 $ges=intval($val5+$val6+$val7+$val8+$val9);
		 $rest=$ges % 7;
		 if ($rest>1) { $pruef=intval(7 - $rest); } else { $pruef=0; }
		 return $pruef;

		}
		}
		} // Ende 51
		end of old version of procedure 51 */

		function verfahren_51 ($ktonr)
		{
			//AUSNAHME
			if($ktonr[2].$ktonr[3] == 99)
			{
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);
				$val3=intval(intval($ktonr[2])*8);
				$val2=intval(intval($ktonr[1])*9);
				$val1=intval(intval($ktonr[0])*10);

				$ges=intval(($val1 % 10)+($val2 % 10)+($val3 % 10)+($val4 % 10)+($val5 % 10)+($val6 % 10)+($val7 % 10)+($val8 % 10)+($val9 % 10));
				$rest=$ges % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

				if (intval($pruef)==intval($ktonr[9])) { return 1; }
				else { return 0; }
			}
			//METHODE A
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1 && $rest != 10) { $pruef=intval(11 - $rest); } else { $pruef=0; }

			if (intval($pruef)==intval($ktonr[9]))
				return 1;
			else {
				//METHODE B
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);

				$ges=intval($val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

				if (intval($pruef)==intval($ktonr[9]))
					return 1;
				else {
					//METHODE C
					if($ktonr[9] > 6) return 0;
					$val9=intval(intval($ktonr[8])*2);
					$val8=intval(intval($ktonr[7])*3);
					$val7=intval(intval($ktonr[6])*4);
					$val6=intval(intval($ktonr[5])*5);
					$val5=intval(intval($ktonr[4])*6);

					$ges=intval($val5+$val6+$val7+$val8+$val9);
					$rest=$ges % 7;
					if ($rest>1) { $pruef=intval(7 - $rest); } else { $pruef=0; }
					if (intval($pruef)==intval($ktonr[9])) { return 1; } else { return 0; }
				}
			}
		} // Ende 51

		function verfahren_52($ktonr)
		{
			$ktonr = strVal(intVal($ktonr));
			$kontonummer = array();
			for ($i = 0; $i < strlen($ktonr); $i++) {
				array_push($kontonummer, intval($ktonr[$i]));
			}

			### Ausnahme.
			if (count($kontonummer) == 10 and $kontonummer[0] == 9) {
				return $this -> verfahren_20($ktonr);
			}
			else {

				### ERstelle ESER-Kontonummer f�r Verfahren 52
				# BLZ      Konto-Nr.
				# XXX5XXXX XPXXXXXX (P = Pr�fziffer)
				# Kontonummer des Altsystems:
				# XXXX-XP-XXXXX
				# (XXXX = variable L�nge, da evtl. vorlaufende Nullen eliminiert werden)

				## Ziffern der Blz in ein Array schmei�en
				$blz = strVal($this -> blz);
				$bankleitzahlen = array();
				for ($i = 0; $i < strlen($blz); $i++) {
					array_push($bankleitzahlen, $blz[$i]);
				}

				# ESER-Ktonr zsambauen.
				$eser = array();
				$XXXX = array_slice($bankleitzahlen, -4);
				for ($i = 0; $i < count($XXXX); $i++) {
					$eser[] = $XXXX[$i];
				}

				$eser[] = $kontonummer[0];

				$eser[] = 0;
				$pruefziffer = $kontonummer[1];

				$varXXXX = array_slice($kontonummer, 2);

				// f�hrende Null eliminieren
				while($varXXXX[0] == '0') {
					$varXXXX = array_slice($varXXXX, 1);
				}

				for ($i = 0; $i < count($varXXXX); $i++) {
					$eser[] = $varXXXX[$i];
				}

				$gewichtung = array(2, 4, 8, 5, 10, 9, 7, 3, 6, 1, 2, 4);

				$anzEser = count($eser);
				$gewichtung = array_slice($gewichtung, 0, count($eser));
				$rGewichtung = array_reverse($gewichtung);
				$sumGesamt = 0;
				for ($i=0; $i < $anzEser; $i++) {
					$produkt = $eser[$i] * $rGewichtung[$i];
//						echo $i . ' rechnung: ' . $eser[$i] . ' * ' . $rGewichtung[$i] . '=' . $produkt . chr(10);
					$sumGesamt += $produkt;
				}
//					echo "\n";
//					echo "\nSUMGESAMT: " . $sumGesamt;

				$rest = $sumGesamt % 11;
				$gewichtUeberPruefziffer = $gewichtung[count($varXXXX)]; // Sechste Stelle
				for($p = 0; $p < 10; $p++ ) {
					$erg = $rest + ($p * $gewichtUeberPruefziffer);
					$r = $erg % 11;
					if($r == 10)
						break;
				}

				if($p == $pruefziffer) {
					return 1;
				} else {
					return 0;
				}
			}
		} // Ende 52


		function verfahren_55 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*7);
			$val1=intval(intval($ktonr[0])*8);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 55

		function verfahren_57 ($ktonr)
		{
			$ktonr = str_repeat('0', 10 - strlen($ktonr)) . $ktonr;

			$firstTwo = substr($ktonr, 0, 2);
			if (($firstTwo >= 0 && $firstTwo <= 50) || $firstTwo == 91 ||   $firstTwo >= 96) {
				return 1;
			}

			$is7or8 = true;
			for ($i = 0; $i < 7; $i++) {
				if ($ktonr{$i} != 7 && $ktonr{$i} != 8) {
					$is7or8 = false;
					break;
				}
			}
			if ($is7or8) {
				return 1;
			}

			$weight = 1;
			$sum = 0;
			for($i = 0; $i <= 8; $i++) {
				$int = $ktonr{$i} * $weight;

				$str_int = (string) $int;
				for ($z = 0; $z < strlen($str_int); $z++) {
					//$sum = bcadd($str_int{$i}, $sum);
					$sum += $str_int{$z};
				}

				if($weight == 2) {
					$weight = 1;
				}
				else {
					$weight = 2;
				}
			}

			$modulo = 10;
			$check = ($modulo - substr($sum, -1)) % 10;
			if($check == $ktonr{9}) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 57

		function verfahren_59 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9]) || strlen (strval ($ktonr)) < 9) { return 1; }
			else { return 0; }
		} // Ende 59

		function verfahren_60 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$ges=intval($this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 60

		function verfahren_61 ($ktonr)
		{
			if ($ktonr[8] == 8) {
				$val10=intval(intval($ktonr[9])*2);
				$val9=intval(intval($ktonr[8])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				$val1=intval(intval($ktonr[0])*2);
				if ($val1>9) { $val1 = "$val1"; $val1 = $val1[0] + $val1[1]; }
				if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				if ($val9>9) { $val9 = "$val9"; $val9 = $val9[0] + $val9[1]; }
				if ($val10>9) { $val10 = "$val10"; $val10 = $val10[0] + $val10[1]; }
				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val9+$val10);
			}
			else {
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				$val1=intval(intval($ktonr[0])*2);
				if ($val1>9) { $val1 = "$val1"; $val1 = $val1[0] + $val1[1]; }
				if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7);
			}
			$ges = "$ges";
			if ((strlen ($ges)) > 1) { $pruef= 10 - $ges[1]; }
			else { $pruef= 10 - $ges; }
			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		} // Ende 61

		function verfahren_62 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$ges=intval($this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		} // Ende 62

		/**
		 * Verfahren 63
		 *
		 * @param int $ktonr
		 * @return boolean
		 */
		function verfahren_63 ($ktonr)
		{
			// 30.08.2012, AM, Unterkontonummern kommen leider hinten dran. Verfahren wird vor dem Aufruf mit einem Spezialfall behandelt!!
			// -> siehe validate fuer Spezialfall
			if($ktonr{0} == '0' && substr($ktonr,1,2)!='00') {
				// 0743528260 (BLZ 86070024)
				$pruefziffer_stelle = 7; // 8. Stelle
			}
			else {
				// Ausnahmefall 63 - 05.09.2012
				// 0008742892 (BLZ 70070010)
				$pruefziffer_stelle = 9; // 10. Stelle
			}

			$pruef = intval($ktonr[$pruefziffer_stelle]);
			$val6 = intval($ktonr[--$pruefziffer_stelle]) * 2;
			$val5 = intval($ktonr[--$pruefziffer_stelle]) * 1;
			$val4 = intval($ktonr[--$pruefziffer_stelle]) * 2;
			$val3 = intval($ktonr[--$pruefziffer_stelle]) * 1;
			$val2 = intval($ktonr[--$pruefziffer_stelle]) * 2;
			$val1 = intval($ktonr[--$pruefziffer_stelle]) * 1;

			$ges = intval($this->quersumme($val1) + $this->quersumme($val2) + $this->quersumme($val3) +
				$this->quersumme($val4) + $this->quersumme($val5) + $this->quersumme($val6));
//					die('einer:'.$ges);
			return ((10 - $this->getEinerStelle($ges)) == $pruef) ? 1 : 0;

			/**
				fehlerhaft, AM, 10.10.2011
			if (($ktonr[8]+$ktonr[9]) == 0) {
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				$ges=intval($val2+$val3+$val4+$val5+$val6+$val7);
				$pruefstelle = 7;
			}
			else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				if ($val8>9) { $val8 = "$val8"; $val8 = $val8[0] + $val8[1]; }
				if ($val9>9) { $val9 = "$val9"; $val9 = $val9[0] + $val9[1]; }
				$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
				$pruefstelle = 9;
			}
			$ges = "$ges";
			if ((strlen ($ges)) > 1) {
				if($ges[1]==0) {
					$pruef=0;
				}
				else {
					$pruef= 10 - $ges[1];
				}
			}
			else {
				$pruef= 10 - $ges;
			}
			if ($ktonr[0]!=0) { return 0; }
			else {
				if (intval($pruef)==intval($ktonr[$pruefstelle])) { return 1; }
				else { return 0; }
			}
			*/

		} // Ende 63

		function verfahren_64 ($ktonr)
		{
			$val6=intval(intval($ktonr[5])*2);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*8);
			$val3=intval(intval($ktonr[2])*5);
			$val2=intval(intval($ktonr[1])*10);
			$val1=intval(intval($ktonr[0])*9);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[6])) { return 1; }
			else { return 0; }
		} // Ende 64

		function verfahren_65 ($ktonr)
		{
			if ((($ktonr[0]+$ktonr[1]) != 0) && ($ktonr[8] == 9)) {
				$val9=intval(intval($ktonr[9])*2);
				$val8=intval(intval($ktonr[8])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				$val1=intval(intval($ktonr[0])*2);
				if ($val1>9) { $val1 = "$val1"; $val1 = $val1[0] + $val1[1]; }
				if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				if ($val8>9) { $val8 = "$val8"; $val8 = $val8[0] + $val8[1]; }
				if ($val9>9) { $val9 = "$val9"; $val9 = $val9[0] + $val9[1]; }
				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$pruefstelle = 7;
			} elseif((($ktonr[8]+$ktonr[9]) == 0) || ((($ktonr[0]+$ktonr[1]) != 0) && (($ktonr[8]+$ktonr[9]) != 0))) {
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				$val1=intval(intval($ktonr[0])*2);
				if ($val1>9) { $val1 = "$val1"; $val1 = $val1[0] + $val1[1]; }
				if ($val2>9) { $val2 = "$val2"; $val2 = $val2[0] + $val2[1]; }
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7);
				$pruefstelle = 7;
			} else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				if ($val3>9) { $val3 = "$val3"; $val3 = $val3[0] + $val3[1]; }
				if ($val4>9) { $val4 = "$val4"; $val4 = $val4[0] + $val4[1]; }
				if ($val5>9) { $val5 = "$val5"; $val5 = $val5[0] + $val5[1]; }
				if ($val6>9) { $val6 = "$val6"; $val6 = $val6[0] + $val6[1]; }
				if ($val7>9) { $val7 = "$val7"; $val7 = $val7[0] + $val7[1]; }
				if ($val8>9) { $val8 = "$val8"; $val8 = $val8[0] + $val8[1]; }
				if ($val9>9) { $val9 = "$val9"; $val9 = $val9[0] + $val9[1]; }
				$ges=intval($val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$pruefstelle = 9;
			}
			$ges = "$ges";
			if ((strlen ($ges)) > 1) {
				if($ges[1]==0) {
					$pruef=0;
				}
				else {
					$pruef= 10 - $ges[1];
				}
			}
			else { $pruef= 10 - $ges; }
			if (intval($pruef)==intval($ktonr[$pruefstelle])) { return 1; }
			else { return 0; }
		} // Ende 65


		function verfahren_67 ($ktonr)
		{
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[7])) { return 1; }
			else { return 0; }
		} // Ende 67

		function verfahren_68 ($kontonummer)
		{
			$kontonummer = strval (doubleval ($kontonummer));
			$length = strlen ($kontonummer);
			$pruefstelle = $length - 1;
			if ($length == 10) {
				$overall = 0;
				$iteration = 1;
				$j = 8;
				if ($kontonummer[3] == 9) {
					while ($iteration <= 6) {
						if (($iteration % 2) == 0) {
							$produkte[$iteration] = $kontonummer[$j] * 1;
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast;
						}
						else {
							$produkte[$iteration] = $kontonummer[$j] * 2;
							if ($produkte[$iteration] > 9) {
								$produktcast = "$produkte[$iteration]";
								$this->quersumme[$iteration] = $produktcast[0] + $produktcast[1];
							}
							else {
								$produktcast = "$produkte[$iteration]";
								$this->quersumme[$iteration] = $produktcast;
							}
						}
						$overall += $this->quersumme[$iteration];
						$iteration++;
						$j--;
					}
				}
				else {
					$overall = -1;
				}
				$stelle = strlen ($overall);
				$overallcast = "$overall";
				if ($stelle == 1) {
					$pruefziffer = 10 - $overallcast;
				}
				else {
					$pruefziffer = 10 - $overallcast [$stelle - 1];
				}
				if ($pruefziffer == 10) {
					$pruefziffer = 0 ;
				}
				if ($pruefziffer == $kontonummer[$pruefstelle]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
			}
			if (($length <= 9) && ($length >= 6)) {
				// Hier Variante 1
				$overall = 0;
				$iteration = 1;
				$j = $length - 2;
				$pruefstelle = $length - 1;
				while ($iteration <= 9) {
					if (($iteration % 2) == 0) {
						$produkte[$iteration] = $kontonummer[$j] * 1;
						$produktcast = "$produkte[$iteration]";
						$this->quersumme[$iteration] = $produktcast;
					}
					else {
						$produkte[$iteration] = $kontonummer[$j] * 2;
						if ($produkte[$iteration] > 9) {
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast[0] + $produktcast[1];
						}
						else {
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast;
						}
					}
					$overall += $this->quersumme[$iteration];
					$iteration++;
					$j--;
				}
			}
			$stelle = strlen ($overall);
			$overallcast = "$overall";
			if ($stelle == 1) {
				$pruefziffer = 10 - $overallcast;
			}
			else {
				$pruefziffer = 10 - $overallcast [$stelle - 1];
			}
			if ($pruefziffer == 10) {
				$pruefziffer = 0 ;
			}
			if ($pruefziffer == $kontonummer[$pruefstelle]) {
				$return_value = 1;
			}
			else {
				// Hier Variante 2
				$overall = 0;
				$iteration = 1;
				$j = $length - 2;
				$pruefstelle = $length - 1;
				if (($length <= 9) && ($length >= 6)) {
					$pruefstelle = $length - 1;
					while ($iteration <= 9) {
						if (($iteration % 2) == 0) {
							$produkte[$iteration] = $kontonummer[$j] * 1;
							$produktcast = "$produkte[$iteration]";
							$this->quersumme[$iteration] = $produktcast;
						}
						else {
							$produkte[$iteration] = $kontonummer[$j] * 2;
							if ($produkte[$iteration] > 9) {
								$produktcast = "$produkte[$iteration]";
								$this->quersumme[$iteration] = $produktcast[0] + $produktcast[1];
							}
							else {
								$produktcast = "$produkte[$iteration]";
								$this->quersumme[$iteration] = $produktcast;
							}
						}
						if (($iteration != 6) && ($iteration != 7)) {
							$overall += $this->quersumme[$iteration];
						}
						else {
						}
						$iteration++;
						$j--;
					}
				}
				$stelle = strlen ($overall);
				$overallcast = "$overall";
				if ($stelle == 1) {
					$pruefziffer = 10 - $overallcast;
				}
				else {
					$pruefziffer = 10 - $overallcast [$stelle - 1];
				}
				if ($pruefziffer == 10) {
					$pruefziffer = 0 ;
				}
				if ($pruefziffer == $kontonummer[$pruefstelle]) {
					$return_value = 1;
				}
				else {
					$return_value = 0;
				}
			}
			if (($length == 9) && (intval ($kontonummer) >= 400000000) && (intval ($kontonummer) <= 499999999)) {
				$return_value = 3;
			}
			return $return_value;
		} // Ende 68

		function verfahren_69 ($ktonr)
		{
			if (intval ($ktonr) >= 9300000000 && intval ($ktonr) <= 9399999999) {
				$returnValue = 1; // Dies Kontonummern sind immer gueltig
			}
			else {
				if (intval ($ktonr) <= 9700000000 || intval ($ktonr) >= 9799999999) {
					$returnValue = $this->verfahren_28 ($ktonr);
				}
				if (intval ($ktonr) >= 9700000000 && intval ($ktonr) <= 9799999999 || $returnValue == 0) {
					$returnValue = $this->verfahren_29 ($ktonr);
				}
			}
			return $returnValue;
		} // Ende 69

		function verfahren_70 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);

			if ($ktonr[3] == 5 || strval ($ktonr[3]) . strval ($ktonr[4]) == "69") {
				$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
			}
			else {
				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
			}
			$rest=$ges % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende 70

		function verfahren_73 ($ktonr)
		{
			if (intval ($ktonr[2]) != 9) {
				/* Zahlendreher bei der Gewichtung
				*  richtige Reihenfolge ist 2 1 2 1 2 1
				*  "alte" Reihenfolge war   1 2 1 2 1 2 */
				$val9 = intval(intval($ktonr[8]) * 2);
				$val8 = intval(intval($ktonr[7]) * 1);
				$val7 = intval(intval($ktonr[6]) * 2);
				$val6 = intval(intval($ktonr[5]) * 1);
				$val5 = intval(intval($ktonr[4]) * 2);
				$val4 = intval(intval($ktonr[3]) * 1);
				$ges = intval($this->quersumme($val4) + $this->quersumme($val5) + $this->quersumme($val6) + $this->quersumme($val7) + $this->quersumme($val8) + $this->quersumme($val9));
				$ges = strval ($ges);
				$gesLength = strlen ($ges);
				if ($gesLength > 1) {
					for ($i = 1; $i < $gesLength; $i++) {
					  $gesEiner = $ges[$i];
					}
				}
				else {
					$gesEiner = $ges;
				}
				$rest = 10 - intval($gesEiner % 10);
				if ($rest == 10) {
					$pruef = 0;
				}
				else {
					$pruef = $rest;
				}
				if (intval($pruef) == intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}
			}
			else {
				$val9 = intval(intval($ktonr[8]) * 2);
				$val8 = intval(intval($ktonr[7]) * 3);
				$val7 = intval(intval($ktonr[6]) * 4);
				$val6 = intval(intval($ktonr[5]) * 5);
				$val5 = intval(intval($ktonr[4]) * 6);
				$val4 = intval(intval($ktonr[3]) * 7);
				$val3 = intval(intval($ktonr[2]) * 8);
				$val2 = intval(intval($ktonr[1]) * 9);
				$val1 = intval(intval($ktonr[0]) * 10);

				$ges = intval($val1 + $val2 + $val3 + $val4 + $val5 + $val6 + $val7 + $val8 + $val9);
				$rest = $ges % 11;
				if ($rest > 1) {
					$pruef = intval(11 - $rest);
				}
				else {
					$pruef = 0;
				}
				if (intval($pruef) == intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}
			}
		} // Ende 73

		function verfahren_76 ($ktonr)
		{
			$gut=0;
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*6);
			$val2=intval(intval($ktonr[1])*7);
			$ges=intval($val2+$val3+$val4+$val5+$val6+$val7);
			$rest=$ges % 11;
			if ($rest==intval($ktonr[7])) { return 1; $gut=1;}
			else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);
				$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 11;
				if ($rest==intval($ktonr[9])) { return 1; $gut=1;}
			}
			if (!$gut) { return 0; }
		} // Ende 76

		function verfahren_77 ($ktonr)
		{
			$val10=intval(intval($ktonr[9])*5);
			$val9=intval(intval($ktonr[8])*4);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);

			$ges=intval($val6+$val7+$val8+$val9+$val10);
			$rest=$ges % 11;
			if ($rest != 0) {
				$val10=intval(intval($ktonr[9])*5);
				$val9=intval(intval($ktonr[8])*4);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);

				$ges=intval($val6+$val7+$val8+$val9+$val10);
				$rest=$ges % 11;
				if ($rest != 0) {
					return 0;
				}
				else {
					return 1;
				}
			}
			else {
				return 1;
			}
		} // Ende 77

		function verfahren_78 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*1);
			$val1=intval(intval($ktonr[0])*2);
			$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9]) && strlen (strval (intval ($ktonr))) != 8) { return 1; }
			else { return 0; }
		} // Ende 78

		function verfahren_79 ($ktonr)
		{
			if ($ktonr[0] >= 3 && $ktonr[0] <= 8 && $ktonr[0] != 0) {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*1);
				$val1=intval(intval($ktonr[0])*2);
				$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
				$ges = strval ($ges);
				$gesLength = strlen ($ges);
				if ($gesLength > 1) {
					for ($i = 1; $i < $gesLength; $i++) {
						$gesEiner = $ges[$i];
					}
				}
				else {
					$gesEiner = $ges;
				}
				$rest=10-intval($gesEiner % 10);
				if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
				if (intval($pruef)==intval($ktonr[9]) && strlen (strval (intval ($ktonr))) != 8) { return 1; }
				else { return 0; }
			}
			else {
				if ($ktonr[0] != 0) {
					$val8=intval(intval($ktonr[7])*2);
					$val7=intval(intval($ktonr[6])*1);
					$val6=intval(intval($ktonr[5])*2);
					$val5=intval(intval($ktonr[4])*1);
					$val4=intval(intval($ktonr[3])*2);
					$val3=intval(intval($ktonr[2])*1);
					$val2=intval(intval($ktonr[1])*2);
					$val1=intval(intval($ktonr[0])*1);
					$ges=intval($this->quersumme($val1)+$this->quersumme($val2)+$this->quersumme($val3)+$this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8));
					$ges = strval ($ges);
					$gesLength = strlen ($ges);
					if ($gesLength > 1) {
						for ($i = 1; $i < $gesLength; $i++) {
							$gesEiner = $ges[$i];
						}
					}
					else {
						$gesEiner = $ges;
					}
					$rest=10-intval($gesEiner % 10);
					if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
					if (intval($pruef)==intval($ktonr[8]) && strlen (strval (intval ($ktonr))) != 8) { return 1; }
					else { return 0; }
				}
			}
		} // Ende 79

		function verfahren_81 ($ktonr)
		{
			if($ktonr[2] == 9) {
				return $this->verfahren_10 ($ktonr);
			}
			else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);

				$ges=intval($val4+$val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

				if (intval($pruef)==intval($ktonr[9])) { return 1; }
				else { return 0; }
			}
		} // Ende 81

		function verfahren_82 ($ktonr)
		{
			if (strval ($ktonr[2]) . strval ($ktonr[3]) == "99") {
				return $this->verfahren_10 ($ktonr);
			}
			else {
				return $this->verfahren_33 ($ktonr);
			}
		} // Ende 82

		function verfahren_84 ($ktonr)
		{
			$result = $this -> verfahren_33($ktonr);
			if(!$result) {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);

				$ges=intval($val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 7;
				if($rest > 0) {
					$pruef=intval(7 - $rest);
				}
				else {
					$pruef=0;
				}

				if (intval($pruef)==intval($ktonr[9])) {
					$result = 1;
				}
			}
			return $result;
		}

		function verfahren_85($ktonr)
		{
			$kontonummer = array();
			for($i = 0; $i < strlen($ktonr); $i++) {
				array_push($kontonummer, intval($ktonr[$i]));
			}

			// Ausnahme 99
			if ($kontonummer[2] == 9 and $kontonummer[3] == 9) {
				$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7, 8));
				$gesamt = 0;
				for ($i = 0, $stelle=2; $i < count($gewichtung); $i++,$stelle++) {
					$produkt = $kontonummer[$stelle] * $gewichtung[$i];
					$gesamt += $produkt;
				}
				$rest = $gesamt % 11;
				if ($rest == 1) return 0;
				$pruef = ($rest == 0) ? 0 : 11 - $rest;

				if ($pruef == $kontonummer[9]) {
					return 1;
				}
				return 0;
			}

			// Methode A
			$verfahren06 = $this->verfahren_06($ktonr);
			if ($verfahren06) {
				return $verfahren06;
			} else {
				// Methode B
				$verfahren33 = $this->verfahren_33($ktonr);
				if ($verfahren33) {
					return  $verfahren33;
				} else {
					// Methode C
					$stelle10 = $kontonummer[9];

					if ($stelle10 == 7 or $stelle10 == 8 or $stelle10 == 9) {
						return 0;
					}

					$gewichtung = array_reverse(array(2, 3, 4, 5, 6));
					$gesamt = 0;
					for ($i = 0, $stelle=4; $i < count($gewichtung); $i++,$stelle++) {
						$produkt = $kontonummer[$stelle] * $gewichtung[$i];
						$gesamt += $produkt;
					}

					$rest = $gesamt % 7;
					$pruef = ($rest == 0) ? 0 : 7 - $rest;

					if ($pruef == $kontonummer[9]) {
						return 1;
					} else {
						return 0;
					}
				}
			}
		} // ende 85

		function verfahren_86($ktonr)
		{
			$kontonummer = array();
			for($i = 0; $i < strlen($ktonr); $i++) {
				array_push($kontonummer, intval($ktonr[$i]));
			}

			### ausnahme
			if($ktonr[2] == 9)
			{
				# variante 1
				$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7, 8));
				$gesamt = 0;
				for($i = 0; $i < count($gewichtung); $i++) {
					$produkt = $kontonummer[2 + $i] * $gewichtung[$i];
					$gesamt += $produkt;
				}

				$rest = $gesamt % 11;
				if($rest > 1) {
					$pruef = intval(11 - $rest);
				} else {
					$pruef=0;
				}

				if(intval($pruef) == intval($ktonr[9])) {
					return 1;
				} else {
					# variante 2
					$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7, 8, 9, 10));
					$gesamt = 0;
					for($i = 0; $i < count($gewichtung); $i++) {
						$produkt = $kontonummer[$i] * $gewichtung[$i];
						$gesamt += $produkt;
					}
					$rest = $gesamt % 11;
					if($rest > 1) {
						$pruef = intval(11 - $rest);
					} else {
						$pruef=0;
					}

					if(intval($pruef) == intval($ktonr[9])) {
						return 1;
					} else {
						return 0;
					}
				}
			}


			### methode a
			$gewichtung = array_reverse(array(2, 1, 2, 1, 2, 1));
			$gesamt = 0;
			for($i = 0; $i < count($gewichtung); $i++) { ## Stelle 4 - 9
				$produkt = $kontonummer[3 + $i] * $gewichtung[$i];

				if($produkt > 9) {
					# quersumme
					$quersumme = $this -> quersumme($produkt);
					$gesamt += $quersumme;
				} else {
					$gesamt += $produkt;
				}
			}

			$rest = 10 - intval($gesamt % 10);
			if($rest == 10) {
				$pruef = 0;
			} else {
				$pruef = $rest;
			}

			if (intval($pruef) == intval($ktonr[9])) {
				return 1;
			} else {
				### methode b
				$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7));
				$gesamt = 0;
				for($i = 0; $i < count($gewichtung); $i++) {
					$produkt = $kontonummer[3 + $i] * $gewichtung[$i];
					$gesamt += $produkt;
				}
				$rest = $gesamt % 11;
				if($rest > 1) {
					$pruef = intval(11 - $rest);
				} else {
					$pruef = 0;
				}

				if(intval($pruef) == intval($ktonr[9])) {
					return 1;
				} else {
					return 0;
				}
			}
		}

		function verfahren_87($ktonr)
		{
			# http://www.bundesbank.de/download/zahlungsverkehr/zv_pz200606.pdf

			# Methode A

			# Initialisierung
//				i = Hilfsvariable (Laufvariable)
//				C2 = Hilfsvariable (Kennung, ob gerade oder ungerade Stelle bearbeitet wird)
//				D2 = Hilfsvariable
//				A5 = Hilfsvariable (Summenfeld), kann negativ werden
//				P = Hilfsvariable (zur Zwischenspeicherung der Pr�fziffer)
//				KONTO = 10-stelliges Kontonummernfeld mit
//				KONTO (i) = in Bearbeitung befindliche Stelle; der Wert an jeder Stelle kann zweistellig werden
//				TAB1; TAB2 = Tabellen mit Pr�fziffern: Tabelle TAB1 Tabelle TAB2

			$TAB1 = array(0, 4, 3, 2, 6);
			$TAB2 = array(7, 1, 5, 9, 8);

			for($j = 1; $j <= strlen($ktonr); $j++) {
				$KONTO[$j] = intval($ktonr[$j - 1]);
			}

			// Ausnahme Sachkonten
			if($KONTO[3] == 9) {
//					echo "Ausnahme:\n";

				# Ausnahme Variante 1
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);
				$val3=intval(intval($ktonr[2])*8);

				$ges = intval($val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$rest = $ges % 11;
				if($rest == 0 || $rest == 1) {
					$pruef = 0;
				}
				else {
					$pruef = intval(11 - $rest);
				}

				if (intval($pruef) == intval($ktonr[9])) {
//						echo "A:Variante1\n";
					return 1;
				} else {
					## Ausnahme Variante 2
					$val9=intval(intval($ktonr[8])*2);
					$val8=intval(intval($ktonr[7])*3);
					$val7=intval(intval($ktonr[6])*4);
					$val6=intval(intval($ktonr[5])*5);
					$val5=intval(intval($ktonr[4])*6);
					$val4=intval(intval($ktonr[3])*7);
					$val3=intval(intval($ktonr[2])*8);
					$val2=intval(intval($ktonr[1])*9);
					$val1=intval(intval($ktonr[0])*10);

					$ges = intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
					$rest = $ges % 11;
					if($rest == 0 || $rest == 1) {
						$pruef = 0;
					}
					else {
						$pruef = intval(11 - $rest);
					}
//						echo "A:Variante2\n";
					if(intval($pruef) == intval($ktonr[9])) {
						return 1;
					} else {
						return 0;
					}
				}
			}

			$i = 4;
			while($KONTO[$i] == 0) {
				$i = $i + 1;
			}

			$C2 = $i % 2;
			$D2 = 0;
			$A5 = 0;
			while($i < 10) {
				switch($KONTO[$i]) {
					case 0: $KONTO[$i] = 5;
						break;
					case 1: $KONTO[$i] = 6;
						break;
					case 5: $KONTO[$i] = 10;
						break;
					case 6: $KONTO[$i] = 1;
						break;
				}

				if($C2 == $D2) {
					if($KONTO[$i] > 5) {
						if($C2 == 0 and $D2 == 0) {
							$C2 = 1;
							$D2 = 1;
							$A5 = $A5 + 6 - ($KONTO[$i] - 6);
						}
						else {
							$C2 = 0;
							$D2 = 0;
							$A5 = $A5 + $KONTO[$i];
						}
					}
					else {
						if($C2 == 0 and $D2 == 0){
							$C2 = 1;
							$A5 = $A5 + $KONTO[$i];
						}
						else {
							$C2 = 0;
							$A5 = $A5 + $KONTO[$i];
						}
					}
				}
				else {
					if($KONTO[$i] > 5) {
						if($C2 == 0) {
							$C2 = 1;
							$D2 = 0;
							$A5 = $A5 - 6 + ($KONTO[$i] - 6);
						}
						else {
							$C2 = 0;
							$D2 = 1;
							$A5 = $A5 - $KONTO[$i];
						}
					}
					else {
						if($C2 == 0) {
							$C2 = 1;
							$A5 = $A5 - $KONTO[$i];
						}
						else {
							$C2 = 0;
							$A5 = $A5 - $KONTO[$i];
						}
					}
				}
				$i = $i + 1;
			}

			while($A5 < 0 or $A5 > 4) {
				if($A5 > 4) {
					$A5 = $A5 - 5;
				}
				else {
					$A5 = $A5 + 5;
				}
			}

			if($D2 == 0) {
				$p = $TAB1[$A5];
			}
			else {
				$p = $TAB2[$A5];
			}

			if($p == $KONTO[10]) {
				//echo "Pr�fziffer OK";
				return 1;
			}
			else {
				if($KONTO[4] == 0) {
					if($p > 4) {
						$p = $p - 5;
					}
					else {
						$p = $p + 5;
					}

					if($p == $KONTO[10]) {
						//echo "Pr�fziffer OK -";
						return 1;
					}
				}
			}

			# Methode B
			$verfahren33 = $this -> verfahren_33($ktonr);
			if($verfahren33) {
//					echo "Verfahren B";
				return $verfahren33;
			} else {
				# Methode C
//					echo "Methode C";
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);

				$ges = intval($val5+$val6+$val7+$val8+$val9);
				$rest = $ges % 7;
				if($rest == 0) {
					$pruef = 0;
				} else {
					$pruef = intval(7 - $rest);
				}

				if (intval($pruef) == intval($ktonr[9])) {
					return 1;
				} else {
					return 0;
				}
			}
			return 0;

		}

		function verfahren_88 ($ktonr)
		{
			if ($ktonr[2] == 9) {
				$val9 = intval(intval($ktonr[8]) * 2);
				$val8 = intval(intval($ktonr[7]) * 3);
				$val7 = intval(intval($ktonr[6]) * 4);
				$val6 = intval(intval($ktonr[5]) * 5);
				$val5 = intval(intval($ktonr[4]) * 6);
				$val4 = intval(intval($ktonr[3]) * 7);
				/* $val3 hinzugefuegt */
				$val3 = intval(intval($ktonr[2]) * 8);
				/*  Zeile von "else" eingefuegt */
				$ges = intval($val3 + $val4 + $val5 + $val6 + $val7 + $val8 + $val9);

			}
			else {
				$val9 = intval(intval($ktonr[8]) * 2);
				$val8 = intval(intval($ktonr[7]) * 3);
				$val7 = intval(intval($ktonr[6]) * 4);
				$val6 = intval(intval($ktonr[5]) * 5);
				$val5 = intval(intval($ktonr[4]) * 6);
				$val4 = intval(intval($ktonr[3]) * 7);
				/* $val3 entfernt */
				/* Zeile von "if" eingefuegt */
				$ges = intval($val4 + $val5 + $val6 + $val7 + $val8 + $val9);
			}
			$rest = $ges % 11;
			if ($rest > 1) {
				$pruef = intval(11 - $rest);
			}
			else {
				$pruef = 0;
			}

			if (intval($pruef) == intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}
		} // Ende 88

		function verfahren_90($ktonr)
		{
			#### methode a
			$kontonummer = array();
			for($i = 0; $i < strlen($ktonr); $i++) {
				array_push($kontonummer, intval($ktonr[$i]));
			}

			$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7));
			$gesamt = 0;
			for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 4 - 9
//					echo $gewichtung[$j] . '-';
				$produkt = $kontonummer[3 + $j] * $gewichtung[$j];
				$gesamt += $produkt;
			}

			$rest = $gesamt % 11;
			if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				#### methode B

				$gewichtung = array_reverse(array(2, 3, 4, 5, 6));
				$gesamt = 0;
				for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 5 - 9
					$produkt = $kontonummer[4 + $j] * $gewichtung[$j];
					$gesamt += $produkt;
				}
				$rest = $gesamt % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
				if (intval($pruef)==intval($ktonr[9])) {
					return 1;
				} else {
					### methode C
					$gewichtung = array_reverse(array(2, 3, 4, 5, 6));
					$gesamt = 0;
					for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 5 - 9
						$produkt = $kontonummer[4 + $j] * $gewichtung[$j];
						$gesamt += $produkt;
					}
					$rest = $gesamt % 7;
					if ($rest>1) { $pruef=intval(7 - $rest); } else { $pruef=0; }
					if (intval($pruef)==intval($ktonr[9]) && intval($ktonr[9]) < 7) {
						return 1;
					} else {
						### methode D
						$gewichtung = array_reverse(array(2, 3, 4, 5, 6));
						$gesamt = 0;
						for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 5 - 9
							$produkt = $kontonummer[4 + $j] * $gewichtung[$j];
							$gesamt += $produkt;
						}
						$rest = $gesamt % 9;
						if ($rest>1) { $pruef=intval(9 - $rest); } else { $pruef=0; }
						if (intval($pruef)==intval($ktonr[9]) && intval($ktonr[9]) < 9) {
							return 1;
						} else {
							### methode E
							$gewichtung = array_reverse(array(2, 1, 2, 1, 2));
							$gesamt = 0;
							for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 5 - 9
								$produkt = $kontonummer[4 + $j] * $gewichtung[$j];
								$gesamt += $produkt;
							}
							$rest = $gesamt % 10;
							if ($rest>1) { $pruef=intval(10 - $rest); } else { $pruef=0; }
							if (intval($pruef)==intval($ktonr[9]) && intval($ktonr[9]) < 9) {
								return 1;
							} else {
								### methode F
								$gewichtung = array_reverse(array(2, 3, 4, 5, 6, 7, 8));
								$gesamt = 0;
								for($j = 0; $j < count($gewichtung); $j++) { ## Stelle 3 - 9
									$produkt = $kontonummer[2 + $j] * $gewichtung[$j];
									$gesamt += $produkt;
								}
								$rest = $gesamt % 11;
								if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
								if (intval($pruef)==intval($ktonr[9])) {

									return 1;
								} else {

									return 0;

								}
							}
						}
					}
				}
			}
		}// Ende 90

		function verfahren_91($ktonr)
		{
			# Variante 1:
			$val6=intval(intval($ktonr[5])*2);
			$val5=intval(intval($ktonr[4])*3);
			$val4=intval(intval($ktonr[3])*4);
			$val3=intval(intval($ktonr[2])*5);
			$val2=intval(intval($ktonr[1])*6);
			$val1=intval(intval($ktonr[0])*7);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);

			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			}
			else {
				$pruef=0;
			}

			if (intval($pruef)==intval($ktonr[6])) {
				return 1;
			}

			# Variante 2:
			$val6=intval(intval($ktonr[5])*7);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*5);
			$val3=intval(intval($ktonr[2])*4);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*2);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);
			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			}
			else {
				$pruef=0;
			}

			if (intval($pruef)==intval($ktonr[6])) {
				return 1;
			}

			# Variante 3:
			$val10=intval(intval($ktonr[9])*2);
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*8);
			$val2=intval(intval($ktonr[1])*9);
			$val1=intval(intval($ktonr[0])*10);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val8+$val9+$val10);
			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			}
			else {
				$pruef=0;
			}

			if (intval($pruef)==intval($ktonr[6])) {
				return 1;
			}

			# Variante 4:
			$val6=intval(intval($ktonr[5])*2);
			$val5=intval(intval($ktonr[4])*4);
			$val4=intval(intval($ktonr[3])*8);
			$val3=intval(intval($ktonr[2])*5);
			$val2=intval(intval($ktonr[1])*10);
			$val1=intval(intval($ktonr[0])*9);

			$ges=intval($val1+$val2+$val3+$val4+$val5+$val6);
			$rest=$ges % 11;
			if ($rest>1) {
				$pruef=intval(11 - $rest);
			}
			else {
				$pruef=0;
			}

			if (intval($pruef)==intval($ktonr[6])) {
				return 1;
			}

			return 0;
		}

		function verfahren_92($ktonr)
		{
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*7);
			$val7=intval(intval($ktonr[6])*1);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*7);
			$val4=intval(intval($ktonr[3])*1);

			$ges=intval($val4 + $val5 + $val6 + $val7 + $val8 + $val9);
			$rest=10- intval($ges % 10);
			if ($rest=="10") {
				$pruef=0;
			}
			else {
				$pruef=$rest;
			}
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return 0;
			}

		} // Ende 92

		function verfahren_95 ($ktonr)
		{
			if (($ktonr >= 1 && $ktonr <= 1999999) || ($ktonr >= 396000000 && $ktonr <= 499999999) || ($ktonr >= 700000000 && $ktonr <= 79999999) || ($ktonr>=9000000 && $ktonr<=25999999)) {
				return 1;
			} else {
				return $this->verfahren_06($ktonr);
			}
			/*
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);
			$val3=intval(intval($ktonr[2])*2);
			$val2=intval(intval($ktonr[1])*3);
			$val1=intval(intval($ktonr[0])*4);
			if ($ktonr[0] != "0") {
				if (($ktonr[3] == 5) || (($ktonr[3] == 6) && ($ktonr[4] == 9))) { $ges=intval($val4+$val5+$val6+$val7+$val8+$val9); }
				else { $ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9); }
				$rest=$ges % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }
				if (intval($pruef)==intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}
			}
			else {

				if (($ktonr >= 1 && $ktonr <= 1999999) || ($ktonr >= 396000000 && $ktonr <= 499999999) || ($ktonr >= 700000000 && $ktonr <= 79999999)) {
					return 0;
				}
				else {
					return 1;
				}
			}
			*/
		} // Ende 95

		function verfahren_96($ktonr)
		{
			// Variante 1
			$verfahren_19 = $this -> verfahren_19($ktonr);
			if($verfahren_19) {
				return $verfahren_19;
			} else {
				// Variante 2
				$verfahren_00 = $this -> verfahren_00($ktonr);
				if($verfahren_00) {
					return $verfahren_00;
				} else {
					// Variante 3
					if(1300000 <= intval($ktonr) && intval($ktonr) <= 99399999) {
						return 1;
					} else {
						return 0;
					}
				}
			}
		}// Ende 96

		function verfahren_98($ktonr)
		{
			$val9=intval(intval($ktonr[8])*3);
			$val8=intval(intval($ktonr[7])*7);
			$val7=intval(intval($ktonr[6])*1);
			$val6=intval(intval($ktonr[5])*3);
			$val5=intval(intval($ktonr[4])*7);
			$val4=intval(intval($ktonr[3])*1);
			$val3=intval(intval($ktonr[2])*3);

			$ges=intval($val3+$val4+$val5+$val6+$val7+$val8+$val9);
			$rest=10- intval($ges % 10);
			if ($rest=="10") {
				$pruef=0;
			}
			else {
				$pruef=$rest;
			}
			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				return $this -> verfahren_32($ktonr);
			}
		} // Ende 98

		function verfahren_99 ($ktonr)
		{
			if (intval ($ktonr) >= 396000000 && intval ($ktonr) <= 499999999) {
				return 1;
			} else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*3);
				$val1=intval(intval($ktonr[0])*4);

				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 11;
				if ($rest>1) { $pruef=intval(11 - $rest); } else { $pruef=0; }

				if (intval($pruef)==intval($ktonr[9])) { return 1; }
				else { return 0; }
			}
		} // Ende 99

		function verfahren_A0 ($ktonr)
		{
			if (intval ($ktonr) < 1000 || intval ($ktonr[0] . $ktonr[1] . $ktonr[2] . $ktonr[3] . $ktonr[4] . $ktonr[5] . $ktonr[6]) == 0) {
				return 1;
			} else {
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*4);
				$val7=intval(intval($ktonr[6])*8);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*10);
				$val4=intval(intval($ktonr[3])*0);
				$val3=intval(intval($ktonr[2])*0);
				$val2=intval(intval($ktonr[1])*0);
				$val1=intval(intval($ktonr[0])*0);

				$ges=intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$rest=$ges % 11;
				if ($rest == 0 || $rest == 1) { $pruef=0; } else { $pruef=11-$rest; }

				if (intval($pruef)==intval($ktonr[9])) { return 1; }
				else { return 0; }
			}
		} // Ende A0

		function verfahren_A1 ($ktonr)
		{
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*1);
			$val7=intval(intval($ktonr[6])*2);
			$val6=intval(intval($ktonr[5])*1);
			$val5=intval(intval($ktonr[4])*2);
			$val4=intval(intval($ktonr[3])*1);
			$ges=intval($this->quersumme($val4)+$this->quersumme($val5)+$this->quersumme($val6)+$this->quersumme($val7)+$this->quersumme($val8)+$this->quersumme($val9));
			$ges = strval ($ges);
			$gesLength = strlen ($ges);
			if ($gesLength > 1) {
				for ($i = 1; $i < $gesLength; $i++) {
					$gesEiner = $ges[$i];
				}
			}
			else {
				$gesEiner = $ges;
			}
			$rest=10-intval($gesEiner % 10);
			if ($rest==10) { $pruef=0; } else { $pruef=$rest; }
			if (intval($pruef)==intval($ktonr[9])) { return 1; }
			else { return 0; }
		} // Ende A1

		function verfahren_A2($ktonr)
		{
			$verfahren00 = $this -> verfahren_00($ktonr);
			if($verfahren00) {
				// Variante 1
				return $verfahren00;
			} else {

				//$verfahren04 = $this -> verfahren_04($ktonr);
				//Algorithmus verfahren04 stimmt komischerweise nicht mit der Beschreibung �berein?
				//siehe: http://www.bundesbank.de/download/zahlungsverkehr/zv_pz200606.pdf

				// Variante2 ("Verfahren04")
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*3);
				$val7=intval(intval($ktonr[6])*4);
				$val6=intval(intval($ktonr[5])*5);
				$val5=intval(intval($ktonr[4])*6);
				$val4=intval(intval($ktonr[3])*7);
				$val3=intval(intval($ktonr[2])*2);
				$val2=intval(intval($ktonr[1])*3);
				$val1=intval(intval($ktonr[0])*4);

				$ges = intval($val1+$val2+$val3+$val4+$val5+$val6+$val7+$val8+$val9);
				$rest = $ges % 11;
				if($rest == 0) {
					$pruef = 0;
				}
				elseif($rest == 1) {
					return 0;
				}
				else {
					$pruef = intval(11 - $rest);
				}

				if (intval($pruef)==intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}

			}
		} // Ende A2

		function verfahren_A3($ktonr)
		{
			// variante 1
			$verfahren00 = $this -> verfahren_00($ktonr);
			if($verfahren00) {
				return $verfahren00;
			} else {
			// variante 2
				return $this -> verfahren_10($ktonr);
			}
		} // Ende A3


		function verfahren_A5($ktonr)
		{
			## variante 1
			$kontonummer = array();
			for($i = 0; $i < strlen($ktonr); $i++) {
				array_push($kontonummer, intval($ktonr[$i]));
			}

			$gewichtung = array_reverse(array(2, 1, 2, 1, 2, 1, 2, 1, 2));
			$gesamt = 0;
			for($j = 0; $j < count($gewichtung); $j++) {
				$produkt = $kontonummer[$j] * $gewichtung[$j];
				$quersumme = $this -> quersumme($produkt);
				$gesamt += $quersumme;
			}
			$rest = 10 - ($this -> getEinerStelle($gesamt));
			$pruef = $rest == 10 ? 0 : $rest;

			if (intval($pruef) == intval($ktonr[9])) {
				return 1;
			} else {
				## variante 2
				$ersteStelle = intval($ktonr[0]);
				if ($ersteStelle != 9) {
					return $this -> verfahren_10($ktonr);
				} else {
					return 0;
				}
			}
		} // Ende A5

		function verfahren_A7($ktonr)
		{
			$result = $this -> verfahren_00($ktonr);
			if(!$result) {
				$result = $this -> verfahren_03($ktonr);
			}
			return $result;
		} // Ende A7

		function verfahren_A8($ktonr)
		{
			// Ausnahme
			$dritteStelle = intval($ktonr[2]);
			if($dritteStelle == 9) {
				return$ $this -> verfahren_51($ktonr);
			}

			// Variante 1
			$val9=intval(intval($ktonr[8])*2);
			$val8=intval(intval($ktonr[7])*3);
			$val7=intval(intval($ktonr[6])*4);
			$val6=intval(intval($ktonr[5])*5);
			$val5=intval(intval($ktonr[4])*6);
			$val4=intval(intval($ktonr[3])*7);


			$ges = intval($val4 + $val5 + $val6 + $val7 + $val8 + $val9);
			$rest = $ges % 11;
			if($rest == 0) {
				$pruef = 0;
			}
			elseif($rest == 1) {
				$pruef = 0;
			}
			else {
				$pruef = intval(11 - $rest);
			}

			if (intval($pruef)==intval($ktonr[9])) {
				return 1;
			}
			else {
				// Variante 2
				$val9=intval(intval($ktonr[8])*2);
				$val8=intval(intval($ktonr[7])*1);
				$val7=intval(intval($ktonr[6])*2);
				$val6=intval(intval($ktonr[5])*1);
				$val5=intval(intval($ktonr[4])*2);
				$val4=intval(intval($ktonr[3])*1);

				$ges=intval($this->quersumme($val4) + $this->quersumme($val5) + $this->quersumme($val6) + $this->quersumme($val7) + $this->quersumme($val8) + $this->quersumme($val9));
				$ges = strval ($ges);
				$gesLength = strlen ($ges);
				if ($gesLength > 1) {
					for ($i = 1; $i < $gesLength; $i++) {
						$gesEiner = $ges[$i];
					}
				}
				else {
					$gesEiner = $ges;
				}
				$rest=10-intval($gesEiner % 10);
				if ($rest==10) {
					$pruef=0;
				}
				else {
					$pruef=$rest;
				}
				if (intval($pruef)==intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}
			}
		} // Ende A8

		function verfahren_B2($ktonr)
		{
			// variante 1
			if(0 <= intval($ktonr[0]) && intval($ktonr[0]) <= 7) {
				return $this -> verfahren_02($ktonr);
			}
			else {
			// variante 2
				return $this -> verfahren_00($ktonr);
			}
		}

		function verfahren_B3($ktonr)
		{
			// variante 1
			if(0 <= $ktonr[0] && $ktonr[0] <= 8) {
				return $this -> verfahren_32($ktonr);
			}
			elseif($ktonr[0] == 9) {
			// variante 2
				return $this -> verfahren_06($ktonr);
			}
		} // Ende B3

		function verfahren_B6($ktonr)
		{
			$stelle1 = intval($ktonr[0]);
			if(1 <= $stelle1 and $stelle1 <= 9) {
				## variante 1
				return $this -> verfahren_20($ktonr);
			} else {
				## variante 2

				/*
				Bildung der Kontonummern angegebener Bankleitzahl und Kontonummer:
				BLZ 	 Konto-Nr.
				XXX5XXXX XTPXXXXXX
				Kontonummer des ESER-Altsystems:
				XXTX-XP-XXXXXX
				*/
				$blz = $this -> blz;

				$ESER_ktonr = array();
				array_push($ESER_ktonr, intval($blz[4]));
				array_push($ESER_ktonr, intval($blz[5]));
				array_push($ESER_ktonr, intval($ktonr[2]));
				array_push($ESER_ktonr, intval($blz[7]));
				array_push($ESER_ktonr, intval($ktonr[1]));
				//array_push($ESER_ktonr, intval($ktonr[3]) ); // Pruefziffer
				array_push($ESER_ktonr, 0);
				array_push($ESER_ktonr, intval($ktonr[4]));
				array_push($ESER_ktonr, intval($ktonr[5]));
				array_push($ESER_ktonr, intval($ktonr[6]));
				array_push($ESER_ktonr, intval($ktonr[7]));
				array_push($ESER_ktonr, intval($ktonr[8]));
				array_push($ESER_ktonr, intval($ktonr[9]));

				$faktoren = array(2, 4, 8, 5, 10, 9, 7, 3, 6, 1, 2, 4);

				# Berechnung
				$sumGesamt = 0;
				for($i = 0; $i < count($ESER_ktonr); $i++) {
					$produkt = $ESER_ktonr[i] * $faktoren[i];
					$sumGesamt += $produkt;
				}

				$rest = $sumGesamt % 11;

				$gewichtUeberPruefziffer = 7;
				for($p = 0; $p < 10; $p++ ) {
					$erg = $rest + $p * 7;
					$r = $erg % 11;
					if($r == 10)
						break;
				}

				if($p == $ktonr[3]) {
					return 1;
				} else {
					return 0;
				}

			}
		} // Ende B6


		function verfahren_B1($ktonr)
		{
			$verfahren05 = $this -> verfahren_05($ktonr);
			if ($verfahren05) {
				// variante 1
				return $verfahren05;
			} else {
				// variante 2
				return $this -> verfahren_01($ktonr);
			}
		}

		function verfahren_B7($ktonr)
		{
			// Variante 1
			$iKtonr = intval($ktonr);
			if(1000000 <= $iKtonr && $iKtonr <= 5999999 ||
			   700000000 <= $iKtonr && $iKtonr <= 899999999) {
					return $this -> verfahren_01($ktonr);
			} else {
			// Variante 2
					return $this -> verfahren_01($ktonr);
			}
		} // Ende B7


		function verfahren_B8($ktonr)
		{
			$verfahren20 = $this -> verfahren_20($ktonr);
			if($verfahren20) {
				// Variante 1
				return $verfahren20;
			} else {
				// Variante 2
				return $this -> verfahren_29($ktonr);
			}
		} // Ende B8

		function verfahren_C0($ktonr)
		{
			$zweiFuehrendeNullen = (intval($ktonr{0}) == 0 and intval($ktonr{1}) == 0);
			if ($zweiFuehrendeNullen) {
				# variante 1
				$verfahren52 = $this->verfahren_52($ktonr);
				if ($verfahren52) {
					return $verfahren52;
				} else {
					# variante 2
					return $this->verfahren_20($ktonr);
				}
			} else {
				# variante 2
				return $this->verfahren_20($ktonr);
			}
		}


		function verfahren_C1($ktonr)
		{
			$einerStelle = intVal($ktonr[0]);
			if($einerStelle == 5) {
				// Variante 2

//					Die Kontonummer ist 10-stellig mit folgendem Aufbau:
//
//					KNNNNNNNNP
//
//					K = Kontoartziffer
//					N = laufende Nummer
//					P = Pr�fziffer

				$val9=intval(intval($ktonr[8])*1);
				$val8=intval(intval($ktonr[7])*2);
				$val7=intval(intval($ktonr[6])*1);
				$val6=intval(intval($ktonr[5])*2);
				$val5=intval(intval($ktonr[4])*1);
				$val4=intval(intval($ktonr[3])*2);
				$val3=intval(intval($ktonr[2])*1);
				$val2=intval(intval($ktonr[1])*2);
				$val1=intval(intval($ktonr[0])*1);

				$ges=intval($val1 + $this->quersumme($val2) + $val3 + $this->quersumme($val4) + $val5 + $this->quersumme($val6) + $val7 + $this->quersumme($val8) + $val9);

				$ges = $ges - 1;
				$rest = $ges % 11;

				if($rest == 0) {
					$pruef = 0;
				}
				else {
					$pruef = intval(10 - $rest);
				}

				if (intval($pruef) == intval($ktonr[9])) {
					return 1;
				}
				else {
					return 0;
				}

			} else {
				// Variante 1
				return $this -> verfahren_17($ktonr);

			}
		} // Ende C1


		function verfahren_C7($ktonr)
		{
			$verfahren63 = $this->verfahren_63($ktonr);
			if ($verfahren63) {
				// Variante 1
				return $verfahren63;
			} else {
				// Variante 2
				return $this->verfahren_06($ktonr);
			}
		}
	}
}