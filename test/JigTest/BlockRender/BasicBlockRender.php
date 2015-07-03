<?php

namespace JigTest\BlockRender;

use Jig\BlockRender;
use Jig\JigException;

class BasicBlockRender implements BlockRender {

    public $passedSegementText;

    public $blockStartCallCount = 0;

    public $blockEndCallCount = 0;

    /**
     * @param $blockName
     * @return bool
     */
    public static function hasBlock($blockName)
    {
        $blocks = self::getBlockList();

        if(in_array($blockName, $blocks, true)) {
            return true;
        }

        return false;
    }

    public static function getBlockList()
    {
        return [
            'warning',
            'trim',
            'htmlEntityDecode'
        ];
    }

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     */
    public function callStart($blockName, $string)
    {
        $blockNameStart = $blockName."Start";

        if (method_exists($this, $blockNameStart) == true) {
            return call_user_func([$this, $blockNameStart], $string);
        }

        throw new JigException("No function called [$blockNameStart] in BasicBlockRender");
    }

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     */
    public function callEnd($blockName, $string)
    {
        $blockNameEnd = $blockName."End";

        if (method_exists($this, $blockNameEnd) == true) {
            return call_user_func([$this, $blockNameEnd], $string);
        }
        
        throw new JigException("No function called [$blockNameEnd] in BasicBlockRender");
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
    
    public function htmlEntityDecodeStart($segmentText)
    {
        return "";
    }

    function htmlEntityDecodeEnd($content) {
        echo html_entity_decode($content);
    }


    function warningStart($segmentText)  {
        $this->passedSegementText = $segmentText;
        $this->blockStartCallCount++;
        return "<span class='warning'>";
    }

    function warningEnd($contents) {
        $this->blockEndCallCount++;
        
        return $contents."\n</span>";
    }
}

