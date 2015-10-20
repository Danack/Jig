<?php


namespace Jig\Plugin;

use Jig\JigException;

class EmptyPlugin implements \Jig\Plugin
{
    /**
     * Return the list of blocks provided by this plugin.
     * @return string[]
     */
    public static function getBlockRenderList()
    {
        return [];
    }

    /**
     * Return the list of filters provided by this plugin.
     * @return string[]
     */
    public static function getFilterList()
    {
        return [];
    }

    /**
     * Return the list of functions provided by this plugin.
     * @return string[]
     */
    public static function getFunctionList()
    {
        return [];
    }

    /**
     * Call the function named 'functionName' with a set of parameters
     * @param $functionName
     * @param array $params
     * @return mixed
     */
    public function callFunction($functionName, array $params)
    {
        throw new JigException("callFunction called for unknown function '$functionName'");
    }

    /**
     * Call the filter named 'filterName' for the string.
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string)
    {
        throw new JigException("callFilter called for unknown function '$filterName'");
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
        throw new JigException("callBlockRenderStart called for unknown block '$blockName'");
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
        throw new JigException("callBlockRenderEnd called for unknown block '$blockName'");
    }
}
