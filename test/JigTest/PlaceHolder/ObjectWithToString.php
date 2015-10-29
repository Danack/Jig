<?php


namespace JigTest\PlaceHolder;

class ObjectWithToString
{
    private $string;
    public function __construct($string)
    {
        $this->string = $string;
    }
    
    public function __toString()
    {
        return $this->string;
    }
}
