<?php

namespace JigDemo\Controller;

use Jig\Jig;

use JigDemo\Response\TextResponse;
use JigDemo\Helper\FunctionHelper;

function globalFunction() {
    return "This is a global function.";
}


class FunctionExample {

//    private $jigRender;
//
//    function __construct(JigRender $jigRender) {
//        $this->jigRender = $jigRender;
//    }

    function classFunction() {
        return "This is a method on class.";
    }

    function display(Jig $jig) {
        $functionHelper = new FunctionHelper();

        $closureFunction = function () {
            $args = func_get_args();
            echo "This is a closure function. The args were: ".var_export($args);
        };
        
        $functionHelper->bindFunction('closureFunction', $closureFunction);
        $functionHelper->bindFunction('globalFunction', 'JigDemo\\Controller\\globalFunction');
        $functionHelper->bindFunction('classFunction', [$this, 'classFunction']);

        $jig->addDefaultHelper(get_class($functionHelper));

        return getTemplateCallable('functionExample', [$functionHelper]);
    }
}

 