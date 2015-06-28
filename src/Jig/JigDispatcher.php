<?php

namespace Jig;

use Jig\Converter\JigConverter;


class JigDispatcher extends Jig
{

    private $injector;

    private $jigRender;

    public function __construct(
        JigConfig $jigConfig,
        JigRender $jigRender,
        JigConverter $jigConverter,
        \Auryn\Injector $injector)
    {
        parent::__construct($jigConfig, $jigConverter);
        $this->injector = $injector;
        $this->jigRender = $jigRender;
    }
    
    
       /**
     * @param $templateFilename
     * @throws JigException
     * @return string
     */
    public function renderTemplateFile($templateFilename)
    {
        $injector = clone $this->injector;
        $injector->share($this->jigRender);
        $injector->share($this->jigConverter);
        
        $this->jigRender->checkTemplateCompiled($templateFilename);
        $className = $this->jigConfig->getFullClassname($templateFilename);
        $contents = $injector->execute([$className, 'render']);

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
}