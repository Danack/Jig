<?php


namespace JigTest;


use Jig\Jig;
use Jig\JigDispatcher;
use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\JigException;
use JigTest\PlaceHolder\PlaceHolderPlugin;


class JigConverterTest extends BaseTestCase
{
    private $templateDirectory;

    private $compileDirectory;

    /**
     * @var \Auryn\Injector
     */
    private $injector;

    /**
     * @var \Jig\JigDispatcher
     */
    private $jig = null;

    function setUp()
    {
        parent::setUp();

        $this->templateDirectory = dirname(__DIR__)."/./templates/";
        $this->compileDirectory = dirname(__DIR__)."/./../tmp/generatedTemplates/";

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_ALWAYS
        );

        $injector = new \Auryn\Injector();
        $this->injector = $injector;
        
        $this->jig = new JigDispatcher($jigConfig, $injector);
    }

    public function teardown()
    {
        parent::teardown();
    }

    function testBasicConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jig->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
    }

    /**
     * @group basic
     */
    function testForeachConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/foreachTest.php");
        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jig->renderTemplateFile('basic/foreachTest');
        $this->assertContains("Direct: redgreenblue", $contents);
        $this->assertContains("From function: redgreenblue", $contents);
    }

    /**
     * @group helper
     */
    function testHelperBasic()
    {
        $contents = $this->jig->renderTemplateFile('basic/helper');
        $this->assertContains(PlaceHolderPlugin::greetings_message, $contents);
    }

    /**
     * @group blah
     */
    function testDependencyInsertionConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        //$contents = $this->jig->renderTemplateFile('basic/DependencyInsertion', $this->viewModel);

        $templateName = 'basic/DependencyInsertion';
        //$className = $this->jigRender->getClassName('basic/DependencyInsertion');
        $className = $this->jig->getTemplateCompiledClassname('basic/DependencyInsertion');
        $this->jig->checkTemplateCompiled($templateName);

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }

    function testFunctionCall()
    {
        $contents = $this->jig->renderTemplateFile('basic/functionCall');
        $this->assertContains("checkRole works", $contents);
    }

    
    function testStringExtendsConversion()
    {
        $templateString = <<< END
{extends file='extendTest/parentTemplate'}
{block name='secondBlock'}
This is the second child block.
{/block}

END;

        $renderedText = $this->jig->renderTemplateFromString(
            $templateString,
            "testStringExtendsConversion123"
        );
    }

    function testBasicCoversExistsConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jig->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
    }

    function testNonExistentConversion()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('nonExistantFile');
    }

    function testMtimeCachesConversion()
    {
        $this->jig->deleteCompiledFile('basic/simplest');
        $contents = $this->jig->renderTemplateFile('basic/simplest');
        $this->assertContains("Hello, this is a template.", $contents);
    }

    function testBasicComment()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jig->renderTemplateFile('basic/comments');
        $this->assertContains("Basic comment test passed.", $contents);
    }

    function testIncludeConversion()
    {
        $contents = $this->jig->renderTemplateFile('includeFile/includeTest');
        $this->assertContains("Include test passed.", $contents);
    }

    /**
     * @group blah
     */
    function testStandardExtends()
    {
        $className = $this->jig->getTemplateCompiledClassname('extendTest/child');
        $this->jig->checkTemplateCompiled('extendTest/child');

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("This is the second child block.", $contents);
        $this->assertContains(\JigTest\PlaceHolder\ChildDependency::output, $contents);
        $this->assertContains(\JigTest\PlaceHolder\ParentDependency::output, $contents);
    }

    /**
     * @group functions
     */
    function testFunctionBinding()
    {
        $contents = $this->jig->renderTemplateFile('binding/binding');
        $this->assertContains(
            \JigTest\PlaceHolder\PlaceHolderPlugin::FUNCTION_MESSAGE,
            $contents
        );
    }

    /**
     * @group functions
     */
    function testBlockEscaping()
    {
        $contents = $this->jig->renderTemplateFile('binding/blocks');
        $this->assertContains("€¥™<>", $contents);

    }

    function testBlockEscapingFromString()
    {
        $string = <<< END

{htmlEntityDecode}
&euro;&yen;&trade;&lt;&gt;
{/htmlEntityDecode}

The above should be decoded to characters

END;

        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        
        $contents = $this->jig->renderTemplateFromString(
            $string,
            'Foo1'
        );
        $this->assertContains("€¥™<>", $contents);
    }

    function testDynamicInclude()
    {
        $contents = $this->jig->renderTemplateFile('includeFile/dynamicIncludeTest');
        $this->assertContains("This is include 1.", $contents);
    }

    /**
     * @group blah
     */
    function testInclude()
    {
        $templateName = 'includeFile/includeTest';
        $className = $this->jig->getTemplateCompiledClassname($templateName);
        $this->jig->checkTemplateCompiled($templateName);

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("Included start", $contents);
        $this->assertContains("Included end", $contents);
        $this->assertContains("This is an include test.", $contents);
        $this->assertContains("This is a foo", $contents);
    }


    function testNoOutput()
    {
        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jig->renderTemplateFile('coverageTesting/nooutput');
        $this->assertEquals(0, strlen(trim($contents)), "Output of [$contents] found when none expected.");
    }

    function testIsset()
    {
        $contents = $this->jig->renderTemplateFile('coverageTesting/checkIsset');
        $this->assertEquals(0, strlen(trim($contents)));
    }

    function testBadIssetCall()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('coverageTesting/badIssetCall');
    }

    function testFunctionNotBound()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('coverageTesting/functionNotDefined');
    }

    function testInjectBadName1()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $this->jig->renderTemplateFile('coverageTesting/injectBadName1');
    }

    function testInjectBadName2()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new PlaceHolderPlugin();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadName2',
            $viewModel
        );
    }

    function testInjectBadValue1()
    {
        $this->setExpectedException('Jig\JigException', "Value must not be zero length");
        $viewModel = new PlaceHolderPlugin();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadValue1',
            $viewModel
        );
    }

    function testInjectBadValue2()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get value for injection");
        $viewModel = new PlaceHolderPlugin();
        $this->jig->renderTemplateFile(
            'coverageTesting/injectBadValue2',
            $viewModel
        );
    }


    function testBorkedCode()
    {
        $this->setExpectedException('Jig\JigException', "Failed to parse code");
        $this->jig->renderTemplateFile(
            'coverageTesting/borkedCode'
        );
    }

    function testBorkedExtends()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedExtends');
    }

    function testBorkedInclude1()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    function testBorkedInclude2()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jig->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    function testBlockNotSet()
    {
        $this->setExpectedException('Jig\JigException', '', JigException::UNKNOWN_BLOCK);
        $this->jig->renderTemplateFile('coverageTesting/blockNotSet');
    }

    function testStringCoverageObject()
    {

        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $this->setExpectedException('Jig\JigException');
        $this->jig->renderTemplateFile('coverageTesting/stringCoverageObject');
    }

    function testStringCoverageArray()
    {
        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $this->setExpectedException('Jig\JigException', \Jig\JigException::IMPLICIT_ARRAY_TO_STRING);
        $this->jig->renderTemplateFile('coverageTesting/stringCoverageArray');
    }

    function testDelete()
    {
        $templateName = 'basic/simplest';
        $this->jig->renderTemplateFile($templateName);
        $this->jig->deleteCompiledFile($templateName);
    }

    //TODO - This needs some assertion.
