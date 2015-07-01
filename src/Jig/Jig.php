<?php

namespace Jig;

use Jig\Converter\JigConverter;

class Jig
{
    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';
    const COMPILE_NEVER         = 'COMPILE_NEVER';

    /**
     * @var Converter\JigConverter
     */
    protected $jigConverter;

    /**
     * @var \Jig\JigConfig
     */
    protected $jigConfig;

    /**
     * @var \Jig\JigRender
     */
    protected $jigRender;
    
    public function __construct(
        JigConfig $jigConfig,
        JigRender $jigRender = null,
        JigConverter $jigConverter = null)
    {
        if ($jigConverter == null) {
            $jigConverter = new JigConverter($jigConfig);
        }
        
        if ($jigRender == null) {
            $jigRender = new JigRender($jigConfig, $jigConverter);
        }
        
        $this->jigConfig = clone $jigConfig;
        $this->jigConverter = $jigConverter;
        $this->jigRender = $jigRender;
    }

    public function getJigRender()
    {
        return $this->jigRender;
    }
    
    public function getJigConverter()
    {
        return $this->jigConverter;
    }
    

    /**
     * @param $blockName
     * @param callable $startCallback
     * @param callable $endCallback
     */
    public function bindCompileBlock($blockName, callable $startCallback, callable $endCallback)
    {
        $this->jigConverter->bindCompileBlock($blockName, $startCallback, $endCallback);
    }

    /**
     * @param $blockName
     * @param $endFunctionName
     * @param null $startFunctionName
     */
    public function bindRenderBlock($blockName, $endFunctionName, $startFunctionName = null)
    {
        $this->jigConverter->bindRenderBlock($blockName, $endFunctionName, $startFunctionName);
    }

    /**
     * Delete the compiled version of a template.
     * @param $templateName
     * @return bool
     */
    public function deleteCompiledFile($templateName)
    {
        $className = $this->jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $this->jigConfig->getCompiledFilename($className);
        $deleted = @unlink($compileFilename);

        return $deleted;
    }

    /**
     * @param $templateFilename
     * @return string
     */
    public function getCompileFilename($templateFilename)
    {
        return getCompileFilename($templateFilename, $this->jigConverter, $this->jigConfig);
    }

    /**
     * @param $templateFilename
     */
    public function checkTemplateCompiled($templateFilename)
    {
        $this->jigRender->checkTemplateCompiled($templateFilename);
    }

    /**
     * @param $templateName
     * @return string
     */
    public function getTemplateCompiledClassname($templateName)
    {
        return $this->jigConfig->getFullClassname($templateName);
    }

    /**
     * @param $classname
     */
    public function addDefaultHelper($classname)
    {
        $classname = (string)$classname;
        
        $this->jigConverter->addDefaultHelper($classname);
    }

    /**
     * @param $filterName
     * @param callable $callback
     */
    public function addFilter($filterName, callable $callback)
    {
        $this->jigRender->addFilter($filterName, $callback);
    }
}