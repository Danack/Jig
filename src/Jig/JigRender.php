<?php


namespace Jig;

use Jig\Converter\JigConverter;
use Auryn\Injector; 

JigFunctions::load();

/**
 * Class JigRender
 * 
 * 
 */
class JigRender {

    /**
     * @var array The class map for dynamically extending classes
     */
    private $mappedClasses = array();

    /**
     * @var Converter\JigConverter
     */
    private $jigConverter;

    /**
     * @var \Auryn\Injector
     */
    private $injector;

    /**
     * @var JigConfig
     */
    private $jigConfig;

    function __construct(
        Jigconfig $jigConfig,
        JigConverter $jigConverter,
        Injector $injector,
        ViewModel $viewModel,
        array $mappedClasses
    ) {
        $this->jigConfig = $jigConfig;
        $this->jigConverter = $jigConverter;
        $this->injector = $injector;
        $this->viewModel = $viewModel;
        $this->mappedClasses = $mappedClasses;
    }

    /**
     * @param $filename
     */
    function includeFile($filename) {
        $contents = $this->renderTemplateFile($filename, $this->viewModel);
        return $contents;
    }


    /**
     * @param $className
     * @throws JigException
     * @return mixed
     */
    function getProxiedClass($className) {
        if (array_key_exists($className, $this->mappedClasses) == false) {
            throw new JigException("Class '$className' not listed in mappedClasses, cannot proxy.");
        }

        $mappedClass = $this->mappedClasses[$className];
        $originalClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, false);
        if (class_exists($originalClassName) == false) {
            $this->checkTemplateCompiled($mappedClass, false);
        }
        
