<?php


namespace Jig\Converter;

/**
 * Class CommentTemplateSegment Allows a commented out line of text from a template to be written
 * as a PHP comment in the generated template.
 */
class CommentTemplateSegment extends TemplateSegment
{
    public function getRawString()
    {
        return $this->text;
    }
    
    public function getString(ParsedTemplate $parsedTemplate, $extraFilters = array())
    {
        return '//'. $this->text.'';
    }
}
