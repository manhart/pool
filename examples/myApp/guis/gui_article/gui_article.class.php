<?php
/**
 * # PHP Object Oriented Library (POOL) #
 *
 * @since 2019/02/17
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

require_once PWD_TILL_CLASSES.'/Lorem.class.php';

/**
 * Class GUI_Article
 *
 * @package auftragserfassung
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @access public
 **/
class GUI_Article extends GUI_Module
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
     * Class GUI_Article
     *
     * @access public
     * @param object $Owner Besitzer
     **/
    function __construct(& $Owner)
    {
        parent::__construct($Owner);
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
        $this->Defaults->addVar('count', 1);
        $this->Defaults->addVar('useLipsum', 1); // very slow
        parent::init(I_GET|I_POST);

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
        $template = $this->Weblication->findTemplate('tpl_article.html', $this->getClassName());
        $this->Template->setFilePath('stdout', $template);

        if(is_a($this->Weblication->getMain(), 'GUI_CustomFrame')) {
            $Frame = &$this->Weblication->getMain();

            $this->Template->setVar('MODULE', $className=$this->getClassName());
            $Frame->addBodyLoad('MODULE=\''.$className.'\'');
        }
    }

    /**
     * Template vorbereiten
     *
     * @access public
     **/
    function prepare()
    {
        $useLipsum = (int)$this->Input->getVar('useLipsum');
        $count = (int)$this->Input->getVar('count');

        $lang = $this->Weblication->getLanguage();

        switch ($lang) {
            case 'de':
                $article = 'Artikel';
                break;

            default: $article = 'article';
        }

        for($i=0; $i<$count; $i++) {
            if($useLipsum) {
                $lipsum = (string)simplexml_load_file('https://www.lipsum.com/feed/xml?amount=1&what=paras&start=0')->lipsum;
            }
            else {
                $lipsum = Lorem::ipsum(1);
            }
            $ArticleBlock = &$this->Template->newBlock('article');
            $ArticleBlock->setVar(
                array(
                    'headline' => ($i+2).'. '.$article,
                    'text' => $lipsum
                )
            );
        }

        if($this->Weblication->hasFrame()) {
            $Frame = &$this->Weblication->getMain(); /* @var $Frame GUI_CustomFrame */
            $this->prepareDefaults($Frame);
        }
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