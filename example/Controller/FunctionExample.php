<?php

namespace Controller;

use Intahwebz\ViewModel;

function globalFunction() {
    return "This is a global function.";
}


class FunctionExample {

    private $viewModel;

    function __construct(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }

    function classFunction() {
        return "The template set for the view model is: ".$this->viewModel->getTemplate();
    }

    function display() {

        $this->viewModel->bindFunction('closureFunction', function () {
            $args = func_get_args();
            return array_sum($args);
        });

        $this->viewModel->bindFunction('globalFunction', 'Controller\\globalFunction');

        $this->viewModel->bindFunction('classFunction', [$this, 'classFunction']);
    }
}

 