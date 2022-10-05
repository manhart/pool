<?php
/*
 * g7system.local
 *
 * JS_File.php created at 23.09.22, 15:51
 *
 * @author p.lehfeld <p.lehfeld@group-7.de>
 * @copyright Copyright (c) 2022, GROUP7 AG
 */

namespace pool\includes\JS_File\dir{

    use Weblication;
    use function addEndingSlash;
    use function readFiles;
    use function remove_extension;

    abstract class _3rdParty{
        public const DIRECTORY = '';
        public const VERSION = '';
        public const SUB_PATH = '';
        public const FILE_EXT_FILTER = '';
        public const NAME_FILTER = '';

        /**Builds a path based on the called subclasses attributes and the const DIR_RELATIVE_3RDPARTY_ROOT<br>
         * and returns one variant of each file matching the filters defined in the aforementioned attributes
         * @param bool $min Prefer minified(.min.X) variant <br> !$min => Prefer plain variant
         * @param string $version Optional override for the default A:VERSION
         * @return array the resulting file list prefixed with the assembled path
         */
        public static final function getFiles(bool $min, string $version = ''):array{
            $path = static::getPath($version,$min);
            $files = readFiles($path,false, '/'.static::NAME_FILTER.'(\.min)?\.'.static::FILE_EXT_FILTER.'$/');
            sort($files);
            $returnFiles=array();
            $minRegex = '\.min\.'.static::FILE_EXT_FILTER.'$/';
            $iMax = count($files);
            for ($i = 0; $i<$iMax;$i+=$step){
                $curFile = $files[$i];
                $nextFile = $files[$i + 1] ?? '';
                if (!$nextFile){//leftover single
                    //take shortcut and exit
                    $returnFiles[] = $path.$curFile;
                    return $returnFiles;
                }
                $hasPlainVersion = !$hasMinifiedVersion = $currIsMin = (bool)preg_match('/'.$minRegex, $curFile);
                if ($currIsMin){
                    $filename = remove_extension(remove_extension($curFile));
                    $hasPlainVersion = preg_match("/$filename\.".static::FILE_EXT_FILTER.'$/', $nextFile);
                }else{//plain first
                    $filename = remove_extension($curFile);
                    $hasMinifiedVersion = preg_match("/$filename$minRegex", $nextFile);
                }
                if ($hasMinifiedVersion xor $hasPlainVersion){
                    //no alternative
                    $returnFiles[] = $path.$curFile;
                    $step = 1;//next set
                }else{
                    //chose one alternative
                    if ($currIsMin === $min)
                        //current file matches request
                        $returnFiles[] = $path.$curFile;
                    else//use alternative
                        $returnFiles[] = $path.$nextFile;
                    $step= 2;//next set
                    }
                }
            return $returnFiles;
        }


