<?php


namespace Jig\PlaceHolder;

use Jig\TemplateHelper\BasicTemplateHelper;


class PlaceHolderHelper extends BasicTemplateHelper {

    private $calledFunctions = array();
    
    const FUNCTION_MESSAGE = "This is a function";
    

    function viewFunction($foo){
        echo "Function was called. Param is '$foo'";
    }

    function var_dump($foo) {
        echo "Value is:";
        var_dump($foo);
    }

    function htmlEntityDecode($content) {
        echo html_entity_decode($content);
    }

    function someFunction($blah) {
        $this->setHasBeenCalled('someFunction', $blah);
    }

    function setHasBeenCalled($functionName, $paramString) {
        $this->calledFunctions[$functionName] = $paramString;
    }

    function hasBeenCalled($functionName, $paramString) {
        if (array_key_exists($functionName, $this->calledFunctions) == true) {
            if ($this->calledFunctions[$functionName] == $paramString) {
                return true;
            }
        }
        return false;
    }

    function checkRole($role)
    {
        if ($role == 'admin') {
            return true;
        }

        return false;
    }

    function getArray() 
    {
        return [];
    }

    function getObject()
    {
        return new \StdClass();
    }

    function getBar()
    {
        return 'bar';
    }

    function testNoOutput()
    {
        return 'This is some output';
    }

    function testCallableFunction()
    {
        echo "I am a callable function";
    }

    function getColors()
    {
        return ['red', 'green', 'blue'];
    }

    function isAllowed()
    {
        return true;
    }
    
    public function placeHolderFunc()
    {
        return self::FUNCTION_MESSAGE;
    }
}
