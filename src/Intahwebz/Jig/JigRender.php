<?php


namespace Intahwebz\Jig;

use Intahwebz\SafeAccess;
use Intahwebz\ViewModel;

use Intahwebz\Jig\Converter\JigConverter;


/**
 * Class JigRender
 * 
 * @package Intahwebz\Jig
 */
class JigRender {
    
    use SafeAccess;

    const COMPILED_NAMESPACE    = "Intahwebz\\PHPCompiledTemplate";

    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';

    /**
     * @var ViewModel
     */
    private $viewModel;

    private $mappedClasses = array();
    public $templatePath = null;
    public $compilePath = null;
    private $extension = ".tpl";

    /**
     * @var Converter\JigConverter
     */
    private $jigConverter;

    /**
     * @var \Auryn\Provider
     */
    private $provider;
    
    private $compileCheck;

    function __construct(JigConfig $jigConfig, \Auryn\Provider $provider) {
        $this->jigConverter = new JigConverter();
        $this->templatePath = $jigConfig->templateSourceDirectory;
        $this->compilePath = $jigConfig->templateCompileDirectory;
        $this->extension = $jigConfig->extension;
        //TODO - don't copy vars around
        $this->compileCheck = $jigConfig->compileCheck;
        $this->provider = $provider;
    }

    function getTemplatePath() {
        return $this->templatePath;
    }

    function bindViewModel(ViewModel $viewModel) {
        $this->viewModel = $viewModel;
    }
    
    /**
     * @param $compileCheck
     */
    function setCompileCheck($compileCheck) {
        $this->compileCheck = $compileCheck;
    }


    /**
     * @param $templateString
     * @param $templateID - Must be a valid PHP class name i.e. cannot start with digit
     * @return string
     */
    function captureRenderTemplateString($templateString, $templateID) {        
        //TODO - check templateID doesn't start with a digit
        ob_start();
        $this->renderTemplateFromString($templateString, $templateID);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * @param $filename
     */
    function includeFile($filename) {
        $this->renderTemplateFile($filename);
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
            $this->getParsedTemplate($mappedClass, $this->mappedClasses, false);
        }
        
        $proxiedClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, true);

        if (class_exists($proxiedClassName) == false) {
            //this is needed if dynamic extended classes are used out of order
            $this->getParsedTemplate($mappedClass, $this->mappedClasses, true);
        }
        
        return $proxiedClassName;
    }

    /**
     * @param $templateString
     * @param $objectID
     * @throws \Exception
     */
    function renderTemplateFromString($templateString, $objectID) {
        try{
            $className = $this->getParsedTemplateFromString($templateString, $objectID, $this->mappedClasses);
            
            $template = new $className($this, $this->viewModel);
            /** @var $template \Intahwebz\Jig\JigBase */
            $template->render();
        }
        catch(JigException $je) {
            //Just rethrow it to keep the stack trace the same
            throw $je;
        }
        catch(\Exception $e){
            //Catch all exceptions, but throw as a JigException to allow only code to only
            //catch the template errors.
            throw new JigException("Failed to render template: ".$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * TODO - remove this or at least remove the capture. It should always be captured
     * and returned, and the calling function can display it if it completes.
     * @param $templateFilename
     * @param bool $capture
     * @return string
     */
    function renderTemplateFile($templateFilename, $capture = false) {
        $contents = '';

        if ($capture == true) {
            ob_start();
        }

        $className = $this->getParsedTemplate($templateFilename, $this->mappedClasses);

        $template = new $className($this, $this->viewModel);
        /** @var $template \Intahwebz\Jig\JigBase */

        $injections = $template->getInjections();

        $injectionValues = array();

        $lowried = $this->viewModel->getMergedParams();

        foreach ($injections as $name => $value) {
            $injectionValues[$name] = $this->provider->make($value, $lowried);
        }

        $template->inject($injectionValues);

        $template->render();

        if ($capture == true) {
            $contents = ob_get_contents();
            ob_end_clean();
        }

        return $contents;
    }

    function bindBlock($blockName, Callable $startCallback, Callable $endCallback) {
        $this->jigConverter->bindBlock($blockName, $startCallback, $endCallback);
    }

    function bindProcessedBlock($blockName, $endFunctionName, $startFunctionName = null) {
        $this->jigConverter->bindProcessedBlock($blockName, $endFunctionName, $startFunctionName);
    }

    function clearCompiledFile(){
        //TODO - implement this.
        // @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        throw new \Exception("clearCompiledFile has not been implemented yet.");
    }

    /**
     * @param $templateFilename
     * @param $extension
     * @return bool
     */
    function isGeneratedFileOutOfDate($templateFilename, $extension) {
        $templateFullFilename = $this->templatePath.$templateFilename.'.'.$extension;
        $className = $this->jigConverter->getClassNameFromFilename($templateFilename);
        $classPath = $this->compilePath.'/'.self::COMPILED_NAMESPACE.'/'.$className.'.php';
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
     * @return \Intahwebz\Jig\Converter\ParsedTemplate
     */
    function prepareTemplateFromFile($templateFilename, $extension) {
        $templateFullFilename = $this->templatePath.$templateFilename.'.'.$extension;
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
    function getParsedTemplate($templateFilename, $mappedClasses, $proxied = false) {

        $className = $this->jigConverter->getNamespacedClassNameFromFileName($templateFilename, $proxied);

        //If not cached
        if ($this->compileCheck == JigRender::COMPILE_CHECK_EXISTS) {
            if (class_exists($className) == true) {
                return $className;
            }
        }

        if ($this->compileCheck == JigRender::COMPILE_CHECK_MTIME) {
            //Check file time here....
            if ($this->isGeneratedFileOutOfDate($templateFilename, $this->extension) == false) {
                if (class_exists($className) == true) {
                    return $className;
                }
            }
        }

        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename, $this->extension);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->compilePath,
            $proxied
        );

        $extendsClass = $parsedTemplate->getExtends();

        if ($extendsClass) {
            $this->getParsedTemplate($extendsClass, $mappedClasses);
        }
        else if ($parsedTemplate->getDynamicExtends()) {
            if (array_key_exists($parsedTemplate->getDynamicExtends(), $mappedClasses) == false) {
                throw new JigException("File $templateFilename is trying to proxy [".$parsedTemplate->getDynamicExtends()."] but that doesn't exist in the mappedClasses.");
            }

            $dynamicExtendsClass = $mappedClasses[$parsedTemplate->getDynamicExtends()];

            //Generate this twice - once for real, once as a proxy.
            $this->getParsedTemplate($dynamicExtendsClass, $mappedClasses, false);
            
            //TODO - once the proxy generating is working, this can be removed?
            $this->getParsedTemplate($dynamicExtendsClass, $mappedClasses, true);
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
     * @param $mappedClasses
     * @return mixed
     */
    function getParsedTemplateFromString($templateString, $cacheName, $mappedClasses) {
        $templateString = str_replace( "<?php", "&lt;php", $templateString);
        $templateString = str_replace( "?>", "?&gt;", $templateString);

        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $parsedTemplate->saveCompiledTemplate($this->compilePath, false);
        $extendsFilename = $parsedTemplate->getExtends();

        if ($extendsFilename) {
            $this->getParsedTemplate($extendsFilename, $mappedClasses);
        }

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }


    /**
     * @param $blockName
     * @return mixed|null
     */
    function startProcessedBlock($blockName) {
        ob_start();
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
