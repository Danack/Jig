<?php


namespace Jig;

/**
 * Interface TemplateHelper
 * Allows helper functions be pulled into a template.
 * @package Jig
 */
interface TemplateHelper
{
    /**
     * @param $functionName
     * @return bool
     */
    public function hasFunction($functionName);

    /**
     * @param $functionName
     * @param array $params
     * @return mixed
     */
    public function call($functionName, array $params);
}