        $proxiedClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, true);

        if (class_exists($proxiedClassName) == false) {
            //this is needed if dynamic extended classes are used out of order
            $this->checkTemplateCompiled($mappedClass, true);
        }
        
        return $proxiedClassName;
    }

    /**
     * Renders 
     * @param $templateString string The template to compile.
     * @param $objectID string An identifying string to name the generated class and so the generated PHP file. It must be a valid class name i.e. may not start with a digit. 
     * @param $viewModel ViewModel A viewmodel to (optional)
     * @return string
     * @throws \Exception
     */
    function renderTemplateFromString($templateString, $objectID) {
        ob_start();
        try{
            $className = $this->getParsedTemplateFromString($templateString, $objectID);
            $template = new $className($this, $this->viewModel);
            /** @var $template \Jig\JigBase */
            $template->render();
            $contents = ob_get_contents();
            ob_end_clean();
            return $contents;
        }
        catch(JigException $je) {
            ob_end_clean();
            //Just rethrow it to keep the stack trace the same
            throw $je;
        }
        catch(\Exception $e){
            ob_end_clean();
            //Catch all exceptions, but throw as a JigException to allow code to only
            //catch the template errors.
            throw new JigException("Failed to render template: ".$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $templateFilename
     * @param bool $capture
     * @return string
     */
    public function renderTemplateFile($templateFilename) {
        $contents = '';

        ob_start();
        
        try {
            $this->checkTemplateCompiled($templateFilename);
            $className = $this->jigConfig->getFullClassname($templateFilename);
            $template = new $className($this, $this->viewModel);
            /** @var $template \Jig\JigBase */
            $injections = $template->getInjections();
            $injectionValues = array();
            
            //TODO - This whole code block could be refactored to
            //do the injection in one step, which would be cleaner.
            foreach ($injections as $name => $value) {
                $injectionValues[$name] = $this->injector->make($value);
            }
    
            $template->inject($injectionValues);
            $template->render();
            $contents = ob_get_contents();
        }
        catch(\Exception $e) {
            //TODO - should put the bit that gave an error somewhere?
            //$contents = ob_get_contents();
            ob_end_clean();
            
            throw new JigException(
                "Failed to render template: ".$e->getMessage(), 
                $e->getCode(), 
                $e
            ); 
        }

        ob_end_clean();

        return $contents;
    }

    /**
     * @param $templateName
     * @return string
     */
    function getCompileFilename($templateName) {
        $className = $this->jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $this->jigConfig->getCompiledFilename($className);
        
        return $compileFilename;
    }

    /**
     * @param $templateFilename
     * @return bool
     */
    function isGeneratedFileOutOfDate($templateFilename) {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $classPath = getCompileFilename($templateFilename, $this->jigConverter, $this->jigConfig);
        $classPath = str_replace('\\', '/', $classPath);
        $templateTime = @filemtime($templateFullFilename);
        $classTime = @filemtime($classPath);
        
        if ($classTime < $templateTime) {
            return true;
        }

        return false;
    }

    /**
     * @param $templateFilename
     * @param $extension
     * @throws JigException
     * @return \Jig\Converter\ParsedTemplate
     */
    function prepareTemplateFromFile($templateFilename) {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $fileLines = @file($templateFullFilename);

        if ($fileLines === false) {
            throw new JigException("Could not open template [".$templateFullFilename."] for reading.");
        }

        try {
            $parsedTemplate = $this->jigConverter->createFromLines($fileLines);
            $className = $this->jigConverter->getClassNameFromFilename($templateFilename);
            $parsedTemplate->setClassName($className);

            return $parsedTemplate;
        }
        catch(JigException $pte) {
            throw new JigException("Error in file $templateFilename: ".$pte->getMessage(), $pte->getCode(), $pte);
        }
    }

    /**
     * @param $templateFilename
     * //TODO rename this to a better name.
     * //TODO this duplicates a significant portion of getParsedTemplateFromString
     * @param $mappedClasses
     * @param bool $proxied
     * @throws JigException
     */
    function checkTemplateCompiled($templateFilename, $proxied = false) {
        if ($this->jigConfig->compileCheck == Jig::COMPILE_NEVER) {
            //This is useful when debugging templates. It allows you to edit the
            //generated code, without having it over-written.
            return;
        }
        
        $className = $this->jigConverter->getNamespacedClassNameFromFileName($templateFilename, $proxied);
        if ($this->jigConfig->compileCheck == Jig::COMPILE_CHECK_EXISTS) {
            if (class_exists($className) == true) {
                return;
            }
        }

        if ($this->jigConfig->compileCheck == Jig::COMPILE_CHECK_MTIME) {
            if ($this->isGeneratedFileOutOfDate($templateFilename) == false) {
                if (class_exists($className) == true) {
                    return;
                }
            }
        }

        //Either class file did not exist or it was out of date. 
        $this->compileTemplate($className, $templateFilename, $proxied);
    }

    /**
     * @param $className
     * @param $templateFilename
     * @param $proxied
     * @throws JigException
     */
    function compileTemplate($className, $templateFilename, $proxied) {
        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            $proxied
        );

        $extendsTemplate = $parsedTemplate->getExtends();

        if ($extendsTemplate) {
            $this->checkTemplateCompiled($extendsTemplate);
        }
        else if ($parsedTemplate->getDynamicExtends()) {
            if (array_key_exists($parsedTemplate->getDynamicExtends(), $this->mappedClasses) == false) {
                throw new JigException("File $templateFilename is trying to proxy [".$parsedTemplate->getDynamicExtends(
                    )."] but that doesn't exist in the mappedClasses."
                );
            }

            $dynamicExtendsClass = $this->mappedClasses[$parsedTemplate->getDynamicExtends()];

            //Generate this twice - once for real, once as a proxy.
            $this->checkTemplateCompiled($dynamicExtendsClass, false);

            //TODO - once the proxy generating is working, this can be removed?
            $this->checkTemplateCompiled($dynamicExtendsClass, true);
        }

        if (class_exists($className, false) == false) {
            if (function_exists('opcache_invalidate') == true) {
                opcache_invalidate($outputFilename);
            }
            //This is fucking stupid. We should be able to auto-load the class
            //if an only if it is required. But the Composer autoloader caches
            //the 'class doesn't exist' result from earlier, which means we
            //have to load it by hand.
            /** @noinspection PhpIncludeInspection */
            require($outputFilename);
        }
        else {
            //Warn - file was compiled when class already exists?
        }
    }
    

    /**
     *
     * This is an entry point
     * @param $templateString
     * @param $cacheName
     * @return mixed
     * 
     */
    function getParsedTemplateFromString($templateString, $cacheName) {
        $templateString = str_replace( "<?php", "&lt;php", $templateString);
        $templateString = str_replace( "?>", "?&gt;", $templateString);

        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            false
        );
        $extendsFilename = $parsedTemplate->getExtends();

        if ($extendsFilename) {
            $this->checkTemplateCompiled($extendsFilename);
        }

        //This is fucking stupid. We should be able to auto-load the class
        //if and only if it is required. But the Composer autoloader caches
        //the 'class doesn't exist' result from earlier, which means we
        //have to load it by hand.
        /** @noinspection PhpIncludeInspection */
        require($outputFilename);

        return $this->jigConfig->getFullClassname($parsedTemplate->getClassName());
    }


    /**
     * @param $blockName
     * @return mixed|null
     */
    function startRenderBlock($blockName, $segmentText) {
        $blockFunction = $this->jigConverter->getProcessedBlockFunction($blockName);
        $startFunctionCallable = $blockFunction[0];

        if ($startFunctionCallable) {
            echo call_user_func($startFunctionCallable, $segmentText);
        }
    }

    /**
     * @param $blockName
     * @return mixed
     */
    function endRenderBlock($blockName) {
        $contents = ob_get_contents();
        ob_end_clean();
        $blockFunction = $this->jigConverter->getProcessedBlockFunction($blockName);
        $endFunctionCallable = $blockFunction[1];
        echo call_user_func($endFunctionCallable, $contents);
    }
}
