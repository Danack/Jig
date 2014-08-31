<?php

namespace  {

    function testFunction1() {
        echo "This is a global function.";
    }
}


namespace Tests\PHPTemplate{



use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\PlaceHolder\PlaceHolderView;
use Jig\JigRender;
use Jig\ViewModel\BasicViewModel;

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


class JigTest extends \Jig\Base\BaseTestCase {

    private $startOBLevel;

    private $templateDirectory;

    private $compileDirectory;
    
    
    function classBoundFunction() {
        echo "This is a class function.";
    }

    /**
     * @var \Jig\JigRender
     */
    private $jigRenderer = null;

    /**
     * @var \Jig\PlaceHolder\PlaceHolderView
     */
    private $viewModel;

    function setUp() {

        parent::setUp();
        
        $this->templateDirectory = dirname(__DIR__)."/../templates/";
        $this->compileDirectory = dirname(__DIR__)."/../../tmp/generatedTemplates/";
        $this->viewModel = new PlaceHolderView();

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $this->jigRenderer = $provider->make(
            'Jig\JigRender',
            [':jigConfig' , $jigConfig,
             ':provider',   $provider
            ]
        );
        
        $this->viewModel->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');
    }

    public function teardown(){
        parent::teardown();
    }

    function testWithoutView() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $renderer = new JigRender($jigConfig, $provider);
        
        $contents = $renderer->renderTemplateFile('basic/templateWithoutView');
        $this->assertContains("This is the simplest template.", $contents);
    }
    
    function testFilter() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $renderer = new JigRender($jigConfig, $provider);

