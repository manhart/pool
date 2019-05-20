<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 *
 * Worms.class.php
 *
 * Analyse and return http worms attack detected on apache access logs
 *
 * @date $Date: 2004/09/21 07:49:25 $
 * @version $Id: Worms.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2004-03-24
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 *
 * generated using DxPHPClassBuilder by Hatem
 * @url http://www.dynamix-tn.com
 *
 *
 * $worm = new worms;
 * echo $worm->get_apache_worms();
 */

// Test require_once('Object.class.php');

/**
* Definition of Trigger Words
*/
define("TRIGGER1", "GET \/default\.ida\?NNNNNN" ); /* CodeRed I  */
define("TRIGGER2", "GET \/default\.ida\?XXXXXX" ); /* CodeRed II */
define("TRIGGER3", "GET \/scripts\/root\.exe" ); /* Nimda */
//	define("TRIGGER4", "" ); /* W32.Klez */

class Worms extends PoolObject
{
	/**
	* @var	accesslog
	* @see 	_set_accesslog(), _get_accesslog()
	* @access public
	*/
	var $accesslog = '/var/log/httpd/access_log';

	/**
	* @var	hackers
	* @see 	_set_hackers(), _get_hackers()
	* @access public
	*/
	var $hackers = array();

	/**
	* @var	counter
	* @see 	_set_counter(), _get_counter()
	* @access public
	*/
	var $counter = array(
		'codered1'	=> 0,
		'codered2'	=> 0,
		'nimda'		=> 0
	);

	/**
	* @var result
	* @access public
	*/
	var $result;


	/**
	* Class worms constructor
	*/
	function __construct()
	{
		parent::__construct();
	}

	/**
	* Worms::get_apache_worms()
	*
	* @param	none
	*
	* @return	result of anaylising worms on access log
	* @access	public
	*
	* @return
	**/
	function get_apache_worms()
	{
		$fd = fopen($this -> accesslog, 'r');

		while ($x = fgets($fd,1024)) {
			list($ip , , ,$time , $GMT, , , $f, , , $referer , ) = explode(" ", $x);
			if (ereg("/*.".TRIGGER1.".*/", $x, $parts))	{
				$this->result .= "<b><font color=red>CodeRed I <small>WORM</small> Attack Detected</font></b> Hacker IP : <b>$ip</b> - Date : <b>$time $GMT</b><br>\n";
				array_push($this->hackers, $x);
				$this->counter[codered1]++;
			}

			if (ereg("/*.".TRIGGER2.".*/", $x, $parts)) {
				$this->result .= "<b><font color=red>CodeRed II <small>WORM</small> Attack Detected</font></b> Hacker IP : <b>$ip</b> - Date : <b>$time $GMT</b><br>\n";
				array_push($this->hackers, $x);
				$this->counter[codered2]++;
			}

			if (ereg("/*.".TRIGGER3.".*/", $x, $parts)) {
				$this->result .= "<b><font color=red>Nimda <small>WORM</small> Attack Detected</font></b> Hacker IP : <b>$ip</b> - Date : <b>$time $GMT</b><br>\n";
				array_push($this->hackers, $x);
				$this->counter[nimda]++;
			}
		}
		return $this->report();
	}

	/**
	 * Worms::report()
	 *
	 * Personalize the HTML report here
	 *
	 * @return string HTML Report
	 **/
	function report()
	{

		$this->result .= "\n\n<br>
			<b>Apache Worms attack Analyser : </b><br><br>\n
			Number of worms attack detected : ".sizeof($this->hackers)." Attacks<br>\n
			N� CodeRed I Attacks: ".$this->counter[codered1]." Attacks<br>\n
			N� CodeRed II Attacks: ".$this->counter[codered2]." Attacks<br>\n
			N� Nimda Attacks: ".$this->counter[nimda]." Attacks<br>\n
			";

		return $this->result;
	}

	/**
	 * Worms::_get_accesslog()
	 *
	 * Return accesslog value
	 *
	 * @access private
	 * @return return accesslog	value
	 * @see var $accesslog
	 **/
	function _get_accesslog()
	{
		return $this -> accesslog;
	}

	/**
	 * Worms::_get_hackers()
	 *
	 * Return hackers value
	 *
	 * @return return hackers	value
	 * @see var $hackers
	 **/
	function _get_hackers()
	{
		return $this -> hackers;
	}

	/**
	 * Worms::_get_counter()
	 *
	 * Return counter value
	 *
	 * @return return counter	value
	 * @see var $counter
	 **/
	function _get_counter()
	{
		return $this->counter;
	}

	/**
	 * Worms::_set_accesslog()
	 *
	 * Set $accesslog value
	 *
	 * @param $_accesslog	the variable value to set
	 * @see var $accesslog
	 **/
	function _set_accesslog($_accesslog)
	{
	   $this -> accesslog = $_accesslog;
	}

	/**
	 * Worms::_set_hackers()
	 *
	 * Set $hackers value
	 *
	 * @param $_hackers	the variable value to set
	 * @see var $hackers
	 **/
	function _set_hackers($_hackers)
	{
		$this->hackers = $_hackers;
	}

	/**
	 * Worms::_set_counter()
	 *
	 * Set $counter value
	 *
	 * @param $_counter	the variable value to set
	 * @see var $counter
	 **/
	function _set_counter($_counter)
	{
		$this->counter = $_counter;
	}
}
/*
$worm = new Worms;
echo $worm->get_apache_worms();
*/