<?php

namespace JigDemo\Controller;

use Jig\Jig;
use JigDemo\Response\TextResponse;


function warningBlockStart() {
    $output = "<div class='warning' style='border: 2px solid #0000000'> **WARNING Wil Robinson**";
    $output .= "<span style='background-color: #ff3f3f'>";
    echo $output;
}

function warningBlockEnd($content) {
    $output = $content;
    $output .= "</span></div>";
    echo $output;
}



class BlockExample {

    private $jigRender;
    
    function __construct(Jig $jigRender) {
        $this->jigRender = $jigRender;
    }

    function display() {
        //This 
        $this->jigRender->bindRenderBlock(
            'warning',          //Block name
            '\JigDemo\Controller\warningBlockEnd',  //Block end callable
            '\JigDemo\Controller\warningBlockStart' //Block start callable.
        );

        $output = $this->jigRender->renderTemplateFile('blockExample');

        return new TextResponse($output);
    }
}

 