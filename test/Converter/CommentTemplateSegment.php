<?php


namespace Intahwebz\Jig\Converter;

/**
 * Class CommentTemplateSegment Allows a commented out line of text from a template to be written
 * as a PHP comment in the generated template.
 * @package Intahwebz\Jig\Converter
 */
class CommentTemplateSegment extends TemplateSegment {

	public function getRawString(){
		return $this->text;
	}

	function getString(ParsedTemplate $parsedTemplate, $extraFilters = array()) {
		return '//'. $this->text.'';
	}
}

