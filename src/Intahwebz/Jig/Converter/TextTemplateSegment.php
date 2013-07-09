<?php


namespace Intahwebz\Jig\Converter;

/**
 * Class TextTemplateSegment Allows a piece of text (or html) to be stored while parsing templates.
 * @package Intahwebz\Jig\Converter
 */
class TextTemplateSegment extends TemplateSegment {

	public function getRawString(){
		return $this->text;
	}

	function getString(ParsedTemplate $parsedTemplate) {
		return $this->text;
	}
}



?>