<?php


namespace Intahwebz\Jig;

use Intahwebz\ViewModel;

use Intahwebz\Jig\Converter\JigConverter;
use Psr\Log\LoggerInterface;


/**
 * Class JigRender
 * 
 * @package Intahwebz\Jig
 */
class JigRender {

    const COMPILED_NAMESPACE = "Intahwebz\\PHPCompiledTemplate";

    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';

    private $viewModel;

    private $mappedClasses = array();
    public $templatePath = null;
    public $compilePath = null;
    private $extension = ".tpl";
    
    private $compileCheck;

    function __construct(LoggerInterface $logger, JigConfig $jigConfig) {
        $this->logger = $logger;
        $this->jigConverter = new JigConverter();
        $this->templatePath = $jigConfig->templateSourceDirectory;
        $this->compilePath = $jigConfig->templateCompileDirectory;
        $this->extension = $jigConfig->extension;
        $this->compileCheck = $jigConfig->compileCheck;
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
    public function setCompileCheck($compileCheck) {
        $this->compileCheck = $compileCheck;
    }


    /**
     * @param $templateString
     * @param $templateID - Must be a valid PHP class name i.e. cannot start with digit
     * @return string
     */
    function captureRenderTemplateString($templateString, $templateID){
        ob_start();
        $this->renderTemplateFromString($templateString, $templateID);
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * @param $filename
     */
    function includeFile($filename){
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
     * @return mixed
     */
    function getProxiedClass($className) {

        if (array_key_exists($className, $this->mappedClasses) == false) {
            throw new JigException("Class '$className' not listed in mappedClasses, cannot proxy.");
        }

        $mappedClass = $this->mappedClasses[$className];

        $originalClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, false);
        if (class_exists($originalClassName) == false) {
            $originalClassName = $this->getParsedTemplate($mappedClass, $this->mappedClasses, false);
        }
        
        $proxiedClassName = $this->jigConverter->getNamespacedClassNameFromFileName($mappedClass, true);

        //DONE - this was needed if dynamic extended classes are used out of order

        if (class_exists($proxiedClassName) == false) {
            $className = $this->getParsedTemplate($mappedClass, $this->mappedClasses, true);
        }
        
        return $proxiedClassName;
    }

    /**
     * @param $templateString
     * @param $objectID
     * @throws \Exception
     */
    public function renderTemplateFromString($templateString, $objectID){
        try{
            $className = $this->getParsedTemplateFromString($templateString, $objectID, $this->mappedClasses);
            
            $template = new $className($this->viewModel, $this);
            $template->render($this->viewModel);
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
     * @param $templateFilename
     * @param bool $capture
     * @return string
     */
    function renderTemplateFile($templateFilename, $capture = false){
        $contents = '';

        if ($capture == true) {
            ob_start();
        }

        $className = $this->getParsedTemplate($templateFilename, $this->mappedClasses);

        $template = new $className($this->viewModel, $this);
        $template->render($this->viewModel, $this);

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
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");   
    }

    
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
     * @return \Intahwebz\Jig\Converter\ParsedTemplate
     * @throws JigException
     */
    function prepareTemplateFromFile($templateFilename, $extension){
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
        

        $this->logger->info("Recompiling template ".$templateFilename.".");

        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename, $this->extension);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->compilePath,
            $proxied
        );

        $extendsClass = $parsedTemplate->getExtends();

        if ($extendsClass) {
            $extendsParsedTemplate = $this->getParsedTemplate($extendsClass, $mappedClasses);
        }
        else if ($parsedTemplate->dynamicExtends) {
            if (array_key_exists($parsedTemplate->dynamicExtends, $mappedClasses) == false) {
                throw new JigException("File $templateFilename is trying to proxy [".$parsedTemplate->dynamicExtends."] but that doesn't exist in the mappedClasses.");
            }

            $dynamicExtendsClass = $mappedClasses[$parsedTemplate->dynamicExtends];

            //Generate this twice - once for reals, once as a proxy.
            $dynamicExtendsParsedTemplate = $this->getParsedTemplate($dynamicExtendsClass, $mappedClasses, false);
            
            //TODO - once the proxy generating is working, this can be removed?
            $dynamicExtendsParsedTemplateProxy = $this->getParsedTemplate($dynamicExtendsClass, $mappedClasses, true);
        }

        if (class_exists($className) == false) {
            require($outputFilename);
        }
        else {
            //Warn - file was compiled when class already exists?
        }
        
        //Save after generating the parents as the saving code requires the file.
        //$fullClassName = $this->saveCompiledTemplate($this->compilePath, $proxied);

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }

    /**
     *
     * This is an entry point
     * @param $templateString
     * @param $cacheName
     * @return mixed
     */
    function getParsedTemplateFromString($templateString, $cacheName, $mappedClasses) {
        $templateString = str_replace( "<?php", "&lt;php", $templateString);
        $templateString = str_replace( "?>", "?&gt;", $templateString);

        //$this->forceCompile = true;
        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $parsedTemplate->saveCompiledTemplate($this->compilePath, false);
        $extendsFilename = $parsedTemplate->getExtends();

        if ($extendsFilename) {
            $parentTemplate = $this->getParsedTemplate($extendsFilename, $mappedClasses);
        }

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }
}
