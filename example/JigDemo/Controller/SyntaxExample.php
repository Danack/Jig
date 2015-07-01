<?php

namespace JigDemo\Controller;

use Jig\JigRender;
//use Jig\ViewModel;
use Jig\TemplateHelper\BasicTemplateHelper;
use JigDemo\Response\TextResponse;


class SyntaxExample {

    private $jigRender;
    
    function __construct(JigRender $jigRender) {
        $this->jigRender = $jigRender;
    }

    function display() {
//        $helper = new BasicTemplateHelper();
//

        
        return getTemplateCallable('SyntaxExample');
        
    }
}

 