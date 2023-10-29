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

namespace pool\includes\Resources\dir{

    use GUI_HeadData;
    use pool\includes\Resources\JavaScriptResource;
    use pool\includes\Resources\StylesheetResource;
    use function readFiles;
    use function remove_extension;

    abstract class ResourceLoader
    {
        protected const DIRECTORY = '';
        protected const VERSION = '';
        protected const SUB_PATH = '';
        protected const FILE_EXT_FILTER = '';

        public static function addResourceTo(GUI_HeadData $Head, bool $min, string $version = '', array $resource = null): int
        {
            $className = static::class;
            //try to load default if no subresource is specified
            $resource ??= (defined(static::class . '::_') ? static::_ : null);
            $nameFilter = $resource[0] ?? '';
            $extension = $resource[1] ?? '';

            if(is_subclass_of($className, JavaScriptResource::class)) {
                $items = $className::getFiles($min, $version, $nameFilter, $extension);
                foreach ($items as $item)
                    $Head->addJavaScript($item);
                return count($items);
            }

            if (is_subclass_of($className, StylesheetResource::class)) {
                $items = $className::getFiles($min, $version, $nameFilter, $extension);
                foreach ($items as $item)
                    $Head->addStyleSheet($item);
                return count($items);
            }

            //no valid Resource
            return -1;
        }

        /**Builds a path based on the called subclasses attributes<br>
         * and returns one variant of each file matching the filters defined in the aforementioned attributes
         * @param bool $min Prefer minified(.min.X) variant <br> !$min => Prefer plain variant
         * @param string $version Optional override for the default A:VERSION
         * @param string $nameFilter in Regex filter applied before extension
         * @param string $extension  in Regex filter applied after . up to end of filename
         * @return array the resulting file list prefixed with the assembled path
         */
        public static function getFiles(bool $min, string $version = '', string $nameFilter = '', string $extension = ''): array
        {
            $path = static::getSubPath($version, $min, $extension);
            $fileExtFilter = static::FILE_EXT_FILTER;
            $pattern = "/{$nameFilter}(\.min)?\.{$fileExtFilter}$/";
            //load filename list from directory with absolute path
            $files = readFiles($path, false, $pattern);
            //pick files and ad relative Path
            return self::chooseVariant($files, $min, static::FILE_EXT_FILTER, $path);
        }

        abstract protected static function getRootPath();

        /**Builds a path based on the called subclasses attributes and the const DIR_RELATIVE_3RDPARTY_ROOT
         * @param string $version Optional override for the default A:VERSION
         * @return string the assembled path with an ending slash
         */
        protected static function getPath(string $version): string
        {
            $version = $version ?: static::VERSION;
            return buildDirPath(static::getRootPath(), static::DIRECTORY, $version);
        }

        protected static function getSubPath(string $version, bool $min, string $extension): string
        {
            if ($extension === '') {
                return buildDirPath(static::getPath($version), static::SUB_PATH);
            }

            return buildDirPath(static::getPath($version), static::EXTENSION_PATH, $extension);
        }

        /**Pick minified/non-minified variants of files from a list of filenames<br>
         * It's not recommended to mix different filetypes with the same name
         * (e.g. hello.css hello.min.js will likely mess up if both extensions are being matched)
         * @param array $files list of filenames to choose from
         * @param bool $min Prefer minified(.min.X) variant <br> !$min => Prefer plain variant
         * @param string $fileExtension file extension to match (Regex compatible)
         * @param string $path the path to prefix the files with
         * @return array the resulting file list prefixed with the path
         */
        final public static function chooseVariant(array $files, bool $min, string $fileExtension, string $path = ''): array
        {
            sort($files);
            $returnFiles = array();
            $minRegex = "\.min\.{$fileExtension}$/";
            $iMax = count($files);
            for ($i = 0; $i < $iMax; $i += $step) {
                $curFile = $files[$i];
                $nextFile = $files[$i + 1] ?? '';
                if (!$nextFile) {//leftover single
                    //take shortcut and exit
                    $returnFiles[] = $path . $curFile;
                    return $returnFiles;
                }
                $hasPlainVersion = !$hasMinifiedVersion = $currIsMin = (bool)preg_match("/{$minRegex}", $curFile);
                if ($currIsMin) {
                    $filename = remove_extension(remove_extension($curFile));
                    $hasPlainVersion = preg_match("/{$filename}\.{$fileExtension}$/", $nextFile);
                } else {//plain first
                    $filename = remove_extension($curFile);
                    $hasMinifiedVersion = preg_match("/{$filename}{$minRegex}", $nextFile);
                }
                if ($hasMinifiedVersion xor $hasPlainVersion) {
                    //no alternative
                    $returnFiles[] = $path . $curFile;
                    $step = 1;//next set
                } else {
                    //chose one alternative
                    if ($currIsMin === $min)
                        //current file matches request
                        $returnFiles[] = $path . $curFile;
                    else//use alternative
                        $returnFiles[] = $path . $nextFile;
                    $step = 2;//next set
                }
            }
            return $returnFiles;
        }
    }
}
namespace pool\includes\Resources {
    interface JavaScriptResource
    {
        public const DEFAULT_FILE_EXT = 'js';
    }
    interface StylesheetResource
    {
        public const DEFAULT_FILE_EXT = '(css|scss)'; //todo scss less?
    }
}