<?php
/**
* Filename.......: example.4.php
* Project........: HTML Mime Mail class
* Last Modified..: 15 July 2002
*/

        error_reporting(E_ALL);
	require_once('../Object.class.php');
	require_once('mimePart.class.php');
	include('HtmlMimeMail.class.php');

/**
* Example of usage. This example shows
* how to use the class to send Bcc:
* and/or Cc: recipients.
*
* Create the mail object.
*/
	$mail = new htmlMimeMail();

/**
* We will just send a text email
*/
	$text = $mail->getFile('example.txt');
	$mail->setText($text);

/**
* Send the email using smtp method. The setSMTPParams()
* method simply changes the HELO string to example.com
* as localhost and port 25 are the defaults.
*/
	$mail->setSMTPParams('localhost', 25, 'develop-la01');
	$mail->setReturnPath('manhart@wochenblatt.de');
	$mail->setBcc('alexander.manhart@wochenblatt.de');
	$mail->setCc('Carbon Copy <manhart@wochenblatt.de>');

	$result = $mail->send(array('manhart@wochenblatt.de'), 'smtp');

	// These errors are only set if you're using SMTP to send the message
	if (!$result) {
		print_r($mail->errors);
	} else {
		echo 'Mail sent!';
	}
?>