<?php


namespace Jig\Converter;

/**
 * Class TextTemplateSegment Allows a piece of text (or html) to be stored while parsing templates.
 */
class TextTemplateSegment extends TemplateSegment {

	public function getRawString(){
		return $this->text;
	}

	function getString(ParsedTemplate $parsedTemplate, $extraFilters = array()) {
		return $this->text;
	}
}

