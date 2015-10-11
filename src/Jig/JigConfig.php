<?php

namespace Jig;

/**
 * Class JigConfig
 *
 * Holds all of the config value for rendering templates.
 */
class JigConfig
{
    public $templateSourceDirectory;
    public $templateCompileDirectory;
    public $extension;
    public $compileCheck;
    public $compiledNamespace;

    /**
     * @param $templateSourceDirectory string - The directory that contains the templates.
     * @param $templateCompileDirectory string - The directory that compiled templates should be
     *   written to.
     * @param $extension string - The extension that will be appended to template names
     * to find the full filenme
     * @param $compileCheck string  - How to determine whether to compile a template. One
     *   of the \Jig\Jig::COMPILE_* constants
     * @param $compiledNamespace string - The namespace to use for compiled templates
     */
    public function __construct(
        $templateSourceDirectory,
        $templateCompileDirectory,
        $extension,
        $compileCheck,
        $compiledNamespace = "Jig\\CompiledTemplate"
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
    public function getTemplatePath($templateName)
    {
        return $this->templateSourceDirectory.$templateName.'.'.$this->extension;
    }

    /**
     * Returns the fully pathed filename for a compiled class.
     * @param $className
     * @return string
     */
    public function getCompiledFilename($className)
    {
        $namespace = $this->compiledNamespace;
        $classPath = $this->templateCompileDirectory.'/'.$namespace.'/'.$className.'.php';
        $classPath = str_replace('\\', '/', $classPath);

        return $classPath;
    }

    /**
     * Returns the classname with full namespace.
     * @param $className
     * @return string
     */
    public function getFullClassname($className)
    {
        $classname = $this->compiledNamespace."\\".$className;
        $classname = str_replace('/', '\\', $classname);

        return $classname;
    }
}
