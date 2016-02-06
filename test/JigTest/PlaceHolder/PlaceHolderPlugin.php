<?php

namespace JigTest\PlaceHolder;

use Jig\Plugin\BasicPlugin;

class PlaceHolderPlugin extends BasicPlugin
{
    private $calledFunctions = array();

    public $passedSegementText;

    public $blockStartCallCount = 0;

    public $blockEndCallCount = 0;

    const FUNCTION_MESSAGE = "This is a function";
    const GREETINGS_MESSAGE = "Hello world!";

    public static function getFunctionList()
    {
        return [
            'viewFunction',
            'someFunction',
            'var_dump',
            'placeHolderFunc',
            'isAllowed',
            'checkRole',
            'getBar',
            'getArray',
            'getObject',
            'getColors',
            'helperSayHello',
            'testCallableFunction',
            'testNoOutput',
            'throwup',
            
        ];
    }

    public function helperSayHello()
    {
        return self::GREETINGS_MESSAGE;
    }

    public static function getBlockRenderList()
    {
        return [
            'trim',
            'warning',
            'htmlEntityDecode',
            'checkNoExtraLine'
        ];
    }

    public function viewFunction($foo)
    {
        echo "Function was called. Param is '$foo'";
    }

    public function var_dump($foo)
    {
        echo "Value is:";
        var_dump($foo);
    }

    public function someFunction($blah)
    {
        $this->setHasBeenCalled('someFunction', $blah);
    }

    public function setHasBeenCalled($functionName, $paramString)
    {
        $this->calledFunctions[$functionName] = $paramString;
    }

    public function hasBeenCalled($functionName, $paramString)
    {
        if (array_key_exists($functionName, $this->calledFunctions) === true) {
            if ($this->calledFunctions[$functionName] === $paramString) {
                return true;
            }
        }
        return false;
    }

    public function checkRole($role)
    {
        if (strcmp($role, 'admin') === 0) {
            return true;
        }

        return false;
    }

    public function getArray()
    {
        return [];
    }

    public function getObject()
    {
        return new \StdClass();
    }

    public function getBar()
    {
        return 'bar';
    }

    public function testNoOutput()
    {
        return 'This is some output';
    }

    public function testCallableFunction()
    {
        echo "I am a callable function";
    }

    public function getColors()
    {
        return ['red', 'green', 'blue'];
    }

    public function isAllowed()
    {
        return true;
    }
    
    public function placeHolderFunc()
    {
        return self::FUNCTION_MESSAGE;
    }

    const MESSAGE = "This is an exception";

    public function throwup()
    {
        throw new \Exception(self::MESSAGE);
    }
    
    public function trimStart($segmentText)
    {
        return "";
    }
    
    /**
     * @param $content
     * @return string
     */
    public function trimEnd($content)
    {
        return trim($content);
    }
    
    public function htmlEntityDecodeBlockRenderStart($segmentText)
    {
        return "";
    }

    public function htmlEntityDecodeBlockRenderEnd($content)
    {
        echo html_entity_decode($content);
    }


    public function warningBlockRenderStart($segmentText)
    {
        $this->passedSegementText = $segmentText;
        $this->blockStartCallCount++;
        return "<span class='warning'>";
    }

    public function warningBlockRenderEnd($contents)
    {
        $this->blockEndCallCount++;

        return $contents."\n</span>";
    }

    public function checkNoExtraLineBlockRenderStart($segmentText)
    {
        return "";
    }

    public function checkNoExtraLineBlockRenderEnd($contents)
    {
        return $contents;
    }
}
