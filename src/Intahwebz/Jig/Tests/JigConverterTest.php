<?php



namespace Intahwebz\Tests\PHPTemplate;


use Intahwebz\Jig\Converter;
//use Intahwebz\Jig\JigBase;
use Intahwebz\Jig\Tests\PlaceHolderView;
use Intahwebz\Jig\Converter\JigConverter;
use Intahwebz\Jig\Tests\JigTestException;






class JigTest extends \PHPUnit_Framework_TestCase {


	protected function setUp(){
        ob_start();
	}

	protected function tearDown(){
        ob_end_clean();
	}




    /**
     * @expectedException \Intahwebz\Jig\Tests\JigTestException
     */
    function testConversion(){

        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

        $phpTemplateConverter = new JigConverter();

        $phpTemplateConverter->init(
            __DIR__."/templates/",
            __DIR__."/generatedTemplates/",
            "php.tpl"
        );

        $phpTemplateConverter->setForceCompile(true);
        $parsedTemplateClassName = $phpTemplateConverter->getParsedTemplate('basic', array());

        require_once(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");

        $view = new PlaceHolderView();

        $template = new $parsedTemplateClassName($view, array());

        /**
         * var Jig
         */


        $template->render($view);

    }





}




?>