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
 * @author Alexander Manhart <alexander@manhart-it.de>
 * @link https://alexander-manhart.de
 */

class GUI_Schema extends GUI_Module
{
    /**
     * @var array list of schemes
     */
    private array $schemes = [];

    /**
     * @var array list of indexed schemes
     */
    private array $handles = [];

    /**
     * @var bool no files needed
     */
    protected bool $autoLoadFiles = false;

    /**
     * @param int|null $superglobals takes parameter schema from request
     */
    public function init(?int $superglobals = I_REQUEST)
    {
        $this->Defaults->addVar('schema');
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
     * load schemes
     *
     * @param array $schemes
     */
    private function loadSchemes(array $schemes = []): void
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

        $directory = $directory . '/' . addEndingSlash(PWD_TILL_SCHEMES);

        // fixed param "category": Divides schemas into subdirectories
        $category = $this->getFixedParam('category');
        if($category != null) $directory .= addEndingSlash($category);

        // fixed param "alternate": Switch to an alternative if the schema was not found in the folder.
        $alternate = $this->getFixedParam('alternate');

        $numSchemes = count($schemes);

        if($numSchemes == 0) {
            $this->schema404();
            return;
        }

        $this->handles = [];

        $this->Template->setDirectory($directory);
        for($i = 0; $i < $numSchemes; $i++) {
            $bExists = file_exists($directory . $schemes[$i] . '.html');
            if(!$bExists and $alternate != null) {
                $schemes[$i] = $alternate;
                $bExists = file_exists($directory . $schemes[$i] . '.html');
            }
            if($bExists) {
                $uniqId = 'file_' . $i;
                $this->Template->setFile($uniqId, $schemes[$i] . '.html');
                $this->handles[] = $uniqId;
            }
            else {
                $this->schema404($schemes[$i]);
                break;
            }
            unset($uniqId);
        }
        $this->schemes = $schemes;
    }

    /**
     * Raise an Error 404: Schema not found.
     * Loads schema404.html from templates!
     *
     * @param string $schema None existing Schema
     */
    private function schema404(string $schema = ''): void
    {
        $schema404 = 'schema404.html';
        $this->raiseError(__FILE__, __LINE__, sprintf('Schema \'%s\' doesn\'t exist', $schema . '.html'));
        $this->handles = array();
        $this->Template->clear();
        $file = $this->Weblication->findTemplate($schema404, $this->getClassName(), true);
        $this->Template->setFilePath('error404', $file);
        $this->Template->setVar('SCHEMA', empty($schema) ? '(empty)' : $schema . '.html');
        $this->handles[] = 'error404';
    }

    /**
     * Liest die _GET Variable "schema" ein, laedt Schemas und sucht nach den darin befindlichen GUIs.
     * Wurde kein Schema angegeben, wird versucht von der Weblication ein Default Schema reinzuladen.
     **
     *
     * @throws \pool\classes\ModulNotFoundExeption
     */
    public function provision(): void
    {
        $schemes = [];

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
        foreach($this->handles as $handle) {
            $content .= $this->Template->parse($handle)->getContent($handle);
        }
        return $content;
    }
}