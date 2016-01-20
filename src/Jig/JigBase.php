<?php

namespace Jig;

use Jig\Escaper;
use Jig\Plugin;

/**
 * Class JigBase
 * This is the base class that all compiled templates are extended from.
 * There should be no reason to touch this class, unless you're debugging why
 * a template isn't working.
 */
abstract class JigBase
{
    /**
     * @var Plugin[]
     */
    protected $plugins = [];
    
    /** @var Escaper */
    protected $escaper;

    /**
     * @return mixed
     */
    abstract public function renderInternal();

    public function __construct(Escaper $escaper)
    {
        $this->escaper = $escaper;
    }

    /**
     * Returns the list of dependencies needed by a template, which for the
     * base template is just an escaper. Used during compilation.
     * @return array
     */
    public static function getDependencyList()
    {
        return ['Jig_Escaper' => 'Jig\Escaper'];
    }

    /**
     * Register the plugin being used by a template in the array of plugins.
     * @param Plugin $plugin
     */
    protected function addPlugin(Plugin $plugin)
    {
        $this->plugins[] = $plugin;
    }
 
    /**
     * Used to call a function in a template
     * @param $functionName
     * @return mixed
     * @throws JigException
     */
    protected function callFunction($functionName)
    {
        $functionArgs = func_get_args();
        $params = array_splice($functionArgs, 1);
        
        foreach ($this->plugins as $plugin) {
            $functionList = $plugin->getFunctionList();
            if (in_array($functionName, $functionList) === true) {
                return $plugin->callFunction($functionName, $params);
            }
        }

        throw new JigException("Function $functionName not known in plugins.");
    }

    /**
     * Called when a block is started in a template.
     *
     * @param $blockName
     * @param $segmentText
     * @throws JigException
     */
    protected function startRenderBlock($blockName, $segmentText)
    {
        foreach ($this->plugins as $plugin) {
            $blockRenderList = $plugin->getBlockRenderList();
            if (in_array($blockName, $blockRenderList) === true) {
                echo $plugin->callBlockRenderStart($blockName, $segmentText);
                ob_start();
                return;
            }
        }
        throw new JigException("Block $blockName not known for starting block in plugins.");
    }

    /**
     * Called when a block is ended in a template.
     * @param $blockName
     * @throws JigException
     */
    protected function endRenderBlock($blockName)
    {
        $contents = ob_get_contents();
        ob_end_clean();
        foreach ($this->plugins as $plugin) {
            $blockRenderList = $plugin->getBlockRenderList();
            if (in_array($blockName, $blockRenderList) === true) {
                echo $plugin->callBlockRenderEnd($blockName, $contents);
                return;
            }
        }
        echo $contents;
        throw new JigException("Block $blockName not known for ending block in plugins.");
    }

    /**
     * @return string
     * @throws JigException
     */
    public function render()
    {
        ob_start();

        try {
            $this->renderInternal();
            $contents = ob_get_contents();
        }
        catch (JigException $je) {
            @ob_end_clean();
            //Just rethrow it to keep the stack trace the same
            throw $je;
        }
        catch (\Exception $e) {
            @ob_end_clean();
            
            $message = sprintf(
                "Failed to render template '%s' : %s",
                get_class($this),
                $e->getMessage()
            );
            
            throw new JigException(
                $message,
                JigException::FAILED_TO_RENDER,
                $e
            );
        }

        ob_end_clean();

        return $contents;
    }
}
