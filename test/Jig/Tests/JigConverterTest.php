<?php

namespace  {

    function testFunction1() {
        echo "This is a global function.";
    }
}


namespace Tests\PHPTemplate{


use Jig\Converter;
use Jig\JigConfig;
use Jig\Tests\PlaceHolderView;
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



class JigTest extends \PHPUnit_Framework_TestCase {

    private $startOBLevel;
    
    function classBoundFunction() {
        echo "This is a class function.";
    }



    /**
     * @var \Jig\JigRender
     */
    private $jigRenderer = null;

    /**
     * @var \Jig\Tests\PlaceHolderView
     */
    private $viewModel;

    protected function setUp() {
        $this->startOBLevel = ob_get_level();
        $this->viewModel = new PlaceHolderView();

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $this->jigRenderer = $provider->make(
            '\Jig\Tests\ExtendedJigRender',
            [':jigConfig' , $jigConfig,
             ':provider',   $provider
            ]
        );

        //$this->jigRenderer->bindViewModel($this->viewModel);
        $this->viewModel->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');
    }


    protected function tearDown(){
        //ob_end_clean();
        $level = ob_get_level();
        
        $this->assertEquals($this->startOBLevel, $level, "Output buffer was left active by somethng");
    }

    function testWithoutView() {
        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
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
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $renderer = new JigRender($jigConfig, $provider);

        $contents = $renderer->renderTemplateFile('basic/filterTest');
        //$this->assertContains("This is the simplest template.", $contents);
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

//        $title = 'Mr';
//        $user = 'Ackers';
//        $this->viewModel->setVariable('title', $title);
//        $this->viewModel->setVariable('user', $user);

        $renderedText = $this->jigRenderer->renderTemplateFromString($templateString, "testStringExtendsConversion123");

//        $this->assertContains('Mr', $renderedText);
//        $this->assertContains($user, $renderedText);
    }

    function testBasicCoversExistsConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
//        $renderer = clone $this->jigRenderer;
//        $renderer->setCompileCheck(JigRender::COMPILE_CHECK_EXISTS);

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_CHECK_EXISTS
        );

        $provider = new \Auryn\Provider();
        $provider->share($jigConfig);
        $provider->share($provider);

        $renderer = $provider->make(
            '\Jig\Tests\ExtendedJigRender',
            [':jigConfig' , $jigConfig,
                ':provider',   $provider
            ]
        );

//        $renderer->bindViewModel($this->viewModel);
        $this->viewModel->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');

        $contents = $renderer->renderTemplateFile('basic/basic', $this->viewModel);
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }


    /**
     * @ preserveGlobal State disabled
     */
    function testNonExistentConversion(){
        $this->setExpectedException('Jig\JigException');
//        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('nonExistantFile');
    }

    function testMtimeCachesConversion(){

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_CHECK_MTIME
        );

        $jigRenderer = new JigRender(
            $jigConfig,
            new \Auryn\Provider()
        );

        $viewModel = new PlaceHolderView();

        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

        $contents = $jigRenderer->renderTemplateFile('basic/basic', $this->viewModel);
        //$jigRenderer->renderTemplateFile('basic/basic');
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
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        
        $this->jigRenderer->mapClasses(array('parent' => 'dynamicExtend/parent1'));
        $contents = $this->jigRenderer->renderTemplateFile('dynamicExtend/dynamicChild');

        $this->assertContains("This is the child content.", $contents);
        $this->assertContains("This is the parent 1 start.", $contents);
        $this->assertContains("This is the parent 1 end.", $contents);
    }

    function testDynamicExtends2() {
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

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
        $this->jigRenderer->bindProcessedBlock('htmlEntityDecode', [$this->viewModel, 'htmlEntityDecode']);
        $contents = $this->jigRenderer->renderTemplateFile('binding/blocks', $this->viewModel);
        $this->assertContains("€¥™<>", $contents);
        $this->assertContains("This is a variable", $contents);
    }

    function testBlockEscapingFromString() {
        $string = file_get_contents(__DIR__."/templates/binding/blocks.php.tpl");
        $this->jigRenderer->bindProcessedBlock('htmlEntityDecode', [$this->viewModel, 'htmlEntityDecode']);
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

//    function testDyanmicExtendsDoesntExist() {
//        $templateName = 'coverage/borkedDynamicExtendsDoesntExist';
//        $this->jigRenderer->renderTemplateFile($templateName);
//        //$this->jigRenderer->deleteCompiledFile($templateName);
//    }


    
    
//    function testForEachFromVariable() {
//        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/foreachFromVariable');
//        $this->assertContains('123', $contents);
//    }
//    


    function testWithoutNameSpace() {

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
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
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
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
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
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

    function testBlockPostProcess(){
        $blockStartCallCount = 0;
        $blockEndCallCount = 0;
        $warningBlockStart = function () use (&$blockStartCallCount) {
            $blockStartCallCount++;
            return "processedBlockStart";
        };

        $warningBlockEnd = function ($contents) use (&$blockEndCallCount) {
            $blockEndCallCount++;
            return $contents."processedBlockEnd";
        };
        

        $this->jigRenderer->bindProcessedBlock(
            'warning',
            $warningBlockEnd,
            $warningBlockStart
        );
        
        $contents = $this->jigRenderer->renderTemplateFile('block/blockProcess');
        
        $this->assertEquals($blockStartCallCount, 1);
        $this->assertEquals($blockEndCallCount, 1);

        $this->assertContains("This is in a warning block", $contents);
        $this->assertContains("processedBlockEnd", $contents);
        $this->assertContains("processedBlockStart", $contents);
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