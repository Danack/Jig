<?php


namespace Jig;


interface Plugin {

    public static function getFilterList();
    
    public static function getFunctionList();
    
    public static function getBlockRenderList();

    public static function hasBlock($blockName);
    
    public static function hasFunction($functionName);
    
    /**
     * @param $functionName
     * @param array $params
     * @return mixed
     */
    public function callFunction($functionName, array $params);

    /**
     * @param string $filterName The name of the filter.
     * @param string $string
     * @return mixed
     */
    public function callFilter($filterName, $string);

    /**
     * @param $blockName
     * @param string $extraParam
     * @return mixed
     */
    public function callBlockRenderStart($blockName, $extraParam);

    /**
     * @param $blockName
     * @param string $contents
     * @return mixed
     */
    public function callBlockRenderEnd($blockName, $contents);
    
}

