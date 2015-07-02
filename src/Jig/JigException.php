<?php


namespace Jig;

/**
 * Class JigException
 */
class JigException extends \Exception {
    const IMPLICIT_ARRAY_TO_STRING = "An array cannot be used as a string.";
    
    const FILTER_NO_INFO = 1;
    //These should never happen and represent a bug in the Jig library
    const INTERNAL_ERROR = 2;
    const FAILED_TO_COMPILE = 3;
    const FAILED_TO_RENDER = 4;
    const UNKNOWN_FILTER = 5;
}
