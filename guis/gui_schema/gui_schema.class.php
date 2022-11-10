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
    /**
     * @var array list of schemes
     */
    private array $Schemes = [];

    /**
     * @var array list of indexed schemes
     */
    private array $SchemeHandles = [];

    /**
     * Konstruktor
     *
     * @param Component|null $Owner Besitzer
     * @param bool $autoLoadFiles
     * @param array $params
     * @throws ReflectionException
     */
    function __construct(Component $Owner, bool $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    /**
     * Initialisierung der Standard Werte und Superglobals.
     *
     * @param int|null $superglobals
     **/
    public function init(?int $superglobals=I_REQUEST)
    {
        $this->Defaults->addVar('schema', '');
        parent::init($superglobals);

        /**
         * fixedParams:
         *
         * directory - a constant or directory
         * category - a subdirectory
         * alternate - if schema was not found, redirect to this schema
         */
    }

    /**
     * Laedt alle uebergebenen Schemas.
     *
     * @param array $schemes
     **/
    private function loadSchemes(array $schemes = [])
    {
        $directory = $this->getFixedParam('directory');
        if($directory != null) {
            // test string for a constant
            if(defined($directory)) {
                $directory = constant($directory);
            }
        }
        else {
            $directory = '.';
        }

        $directory = $directory.'/'.addEndingSlash(PWD_TILL_SCHEMES);

        // fixed param "category": Divides schemas into subdirectories
        $category = $this->getFixedParam('category');
        if($category != null) $directory .= addEndingSlash($category);

        // fixed param "alternate": Switch to an alternative if the schema was not found in the folder.
        $alternate = $this->getFixedParam('alternate');

        $numSchemes = count($schemes);
        if ($numSchemes > 0) {
            $this->SchemeHandles = Array();

            $this->Template->setDirectory($directory);
            for ($i=0; $i<$numSchemes; $i++) {
                $bExists = file_exists($directory . $schemes[$i] . '.html');
                if($bExists == false and $alternate != null) {
                    $schemes[$i] = $alternate;
                    $bExists = file_exists($directory . $schemes[$i] . '.html');
                }
                if ($bExists) {
                    $uniqid = 'file_'.$i; // uniqid('file_'); uniqid bremst das Laufzeitverhalten emens!
                    $this->Template->setFile($uniqid, $schemes[$i] . '.html');
                    $this->SchemeHandles[] = $uniqid;
                }
                else {
                    $this->Schema404($schemes[$i]);
                    break;
                }
                unset($uniqid);
            }
            $this->Schemes = $schemes;
        }
        else {
            $this->Schema404();
        }
    }

    /**
     * GUI_Schema::Schema404()
     *
     * Raise an Error 404: Schema not found.
     * Loads schema404.html from templates!
     *
     * @param string $schema None existing Schema
     **/
    private function Schema404($schema = '')
    {
        $schema404 = 'schema404.html';
        $this->raiseError(__FILE__, __LINE__, sprintf('Schema \'%s\' doesn\'t exist', $schema . '.html'));
        $this->SchemeHandles = array();
        $this->Template->clear();
        $file = $this->Weblication->findTemplate($schema404, $this->getClassName(), true);
        $this->Template->setFilePath('error404', $file);
        $this->Template->setVar('SCHEMA', empty($schema) ? '(empty)' : $schema . '.html');
        $this->SchemeHandles[] = 'error404';
    }

    /**
     * Liest die _GET Variable "schema" ein, laedt Schemas und sucht nach den darin befindlichen GUIs.
     * Wurde kein Schema angegeben, wird versucht von der Weblication ein Default Schema reinzuladen.
     **/
    public function provision()
    {
        $schemes = array();

        $schema = $this->Input->getVar('schema');
        if($schema == '') {
            $schema = $this->Weblication->getDefaultSchema();
        }

        if(str_contains($schema, ',')) {
            $schemes = explode(',', $schema);
        }
        else {
            $schemes[] = $schema;
        }

        $this->loadSchemes($schemes);
        $this->searchGUIsInPreloadedContent();

        parent::provision();
    }

    /**
     * Analysiert jedes Html Template. Dabei werden Bloecke und Variablen zugewiesen.
     * Alle fertigen Html Templates werden zu einem Inhalt zusammen gefuehrt.
     * Der gesamte Inhalt wird zurueck gegeben.
     *
     * @return string Content
     **/
    public function finalize(): string
    {
        $content = '';
        $numSchemes = count($this->SchemeHandles);
        for ($i=0; $i<$numSchemes; $i++) {
            $this->Template->parse($this->SchemeHandles[$i]);
            $content .= $this->Template->getContent($this->SchemeHandles[$i]);
        }
        return $content;
    }
}