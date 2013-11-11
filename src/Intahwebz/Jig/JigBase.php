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
        $this->init();
    }

    abstract function renderInternal();

    function init() {
        //Override stuff.
    }

    function inject($injectionValues) {
        foreach ($injectionValues as $name => $value) {
            $this->{$name} = $value;
        }
    }

    function getInjections() {
        return [];
    }

    /**
     * @internal param $view
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
     * @param $placeHolder
     * @internal param array $functionArgs
     * @return mixed|void
     */
    function call($placeHolder) {
        $functionArgs = func_get_args();
        //todo - if this template has $functionName - call it?
        return $this->viewModel->call($functionArgs);
    }
}

