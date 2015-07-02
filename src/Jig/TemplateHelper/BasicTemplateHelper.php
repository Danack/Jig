<?php


namespace Jig\TemplateHelper;

use Jig\JigException;

class BasicTemplateHelper implements \Jig\TemplateHelper
{

    /**
     * @param $functionName
     * @return bool
     */
    public function hasFunction($functionName)
    {
        return method_exists($this, $functionName);
    }

    /**
     * @param array $params
     * @return mixed
     * @throws JigException
     *
     * TODO - should this be replaced with one method to get the callable, and then calling it from the calling code?
     * That would make the stack trace be smaller.
     */
    public function call($functionName, array $params)
    {
        if (method_exists($this, $functionName) == true) {
            return call_user_func_array([$this, $functionName], $params);
        }

        throw new JigException("No function called [$functionName] in BasicTemplateHelper");
    }
}
