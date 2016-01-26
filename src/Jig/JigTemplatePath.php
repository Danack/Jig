<?php

namespace Jig;

use Jig\JigException;

/**
 * Class JigTemplatePath
 *
 *  The path that templates are stored.
 */
class JigTemplatePath
{
    private $path;

    public function __construct($path)
    {
        if ($path === null) {
            throw new JigException(
                "Path cannot be null for class TemplatePath"
            );
        }
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}
