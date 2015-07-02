<?php


namespace JigTest\PlaceHolder;


class ChildDependency {

    const output = "This is a child dependency.";
    
    function render()
    {
        return ChildDependency::output;
    }
}

