<?php

namespace Jig;

use Jig\Converter\JigConverter;
use Auryn\Injector;
use Jig\Escaper;
use Zend\Escaper\Escaper as ZendEscaper;
use Jig\Bridge\ZendEscaperBridge;

/**
 * Class JigDispatcher Allows compiled templates to be rendered by using
 * Auryn as a service locator.
 *
 * @package Jig
 */
class JigDispatcher extends Jig
{
    /** @var \Auryn\Injector */
    private $injector;
    
    public $escaper = null;

    public function __construct(
        JigConfig $jigConfig,
        Injector $injector,
        JigConverter $jigConverter = null,
        Escaper $escaper = null
    ) {
        parent::__construct($jigConfig, $jigConverter);
        $this->injector = $injector;
        $this->injector->alias('Jig\Jig', get_class($this));
        $this->injector->share($this);
        if ($escaper === null) {
            $escaper = new ZendEscaperBridge(new ZendEscaper());
        }
        $this->injector->alias('Jig\Escaper', get_class($escaper));
        $this->injector->share($escaper);
        
        $this->escaper = $escaper;
    }

    /**
     * @param $templateFilename
     * @throws JigException
     * @return string
     */
    public function renderTemplateFile($templateFilename)
    {
        $this->compile($templateFilename);
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
        $className = $this->getParsedTemplateFromString($templateString, $objectID);
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
