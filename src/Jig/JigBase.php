<?php

namespace Jig;

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

    /**
     * @return mixed
     */
    abstract public function renderInternal();

    /**
     *  Used during compilation
     * @return array
     */
    public static function getDependencyList()
    {
        return [];
    }

    protected function addPlugin(Plugin $plugin)
    {
        $this->plugins[] = $plugin;
    }
 
    /**
     * @param $functionName
     * @return mixed
     * @throws JigException
     */
    protected function callFunction($functionName)
    {
        $functionArgs = func_get_args();
        $params = array_splice($functionArgs, 1);
        
        foreach ($this->plugins as $plugin) {
            if ($plugin->hasFunction($functionName)) {
                return $plugin->callFunction($functionName, $params);
            }
        }

        throw new JigException("Function $functionName not known.");
    }

    protected function startRenderBlock($blockName, $segmentText)
    {
        foreach ($this->plugins as $renderBlock) {
            if ($renderBlock->hasBlock($blockName)) {
                echo $renderBlock->callBlockRenderStart($blockName, $segmentText);
                ob_start();
                return;
            }
        }
        throw new JigException("Block $blockName not known for starting block.");
    }
    
    protected function endRenderBlock($blockName)
    {
        $contents = ob_get_contents();
        ob_end_clean();
        foreach ($this->plugins as $renderBlock) {
            if ($renderBlock->hasBlock($blockName)) {
                echo $renderBlock->callBlockRenderEnd($blockName, $contents);
                return;
            }
        }
        echo $contents;
        throw new JigException("Block $blockName not known for ending block.");
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
            ob_end_clean();
            //Just rethrow it to keep the stack trace the same
            throw $je;
        }
        catch (\Exception $e) {
            //TODO - should put the bit that gave an error somewhere?
            //$contents = ob_get_contents();
            ob_end_clean();
            
            throw new JigException(
                "Failed to render template: ".$e->getMessage(),
                JigException::FAILED_TO_RENDER,
                $e
            );
        }
        //TODO - think about this.
//        finally {
//            ob_end_clean();
//        }
    
        ob_end_clean();

        return $contents;
    }
}
