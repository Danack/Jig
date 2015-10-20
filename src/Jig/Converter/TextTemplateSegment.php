<?php


namespace Jig\Converter;

/**
 * Class TextTemplateSegment
 * Allows a piece of text (or html) to be stored while parsing templates.
 */
class TextTemplateSegment extends TemplateSegment
{

    /**
     * @return mixed
     */
    public function getRawString()
    {
        return $this->text;
    }

    /**
     * @param ParsedTemplate $parsedTemplate
     * @param array $extraFilters
     * @return mixed
     */
    public function getTextString(ParsedTemplate $parsedTemplate, $extraFilters = array())
    {
        return $this->text;
    }
}
