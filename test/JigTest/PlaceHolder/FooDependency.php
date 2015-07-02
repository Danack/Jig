<?php


namespace JigTest\PlaceHolder;


class FooDependency {

    const output = "This is a foo"; 
    
    function render()
    {
        return FooDependency::output;
    }
}

