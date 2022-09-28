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
        protected static function getFileName(string $version): string
        {
            return "jquery-$version";
        }
        protected static function getPath(string $version): string
        {
            $path = parent::getPath($version).'js/jquery/'.$version;
            return \addEndingSlash($path);
        }
    }


}
namespace pool\includes\JS_File\dir{
    abstract class JS_File{
        public const VERSION = '';

        /**
         * Retrieves the
         * @param bool $min
         * @param string $version Specify the version to use defaults to self::VERSION
         * @param bool $raiseError
         * @return string
         */
        public final static function getFile(bool $min, string $version = '', bool $raiseError = true):string{
            $version_param = $version;
            //use default version unless specific version is requested
            if (!$version)
                $version = self::VERSION;
            //Request filename using the called child's implementation
            $file = get_called_class()::getPath($version) . get_called_class()::getFileName($version);
            $success = false;
            //assemble final filename and fallback to minified/non-minified version if necessary
            if ($min){
                $completeFile = $file.'.min.js';
                if (file_exists($completeFile))
                    $success = true;
                else
                    $completeFile = $file . '.js';
            }else{
                $completeFile = $file.'.js';
                if (file_exists($completeFile))
                    $success = true;
                else
                    $completeFile = $file .'.min.js';
            }
            //check result
            if ($success || file_exists($completeFile)) {
                //file found
                return $file;
            } elseif ((bool)$version_param) {
                //try default version
                return get_called_class()::getFile();
            } else {
                //handel missing file
                if($raiseError) {
                    $weblication = \Weblication::getInstance();
                    $weblication->raiseError(__FILE__, __LINE__, sprintf('JavaScript \'%s.[js/min.js]\' not found (@getFile)!', $file));
                }
                return '';
            }

        }

        /**
         * @param string $version
         * @return string the filename without the file extension
         */
        abstract protected static function getFileName(string $version): string;

        /**
         * @param string $version
         * @return string the directory where this resource is located with an ending slash
         */
        abstract protected static function getPath(string $version): string;

    }

    abstract class _3rdParty extends JS_File {
        protected static function getPath(string $version): string
        {
            return \addEndingSlash(DIR_RELATIVE_3RDPARTY_ROOT);
        }

    }


    abstract class Base_root extends JS_File {
        //  const DIR =
    }

    abstract class Scripts_root extends JS_File {
        //  const DIR =
    }

    abstract class GUIs_root extends JS_File {
        //  const DIR =
    }
}