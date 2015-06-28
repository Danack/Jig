<?php

namespace Jig\Helper;

class BasicHelper extends \Jig\TemplateHelper\BasicTemplateHelper {

    const message = "Hello, I am a basic helper.";
    
    public function helperSayHello()
    {
        return self::message;
    }
    
}

