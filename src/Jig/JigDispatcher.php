<?php

namespace Jig;

use Jig\Converter\JigConverter;
use Auryn\Injector;

/**
 * Class JigDispatcher Allows compiled templates to be rendered by using
 * Auryn as a service locator.
 *
 * @package Jig
 */
class JigDispatcher extends Jig
{
    /**
     * @var \Auryn\Injector
     */
    private $injector;

    public function __construct(
        JigConfig $jigConfig,
        Injector $injector,
        JigRender $jigRender = null
    ) {
        if ($jigRender == null) {
            $jigRender = new JigRender($jigConfig);
        }

        parent::__construct($jigConfig, $jigRender);
        $this->injector = $injector;
        $this->injector->share($jigRender);
    }

    /**
     * @param $templateFilename
     * @throws JigException
     * @return string
     */
    public function renderTemplateFile($templateFilename)
    {
        $this->jigRender->checkTemplateCompiled($templateFilename);
        $className = $this->jigConfig->getFQCNFromTemplateName($templateFilename);
        $contents = $this->injector->execute([$className, 'render']);

        return $contents;
    }

    /**
     * Renders
     * @param $templateString string The template to compile.
     * @param $objectID string An identifying string to name the generated class and so the
     * generated PHP file. It must be a valid class name i.e. may not start with a digit.
     * @return string
     * @throws \Exception
     */
    public function renderTemplateFromString($templateString, $objectID)
    {
        $className = $this->jigRender->getParsedTemplateFromString($templateString, $objectID);
        $contents = $this->injector->execute([$className, 'render']);

        return $contents;
    }

    /**
     * @param $plugin
     * @throws \Auryn\InjectorException
     */
    public function addPlugin($plugin)
    {
        $this->injector->share($plugin);
        $this->addDefaultPlugin(get_class($plugin));
    }
}
