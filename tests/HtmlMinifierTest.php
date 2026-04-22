<?php
/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);

namespace pool\tests;

use PHPUnit\Framework\TestCase;
use pool\classes\Core\Weblication;
use pool\utils\HtmlMinifier;

class HtmlMinifierTest extends TestCase
{
    public function testOffReturnsInputUnchanged(): void
    {
        $html = "<div>\n  <p>hi</p>\n</div>";
        self::assertSame($html, HtmlMinifier::minify($html, Weblication::MINIFY_OFF));
    }

    public function testEmptyInput(): void
    {
        self::assertSame('', HtmlMinifier::minify('', Weblication::MINIFY_LEAN));
    }

    public function testCollapsesIndentationBetweenBlockTags(): void
    {
        $in = "<div>\n    <p>hi</p>\n</div>";
        $out = HtmlMinifier::minify($in, Weblication::MINIFY_LEAN);
        self::assertSame('<div> <p>hi</p> </div>', $out);
    }

    public function testPreservesInlineSpacing(): void
    {
        // single space without newline between inline elements must survive
        $in = '<a>foo</a> <a>bar</a>';
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testPreservesPreContent(): void
    {
        $in = "<pre>\n  line1\n  line2\n</pre>";
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testPreservesTextareaContent(): void
    {
        $in = "<textarea>\n  keep\n  me\n</textarea>";
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testPreservesScriptContent(): void
    {
        $in = "<script>\n  var a = 1;\n  var b = 2;\n</script>";
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testPreservesStyleContent(): void
    {
        $in = "<style>\n  .a { color: red; }\n</style>";
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testAttributesAreNotTouched(): void
    {
        $in = "<a\n  href=\"x\"\n  title=\"y\">z</a>";
        $out = HtmlMinifier::minify($in, Weblication::MINIFY_LEAN);
        // attribute whitespace inside the tag must be preserved byte-for-byte
        self::assertStringContainsString("<a\n  href=\"x\"\n  title=\"y\">", $out);
    }

    public function testLeanKeepsCommentsByDefault(): void
    {
        $in = "<div>\n<!-- keep me -->\n</div>";
        self::assertStringContainsString('<!-- keep me -->', HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testFullDropsComments(): void
    {
        $in = "<div>\n<!-- drop me -->\n</div>";
        $out = HtmlMinifier::minify($in, Weblication::MINIFY_FULL);
        self::assertStringNotContainsString('drop me', $out);
    }

    public function testCdataIsPreserved(): void
    {
        $in = "<svg><![CDATA[\n  keep this\n  too\n]]></svg>";
        self::assertSame($in, HtmlMinifier::minify($in, Weblication::MINIFY_LEAN));
    }

    public function testFullPageRoundtrip(): void
    {
        $in = "<!doctype html>\n<html>\n  <head>\n    <title>t</title>\n  </head>\n  <body>\n    <p>hi</p>\n  </body>\n</html>";
        $out = HtmlMinifier::minify($in, Weblication::MINIFY_LEAN);
        // sanity: output shorter, still starts with doctype, still contains body text
        self::assertLessThan(strlen($in), strlen($out));
        self::assertStringStartsWith('<!doctype html>', $out);
        self::assertStringContainsString('<p>hi</p>', $out);
    }
}
