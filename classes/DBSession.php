<?php
/**
 * -= PHP Object Oriented Library (POOL) =-
 * DBSession.class.php
 * Sessionverwaltung laeuft ueber eine Datenbankschnittstelle (z.B. MySQL).
 * Wichtig fuer den Einsatz: DBSession benutzt das Data Access Object Modell!
 * Session Daten werden in der Tabelle "Session" abgelegt, Tabellenstruktur:
 * CREATE TABLE `tbl_Session` (
 *   `sid` varchar(32) NOT NULL default '',
 *   `expire` int(11) NOT NULL default '0',
 *   `data` text NOT NULL,
 *   `ip` varchar(15) NOT NULL default '',
 *   `browser` varchar(96) NOT NULL default '',
 *   PRIMARY KEY  (`sid`),
 *   KEY `expire` (`expire`),
 *   KEY `sid` (`sid`,`ip`,`browser`)
 * ) TYPE=MyISAM;
 * @date $Date: 2007/05/16 15:17:59 $
 *
 * @version $Id: DBSession.class.php,v 1.6 2007/05/16 15:17:59 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * @since 2004-02-02
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @link http://www.misterelsa.de
 */

use pool\classes\Core\Input\Input;
use pool\classes\Core\PoolObject;
use pool\classes\Database\DAO;


/**
 * DBSession
 * Database session management (look at file description for more information)
 *
 * @package pool
 * @author manhart
 * @version $Id: DBSession.class.php,v 1.6 2007/05/16 15:17:59 manhart Exp $
 * @access public
 **/
class DBSession extends PoolObject
{
    /**
     * Session ID
     *
     * @var string
     */
    var $sid;

    /**
     * Aktuelle Zeit + session.gc_maxlifetime (php.ini Variable) -> Session Timeout
     *
     * @var int
     */
    var $expire;

    /**
     * Session Data Access Object
     *
     * @var tbl_Session
     */
    var $DAO_Session = null;

    /**
     * Data Container Input
     *
     * @var Input
     */
    var $Input = null;

    /**
     * IP Adresse des Benutzers (im Normalfall)
     *
     * @var string
     */
    var $RemoteAddr = '';

    /**
     * Client des Benutzers (Browserinformationen)
     *
     * @var string
     */
    var $HttpUserAgent = '';

    /**
     * @var bool Beschraenkung der DBSession auf IP/Browser (solange nicht die Gefahr besteht, dass man die URL z.B. in Foren kopiert, kann man dieses Feature ausschalten)
     */
    var $ipUserAgentRestriction = true;

    /**
     * Constructor
     *
     * @param mixed $interfaces Array (Interfaces) oder Object z.B. MySQL_db
     * @param string $tabledefine Tabellendefinition
     * @param string $sid Session ID
     * @param boolean $ipUserAgentRestriction Beschraenkung der DBSession auf IP/Browser (solange nicht die Gefahr besteht, dass man die URL z.B. in Foren kopiert, kann man dieses
     *     Feature ausschalten)
     **/
    function __construct($tabledefine, $sid = '', $ipUserAgentRestriction = true)
    {
        $this->ipUserAgentRestriction = $ipUserAgentRestriction;

        $this->DAO_Session = DAO::createDAO($tabledefine);

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Proxy Weiterleitung
            $client_ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $this->RemoteAddr = $client_ip[0];
        } else {
            $this->RemoteAddr = $_SERVER['REMOTE_ADDR'];
        }
        $this->HttpUserAgent = $_SERVER['HTTP_USER_AGENT'];

        $this->expire = $this->getCurrentTime() + $this->getMaxLifetime();
        $this->cleanUp();

        if ($sid == '' or !$this->sid_exists($sid)) {
            $this->sid = $this->generateSID();
            $this->DAO_Session->newSession(
                $this->sid,
                $this->expire,
                '',
                $this->RemoteAddr,
                $this->HttpUserAgent,
            );
        } else {
            $this->sid = $sid;
            $this->DAO_Session->updExpire($this->sid, $this->expire);
        }

        //if ($_GET['sid'] == '') {
        //    $_GET['sid'] = $this->sid;	// to find for MSiteUrl; not very clean
        //}
        //setcookie("sid", $this->sid, time() + $this->lifetime, '/');

