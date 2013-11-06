<?php

namespace  {

    function testFunction1() {
        echo "This is a global function.";
    }
}


namespace Intahwebz\Tests\PHPTemplate{


use Intahwebz\Jig\Converter;
use Intahwebz\Jig\JigConfig;
use Intahwebz\Jig\Tests\PlaceHolderView;
use Intahwebz\Jig\Tests\PlaceHolderLogger;

use Intahwebz\ViewModel;

use Intahwebz\Jig\JigRender;

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

    function classBoundFunction() {
        echo "This is a class function.";
    }

    function testFunctionCall() {
        ob_start();
        $this->jigRenderer->renderTemplateFile('basic/functionCall');
        $contents = ob_get_contents();
        ob_end_clean();

        $hasBeenCalled = $this->viewModel->hasBeenCalled('someFunction', '$("#myTable").tablesorter();');
        $this->assertTrue($hasBeenCalled);
        $this->assertContains("checkRole works", $contents);
    }

    /**
     * @var \Intahwebz\Jig\JigRender
     */
    private $jigRenderer = null;

    /**
     * @var \Intahwebz\ViewModel
     */
    private $viewModel;

    protected function setUp() {
        $this->viewModel = new PlaceHolderView();

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();

        $this->jigRenderer = new JigRender(
            new PlaceHolderLogger(),
            $jigConfig,
            $provider
        );

        $this->jigRenderer->bindViewModel($this->viewModel);

        $this->viewModel->bindFunction('testCallableFunction', 'Intahwebz\Tests\PHPTemplate\testCallableFunction');
    }


    protected function tearDown(){
        //ob_end_clean();
    }

    function testBasicConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('basic/basic');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }

    function testDependencyInsertionConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('basic/DependencyInsertion');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }


    function testBasicCapturingConversion() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

        $contents = $this->jigRenderer->renderTemplateFile('basic/basic', true);

        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }

    function testStringConversion() {
        $templateString = 'Hello there {$title} {$user} !!!!';

        $title = 'Mr';
        $user = 'Ackers';
        $this->viewModel->setVariable('title', $title);
        $this->viewModel->setVariable('user', $user);

        $renderedText = $this->jigRenderer->captureRenderTemplateString($templateString, "test123");

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

        $renderedText = $this->jigRenderer->captureRenderTemplateString($templateString, "testStringExtendsConversion123");

//        $this->assertContains('Mr', $renderedText);
//        $this->assertContains($user, $renderedText);
    }




    function testBasicCoversExistsConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();

        $renderer = clone $this->jigRenderer;

        $renderer->setCompileCheck(JigRender::COMPILE_CHECK_EXISTS);

        $renderer->renderTemplateFile('basic/basic');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }




    /**
     * @expectedException     \Intahwebz\Jig\JigException
     */
    function testNonExistantConversion(){
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('nonExistantFile');
        $contents = ob_get_contents();
        ob_end_clean();
    }




    function testMtimeCachesConversion(){

        $jigConfig = new JigConfig(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl",
            JigRender::COMPILE_CHECK_MTIME
        );

        $jigRenderer = new JigRender(
            new PlaceHolderLogger(),
            $jigConfig,
            new \Auryn\Provider()
        );

        $viewModel = new PlaceHolderView();
        $jigRenderer->bindViewModel($viewModel);

        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $jigRenderer->renderTemplateFile('basic/basic');
        $jigRenderer->renderTemplateFile('basic/basic');
        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("Function was called.", $contents);
    }


    function testBasicComment() {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('basic/comments');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Basic comment test passed.", $contents);
    }

    function testIncludeConversion(){
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('includeFile/includeTest');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("Include test passed.", $contents);
    }


    function testStandardExtends(){
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('extendTest/child');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("This is the second child block.", $contents);
    }

    function testDynamicExtends1() {
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->mapClasses(array('parent' => 'dynamicExtend/parent1'));
        $this->jigRenderer->renderTemplateFile('dynamicExtend/dynamicChild');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("This is the child content.", $contents);
        $this->assertContains("This is the parent 1 start.", $contents);
        $this->assertContains("This is the parent 1 end.", $contents);
    }

    function testDynamicExtends2() {
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->mapClasses(array('parent' => 'dynamicExtend/parent2'));
        $this->jigRenderer->renderTemplateFile('dynamicExtend/dynamicChild');

        $contents = ob_get_contents();
        ob_end_clean();
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


        ob_start();

        $this->jigRenderer->renderTemplateFile('binding/binding');

        $contents = ob_get_contents();
        ob_end_clean();
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

        ob_start();

        $this->jigRenderer->renderTemplateFile('assigning/assigning');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains($variable1, $contents);
        $this->assertContains($variableArray['index1'], $contents);
        $this->assertContains($objectMessage, $contents);
    }

    function testBlockEscaping() {
        $this->viewModel->setVariable('variable1', "This is a variable");

        $this->jigRenderer->bindProcessedBlock('htmlEntityDecode','htmlEntityDecode');
        ob_start();
        $this->jigRenderer->renderTemplateFile('binding/blocks');
        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("€¥™<>", $contents);
        $this->assertContains("This is a variable", $contents);
    }

    function testBlockEscapingFromString() {

        $string = file_get_contents(__DIR__."/templates/binding/blocks.php.tpl");

        $this->jigRenderer->bindProcessedBlock('htmlEntityDecode', 'htmlEntityDecode');
        ob_start();
        $this->jigRenderer->renderTemplateFromString($string, 'Foo1');
        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("€¥™<>", $contents);
    }




    function testDynamicInclude(){

        $this->viewModel->setVariable('dynamicInclude', "includeFile/includedFile");

        ob_start();
        $this->jigRenderer->renderTemplateFile('includeFile/dynamicIncludeTest');

        $contents = ob_get_contents();
        ob_end_clean();
        $this->assertContains("This is the included file.", $contents);
    }


    function testCoverageConversion(){

        $this->viewModel->setVariable('filteredVar', '<b>bold</b>');

        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        ob_start();
        $this->jigRenderer->renderTemplateFile('coverageTesting/coverage');

        $contents = ob_get_contents();
        ob_end_clean();

        $this->assertContains('comment inside', $contents);
        $this->assertContains('<b>bold</b>', $contents);

        $this->assertContains('test is 5', $contents);
    }

}

}//end namespace


?>