<?php

namespace Jig;

use Jig\ViewModel;
use Jig\Converter\JigConverter;

class Jig
{
    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';
    const COMPILE_NEVER         = 'COMPILE_NEVER';

    /**
     * @var array The class map for dynamically extending classes
     */
    private $mappedClasses = array();

    /**
     * @var Converter\JigConverter
     */
    private $jigConverter;

    /**
     * @var \Auryn\Injector
     */
    private $injector;

    /**
     * @var JigConfig
     */
    private $jigConfig;

    public function __construct(JigConfig $jigConfig, \Auryn\Injector $injector)
    {
        $this->jigConfig = clone $jigConfig;
        $this->jigConverter = new JigConverter($this->jigConfig);
        $this->injector = $injector;
    }

    public function renderTemplateFromString($templateString, $objectID, ViewModel $viewModel)
    {
        $jigRender = $this->createJigRender($viewModel);

        return $jigRender->renderTemplateFromString($templateString, $objectID);
    }

    public function renderTemplateFile($templateFilename, ViewModel $viewModel)
    {
        $jigRender = $this->createJigRender($viewModel);
        
        return $jigRender->renderTemplateFile($templateFilename);
    }
    
    private function createJigRender(ViewModel $viewModel)
    {
        $jigRender = new JigRender(
            $this->jigConfig,
            $this->jigConverter,
            $this->injector,
            $viewModel,
            $this->mappedClasses
        );

        return $jigRender;
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
     * Sets the class map for dynamically extending classes
     *
     * e.g. standardLayout => standardJSONLayout or standardHTMLLayout
     *
     * @param $classMap
     */
    public function mapClasses($classMap)
    {
        $this->mappedClasses = array_merge($this->mappedClasses, $classMap);
    }

    /**
     * @param $templateFilename
     * @return string
     */
    public function getCompileFilename($templateFilename)
    {
        return getCompileFilename($templateFilename, $this->jigConverter, $this->jigConfig);
    }
}