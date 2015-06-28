<?php

namespace Jig;

use Jig\JigException;
use Jig\TemplateHelper;

/**
 * Class JigBase
 * This is the base class that all compiled templates are extended from.
 * There should be no reason to touch this class, unless you're debugging why
 * a template isn't working.
 */
abstract class JigBase
{
    /**
     * @var JigRender
     */
    protected $jigRender;

    /**
     * @var TemplateHelper[]
     */
    protected $templateHelpers = [];


    public function __construct(JigRender $jigRender)
    {
        $this->jigRender = $jigRender;
    }

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

    /**
     * @return array
     */
    public function getInjections()
    {
        return [];
    }

    /**
     * Render this template
     * @return mixed
     */
    public function renderDirect()
    {
        return $this->renderInternal();
    }

    public function addTemplateHelper(TemplateHelper $templateHelper)
    {
        $this->templateHelpers[] = $templateHelper;
    }
    

    /**
     * @param $placeHolder
     * @internal param array $functionArgs
     * @return mixed|void
     * @todo - if this template has $functionName - call it?
     */
    public function call($functionName)
    {
        $functionArgs = func_get_args();
        $params = array_splice($functionArgs, 1);
        
        foreach ($this->templateHelpers as $templateHelper) {
            if ($templateHelper->hasFunction($functionName)) {
                return $templateHelper->call($functionName, $params);
            }
        }

        throw new JigException("Function $functionName not known.");
    }
    
    
    public function render()
    {
        ob_start();

        try {
            $this->renderDirect();
            $contents = ob_get_contents();
        }
        catch(JigException $je) {
            ob_end_clean();
            //Just rethrow it to keep the stack trace the same
            throw $je;
        }
        catch(\Exception $e) {
            //TODO - should put the bit that gave an error somewhere?
            //$contents = ob_get_contents();
            ob_end_clean();
            
            throw new JigException(
                "Failed to render template: ".$e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        ob_end_clean();

        return $contents;
    }
}
