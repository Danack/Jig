<?php

namespace JigTest;

use Jig\Jig;
use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\JigCompilePath;
use Jig\JigTemplatePath;

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

        $templatePath = new JigTemplatePath(dirname(__DIR__)."/./templates/");
        $compilePath = new JigCompilePath(dirname(__DIR__)."/./../tmp/generatedTemplates/");

        $jigConfig = new JigConfig(
            $templatePath,
            $compilePath,
            Jig::COMPILE_ALWAYS,
            "php.tpl"
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

    public function testEscapingNull()
    {
        $contents = $this->jig->renderTemplateFile('bugs/escapingNull');
        
        $this->assertContains('foo is []', $contents);
        $this->assertContains('Fin.', $contents);
    }

    public function testQuotesInTemplate()
    {
        $contents = $this->jig->renderTemplateFile('bugs/quotes');
        $this->assertContains('content: " ";', $contents);
    }
    
    public function testBlocksInsideLiteral()
    {
        $contents = $this->jig->renderTemplateFile('bugs/blocksInsideLiteral');
        $this->assertContains(
            "{trim}\n{/trim}",
            $contents
        );
    }
    
    public function testLiteralInsideBlocks()
    {
        $contents = $this->jig->renderTemplateFile('bugs/literalInsideBlocks');
        
        $this->assertEquals("    {foo is bar}\nend", $contents);
    }

    public function testcheckNoExtraLines()
    {
        $contents = $this->jig->renderTemplateFile('block/checkNoExtraLines');
        $this->assertEquals(4, count(explode("\n", $contents)), "Rendering is inserting extra lines.");
    }
}
