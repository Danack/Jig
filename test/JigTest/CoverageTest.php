<?php


namespace Jig\Converter {
    
    // The following is a hack to allow us to test when mkdir and file_exists
    // behave badly.
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
use Jig\Converter\ParsedTemplate;
use Jig\JigException;
use Jig\JigCompilePath;
use Jig\JigTemplatePath;
    
/**
 * Class CoverageTest
 * 
 * These tests are not particularly useful. They just add coverage to the code base,
 * to make it easier to see that important stuff is covered.
 */
class CoverageTest extends BaseTestCase
{
    private $templateDirectory;
    private $compileDirectory;

    public function setUp()
    {
        parent::setup();
        $this->templateDirectory = new JigTemplatePath(dirname(__DIR__)."/./templates/");
        $this->compileDirectory = new JigCompilePath(dirname(__DIR__)."/./../tmp/generatedTemplates/");
    }
    
    private function getJigConfig()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            Jig::COMPILE_ALWAYS,
            "php.tpl"
        );

        return $jigConfig;
    }
    
    public function testNamespaceCoverage()
    {
        $namespace = ParsedTemplate::getNamespace(new StdClass);
        $this->assertEmpty($namespace);
    }
    
    public function testMkdirFailureThrowsException()
    {
        $GLOBALS['mkdirCallable'] = function () {
            return false;
        };

        $GLOBALS['file_exists_callable'] = function () {
            return false;
        };

        $jig = $this->getJigDispatcher();

        $jig->deleteCompiledFile("basic/basic");
        $this->setExpectedException('Jig\JigException', "does not exist and could not be created");
        $viewModel = new PlaceHolderPlugin();

        $jig->renderTemplateFile("basic/basic", $viewModel);
    }

    /**
     * @return \Jig\JigDispatcher
     */
    private function getJigDispatcher()
    {
        $jigConfig = $this->getJigConfig();

        $injector = new \Auryn\Injector();
        $injector->share($jigConfig);
        $injector->share($injector);
        $jig = $injector->make('Jig\JigDispatcher');
        
        return $jig;
    }
    
    public function testGetConverter()
    {
        $jigConfig = $this->getJigConfig();
        $jig = new Jig($jigConfig);
        $jigConverter = $jig->getJigConverter();
        
        $this->assertInstanceOf('Jig\Converter\JigConverter', $jigConverter);
    }
    
    public function testNeverCompile()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            Jig::COMPILE_NEVER,
            "php.tpl"
        );
        $jig = new Jig($jigConfig);
        $jig->compile("basic/basic");

    }

    public function testGlobalNamespaceClassName()
    {
        $classname = 'Foo';
        $fqcn = \Jig\Converter\ParsedTemplate::getFQCN('', $classname);
        $this->assertEquals($classname, $fqcn);
        
        $result = \Jig\Converter\ParsedTemplate::getClassName($classname);
        $this->assertEquals($classname, $result);
    }
    
    public function testPluginDoesntExist()
    {
        $this->setExpectedException(
            'Jig\JigException',
            JigException::FILTER_NO_INFO
        );
        
        $templateString = <<< TPL
{plugin type='nonExistentClass'}
This is some output

{foo()}
TPL;
        $jig = $this->getJigDispatcher();
        $output = $jig->renderTemplateFromString($templateString, "testPluginDoesntExist".time());
    }

    public function testPluginMissingImplementation()
    {
        $this->setExpectedException(
            'Jig\JigException',
            JigException::FILTER_NO_INFO
        );
        
        $templateString = <<< TPL
{plugin type='StdClass'}
This is some output

{foo | unknownFilter}

TPL;
        $jig = $this->getJigDispatcher();
        $output = $jig->renderTemplateFromString($templateString, "testPluginDoesntExist".time());
    }
    
    
    public function testPluginReturningWrongFilterList()
    {
        $this->setExpectedException(
            'Jig\JigException',
            JigException::FILTER_NO_INFO
        );
        
        $templateString = <<< TPL
{plugin type='\JigTest\PlaceHolder\Plugin\BadPlugin'}
This is some output

{foo | unknownFilter}

TPL;
        $jig = $this->getJigDispatcher();
        $output = $jig->renderTemplateFromString($templateString, "testPluginReturningWrongFilterList".time());
    }
    

    
    public function testPluginReturningArrayOfNotStrings()
    {
        $this->setExpectedException(
            'Jig\JigException',
            JigException::FILTER_NO_INFO
        );
        
        $templateString = <<< TPL
{plugin type='\JigTest\PlaceHolder\Plugin\ReturnsNonStringArrayPlugin'}
This is some output

{foo | unknownFilter}

TPL;
        $jig = $this->getJigDispatcher();
        $output = $jig->renderTemplateFromString($templateString, "testPluginReturningWrongFilterList".time());
    }
    
    public function testBuiltinEscaping()
    {

$templateString = <<< TPL

js is {"<>" | js}
css is {"<>" | css}
url is {"<>" | url}
TPL;

        $jig = $this->getJigDispatcher();
        $output = $jig->renderTemplateFromString($templateString, "testBuiltinEscaping".time());

        $this->assertContains('url is %3C%3E', $output);
        $this->assertContains('css is \\3C \\3E ', $output);
        $this->assertContains('js is \\x3C\\x3E', $output);
    }

    
    public function testForeachVariable()
    {
$templateString = <<< 'TPL'

{$foo = [1, 2, 3]}

{foreach $foo as $value}
value is {$value}
{/foreach}

TPL;
        $jig = $this->getJigDispatcher();

        $objectID = "testForeachVariable".time();
        $jig->deleteCompiledString($objectID);
        $output = $jig->renderTemplateFromString($templateString, $objectID);
        $jig->deleteCompiledString($objectID);

        $this->assertContains('value is 1', $output);
        $this->assertContains('value is 2', $output);
        $this->assertContains('value is 3', $output);
    }
    
    /**
     * @group foreachcoverage
     */
    public function testForeachBadSyntax()
    {
$templateString = <<< 'TPL'

{$foo = [1, 2, 3]}

{foreach asdsd}
This should not compile
{/foreach}

TPL;
        $jig = $this->getJigDispatcher();

        $objectID = "testForeachBadSyntax".time();
        $jig->deleteCompiledString($objectID);
        $this->setExpectedException('Jig\JigException');
        $output = $jig->renderTemplateFromString($templateString, $objectID);
        
    }
    
}

}
