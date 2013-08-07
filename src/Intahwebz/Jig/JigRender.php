<?php


namespace Intahwebz\Jig;

use Intahwebz\View;

use Intahwebz\Jig\Converter\JigConverter;


/**
 * Class JigRender
 * 
 * This class seems to have very little point. 
 * 
 * @package Intahwebz\Jig
 */
class JigRender {

    const COMPILED_NAMESPACE = "Intahwebz\\PHPCompiledTemplate";

    private $view;

    private $mappedClasses = array();

    private $boundFunctions = array();

    public $templatePath = null;
    public $compilePath = null;
    public $forceCompile = false;

    private $extension = ".tpl";


    function __construct(View $view, $templateSourceDirectory, $templateCompileDirectory, $extension) {
        
        $this->view = $view;
        
        $this->jigConverter = new JigConverter();

        $this->templatePath = $templateSourceDirectory;
        $this->compilePath = $templateCompileDirectory;
        $this->extension = $extension;

        //TODO - erm...how to do config
        $this->setForceCompile(true);
    }

    /**
     * @param $forceCompile
     */
    public function setForceCompile($forceCompile) {
        $this->forceCompile = $forceCompile;
    }
    
    function isVariableSet($variableName){
        return $this->view->isVariableSet($variableName);
    }

    function getVariable($variable) {
        return $this->view->getVariable($variable);
    }


    /**
     * @param $templateString
     * @param $templateID
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
     * @param $functionName
     * @param callable $callable
     */
    function bindFunction($functionName, callable $callable){
        $this->boundFunctions[$functionName] = $callable;
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

        $proxiedClassName = $this->mappedClasses[$className];

        //TODO - this should be needed if dynamic extended classes are used out of order
        //need to add tests.
//		if (class_exists($proxiedClassName) == false) {
//			echo "It's compiling time.";
//			$className = $this->phpTemplateConverter->getParsedTemplate($templateFilename, $this->mappedClasses);
//			exit(0);
//		}

        //TODO make sure class exists here.
        $lastSlashPosition = strrpos($proxiedClassName, '/');

        if ($lastSlashPosition !== false) {
            $part1 = substr($proxiedClassName, 0, $lastSlashPosition + 1);
            $part2 = substr($proxiedClassName, $lastSlashPosition + 1);
            $proxiedClassName = $part1.'Proxied'.$part2;
        }

        $proxiedClassName = str_replace("/", "\\", $proxiedClassName);
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
            
            $template = new $className($this->view, $this);
            $template->render($this->view);
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

        $template = new $className($this->view, $this);
        $template->render($this->view, $this);

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
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");   
    }

    /**
     * @param $templateFilename
     * @return \Intahwebz\Jig\Converter\ParsedTemplate
     * @throws JigException
     */
    function prepareTemplateFromFile($templateFilename, $extension){
        $templateFullFilename = $this->templatePath.$templateFilename.'.'.$extension;
        $fileLines = file($templateFullFilename);

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

        $className = $this->jigConverter->getNamespacedClassNameFromFileName($templateFilename);

        //If not cached
        if ($this->forceCompile == false) {
            if (class_exists($className) == true) {
                return $className;
            }
        }

        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename, $this->extension);
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->compilePath,
            $proxied//,
            //self::COMPILED_NAMESPACE
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

        $this->forceCompile = true;
        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $parsedTemplate->saveCompiledTemplate($this->compilePath, false);
        $extendsFilename = $parsedTemplate->getExtends();

        if ($extendsFilename) {
            $parentTemplate = $this->getParsedTemplate($extendsFilename, $mappedClasses);
        }

        return self::COMPILED_NAMESPACE."\\".$parsedTemplate->getClassName();
    }

    /**
     * @param $params
     * @return mixed|void
     */
    function call($params) {
        $functionName = array_shift($params);

        if (array_key_exists($functionName, $this->boundFunctions) == true) {
            return call_user_func_array($this->boundFunctions[$functionName], $params);
        }

        if (method_exists($this->view, $functionName) == true) {
            return call_user_func_array([$this->view, $functionName], $params);
        }

        echo "No method $functionName";
        return;
    }
}



?>