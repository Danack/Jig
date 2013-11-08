<?php

namespace Controller;

use Intahwebz\ViewModel;


class DataExample {

    private $viewModel;

    function __construct(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }

    function display() {

        $colors = [
            'red' => '#ff3f3f',
            'green' => '#3fff3f',
            'blue' => '#3f3fff'
        ];

        $this->viewModel->setVariable('colors', $colors);
    }
}

 