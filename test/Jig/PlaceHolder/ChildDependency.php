<?php


namespace Jig\PlaceHolder;


class ChildDependency {

    const output = "This is a child dependency.";
    
    function render()
    {
        return ChildDependency::output;
    }
}

