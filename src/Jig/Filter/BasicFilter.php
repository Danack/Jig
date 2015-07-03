<?php

namespace Jig\Filter;

use Jig\Filter;
use Jig\JigException;

class BasicFilter implements Filter {

    /**
     * @param $filterName
     * @return bool
     */
    public static function hasFilter($filterName)
    {
        return method_exists(get_class(), $filterName);
    }

    public static function getFilterList()
    {
        return ['upper', 'lower'];
    }
    

    /**
     * @param $filterName
     * @param string $string
     * @return mixed
     * @throws JigException
     */
    public function call($filterName, $string)
    {
        if (method_exists($this, $filterName) == true) {
            return call_user_func([$this, $filterName], $string);
        }

        throw new JigException(
            "No filter called [$filterName] in BasicTemplateHelper"
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

