<?php

namespace JigTest\PlaceHolder;

class FooDependency
{
    const OUTPUT = "This is a foo";
    
    public function render()
    {
        return FooDependency::OUTPUT;
    }
}
