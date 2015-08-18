<?php

namespace JigTest\PlaceHolder;

class ChildDependency
{
    const OUTPUT = "This is a child dependency.";
    
    public function render()
    {
        return ChildDependency::OUTPUT;
    }
}
