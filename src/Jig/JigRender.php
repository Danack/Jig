<?php


namespace Jig;

use Jig\Converter\JigConverter;

JigFunctions::load();

/**
 * Class JigRender
 *
 */
class JigRender {
    
    const COMPILED_NAMESPACE    = "Jig\\PHPCompiledTemplate";

    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';

    /**
     * @var ViewModel
     */
    private $viewModel;

    /**
     * @var array The class map for dynamically extending classes
     */
    private $mappedClasses = array();

    /**
     * @var Converter\JigConverter
     */
    private $jigConverter;

    /**
     * @var \Auryn\Provider
     */
    private $provider;

    function __construct(JigConfig $jigConfig, \Auryn\Provider $provider) {
        $this->jigConfig = clone $jigConfig;
        $this->jigConverter = new JigConverter();
        $this->provider = $provider;
    }


    /**
     * @param ViewModel $viewModel
     */
    function bindViewModel(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }
    
    /**
     * @param $filename
     */
    function includeFile($filename) {
        $contents = $this->renderTemplateFile($filename);
        return $contents;
    }

    /**
     * Sets the class map for dynamically extending classes
     *
     * e.g. standardLayout => standardJSONLayout or standardHTMLLayout
     *
     * @param $classMap
     */
    function mapClasses($classMap){
        $this->mappedClasses = array_merge($this->mappedClasses, $classMap);
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
            $this->getParsedTemplate($mappedClass, false);
        }
        
        $proxiedClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, true);

        if (class_exists($proxiedClassName) == false) {
            //this is needed if dynamic extended classes are used out of order
            $this->getParsedTemplate($mappedClass, true);
        }
        
        return $proxiedClassName;
    }

    /**
     * @param $templateString
     * @param $objectID
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
    function renderTemplateFile($templateFilename) {
        $contents = '';

        ob_start();
        
        try {
            $className = $this->getParsedTemplate($templateFilename);
    
            $template = new $className($this, $this->viewModel);
            /** @var $template \Jig\JigBase */
            $injections = $template->getInjections();
            $injectionValues = array();
            $lowried = [];

            if ($this->viewModel) {
                $lowried = $this->viewModel->getMergedParams();
            }

            //TODO - there replace this with $provider->execute
            foreach ($injections as $name => $value) {
                $injectionValues[$name] = $this->provider->make($value, $lowried);
            }
    
            $template->inject($injectionValues);
            $foo = $template->render();
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
     * @param $blockName
     * @param callable $startCallback
     * @param callable $endCallback
     */
    function bindBlock($blockName, Callable $startCallback, Callable $endCallback) {
        $this->jigConverter->bindBlock($blockName, $startCallback, $endCallback);
    }

    /**
     * @param $blockName
     * @param $endFunctionName
     * @param null $startFunctionName
     */
    function bindProcessedBlock($blockName, $endFunctionName, $startFunctionName = null) {
        $this->jigConverter->bindProcessedBlock($blockName, $endFunctionName, $startFunctionName);
    }

    /**
     * @throws \Exception
     */
    function clearCompiledFile() {
        //TODO - implement this.
        // @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        throw new \Exception("clearCompiledFile has not been implemented yet.");
    }

    /**
     * @param $templateFilename
     * @param $extension
     * @return bool
     */
    function isGeneratedFileOutOfDate($templateFilename) {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $className = $this->jigConverter->getClassNameFromFilename($templateFilename);
        $classPath = $this->jigConfig->getCompiledFilename(self::COMPILED_NAMESPACE, $className);
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
        //$templateFullFilename = $this->jigConfig->templateSourceDirectory.$templateFilename.'.'.$extension;
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
     * @return string The full classname of the generated file
     */
    function getParsedTemplate($templateFilename, $proxied = false) {

        $className = $this->jigConverter->getNamespacedClassNameFromFileName($templateFilename, $proxied);

        if ($this->jigConfig->compileCheck == JigRender::COMPILE_CHECK_EXISTS) {
            if (class_exists($className) == true) {
                return $className;
            }
        }

        if ($this->jigConfig->compileCheck == JigRender::COMPILE_CHECK_MTIME) {
            if ($this->isGeneratedFileOutOfDate($templateFilename) == false) {
                if (class_exists($className) == true) {
                    return $className;
                }
            }
        }

        //Either class file did not exist or it was out of date. 
        return $this->parseTemplate($className, $templateFilename, $proxied);
    }

    function parseTemplate($className, $templateFilename, $proxied) {
        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            $proxied
        );

        $extendsClass = $parsedTemplate->getExtends();

        if ($extendsClass) {
            $this->getParsedTemplate($extendsClass);
        }
        else if ($parsedTemplate->getDynamicExtends()) {
            if (array_key_exists($parsedTemplate->getDynamicExtends(), $this->mappedClasses) == false) {
                throw new JigException("File $templateFilename is trying to proxy [".$parsedTemplate->getDynamicExtends(
                    )."] but that doesn't exist in the mappedClasses."
                );
            }

            $dynamicExtendsClass = $this->mappedClasses[$parsedTemplate->getDynamicExtends()];

            //Generate this twice - once for real, once as a proxy.
            $this->getParsedTemplate($dynamicExtendsClass, false);

            //TODO - once the proxy generating is working, this can be removed?
            $this->getParsedTemplate($dynamicExtendsClass, true);
        }

        if (class_exists($className, false) == false) {
            if (function_exists('opcache_invalidate') == true) {
                opcache_invalidate($outputFilename);
            }
            /** @noinspection PhpIncludeInspection */
            require($outputFilename);
        }
        else {
            //Warn - file was compiled when class already exists?
        }

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }
    

    /**
     *
     * This is an entry point
     * @param $templateString
     * @param $cacheName
     * @return mixed
     */
    function getParsedTemplateFromString($templateString, $cacheName) {
        $templateString = str_replace( "<?php", "&lt;php", $templateString);
        $templateString = str_replace( "?>", "?&gt;", $templateString);

        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            false
        );
        $extendsFilename = $parsedTemplate->getExtends();

        if ($extendsFilename) {
            $this->getParsedTemplate($extendsFilename);
        }

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }


    /**
     * @param $blockName
     * @return mixed|null
     */
    function startProcessedBlock($blockName) {
        $blockFunction = $this->jigConverter->getProcessedBlockFunction($blockName);
        $startFunctionCallable = $blockFunction[0];

        if ($startFunctionCallable) {
            echo call_user_func($startFunctionCallable);
        }
    }

    /**
     * @param $blockName
     * @return mixed
     */
    function endProcessedBlock($blockName) {
        $contents = ob_get_contents();
        ob_end_clean();

        $blockFunction = $this->jigConverter->getProcessedBlockFunction($blockName);

        $endFunctionCallable = $blockFunction[1];

        echo call_user_func($endFunctionCallable, $contents);
    }
    
}
