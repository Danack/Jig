<?php

namespace Jig;

/**
 * Class JigBase
 * This is the base class that all compiled templates are extended from.
 * There should be no reason to touch this class, unless you're debugging why
 * a template isn't working.
 */
abstract class JigBase
{
    /**
     * @var ViewModel
     */
    protected $viewModel;

    /**
     * @var JigRender
     */
    protected $jigRender;

    public function __construct(JigRender $jigRender, ViewModel $viewModel = null)
    {
        $this->viewModel = $viewModel;
        $this->jigRender = $jigRender;
    }

    /**
     * @return mixed
     */
    abstract public function renderInternal();

    /**
     * @param $injectionValues
     */
    public function inject($injectionValues)
    {
        foreach ($injectionValues as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @return array
     */
    public function getInjections()
    {
        return [];
    }

    /**
     * Render this template
     * @return mixed
     */
    public function render()
    {
        return $this->renderInternal();
    }

    /**
     * Get a variable or null if it is not set.
     * @param $name
     * @return mixed
     */
    public function getVariable($name)
    {
        if ($name == 'jigRender') {
            return $this->jigRender;
        }
        
        if ($this->viewModel) {
            return $this->viewModel->getVariable($name);
        }
        
        return null;
    }

    /**
     * @param $placeHolder
     * @internal param array $functionArgs
     * @return mixed|void
     * @todo - if this template has $functionName - call it?
     */
    public function call($placeHolder)
    {
        $functionArgs = func_get_args();

        if ($this->viewModel) {
            return $this->viewModel->call($functionArgs);
        }

        return null;
    }
}
