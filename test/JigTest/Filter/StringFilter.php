<?php

namespace JigTest\Filter;

use Jig\Filter;

class StringFilter implements Filter {

    public function upper($string)
    {
        return strtoupper($string);
    }
}

