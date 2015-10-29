<?php

namespace JigTest\PlaceHolder\Plugin;

use Jig\Plugin;

class BadPlugin implements Plugin
{
    /**
     * Return the list of blocks provided by this plugin.
     * @return string[]
     */
    public static function getBlockRenderList()
    {
        return new \StdClass();
    }

    /**
     * Return the list of filters provided by this plugin.
     * @return string[]
     */
    public static function getFilterList()
    {
        return new \StdClass();
    }

    /**
     * Return the list of functions provided by this plugin.
     * @return string[]
     */
    public static function getFunctionList()
    {
        return new \StdClass();
    }

    /**
     * Call the function named 'functionName' with a set of parameters
     * @param $functionName
     * @param array $params
     * @return mixed
     */
    public function callFunction($functionName, array $params)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Call the filter named 'filterName' for the string.
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Call the start function for the block named 'blockName' with any extra provided
     * parameters.
     * @param $blockName
     * @param string $extraParam
     * @return mixed
     */
    public function callBlockRenderStart($blockName, $extraParam)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Call the end function for the block named 'blockName' with any extra provided
     * parameters.
     * @param $blockName
     * @param string $contents
     * @return mixed
     */
    public function callBlockRenderEnd($blockName, $contents)
    {
        throw new \Exception("Not implemented");
    }
}
