<?php
/**
* # PHP Object Oriented Library (POOL) #
*
* Class MySQL_Interface ist ein Datenbank-Layer fuer CISAM.
* Diese Klasse implementiert die Schnittstelle zu CISAM.
* Die Verbindung ist wie in PHP verbindungslos. D.h. ein
* Kommando wird erstellt und zu COBOL uebertragen. Das COBOL
* Programm wertet das Kommando aus und uebertraegt die Daten
* zurueck! Waehrend dieser Aktion wartet das PHP Script auf
* eine Antwort des Java Servers.
*
* Abfolge:
* User Oberflaeche (PHP Script) => CISAM_DAO => CISAM_Interface
* => Java Client => DEC4000 Java Server =>
* DEC4000 Shell Script => DEC4000 COBOL <- Rueckweg ->!
*
* CISAM_Interface = Schnittstelle zum Java Server, um ueber
* ein COBOL Programm CISAM Daten abzufragen.
*
* $Log: CISAM_Interface.class.php,v $
* Revision 1.11  2007/04/25 11:54:54  schmidseder
* Fehlermeldung berichtigt
*
* Revision 1.10  2006/10/11 08:38:35  manhart
* Fix Leerzeilen
*
* Revision 1.9  2006/04/12 10:38:34  manhart
* done
*
* Revision 1.8  2005/02/14 15:16:17  manhart
* k
*
* Revision 1.7  2005/02/14 15:13:47  manhart
* Fix Andi
*
* Revision 1.6  2004/12/07 08:28:00  horvath
* -
*
* Revision 1.5  2004/12/06 12:56:48  manhart
* executes dec4000 commando without Java!
*
* Revision 1.4  2004/11/09 15:13:04  horvath
* -
*
* Revision 1.3  2004/11/09 09:21:04  manhart
* no message
*
* Revision 1.2  2004/10/26 15:15:40  manhart
* comments
*
* Revision 1.1.1.1  2004/09/21 07:49:25  manhart
* initial import
*
* Revision 1.6  2004/06/21 07:57:50  manhart
* test
*
* Revision 1.5  2004/05/05 07:26:17  manhart
* nix
*
* Revision 1.4  2004/04/16 12:35:34  manhart
* Fix (Fehlermeldungen)
*
* Revision 1.2  2004/04/13 13:16:20  manhart
* update
*
* Revision 1.1  2004/04/01 15:08:07  manhart
* Initial Import
*
*
 * @version $Id: CISAM_Interface.class.php,v 1.11 2007/04/25 11:54:54 schmidseder Exp $
 * @version $Revision: 1.11 $
 *
 * @see DataInterface.class.php
 * @since 2004/03/31
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CISAM_LAYER'))
{
	#### Prevent multiple loading
	define('CISAM_LAYER', 'cisam5');

	/**
	 * CISAM_Interface
	 *
	 * Siehe Datei fuer ausfuehrliche Beschreibung!
	 *
	 * @package pool
	 * @author Alexander Manhart <alexander.manhart@freenet.de>
	 * @version $Id: CISAM_Interface.class.php,v 1.11 2007/04/25 11:54:54 schmidseder Exp $
	 * @access public
	 **/
	class CISAM_Interface extends DataInterface
	{
		//@var string Entaelt den Host (Client)
		//@access private
		var $host = '';

		//@var string Enthaelt den Pfad zum Java Programm
		//@access private
		var $java = '';

		//@var string Enthaelt den Pfad zum Wochenblatt Java Client
		//@access private
		var $class_path = '';

		//@var array Query Ergebnis
		//@access private
		var $query_result = null;

		var $error;

		/**
		* CISAM_Interface::CISAM_Interface()
		*
		* Constructor
		*
		* @access public
		*/
		function __construct()
		{
		}

		/**
		 * CISAM_Interface::setOptions()
		 *
		 * Sets up the object.
		 *
		 * Einstellungen:
		 *
		 * host = (string) Java Server Host
		 * class_path = (string) Java Klassenpfad
		 * java = (string) Pfad zu java
		 *
		 * @param array $Packet Einstellungen
		 * @return boolean true
		 **/
		function setOptions($Packet)
		{
			if (array_key_exists('host', $Packet)) {
				$this -> host =  $Packet['host'];
			}
			else {
				$this -> raiseError(__FILE__, __LINE__, 'MySQL_db::setOptions Bad Packet: no key "host"');
				return false;
			}

			if (array_key_exists('class_path', $Packet)) {
				$this -> class_path =  $Packet['class_path'];
			}
			else {
				$this -> raiseError(__FILE__, __LINE__, 'MySQL_db::setOptions Bad Packet: no key "class_path"');
				return false;
			}

			if (array_key_exists('java', $Packet)) {
				$this -> java =  $Packet['java'];
			}
			else {
				$this -> java = '/usr/lib/java/bin/java';
			}

			return true;
		}

		/**
		 * Fuehrt Java Client mit einem Kommando aus und wartet auf ein Ergebnis. Hinweis: bei Problemen mit der Darstellung im Browser muss darauf geachtet werden, dass HTML den Zeichensatz ISO-8859-1 verwendet und eventl. mit htmlentitities umwandeln
		 *
		 * @param string $params
		 * @param string $program
		 * @return
		 **/
		function query($params, $program)
		{
//				$cmd = $this -> java . ' -classpath ' . $this -> class_path . ' StartClient LOW -h' .
//					$this -> host . ' -aReturnCommandOutputByParameter -k\'' . $program . ' ' . $params . '' . '\'';
			$cmd = 'rsh '.$this->host.' \'TERM=xterm '.$program.' '.$params.'\'';

/*				if(IS_TESTSERVER) {
				$fh = fopen('/home/manhart/public_html/debug.txt', 'a');
				fwrite($fh, $cmd."\n", strlen($cmd));
				fclose($fh);
			}
			return array();*/
			exec($cmd, $lines, $return_var);

//				echo $cmd . '<br>';
//				echo pray($lines);

			if($return_var != 0) {
				$this->error['message'] = 'CISAM Script Aufruf fehlgeschlagen ('.$cmd.'). Die Ausgabe enthaelt '.print_r($lines, true).'. Der Rueckgabewert ist '.$return_var.'!';
				$records = false;
			}
			else {
				$records = array();
//					echo pray($lines);
				// ERROR: Could not execute the command
/*					$numLines = count($lines);
				for($i=0; $i<$numLines-1; $i++) {
					if(trim($lines[$i]) == '') array_shift($lines);
				}
*/
				$first_line = array_shift($lines);
				$first_line = array_shift($lines);
				if (substr(ltrim($first_line), 0, 6) == 'FEHLER' or
					substr(ltrim($first_line), 0, 6) == 'ERROR' or
					substr(ltrim($first_line), 0, 7) == 'ABBRUCH') {
					$this -> error['message'] = $first_line;
					$records = false;
				}
				else {
//					$fp=fopen('/home/horvath/public_html/cisam_testline.txt', 'w');
					$header = explode(';', $first_line);
					if (is_array($lines)) {
						$i=0;
						foreach ($lines as $line) {
							if (empty($line)) {
								continue;
							}

							$record = explode(';', $line);
							if (!is_array($record)) {
								continue;
							}

							$z = 0;
							foreach($record as $value) {
								$records[$i][$header[$z]] = $value;
								$z++;
							}
							$i++;
						}
					}
					else {
					}
					//fclose($fp);
				}
			}
			return $records;
		}

		/**
		 * CISAM_Interface::count()
		 *
		 * not yet implemented
		 *
		 * @param string $params
		 * @param string $program
		 * @return
		 **/
		function count($params, $program)
		{
		}

		function convertChars($line)
		{
			$line = str_replace(
				array(chr(63)),
					array('�'), $line);
			return $line;
		}

		function getErrormsg()
		{
			return $this -> error['message'];
		}

		function getError()
		{
			$result['code'] = '';
			$result['message'] = $this -> error['message'];

			return $result;
		}

		/**
		 * CISAM_Interface::close()
		 *
		 * Closes all client links
		 *
		 * @access public
		 * @return
		 **/
		function close()
		{
		}

		/**
		 * CISAM_Interface::escapestring()
		 *
		 * Maskiert Anfuehrungszeichen: "
		 *
		 * @param string $string Text
		 * @return string Maskierter Text
		 **/
		function escapestring($string)
		{
			return str_replace('"', '\"', $string);
		}
	}
}