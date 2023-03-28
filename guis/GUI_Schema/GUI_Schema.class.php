<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     * * fixedParams:
     * directory - a constant or directory
     * category - a subdirectory
     * alternate - if schema was not found, redirect to this schema
     * @var int $superglobals takes parameter schema from request
     */
    protected int $superglobals = Input::INPUT_REQUEST;

    /**
     * load schemes
     * @param array $schemes
     * @return void
     */
    private function loadSchemes(array $schemes = []): void
    {
        if (0 == $numSchemes = count($schemes)) {//no schema -> abort
            $this->schema404();
            return;
        }
        //get schema directory
        $directory = $this->getInternalParam('directory');
        if (is_string($directory)) {
            if (defined($directory))//test string for a constant
                $directory = constant($directory);
        } else
            $directory = '.';
        $directory = buildDirPath($directory, PWD_TILL_SCHEMES);
        /** @var string $category Divides schemas into subdirectories */
        $category = $this->getInternalParam('category');
        /** @var $vhostMode 0-> category only, 1-> vhost/category/, 2-> category/vhost/, other-> vhost as alternative for category */
        $vhostMode = (int)$this->getInternalParam('vhostMode', -1);
        if ($category) {
            if ($vhostMode == 1) //preceding vHost
                $this->appendVHost($directory);
            //category given and to be included
            $directory .= addEndingSlash($category);
            if ($vhostMode == 2)//trailing vHost
                $this->appendVHost($directory);
        } elseif ($vhostMode)//no category and vHost to be included
            $this->appendVHost($directory);
        /** @var string $alternate Alternative schema if the target-schema was not found */
        $alternate = $this->getInternalParam('alternate');
        //Prep template-engine
        $this->templates = [];
        $this->Template->setDirectory($directory);
        //try to load according schema files into template-engine...
        for ($i = 0; $i < $numSchemes; $i++) {
            $schemaExists = file_exists($directory . $schemes[$i] . '.html');
            if (!$schemaExists && $alternate) {//schema missing
                $schemes[$i] = $alternate;
                $schemaExists = file_exists($directory . $alternate . '.html');//test alternative
            }
            if ($schemaExists) {//add schema to our templates
                $uniqId = 'file_' . $i;
                $this->Template->setFile($uniqId, $schemes[$i] . '.html');
                $this->templates[$uniqId] = null;//manually set the template
                unset($uniqId);
            } else {//schema not found -> abort
                $this->schema404($schemes[$i]);
                return;
            }
        }
    }

    /**
     * Raise an Error 404: Schema not found.
     * Loads schema404.html from templates!
     *
     * @param string|null $schema None existing Schema
     */
    private function schema404(?string $schema = null): void
    {
        //report problem
        $errMessage = $schema ? "Schema '$schema.html' doesn't exist" : "No schema specified";
        $this->raiseError(__FILE__, __LINE__, $errMessage);
        //clear
        $this->templates = [];
        $this->Template->clear();
        //become 404 GUI
        http_response_code(404);
        $file = $this->Weblication->findTemplate('schema404.html', $this->getClassName(), true);
        $this->Template->setFilePath('error404', $file);
        $this->Template->setVar('SCHEMA', empty($schema) ? '(empty)' : $schema . '.html');
        $this->templates['error404'] = null;//manually set the template
    }

    /**
     * Liest die _GET Variable "schema" ein und laedt die angegebenen Schemata.
     * Wurde kein Schema angegeben, wird versucht von der Weblication ein Default Schema reinzuladen.
     */
    public function loadFiles(): void
    {
        if (!$schema = $this->Input->getVar('schema'))//'' also gets replaced with the default
            $schema = $this->Weblication->getDefaultSchema();
        $schemes = explode(',', $schema);
        $this->loadSchemes($schemes);
    }

    /**
     * Checks whether the given directory includes a subdirectory which name matches the current vHost and appends it
     * @param string $directory the directory to be appended
     * @return void
     */
    private function appendVHost(string &$directory): void
    {
        $vhost =  $_SERVER['SERVER_NAME'];
        if (is_dir($proposed = buildDirPath($directory, $vhost)))
            $directory = $proposed;
    }
}