<?php


namespace Jig;

/**
 * Class DynamicTemplateExtender
 *
 * All templates that dynamicExtends do so through this file, coupled with
 * a Proxied version of the class they are extending.
 * e.g. var/src/JigTemplate/standardContentPanel
 * var/src/JigTemplate/ProxiedstandardContentPanel
 * @package Jig
 */

class DynamicTemplateExtender extends JigBase
{
    /**
     * @var JigBase
     */
    private $parentInstance = null;

    /**
     * @param JigBase $parentInstance
     */
    public function setParentInstance(JigBase $parentInstance)
    {
        $this->parentInstance = $parentInstance;
    }

    /**
     * @param $name
     * @param array $arguments
     * @return mixed
     * @throws JigException
     */
    public function __call($name, array $arguments)
    {
        if ($this->parentInstance == null) {
            throw new JigException("Parent Instance is null in Proxied class in renderInternal.");
        }

        return call_user_func_array([$this->parentInstance, $name], $arguments);
    }

    /**
     * @return array
     * @throws JigException
     */
    public function getInjections()
    {
        if ($this->parentInstance == null) {
            throw new JigException("Parent Instance is null in Proxied class in renderInternal.");
        }

        return $this->parentInstance->getInjections();
    }

    public function inject($injectionValues)
    {
        parent::inject($injectionValues);
        $this->parentInstance->inject($injectionValues);
    }


    /**
     * @throws JigException
     */
    public function renderInternal()
    {
        if ($this->parentInstance == null) {
            throw new JigException("Instance is null in Proxied class in renderInternal.");
        }

        return $this->parentInstance->renderInternal();
    }
}
