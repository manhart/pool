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

namespace pool\utils;

use pool\classes\Exception\FileOperationException;
use pool\classes\Exception\RuntimeException;

/**
 * Depending on the method, popper-utils package is required.
 */
final class Pdf
{
    static private bool $isPdf2txtAvailable;

    public static function toTextFile(string $pdfFile, ?string $txtFile = null, ...$options): string|false
    {
        self::$isPdf2txtAvailable ??= (exec('pdftotext -v >/dev/null 2>&1', result_code: $resultCode) !== false && $resultCode === 0);
        if (!self::$isPdf2txtAvailable) {
            throw new RuntimeException('pdftotext is not available on the system.');
        }
        if (!$txtFile) {
            $sysTempDir = sys_get_temp_dir();
            $txtFile = tempnam($sysTempDir, 'pdftotext_');
            if ($txtFile === false) throw new FileOperationException('Cannot create temporary file');
            if (!chmod($txtFile, 0600)) {
                unlink($txtFile);
                throw new FileOperationException("Could not set permissions on temporary file in $sysTempDir");
            }
        }

        $options[] = $pdfFile;
        $options[] = $txtFile;
        $arguments = implode(' ', array_map(escapeshellarg(...), $options));
        $cmd = escapeshellcmd("pdftotext $arguments");
        exec($cmd, result_code: $resultCode);
        if ($resultCode) match ($resultCode) {
            1 => throw new RuntimeException('Error opening PDF file'),
            2 => throw new RuntimeException('Error opening output file'),
            3 => throw new RuntimeException('Error related to PDF permissions'),
            default => throw new RuntimeException('Unknown error')
        };
        return $resultCode === 0 && file_exists($txtFile) ? $txtFile : false;
    }

    /**
     * Extracts text from a PDF file.
     *
     * @param string $pdfFile The path to the PDF file.
     * @param bool $layout Maintain (as best as possible) the original physical layout of the text.  The default is to ´undo' physical layout (columns, hyphenation, etc.) and
     *     output the text in reading order.
     * @param bool $cropbox Use the crop box rather than the media box with -bbox and -bbox-layout.
     * @param string $eol Sets the end-of-line convention to use for text output.
     * @param mixed ...$options Additional arguments for pdftotext command
     * @return string The extracted text from the PDF file.
     */
    public static function getText(string $pdfFile, bool $layout = true, bool $cropbox = true, string $eol = 'unix', ...$options): string
    {
        if ($layout) $options[] = '-layout';
        if ($cropbox) $options[] = '-cropbox';
        if ($eol) {
            $options[] = '-eol';
            $options[] = $eol;
        }
        if (!($txtFile = self::toTextFile($pdfFile, null, ...$options))) {
            throw new RuntimeException('pdftotext failed');
        }
        $text = file_get_contents($txtFile);
        if ($text === false) {
            throw new RuntimeException("Could not read text from temporary file $txtFile");
        }
        unlink($txtFile);
        return $text;
    }

    public static function isValid(string $filePath): bool
    {
        static $isPdfInfoAvailable = null;

        if ($isPdfInfoAvailable === null) {
            exec('command -v pdfinfo >/dev/null 2>&1', $output, $exitCode);
            $isPdfInfoAvailable = ($exitCode === 0);
        }

        if (!$isPdfInfoAvailable) {
            throw new RuntimeException('pdfinfo is not available on the system.');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            return false;
        }

        $escapedFilePath = escapeshellarg($filePath);
        $command = "pdfinfo $escapedFilePath 2>&1";
        exec($command, $output, $exitCode);

        return ($exitCode === 0);
    }
}