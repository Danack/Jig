<?php


namespace Jig;


interface Filter {

    /**
     * @param $filterName
     * @return bool
     */
    public static function hasFilter($filterName);
    
    public static function getFilterList();

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     */
    public function call($filterName, $string);
}

