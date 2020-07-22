<?php
/**
 * -= Rapid Module Library (RML) =-
 *
 * gui_schema.class.php
 *
 * GUI_Scheme laedt verschiedene Html Schemas (Html Schablonen) und sucht darin nach weiteren GUIs.
 * Hinweis: Ein Schema wird ueber die URI als Parameter ?schema=index definiert!
 *
 * @version $Id: gui_schema.class.php,v 1.2 2007/05/31 14:35:10 manhart Exp $
 * @version $Revision 1.0$
 * @version
 *
 * @package GUI_Schema
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

class GUI_Schema extends GUI_Module
{
    //@var array Hier sind alle uebergebenen Schemas enthalten.
    //@access private
    var $Schemes = Array();

    //@var array Behaelt Indexierung von $this -> Schemes bei. Das Array enthaelt jedoch die Template Handles fuer das jeweilige Scheme (um es eindeutig zuordnen zu koennen)
    //@access private
    var $SchemeHandles = Array();

    /**
     * Konstruktor
     *
     * @access public
     * @param object $Owner Besitzer
     * @param bool $autoLoadFiles
     * @param array $params
     */
    function __construct(& $Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    /**
     * Initialisierung der Standard Werte und Superglobals.
     *
     * @param int $superglobals
     * @access public
     **/
    function init($superglobals=I_REQUEST)
    {
        $this->Defaults->addVar('schema', '');
        parent::init($superglobals);
    }

    /**
     * GUI_Schema::loadSchemes()
     *
     * Laedt alle uebergebenen Schemas.
     *
     * @access public
     * @param array $schemes
     **/
    function loadSchemes($schemes = Array())
    {
        $directory = './' . addEndingSlash(PWD_TILL_SCHEMES);

        $numSchemes = count($schemes);
        if ($numSchemes > 0) {
            $this -> SchemeHandles = Array();

            $this -> Template -> setDirectory($directory);
            for ($i=0; $i<$numSchemes; $i++) {
                $bExists = file_exists($directory . $schemes[$i] . '.html');
                if ($bExists) {
                    $uniqid = 'file_'.$i; // uniqid('file_'); uniqid bremst das Laufzeitverhalten emens!
                    $this -> Template -> setFile($uniqid, $schemes[$i] . '.html');
                    $this -> SchemeHandles[] = $uniqid;
                }
                else {
                    $this -> Schema404($schemes[$i]);
                    break;
                }
                unset($uniqid);
            }
            $this -> Schemes = $schemes;
        }
        else {
            $this -> Schema404();
        }
    }

    /**
     * GUI_Schema::Schema404()
     *
     * Raise an Error 404: Schema not found.
     * Loads schema404.html from templates!
     *
     * @access private
     * @param string $schema None existing Schema
     **/
    function Schema404($schema = '')
    {
        $this -> raiseError(__FILE__, __LINE__, sprintf('Schema \'%s\' doesn\'t exist', $schema . '.html'));
        $this -> SchemeHandles = Array();
        $this -> Template -> clear();
        $file = $this -> Weblication -> findTemplate('schema404.html', $this -> getClassName(), true);
        $this -> Template -> setFilePath('error404', $file);
        $this -> Template -> setVar('SCHEMA', empty($schema) ? '(empty)' : $schema  . '.html');
        $this -> SchemeHandles[] = 'error404';
        $schemes = Array();
    }

    /**
     * Liest die _GET Variable "schema" ein, laedt Schemas und sucht nach den darin befindlichen GUIs.
     * Wurde kein Schema angegeben, wird versucht von der Weblication ein Default Schema reinzuladen.
     *
     * @access public
     **/
    function prepare()
    {
        $schemes = array();

        $schema = $this->Input->getVar('schema');
        if($schema == '') {
            $schema = $this->Weblication->getDefaultSchema();
        }

        if(strpos($schema, ',') !== false) {
            $schemes = explode(',', $schema);
        }
        else {
            $schemes[] = $schema;
        }
//        $schemes = trim($this -> Input -> getVar('schema'));
//        if (strlen($schemes) > 0) {
//
//        }
//        else {
//            $schemes = Array();
//            $schemes[] = $this->Weblication->getSchema();
//        }

        $this -> loadSchemes($schemes);
        $this -> searchGUIsInPreloadedContent();
    }

    /**
     * GUI_Schema::finalize()
     *
     * Analysiert jedes Html Template. Dabei werden Bloecke und Variablen zugewiesen.
     * Alle fertigen Html Templates werden zu einem Inhalt zusammen gefuehrt.
     * Der gesamte Inhalt wird zurueck gegeben.
     *
     * @access public
     * @return string Content
     **/
    function finalize()
    {
        $content = '';
        $numSchemes = sizeof($this->SchemeHandles);
        for ($i=0; $i<$numSchemes; $i++) {
            $this -> Template -> parse($this -> SchemeHandles[$i]);
            $content .= $this -> Template -> getContent($this -> SchemeHandles[$i]);
        }
        return $content;
    }
}