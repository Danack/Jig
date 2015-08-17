<?php

namespace Jig;

/**
 * Class JigException
 */
class JigException extends \Exception
{
    const IMPLICIT_ARRAY_TO_STRING = "An array cannot be used as a string.";

    /**
     * No guarantee is made to the value of these entries between different
     * versions of this library.
     */
    const FILTER_NO_INFO = 1;
    const INTERNAL_ERROR = 2;   //something should never happen and represent a bug in the Jig library
    const FAILED_TO_COMPILE = 3;
    const FAILED_TO_RENDER = 4;
    const UNKNOWN_FILTER = 5;
    const UNKNOWN_BLOCK = 6;
    const UNKNOWN_VARIABLE = 7;
    const INJECTION_ERROR = 8;
}
