<?php

namespace Controller;

use Intahwebz\ViewModel;


class SyntaxExample {

    /** @var \Intahwebz\ViewModel */
    private $viewModel;

    function __construct(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }

    function display() {

        $this->viewModel->setVariable('htmlString', 'Example string with <i>embedded</i> HTML');

        $this->viewModel->bindFunction('greet', function($username) {return sprintf("Hello %s!", $username);} );

        $this->viewModel->bindFunction('getColors', function() {return ['red', 'green', 'blue'];} );
    }
}

 