        $this->initialize();
    }

    /**
     * DBSession::generateSID()
     * Generiert eine eindeutige Session ID (MD5 hash).
     *
     * @access public
     * @return string Session ID
     **/
    function generateSID()
    {
        mt_srand((double)microtime() * 1000000);
        return md5(time().$this->RemoteAddr.$this->HttpUserAgent.mt_rand(100000, 999999));
    }

    /**
     * Liest die Session Daten ein und cached sie im Input Object.
     *
     * @access public
     * @return boolean Erfolgsstatus
     **/
    function initialize()
    {
        $this->Input = new Input(Input::EMPTY);

        $data = $this->DAO_Session->getData($this->sid);
        if ($data === false) {
            $bResult = $this->raiseError(
                __FILE__,
                __LINE__,
                'Foul! DBSession data has the value "false". '.
                'Please inform the author.',
            );
        } else {
            $bResult = $this->Input->setByteStream($data);
        }

        return $bResult;
    }

    /**
     * Speichert die gecachten Daten weg.
     *
     * @access public
     **/
    function save()
    {
        $data = $this->Input->getByteStream();
        //echo 'sid: ' .$this -> sid. ' data:'.$data;

        $Resultset_Session = $this->DAO_Session->updSession($this->sid, $this->expire, $data);
        return $Resultset_Session->getValue('affected_rows');
    }

    /**
     * Loescht alte Session.
     *
     * @access public
     * @return boolean Erfolgsstatus
     **/
    function cleanUp()
    {
        return $this->DAO_Session->cleanUp();
    }

    /**
     * DBSession::sid_exists()
     * Prueft, ob eine Session existiert
     *
     * @access public
     * @param string $sid Session ID
     * @return boolean Status, ob die Session existiert
     **/
    function sid_exists($sid)
    {
        $remoteAddr = null;
        $httpUserAgent = null;
        if ($this->ipUserAgentRestriction) {
            $remoteAddr = $this->RemoteAddr;
            $httpUserAgent = $this->HttpUserAgent;
        }
        return $this->DAO_Session->exists($sid, $remoteAddr, $httpUserAgent);
    }

    /**
     * Gibt die aktuelle Session ID zurueck
     *
     * @access public
     * @return string Session ID
     **/
    function getSID()
    {
        return $this->sid;
    }

    /**
     * Setzt einen Wert und schreibt die Daten weg.
     *
     * @access public
     * @param string $key Schluessel
     * @param string $value Wert
     **/
    function setVar($key, $value = '')
    {
        $this->Input->setVar($key, $value);
        $this->save();
    }

    /**
     * Liefert einen Wert.
     *
     * @param string $key Schluessel
     * @return string Wert
     **/
    function getVar($key)
    {
        return $this->Input->getVar($key);
    }

    /**
     * Prueft, ob eine Variable ueberhaupt gesetzt wurde.
     *
     * @param string $key Name der Variable
     * @return boolean True=ja; False=nein
     **/
    function exists($key)
    {
        return $this->Input->exists($key);
    }

    /**
     * Loescht eine Variable aus dem internen Container.
     *
     * @access public
     * @param string $key Schluessel (bzw. Name der Variable)
     */
    function delVar($key)
    {
        $this->Input->delVar($key);
        $this->save();
    }

    /**
     * DBSession::setFielddata()
     * Setzt spezielle Felder, die vom Standard SQL Statement abweichen (z.B. username, service, callback)
     *
     * @param array $fielddata
     * @return integer Anzahl betroffener Datensaetze (0, 1)
     **/
    function setFielddata($fielddata = [])
    {
        $Resultset_Session = &$this->DAO_Session->updFielddata($this->sid, $this->expire, $fielddata);
        return $Resultset_Session->getValue('affected_rows');
    }

    /**
     * Leert die Session (alle Session Daten gehen dabei verloren).
     *
     * @access public
     **/
    function clear()
    {
        $this->Input->clear();
        $this->save();
    }

    /**
     * Gibt die maximale Lebenszeit der Session zur�ck
     *
     * @return int Maximale Lebenszeit in Sekunden
     */
    function getMaxLifetime()
    {
        return get_cfg_var('session.gc_maxlifetime');
    }

    /**
     * Liefert die aktuelle Uhrzeit als Unix Zeitstempel
     *
     * @return int
     */
    function getCurrentTime()
    {
        $Set = $this->DAO_Session->getCurrentTime();
        return (int)$Set->getValue('ts');
    }
    /*		function lockSession ()
        {
            $sql = "UPDATE Session SET locked='1' WHERE sid='".$this->sid."'";
        }

        function setData ($data)
        {
            $this->cleanUp();
            $this->data = $data;
            $this->updateSession();
        }

        function getData ()
        {
            $data = $this -> DAO_Session -> getData($this -> sid);

            $Input = new Input(INPUT_EMPTY);
            $Input -> setData($data);

            $this->cleanUp();
            $sql = "SELECT data FROM Session WHERE sid='".$this->sid."'";
            $result = $this->db->sql_query($sql, $this->dbname);
            if ($this->db->sql_numrows($result) > 0) {
                $row = $this->db->sql_fetchrow($result);
            }

            $data = new MInput(0);
            $data->putData($row["data"],0);

            return $data;
        }
    */
}