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

        $this->jigRenderer->bindViewModel($this->viewModel);
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
        $contents = $this->jigRenderer->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }


    function testForeachConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/foreachTest.php");
        $this->viewModel->setVariable('colors', ['red', 'green', 'blue']);
        $this->viewModel->bindFunction('getColors', function (){ return ['red', 'green', 'blue'];});
        $contents = $this->jigRenderer->renderTemplateFile('basic/foreachTest');
        $this->assertContains("Direct: redgreenblue", $contents);
        $this->assertContains("Assigned: redgreenblue", $contents);
        $this->assertContains("Fromfunction: redgreenblue", $contents);
    }



    function testDependencyInsertionConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/DependencyInsertion');
        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }

    function testFunctionCall() {
        $contents = $this->jigRenderer->renderTemplateFile('basic/functionCall');
        $hasBeenCalled = $this->viewModel->hasBeenCalled('someFunction', '$("#myTable").tablesorter();');
        $this->assertTrue($hasBeenCalled);
        $this->assertContains("checkRole works", $contents);
    }

    function testBasicCapturingConversion() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }

    function testStringConversion() {
        $templateString = 'Hello there {$title} {$user} !!!!';

        $title = 'Mr';
        $user = 'Ackersgaah';
        $this->viewModel->setVariable('title', $title);
        $this->viewModel->setVariable('user', $user);
        $renderedText = $this->jigRenderer->renderTemplateFromString($templateString, "test123");

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

        $renderer->bindViewModel($this->viewModel);
        $this->viewModel->bindFunction('testCallableFunction', 'Tests\PHPTemplate\testCallableFunction');

        $contents = $renderer->renderTemplateFile('basic/basic');
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
        $jigRenderer->bindViewModel($viewModel);

        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

        $contents = $jigRenderer->renderTemplateFile('basic/basic');
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

        $contents = $this->jigRenderer->renderTemplateFile('binding/binding');
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

        $contents = $this->jigRenderer->renderTemplateFile('assigning/assigning');
        $this->assertContains($variable1, $contents);
        $this->assertContains($variableArray['index1'], $contents);
        $this->assertContains($objectMessage, $contents);
    }

    function testBlockEscaping() {
        $this->viewModel->setVariable('variable1', "This is a variable");
        $this->jigRenderer->bindProcessedBlock('htmlEntityDecode', [$this->viewModel, 'htmlEntityDecode']);
        $contents = $this->jigRenderer->renderTemplateFile('binding/blocks');
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
        $contents = $this->jigRenderer->renderTemplateFile('includeFile/dynamicIncludeTest');
        $this->assertContains("This is the included file.", $contents);
    }


    function testCoverageConversion(){
        $this->viewModel->setVariable('filteredVar', '<b>bold</b>');
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigRenderer->renderTemplateFile('coverageTesting/coverage');
        $this->assertContains('comment inside', $contents);
        $this->assertContains('<b>bold</b>', $contents);
        $this->assertContains('test is 5', $contents);
    }


    function testBlockPostProcess(){
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        
        $contents = $this->jigRenderer->renderTemplateFile('block/spoiler');
        $this->assertContains("is in a spoiler", $contents);
    }
}

}//end namespace


?>