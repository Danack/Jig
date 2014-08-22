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
    
    function getTemplatePath($templateName) {
        return $this->templateSourceDirectory.$templateName.'.'.$this->extension;
    }

    function getCompiledFilename($namespace, $className) {
        $classPath = $this->templateCompileDirectory.'/'.$namespace.'/'.$className.'.php';
        
        return $classPath;
    }
}


