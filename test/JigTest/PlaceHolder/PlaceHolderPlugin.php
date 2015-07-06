<?php


namespace JigTest\PlaceHolder;

use Jig\Plugin\BasicPlugin;


class PlaceHolderPlugin extends BasicPlugin {

    private $calledFunctions = array();

    public $passedSegementText;

    public $blockStartCallCount = 0;

    public $blockEndCallCount = 0;

    const FUNCTION_MESSAGE = "This is a function";
    const greetings_message = "Hello world!";

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

    function helperSayHello()
    {
        return self::greetings_message;
    }
    
    
    public static function getBlockRenderList()
    {
        return [
            'trim',
            'warning',
            'htmlEntityDecode',
        ];
    }
    

    
    function viewFunction($foo){
        echo "Function was called. Param is '$foo'";
    }

    function var_dump($foo) {
        echo "Value is:";
        var_dump($foo);
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
    
      const message = "This is an exception";
    
    public function throwup()
    {
        throw new \Exception(self::message);
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

    function htmlEntityDecodeBlockRenderEnd($content) {
        echo html_entity_decode($content);
    }


    function warningBlockRenderStart($segmentText)  {
        $this->passedSegementText = $segmentText;
        $this->blockStartCallCount++;
        return "<span class='warning'>";
    }

    function warningBlockRenderEnd($contents) {
        $this->blockEndCallCount++;

        return $contents."\n</span>";
    }

    

    
}


  