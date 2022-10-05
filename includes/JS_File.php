<?php
/*
 * g7system.local
 *
 * JS_File.php created at 23.09.22, 15:51
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */
namespace pool\includes\JS_File {

    class jQuery extends dir\_3rdParty {
        public const VERSION = '3.3.1';
        public const DIRECTORY = 'jquery';
        public const SUB_PATH = '';
    }


}
namespace pool\includes\JS_File\dir{

    use Weblication;
    use function addEndingSlash;
    use function readFiles;

    //abstract class JS_File{
//        public const VERSION = '';
//
//        /**
//         * Retrieves the
//         * @param bool $min
//         * @param string $version Specify the version to use defaults to self::VERSION
//         * @param bool $raiseError
//         * @return string
//         */
//        public final static function getFile(bool $min, string $version = '', bool $raiseError = true):string{
//            $version_param = $version;
//            //use default version unless specific version is requested
//            if (!$version)
//                $version = self::VERSION;
//            //Request filename using the called child's implementation
//            $file = static::getPath($version) . static::getFileName($version);
//            $success = false;
//            //assemble final filename and fallback to minified/non-minified version if necessary
//            if ($min){
//                $completeFile = $file.'.min.js';
//                if (file_exists($completeFile))
//                    $success = true;
//                else
//                    $completeFile = $file . '.js';
//            }else{
//                $completeFile = $file.'.js';
//                if (file_exists($completeFile))
//                    $success = true;
//                else
//                    $completeFile = $file .'.min.js';
//            }
//            //check result
//            if ($success || file_exists($completeFile)) {
//                //file found
//                return $file;
//            } elseif ((bool)$version_param) {
//                //try default version
//                return static::getFile($min,self::VERSION,$raiseError);
//            } else {
//                //handel missing file
//                if($raiseError) {
//                    $weblication = Weblication::getInstance();
//                    $weblication->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s.[js/min.js]\' not found (@getFile)!', $file));
//                }
//                return '';
//            }
//
//        }
//
//        /**
//         * @param string $version
//         * @return string the filename without the file extension
//         */
//        protected static function getFileName(string $version): string{
//            return '';
//        }
//
//        /**
//         * @param string $version
//         * @return string the directory where this resource is located with an ending slash
//         */
//        abstract protected static function getPath(string $version): string;
//
//    }

    abstract class _3rdParty{
        public const DIRECTORY = '';
        public const VERSION = '';
        public const SUB_PATH = '';
        //public const FILE_EXT = '';
        public static function getFiles(bool $min, string $version = ''):array{
            $path = static::getPath($version);
            $files = readFiles($path,false, '/\.js$/');
            sort($files);
            $returnFiles=array();
            for ($i = 0; $i<count($files);$i++){
                if (preg_match('/.min.js$/',$files[$i])){
                    //add non skipped min.js
                    $returnFiles[] = $files[$i];
                }else{
                    //plain JavaScript file
                    $filename = substr($files[$i],0,strlen($files[$i])-3);
                    $hasMinifiedVersion = preg_match("/$filename.min.js$/",$files[$i+1]);
                    if (!$hasMinifiedVersion){
                        //no alternative
                        $returnFiles[] = $files[$i];
                    }else {
                        if ($min) //use upcoming min.js
                            $returnFiles[] = $files[$i+1];
                        else //use this plain js file
                            $returnFiles[] = $files[$i];
                        //skip the alternative
                        $i++;
                    }
                }

            }
            return $returnFiles;
        }