        $contents = $renderer->renderTemplateFile('basic/filterTest');
    }

    function testBasicConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/basic', $this->viewModel);
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }
    
    function testForeachConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/foreachTest.php");
        $this->viewModel->setVariable('colors', ['red', 'green', 'blue']);
        $this->viewModel->bindFunction('getColors', function (){ return ['red', 'green', 'blue'];});
        $contents = $this->jigRenderer->renderTemplateFile('basic/foreachTest', $this->viewModel);
        $this->assertContains("Direct: redgreenblue", $contents);
        $this->assertContains("Assigned: redgreenblue", $contents);
        $this->assertContains("Fromfunction: redgreenblue", $contents);
    }
    
    function testDependencyInsertionConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/DependencyInsertion', $this->viewModel);
        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }

    function testFunctionCall() {
        $contents = $this->jigRenderer->renderTemplateFile('basic/functionCall', $this->viewModel);
        $hasBeenCalled = $this->viewModel->hasBeenCalled('someFunction', '$("#myTable").tablesorter();');
        $this->assertTrue($hasBeenCalled);
        $this->assertContains("checkRole works", $contents);
    }

    function testBasicCapturingConversion() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/basic', $this->viewModel);
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }

    function testStringConversion() {
        $templateString = 'Hello there {$title} {$user} !!!!';

        $title = 'Mr';
        $user = 'Ackersgaah';
        $this->viewModel->setVariable('title', $title);
        $this->viewModel->setVariable('user', $user);
        $renderedText = $this->jigRenderer->renderTemplateFromString($templateString, "test123", $this->viewModel);

        $this->assertContains('Mr', $renderedText);
        $this->assertContains($user, $renderedText);
    }

    function testStringExtendsConversion() {
        $templateString = <<< END
{extends file='extendTest/parentTemplate'}

    {block name='secondBlock'}
    This is the second child block.
{/block}

END;


        $renderedText = $this->jigRenderer->renderTemplateFromString($templateString, "testStringExtendsConversion123");
    }

    function testBasicCoversExistsConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_CHECK_EXISTS
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $renderer = $provider->make(
            //'\Jig\Tests\ExtendedJigRender',
            'Jig\JigRender',
            [':jigConfig' , $jigConfig,
                ':provider',   $provider
            ]
        );
        
        $this->viewModel->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');
        $contents = $renderer->renderTemplateFile('basic/basic', $this->viewModel);
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }

    function testNonExistentConversion(){
        $this->setExpectedException('Jig\JigException');
        $this->jigRenderer->renderTemplateFile('nonExistantFile');
    }

    function testMtimeCachesConversion(){

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_CHECK_MTIME
        );

        $jigRenderer = new JigRender(
            $jigConfig,
            new \Auryn\Provider()
        );

        $jigRenderer->deleteCompiledFile('basic/basic');
        $contents = $jigRenderer->renderTemplateFile('basic/basic', $this->viewModel);
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }
    
    function testBasicComment() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/comments');
        $this->assertContains("Basic comment test passed.", $contents);
    }

    function testIncludeConversion(){
        $contents = $this->jigRenderer->renderTemplateFile('includeFile/includeTest');
        $this->assertContains("Include test passed.", $contents);
    }

    function testStandardExtends(){
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('extendTest/child');
        $this->assertContains("This is the second child block.", $contents);
    }

    function testDynamicExtends1() {
        $this->jigRenderer->mapClasses(array('parent' => 'dynamicExtend/parent1'));
        $contents = $this->jigRenderer->renderTemplateFile('dynamicExtend/dynamicChild');

        $this->assertContains("This is the child content.", $contents);
        $this->assertContains("This is the parent 1 start.", $contents);
        $this->assertContains("This is the parent 1 end.", $contents);
    }

    function testDynamicExtends2() {
        $this->jigRenderer->mapClasses(array('parent' => 'dynamicExtend/parent2'));
        $contents = $this->jigRenderer->renderTemplateFile('dynamicExtend/dynamicChild');
        $this->assertContains("This is the child content.", $contents);
        $this->assertContains("This is the parent 2 start.", $contents);
        $this->assertContains("This is the parent 2 end.", $contents);
    }

    function testFunctionBinding() {

        $this->viewModel->bindFunction('testFunction1', 'testFunction1');
        $this->viewModel->bindFunction('testFunction2', [$this, 'classBoundFunction']);
        $this->viewModel->bindFunction('testFunction3', function () {
            echo "This is a closure function.";
        });

        $contents = $this->jigRenderer->renderTemplateFile('binding/binding', $this->viewModel);
        $this->assertContains("This is a global function.", $contents);
        $this->assertContains("This is a class function.", $contents);
        $this->assertContains("This is a closure function.", $contents);
    }
    
    function testAssignment() {

        $variable1 = "This is variable 1.";
        $variableArray = array('index1' => "This is a variable array.");

        $objectMessage = "This is an object variable";
        $variableObject = new VariableTest($objectMessage);

        $this->viewModel->setVariable('variable1', $variable1);
        $this->viewModel->setVariable('variableArray', $variableArray);
        $this->viewModel->setVariable('variableObject', $variableObject);

        $contents = $this->jigRenderer->renderTemplateFile('assigning/assigning', $this->viewModel);
        $this->assertContains($variable1, $contents);
        $this->assertContains($variableArray['index1'], $contents);
        $this->assertContains($objectMessage, $contents);
    }

    function testBlockEscaping() {
        $this->viewModel->setVariable('variable1', "This is a variable");
        $this->jigRenderer->bindRenderBlock('htmlEntityDecode', [$this->viewModel, 'htmlEntityDecode']);
        $contents = $this->jigRenderer->renderTemplateFile('binding/blocks', $this->viewModel);
        $this->assertContains("€¥™<>", $contents);
        $this->assertContains("This is a variable", $contents);
    }

    function testBlockEscapingFromString() {
        $string = <<< END

{htmlEntityDecode}
&euro;&yen;&trade;&lt;&gt;
{/htmlEntityDecode}

Hmm that was odd

{htmlEntityDecode}

Variable is: {\$variable1}

{/htmlEntityDecode}
END;
        
        $this->jigRenderer->bindRenderBlock('htmlEntityDecode', [$this->viewModel, 'htmlEntityDecode']);
        $contents = $this->jigRenderer->renderTemplateFromString($string, 'Foo1');
        $this->assertContains("€¥™<>", $contents);
    }

    function testDynamicInclude(){
        $this->viewModel->setVariable('dynamicInclude', "includeFile/includedFile");
        $contents = $this->jigRenderer->renderTemplateFile('includeFile/dynamicIncludeTest', $this->viewModel);
        $this->assertContains("This is the included file.", $contents);
    }

    function testCoverageConversion(){
        $this->viewModel->setVariable('filteredVar', '<b>bold</b>');
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/coverage', $this->viewModel);
        $this->assertContains('comment inside', $contents);
        $this->assertContains('<b>bold</b>', $contents);
        $this->assertContains('test is 5', $contents);
    }

    function testNoOutput(){
        $viewModel = new BasicViewModel();
        $viewModel->setVariable('bar', 'This is some output');
        $viewModel->bindFunction('getBar', function() {return 'This is some output';});
        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/nooutput', $viewModel);
        $this->assertEquals(0, strlen(trim($contents)), "Output of [$contents] found when none expected.");

    }
    
    function testIsset() {
        $viewModel = new BasicViewModel();
        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/checkIsset', $viewModel);
        $this->assertEquals(0, strlen(trim($contents)));
    }

    function testBadIssetCall() {
        $this->setExpectedException('Jig\JigException');
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/badIssetCall', $viewModel);
    }

    function testFunctionNotBound() {
        $this->setExpectedException('Jig\JigException');
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/functionNotDefined', $viewModel);
    }
    
    function testSetVariables(){
        $viewModel = new BasicViewModel();
        $viewModel->setVariables([
            'variable1' => 'red',
            'variable2' => 'green',
            'variable3' => 'blue'
        ]);
        
        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/setVariables', $viewModel);
      
        $this->assertContains('red', $contents);
        $this->assertContains('green', $contents);
        $this->assertContains('blue', $contents);
    }
    
    function testInjectBadName1() {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/injectBadName1', $viewModel);
    }

    function testInjectBadName2() {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/injectBadName2', $viewModel);
    }

    function testInjectBadValue1() {
        $this->setExpectedException('Jig\JigException', "Value must not be zero length");
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/injectBadValue1', $viewModel);
    }

    function testInjectBadValue2() {
        $this->setExpectedException('Jig\JigException', "Failed to get value for injection");
        $viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/injectBadValue2', $viewModel);
    }


    function testBorkedCode() {
        $this->setExpectedException('Jig\JigException', "Failed to parse code");
        //$viewModel = new BasicViewModel();
        $this->jigRenderer->renderTemplateFile('coverageTesting/borkedCode');
    }


    function testBorkedExtends() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigRenderer->renderTemplateFile('coverageTesting/borkedExtends');
    }

    function testBorkedDynamicExtends() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigRenderer->renderTemplateFile('coverageTesting/borkedDynamicExtends');
    }
    
    function testBorkedInclude1() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigRenderer->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    function testBorkedInclude2() {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigRenderer->renderTemplateFile('coverageTesting/borkedInclude1');
    }



    function testBlockNotSet() {
        $this->setExpectedException('Jig\JigException', "Detected end of unknown block.");
        $this->jigRenderer->renderTemplateFile('coverageTesting/blockNotSet');
    }


    function testStringCoverage() {
        $viewModel = new BasicViewModel();
        $viewModel->setVariable('someObjectWithoutToString', new \stdClass);
        $viewModel->setVariable('someArray', []);
        $this->jigRenderer->renderTemplateFile('coverageTesting/stringCoverage', $viewModel);
    }

    function testDelete() {
        $templateName = 'basic/basic';
        $this->jigRenderer->renderTemplateFile($templateName);
        $this->jigRenderer->deleteCompiledFile($templateName);
    }

    function testWithoutNameSpace() {

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_ALWAYS,
            ""
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);
        $jigRenderer = $provider->make('Jig\JigRender');
        $jigRenderer->renderTemplateFile("templateInRoot");
    }

    function testCheckExistsCoverage() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_CHECK_EXISTS,
            ""
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);
        $jigRenderer = $provider->make('Jig\JigRender');
        $jigRenderer->renderTemplateFile("basic/basic");
        $jigRenderer->renderTemplateFile("basic/basic");
    }

    function testCheckMtimeCoverage() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_CHECK_MTIME,
            ""
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jigRenderer = $provider->make('Jig\JigRender');
        $templateName = "coverageTesting/mtimeonce";
        $jigRenderer->deleteCompiledFile($templateName);
        $jigRenderer->renderTemplateFile($templateName);
        $filename = $jigRenderer->getCompileFilename($templateName);
        touch($filename, time() - 3600); // will break if the test takes an hour ;)
        $jigRenderer->renderTemplateFile($templateName);
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

        $this->jigRenderer->bindRenderBlock(
            'warning',
            $warningBlockEnd,
            $warningBlockStart
        );
        
        $contents = $this->jigRenderer->renderTemplateFile('block/renderBlock');
        
        $this->assertEquals($blockStartCallCount, 1);
        $this->assertEquals($blockEndCallCount, 1);
        $this->assertEquals("foo='bar'", $passedSegementText);
        $this->assertContains("This is in a warning block", $contents);
        $this->assertContains("processedBlockEnd", $contents);
        $this->assertContains("processedBlockStart", $contents);
    }

    function testCompileBlock() {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            JigRender::COMPILE_CHECK_MTIME,
            ""
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jigRenderer = $provider->make('Jig\JigRender');
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

        $jigRenderer->bindCompileBlock(
            'compile',
            $compileBlockStart,
            $compileBlockEnd
        );

        $jigRenderer->deleteCompiledFile('block/compileBlock');
        $contents = $jigRenderer->renderTemplateFile('block/compileBlock');
        $contents = $jigRenderer->renderTemplateFile('block/compileBlock');

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
        $templateString = "This is an invalud template {not valid construct}";
        $this->jigRenderer->renderTemplateFromString($templateString, "Exception1");
    }

    function testRenderFromStringGenericExceptionHandling() {
        $exceptionMessage = "This is an exception";
        $callable = function() use ($exceptionMessage) {
            throw new \Exception($exceptionMessage);
        };
        
        $viewModel = new BasicViewModel();
        $viewModel->bindFunction('throwup', $callable);
        $templateString = "This throws {throwup()}";
        $this->setExpectedException('Jig\JigException', $exceptionMessage);
        $this->jigRenderer->renderTemplateFromString($templateString, "Exception1", $viewModel);
    }

}

}//end namespace


?>