<?php

namespace Jig;

/**
 * Interface Plugin
 * The interface that all plugins must implement to be a valid Jig plugin.
 * @package Jig
 */
interface Plugin
{

    /**
     * Return the list of blocks provided by this plugin.
     * @return string[]
     */
    public static function getBlockRenderList();

    /**
     * Return the list of filters provided by this plugin.
     * @return string[]
     */
    public static function getFilterList();

    /**
     * Return the list of functions provided by this plugin.
     * @return string[]
     */
    public static function getFunctionList();

    /**
     * Call the function named 'functionName' with a set of parameters
     * @param $functionName
     * @param array $params
     * @return mixed
     */
    public function callFunction($functionName, array $params);

    /**
     * Call the filter named 'filterName' for the string.
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string);

    /**
     * Call the start function for the block named 'blockName' with any extra provided
     * parameters.
     * @param $blockName
     * @param string $extraParam
     * @return mixed
     */
    public function callBlockRenderStart($blockName, $extraParam);

    /**
     * Call the end function for the block named 'blockName' with any extra provided
     * parameters.
     * @param $blockName
     * @param string $contents
     * @return mixed
     */
    public function callBlockRenderEnd($blockName, $contents);
}
