<?php


namespace Jig;


interface ViewModel {

    /**
     * @param array $functionArgs First entry is function name, the rest are the function parameters.
     * @return mixed
     */
    function call(array $functionArgs);

    /**
     * @param $name
     * @return mixed
     */
    function getVariable($name);

    /**
     * @param $name
     * @return mixed
     */
    function isVariableSet($name);

    /**
     * @param $name
     * @param $value
     * @return mixed
     */
    function setVariable($name, $value);

    /**
     * @param array $variable
     * @return mixed
     */
    function setVariables(array $variable);

    /**
     * Bind a callable to be usable in a template 
     * @param string $functionName The name the function should be called by. 
     * @param callable $callable 
     * @return mixed
     */
    function bindFunction($functionName, callable $callable);
}
