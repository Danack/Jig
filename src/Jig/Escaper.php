<?php

namespace Jig;

/*
 * This file was taken from Twig. Portions (most of it actualy) are :
 *
 * (c) 2009 Fabien Potencier
 *
 * Copyright (c) 2009-2014 by the Twig Team.
 * 
 * Some rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are
 * met:
 * 
 * * Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 * 
 *    * Redistributions in binary form must reproduce the above
 *      copyright notice, this list of conditions and the following
 *      disclaimer in the documentation and/or other materials provided
 *      with the distribution.
 * 
 *    * The names of the contributors may not be used to endorse or
 *      promote products derived from this software without specific
 *      prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 * 
 */

//TODO - does anyone actually care about anything other than UTF-8 ?

if (function_exists('mb_convert_encoding')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return mb_convert_encoding($string, $to, $from);
    }
}
elseif (function_exists('iconv')) {
    function twig_convert_encoding($string, $to, $from)
    {
        return iconv($from, $to, $string);
    }
}
else {
    throw new JigException(
        'No suitable convert encoding function (use UTF-8 as your encoding ".
        "or install the iconv or mbstring extension).'
    );
}


function _twig_escape_js_callback($matches)
{
    $char = $matches[0];

    // \xHH
    if (!isset($char[1])) {
        return '\\x'.strtoupper(substr('00'.bin2hex($char), -2));
    }

    // \uHHHH
    $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');

    return '\\u'.strtoupper(substr('0000'.bin2hex($char), -4));
}

function _twig_escape_css_callback($matches)
{
    $char = $matches[0];

    // \xHH
    if (!isset($char[1])) {
        $hex = ltrim(strtoupper(bin2hex($char)), '0');
        if (0 === strlen($hex)) {
            $hex = '0';
        }

        return '\\'.$hex.' ';
    }

    // \uHHHH
    $char = twig_convert_encoding($char, 'UTF-16BE', 'UTF-8');

    return '\\'.ltrim(strtoupper(bin2hex($char)), '0').' ';
}

/**
 * This function is adapted from code coming from Zend Framework.
 *
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
function _twig_escape_html_attr_callback($matches)
{
    /*
     * While HTML supports far more named entities, the lowest common denominator
     * has become HTML5's XML Serialisation which is restricted to the those named
     * entities that XML supports. Using HTML entities would result in this error:
     *     XML Parsing Error: undefined entity
     */
    static $entityMap = array(
        34 => 'quot', /* quotation mark */
        38 => 'amp',  /* ampersand */
        60 => 'lt',   /* less-than sign */
        62 => 'gt',   /* greater-than sign */
    );

    $chr = $matches[0];
    $ord = ord($chr);

    /**
     * The following replaces characters undefined in HTML with the
     * hex entity for the Unicode replacement character.
     */
    if (($ord <= 0x1f && $chr != "\t" && $chr != "\n" && $chr != "\r") || ($ord >= 0x7f && $ord <= 0x9f)) {
        return '&#xFFFD;';
    }

    /**
     * Check if the current character to escape has a name entity we should
     * replace it with while grabbing the hex value of the character.
     */
    if (strlen($chr) == 1) {
        $hex = strtoupper(substr('00'.bin2hex($chr), -2));
    } else {
        $chr = twig_convert_encoding($chr, 'UTF-16BE', 'UTF-8');
        $hex = strtoupper(substr('0000'.bin2hex($chr), -4));
    }

    $int = hexdec($hex);
    if (array_key_exists($int, $entityMap)) {
        return sprintf('&%s;', $entityMap[$int]);
    }

    /**
     * Per OWASP recommendations, we'll use hex entities for any other
     * characters where a named entity does not exist.
     */
    return sprintf('&#x%s;', $hex);
}

class Escaper
{

