<?php

namespace Jig;

use Jig\JigException;

/**
 * Class JigCompilePath
 *
 * The path that templates which are compiled into PHP files should be written
 */
class JigCompilePath
{
    private $path;

    public function __construct($path)
    {
        if ($path === null) {
            throw new JigException(
                "Path cannot be null for class JigCompilePath"
            );
        }
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
