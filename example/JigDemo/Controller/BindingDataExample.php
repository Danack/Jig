<?php

namespace JigDemo\Controller;

use Jig\ViewModel;
use Jig\JigRender;
use JigDemo\ViewModel\VariableViewModel;
use JigDemo\Response\TextResponse;


class BindingDataExample {

    private $jigRender;

    function __construct(JigRender $jigRender) {
        $this->jigRender = $jigRender;
    }

    function display() {
        $colors = [
            'red' => '#ff3f3f',
            'green' => '#3fff3f',
            'blue' => '#3f3fff'
        ];

        $viewModel = new VariableViewModel();
        $viewModel->setVariable('colors', $colors);
        $output = $this->jigRender->renderTemplateFile('bindingDataExample', $viewModel);

        return new TextResponse($output);
    }
}

 