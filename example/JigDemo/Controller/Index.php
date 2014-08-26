<?php

namespace JigDemo\Controller;

use Jig\JigRender;
use JigDemo\Response\TextResponse;



class Index {
    
    function display(JigRender $jigRender) {
        $output = $jigRender->renderTemplateFile('index');
        return new TextResponse($output);
    }
}

 