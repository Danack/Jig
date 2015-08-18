<?php

namespace JigTest\PlaceHolder;

class ParentDependency
{
    const OUTPUT = "This is a parent";
    
    public function render()
    {
        return self::OUTPUT;
    }
}
