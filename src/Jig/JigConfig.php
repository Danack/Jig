<?php


namespace Jig;


/**
 * Class JigConfig Holds all of the config value for rendering templates.
 */
class JigConfig {
    
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

    /**
     * Returns the fully pathed filename for a template.
     * @param $templateName
     * @return string
     */
    function getTemplatePath($templateName) {
        return $this->templateSourceDirectory.$templateName.'.'.$this->extension;
    }

    /**
     * Returns the fully pathed filename for a compiled class. 
     * @param $className
     * @return string
     */
    function getCompiledFilename($className) {
        $namespace = $this->compiledNamespace;
        $classPath = $this->templateCompileDirectory.'/'.$namespace.'/'.$className.'.php';
        
        return $classPath;
    }

    /**
     * Returns the classname with full namespace.
     * @param $className
     * @return string
     */
    function getFullClassname($className) {
        return  $this->compiledNamespace."\\".$className;

    }
}


