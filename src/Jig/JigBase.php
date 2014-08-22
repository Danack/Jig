<?php


namespace Jig;


/**
 * Class JigBase
 * This is the base class that all compiled templates are extended from.
 * There should be no reason to touch this class, unless you're debugging why
 * a template isn't working.
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

    function __construct(JigRender $jigRender, ViewModel $viewModel = null){
        $this->viewModel = $viewModel;
        $this->jigRender = $jigRender;
    }

    /**
     * @return mixed
     */
    abstract function renderInternal();

    /**
     * @param $injectionValues
     */
    function inject($injectionValues) {
        foreach ($injectionValues as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @return array
     */
    function getInjections() {
        return [];
    }

    /**
     * @internal param $view
     */
    public function render() {
        return $this->renderInternal();
    }

    /**
     * @param $name
     * @return mixed
     */
    function getVariable($name) {
        if ($this->viewModel) {
            return $this->viewModel->getVariable($name);
        }
        
        return null;
    }

    /**
     * @param $placeHolder
     * @internal param array $functionArgs
     * @return mixed|void
     */
    function call($placeHolder) {
        $functionArgs = func_get_args();
        //todo - if this template has $functionName - call it?
        
        if ($this->viewModel) { 
            return $this->viewModel->call($functionArgs);
        }

        return null;
    }
}

