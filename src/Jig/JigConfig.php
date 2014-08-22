<?php


namespace Jig;


class JigConfig {

    public $forceCompile;
    public $templateSourceDirectory;
    public $templateCompileDirectory;
    public $extension;
    public $compileCheck;
    public $compiledNamespace = "Jig\\PHPCompiledTemplate";
    
    function __construct(
        $templateSourceDirectory,
        $templateCompileDirectory,
        $extension,
        $compileCheck,
        $compiledNamespace = "Jig\\PHPCompiledTemplate"
    ) {
        $this->templateSourceDirectory = $templateSourceDirectory;
        $this->templateCompileDirectory = $templateCompileDirectory;
        $this->extension = $extension;
        $this->compileCheck = $compileCheck;
        $this->compiledNamespace = $compiledNamespace;
    }
    
    function getTemplatePath($templateName) {
        return $this->templateSourceDirectory.$templateName.'.'.$this->extension;
    }

    function getCompiledFilename($className) {
        $namespace = $this->compiledNamespace;
        $classPath = $this->templateCompileDirectory.'/'.$namespace.'/'.$className.'.php';
        
        return $classPath;
    }

    function getFullClassname($className) {
        return  $this->compiledNamespace."\\".$className;

    }
}


