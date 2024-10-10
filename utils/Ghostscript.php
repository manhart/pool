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
use pool\classes\Exception\InvalidArgumentException;
use pool\classes\Exception\RuntimeException;

final class Ghostscript
{
    const GHOSTSCRIPT_SUPRESS_OPTIONS = ['-dBATCH', '-dNOPAUSE', '-dNOPROMPT', '-dSAFER'];

    /**
     * @var string $gsBin ghostscript path to the executable
     */
    private static string $gsBin = '/usr/bin/gs';

    private static bool $gsInstalled = false;

    private static array $defaultImageOptions = [
        '-dTextAlphaBits=4',
        '-dGraphicsAlphaBits=4',
        '-dMaxBitmap=500000000',
        '-dAlignToPixels=0',
        '-dGridFitTT=2',
        '-dUseCropBox',
    ];

    public static function pdfToPs(string $inputFile, string $outputFile, array $options = []): bool
    {
        $arguments = array_merge(
            [
                '-sDEVICE=ps2write',
                '-o',
                $outputFile,
                $inputFile,
            ],
            $options,
        );
        return self::execute($arguments);
    }

    private static function execute(array $arguments, array &$output = [], int &$return = 0): bool
    {
        // suppress user interaction and run run in safe mode
        $arguments = array_merge(self::GHOSTSCRIPT_SUPRESS_OPTIONS, $arguments);
        $escapedArguments = array_map(escapeshellarg(...), $arguments);
        $argumentsString = implode(' ', $escapedArguments);
        $command = escapeshellcmd(self::getGsBin().' '.$argumentsString);
        exec($command, $output, $return);
        return $return === 0;
    }

    public static function getGsBin(): string
    {
        if (!self::$gsInstalled) {
            self::$gsInstalled = is_executable(self::$gsBin);
            if (!self::$gsInstalled) {
                throw new \RuntimeException('Ghostscript is not installed or not executable at '.self::$gsBin);
            }
        }
        return self::$gsBin;
    }

    public static function setGsBin(string $gsBin): void
    {
        self::$gsBin = $gsBin;
        self::$gsInstalled = false;
    }

    public static function mergePdfs(array $inputFiles, string $outputFile, array $options = []): bool
    {
        $arguments = array_merge(
            [
                '-sDEVICE=pdfwrite',
                '-sOUTPUTFILE='.$outputFile,
            ],
            $inputFiles,
            $options,
        );
        return self::execute($arguments);
    }

    public static function pdfToJpeg(
        string $inputFile,
        string $outputFile,
        int $dpi = 300,
        int $quality = 75,
        string $pages = '',
        array $options = [],
        string $jpegPattern = '*.jpg',
    ): false|int {
        // create unique temp directory for outputs
        $tempDir = self::getTempDir();
        $targetDir = dirname($outputFile);
        $outputFile = $tempDir.'/'.basename($outputFile);

        $arguments = [
            '-sDEVICE=jpeg',
            "-r$dpi",
            "-dJPEGQ=$quality",
        ];

        // Add page area if specified
        $arguments = self::getPageArguments($pages, $arguments);
        $arguments = array_merge($arguments, self::$defaultImageOptions, $options);
        // add output and input file at the end
        array_push($arguments, '-o', $outputFile, $inputFile);

        $output = [];
        if (!self::execute($arguments, $output)) {
            return false;
        }

        return self::handleOutputFiles($tempDir, $targetDir, $jpegPattern, self::checkImageFile(...), 'image/jpeg');
    }