        protected static function getPath(string $version): string
        {
            $version = $version?: static::VERSION;
            $root =     addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT);
            $dir =      addEndingSlash($root.   static::DIRECTORY);
            $versDir =  addEndingSlash($dir.    $version);
            return      addEndingSlash($versDir.static::SUB_PATH);
        }

    }
    //Directory's define their
    //const DIRECTORY
    {
        class Dir_air_datepicker extends _3rdParty {
            const DIRECTORY = 'air-datepicker';
            const VERSION = '3.3.0';
        }
        class Dir_autocomplete extends _3rdParty{
            const DIRECTORY = 'autocomplete';
            const VERSION = '1.8.4';
        }
        class Dir_bootstrap extends _3rdParty{
            const DIRECTORY = 'bootstrap';
            const VERSION = '4.6.0';
        }
        class Dir_bootstrap_datepicker extends _3rdParty{
            const DIRECTORY = 'bootstrap-datepicker';
            const VERSION = '1.9.0';
        }
        class Dir_bootstrap_datetimepicker extends _3rdParty{
            const DIRECTORY = 'bootstrap-datetimepicker';
            const VERSION = '5.39.0';
        }
        class Dir_bootstrap_icons extends _3rdParty{
            const DIRECTORY = 'bootstrap-icons';
        }
        class Dir_bootstrap_select extends _3rdParty{
            const DIRECTORY = 'bootstrap-select';
            const VERSION = '1.13.18';
        }
        class Dir_bootstrap_table extends _3rdParty{
            const DIRECTORY = 'bootstrap-table';
            const VERSION = '1.21.0';
        }
        class Dir_bootstrap_toggle extends _3rdParty{
            const DIRECTORY = 'bootstrap-toggle';
            const VERSION = '3.6.1';
        }
        class Dir_bootstrap_typeahead extends _3rdParty{
            const DIRECTORY = 'bootstrap-typeahead';
            const VERSION = '0.0.5-8';
        }
        class Dir_currentScript_polyfill extends _3rdParty{
            const DIRECTORY = 'currentScript-polyfill';
        }
        class Dir_datatables extends _3rdParty{
            const DIRECTORY = 'datatables';
            const VERSION = '1.10.25';
        }
        class Dir_datatables_buttons extends _3rdParty{
            const DIRECTORY = 'datatables-buttons';
            const VERSION = '1.7.1';
        }
        class Dir_datatables_responsive extends _3rdParty{
            const DIRECTORY = 'datatables-responsive';
            const VERSION = '2.2.9';
        }
        class Dir_datatables_rowgroup extends _3rdParty{
            const DIRECTORY = 'datatables-rowgroup';
            const VERSION = '1.1.3';
        }
        class Dir_datatables_select extends _3rdParty{
            const DIRECTORY = 'datatables-select';
            const VERSION = '1.3.3';
        }
        class Dir_dhtmlx extends _3rdParty{
            const DIRECTORY = 'dhtmlx';
            const VERSION = '7.2.5';
        }
        class Dir_dropzone extends _3rdParty{
            const DIRECTORY = 'dropzone';
            const VERSION = '5.9.3';
            //todo min folder
        }
        class Dir_fontawesome extends _3rdParty{
            const DIRECTORY = 'fontawesome';
            const VERSION = '5.15.4';
        }
        class Dir_jQuery extends _3rdParty{
            const DIRECTORY = 'jquery';
            const VERSION = '3.6.0';
        }
        class Dir_jquery_dragtable extends _3rdParty{
            const DIRECTORY = 'jquery-dragtable';
            const VERSION = '2.0.15';
        }
        class Dir_jquery_resizable_columns extends _3rdParty{
            const DIRECTORY = 'jquery-resizable-columns';
            const VERSION = '0.2.3';
        }
        class Dir_jquery_tablednd extends _3rdParty{
            const DIRECTORY = 'jquery-tablednd';
            const VERSION = '1.0.3';
        }
        class Dir_jquery_tableexport extends _3rdParty{
            const DIRECTORY = 'jquery-tableexport';
            const VERSION = '1.10.24';
        }
        class Dir_js extends _3rdParty{
            const DIRECTORY = 'js';
        }
        class Dir_jstree extends _3rdParty{
            const DIRECTORY = 'jstree';
            const VERSION = '3.3.12';
        }
        class Dir_moment extends _3rdParty{
            const DIRECTORY = 'moment';
            const VERSION = '2.29.1';
        }
        class Dir_moment_timezone extends _3rdParty{
            const DIRECTORY = 'moment-timezone';
            const VERSION = '0.5.33';
        }
        class Dir_perfect_scrollbar extends _3rdParty{
            const DIRECTORY = 'perfect-scrollbar';
            const VERSION = '1.5.3';
        }
        class Dir_popper extends _3rdParty{
            const DIRECTORY = 'popper';
            const VERSION = '1.16.1';
        }
        class Dir_quilljs extends _3rdParty{
            const DIRECTORY = 'quilljs';
            const VERSION = '1.3.7';
        }
        class Dir_select2 extends _3rdParty{
            const DIRECTORY = 'select2';
            const VERSION = '4.0.13';
        }
        class Dir_summernote extends _3rdParty{
            const DIRECTORY = 'summernote';
            const VERSION = '0.8.18';
            //todo plugin?
        }
        class Dir_uppy extends _3rdParty{
            const DIRECTORY = 'uppy';
            const VERSION = '3.0.1';
        }
        class Dir_zxcvbn extends _3rdParty{
            const DIRECTORY = 'zxcvbn';
            const VERSION = '4.4.2';
        }
        //Resource's define their
        //const SUB_PATH
        // const VERSION (default version)
        {
            interface StylesheetResource{const FILE_EXT = '.[css][scss]';}//Marker interfaces
            class Res_S_air_datepicker extends Dir_air_datepicker implements StylesheetResource {
            }
            class Res_S_autocomplete extends Dir_autocomplete implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap extends Dir_bootstrap implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_datepicker extends Dir_bootstrap_datepicker implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_datetimepicker extends Dir_bootstrap_datetimepicker implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_icons extends Dir_bootstrap_icons implements StylesheetResource {
            }
            class Res_S_bootstrap_select extends Dir_bootstrap_select implements StylesheetResource {
                const SUB_PATH = 'dist/css';
            }
            class Res_S_bootstrap_table extends Dir_bootstrap_table implements StylesheetResource {
                //extensions? themes?
            }
            class Res_S_bootstrap_toggle extends Dir_bootstrap_toggle implements StylesheetResource {
                const SUB_PATH = 'css';
                //extensions? themes?
            }
            //
            //
            class Res_S_datatables extends Dir_datatables implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_buttons extends Dir_datatables_buttons implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_responsive extends Dir_datatables_responsive implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_rowgroup extends Dir_datatables_rowgroup implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_select extends Dir_datatables_select implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_dhtmlx extends Dir_dhtmlx implements StylesheetResource {
                const SUB_PATH = 'codebase';
            }
            class Res_S_dropzone extends Dir_dropzone implements StylesheetResource {
            }
            class Res_S_fontawesome extends Dir_fontawesome implements StylesheetResource {
                const SUB_PATH = 'css';
                //todo scss less?
            }
            //
            class Res_S_jquery_dragtable extends Dir_jquery_dragtable implements StylesheetResource {
            }
            class Res_S_jquery_resizable_columns extends Dir_jquery_resizable_columns implements StylesheetResource {
            }
            //
            //
            class Res_S_g7bootstrap extends Dir_js implements StylesheetResource {
                const SUB_PATH = 'bootstrap';
            }
            class Res_S_g7theme extends Dir_js implements StylesheetResource {
                //todo used?
                const SUB_PATH = 'bootstrap-theming/scss';
            }
            class Res_S_jstree extends Dir_jstree implements StylesheetResource {
                const SUB_PATH = 'themes/default';
            }
            class Res_S_jstree_dark extends Dir_jstree implements StylesheetResource {
                const SUB_PATH = 'themes/default-dark';
            }
            //
            //
            class Res_S_perfect_scrollbar extends Dir_perfect_scrollbar implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            //
            class Res_S_quilljs extends Dir_quilljs implements StylesheetResource {
            }
            class Res_S_select2 extends Dir_select2 implements StylesheetResource {
                const SUB_PATH = 'css';
            }
            class Res_S_summernote extends Dir_summernote implements StylesheetResource {
            }
            class Res_S_uppy extends Dir_uppy implements StylesheetResource {
            }
            //
        }
        {
            interface JavaScriptResource{const FILE_EXT = '.[js]';}
            class Res_J_air_datepicker extends Dir_air_datepicker implements JavaScriptResource{
            }
            class Res_J_autocomplete extends Dir_autocomplete implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap extends Dir_bootstrap implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_datepicker extends Dir_bootstrap_datepicker implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_datetimepicker extends Dir_bootstrap_datetimepicker implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            //
            class Res_J_bootstrap_select extends Dir_bootstrap_select implements JavaScriptResource{
                const SUB_PATH = 'dist/js';
            }
            class Res_J_bootstrap_table extends Dir_bootstrap_table implements JavaScriptResource{
                //extensions? themes?
            }
            class Res_J_bootstrap_toggle extends Dir_bootstrap_toggle implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_typeahead extends Dir_bootstrap_typeahead implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_currentScript_polyfill extends Dir_currentScript_polyfill implements JavaScriptResource{
            }
            class Res_J_datatables extends Dir_datatables implements JavaScriptResource{
            const SUB_PATH = 'js';
            }
            class Res_J_datatables_buttons extends Dir_datatables_buttons implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_responsive extends Dir_datatables_responsive implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_rowgroup extends Dir_datatables_rowgroup implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_select extends Dir_datatables_select implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_dhtmlx extends Dir_dhtmlx implements JavaScriptResource{
                const SUB_PATH = 'codebase';
            }
            class Res_J_dropzone extends Dir_dropzone implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_fontawesome extends Dir_fontawesome implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_jQuery extends Dir_jQuery implements JavaScriptResource{
            }
            class Res_J_jquery_dragtable extends Dir_jquery_dragtable implements JavaScriptResource{
            }
            class Res_J_jquery_resizable_columns extends Dir_jquery_resizable_columns implements JavaScriptResource{
            }
            class Res_J_jquery_tablednd extends Dir_jquery_tablednd implements JavaScriptResource{
            }
            class Res_J_jquery_tableexport extends Dir_jquery_tableexport implements JavaScriptResource{
                //libs?
            }
            //js
            class Res_J_jstree extends Dir_jstree implements JavaScriptResource{
            }
            class Res_J_moment extends Dir_moment implements JavaScriptResource{
            }
            class Res_J_moment_timezone extends Dir_moment_timezone implements JavaScriptResource{
            }
            class Res_J_perfect_scrollbar extends Dir_perfect_scrollbar implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_popper extends Dir_popper implements JavaScriptResource{
            }
            class Res_J_quilljs extends Dir_quilljs implements JavaScriptResource{
            }
            class Res_J_select2 extends Dir_select2 implements JavaScriptResource{
                const SUB_PATH = 'js';
            }
            class Res_J_summernote extends Dir_summernote implements JavaScriptResource{
            }
            class Res_J_uppy extends Dir_uppy implements JavaScriptResource{
            }
            class Res_J_zxcvbn extends Dir_zxcvbn implements JavaScriptResource{
            }


        }
    }
}