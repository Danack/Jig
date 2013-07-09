<?php


namespace Intahwebz\Jig\Converter;


/**
 * Class TemplateSegment Base class for sections of a template.
 * @package Intahwebz\Jig\Converter
 */
abstract class TemplateSegment {

	public $text;

	function __construct($text) {
		$this->text = $text;
	}

	abstract function getString(ParsedTemplate $parsedTemplate);
	abstract function getRawString();
}



?>