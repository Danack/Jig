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
     * @var JigConfig
     */
    protected $jigConfig;

    public function __construct(JigConfig $jigConfig, JigConverter $jigConverter)
    {
        $this->jigConfig = clone $jigConfig;
        $this->jigConverter = $jigConverter;
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
}