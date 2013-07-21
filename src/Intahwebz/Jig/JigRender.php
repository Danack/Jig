<?php


namespace Intahwebz\Jig;

use Intahwebz\View;

use Intahwebz\Jig\Converter\JigConverter;

class JigRender {

    private $view;

    private $mappedClasses = array();
    
    function __construct(View $view, $templateSourceDirectory, $templateCompileDirectory, $extension) {
        
        $this->view = $view;
        
        $this->jigConverter = new JigConverter();

        $this->jigConverter->init(
            $templateSourceDirectory,
            $templateCompileDirectory,
            $extension
        );

        //TODO - erm...how to do config
        $this->jigConverter->setForceCompile(true);
    }

    function isVariableSet($variableName){
        return $this->view->isVariableSet($variableName);
    }

    function getVariable($variable) {
        return $this->view->getVariable($variable);
    }

    function call($params) {
        return $this->view->call($params);
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

    private $boundFunctions = array();
    
    /**
     * @param $functionName
     * @param callable $callable
     */
    function bindFunction($functionName, callable $callable){
        $this->boundFunctions[$functionName] = $callable;
    }




//    /**
//     * @param $params
//     * @return mixed|void
//     */
//    function call($params) {
//        $functionName = array_shift($params);
//
//        if (array_key_exists($functionName, $this->boundFunctions) == true) {
//            return call_user_func_array($this->boundFunctions[$functionName], $params);
//        }
//
//        if (method_exists($this->view, $functionName) == true) {
//            return call_user_func_array([$this->view, $functionName], $params);    
//        }
//
//        echo "No method $functionName";
//        return;
//    }

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

        //TODO - why is this apparently not needed.
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
            $className = $this->jigConverter->getParsedTemplateFromString($templateString, $objectID, $this->mappedClasses);
            
            $template = new $className($this->view, $this);
            $template->render($this->view);
        }
        catch(JigException $je) {
            throw $je;
        }
        catch(\Exception $e){
            throw new \Exception("Failed to render template: ".$e->getMessage(), $e->getCode(), $e);

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

        $className = $this->jigConverter->getParsedTemplate($templateFilename, $this->mappedClasses);

        //Injecting them
        //$instance = new \Intahwebz\PHPCompiledTemplate\pages\image\displayAll($this->view, $this->mappedClasses);
        $template = new $className($this->view, $this);
        $template->render($this->view, $this);

        if ($capture == true) {
            $contents = ob_get_contents();
            ob_end_clean();
        }

        return $contents;
    }

}



?>