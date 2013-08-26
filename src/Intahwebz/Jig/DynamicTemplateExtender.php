<?php


namespace Intahwebz\Jig;

use Intahwebz\Jig\JigException;

/**
 * Class DynamicTemplateExtender All templates that dynamicExtends do so through this file, coupled with
 * a Proxied version of the class they are extending. e.g. var/src/Intahwebz/JigTemplate/standardContentPanel var/src/Intahwebz/JigTemplate/ProxiedstandardContentPanel
 * @package Intahwebz\Jig
 */

class DynamicTemplateExtender extends JigBase {

	private $parentInstance = null;

	public function setParentInstance($parentInstance) {
		$this->parentInstance = $parentInstance;
	}

	public function __call($name, array $arguments) {
		if ($this->parentInstance == null) {
			throw new JigException("Parent Instance is null in Proxied class in renderInternal.");
		}

		return call_user_func_array([$this->parentInstance, $name], $arguments);
	}


    /**
     * @throws JigException
     */
    function renderInternal() {
		if ($this->parentInstance == null) {
			throw new JigException("Instance is null in Proxied class in renderInternal.");
		}

		//TODO if this.child has method renderInternal then call?
		$this->parentInstance->renderInternal();
	}
}


