<?php

namespace Jig;

class EscapeException extends \Exception
{
    const IMPLICIT_ARRAY_TO_STRING = "Cannot escape an array. String or object with __toString required.";

    const E_OBJECT_NOT_STRING = 1;
    const E_ARRAY_NOT_STRING = 2;

    public static function fromBadObject($object)
    {
        $message = sprintf(
            "Object of type %s does not have a __toString method. Cannot use it as a string.",
            get_class($object)
        );

        return new EscapeException($message, EscapeException::E_OBJECT_NOT_STRING);
    }

    public static function fromBadArray()
    {
        return new EscapeException(
            EscapeException::IMPLICIT_ARRAY_TO_STRING,
            EscapeException::E_ARRAY_NOT_STRING
        );
    }
}
