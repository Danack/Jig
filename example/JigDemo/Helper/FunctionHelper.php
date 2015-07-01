<?php

namespace JigDemo\Helper;

use Jig\TemplateHelper\BasicTemplateHelper;

class FunctionHelper extends BasicTemplateHelper {

    function showCompanyMotto()
    {
        echo "A computer on every desktop.";
    }

    function testMethod()
    {
        return "This is a method in the helper.";
    }    
}

 