    /**
     * Escapes a string.
     *
     * @param string $string The value to be escaped
     *
     * @return string
     */
    public static function escapeHTML($string)
    {
        $charset = 'UTF-8';

        if (is_object($string) == true) {
            if (method_exists($string, '__toString') == false) {
                $message = sprintf(
                    "Object of type %s does not have a __toString method. Cannot use it as a string.",
                    get_class($string)
                );

                throw new JigException($message);
            }

            $string = (string)$string;
        }
        if (is_array($string) == true) {
            throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
        }

        // see http://php.net/htmlspecialchars

        // Using a static variable to avoid initializing the array
        // each time the function is called. Moving the declaration on the
        // top of the function slow downs other escaping strategies.
        static $htmlspecialcharsCharsets;

        if (null === $htmlspecialcharsCharsets) {
            if (defined('HHVM_VERSION')) {
                $htmlspecialcharsCharsets = array('utf-8' => true, 'UTF-8' => true);
            }
            else {
                $htmlspecialcharsCharsets = array(
                    'ISO-8859-1' => true, 'ISO8859-1' => true,
                    'ISO-8859-15' => true, 'ISO8859-15' => true,
                    'utf-8' => true, 'UTF-8' => true,
                    'CP866' => true, 'IBM866' => true, '866' => true,
                    'CP1251' => true, 'WINDOWS-1251' => true, 'WIN-1251' => true,
                    '1251' => true,
                    'CP1252' => true, 'WINDOWS-1252' => true, '1252' => true,
                    'KOI8-R' => true, 'KOI8-RU' => true, 'KOI8R' => true,
                    'BIG5' => true, '950' => true,
                    'GB2312' => true, '936' => true,
                    'BIG5-HKSCS' => true,
                    'SHIFT_JIS' => true, 'SJIS' => true, '932' => true,
                    'EUC-JP' => true, 'EUCJP' => true,
                    'ISO8859-5' => true, 'ISO-8859-5' => true, 'MACROMAN' => true,
                );
            }
        }

        if (isset($htmlspecialcharsCharsets[$charset])) {
            return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
        }

        if (isset($htmlspecialcharsCharsets[strtoupper($charset)])) {
            // cache the lowercase variant for future iterations
            $htmlspecialcharsCharsets[$charset] = true;

            return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, $charset);
        }

        $string = twig_convert_encoding($string, 'UTF-8', $charset);
        // htmlentities($string, ENT_DISALLOWED | ENT_HTML401 | ENT_NOQUOTES, 'UTF-8');
        $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return twig_convert_encoding($string, $charset, 'UTF-8');

    }

    public static function escapeJavascript($string)
    {
        if (is_object($string) == true) {
            if (method_exists($string, '__toString') == false) {
                $message = sprintf(
                    "Object of type %s does not have a __toString method. Cannot use it as a string.",
                    get_class($string)
                );

                throw new JigException($message);
            }

            $string = (string)$string;
        }
        if (is_array($string) == true) {
            throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
        }

        $charset = 'UTF-8';
        // escape all non-alphanumeric characters
        // into their \xHH or \uHHHH representations
        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, 'UTF-8', $charset);
        }

        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new JigException('The string to escape is not a valid UTF-8 string.');
        }

        $string = preg_replace_callback(
            '#[^a-zA-Z0-9,\._]#Su',
            'Jig\_twig_escape_js_callback',
            $string
        );

        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, $charset, 'UTF-8');
        }

        return $string;
    }

    public static function escapeCSS($string)
    {
        if (is_object($string) == true) {
            if (method_exists($string, '__toString') == false) {
                $message = sprintf(
                    "Object of type %s does not have a __toString method. Cannot use it as a string.",
                    get_class($string)
                );

                throw new JigException($message);
            }

            $string = (string)$string;
        }
        if (is_array($string) == true) {
            throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
        }

        $charset = 'UTF-8';
        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, 'UTF-8', $charset);
        }

        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new JigException('The string to escape is not a valid UTF-8 string.');
        }

        $string = preg_replace_callback(
            '#[^a-zA-Z0-9]#Su',
            'Jig\_twig_escape_css_callback',
            $string
        );

        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, $charset, 'UTF-8');
        }

        return $string;
    }

    public static function escapeHTMLAttribute($string)
    {
        if (is_object($string) == true) {
            if (method_exists($string, '__toString') == false) {
                $message = sprintf(
                    "Object of type %s does not have a __toString method. Cannot use it as a string.",
                    get_class($string)
                );

                throw new JigException($message);
            }

            $string = (string)$string;
        }
        if (is_array($string) == true) {
            throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
        }

        $charset = 'UTF-8';
        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, 'UTF-8', $charset);
        }

        if (0 == strlen($string) ? false : (1 == preg_match('/^./su', $string) ? false : true)) {
            throw new JigException('The string to escape is not a valid UTF-8 string.');
        }

        $string = preg_replace_callback(
            '#[^a-zA-Z0-9,\.\-_]#Su',
            'Jig\_twig_escape_html_attr_callback',
            $string
        );

        if ('UTF-8' != $charset) {
            $string = twig_convert_encoding($string, $charset, 'UTF-8');
        }

        return $string;
    }

    public static function escapeURL($string)
    {
        if (is_object($string) == true) {
            if (method_exists($string, '__toString') == false) {
                $message = sprintf(
                    "Object of type %s does not have a __toString method. Cannot use it as a string.",
                    get_class($string)
                );

                throw new JigException($message);
            }

            $string = (string)$string;
        }
        if (is_array($string) == true) {
            throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
        }
        
        return rawurlencode($string);
    }
}