        /**Builds a path based on the called subclasses attributes and the const DIR_RELATIVE_3RDPARTY_ROOT
         * @param string $version Optional override for the default A:VERSION
         * @param bool $min Parameter for subclasses that override this method
         * @return string the assembled path with an ending slash
         */
        protected static function getPath(string $version, bool $min): string
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
    //const VERSION default
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
            protected static function getPath(string $version,$min): string
            {
                $path = parent::getPath($version, $min);
                if ($min)
                    $path = addEndingSlash($path.'min');;
                return  $path;
            }
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
        {
            interface StylesheetResource
            {
                const DEFAULT_FILE_EXT = '(css|scss)'; //todo scss less?

            }
            class Res_S_air_datepicker extends Dir_air_datepicker implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_autocomplete extends Dir_autocomplete implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap extends Dir_bootstrap implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_datepicker extends Dir_bootstrap_datepicker implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_datetimepicker extends Dir_bootstrap_datetimepicker implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_bootstrap_icons extends Dir_bootstrap_icons implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_bootstrap_select extends Dir_bootstrap_select implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'dist/css';
            }
            class Res_S_bootstrap_table extends Dir_bootstrap_table implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                //extensions? themes?
            }
            class Res_S_bootstrap_toggle extends Dir_bootstrap_toggle implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
                //extensions? themes?
            }
            //
            //
            class Res_S_datatables extends Dir_datatables implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_buttons extends Dir_datatables_buttons implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_responsive extends Dir_datatables_responsive implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_rowgroup extends Dir_datatables_rowgroup implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_datatables_select extends Dir_datatables_select implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_dhtmlx extends Dir_dhtmlx implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'codebase';
            }
            class Res_S_dropzone extends Dir_dropzone implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_fontawesome extends Dir_fontawesome implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            //
            class Res_S_jquery_dragtable extends Dir_jquery_dragtable implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_jquery_resizable_columns extends Dir_jquery_resizable_columns implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            //
            //
            class Res_S_g7bootstrap extends Dir_js implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'bootstrap';
            }
            class Res_S_g7theme extends Dir_js implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                //todo used?
                const SUB_PATH = 'bootstrap-theming/scss';
            }
            class Res_S_jstree extends Dir_jstree implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'themes/default';
            }
            class Res_S_jstree_dark extends Dir_jstree implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'themes/default-dark';
            }
            //
            //
            class Res_S_perfect_scrollbar extends Dir_perfect_scrollbar implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            //
            class Res_S_quilljs extends Dir_quilljs implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_select2 extends Dir_select2 implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'css';
            }
            class Res_S_summernote extends Dir_summernote implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_S_uppy extends Dir_uppy implements StylesheetResource {
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            //
        }
        {
            interface JavaScriptResource
            {
                const DEFAULT_FILE_EXT = 'js';
            }
            class Res_J_air_datepicker extends Dir_air_datepicker implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_autocomplete extends Dir_autocomplete implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap extends Dir_bootstrap implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_datepicker extends Dir_bootstrap_datepicker implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_datetimepicker extends Dir_bootstrap_datetimepicker implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            //
            class Res_J_bootstrap_select extends Dir_bootstrap_select implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'dist/js';
            }
            class Res_J_bootstrap_table extends Dir_bootstrap_table implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                //extensions? themes?
            }
            class Res_J_bootstrap_toggle extends Dir_bootstrap_toggle implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_bootstrap_typeahead extends Dir_bootstrap_typeahead implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_currentScript_polyfill extends Dir_currentScript_polyfill implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_datatables extends Dir_datatables implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            const SUB_PATH = 'js';
            }
            class Res_J_datatables_buttons extends Dir_datatables_buttons implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_responsive extends Dir_datatables_responsive implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_rowgroup extends Dir_datatables_rowgroup implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_datatables_select extends Dir_datatables_select implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_dhtmlx extends Dir_dhtmlx implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'codebase';
            }
            class Res_J_dropzone extends Dir_dropzone implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_fontawesome extends Dir_fontawesome implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_jQuery extends Dir_jQuery implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_jquery_dragtable extends Dir_jquery_dragtable implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_jquery_resizable_columns extends Dir_jquery_resizable_columns implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_jquery_tablednd extends Dir_jquery_tablednd implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_jquery_tableexport extends Dir_jquery_tableexport implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                //libs?
            }
            //js
            class Res_J_jstree extends Dir_jstree implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_moment extends Dir_moment implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_moment_timezone extends Dir_moment_timezone implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_perfect_scrollbar extends Dir_perfect_scrollbar implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_popper extends Dir_popper implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_quilljs extends Dir_quilljs implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_select2 extends Dir_select2 implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
                const SUB_PATH = 'js';
            }
            class Res_J_summernote extends Dir_summernote implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_uppy extends Dir_uppy implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }
            class Res_J_zxcvbn extends Dir_zxcvbn implements JavaScriptResource{
                const FILE_EXT_FILTER = self::DEFAULT_FILE_EXT;
            }


        }
    }
}
namespace pool\includes\JS_File {

    use pool\includes\JS_File\dir\_3rdParty;


    class jQuery extends dir\Res_J_jQuery {
        const NAME_FILTER = '\d\.\d';
    }
    class jQuery_Slim extends dir\Res_J_jQuery{
        const NAME_FILTER = 'slim';
    }

}