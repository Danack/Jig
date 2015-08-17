<?php

namespace Jig\Plugin;

use Jig\Plugin;

use Jig\JigException;

class BasicPlugin implements Plugin
{
    static private $functionList = [
        'var_dump'
    ];

    public static function getFunctionList()
    {
        return self::$functionList;
    }

    
    public static function hasBlock($blockName)
    {
        $blockList = static::getBlockRenderList();

        return in_array($blockName, $blockList, true);
    }
    
    public static function hasFunction($functionName)
    {
        $functionList = static::getFunctionList();

        return in_array($functionName, $functionList, true);
    }
    
    /**
     * @param string $functionName
     * @param array $params
     * @return mixed
     * @throws JigException
     */
    public function callFunction($functionName, array $params)
    {
        if (in_array($functionName, self::$functionList)) {
            call_user_func_array($functionName, $params);
        }
        
        if (method_exists($this, $functionName) == true) {
            return call_user_func_array([$this, $functionName], $params);
        }

        throw new JigException("No function called [$functionName] in BasicPlugin");
    }

    
    
    public static function getBlockRenderList()
    {
        return [
            'trim',
        ];
    }

    /**
     * @param $blockName
     * @param string $extraParam
     * @return mixed
     */
    public function callBlockRenderStart($blockName, $extraParam)
    {
        $blockNameStart = $blockName."BlockRenderStart";

        if (method_exists($this, $blockNameStart) == true) {
            return call_user_func([$this, $blockNameStart], $extraParam);
        }

        throw new JigException("No function called [$blockNameStart] in BasicPlugin");
    }

    /**
     * @param string $blockName
     * @param string $contents
     * @return mixed
     */
    public function callBlockRenderEnd($blockName, $contents)
    {
        $blockNameEnd = $blockName."BlockRenderEnd";

        if (method_exists($this, $blockNameEnd) == true) {
            return call_user_func([$this, $blockNameEnd], $contents);
        }
        
        throw new JigException("No function called [$blockNameEnd] in BasicPlugin");
    }

    public function trimBlockRenderStart($segmentText)
    {
        return "";
    }

    /**
     * @param $content
     * @return string
     */
    public function trimBlockRenderEnd($content)
    {
        return trim($content);
    }

    public static function getFilterList()
    {
        return ['upper', 'lower'];
    }
    
    /**
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string)
    {
        if (method_exists($this, $filterName) == true) {
            return call_user_func([$this, $filterName], $string);
        }

        throw new JigException(
            "No filter called [$filterName] in BasicPlugin"
        );
    }

    public function upper($string)
    {
        return strtoupper($string);
    }
    
    public function lower($string)
    {
        return strtolower($string);
    }
}
