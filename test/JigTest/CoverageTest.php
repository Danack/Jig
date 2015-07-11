<?php


namespace Jig {
    $GLOBALS['mkdirCallable'] = null;
    function mkdir($pathname,$mode = 0777, $recursive = false , $context = null ){

        if ($GLOBALS['mkdirCallable'] != null) {
            $callable = $GLOBALS['mkdirCallable'];
            $GLOBALS['mkdirCallable'] = null;
            return $callable($pathname, $mode, $recursive, $context);
        }

        if ($context) {
            return \mkdir($pathname, $mode, $recursive, $context);
        }
        else {
            return \mkdir($pathname, $mode, $recursive);
        }
    }

    $GLOBALS['file_exists_callable'] = null;
    function file_exists($filename) {
        if ($GLOBALS['file_exists_callable'] != null) {
            $callable = $GLOBALS['file_exists_callable'];
            $GLOBALS['file_exists_callable'] = null;
            return $callable($filename);
        }
        
        return \file_exists($filename);
    }
}


namespace {

use Jig\JigConfig;
use JigTest\BaseTestCase;
use Jig\Jig;
use JigTest\PlaceHolder\PlaceHolderPlugin;
    
class CoverageTest extends BaseTestCase {

    private $templateDirectory;
    private $compileDirectory;

    public function setUp() {
        parent::setup();
        $this->templateDirectory = dirname(__DIR__)."/./templates/";
        $this->compileDirectory = dirname(__DIR__)."/./../tmp/generatedTemplates/";
    }

    function testNamespaceCoverage() {
        \Jig\JigFunctions::load();
        $namespace = \Jig\getNamespace(new StdClass);
        $this->assertEmpty($namespace);
    }
    
    function testMkdirFailureThrowsException() {
        $GLOBALS['mkdirCallable'] = function () {
            return false;
        };

        $GLOBALS['file_exists_callable'] = function () {
            return false;
        };

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_ALWAYS
        );

        $injector = new \Auryn\Injector();
        $injector->share($jigConfig);
        $injector->share($injector);
        $jig = $injector->make('Jig\JigDispatcher');
        $jig->deleteCompiledFile("basic/basic");
        $this->setExpectedException('Jig\JigException', "does not exist and could not be created");
        $viewModel = new PlaceHolderPlugin();

        $jig->renderTemplateFile("basic/basic", $viewModel);
    }
    
    
//    function testPHPUnitCoverage()
//    {
//        $jigConfig = new JigConfig(
//            $this->templateDirectory,
//            $this->compileDirectory,
//            "php.tpl",
//            Jig::COMPILE_ALWAYS
//        );
//        
//        $injector = new \Auryn\Injector();
//        $injector->share($jigConfig);
//        $jig = $injector->make('Jig\JigDispatcher');
//        
//        $jig->renderTemplateFile("basic/basic", $viewModel);
//    }
    
}
    
    
}