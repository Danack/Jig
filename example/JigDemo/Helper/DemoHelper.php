<?php

namespace JigDemo\Helper;

use Jig\TemplateHelper\BasicTemplateHelper;
use JigDemo\Model\ColorScheme;


class DemoHelper extends BasicTemplateHelper
{
    function greet($username)
    {
        return sprintf("Hello %s!", $username);
    } 

    function getColors()
    {
        return ['red', 'green', 'blue'];
    }

    function exampleFunction(ColorScheme $colorScheme)
    {
        return "This is an example function. There are ".count($colorScheme->getColors())." colors in the color scheme.";
    }
    
    function getTextThatContainsHTML()
    {
        return "This is some <b>text</b> that <i>contains</i> HTML tags.";
    }
    
}
