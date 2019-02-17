<?php
/**
 * 		-==|| Rapid Module Library (RML) ||==-
 *
 * Weblication.class.php
 *
 * Die Hauptklasse aller Webanwendungen. Jedes neue Projekt beginnt mit Weblication und wird davon instanziert.
 *
 * @version $Id: Weblication.class.php,v 1.16 2007/05/31 14:36:23 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

if(!defined('CLASS_WEBLICATION')) {

    define('CLASS_WEBLICATION', 1); 	// Prevent multiple loading

     /**
      * Weblication
      *
      * Klasse fuer eine Webanwendung.
      *
      * @package rml
      * @author Alexander Manhart <alexander.manhart@freenet.de>
      * @version $Id: Weblication.class.php,v 1.16 2007/05/31 14:36:23 manhart Exp $
      * @access public
      **/
     class Weblication extends Component
     {
        /**
         * Name des Projekts (wird im Konstruktor gesetzt)
         *
         * @var string $Project
         * @access private
         */
        var $Project = 'unknown';

        /**
         * Titel der Weblication
         *
         * @var string
         */
        var $Title = '';

        /**
         * Enthaelt das erste geladene GUI_Module (wird in Weblication::run() eingeleitet)
         *
         * @var GUI_Frame $Frame
         * @access private
         */
        var $Frame = null;

        /**
         * Enthaelt das erste geladene GUI_Module (wird in Weblication::run() eingeleitet)
         *
         * @var GUI_Module $Main
         * @access private
         */
        var $Main = null;

        /**
         * PHP Session gekapselt in ISession
         *
         * @var ISession $Session
         * @access public
         */
        var $Session = null;

        /**
         * Benutzer Klasse (nicht realisiert)
         *
         * @var User
         * @access private
         */
        var $User = null;

        /**
         * Root Verzeichnis der Hauptbibliothek
         *
         * @var string
         * @access private
         */
        var $PathBaselib = '';

        /**
         * Relativer Pfad zur Hauptbibliothek
         *
         * @var string
         * @access private
         */
        var $RelativePathBaselib = '';

        /**
         * Skin / Theme (Designvorlage bzw. Bilderordner)
         *
         * @var string
         */
        var $Skin = 'default';

        /**
         * Sprache der Seite
         *
         * @var string
         * @access private
         */
        var $Language = 'de';

        /**
         * Schema / Layout (index ist das Standard-Schema)
         *
         * @var string
         * @access private
         */
        var $Schema = 'index';

        /**
         * Bewahrt alle Schnittstellen Instanzen der unterschiedlichsten Speichermedien als Liste auf
         *
         * @var array
         * @access private
         */
        var $Interfaces = Array();

        /**
         * Zeichensatz
         *
         * @var string
         */
        var $Charset = '';

        /**
         * Programm ID
         *
         * @var int
         */
        var $progId = null;

         /**
          * @var string
          */
        private $cssFolder = 'css';

        /**
         * Flag, ob Session initiiert wurde.
         *
         * @var boolean
         * @access private
         */
        // var $session_started = false;

        /**
         * Der Konstruktor erwartet den Projektnamen, den absoluten und relativen Pfad zur Baselib.
         *
         * @access public
         * @param string $Project Name des Projekts.
         * @param string $PathBaselib absoluter Pfad zur Baselib
         * @param string $RelativePathBaselib relativer Pfad zur Baselib
         **/
        function Weblication($Project='', $PathBaselib = '', $RelativePathBaselib = '')
        {
            if($Project != '') {
                $this->Project = $Project;
            }
            $this->PathBaselib = $PathBaselib;
            $this->RelativePathBaselib = $RelativePathBaselib;

            parent::Component(new Nil());
        }

        /**
         * Aendert den Ordner fuer die Designvorlagen (Html Templates) und Bilder.
         *
         * @access public
         * @param string $skin Ordner fuer die Designvorlagen (Html Templates) und Bilder. (Standardwert: default)
         **/
        function setSkin($skin = 'default')
        {
            $this->Skin = $skin;
        }

        /**
         * Liefert den Ordner (Namen) der aktuellen Designvorlagen und Bilder zurueck.
         * (wird derzeit fuer die Bilder und Html Templates missbraucht)
         *
         * @access public
         * @return string Name des Designs (Skin)
         **/
        function getSkin()
        {
            return $this->Skin;
        }

        /**
         * Aendert die Sprache der Seite.
         * (wird derzeit fuer die Bilder und Html Templates missbraucht)
         *
         * @access public
         * @param string $lang Sprache (siehe Laenderkuerzel im WWW)
         **/
        function setLanguage($lang = 'de')
        {
            $this->Language = $lang;
        }

        /**
         * Liefert die Sprache der Seite.
         *
         * @access public
         * @return string Sprache der Webseite
         **/
        function getLanguage()
        {
            return $this->Language;
        }

        /**
         * Liefert den Zeichensatz der Webanwendung zurueck
         *
         * @return string
         */
        function getCharset()
        {
            return $this->Charset;
        }

        /**
         * Setzt den Zeichensatz fuer die Webanwendung
         *
         * @param string $charset
         */
        function setCharset($charset)
        {
            header('content-type: text/html; charset='.$charset);
            $this->Charset = strtoupper($charset);
        }

        /**
         * Setzt die Programm ID
         *
         * @param int $progId
         */
        function setProgId($progId)
        {
            $this->progId = $progId;
        }

        /**
         * Liefert die Programm ID
         *
         * @return int|null
         */
        function getProgId()
        {
            return $this->progId;
        }

        /**
         * Setzt das Standard Schema, welches geladen wird, wenn kein Schema uebergeben wurde.
         *
         * @access public
         * @param string $default Standard Schema
         **/
        function setSchema($default = 'index')
        {
            $this->Schema = $default;
        }

        /**
         * Gibt das voreingestellte Standard Schema zurueck (meist ist definiert das Schema index die Startseite).
         *
         * @access public
         * @return string Standard Schema
         **/
        function getSchema()
        {
            return $this->Schema;
        }

        /**
         * Setzt das Haupt-GUI.
         *
         * @param GUI_Module $GUI_Module
         */
        function setMain(&$GUI_Module)
        {
            $this -> Main = &$GUI_Module;
        }

        /**
         * Liefert das Haupt-GUI (meistens erstes GUI, das im Startscript �bergeben wurde).
         *
         * @return GUI_Module
         */
        function &getMain()
        {
            return $this -> Main;
        }

        /**
         * Gibt Hauptframe zurueck (falls ueberhaupt einer existiert).
         *
         * @deprecated Bitte auf getMain() zur�ck greifen
         * @return GUI_Frame Klasse vom Typ GUI_CustomFrame
         **/
        function &getFrame()
        {
            return $this -> Main;
        }

        /**
         * Liefert true, wenn Weblication das Frame Objekt enth�lt.
         *
         * @return bool enth�lt Frame ja/nein
         */
        function hasFrame()
        {
            return (is_a($this -> Main, 'gui_customframe'));
        }

        /**
         * Liefert den Pfad zu den Templates (abh�ngig vom Skin-Ordner und der gew�hlten Sprache).
         *
         * @access public
         * @param string $additionalDir Ordner werden an ermittelten Template Pfad geh�ngt
         * @return string Pfad
         */
        function getTemplatePath($additionalDir=''/*, $baselib=false*/)
        {
            $skin = addEndingSlash($this -> Skin);
            $language = addEndingSlash($this -> Language);
            $dir = 'templates';

            # Ordner skins
            $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
            $folder_dir = $folder_skins . $dir;
            $folder_language = $folder_skins . $language . $dir;

            /*if ($baselib) {
                $path = addEndingSlash(PWD_TILL_GUIS) . $additionalDir;
            }
            else {*/
            if (is_dir($folder_language)) { // Language Ordner
                $path = $folder_language;
            }
            else if (is_dir($folder_dir)) { // Template Ordner
                $path = $folder_dir;
            }
            /*}*/

            if($additionalDir) {
                $path = addEndingSlash($path) . $additionalDir;
            }

            if(!is_dir($path)) {
                $this -> raiseError(__FILE__, __LINE__, sprintf('Path \'%s\' not found (@getTemplatePath)!', $path));
            }

            return $path;
        }

        /**
         * Liefert einen Pfad zum Skin-Verzeichnis zur�ck. Wenn der Parameter $additionalDir gef�llt wird, wird er an das Skin-Verzeichnis dran geh�ngt.
         *
         * @param string $additionalDir Unterverzeichnis vom Skin-Verzeichnis
         * @return string
         */
        function getSkinPath($additionalDir='', $absolute=true)
        {
            $skin = addEndingSlash($this -> Skin);
            $language = addEndingSlash($this -> Language);

            # Ordner Skins

            $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
            if($absolute) {
                $folder_skins = addEndingSlash(realpath('.')) . $folder_skins;
            }
            $folder_language = $folder_skins . $language;
            if($additionalDir != '') {
                $folder_skin_dir = addEndingSlash($folder_skins) . $additionalDir;
                $folder_language_dir = addEndingSlash($folder_language) . $additionalDir;
            }
            else {
                $folder_skin_dir = $folder_skins;
                $folder_language_dir = $folder_language;
            }

            if(is_dir($folder_language_dir)) {
                $path = $folder_language_dir;
            }
            elseif(is_dir($folder_skin_dir)) {
                $path = $folder_skin_dir;
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, sprintf('Path \'%s\' and \'%s\' not found (@getSkinPath)!',
                    $folder_skin_dir, $folder_language_dir));
            }

            return $path;
        }

        /**
         * Sucht das uebergebene Image in einer fest vorgegebenen Verzeichnisstruktur. Nur im Ordner skins.
         *
         * @param string $filename Image Dateiname
         * @return string Bei Erfolg Pfad und Dateiname des gefunden Templates. Im Fehlerfall ''.
         **/
        function findImage($filename)
        {
            $skin = addEndingSlash($this->Skin);
            $language = addEndingSlash($this->Language);
            $images = 'images/';

            # Ordner skins
            $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
            $folder_images = $folder_skins . $images;
            $folder_language = $folder_skins . addEndingSlash($language) . $images;

            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }
            if (is_dir($folder_images)) { // Images Ordner
                if (file_exists($folder_images . $filename)) {
                    return $folder_images . $filename;
                }
            }

            $this -> raiseError(__FILE__, __LINE__, sprintf('Image \'%s\' not found (@findTemplate)!', $folder_images.$filename));
            return '';
        }

        /**
         * Sucht das uebergebene Template in einer fest vorgegebenen Verzeichnisstruktur.
         * Zuerst im Ordner skins, als naechstes im guis Ordner. Wird der Parameter baslib auf true gesetzt,
         * wird abschliessend noch in der baselib gesucht.
         *
         * @param string $filename Template Dateiname
         * @param string $classfolder Unterordner (guis/*) zur Klasse
         * @param boolean $baselib Schau auch in die baselib
         * @return string Bei Erfolg Pfad und Dateiname des gefunden Templates. Im Fehlerfall ''.
         **/
        function findTemplate($filename, $classfolder='', $baselib=false)
        {
            $classfolder = strtolower($classfolder); // 29.05.2007, Workaround for PHP 5
            // PHP 5 liefert mit get_class() pl�tzlich richtig geschriebene Klassennamen in Gro�buchstaben

            $skin = addEndingSlash($this -> Skin);
            $language = addEndingSlash($this -> Language);
            $templates = 'templates/';

            # Ordner skins
            $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
            $folder_templates = $folder_skins . $templates;
            $folder_language = $folder_skins . addEndingSlash($language) . $templates;

            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }

            if (is_dir($folder_templates)) { // Template Ordner
                if (file_exists($folder_templates . $filename)) {
                    return $folder_templates . $filename;
                }
            }

            # Ordner Projekt guis
            $folder_guis = addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
            $folder_skins = $folder_guis . $skin;
            $folder_language = $folder_skins . $language;
            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }
            if (is_dir($folder_skins)) { // Skin Ordner
                if (file_exists($folder_skins . $filename)) {
                    return $folder_skins . $filename;
                }
            }
            if (is_dir($folder_guis)) { // GUI Ordner
                if (file_exists($folder_guis . $filename)) {
                    return $folder_guis . $filename;
                }
            }

            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this -> PathBaselib) . addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
                if (is_dir($folder)) {
                    if (file_exists($folder . $filename)) {
                        return $folder . $filename;
                    }
                }
            }

            $this -> raiseError(__FILE__, __LINE__, sprintf('Template \'%s\' not found (@findTemplate)!', $filename));
            return '';
        }

        /**
         * Sucht das uebergebene StyleSheet in einer fest vorgegebenen Verzeichnisstruktur.
         * Zuerst im Ordner skins, als naechstes im guis Ordner.
         *
         * @param string $filename StyleSheet Dateiname
         * @param string $classfolder Unterordner (guis/*) zur Klasse
         * @param boolean $baselib Schau auch in die baselib
         * @return string Bei Erfolg Pfad und Dateiname des gefunden StyleSheets. Im Fehlerfall ''.
         **/
        function findStyleSheet($filename, $classfolder='', $baselib=false)
        {
            $skin = addEndingSlash($this->Skin);
            $language = addEndingSlash($this -> Language);
            $stylesheets = $this->cssFolder.'/';

            # Ordner skins
            $folder_skins = addEndingSlash(PWD_TILL_SKINS) . $skin;
            $folder_stylesheets = $folder_skins . $stylesheets;
            $folder_language = $folder_skins . $language . $stylesheets;

            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language.$filename)) {
                    return $folder_language.$filename;
                }
            }

            if (is_dir($folder_stylesheets)) { // Template Ordner
                if (file_exists($folder_stylesheets.$filename)) {
                    return $folder_stylesheets.$filename;
                }
            }

            # Ordner Projekt guis
            $folder_guis = addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
            $folder_skins = $folder_guis . $skin;
            $folder_language = $folder_skins . $language;
            if (is_dir($folder_language)) { // Language Ordner
                if (file_exists($folder_language . $filename)) {
                    return $folder_language . $filename;
                }
            }
            if (is_dir($folder_skins)) { // Skin Ordner
                if (file_exists($folder_skins . $filename)) {
                    return $folder_skins . $filename;
                }
            }
            if (is_dir($folder_guis)) { // GUI Ordner
                if (file_exists($folder_guis . $filename)) {
                    return $folder_guis . $filename;
                }
            }

            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this -> RelativePathBaselib) . addEndingSlash(PWD_TILL_GUIS) . addEndingSlash($classfolder);
                if (is_dir($folder)) {
                    if (file_exists($folder . $filename)) {
                        return $folder . $filename;
                    }
                }
            }

            // PHP 4 Workaround:
            if(preg_match('/[A-Z]/', $filename.$classfolder)) {
                return $this->findStyleSheet(strtolower($filename), strtolower($classfolder), $baselib);
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, sprintf('StyleSheet \'%s\' not found (@findStyleSheet)!', $filename));
            }
            return '';
        }

        /**
         * Sucht das uebergebene JavaScript in einer fest vorgegebenen Verzeichnisstruktur.
         * Sucht im Ordner /javascripts! Der 2. Parameter wird momentan nicht beruecksichtigt.
         * Wird der dritte Parameter benutzt, werden die JavaScript Dateien in der Hauptbibliothek gesucht.
         *
         * JavaScripts aus der Hauptbibliothek koennen nicht ueberschrieben werden (macht auch keinen Sinn).
         *
         * @param string $filename JavaScript Dateiname
         * @param string $classfolder Unterordner (guis/*) zur Klasse
         * @return string Bei Erfolg Pfad und Dateiname des gefunden JavaScripts. Im Fehlerfall ''.
         **/
        function findJavaScript($filename, $classfolder='', $baselib=false)
        {
            $javascripts = addEndingSlash(PWD_TILL_JAVASCRIPTS);

            # Ordner skins
            $folder_javascripts = $javascripts;

            # Ordner baselib
            if ($baselib) {
                $folder = addEndingSlash($this->RelativePathBaselib).$javascripts;
                if (file_exists($folder.$filename)) {
                    return $folder.$filename;
                }
            }
            else {
                $folder_guis = addEndingSlash(PWD_TILL_GUIS).addEndingSlash($classfolder);
                if (file_exists($folder_javascripts.$filename)) {
                    return $folder_javascripts.$filename;
                }
                elseif(file_exists($folder_guis.$filename)) {
                    return $folder_guis.$filename;
                }
            }

            $this->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s\' not found (@findJavaScript)!', $filename));
            return '';
        }

         /**
          * Setzte Pfad zum POOL
          *
          * @param $path
          */
        function setPathBaselib($path)
        {
            $this->PathBaselib = $path;
        }
        /**
         * Pfad zum Rootverzeichnis der Baselib
         *
         * @access public
         * @return string Pfad zur Base-Lib
         **/
        function getPathBaselib($path = '')
        {
            return $this->PathBaselib.$path;
        }

         /**
          * @param $path
          */
        function setRelativePathBaselib($path)
        {
            $this->RelativePathBaselib = $path;
        }

        /**
         * Relativer Pfad zum Rootverzeichnis der Baselib
         *
         * @access public
         * @return string Relativer Pfad zur Baselib
         **/
        function getRelativePathBaselib($path = '')
        {
            return $this->RelativePathBaselib . $path;
        }

        /**
         * Erzeugt das MySQL Datenbank Objekt
         *
         * @deprecated
         * @access public
         * @param string $host Hostname des Datenbankservers
         * @param string $dbname Standard Datenbankname
         * @param string $name_of_auth_array Name des Authentifizierungsarrays
         * @param boolean $persistent
         * @return object MySQL_db
         **/
        function &createMySQL($host, $dbname, $name_of_auth_array = 'mysql_auth', $persistent=false)
        {
            $Packet = array(
                'host' => $host,
                'database' => $dbname,
                'auth' => $name_of_auth_array,
                'persistency' => $persistent
            );
            $MySQLInterface = &DataInterface::createDataInterface(DATAINTERFACE_MYSQL, $Packet);

            $DI = &$this->addDataInterface($MySQLInterface);
            return $DI;
        }

        /**
         * Erzeugt das CISAM Client Objekt (not yet implemented)
         *
         * @deprecated
         * @param string $host Hostname des Java Servers
         * @param string $class_path Java Klassenpfad
         * @access public
         **/
        function & createCISAM($host, $class_path)
        {
            $Packet = array(
                'host' => $host,
                'class_path' => $class_path
            );
            $CISAMInterface = & DataInterface::createDataInterface(DATAINTERFACE_CISAM, $Packet);

            return $this -> addDataInterface($CISAMInterface);
        }

        /**
         * DataInterface in die Anwendung einfuegen. Somit ist es ueberall bekannt und kann
         * fuer die DAO Geschichte verwendet werden.
         *
         * @access public
         * @param object $DataInterface Einzufuegendes DataInterface
         * @return object Eingefuegtes DataInterface
         **/
        function &addDataInterface(&$DataInterface)
        {
            $interfaces = $this->Interfaces[$DataInterface->getInterfaceType()] = &$DataInterface;
            return $interfaces;
        }

        /**
         * Liefert ein Interface Objekt.
         *
         * @access public
         * @param string $interface_name
         * @return DataInterface Interface Objekt
         **/
        function &getInterface($interface_name)
        {
            return $this->Interfaces[$interface_name];
        }

        /**
         * Liefert alle Interface Objekte.
         *
         * @access public
         * @return array Interface Objekte
         * @see DAO::createDAO()
         **/
        function &getInterfaces()
        {
            return $this -> Interfaces;
        }

        /**
         * Starts a PHP Session via session_start()!
         * We use the standard php sessions.
         *
         * @access public|static
         * @param string $session_name Name der Session (Default: sid)
         * @param integer $use_trans_sid Transparente Session ID (Default: 0)
         * @param integer $use_cookies Verwende Cookies (Default: 1)
         * @param integer $use_only_cookies Verwende nur Cookies (Default: 0)
         * @return ISession
         **/
        function &startPHPSession($session_name='PHPSESSID', $use_trans_sid=0, $use_cookies=1, $use_only_cookies=0, $autoClose=true)
        {
            ini_set('session.name', $session_name);
            ini_set('session.use_trans_sid', $use_trans_sid);
            ini_set('session.use_cookies', $use_cookies);
            ini_set('session.use_only_cookies', $use_only_cookies);

//				$session_started = session_start();
            $Session = new ISession($autoClose);

            if(isset($this)) { // static call possible
//					$this->session_started = $session_started;
                $this->Session = &$Session;
            }
            return $Session;
        }

        /**
         * Erzeugt eine Instanz vom eigenen Session Handler. Ansprechbar ueber Weblication::Session.
         *
         * @access public
         * @param string $tabledefine DAO Tabellendefinition.
         **/
        function createSessionHandler($tabledefine)
        {
            $this->SessionHandler = new SessionHandler($this->Interfaces, $tabledefine);
            $this->Session = new ISession();
        }

        /**
         * Schliesst alle Verbindungen und loescht die Interface Objekte.
         * Bitte bei der Erstellung von Interface Objekten sicherheitshalber immer abschliessend mit destroy() alle Verbindungen trennen!
         *
         * @access public
         **/
        function destroy()
        {
            if(defined('DATAINTERFACE_MYSQL')) {
                if (isset($this->Interfaces[DATAINTERFACE_MYSQL]) and is_a($this->Interfaces[DATAINTERFACE_MYSQL], 'MySQL_Interface')) {
                    $this->Interfaces[DATAINTERFACE_MYSQL] -> close();
                    unset($this->Interfaces[DATAINTERFACE_MYSQL]);
                }
            }
            if(defined('DATAINTERFACE_C16')) {
                if(isset($this->Interfaces[DATAINTERFACE_C16]) and is_a($this->Interfaces[DATAINTERFACE_C16], 'C16_Interface')) {
                    $this->Interfaces[DATAINTERFACE_C16]->close();
                    unset($this->Interfaces[DATAINTERFACE_C16]);
                }
            }
            parent::destroy();
        }

        /**
         * Weblication::compress()
         *
         * Komprimiert Html Ausgaben (Entfernt Kommentare, Leerzeichen, Zeilenvorschuebe)
         *
         * @access public
         * @param string $html Content
         * @return string Komprimierter Content
         **/
        function compress($html)
        {
            //$html = ereg_replace("<!--.*-->", "", $html);
            //$html = str_replace("\n\r", '', $html);
            $html = str_replace(array("\n", "\r"), '', $html);
            $space_str = '   ';
            $html = str_replace($space_str, '', $html);
            return $html;
        }

        /**
         * Projektname auslesen
         *
         * @return string
         */
        function getProject()
        {
            return $this->Project;
        }

        /**
         * Definiert einen Projektnamen / App-Name
         *
         * @param string $project
         * @return boolean
         */
        function setProject($project)
        {
            if($this->Project == 'unknown') {
                $this->Project = $project;
                return true;
            }
            return false;
        }

        /**
         * Seitentitel setzen
         *
         * @param string $title
         */
        function setTitle($title)
        {
            $this->Title = $title;
        }

        /**
         * Seitentitel auslesen
         *
         * @return string
         */
        function getTitle()
        {
            return $this->Title;
        }

        /**
         * Erzeugt das erste GUI_Module in der Kette (Momentan wird hier der Seitentitel mit dem Projektnamen gefuellt).
         *
         * @access public
         * @param string $GUIName GUI_Module (Standard-Wert: GUI_CustomFrame)
         * @return boolean Erfolgsstatus
         **/
        function run($Module = 'GUI_CustomFrame')
        {
            // TODO Get Parameter frame
            // TODO Subcode :: createSubCode()
            $GUI = &GUI_Module::createGUIModule($Module, $this, $this);
            if (isNil($GUI)) {
                $this->raiseError(__FILE__, __LINE__, 'Klasse ' . $Module .
                    ' wurde nicht gefunden oder existiert nicht. (@Weblication -> run).');
                // TODO Page Error
                return false;
            }
            else {
                /** Hinweis: erstes GUI registriert sich selbst �ber setMain als
                    Haupt-GUI im GUI_Module Konstruktor **/

//					$this -> Main = & $GUI;

                if (is_a($this->Main, 'gui_customframe')) {
                    # Seitentitel (= Project)
                    $Header = & $this->Main->getHeaderdata();
                    if ($Header) {
                        $title = $this->Title;
                        $Header->setTitle($title);
                        $Header->setLanguage($this->Language);
                        if($this->Charset) $Header->setCharset($this->Charset);
                    }
                }
                return true;
            }
        }

        /**
         * Weblication::prepareContent()
         *
         * Einleitung zur Aufbereitung des Contents (der Inhalte) der Webseite.
         *
         * @access public
         **/
        function prepareContent()
        {
            if (is_a($this -> Main, 'gui_module')) {
                $this -> Main -> prepareContent();
            }
            else {
                $this -> raiseError(__FILE__, __LINE__, 'Main ist nicht vom Typ GUI_Module oder nicht gesetzt (@PrepareContent).');
            }
        }

        /**
         * Passt die Verzeichnisse der Bilder auf den Skin und/oder die Sprache an.
         *
         * @param string $content Inhalt eines Templates
         * @return string
         */
        function adjustImageDir($content)
        {
            $folderImages = 'skins/' . $this -> Skin;
            if(is_dir($folderImages . '/' . $this -> Language)) {
                $folderImages .= '/' . $this -> Language;
            }
            $folderImages .= '/images/';

            $content = str_replace($folderImages, 'images/', $content);
            $content = str_replace('images/', $folderImages, $content);

            return $content;
        }

        /**
         * Fertigen Content (Inhalt) ausgeben.
         *
         * @access public
         * @param boolean $print True gibt den Inhalt sofort auf den Bildschirm aus. False liefert den Inhalt zurueck
         * @return string Inhalt der Webseite
         **/
        function finalizeContent($print=true, $compress=false)
        {
            if (is_a($this->Main, 'gui_module')) {
                $content = $this->Main->finalizeContent();

                $content = $this->adjustImageDir($content);

                if ($compress) {
                    $content = $this->compress($content);
                }

                if ($print) {
                    print $content;
                }
                else {
                    return $content;
                }
            }
            else {
                $this->raiseError(__FILE__, __LINE__, 'Main ist nicht vom Typ GUI_Module oder nicht gesetzt (@CreateContent).');
            }
        }
    }
}