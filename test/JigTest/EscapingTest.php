<?php

namespace JigTest;

use Jig\Escaper;

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
        foreach ($this->htmlSpecialChars as $key => $value) {
            $value = Escaper::escapeHTML($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testHtmlAttributeEscapingConvertsSpecialChars()
    {
        foreach ($this->htmlAttrSpecialChars as $key => $value) {
            $value = Escaper::escapeHTMLAttribute($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingConvertsSpecialChars()
    {
        foreach ($this->jsSpecialChars as $key => $value) {
            $value = Escaper::escapeJavascript($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testJavascriptEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', $value = Escaper::escapeJavascript(''));
    }

    public function testJavascriptEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', $value = Escaper::escapeJavascript('123'), 'js');
    }

    public function testCssEscapingConvertsSpecialChars()
    {
        foreach ($this->cssSpecialChars as $key => $value) {
            $value = Escaper::escapeCSS($key);
            $this->assertEquals($value, $value, 'Failed to escape: '.$key);
        }
    }

    public function testCssEscapingReturnsStringIfZeroLength()
    {
        $this->assertEquals('', Escaper::escapeCSS(''));
    }

    public function testCssEscapingReturnsStringIfContainsOnlyDigits()
    {
        $this->assertEquals('123', Escaper::escapeCSS('123'));
    }

    public function testUrlEscapingConvertsSpecialChars()
    {
        foreach ($this->urlSpecialChars as $key => $value) {
            $value = Escaper::escapeURL($key);
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
        $immune = array(',', '.', '_'); // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = Escaper::escapeJavascript($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune)) {
                    $value = Escaper::escapeJavascript($literal);
                    $this->assertEquals($literal, $value);
                } else {
                    $this->assertNotEquals(
                        $literal,
                        Escaper::escapeJavascript($literal),
                        "$literal should be escaped!"
                    );
                }
            }
        }
    }

    public function testHtmlAttributeEscapingEscapesOwaspRecommendedRanges()
    {
        $immune = array(',', '.', '-', '_'); // Exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = Escaper::escapeHTMLAttribute($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                if (in_array($literal, $immune)) {
                    $value = Escaper::escapeHTMLAttribute($literal);
                    $this->assertEquals($literal, $value);
                } else {
                    $value = Escaper::escapeHTMLAttribute($literal);
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
        // CSS has no exceptions to escaping ranges
        for ($chr = 0; $chr < 0xFF; ++$chr) {
            if ($chr >= 0x30 && $chr <= 0x39
            || $chr >= 0x41 && $chr <= 0x5A
            || $chr >= 0x61 && $chr <= 0x7A) {
                $literal = $this->codepointToUtf8($chr);
                $value = Escaper::escapeCSS($literal);
                $this->assertEquals($literal, $value);
            } else {
                $literal = $this->codepointToUtf8($chr);
                $value = Escaper::escapeCSS($literal);
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
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeJavascript(new \StdClass);
    }
    
    public function testEscapeJavascriptFailArray()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeJavascript(['Hello world']);
    }
    
    public function testEscapeJavascriptObjectToString()
    {
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = Escaper::escapeJavascript($obj);
        $this->assertEquals($string, $result);
    }

    
    public function testEscapeHTMLObjectToString()
    {
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = Escaper::escapeHTML($obj);
        $this->assertEquals($string, $result);
    }
    
    
    public function testEscapeCSSFailObject()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeCSS(new \StdClass);
    }
    
    public function testEscapeCSSFailArray()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeCSS(['Hello world']);
    }
    
    public function testEscapeCSSObjectToString()
    {
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = Escaper::escapeCSS($obj);
        $this->assertEquals($string, $result);
    }

    public function testEscapeHTMLAttrFailObject()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeHTMLAttribute(new \StdClass);
    }
    
    public function testEscapeHTMLAttrFailArray()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeHTMLAttribute(['Hello world']);
    }
    
    public function testEscapeHTMLAttrObjectToString()
    {
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = Escaper::escapeHTMLAttribute($obj);
        $this->assertEquals($string, $result);
    }
    
    public function testEscapeURLFailObject()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeURL(new \StdClass);
    }
    
    public function testEscapeURLFailArray()
    {
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\JigException::FAILED_TO_RENDER
        );

        Escaper::escapeURL(['Hello world']);
    }
    
    public function testEscapeURLObjectToString()
    {
        $string = 'Foobar';
        $obj = new \JigTest\PlaceHolder\ObjectWithToString($string);
        $result = Escaper::escapeURL($obj);
        $this->assertEquals($string, $result);
    }
}
