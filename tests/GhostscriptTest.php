<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PHPUnit\Framework\TestCase;
use pool\utils\Ghostscript;


class GhostscriptTest extends TestCase
{
    private static string $testPdf = __DIR__.'/_data/test-pdf-one-page.pdf';

    private static string $testPdfMorePages = __DIR__.'/_data/test-pdf-more-pages.pdf';

    private static string $outputPng = __DIR__.'/_output/generated_files/pdf2png.png';

    private static string $outputJpeg = __DIR__.'/_output/generated_files/pdf2jpg.jpg';

    private static string $mergedPdf = __DIR__.'/_output/generated_files/merged_pdfs.pdf';

    private static string $extractedText = __DIR__.'/_output/generated_files/extracted_text.txt';

    public static function setUpBeforeClass(): void
    {
        @unlink(self::$outputPng);
        @unlink(self::$outputJpeg);
        @unlink(self::$mergedPdf);
        @unlink(self::$extractedText);
    }

    public function testPdfToJpeg()
    {
        $result = Ghostscript::pdfToJpeg(self::$testPdf, self::$outputJpeg);
        $this->assertEquals(1, $result, 'PDF to JPEG conversion failed');
        $this->assertFileExists(self::$outputJpeg, 'Output JPEG file does not exist');
        $this->assertEquals('image/jpeg', mime_content_type(self::$outputJpeg), 'Output file is not a valid JPEG');
    }

    public function testPdfToPng()
    {
        $result = Ghostscript::pdfToPng(self::$testPdf, self::$outputPng);
        // assert result if result is false
        $this->assertEquals(1, $result, 'PDF to PNG conversion failed');
        $this->assertFileExists(self::$outputPng, 'Output PNG file does not exist');
        $this->assertEquals('image/png', mime_content_type(self::$outputPng), 'Output file is not a valid PNG');
    }

    public function testMergePdfs()
    {
        $additionalPdf = self::$testPdfMorePages;
        $result = Ghostscript::mergePdfs([self::$testPdf, $additionalPdf], self::$mergedPdf);
        $this->assertTrue($result, 'PDF merge failed');
        $this->assertFileExists(self::$mergedPdf, 'Merged PDF file does not exist');
    }

    public function testExtractText()
    {
        $result = Ghostscript::extractText(self::$testPdf, self::$extractedText);
        $this->assertTrue($result, 'Text extraction failed');
        $this->assertFileExists(self::$extractedText, 'Extracted text file does not exist');
        $this->assertGreaterThan(0, filesize(self::$extractedText), 'Extracted text file is empty');
    }

    protected function tearDown(): void
    {
        //  @unlink(self::$outputPng);
        //  @unlink(self::$outputJpeg);
        //  @unlink(self::$mergedPdf);
        //  @unlink(self::$extractedText);
    }
}