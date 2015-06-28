<?php


namespace Jig\PlaceHolder;

class ParentDependency {

    const output = "This is a parent";
    
    function render()
    {
        return self::output;
    }
}

