<?php
/**
* 		-==|| Rapid Module Library (RML) ||==-
*
* SessionHandler.class.php
*
* Session Verwaltung. Ueberschreibt den PHP Session Handler und setzt auf eine Datenbankschnittstelle (z.B. MySQL).
*
* MySQL Tabelle - SQL Statement:
*
* CREATE TABLE tbl_Session (
* sid char(32) NOT NULL,
* expire int(11) NOT NULL default '0',
* data text NOT NULL,
* ip char(15) NOT NULL,
* browser char(96) NOT NULL,
* PRIMARY KEY  (sid),
* INDEX (expire),
* INDEX (sid,ip,browser)
* ) TYPE=MyISAM;
*
* @version $Id: SessionHandler.class.php,v 1.2 2007/05/31 14:39:46 manhart Exp $
* @version $Revision 1.0$
* @version
*
* @since 2003-09-30
* @author Alexander Manhart <alexander@manhart.bayern>
* @link https://alexander-manhart.de
*/

if(!defined('CLASS_SESSIONHANDLER')) {

    define('CLASS_SESSIONHANDLER', 1); 	// Prevent multiple loading

    /**
     * SessionHandler
     *
     * Ueberschreibt den PHP Session Handler (siehe PHP Manual session_set_save_handler).
     * Die Klasse speichert die Daten als Byte-Stream in einer Datenbank ab.
     *
     * @package rml
     * @access public
     * @author Alexander Manhart <alexander.manhart@freenet.de>
     * @version $Id: SessionHandler.class.php,v 1.2 2007/05/31 14:39:46 manhart Exp $
     **/
    class SessionHandler extends Object
    {
        //@var object Datenbankhandle
        //@access private
        var $db = null;

        //@var object Data Access Object (Tabelle Session)
        //@access private
        var $dao = null;

        //@var string Datenbankname
        //@access private
        var $dbname = '';

        //@var array Tabellendefinition
        //@access private
        var $tabledefine = null;

        //@var string Session Name
        //@access private
        var $sessionName = 'sid';

        //@var object Input
        //@access private
        var $Input = null;

        /**
         * Session::Session()
         *
         * Konstruktor
         *
         * @access public
         * @param array $interfaces
         * @param array $tabledefine
         **/
        function SessionHandler(& $interfaces, $tabledefine)
        {
            # extract Tabellendefinition:
            $this -> tabledefine = $tabledefine;
            global $$tabledefine;
            $tabledefine = $$tabledefine;
            $type = strtolower($tabledefine[0]);

            if (empty($type)) {
                trigger_error(sprintf('Unknown type of table definition \'%s\'! (@SessionHandler)', $this -> tabledefine));
                return;
            }
            $this -> dbname = $tabledefine[1];
            $this -> db = & $interfaces[$type];	// must be a database layer

            # ini setzen
            $this -> set_ini();

            # php session handler ueberschreiben
            session_set_save_handler(
                array(& $this, '__open_method'),
                array(& $this, '__close_method'),
                array(& $this, '__read_method'),
                array(& $this, '__write_method'),
                array(& $this, '__destroy_method'),
                array(& $this, '__gc_method')
            );

            # input object
            $this -> Input = & new Input(I_REQUEST|I_SERVER);

            if ($this -> db -> connect($this -> dbname)) {
                $this -> dao = & DAO::createDAO($this -> db, $this -> tabledefine, false);

                $this -> ip = substr($this->db->escapestring($this->Input->getVar('REMOTE_ADDR')), 0, 15);
                $this -> browser = substr($this->db->escapestring($this -> Input -> getVar('HTTP_USER_AGENT')), 0, 96);

                $sid = $this -> Input -> getVar($this -> sessionName);

                if ($this -> dao -> exists($sid)) {
                    if (!$this -> dao -> exists($sid, $this -> ip, $this -> browser)) {
                        $sid = $this -> new_sid();
                        session_id($sid);
                    }
                }
                //session_cache_limiter('private');
                //session_cache_expire(90);

                session_start();
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, sprintf('Session start failed, because Database (%s) is not connected (@SessionHandler).', $this -> dbname));
            }
        }

        /**
         * SessionHandler::set_ini()
         *
         * Setzt PHP.ini Einstellungen.
         *
         * @access private
         **/
        function set_ini()
        {
            ini_set('session.save_handler', 'user');
            ini_set('session.name', $this -> sessionName);
            ini_set('session.use_trans_sid', 0);
            ini_set('session.use_cookies', 1);
            //ini_set( 'session.gc_maxlifetime', );
            //ini_set( 'session.gc_probability', );
            ini_set('session.use_only_cookies', 0);
        }

        /**
         * SessionHandler::new_sid()
         *
         * Erzeugt eine neue eindeutige Session ID (kann auch statisch verwendet werden).
         *
         * @method static
         * @access public
         * @return string Session ID
         **/
        function new_sid()
        {
            mt_srand((double)microtime() * 1000000);
            return md5(time() . $this -> Input -> getVar('REMOTE_ADDR') . $this -> Input -> getVar('HTTP_USER_AGENT') . mt_rand(100000, 999999));
        }

        /**
         * Session::__open_method()
         *
         * Diese Funktion ist verantwortlich fuer das Oeffnen der Verbindung zur Datenbank.
         *
         * @access private
         * @param string $save_path
         * @param string $session_name
         * @return boolean Erfolgsstatus
         **/
        function __open_method($save_path, $session_name)
        {
            return ((bool) true);
        }

        /**
         * Session::__close_method()
         *
         * Diese Funktion wird beim Beenden der Session ausgefuehrt.
         * Normal wird sie zum Freigeben von Speicher und Freigeben von Variablen benutzt, aber wir benutzen eine Datenbankanbindung
         * und haben daher keine Resourcen zum saeubern.
         *
         * @access private
         * @return true bei Erfolg, false bei einem Fehler.
         **/
        function __close_method()
        {
            return ((bool) true);
        }

        /**
         * Session::__read_method()
         *
         * Liest die Daten anhand der Session Id $sid ein und gibt einen serialisierten String zurueck
         * Wenn keine Session ID da ist, wir ein leerer String zurueck gegeben.
         * Wenn ein Fehler auftritt, wird false zurueck gegeben.
         *
         * @param string $sid Session ID (binary)
         * @return string Serialized Daten
         **/
        function __read_method($sid)
        {
            return $this -> dao -> getData($sid);

/*			$data_new = '';
            if ($resultset -> count() == 1) {
                $data_new = $resultset -> getValue('data');
            }

            return $data_new;
            */
        }

        /**
         * Session::__write_method()
         *
         * Schreibt byte-stream (serialized) Session Daten in die Datenbank. Wenn die Session bereits existiert, wird sie aktualisiert.
         *
         * Apache users: note that this function is not called until after the
         * output stream has been closed. DO NOT print anything in this
         * function, as not only will you not see it, but you may prevent
         * the session data being updated. You have been warned.
         *
         * @param string $sid Session ID
         * @param string $data Zu speicherndes Datenformat (byte-stream)
         * @return boolean true bei Erfolg, false im Fehlerfall
         **/
        function __write_method($sid, $data)
        {
            $expire = time() + get_cfg_var('session.gc_maxlifetime');

            if (!$this -> dao -> exists($sid, $this -> ip, $this -> browser)) {
                // insert
                $result = & $this -> dao -> newSession($sid, $expire, $data, $this -> ip, $this -> browser);
            }
            else {
                // update
                $result = & $this -> dao -> updSession($sid, $expire, $data);
            }
            return (bool)($result -> getLastError() == false);
        }

        /**
         * Zerstoert alle verbundenen Daten mit der Session. Wird von session_destroy() aufgerufen.
         *
         * @param string $sid
         **/
        function __destroy_method($sid)
        {
            $this -> dao -> delete($sid);
            if (isset($_COOKIE[$this -> sessionName])) {
                unset($_COOKIE[$this -> sessionName]);
            }
        }

        /**
         * Session::__gc_method()
         *
         * Wird beim Session Startup mit einer Wahrscheinlichkeit, die in der ini in gc_probability spezifiert wurde, aufgerufen.
         * Diese Funktion entfernt Sessions, die nicht mehr aktualisiert wurden, und deren gc_maxlifetime ueberschritten wurde.
         *
         * @return boolean true bei Erfolg, false beim Fehler
         **/
        function __gc_method()
        {
            return $this -> dao -> cleanUp();
        }
    }
}