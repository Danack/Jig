<?php

namespace JigTest\Helper;

class GenericExceptionHelper extends \Jig\TemplateHelper\BasicTemplateHelper {

    const message = "This is an exception";
    
    public function throwup()
    {
        throw new \Exception(self::message);
    }
}

