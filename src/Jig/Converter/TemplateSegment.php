<?php


namespace Jig\Converter;


/**
 * Class TemplateSegment Base class for sections of a template.
 */
abstract class TemplateSegment {

    public $text;

    function __construct($text) {
        $this->text = $text;
    }

    abstract function getString(ParsedTemplate $parsedTemplate, $extraFilters = array());
    abstract function getRawString();
}

