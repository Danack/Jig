<?php

namespace Jig\TemplateHelper;



class DebugHelper implements \Jig\TemplateHelper
{
    
    private $functionList = [];

    public function __construct()
    {
        $this->functionList[] = 'var_dump';
    }
    
    /**
     * @return bool
     */
    public function hasFunction($functionName)
    {
        return in_array($functionName, $this->functionList);
    }

    /**
     * @param array $functionArgs First entry is function name, the rest are the function parameters.
     * @return mixed
     */
    public function call($functionName, array $params)
    {
        if (in_array($functionName, $this->functionList)) {
            call_user_func_array($functionName, $params);
        }
    }
}