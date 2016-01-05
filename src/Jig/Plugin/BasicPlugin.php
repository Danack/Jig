<?php

namespace Jig\Plugin;

use Jig\Plugin;
use Jig\JigException;

/**
 * Class BasicPlugin
 * An example plugin that shows how functions, filters and blocks rendering can
 * be exposed by a plugin. Plugins are used each time the template is rendered.
 *
 * @package Jig\Plugin
 */
class BasicPlugin implements Plugin
{

    /**
     * The list of global functions that this plugin allows
     * people to call in a template.
     * @var array
     */
    private static $globalFunctions = array(
        'var_dump',
    );

    /**
     * Return the list of blocks provided by this plugin.
     * @return string[]
     */
    public static function getBlockRenderList()
    {
        return [
            'trim',
        ];
    }

    /**
     * Return the list of functions provided by this plugin.
     * @return string[]
     */
    public static function getFunctionList()
    {
        $methodFunctions = [
            'memory_usage'
        ];
        
        return array_merge($methodFunctions, self::$globalFunctions);
    }

    /**
     * Return the list of filters provided by this plugin.
     * @return string[]
     */
    public static function getFilterList()
    {
        return ['upper', 'lower'];
    }

    /**
     * @param string $functionName
     * @param array $params
     * @return mixed
     * @throws JigException
     */
    public function callFunction($functionName, array $params)
    {
        if (in_array($functionName, self::$globalFunctions) === true) {
            return call_user_func_array($functionName, $params);
        }
        
        if (method_exists($this, $functionName) === true) {
            return call_user_func_array([$this, $functionName], $params);
        }

        $message = "callFunction for unsupported function $functionName in ".get_class($this);

        throw new JigException($message);
    }

    /**
     * @param $blockName
     * @param string $extraParam
     * @return mixed
     */
    public function callBlockRenderStart($blockName, $extraParam)
    {
        $blockNameStart = $blockName."BlockRenderStart";

        if (method_exists($this, $blockNameStart) === true) {
            return call_user_func([$this, $blockNameStart], $extraParam);
        }

        $message = "callBlockRenderStart for unsupported block $blockName in ".get_class($this);
        
        throw new JigException($message);
    }

    /**
     * @param string $blockName
     * @param string $contents
     * @return mixed
     */
    public function callBlockRenderEnd($blockName, $contents)
    {
        $blockNameEnd = $blockName."BlockRenderEnd";

        if (method_exists($this, $blockNameEnd) === true) {
            return call_user_func([$this, $blockNameEnd], $contents);
        }
        
        $message = "callBlockRenderEnd for unsupported block $blockName in ".get_class($this);
        
        throw new JigException($message);
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


    /**
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string)
    {
        if (method_exists($this, $filterName) === true) {
            return call_user_func([$this, $filterName], $string);
        }
        
        $message = "callFilter for unsupported filter $filterName in ".get_class($this);

        throw new JigException($message);
    }

    /**
     * An example of how a function in a template can be connected to a method in
     * the plugin.
     * @return string
     */
    public function memory_usage()
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Example filter method. Converts string to upper case.
     * @param $string
     * @return string
     */
    public function upper($string)
    {
        return strtoupper($string);
    }

    /**
     * Example filter method. Converts string to lower case.
     * @param $string
     * @return string
     */
    public function lower($string)
    {
        return strtolower($string);
    }
}
