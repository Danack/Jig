<?php


namespace Intahwebz\Jig;


class JigConfig {

    public $forceCompile;
    public $templateSourceDirectory;
    public $templateCompileDirectory;
    public $extension;
    
    function __construct($forceCompile, $templateSourceDirectory, $templateCompileDirectory, $extension) {

        $this->forceCompile = $forceCompile;
        $this->templateSourceDirectory = $templateSourceDirectory;
        $this->templateCompileDirectory = $templateCompileDirectory;
        $this->extension = $extension;
    }
}


