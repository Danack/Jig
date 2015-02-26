<?php


use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\PlaceHolder\PlaceHolderView;
use Jig\JigRender;
use Jig\ViewModel\BasicViewModel;


class BugTest extends \Jig\Base\BaseTestCase {

    /**
     * @var \Jig\PlaceHolder\PlaceHolderView
     */
    private $viewModel;

    /**
     * @var \Jig\JigRender
     */
    private $jigRenderer;

    /**
     * 
     */
    function setUp() {

        parent::setUp();

        $templateDirectory = dirname(__DIR__)."/../templates/";
        $compileDirectory = dirname(__DIR__)."/../../tmp/generatedTemplates/";
        $this->viewModel = new PlaceHolderView();

        $jigConfig = new JigConfig(
            $templateDirectory,
            $compileDirectory,
            "php.tpl",
            JigRender::COMPILE_ALWAYS
        );

        $provider = new \Auryn\Provider();
        $provider->alias('Auryn\Injector', 'Auryn\Provider');
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

    /**
     * @throws Exception
     * @throws \Jig\JigException
     */
    function testQuotes() {
        $testLines = [
            "Single 'quotes'",
            "Double \"quotes\"",
            "Single-double '\"quotes\"'",
            "Double-single \"'quotes'\"",
        ];

        $templateString = implode("\n{\$foo}\n", $testLines);
        $this->viewModel->setVariable('foo', 'bar');

        $renderedText = $this->jigRenderer->renderTemplateFromString($templateString, "bug123", $this->viewModel);

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


    function testQuotesInTemplate() {
        //@unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        $contents = $this->jigRenderer->renderTemplateFile('bugs/quotes', $this->viewModel);
        $this->assertContains('content: " ";', $contents);
    }
}




