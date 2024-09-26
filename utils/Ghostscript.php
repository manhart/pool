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

final class Ghostscript
{
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
        $arguments = array_merge(['-dBATCH', '-dNOPAUSE', '-dNOPROMPT', '-dSAFER'], $arguments);
        $cmd = self::getGsBin().' '.implode(' ', array_map(escapeshellarg(...), $arguments));
        $command = escapeshellcmd($cmd);
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

    public static function pdfToJpeg(string $inputFile, string $outputFile, int $dpi = 300, int $quality = 75, string $pages = '', array $options = []): bool
    {
        $arguments = [
            '-sDEVICE=jpeg',
            "-r$dpi",
            "-dJPEGQ=$quality",
            '-o',
            $outputFile,
            $inputFile,
        ];

        // Add page area if specified
        $arguments = self::getPageArguments($pages, $arguments);

        $arguments = array_merge($arguments, self::$defaultImageOptions, $options);
        return self::execute($arguments) && self::checkJpeg($outputFile);
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

    private static function checkJpeg(string $outputFile): bool
    {
        return file_exists($outputFile) && mime_content_type($outputFile) === 'image/jpeg';
    }

    public static function pdfToPng(
        string $inputFile,
        string $outputFile,
        int $dpi = 300,
        string $colorMode = 'color', // color, gray, mono, transparent
        string $pages = '',
        array $options = [],
    ): bool {
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
            '-o',
            $outputFile,
            $inputFile,
        ];

        $arguments = self::getPageArguments($pages, $arguments);

        $arguments = array_merge($arguments, self::$defaultImageOptions, $options);
        return self::execute($arguments) && self::checkPng($outputFile);
    }

    private static function checkPng(string $outputFile): bool
    {
        return file_exists($outputFile) && mime_content_type($outputFile) === 'image/png';
    }
}