<?php



namespace Intahwebz\Tests\PHPTemplate;


use Intahwebz\Jig\Converter;
use Intahwebz\Jig\Tests\PlaceHolderView;
use Intahwebz\Jig\Converter\JigConverter;
use Intahwebz\Jig\Tests\JigTestException;

use Intahwebz\Jig\JigRender;




class JigTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Intahwebz\Jig\JigRender
     */
    private $jigRenderer = null;

	protected function setUp(){
        $viewModel = new PlaceHolderView();
        
        $this->jigRenderer = new JigRender(
            $viewModel,
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl"
        );

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



}




?>