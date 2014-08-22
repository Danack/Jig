<?php


namespace Jig;


class JigConfig {

    public $forceCompile;
    public $templateSourceDirectory;
    public $templateCompileDirectory;
    public $extension;
    public $compileCheck;
    
    function __construct($templateSourceDirectory, $templateCompileDirectory, $extension, $compileCheck) {
        $this->templateSourceDirectory = $templateSourceDirectory;
        $this->templateCompileDirectory = $templateCompileDirectory;
        $this->extension = $extension;
        $this->compileCheck = $compileCheck;
    }
}


