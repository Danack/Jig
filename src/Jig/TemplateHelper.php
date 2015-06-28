<?php


namespace Jig;

interface TemplateHelper
{

    /**
     * @return bool
     */
    public function hasFunction($functionName);
    
    /**
     * @param array $functionArgs First entry is function name, the rest are the function parameters.
     * @return mixed
     */
    public function call($functionName, array $params);
}
