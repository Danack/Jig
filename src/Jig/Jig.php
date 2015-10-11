<?php

namespace Jig;

use Jig\Converter\JigConverter;

/**
 * Class Jig
 * @package Jig
 */
class Jig
{
    /** Templates are never compiled. Useful for debugging only */
    const COMPILE_NEVER         = 'COMPILE_NEVER';
    
    /** Templates are only compiled if the compiled version does not exist */
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    
    /** Checks the last modified time of template and generated class.  */
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';
  
    /** Templates are always compiled */
    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';

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

    /**
     * @param JigConfig $jigConfig
     * @param JigRender $jigRender
     * @param JigConverter $jigConverter
     */
    public function __construct(
        JigConfig $jigConfig,
        JigRender $jigRender = null,
        JigConverter $jigConverter = null
    ) {
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

    /**
     * @return JigRender
     */
    public function getJigRender()
    {
        return $this->jigRender;
    }

    /**
     * @return JigConverter
     */
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
        return self::getCompileFilenameInternal($templateFilename, $this->jigConverter, $this->jigConfig);
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
    public function addDefaultPlugin($classname)
    {
        $classname = (string)$classname;

        $this->jigConverter->addDefaultPlugin($classname);
    }

    /**
     * @param $templateString
     * @param $cacheName
     * @return mixed
     */
    public function getParsedTemplateFromString($templateString, $cacheName)
    {
        return $this->jigRender->getParsedTemplateFromString($templateString, $cacheName);
    }
    
    /**
     * @param $templateName
     * @return string
     */
    public static function getCompileFilenameInternal($templateName, JigConverter $jigConverter, JigConfig $jigConfig)
    {
        $className = $jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $jigConfig->getCompiledFilename($className);
    
        return $compileFilename;
    }
}
