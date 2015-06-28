<?php

namespace JigDemo\Controller;

use Jig\JigRender;
use Jig\ViewModel;
use Jig\ViewModel\BasicTemplateHelper;
use JigDemo\Response\TextResponse;


class SyntaxExample {

    private $jigRender;
    
    function __construct(JigRender $jigRender) {
        $this->jigRender = $jigRender;
    }

    function display() {
        $viewModel = new BasicTemplateHelper();
        $viewModel->setVariable('htmlString', 'Example string with <i>embedded</i> HTML');
        $viewModel->bindFunction('greet', function($username) {return sprintf("Hello %s!", $username);} );
        $viewModel->bindFunction('getColors', function() {return ['red', 'green', 'blue'];} );
        $output = $this->jigRender->renderTemplateFile('SyntaxExample', $viewModel);
        return new TextResponse($output);
    }
}

 