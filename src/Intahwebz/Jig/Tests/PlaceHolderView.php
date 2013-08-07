<?php


namespace Intahwebz\Jig\Tests;



use Intahwebz\Jig\JigException;
use Intahwebz\View;
use Intahwebz\Jig\Tests\JigTestException;


class PlaceHolderView implements View {

    private $variables = array();
    private $boundFunctions = array();

    /**
     * TODO - This is smelling like an abstract class IntahwebzView
     * @param $variable
     * @return bool
     */
    function getVariable($variable) {
        if (array_key_exists($variable, $this->variables) == true) {
            return $this->variables[$variable];
        }

        return false;
    }

    /**
     *
     * TODO - should this be part of BaseTemplate as it is going to be the same for every view?
     *
     * @param $params
     * @return mixed|void
     */
    function call($params) {
        $functionName = array_shift($params);

        if (array_key_exists($functionName, $this->boundFunctions) == true) {
            return call_user_func_array($this->boundFunctions[$functionName], $params);
        }

        if (method_exists($this, $functionName) == false) {
            throw new JigException("No method $functionName");
        }

        return call_user_func_array([$this, $functionName], $params);
    }


    function isVariableSet($string){
        return array_key_exists($string, $this->variables);
        //return false;
    }

    function assign($variable, $value){
        $this->variables[$variable] = $value;
    }

    function viewFunction($foo){
        echo "Function was called.";
    }

    function var_dump($foo) {
        echo "Value is:";
        var_dump($foo);
    }


    function htmlEntityDecode($content) {
        //$result = Markdown($contents);
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

    function checkRole($role) {
        if ($role == 'admin') {
            return true;
        }

        return false;
    }
}



?>