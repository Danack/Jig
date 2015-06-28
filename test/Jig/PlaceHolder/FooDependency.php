<?php


namespace Jig\PlaceHolder;


class FooDependency {

    const output = "This is a foo"; 
    
    function render()
    {
        return FooDependency::output;
    }
}

