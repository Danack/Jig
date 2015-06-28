<?php

namespace  {

    function testFunction1() {
        echo "This is a global function.";
    }
}


namespace Tests\PHPTemplate {


use Jig\Jig;
use Jig\JigDispatcher;
use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\JigException;
use Jig\JigRender;
use Jig\TemplateHelper\BasicTemplateHelper;
use Jig\PlaceHolder\PlaceHolderHelper;
use Jig\Helper\GenericExceptionHelper;
    

class VariableTest{

    private $value;

    function __construct($value) {
        $this->value = $value;
    }

    function getValue() {
        return $this->value;
    }
}

function testCallableFunction() {
    echo "I am a callable function";
}


class JigConverterTest extends \Jig\Base\BaseTestCase {

    private $startOBLevel;

    private $templateDirectory;

    private $compileDirectory;


    /**
     * @var \Auryn\Injector
     */
    private $provider;
    
    
    function classBoundFunction() {
        echo "This is a class function.";
    }

    /**
     * @var \Jig\JigDispatcher
     */
    private $jig = null;

    /**
     * @var \Jig\JigRender
     */
    private $jigRender = null;

    /**
     * @var \Jig\JigConfig
     */
    private $jigConfig; 
    
    /**
     * @var \Jig\PlaceHolder\PlaceHolderHelper
     */
    private $helper;
    
    private $emptyHelper;

    function setUp() {

        parent::setUp();
        
        $this->templateDirectory = dirname(__DIR__)."/../templates/";
        $this->compileDirectory = dirname(__DIR__)."/../../tmp/generatedTemplates/";
        $this->helper = new PlaceHolderHelper();

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_ALWAYS
        );
        
        $this->jigConfig = $jigConfig;

        $provider = new \Auryn\Injector();
        $this->provider = $provider;
        $jigConverter = new JigConverter($jigConfig);
        
        $provider->share($jigConfig);
        $provider->share($provider);
        $provider->share($this->helper);
        $provider->share($jigConverter);
        
        $this->jigRender = new JigRender($jigConfig, $jigConverter);
        
        $this->jig = new JigDispatcher($jigConfig, $this->jigRender, $jigConverter, $provider);
        
        $this->helper->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');
        $this->emptyHelper = new PlaceHolderHelper();
    }

    public function teardown(){
        parent::teardown();
    }

