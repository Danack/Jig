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
        //The {}'s are stripped by the parse, so re-add them.
        return '{'.$this->text.'}';
    }
    
    public function getCommentString(ParsedTemplate $parsedTemplate, $extraFilters = array())
    {
        return '//'. $this->text."\n";
    }
}
