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

use function deleteDir;

final class Ghostscript
{
    const GHOSTSCRIPT_SUPPRESS_OPTIONS = ['-dBATCH', '-dNOPAUSE', '-dNOPROMPT', '-dSAFER'];

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

    /** @noinspection PhpUnused */
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

    /** @noinspection PhpSameParameterValueInspection */
    private static function execute(array $arguments, array &$output = [], int &$resultCode = 0): bool
    {
        // suppress user interaction and run run in safe mode
        $arguments = array_merge(self::GHOSTSCRIPT_SUPPRESS_OPTIONS, $arguments);
        $escapedArguments = array_map(escapeshellarg(...), $arguments);
        $argumentsString = implode(' ', $escapedArguments);
        $command = escapeshellcmd(self::getGsBin().' '.$argumentsString);
        exec($command, $output, $resultCode);
        return $resultCode === 0;
    }

    public static function getGsBin(): string
    {
        if (!self::$gsInstalled) {
            self::$gsInstalled = is_executable(self::$gsBin);
            if (!self::$gsInstalled) {
                throw new RuntimeException('Ghostscript is not installed or not executable at '.self::$gsBin);
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
    ): array
    {
        // create unique temp directory for outputs
        $tempDir = self::getTempDir();
        $tempOutputFile = $tempDir.'/'.basename($outputFile);
        $targetDir = dirname($outputFile);

        $arguments = [
            '-sDEVICE=jpeg',
            "-r$dpi",
            "-dJPEGQ=$quality",
        ];

        // Add page area if specified
        $arguments = self::getPageArguments($pages, $arguments);
        $arguments = array_merge($arguments, self::$defaultImageOptions, $options);
        // add output and input file at the end
        array_push($arguments, '-o', $tempOutputFile, $inputFile);

        $output = [];
        if (!self::execute($arguments, $output)) {
            throw new RuntimeException('Ghostscript execution failed');
        }

        return self::handleOutputFiles($tempDir, $targetDir, $jpegPattern, self::checkImageFile(...), 'image/jpeg');
    }

    /**
     * @return string
     */
    private static function getTempDir(string $suffix = ''): string
    {
        $tempDir = sys_get_temp_dir().'/'.uniqid("pool_$suffix");
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0700, true);
        }
        if (!is_writable($tempDir)) {
            throw new FileOperationException("Could not create temp directory $tempDir");
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

    private static function handleOutputFiles(string $tempDir, string $targetDir, string $pattern, callable $checkMethod, string $expectedMimeType): array
    {
        try {
            $outputFiles = [];
            $files = glob("$tempDir/$pattern");
            if (!$files) {
                throw new FileOperationException("Could not find files matching pattern $pattern in temp directory $tempDir");
            }
            if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true)) {
                throw new FileOperationException("Could not create target directory $targetDir");
            }
            \error_clear_last();
            foreach ($files as $file) {
                if ($checkMethod($file, $expectedMimeType)) {
                    $filename = basename($file);
                    $dest = "$targetDir/$filename";
                    /** @noinspection PhpFullyQualifiedNameUsageInspection */
                    if (!\moveFile($file, $dest)) {
                        $error = \error_get_last() ?? 'Unknown error';
                        throw new FileOperationException("Could not copy file $file to target directory $targetDir with error: $error[message]");
                    }
                    $outputFiles[] = $dest;
                }
            }
            return $outputFiles;
        }
        finally {
            deleteDir($tempDir);
        }
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
    ): array
    {
        // create unique temp directory for outputs
        $tempDir = self::getTempDir();
        $tempOutputFile = $tempDir.'/'.basename($outputFile);
        $targetDir = dirname($outputFile);

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
        array_push($arguments, '-o', $tempOutputFile, $inputFile);

        $output = [];
        if (!self::execute($arguments, $output)) {
            throw new RuntimeException('Ghostscript execution failed');
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

    /**
     * Extracts Text from a pdf or ps file
     *
     * @param string $inputFile The path to the input file from which text will be extracted.
     * @param string $outputFile The path to the output file where the extracted text will be saved.
     * @param array $options Additional options to customize the text extraction process.
     * @return bool Returns true if the text extraction was successful, false otherwise.
     */
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

    public static function getTextFromPdf(string $inputFile, array $options = []): string
    {
        $tmpDir = self::getTempDir('txt_output');
        $tmpFile = tempnam($tmpDir, 'pool_txt_output');
        if ($tmpFile === false) {
            throw new FileOperationException("Could not create a temporary file in $tmpDir");
        }
        if (!chmod($tmpFile, 0600)) {
            unlink($tmpFile);
            throw new FileOperationException("Could not set permissions on temporary file in $tmpDir");
        }

        try {
            if (!self::extractText($inputFile, $tmpFile, $options)) {
                throw new RuntimeException("Text extraction failed for $inputFile");
            }

            if (!file_exists($tmpFile)) {
                throw new RuntimeException("Extraction resulted in non-existent file $tmpFile for $inputFile");
            }
            $text = file_get_contents($tmpFile);
            if ($text === false) {
                throw new RuntimeException("Could not read text from temporary file $tmpFile");
            }
            return $text;
        }
        finally {
            deleteDir($tmpDir);
        }
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