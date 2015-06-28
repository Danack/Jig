<?php


namespace Jig\PlaceHolder;

use Jig\TemplateHelper\BasicTemplateHelper;


class PlaceHolderHelper extends BasicTemplateHelper {

    private $calledFunctions = array();

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

    function checkRole($role) {
        if ($role == 'admin') {
            return true;
        }

        return false;
    }
}