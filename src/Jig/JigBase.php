<?php

namespace Jig;

use Jig\JigException;
use Jig\TemplateHelper;
use Jig\Filter;

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

    /**
     * @var Filter[]
     */
    protected $filters = [];
    
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

    public function addTemplateHelper(TemplateHelper $templateHelper)
    {
        $this->templateHelpers[] = $templateHelper;
    }

    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param $functionName
     * @return mixed
     * @throws JigException
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
    
    public function callFilter($text, $filterName)
    {
        foreach ($this->filters as $filter) {
            if ($filter->hasFilter($filterName)) {
                return $filter->call($filterName, $text);
            }
        }

        throw new JigException("Filter $filterName not known.");
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
                JigException::FAILED_TO_RENDER,
                $e
            );
        }

        ob_end_clean();

        return $contents;
    }
}
