<?php

namespace Jig\Bridge;

use Jig\Escaper;
use Zend\Escaper\Escaper as ZendEscape;
use Jig\EscapeException;

class ZendEscaperBridge implements Escaper
{
    /** @var ZendEscape */
    private $zendEscape;
    
    public function __construct(ZendEscape $zendEscape)
    {
        $this->zendEscape = $zendEscape;
    }
    
    public function escapeHTML($string)
    {
        if (is_object($string) === true) {
            if (method_exists($string, '__toString') === false) {
                throw EscapeException::fromBadObject($string);
            }
            $string = (string)$string;
        }
        if (is_array($string) === true) {
            throw EscapeException::fromBadArray();
        }
        $string = (string)$string;

        return $this->zendEscape->escapeHtml($string);
    }

    public function escapeHTMLAttribute($string)
    {
        if (is_object($string) === true) {
            if (method_exists($string, '__toString') === false) {
                throw EscapeException::fromBadObject($string);
            }
            $string = (string)$string;
        }
        if (is_array($string) === true) {
            throw EscapeException::fromBadArray();
        }
        $string = (string)$string;

        return $this->zendEscape->escapeHtmlAttr($string);
    }

    public function escapeJavascript($string)
    {
        if (is_object($string) === true) {
            if (method_exists($string, '__toString') === false) {
                throw EscapeException::fromBadObject($string);
            }
            $string = (string)$string;
        }
        if (is_array($string) === true) {
            throw EscapeException::fromBadArray();
        }
        $string = (string)$string;

        return $this->zendEscape->escapeJs($string);
    }

    public function escapeCSS($string)
    {
        if (is_object($string) === true) {
            if (method_exists($string, '__toString') === false) {
                throw EscapeException::fromBadObject($string);
            }
            $string = (string)$string;
        }
        if (is_array($string) === true) {
            throw EscapeException::fromBadArray();
        }
        $string = (string)$string;

        return $this->zendEscape->escapeCss($string);
    }

    public function escapeURLComponent($string)
    {
        if (is_object($string) === true) {
            if (method_exists($string, '__toString') === false) {
                throw EscapeException::fromBadObject($string);
            }
            $string = (string)$string;
        }
        if (is_array($string) === true) {
            throw EscapeException::fromBadArray();
        }
        $string = (string)$string;

        return $this->zendEscape->escapeUrl($string);
    }
}
