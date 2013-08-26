<?php


namespace Intahwebz\Jig;

use Intahwebz\ViewModel;

/**
 * Class JigBase
 * @package Intahwebz\Jig
 */
abstract class JigBase {

	/**
	 * @var ViewModel
	 */
	protected $viewModel;

    /**
     * @var JigRender
     */
    protected   $jigRender;
    
    function __construct(ViewModel $viewModel, $jigRender){
        $this->viewModel = $viewModel;
        $this->jigRender = $jigRender;
    }

    abstract function renderInternal();

    /**
     * @param $view
     */
    public function render() {
		$this->renderInternal();
	}

    /**
     * @param $name
     * @return mixed
     */
    function getVariable($name) {
		return $this->viewModel->getVariable($name);
	}

    /**
     * @param array $functionArgs
     * @return mixed|void
     */
    function call($placeHolder) {
		$functionArgs = func_get_args();
		//todo - if this template has $functionName - call it?
		return $this->viewModel->call($functionArgs);
	}
}