//    function testWithoutNameSpace() {
//        $this->jig->renderTemplateFile(
//            "templateInRoot",
//            $this->emptyHelper
//        );
//    }

    function testCheckExistsCoverage()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_CHECK_EXISTS
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jig = $provider->make('Jig\JigDispatcher');
        $jig->renderTemplateFile("basic/simplest");
        $jig->renderTemplateFile("basic/simplest");
    }

    function testCheckMtimeCoverage()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            "php.tpl",
            Jig::COMPILE_CHECK_MTIME
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jig = new \Jig\JigDispatcher($jigConfig, $provider);
        $templateName = "coverageTesting/mtimeonce";
        $this->jig->deleteCompiledFile($templateName);
        $jig->renderTemplateFile($templateName);
        $filename = $jig->getCompileFilename($templateName);
        touch($filename, time() - 3600); // will break if the test takes an hour ;)
        $jig->renderTemplateFile($templateName);
    }

    function testRenderBlock()
    {
        $blockRender = $this->injector->make('JigTest\PlaceHolder\PlaceHolderPlugin');
        
        $this->jig->addPlugin($blockRender);
        $contents = $this->jig->renderTemplateFile('block/renderBlock');

        $this->assertEquals(1, $blockRender->blockStartCallCount);
        $this->assertEquals(1, $blockRender->blockEndCallCount);
        $this->assertEquals("foo='bar'", $blockRender->passedSegementText);
        $this->assertContains("This is in a warning block", $contents);
        $this->assertContains("</span>", $contents);
        $this->assertContains("<span class='warning'>", $contents);

        $this->assertContains("This is in a warning block", $contents);
    }

    function testCompileBlock()
    {
        $blockStartCallCount = 0;
        $blockEndCallCount = 0;

        $passedSegementText = null;

        $compileBlockStart = function (JigConverter $jigConverter, $segmentText) use (
            &$blockStartCallCount,
            &$passedSegementText
        ) {
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

    function testRenderFromStringJigExceptionHandling()
    {
        $this->setExpectedException('Jig\JigException', "Could not parse template segment");
        $templateString = "This is an invalid template {not valid construct}";
        $this->jig->renderTemplateFromString($templateString, "Exception1");
    }

    function testRenderFromStringGenericExceptionHandling()
    {
        $this->setExpectedException('Jig\JigException', PlaceHolderPlugin::message);
        $templateString = "
    {plugin type='JigTest\\PlaceHolder\\PlaceHolderPlugin'}
    
    This throws {throwup()}";
        $this->jig->renderTemplateFromString($templateString, "Exception1");
    }

    function testCheckInlinePHP()
    {
        $contents = $this->jig->renderTemplateFile("inlinePHP/simple");
        $this->assertContains('value is 5', $contents);
    }

    /**
     * @group filtertest
     */
    function testFilterBinding()
    {
        $this->jig->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jig->renderTemplateFile("filter/defaultFilter");
        $this->assertContains('HELLO', $contents);
    }

    function testFilterInjection()
    {
        $contents = $this->jig->renderTemplateFile("filter/injectedFilter");
        $this->assertContains('HELLO', $contents);
    }

    function testUnknownFilter()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            \Jig\JigException::UNKNOWN_FILTER
        );

        $contents = $this->jig->renderTemplateFile("filter/defaultFilter");
    }
    
    function unknownVariableTemplateProvider()
    {
        return [ 
            ["errors/unknownVariable"],
            ["errors/unknownVariableForEach"],
            ["errors/unknownVariableWithFunction"]
        ];
    }

    /**
     * @group injection
     * @dataprovider unknownVariableTemplateProvider
     */
   function testUnknownVariable($templateName)
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            \Jig\JigException::UNKNOWN_VARIABLE
        );

       $this->jig->checkTemplateCompiled($templateName);
    }
}