    /**
     * @return string
     */
    private static function getTempDir(): string
    {
        $tempDir = sys_get_temp_dir().'/'.uniqid('pool_');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }
        return $tempDir;
    }

    /**
     * @param string $pages
     * @param array $arguments
     * @return array
     */
    private static function getPageArguments(string $pages, array $arguments): array
    {
        if (!empty($pages)) {
            $range = explode('-', $pages);
            $arguments[] = '-dFirstPage='.$range[0];
            $arguments[] = '-dLastPage='.($range[1] ?? $range[0]);
        }
        return $arguments;
    }

    private static function handleOutputFiles(string $tempDir, string $targetDir, string $pattern, callable $checkMethod, string $expectedMimeType): int
    {
        $pageCount = 0;
        foreach (glob("$tempDir/$pattern") as $file) {
            if ($checkMethod($file, $expectedMimeType)) {
                $pageCount++;
                $filename = basename($file);
                if (!move_file($file, "$targetDir/$filename")) {
                    deleteDir($tempDir);
                    throw new FileOperationException("Could not copy file $file to target directory $targetDir");
                }
            }
        }
        if (!rmdir($tempDir)) {
            throw new FileOperationException("Could not remove temp directory $tempDir");
        }
        return $pageCount;
    }

    /**
     * @param string $colorMode color mode: color, gray, mono, transparent
     */
    public static function pdfToPng(
        string $inputFile,
        string $outputFile,
        int $dpi = 300,
        string $colorMode = 'color',
        string $pages = '',
        array $options = [],
        string $pngPattern = '*.png',
    ): false|int {
        // create unique temp directory for outputs
        $tempDir = self::getTempDir();
        $targetDir = dirname($outputFile);
        $outputFile = $tempDir.'/'.basename($outputFile);

        /* pngalpha = 32-bit RGBA color with transparency indicating pixel coverage. The background is transparent unless it has been explicitly filled.
           PDF 1.4 transparent files do not give a transparent background with this device. Text and graphics anti-aliasing are enabled by default. */
        // set device according to color mode
        $device = match ($colorMode) {
            'gray' => 'pnggray',
            'mono' => 'pngmono',
            'transparent' => 'pngalpha',
            default => 'png16m',/*24-bit RGB color*/
        };

        $arguments = [
            "-sDEVICE=$device",
            "-r$dpi",
        ];
        // add page area if specified
        $arguments = self::getPageArguments($pages, $arguments);
        $arguments = array_merge($arguments, self::$defaultImageOptions, $options);
        // add output and input file at the end
        array_push($arguments, '-o', $outputFile, $inputFile);

        $output = [];
        if (!self::execute($arguments, $output)) {
            return false;
        }

        return self::handleOutputFiles($tempDir, $targetDir, $pngPattern, self::checkImageFile(...), 'image/png');
    }

    /**
     * Checks if the provided PDF file is a scanned PDF by extracting text content.
     *
     * @param string $pdfFile The path to the PDF file to be checked.
     * @return bool True if the PDF is scanned (i.e., contains no text content), false otherwise.
     * @throws InvalidArgumentException If the file does not exist.
     * @throws RuntimeException If text extraction fails.
     */
    public static function isScannedPDF(string $pdfFile): bool
    {
        if (!file_exists($pdfFile)) {
            throw new InvalidArgumentException("File not found: $pdfFile");
        }

        $textFile = tempnam(sys_get_temp_dir(), 'pool_scanned_pdf_');
        self::extractText($pdfFile, $textFile);
        if (!file_exists($textFile)) {
            throw new RuntimeException("Could not extract text from scanned PDF $pdfFile");
        }
        $text = file_get_contents($textFile);
        unlink($textFile);

        return empty($text);
    }

    public static function extractText(string $inputFile, string $outputFile, array $options = []): bool
    {
        $arguments = array_merge(
            [
                '-sDEVICE=txtwrite',
                '-o',
                $outputFile,
                $inputFile,
            ],
            $options,
        );
        return self::execute($arguments);
    }

    /**
     * @param string $outputFile
     * @param string $expectedMimeType
     * @return bool
     */
    private static function checkImageFile(string $outputFile, string $expectedMimeType): bool
    {
        return file_exists($outputFile) && mime_content_type($outputFile) === $expectedMimeType;
    }
}