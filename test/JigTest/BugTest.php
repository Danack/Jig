<?php

namespace JigTest;

use Jig\Jig;
use Jig\Converter\JigConverter;
use Jig\JigConfig;

class BugTest extends BaseTestCase
{
    /**
     * @var \Jig\JigDispatcher
     */
    private $jig;

    private $jigRender;
    
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();

        $templateDirectory = dirname(__DIR__)."/./templates/";
        $compileDirectory = dirname(__DIR__)."/./../tmp/generatedTemplates/";

        $jigConfig = new JigConfig(
            $templateDirectory,
            $compileDirectory,
            "php.tpl",
            Jig::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);
        
        $jigConverter = new JigConverter($jigConfig);
        
        //$this->jigRender = new JigRender($jigConfig, $jigConverter);
        $this->jig = new \Jig\JigDispatcher(
            $jigConfig,
            $provider,
            $jigConverter
        );
    }

    /**
     * @throws \Exception
     * @throws \Jig\JigException
     */
    public function testQuotes()
    {
        $testLines = [
            "Single 'quotes'",
            "Double \"quotes\"",
            "Single-double '\"quotes\"'",
            "Double-single \"'quotes'\"",
        ];

        $templateString = "{\$foo = 'hello';}";
        $templateString .= implode("\n{\$foo}\n", $testLines);
        $renderedText = $this->jig->renderTemplateFromString($templateString, "testQuotes");
        
        $count = 0;
        foreach ($testLines as $testLine) {
            $errorString = sprintf(
                "Failed on quotes %d %s not found in %s ",
                $count,
                $testLine,
                $renderedText
            );

            $this->assertContains(
                $testLine,
                $renderedText,
                $errorString
            );
            $count++;
        }
    }


    public function testQuotesInTemplate()
    {
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        $contents = $this->jig->renderTemplateFile('bugs/quotes');
        $this->assertContains('content: " ";', $contents);
    }
}
