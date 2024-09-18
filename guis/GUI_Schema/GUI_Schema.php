<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

use pool\classes\Core\Input\Input;
use pool\classes\Core\Weblication;

use const pool\PWD_TILL_SCHEMES;

/**
 * GUI_Scheme loads various Html schemas (Html templates) and searches for further GUIs in them.
 * Note: A schema is defined via the URI as a parameter ?schema=index!
 *
 * @package GUI_Schema
 * @since 2003-07-10
 */
class GUI_Schema extends GUI_Module
{
    /**
     * fixedParams:
     * directory - a constant or directory
     * category - a subdirectory
     * alternate - if schema was not found, redirect to this schema
     * vhostMode - 0-> category only, 1-> vhost/category/, 2-> category/vhost/, other-> vhost as alternative for category
     * errorFallbackDefaultSchema - if alternate schema was not found, redirect to the default schema
     *
     * @var int $superglobals takes parameter schema from request
     */
    protected int $superglobals = Input::REQUEST;

    /**
     * load schemes
     *
     * @param array $schemes
     * @return void
     */
    private function loadSchemes(array $schemes = []): void
    {
        if (0 === count($schemes)) {//no schema -> abort
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
            if ($vhostMode === 1) //preceding vHost
                $this->appendVHost($directory);
            //category given and to be included
            $directory .= addEndingSlash($category);
            if ($vhostMode === 2)//trailing vHost
                $this->appendVHost($directory);
        } elseif ($vhostMode)//no category and vHost to be included
            $this->appendVHost($directory);
        /** @var string $alternate Alternative schema if the target-schema was not found */
        $alternate = $this->getInternalParam('alternate', $this->getInternalParam('errorFallbackDefaultSchema') ? Weblication::getInstance()->getDefaultSchema() : '');
        //Prep template-engine
        $this->templates = [];
        $this->Template->setDirectory($directory);
        //try to load according schema files into template-engine...
        foreach ($schemes as $i => $iValue) {
            $schemaExists = file_exists($directory.$iValue.'.html');
            if (!$schemaExists && $alternate) {//schema missing
                $schemes[$i] = $alternate;
                $schemaExists = file_exists($directory.$alternate.'.html');//test alternative
            }
            if ($schemaExists) {//add schema to our templates
                $uniqId = 'file_'.$i;
                $this->Template->setFile($uniqId, $iValue.'.html');
                $this->templates[$uniqId] = null;//manually set the template
                unset($uniqId);
            } else {//schema not found -> abort
                $this->schema404($iValue);
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
        // clear templates
        $this->templates = [];
        $this->Template->clear();
        //become 404 GUI
        http_response_code(404);
        $file = $this->Weblication->findTemplate('schema404.html', $this->getClassName(), true);
        $this->Template->setFilePath('error404', $file);
        $this->Template->setVar('SCHEMA', empty($schema) ? '(empty)' : $schema.'.html');
        $this->Template->setVar('WEBMASTER', $_SERVER['SERVER_ADMIN']);
        $this->templates['error404'] = null;//manually set the template
    }

    /**
     * Reads the query parameter (_GET variable) "schema" and loads the specified schemas.
     * If no schema was specified, the weblication attempts to load a default schema.
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
     *
     * @param string $directory the directory to be appended
     * @return void
     */
    private function appendVHost(string &$directory): void
    {
        $vhost = $_SERVER['SERVER_NAME'];
        if (is_dir($proposed = buildDirPath($directory, $vhost)))
            $directory = $proposed;
    }
}