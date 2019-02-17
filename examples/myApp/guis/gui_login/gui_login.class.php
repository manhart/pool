<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * @since 2019/02/17
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * Class GUI_Login
 *
 * @package auftragserfassung
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @access public
 **/
class GUI_Login extends GUI_Module
{
    /**
     * Skript-/Programmdaten
     *
     * @var array
     */
    var $data = null;

    /**
     * Datenbank Session
     *
     * @var DBSession
     */
    var $DBSession = null;

    /**
     * Session Schluessel fuer dieses GUI
     *
     * @var string
     */
    var $guiSessionKey;

    /**
     * ID der Software
     *
     * @var int
     */
    var $progID = 0;

    /**
     * Rechte
     *
     * @var Rechte fuer dieses Modul
     */
    var $rechte = array(
    );

    /**
     * Class GUI_Login
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function GUI_Login(& $Owner)
    {
        parent::GUI_Module($Owner);
        $Frame = $this->Weblication->getMain(); /* @var $Frame GUI_Frame */
        if($Frame  instanceof GUI_CustomFrame) {
            $Frame->readoutErrorList($this->getClassName());
        }
    }

    /**
     * Default Werte setzen. Input initialisieren.
     *
     * @access public
     * @param int $superglobals
     **/
    function init($superglobals=0)
    {
        $this->Defaults->addVar('username', '');
        $this->Defaults->addVar('password', '');
        parent::init(I_REQUEST);

        // Programmdaten aus der Session laden
        $this->restoreSession();
    }

    /**
     * Templates laden
     *
     * @access public
     **/
    function loadFiles()
    {
        $template = $this->Weblication->findTemplate('tpl_login.html', $this->getClassName());
        $this->Template->setFilePath('stdout', $template);

        if(is_a($this->Weblication->getMain(), 'GUI_CustomFrame')) {
            $Frame = &$this->Weblication->getMain();

            $this->Template->setVar('GUI_LOGIN', $className=$this->getClassName());
            $Frame->addBodyLoad('GUI_LOGIN=\''.$className.'\'');

            $loginJS = $this->Weblication->findJavaScript('login.js', strtolower($className), false);
            $Frame->Headerdata->addJavaScript($loginJS);
        }
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $loggedin = $this->Session->getVar('logged-in');
        // $username = $this->Input->getVar('username');
        if($loggedin) {
            $this->Template->setVar('LOGGEDIN', true);
        }
        else {
            $this->Template->setVar('LOGGEDIN', bool2string(false));
        }

        if($this->Weblication->hasFrame()) {
            $Frame = &$this->Weblication->getMain(); /* @var $Frame GUI_CustomFrame */
            $this->prepareDefaults($Frame);
        }
    }

    function login()
    {
        $success = false;
        $username = $this->Input->getVar('username');
        $password = $this->Input->getVar('password');

        $filter = array(
            array('username', 'equal', $username),
            array('password', 'equal', 'password("'.$password.'")', DAO_NO_QUOTES|DAO_NO_ESCAPE)
        );

        $UserDAO = DAO::createDAO($this->Weblication->getInterfaces(), 'Testing_User', false);
        $CountSet = $UserDAO->getCount(null, null, $filter);

        if(intval($CountSet->getValue('count')) == 1) {
            $success = true;
            $this->Session->setVar('logged-in', time());
            $this->Session->setVar('username', $username);
        }
        return array('success' => $success, 'username' => $username);
    }

    function logout()
    {
        $this->Session->delVar('logged-in');
        $this->Session->delVar('username');
    }

    /**
     * Standard Variablen für das Template vorbereiten
     *
     * @param $Frame GUI_CustomFrame
     */
    function prepareDefaults(&$Frame)
    {

    }

    /**
     * Die Funktion stellt die Session wieder her
     *
     */
    function restoreSession()
    {
        $this->data = array();
    }

    /**
     * schreibt Daten in die Session
     */
    function writeSession()
    {
    }

    /**
     * Inhalt parsen und zurueck geben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $this->Template->parse('stdout');
        return $this->Template->getContent('stdout');
    }
}