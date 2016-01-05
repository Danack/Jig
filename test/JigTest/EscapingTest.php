<?php

namespace JigTest;

use Jig\Escaper;
use Zend\Escaper\Escaper as ZendEscaper;
use Jig\Bridge\ZendEscaperBridge;

/**
 * This class is adapted from code coming from Twig.
 *
 * This class is adapted from code coming from Zend Framework.
 *
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 *
 * @group security
 */
class EscapingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * All character encodings supported by htmlspecialchars().
     */
    protected $htmlSpecialChars = array(
        '\'' => '&#039;',
        '"' => '&quot;',
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
    );

    protected $htmlAttrSpecialChars = array(
        '\'' => '&#x27;',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '&#x0100;',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '-' => '-',
        '_' => '_',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '&#x0D;',
        "\n" => '&#x0A;',
        "\t" => '&#x09;',
        "\0" => '&#xFFFD;', // should use Unicode replacement char
        /* Encode chars as named entities where possible */
        '<' => '&lt;',
        '>' => '&gt;',
        '&' => '&amp;',
        '"' => '&quot;',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '&#x20;',
    );

    protected $jsSpecialChars = array(
        /* HTML special chars - escape without exception to hex */
        '<' => '\\x3C',
        '>' => '\\x3E',
        '\'' => '\\x27',
        '"' => '\\x22',
        '&' => '\\x26',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\u0100',
        /* Immune chars excluded */
        ',' => ',',
        '.' => '.',
        '_' => '_',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\\x0D',
        "\n" => '\\x0A',
        "\t" => '\\x09',
        "\0" => '\\x00',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\x20',
    );

    protected $urlSpecialChars = array(
        /* HTML special chars - escape without exception to percent encoding */
        '<' => '%3C',
        '>' => '%3E',
        '\'' => '%27',
        '"' => '%22',
        '&' => '%26',
        /* Characters beyond ASCII value 255 to hex sequence */
        'Ā' => '%C4%80',
        /* Punctuation and unreserved check */
        ',' => '%2C',
        '.' => '.',
        '_' => '_',
        '-' => '-',
        ':' => '%3A',
        ';' => '%3B',
        '!' => '%21',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '%0D',
        "\n" => '%0A',
        "\t" => '%09',
        "\0" => '%00',
        /* PHP quirks from the past */
        ' ' => '%20',
        '~' => '~',
        '+' => '%2B',
    );

    protected $cssSpecialChars = array(
        /* HTML special chars - escape without exception to hex */
        '<' => '\\3C ',
        '>' => '\\3E ',
        '\'' => '\\27 ',
        '"' => '\\22 ',
        '&' => '\\26 ',
        /* Characters beyond ASCII value 255 to unicode escape */
        'Ā' => '\\100 ',
        /* Immune chars excluded */
        ',' => '\\2C ',
        '.' => '\\2E ',
        '_' => '\\5F ',
        /* Basic alnums excluded */
        'a' => 'a',
        'A' => 'A',
        'z' => 'z',
        'Z' => 'Z',
        '0' => '0',
        '9' => '9',
        /* Basic control characters and null */
        "\r" => '\\D ',
        "\n" => '\\A ',
        "\t" => '\\9 ',
        "\0" => '\\0 ',
        /* Encode spaces for quoteless attribute protection */
        ' ' => '\\20 ',
    );


    public function testHtmlEscapingConvertsSpecialChars()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        foreach ($this->htmlSpecialChars as $key => $value) {
            $value = $escaper->escapeHTML($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testHtmlAttributeEscapingConvertsSpecialChars()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        foreach ($this->htmlAttrSpecialChars as $key => $value) {
            $value = $escaper->escapeHTMLAttribute($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingConvertsSpecialChars()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        foreach ($this->jsSpecialChars as $key => $value) {
            $value = $escaper->escapeJavascript($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingReturnsStringIfZeroLength()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->assertEquals('', $value = $escaper->escapeJavascript(''));
    }

    public function testJavascriptEscapingReturnsStringIfContainsOnlyDigits()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->assertEquals('123', $value = $escaper->escapeJavascript('123'), 'js');
    }

    public function testCssEscapingConvertsSpecialChars()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        foreach ($this->cssSpecialChars as $key => $value) {
            $value = $escaper->escapeCSS($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testCssEscapingReturnsStringIfZeroLength()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->assertEquals('', $escaper->escapeCSS(''));
    }

    public function testCssEscapingReturnsStringIfContainsOnlyDigits()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->assertEquals('123', $escaper->escapeCSS('123'));
    }

    public function testUrlEscapingConvertsSpecialChars()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        foreach ($this->urlSpecialChars as $key => $value) {
            $value = $escaper->escapeURLComponent($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    /**
     * Range tests to confirm escaped range of characters is within OWASP recommendation.
     */

    /**
     * Only testing the first few 2 ranges on this prot. function as that's all these
     * other range tests require.
     */
    public function testUnicodeCodepointConversionToUtf8()
    {
        $expected = ' ~ޙ';
        $codepoints = array(0x20, 0x7e, 0x799);
        $result = '';
        foreach ($codepoints as $value) {
            $result .= $this->codepointToUtf8($value);
        }
        $this->assertEquals($expected, $result);
    }

    /**
     * Convert a Unicode Codepoint to a literal UTF-8 character.
     *
     * @param int $codepoint Unicode codepoint in hex notation
     *
     * @return string UTF-8 literal string
     */
    protected function codepointToUtf8($codepoint)
    {
        if ($codepoint < 0x80) {
            return chr($codepoint);
        }
        if ($codepoint < 0x800) {
            return chr($codepoint >> 6 & 0x3f | 0xc0)
                .chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x10000) {
            return chr($codepoint >> 12 & 0x0f | 0xe0)
                .chr($codepoint >> 6 & 0x3f | 0x80)
                .chr($codepoint & 0x3f | 0x80);
        }
        if ($codepoint < 0x110000) {
            return chr($codepoint >> 18 & 0x07 | 0xf0)
                .chr($codepoint >> 12 & 0x3f | 0x80)
                .chr($codepoint >> 6 & 0x3f | 0x80)
                .chr($codepoint & 0x3f | 0x80);
        }
        throw new \Exception('Codepoint requested outside of Unicode range');
    }

    public function testJavascriptEscapingEscapesOwaspRecommendedRanges()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $immune = array(',', '.', '_'); // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = $escaper->escapeJavascript($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune) === true) {
                    $value = $escaper->escapeJavascript($literal);
                    $this->assertEquals($literal, $value);
                } else {
                    $this->assertNotEquals(
                        $literal,
                        $escaper->escapeJavascript($literal),
                        "$literal should be escaped!"
                    );
                }
            }
        }
    }

    public function testHtmlAttributeEscapingEscapesOwaspRecommendedRanges()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $immune = array(',', '.', '-', '_'); // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = $escaper->escapeHTMLAttribute($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune) === true) {
                    $value = $escaper->escapeHTMLAttribute($literal);
                    $this->assertEquals($literal, $value);
                } else {
                    $value = $escaper->escapeHTMLAttribute($literal);
                    $this->assertNotEquals(
                        $literal,
                        $value,
                        "$literal should be escaped!"
                    );
                }
            }
        }
    }

    public function testCssEscapingEscapesOwaspRecommendedRanges()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        // CSS has no exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = $escaper->escapeCSS($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                $value = $escaper->escapeCSS($literal);
                $this->assertNotEquals(
                    $literal,
                    $value,
                    "$literal should be escaped!"
                );
            }
        }
    }
    
    
    public function testEscapeJavascriptFailObject()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_OBJECT_NOT_STRING
        );

        $escaper->escapeJavascript(new \StdClass);
    }
    
    public function testEscapeJavascriptFailArray()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_ARRAY_NOT_STRING
        );

        $escaper->escapeJavascript(['Hello world']);
    }
    
    public function testEscapeJavascriptObjectToString()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = $escaper->escapeJavascript($obj);
        $this->assertEquals($string, $result);
    }

    
    public function testEscapeHTMLObjectToString()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = $escaper->escapeHTML($obj);
        $this->assertEquals($string, $result);
    }
    
    
    public function testEscapeCSSFailObject()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_OBJECT_NOT_STRING
        );

        $escaper->escapeCSS(new \StdClass);
    }
    
    public function testEscapeCSSFailArray()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_ARRAY_NOT_STRING
        );

        $escaper->escapeCSS(['Hello world']);
    }
    
    public function testEscapeCSSObjectToString()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = $escaper->escapeCSS($obj);
        $this->assertEquals($string, $result);
    }

    public function testEscapeHTMLAttrFailObject()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_OBJECT_NOT_STRING
        );

        $escaper->escapeHTMLAttribute(new \StdClass);
    }
    
    public function testEscapeHTMLAttrFailArray()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_ARRAY_NOT_STRING
        );

        $escaper->escapeHTMLAttribute(['Hello world']);
    }
    
    public function testEscapeHTMLAttrObjectToString()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = $escaper->escapeHTMLAttribute($obj);
        $this->assertEquals($string, $result);
    }
    
    public function testEscapeURLFailObject()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_OBJECT_NOT_STRING
        );

        $escaper->escapeURLComponent(new \StdClass);
    }
    
    public function testEscapeURLFailArray()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->setExpectedException(
            'Jig\EscapeException',
            \Jig\EscapeException::E_ARRAY_NOT_STRING
        );

        $escaper->escapeURLComponent(['Hello world']);
    }
    
    public function testEscapeURLObjectToString()
    {
        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = $escaper->escapeURLComponent($obj);
        $this->assertEquals($string, $result);
    }
}
