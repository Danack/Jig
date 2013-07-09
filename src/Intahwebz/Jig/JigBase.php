<?php


namespace Intahwebz\Jig;

use Intahwebz\View;

/**
 * Class JigBase
 * @package Intahwebz\Jig
 */
abstract class JigBase {

	/**
	 * @var View
	 */
	protected $view;

    abstract function renderInternal();

    /**
     * @param $view
     */
    public function render($view) {
		$this->view = $view;
		$this->renderInternal();
	}

    /**
     * @param $name
     * @return mixed
     */
    function getVariable($name) {
		return $this->view->getVariable($name);
	}

    /**
     * @param $functionName
     */
    function call($functionName) {
		$functionArgs = func_get_args();
		//todo - if this template has $functionName - call it?
		return $this->view->call($functionArgs);
	}
}





?>