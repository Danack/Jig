<?php


namespace Jig\TemplateHelper;

use Jig\JigException;

class BasicTemplateHelper implements \Jig\TemplateHelper
{
    /**
     * @var array Stores the bound functions that are available through the helper
     */
    private $boundFunctions = array();

    /**
     * @param $functionName
     * @param callable $callable
     */
    public function bindFunction($functionName, callable $callable)
    {
        $this->boundFunctions[$functionName] = $callable;
    }

    /**
     * @param $functionName
     * @return bool
     */
    public function hasFunction($functionName)
    {
        if (method_exists($this, $functionName)) {
            return true;
        }

        return array_key_exists($functionName, $this->boundFunctions);
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
        if (array_key_exists($functionName, $this->boundFunctions) == true) {
            return call_user_func_array($this->boundFunctions[$functionName], $params);
        }

        if (method_exists($this, $functionName) == true) {
            return call_user_func_array([$this, $functionName], $params);
        }

        throw new JigException("No bound function called [$functionName]");
    }
}
