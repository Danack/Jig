<?php


namespace Jig;


interface BlockRender {

    /**
     * @param $filterName
     * @return bool
     */
    public static function hasBlock($filterName);

    public static function getBlockList();

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     */
    public function callStart($filterName, $string);

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     */
    public function callEnd($filterName, $string);
}

