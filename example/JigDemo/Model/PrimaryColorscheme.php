<?php


namespace JigDemo\Model;


class PrimaryColorscheme implements ColorScheme {

    function getColors() {
        return ['red', 'green', 'blue'];
    }
}