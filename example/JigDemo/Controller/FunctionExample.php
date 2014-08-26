<?php

namespace JigDemo\Controller;

use Jig\JigRender;
use Jig\ViewModel;
use JigDemo\Response\TextResponse;
use JigDemo\ViewModel\FunctionViewModel;

function globalFunction() {
    return "This is a global function.";
}


class FunctionExample {

    private $jigRender;

    function __construct(JigRender $jigRender) {
        $this->jigRender = $jigRender;
    }

    function classFunction() {
        return "This is a method on class.";
    }

    function display() {
        $viewModel = new FunctionViewModel();

        $closureFunction = function () {
            $args = func_get_args();
            echo "This is a closure function. The args were: ".var_export($args);
        };
        
        $viewModel->bindFunction('closureFunction', $closureFunction);
        $viewModel->bindFunction('globalFunction', 'JigDemo\\Controller\\globalFunction');
        $viewModel->bindFunction('classFunction', [$this, 'classFunction']);
        
        $output = $this->jigRender->renderTemplateFile('functionExample', $viewModel);

        return new TextResponse($output);
    }
}

 