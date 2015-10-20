<?php


namespace Jig\Converter;

/**
 * Class TemplateSegment Base class for sections of a template.
 */
abstract class TemplateSegment
{
    protected $text;

    public function __construct($text)
    {
        $this->text = $text;
    }
    
    public function getOriginalText()
    {
        return $this->text;
    }

    //abstract public function getString(ParsedTemplate $parsedTemplate, $extraFilters = array());
    abstract public function getRawString();
}
