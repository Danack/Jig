<?php


namespace Jig;


interface ViewModel {

    /**
     * @param array $functionArgs First entry is function name, the rest are the function parameters.
     * @return mixed
     */
    function call(array $functionArgs);

    function getVariable($name);

    function isVariableSet($name);

    function setVariable($name, $value);

//    function setTemplate($templateFile);
//        
//    function getTemplate();
//    

    function bindFunction($functionName, callable $callable);

    function setMergedParams(array $array);

    /** @return array */
    function getMergedParams();

//    /**
//     * @param $message
//     * @return mixed
//     */
//    function addStatusMessage($message);
//
//    function getStatusMessages();

    function setResponse($data);
}