    function testBasicConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jig->renderTemplateFile('basic/basic', $this->helper);
        $this->assertContains("Basic test passed.", $contents);
    }
    
    function testForeachConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/foreachTest.php");
        //$this->helper->setVariable('colors', ['red', 'green', 'blue']);
        $this->helper->bindFunction('getColors', function (){ return ['red', 'green', 'blue'];});
        $contents = $this->jig->renderTemplateFile('basic/foreachTest', $this->helper);
        $this->assertContains("Direct: redgreenblue", $contents);
        $this->assertContains("From function: redgreenblue", $contents);
    }

    /**
     * @group helper
     */
    function testHelperBasic(){
        $contents = $this->jig->renderTemplateFile('basic/helper');
        $this->assertContains(\Jig\Helper\BasicHelper::message, $contents);
    }
    
    /**
     * @group blah
     */
    function testDependencyInsertionConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        //$contents = $this->jig->renderTemplateFile('basic/DependencyInsertion', $this->viewModel);

        $templateName = 'basic/DependencyInsertion';
        //$className = $this->jigRender->getClassName('basic/DependencyInsertion');
        $className = $this->jigConfig->getFullClassname('basic/DependencyInsertion');
        $this->jigRender->checkTemplateCompiled($templateName);

        $contents = $this->provider->execute([$className, 'render']);

        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }

    function testFunctionCall() {
        $contents = $this->jig->renderTemplateFile('basic/functionCall');
        $this->assertContains("checkRole works", $contents);
    }

    function testStringExtendsConversion() {
        $templateString = <<< END
{extends file='extendTest/parentTemplate'}

    {block name='secondBlock'}
    This is the second child block.
{/block}

END;

        $renderedText = $this->jig->renderTemplateFromString(
            $templateString,
            "testStringExtendsConversion123",
            $this->emptyHelper
        );
    }

    function testBasicCoversExistsConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $renderer = $this->jig; 
        $this->helper->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');
        $contents = $renderer->renderTemplateFile('basic/basic', $this->helper);
        $this->assertContains("Basic test passed.", $contents);
    }

    function testNonExistentConversion(){
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('nonExistantFile');
    }

    function testMtimeCachesConversion(){
        $this->jig->deleteCompiledFile('basic/simplest');
        $contents = $this->jig->renderTemplateFile('basic/simplest');
        $this->assertContains("Hello, this is a template.", $contents);
    }
    
    function testBasicComment() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jig->renderTemplateFile('basic/comments');
        $this->assertContains("Basic comment test passed.", $contents);
    }

    function testIncludeConversion(){
        $contents = $this->jig->renderTemplateFile('includeFile/includeTest');
        $this->assertContains("Include test passed.", $contents);
    }

    /**
     * @group blah
     */    
    function testStandardExtends(){
        $className = $this->jigRender->getClassName('extendTest/child');
        $this->jigRender->checkTemplateCompiled('extendTest/child');

        $contents = $this->provider->execute([$className, 'render']);

        $this->assertContains("This is the second child block.", $contents);
        $this->assertContains(\Jig\PlaceHolder\ChildDependency::output, $contents);
        $this->assertContains(\Jig\PlaceHolder\ParentDependency::output, $contents);
    }

    function testFunctionBinding() {
        $this->helper->bindFunction('testFunction1', 'testFunction1');
        $this->helper->bindFunction('testFunction2', [$this, 'classBoundFunction']);
        $this->helper->bindFunction('testFunction3', function () {
            echo "This is a closure function.";
        });

        $this->helper->bindFunction('isAllowed', function () {
            return true;
        });

        $contents = $this->jig->renderTemplateFile('binding/binding');
        $this->assertContains("This is a global function.", $contents);
        $this->assertContains("This is a class function.", $contents);
        $this->assertContains("This is a closure function.", $contents);
        $this->assertContains("isAllowed was true", $contents);
    }

    function testBlockEscaping() {

        $this->jig->bindRenderBlock('htmlEntityDecode', [$this->helper, 'htmlEntityDecode']);
        $contents = $this->jig->renderTemplateFile('binding/blocks', $this->helper);
        $this->assertContains("€¥™<>", $contents);
        //$this->assertContains("This is a variable", $contents);
    }

    function testBlockEscapingFromString() {
        $string = <<< END

{htmlEntityDecode}
&euro;&yen;&trade;&lt;&gt;
{/htmlEntityDecode}

The above should be decoded to characters

END;
        $this->jig->bindRenderBlock('htmlEntityDecode', [$this->helper, 'htmlEntityDecode']);

        $contents = $this->jig->renderTemplateFromString(
            $string,
            'Foo1'
        );
        $this->assertContains("€¥™<>", $contents);
    }

    function testDynamicInclude() {
        $contents = $this->jig->renderTemplateFile('includeFile/dynamicIncludeTest', $this->helper);
        $this->assertContains("This is include 1.", $contents);
    }

    /**
     * @group blah
     */    
    function testInclude() {
        $templateName = 'includeFile/includeTest';
        $className = $this->jigRender->getClassName($templateName);
        $this->jigRender->checkTemplateCompiled($templateName);

        $contents = $this->provider->execute([$className, 'render']);

        $this->assertContains("Included start", $contents);
        $this->assertContains("Included end", $contents);
        $this->assertContains("This is an include test.", $contents);
        $this->assertContains("This is a foo", $contents);
    }


    function testNoOutput(){
        $this->helper->bindFunction('testNoOutput', function() { return 'This is some output';});
        $this->helper->bindFunction('getBar', function() { return 'bar';});

        $contents = $this->jig->renderTemplateFile('coverageTesting/nooutput');
        $this->assertEquals(0, strlen(trim($contents)), "Output of [$contents] found when none expected.");
    }
    
    function testIsset() {
        $viewModel = new BasicTemplateHelper();
        $contents = $this->jig->renderTemplateFile('coverageTesting/checkIsset', $viewModel);
        $this->assertEquals(0, strlen(trim($contents)));
    }

    function testBadIssetCall() {
        $this->setExpectedException('Jig\JigException');
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/badIssetCall',
            $viewModel
        );
    }

    function testFunctionNotBound() {
        $this->setExpectedException('Jig\JigException');
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/functionNotDefined',
            $viewModel
        );
    }

    function testInjectBadName1() {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadName1',
            $viewModel
        );
    }

    function testInjectBadName2() {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadName2',
            $viewModel
        );
    }

    function testInjectBadValue1() {
        $this->setExpectedException('Jig\JigException', "Value must not be zero length");
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadValue1',
            $viewModel
        );
    }

    function testInjectBadValue2() {
        $this->setExpectedException('Jig\JigException', "Failed to get value for injection");
        $viewModel = new BasicTemplateHelper();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadValue2',
            $viewModel
        );
    }


    function testBorkedCode() {
        $this->setExpectedException('Jig\JigException', "Failed to parse code");
        $this->jig->renderTemplateFile(
            'coverageTesting/borkedCode'
        );
    }

    function testBorkedExtends() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedExtends');
    }
    
    function testBorkedInclude1() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    function testBorkedInclude2() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    function testBlockNotSet() {
        $this->setExpectedException('Jig\JigException', "Detected end of unknown block.");
        $this->jig->renderTemplateFile('coverageTesting/blockNotSet');
    }

    function testStringCoverageObject() {
        $this->helper->bindFunction('getObject', function() {return new \StdClass;});
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('coverageTesting/stringCoverageObject');
    }

    function testStringCoverageArray() {
        $this->helper->bindFunction('getArray', function() {return [];});
        $this->setExpectedException('Jig\JigException', \Jig\JigException::IMPLICIT_ARRAY_TO_STRING);
        $this->jig->renderTemplateFile('coverageTesting/stringCoverageArray');
    }

    function testDelete() {
        $templateName = 'basic/simplest';
        $this->jig->renderTemplateFile($templateName);
        $this->jig->deleteCompiledFile($templateName);
    }

    function testWithoutNameSpace() {

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_ALWAYS,
            ""
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);
        $jig = $provider->make('Jig\JigDispatcher');
        $jig->renderTemplateFile(
            "templateInRoot",
            $this->emptyHelper
        );
    }

    function testCheckExistsCoverage() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_CHECK_EXISTS,
            ""
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);
        $jig = $provider->make('Jig\JigDispatcher');
        $jig->renderTemplateFile("basic/simplest");
        $jig->renderTemplateFile("basic/simplest");
    }

    function testCheckMtimeCoverage() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_CHECK_MTIME,
            ""
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jig = $provider->make('Jig\JigDispatcher');
        $templateName = "coverageTesting/mtimeonce";
        $jig->deleteCompiledFile($templateName);
        $jig->renderTemplateFile($templateName);
        $filename = $jig->getCompileFilename($templateName);
        touch($filename, time() - 3600); // will break if the test takes an hour ;)
        $jig->renderTemplateFile($templateName);
    }

    function testRenderBlock(){
        $blockStartCallCount = 0;
        $blockEndCallCount = 0;
        $passedSegementText = null;
        $warningBlockStart = function ($segmentText) use (&$blockStartCallCount, &$passedSegementText) {
            $blockStartCallCount++;
            $passedSegementText = $segmentText;
            return "processedBlockStart";
        };

        $warningBlockEnd = function ($contents) use (&$blockEndCallCount) {
            $blockEndCallCount++;
            return $contents."processedBlockEnd";
        };

        $this->jig->bindRenderBlock(
            'warning',
            $warningBlockEnd,
            $warningBlockStart
        );
        
        $contents = $this->jig->renderTemplateFile('block/renderBlock');
        
        $this->assertEquals($blockStartCallCount, 1);
        $this->assertEquals($blockEndCallCount, 1);
        $this->assertEquals("foo='bar'", $passedSegementText);
        $this->assertContains("This is in a warning block", $contents);
        $this->assertContains("processedBlockEnd", $contents);
        $this->assertContains("processedBlockStart", $contents);

        $this->assertContains("This is in a warning block", $contents);
    }

    function testCompileBlock() {
        $blockStartCallCount = 0;
        $blockEndCallCount = 0;

        $passedSegementText = null;

        $compileBlockStart = function (JigConverter $jigConverter, $segmentText) use (&$blockStartCallCount, &$passedSegementText) {
            $blockStartCallCount++;
            $jigConverter->addHTML("compileBlockStart");
            $jigConverter->addHTML($segmentText);
            $passedSegementText = $segmentText;
        };

        $compileBlockEnd = function (JigConverter $jigConverter) use (&$blockEndCallCount) {
            $blockEndCallCount++;
            $jigConverter->addHTML("compileBlockEnd");
        };

        
        $this->jig->bindCompileBlock(
            'compile',
            $compileBlockStart,
            $compileBlockEnd
        );

        $this->jig->deleteCompiledFile('block/compileBlock');
        $contents = $this->jig->renderTemplateFile('block/compileBlock');

        //Because the block is called when the template is compiled, and
        //as the template should only be compiled once (due to caching) each
        //block function should only be called once.

        $this->assertEquals('foo="bar"', $passedSegementText);
        $this->assertEquals($blockStartCallCount, 1);
        $this->assertEquals($blockEndCallCount, 1);
        $this->assertContains("This is in a compile time block", $contents);
        $this->assertContains("compileBlockStart", $contents);
        $this->assertContains("compileBlockEnd", $contents);

    }

    function testRenderFromStringJigExceptionHandling() {
        $this->setExpectedException('Jig\JigException', "Could not parse template segment");
        $templateString = "This is an invalid template {not valid construct}";
        $this->jig->renderTemplateFromString($templateString, "Exception1");
    }

    function testRenderFromStringGenericExceptionHandling() {
        $this->setExpectedException('Jig\JigException', GenericExceptionHelper::message);
        $templateString = "
        {helper type='Jig\\Helper\\GenericExceptionHelper'}
        
        This throws {throwup()}";
        $this->jig->renderTemplateFromString($templateString, "Exception1");
    }

    function testCheckInlinePHP() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_CHECK_EXISTS,
            ""
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);
        $jig = $provider->make('Jig\JigDispatcher');
        $contents = $jig->renderTemplateFile("inlinePHP/simple");
        $this->assertContains('value is 5', $contents);
    }
    
    
}

}//end namespace
