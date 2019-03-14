<?php
/**
* POOL (PHP Object Oriented Library): die Datei Object.class.php enthaelt die Grundklasse, der Uhrahn aller Objekte.
*
* Die Klasse Nil ist ein NULL Objekt und hat keine Bedeutung (wie in Pascal/Delphi).<br>
* Die Klasse Xception integriert eine Fehlerbehandlung.
*
* Vermerk Author:<br>
* Ich will an diesem System nichts verkomplizieren, keep it simple stupid.
*
* Letzte Änderung am: $Date: 2007/02/16 07:46:28 $
*
* @version $Id: Exception.class.php,v 1.18 2007/02/16 07:46:28 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-07-10
* @author Alexander Manhart <alexander.manhart@freenet.de>
* @link http://www.misterelsa.de
* @package pool
*/

if(!defined('CLASS_EXCEPTION')) {
    /**
     * Verhindert mehrfach Einbindung der Klassen (prevent multiple loading)
     * @ignore
     */
    define('CLASS_EXCEPTION',			1);

    /**
     * Erzwingt Fehler mittels PHP trigger_error.
     */
    define('POOL_ERROR_LOGFILE',	2);
    /**
     * Erzwingt Fehlerausgabe am Bildschirm.
     */
    define('POOL_ERROR_DISPLAY',	4);
    /**
     * Sendet Fehlermail.
     */
    define('POOL_ERROR_MAIL',		8);
    /**
     * Schreibt Fehler in die Datenbank
     */
    define('POOL_ERROR_DB',			16);
    /**
     * Leitet Fehler an Callback Funktion weiter.
     */
    define('POOL_ERROR_CALLBACK',	32);
    /**
     * Fehler wird an den PHP Debugger gesendet.
     */
    define('POOL_ERROR_DEBUG',		64);
    /**
     * Stoppt die Ausfuehrung des Programms mit der Fehlermeldung.
     */
    define('POOL_ERROR_DIE',		128);

    ini_set('track_errors', true);

    /**
     * Klasse Xception dient zur Fehlerbehandlung.
     *
     * @package pool
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @access public
     */
    class Xception
    {
        /**
         * Enthaelt Fehlertext.
         *
         * @var string $message Fehlermeldung
         * @access protected
         */
        var $message='Unknow Exception';

        /**
         * Enthaelt Fehlercode.
         *
         * @var int $code Fehlercode (optional)
         * @access protected
         */
        var $code=0;

        /**
         * Zeitstempel der Xception
         *
         * @var int Unix Zeitstempel
         * @access protected
         */
        var $timestamp;

        /**
         * Backtrace enthaelt Einzelschritt-Fehlersuche / Fehlerprotokollierung.
         *
         * @var array $backtrace Programm - Ablaufverfolgung
         * @access private
         */
        var $backtrace=array();

        /**
         * Maximale Größe eines Backtrace (der in die DB oder per E-Mail versendet wird)
         *
         * @var int
         */
        var $maxBacktraceSize=32768; //32*1024; 32KB

        /**
         * Fehler-Modus als Bit-Feld
         *
         * @var int Bit-Feld (siehe Konstanten POOL_ERROR_*)
         */
        var $mode = 0;

        /**
         * Assoziatives Array enthält "magische" Daten: __FILE__, __LINE__, __CLASS__, __FUNCTION__ (siehe PHP Doku -> magical constants). Das Array kann auch vom Programmierer spezifisch definierte Daten enthalten!
         *
         * @var array
         */
        var $magicInfo=array();

        /**
         * ID der ErrorLog in die Datenbank
         *
         * @var int
         * @access private
         */
        var $__last_insert_id=0;

        /**
         * Konstruktur erwartet Fehlertext, Fehlercode und optional Optionen wie File, Line und Callback.
         *
         * @param string $message Fehlertext
         * @param int $code Fehlercode
         * @param array $backtrace Optionen wie __FILE__, __LINE__ und Callback-Funktion
         * @access public
         */
        function __construct($message=null, $code=0, $magicInfo=array(), $mode=null)
        {
/*				if (version_compare(phpversion(), '4.3.0', '>=')) {
                // you're on 4.3.0 or later
                $backtrace = debug_backtrace();
                array_shift($backtrace);
                $this->backtrace = $backtrace;
            }*/
            $this->construct($message, $code, $magicInfo, $mode);
        }

        /**
         * __construct PHP 5 compatible (siehe Konstruktor).
         *
         * @access private
         */
        function construct($message=null, $code=0, $magicInfo=array(), $mode)
        {
            if (version_compare(phpversion(), '4.3.0', '>=')) {
                // you're on 4.3.0 or later
                $backtrace = debug_backtrace();
                array_shift($backtrace);
                $this->backtrace = $backtrace;
            }

            if(is_null($magicInfo)) $magicInfo = array();
            if (!is_null($message)) {
                $this->message = $message;
            }

            $this->code = $code;
            $this->magicInfo = $magicInfo;
            $this->timestamp = time();
            $this->mode = $mode;

            $ExceptionHandler = null;
            if(global_exists('DEFAULT_EXCEPTION_HANDLER')) {
                $ExceptionHandler = &getGlobal('DEFAULT_EXCEPTION_HANDLER');
            }

            if(is_a($ExceptionHandler, 'ExceptionHandler')) {
                $ExceptionHandler->add($this);
            }
            else {
                $EXCEPTION_INSTANCES = array();
                if(global_exists('EXCEPTION_INSTANCE_STACK')) {
                    $EXCEPTION_INSTANCES = &getGlobal('EXCEPTION_INSTANCE_STACK');
                }
                $EXCEPTION_INSTANCES[] = &$this;
                setGlobal('EXCEPTION_INSTANCE_STACK', $EXCEPTION_INSTANCES);
            }
        }

        /**
         * Erzeugt eine Xception
         *
         * @static
         * @access public
         * @param string $message Fehlermeldung
         * @param int $code Fehlercode (z.B. PHP Codes E_USER_WARNING)
         * @param array $magicInfo Magic Infos bekommt man von der Funktion magicInfo()
         * @param int $mode Fehler-Modus als Bit-Wert
         * @see magicInfo()
         * @return Xception
         */
        function create($message=null, $code=0, $magicInfo=array(), $mode=null)
        {
            $Xception = new Xception($message, $code, $magicInfo, $mode);
            return $Xception;
        }

        /**
         * Gibt geparste Fehlermeldung zurück.
         *
         * @access public
         * @return string Fehlermeldung
         */
        function getMessage()
        {
            return $this -> parseMessage($this -> message);
        }

        /**
         * Parst die Fehlermeldung nach Platzhalter und wandelt Array Informationen in HTML oder Text um.
         *
         * @access protected
         * @param string $message Fehlermeldung
         * @param boolean $html TRUE=HTML-Format, FALSE=Text-Format
         * @param boolean $self TRUE=parst sich selbst, sprich Platzhalter {MESSAGE}
         * @return string Fehlermeldung
         */
        function parseMessage($message, $html=true, $self=false)
        {
            $ayBacktrace = $this -> getBacktrace();
            // Args werden aus dem Backtrace gelöscht:
            // ab und zu kam es vor, dass der Backtrace die Max Memory Size von PHP überstieg!
            // aber erst beim Uwandeln von dem Array in HTML mit array2html (print_r)
            for($i=0; $i<count($ayBacktrace); $i++) {
                unset($ayBacktrace[$i]['args']);
            }
            $ayOther = $this -> getOther();
            if($html==true) {
                $backtrace = pray($ayBacktrace);
                $other = pray($ayOther);
            }
            else {
                $backtrace = print_r($ayBacktrace, true);
                $other = print_r($ayOther, true);
            }
            if(strlen($backtrace) > $this -> maxBacktraceSize) {
                $backtrace = substr($backtrace, 0, $this -> maxBacktraceSize);
            }
            unset($ayBacktrace);


            $search = array('{CODE}', '{DATETIME}', '{TIMESTAMP}', '{FILE}', '{LINE}', '{CLASS}', '{FUNCTION}', '{BACKTRACE}', '{OTHER}', '{LAST_INSERT_ID}');
            $replace = array($this -> getCode(), $this -> getDateTime(), $this -> getTimestamp(), $this -> getFile(), $this -> getLine(),
                $this -> getClass(), $this -> getFunction(), $backtrace, $other, $this -> __last_insert_id);
            unset($backtrace, $other);

            if($self==true) {
                array_push($search, '{MESSAGE}');
                array_push($replace, $this -> getMessage());
            }

            if(is_array($ayOther) and sizeof($ayOther) > 0) {
                $bufOther = array();
                foreach($ayOther as $key => $value) {
                    $bufOther['{' . $key . '}'] = $value;
                }
                $search = array_merge($search , array_keys($bufOther));
                $replace = array_merge($replace, array_values($bufOther));
                unset($bufOther);
            }
            unset($ayOther);

            for($i=0; $i<sizeof($replace); $i++) {
                if($replace[$i]==='') $replace[$i] = '-';
            }

            $message = str_replace($search, $replace, $message);
            unset($search, $replace);
            return $message;
        }

        /**
         * Liefert alle Fehler-Informationen als Array (Code, Meldung, Zeitstempel, Backtrace, Zeile, Datei...)
         *
         * @access public
         * @return array
         */
        function getData()
        {
            $data = array(
                'code' => $this -> getCode(),
                'message' => $this -> getMessage(),
                'timestamp' => $this -> getTimestamp(),
                'backtrace' => $this -> getBacktrace()
            );
            $data = array_merge($data, $this -> magicInfo);
            return $data;
        }

        /**
         * Gibt Fehlercode aus.
         *
         * @access public
         * @return int Fehlercode
         */
        function getCode()
        {
           return $this -> code;
        }

        /**
         * Liefert Dateiname, in der der Fehler auftrat, zurueck.
         *
         * @access public
         * @return string Dateiname
         */
        function getFile()
        {
            if (isset($this -> magicInfo['file']))
                return $this -> magicInfo['file'];
            else
                return '';
        }

        /**
         * Liefert Quellcode-Zeile, in der der Fehler auftrat, zurueck.
         *
         * @access public
         * @return int Quellzeile
         */
        function getLine()
        {
            if (isset($this -> magicInfo['line']))
                return $this -> magicInfo['line'];
            else
                return '';
        }

        /**
         * Liefert Name der Klasse, in der der Fehler auftrat.
         *
         * @access public
         * @return string Name der Klasse
         */
        function getClass()


        {
            if(isset($this -> magicInfo['class']))
                return $this -> magicInfo['class'];
            else
                return '';
        }

        /**
         * Liefert Name der Funktion, in der der Fehler auftrat.
         *
         * @access public
         * @return string Name der Funktion
         */
        function getFunction()
        {
            if(isset($this -> magicInfo['function']))
                return $this -> magicInfo['function'];
            else
                return '';
        }

        /**
         * Gibt die Ablaufverfolgung bis zum Zeitpunkt des Fehlerauftritts aus. Wird erst ab PHP 4.3.0 unterstuezt.
         *
         * @access public
         * @return array Backtrace (siehe debug_backtrace in der PHP Doku)
         */
        function getBacktrace()
        {
            return $this -> backtrace;
        }


        /**
         * Liefert den Fehler-Modus
         *
         * @return string
         */
        function getMode()
        {
            return $this -> mode;
        }

        /**
         * Setzt den Fehler-Modus
         *
         * @param string $mode
         */
        function setMode($mode)
        {
            $this -> mode = $mode;
        }

        /**
         * Liefert die Zeit als der Fehler auftrat.
         *
         * @return int Unix Zeitstempel
         */
        function getTimestamp()
        {
            return $this -> timestamp;
        }

        /**
         * Liefert die Zeit als der Fehler auftrat formatiert.
         *
         * @param unknown_type $format
         * @return unknown
         */
        function getDateTime($format='%d.%m.%Y %H:%M:%S')
        {
            return formatDateTime($this -> timestamp, $format);
        }

        /**
         * Liefert vom Programmierer spezifierte Daten (sind in den magicInfo's enthalten)
         *
         * @return array
         */
        function getOther()
        {
            $magicInfo = $this -> magicInfo;
            unset($magicInfo['file']);
            unset($magicInfo['line']);
            unset($magicInfo['class']);
            unset($magicInfo['function']);
            return $magicInfo;
        }

        /**
         * Setzt ID, wenn Fehler in Datenbank geschrieben wird (! speziell für ExceptionHandler)
         *
         * @param int $last_insert_id ID des Datensatzes
         * @access private
         */
        function __setLastInsertId($last_insert_id)
        {
            $this -> __last_insert_id = $last_insert_id;
        }

        /**
         * Wirft die Xception aus, z.B. am Bildschirm, per E-Mail oder in eine Logdatei, etc. Existiert kein ExceptionHandler, werden die Fehlermeldung wie von PHP gehandhabt per trigger_error ausgegeben.
         *
         * Wohin die Fehlermeldung geschrieben wird, bestimmt der Fehler-Modus (Mode)
         * TODO: projekt properties
         *
         * @access public
         * @param const $mode Fehlermodus (siehe POOL_ERROR_* Konstanten)
         */
        function raiseError($triggerErrorType=E_USER_WARNING)
        {
            #$ExceptionHandlerClass = 'ExceptionHandler';
            $ExceptionHandler = null;
            if(global_exists('DEFAULT_EXCEPTION_HANDLER')) {
                $ExceptionHandler = &getGlobal('DEFAULT_EXCEPTION_HANDLER');
            }

            if(is_a($ExceptionHandler, 'ExceptionHandler')) {
                /* @var $ExceptionHandler ExceptionHandler */
                $ExceptionHandler->handle($this);
            }
            else {
                restore_error_handler();
                if($this->getMode() == POOL_ERROR_DIE) $triggerErrorType=E_USER_ERROR;
                trigger_error($this->getMessage(), $triggerErrorType);
            }
        }
    }


    class ExceptionHandler
    {
        /**
         * Fehlermodus als Bit-Feld
         *
         * @var const $mode Fehlermodus als Bit-Feld (Standard: POOL_ERROR_TRIGGER)
         * @access private
         */
        var $mode = 0;

        var $logFile = '';
        var $mailAddress = array();
        var $from = 'ExceptionHandler';
        var $mailFrom = 'no-reply@yourdomain.de';
        var $mailFormat = 'HTML';
        var $mailSubject = 'PHP error_log message';
        var $displayFormat = 'HTML';
        var $logText = "{DATETIME} \"{MESSAGE}\" (DATEI: \"{FILE}\" ZEILE: \"{LINE}\" KLASSE: \"{CLASS}\" FUNCTION: \"{FUNCTION}\")\n";
        var $displayText = "Fehlermeldung: {MESSAGE}\nZeitstempel: {DATETIME}\n\nDatei: {FILE}\nZeile: {LINE}\nKlasse: {CLASS}\nFunktion {FUNCTION}\n\nProgrammspezifische Informationen\n{OTHER}\n\nDebug Backtrace\n{BACKTRACE}";
        var $displayHTML = '<table border="0" cellpadding="0" cellspacing="0" style="font-family: Times New Roman; color: #FF0000">
              <tr>
                <td>Fehlermeldung:</td>
                <td><b>{MESSAGE}</b></td>
              </tr>
              <tr>
                <td>Zeitstempel:</td>
                <td>{DATETIME}</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td>Datei:</td>
                <td>{FILE}</td>
              </tr>
              <tr>
                <td>Zeile:</td>
                <td>{LINE}</td>
              </tr>
              <tr>
                <td>Klasse:</td>
                <td>{CLASS}</td>
              </tr>
              <tr>
                <td>Funktion:</td>
                <td>{FUNCTION}</td>
              </tr>
              <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
              </tr>
              <tr>
                <td colspan="3"><u>Programmspezifische Informationen</u><br>
                {OTHER}<br>
                <br>
                <u>Debug Backtrace</u><br>
                {BACKTRACE}</td>
              </tr>
            </table>
            <hr>';

        var $ExceptionList=array();

        /**
         * Bei der Behandlung eines Fehlers wird die Callback Funktion aufgerufen.
         *
         * @var mixed $callback Funktion oder Klasse -> Methode (als Array)
         * @access private
         */
        var $callback = '';
        var $interfaces = null;
        var $tabledefine = '';
        var $additionalMagicInfo = array();

        var $old_error_handler;

        /**
         * Xception
         *
         * @var Xception
         */
        var $E;

        /**
         * Fehlertyp fuer trigger_error (siehe PHP Funktion trigger_error)
         *
         * E_ALL             - All errors and warnings
         * E_ERROR           - fatal run-time errors
         * E_WARNING         - run-time warnings (non-fatal errors)
         * E_PARSE           - compile-time parse errors
         * E_NOTICE          - run-time notices (these are warnings which often result
         *                     from a bug in your code, but it's possible that it was
         *                     intentional (e.g., using an uninitialized variable and
         *                     relying on the fact it's automatically initialized to an
         *                     empty string)
         * E_CORE_ERROR      - fatal errors that occur during PHP's initial startup
         * E_CORE_WARNING    - warnings (non-fatal errors) that occur during PHP's
         *                     initial startup
         * E_COMPILE_ERROR   - fatal compile-time errors
         * E_COMPILE_WARNING - compile-time warnings (non-fatal errors)
         * E_USER_ERROR      - user-generated error message
         * E_USER_WARNING    - user-generated warning message
         * E_USER_NOTICE     - user-generated notice message
         *
         * @var const $defaultTriggerErrorType Fehlertyp
         * @access private
         */
//			var $defaultTriggerErrorType = E_USER_NOTICE;

        function __construct($mode=POOL_ERROR_DISPLAY, $error_handler='pool_error_handler')
        {
            $this->from = $_SERVER['SERVER_NAME'];
            $this->mode = $mode;

            setGlobal('DEFAULT_EXCEPTION_HANDLER', $this);

            if(function_exists($error_handler)) {
                if(version_compare(phpversion(), '5.0.0', '>=')) {
                    $this->old_error_handler = set_error_handler($error_handler, error_reporting());
                }
                else {
                    $this->old_error_handler = set_error_handler($error_handler);
                }
            }
        }

        function __isMailHTML()
        {
            return ($this -> mailFormat == 'HTML');
        }

        function __isDisplayHTML()
        {
            return ($this -> displayFormat == 'HTML');
        }

        function __getMessageAsHTML()
        {
            $E = &$this -> E;
            return $E -> parseMessage($this -> displayHTML, true, true);
        }

        function __getMessageAsText($text='')
        {
            $E = &$this -> E;
            if(strlen($text)==0) $text = $this -> displayText;
            return $E -> parseMessage($text, false, true);
        }

        function __error2display()
        {
            if($this -> __isDisplayHTML()) {
                $message = $this -> __getMessageAsHTML();
            }
            else {
                $message = $this -> __getMessageAsText($this -> displayText);
            }

            print($message);
        }

        function __error2logfile()
        {
            if($this -> logFile) {
                # schreibt Error in das angegebene Logfile
                error_log($this -> __getMessageAsText($this -> logText), 3, $this -> logFile);
            }
            else {
                # wie error_log in der /etc/php.ini eingestellt ist.
                error_log($this -> __getMessageAsText($this -> logText), 0);
            }
        }

        function __error2mailaddress()
        {
            if($this->__isMailHTML()) {
                $message = '<body>'.$this->__getMessageAsHTML().'</body>';
                $extra_headers = "MIME-Version: 1.0\nContent-Type:text/html; charset=\"ISO-8859-1\"\n";
            }
            else {
                $message = $this->__getMessageAsText($this->displayText);
            }
            if(count($this->mailAddress)) {
                $mailAddress = implode(',', $this->mailAddress);
                // PHP 5.2.5, Subject wird nicht ersetzt, nur dran gehängt (siehe Mail Header)!
                error_log($message, 1, $mailAddress, $extra_headers.'From: '.$this->from.' <'.$this->mailFrom.'>'."\n".'Subject: '.$this->mailSubject."\n");
            }
        }

        function __error2callback()
        {
            $E = &$this -> E;

            $continue = true;
            if(!$this -> callback) return;

            if(is_array($this -> callback)) {
                eval("\$continue = \$this -> callback[0] -> " . $this -> callback[1] . "(&\$E);");
            }
            else {
                eval("\$continue = " . $this -> callback . "(&\$E);");
            }
            if(!$continue) exit(1);
        }

        function __error2db()
        {
            $E = &$this -> E;

            $errno = mysql_errno();
            if($errno >= 2000 and $errno <= 2055) return; // MySQL client error, e.g. connect failed, server has gone away...

            $ErroLog = @DAO::createDAO($this->interfaces, $this->tabledefine, true);

            /* @var $ErroLog MySQL_DAO */

            $databaseName = DAO::extractDatabase($this -> tabledefine);
            //$interfaceName = DAO::extractInterface($this -> tabledefine);
            //echo $interfaceName;
            //$Db = &$this -> interfaces[$interfaceName];
            /* @var $ MySQL_Interface */

            $bResult = ($ErroLog -> db -> isConnected($databaseName));

            if($bResult) {
                $Input = new Input(0);
                $Input -> setData($E -> getData());
                $Input -> setVar('backtrace', substr(print_r($Input -> getVar('backtrace'), true), 0, $E -> maxBacktraceSize));
                $Input = $Input -> filter($ErroLog -> getFieldlist());

                $type = $ErroLog -> getFieldType('timestamp');

                # TODO auf Datenbank unterscheiden MySQL,PostgreSQL, etc.
                if($type == 'timestamp') $Input -> setVar('timestamp', $E -> getDateTime('%Y-%m-%d %H:%M:%S'));

                $data = $Input -> getData();

                $Result = $ErroLog -> insert($data);
                $last_insert_id = $Result->getValue('last_insert_id');
                $E -> __setLastInsertId($last_insert_id);
            }
        }

        function add(&$E)
        {
            $this -> ExceptionList[] = &$E;
            /* @var $E Xception */
            $E -> magicInfo = array_merge($E -> magicInfo, $this -> additionalMagicInfo);
            return true;
        }

        function remove(&$E)
        {
            $count=count($this->ExceptionList);
            for($i=($count); $i--; $i>=0) {
                $E2 = &$this->ExceptionList[$i];
                // strict comparison will simple check whether the two objects are at the same location in memory
                // and so doesn't even look at the values of the properties (which can cause "nesting level too deep")
                if($E2 === $E) {
                    unset($this->ExceptionList[$i]);
                    return true;
                }
            }
            return false;
        }

        function handle(&$E)
        {
            $this -> E = & $E;

            //
            // Fehler-Modus (siehe POOL_ERROR_* Konstanten)
            //
            $mode = $this -> mode;

            if($E -> getMode() != null) {
                $mode = $E -> getMode();
            }


            // Achtung! Die Reihenfolge für POOL_ERROR_CALLBACK und POOL_ERROR_DIE darf nicht geändert werden.
            // Achtung! POOL_ERROR_MAIL und POOL_ERROR_DB müssen hintereinander gesetzt bleiben, z.B. zum Verhindern von Spam Error Mails
            if($mode & POOL_ERROR_CALLBACK) {
                $this -> __error2callback();
            }

            if($mode & POOL_ERROR_DISPLAY) {
                $this -> __error2display();
            }

            if($mode & POOL_ERROR_LOGFILE) {
                $this -> __error2logfile();
            }

            if($mode & POOL_ERROR_MAIL) {
                $this -> __error2mailaddress();
            }

            if($mode & POOL_ERROR_DB) {
                $this -> __error2db();
            }

            if($mode & POOL_ERROR_DIE) {
                if(~$mode & POOL_ERROR_DISPLAY)	$this -> __error2display();
                $dieMsg = ' PHP process terminated ';
                if($this -> __isDisplayHTML())	$dieMsg = '<b>&lt;&lt; PHP Proccess terminated &gt;&gt;</b>';
                die($dieMsg);
            }

            $this->remove($E);
        }

        function setLogfile($logFile)
        {
            $this -> logFile = $logFile;
        }

        function setLogtext($logText)
        {
            $this -> logText = $logText;
        }

        /**
         * Setzt die Empfänger E-Mail Adresse
         *
         * @param array|string $mailAddress
         */
        function setMailAddress($mailAddress)
        {
            if(!is_array($mailAddress)) $mailAddress=array($mailAddress);
            $this->mailAddress=$mailAddress;
        }

        /**
         * Liefert alle Empfänger E-Mail Adressen
         *
         * @return array
         */
        function getMailAddress()
        {
            return $this->mailAddress;
        }

        /**
         * Fügt Empfänger E-Mail Adressen hinzu
         *
         * @param array|string $mailAddress
         */
        function addMailAddress($mailAddress)
        {
            if(!is_array($mailAddress)) $mailAddress=array($mailAddress);
            $this->mailAddress=array_merge($this->mailAddress, $mailAddress);
            $this->mailAddress=array_unique($this->mailAddress);
        }

        /**
         * Enternt übergebene Empfänger E-Mail Adressen
         *
         * @param array|string $mailAddress
         */
        function removeMailAddress($mailAddress)
        {
            if(!is_array($mailAddress)) $mailAddress=array($mailAddress);
            $this->mailAddress=array_diff($this->mailAddress, $mailAddress);
        }

        function setFrom($from, $mailFrom='')
        {
            if(!empty($from)) $this->from=$from;
            if(!empty($mailFrom)) $this->setMailFrom($mailFrom);
        }

        function setMailFrom($mailFrom)
        {
            $this->mailFrom = $mailFrom;
        }

        function setMailSubject($subject)
        {
            $this->mailSubject = $subject;
        }

        /**
         * Setzt eine Callback-Funktion, die im Fehlerfall aufgerufen wird (nur wenn bei POOL_ERROR_CALLBACK)
         *
         * @param string|array $callback Callback-Funktion
         */
        function setCallback($callback)
        {
            $this -> callback = $callback;
        }

        /**
         * Setzt das Darstellungsformat (mögliche Werte TEXT, HTML) für E-Mail und Bildschirmausgabe (POOL_ERROR_MAIL, POOL_ERROR_DISPLAY).
         *
         * @param string $displayFormat HTML|TEXT
         */
        function setMailFormat($mailFormat)
        {
            $this -> mailFormat = $mailFormat;
        }

        /**
         * Setzt das Darstellungsformat (mögliche Werte TEXT, HTML) für E-Mail und Bildschirmausgabe (POOL_ERROR_MAIL, POOL_ERROR_DISPLAY).
         *
         * @param string $displayFormat HTML|TEXT
         */
        function setDisplayFormat($displayFormat)
        {
            $this -> displayFormat = $displayFormat;
        }

        /**
         * Setzt HTML-Vorlage für Fehlermeldungen (beeinflusst POOL_ERROR_MAIL und POOL_ERROR_DISPLAY)
         *
         * @param string $html HTML (kann Platzhalter enthalten, z.B. {FILE}, {CLASS}, {DATETIME}, {TIMESTAMP}, etc.
         */
        function setDisplayHTML($html)
        {
            $this -> displayHTML = $html;
        }

        function setMailHTML($html)
        {
            $this -> displayHTML = $html;
        }

        /**
         * Setzt Textvorlage für Fehlermeldungen (beeinflusst POOL_ERROR_MAIL und POOL_ERROR_DISPLAY)
         *
         * @param string $text Text (kann Platzhalter enthalten, z.B. {FILE}, {LINE}, {CODE}, etc.
         */
        function setDisplayText($text)
        {
            $this -> displayText = $text;
        }

        function setMailText($text)
        {
            $this -> displayText = $text;
        }

        /**
         * Setzt Tabellendefinition, notwendig für POOL_ERROR_DB
         *
         * @param DataInterface $interfaces Datenschnittstelle z.B. MySQL
         * @param string $tabledefine Tabellendefinitionsname
         */
        function setTabledefine(&$interfaces, $tabledefine)
        {
            $this -> interfaces = &$interfaces;
            $this -> tabledefine = $tabledefine;
        }

        /**
         * Füge zusätzliche magische Informationen zu allen Exceptions hinzu
         *
         * @param array $magicInfo
         * @return bool
         */
        function addMagicInfo($magicInfo)
        {
            if(is_array($magicInfo)) {
                $this -> additionalMagicInfo = $magicInfo;
                return true;
            }
            else {
                die('ExceptionHandler::addMagicInfo bitte ein Array übergeben!');
            }
            return false;
        }
    }


    /**
     * Ermittelt ob noch nicht behandelte Exceptions im Stack liegen
     *
     * @return boolean
     */
    function isExceptionInStack()
    {
        if(global_exists('DEFAULT_EXCEPTION_HANDLER')) {
            $ExceptionHandler = &getGlobal('DEFAULT_EXCEPTION_HANDLER');
            return (sizeof($ExceptionHandler -> ExceptionList) > 0);
        }
        if(global_exists('EXCEPTION_INSTANCE_STACK')) {
            $EXCEPTION_INSTANCES = &getGlobal('EXCEPTION_INSTANCE_STACK');
            return (sizeof($EXCEPTION_INSTANCES) > 0);
        }
        return false;
    }

    /**
     * Nicht behandelte Exceptions auslösen.
     *
     */
    function raise()
    {
        $ExceptionList = array();

        if(global_exists('DEFAULT_EXCEPTION_HANDLER')) {
            $ExceptionHandler = &getGlobal('DEFAULT_EXCEPTION_HANDLER');
        }

        if(is_a($ExceptionHandler, 'ExceptionHandler')) {
            $ExceptionList = &$ExceptionHandler -> ExceptionList;
        }
        else {
            if(global_exists('EXCEPTION_INSTANCE_STACK')) {
                $ExceptionList = &getGlobal('EXCEPTION_INSTANCE_STACK');
            }
        }

        $count = sizeof($ExceptionList);
        for($i=$count; $i--; $i>=0) {
            $ExceptionList[$i] -> raiseError();
            unset($ExceptionList[$i]);
        }
    }


    function pool_error_handler($errno, $errstr, $errfile, $errline, $vars)
    {

        //echo error_reporting() . ' vs ' . $errno;
//			if(error_reporting() & $errno) {
            #echo error_reporting() . ' vs ' . $errno.' '.bool2string(error_reporting() & $errno).'<br>';

            // Stack verhindert, dass in Endlosschleifen tausende von Fehlermeldungen generiert werden
            static $stack;
            if(!is_array($stack)) $stack = array();

            // definiere ein assoziatives Array mit Fehler String
            // in der Realität sind die einzigen zu bedenkenden
            // Einträge 2,8,256,512 und 1024
            $errortype = array (
                            1	=> 'Error',				# kann nicht behandelt werden
                            2	=> 'Warning',
                            4	=> 'Parsing Error',		# kann nicht behandelt werden
                            8	=> 'Notice',
                            16	=> 'Core Error',		# kann nicht behandelt werden
                            32	=> 'Core Warning',		# kann nicht behandelt werden
                            64	=> 'Compile Error',		# kann nicht behandelt werden
                            128	=> 'Compile Warning',	# kann nicht behandelt werden
                            256	=> 'User Error',
                            512	=> 'User Warning',
                            1024=> 'User Notice',
                            2047=> 'All',
                            2048=> 'Strict'
            );

            /*
            Bitwert Konstante
                1 E_ERROR
                2 E_WARNING
                4 E_PARSE
                8 E_NOTICE
                16 E_CORE_ERROR
                32 E_CORE_WARNING
                64 E_COMPILE_ERROR
                128 E_COMPILE_WARNING
                256 E_USER_ERROR
                512 E_USER_WARNING
                1024 E_USER_NOTICE
                2047 E_ALL
                2048 E_STRICT
            */

            // Gruppe von Fehlern, die zur Nachverfolgung gespeichert werden
            #$user_errors = array(E_USER_ERROR, E_USER_WARNING, E_USER_NOTICE, E_WARNING);
            # if(in_array($errno, $user_errors)) ...

            $error = array('errstr' => $errstr, 'errno' => $errno);
            $errorFound = in_array($error, $stack);

            if(!$errorFound) {
                array_push($stack, $error);

            ### hier stellt sich die Frage: sollen Exceptions bei @ komplett unterdrückt werden oder nur nicht ausgegeben werden?
                if(error_reporting() != 0) {
                    $Xception = new Xception($errstr, $errno, magicInfo($errfile, $errline, '', ''), null);
                    array_shift($Xception -> backtrace);
                    $Xception -> raiseError();
                }
            }
//			}
        return 	true;

        //
        //return true;
    }
}
/*
$Xception = new Xception();
$Xception -> raiseError();
*/