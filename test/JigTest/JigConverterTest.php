<?php

namespace JigTest;

use Jig\Jig;
use Jig\JigDispatcher;
use Jig\Converter\JigConverter;
use Jig\JigConfig;
use Jig\JigException;
use JigTest\PlaceHolder\PlaceHolderPlugin;
use Jig\Bridge\ZendEscaperBridge;
use Zend\Escaper\Escaper as ZendEscaper;
use Jig\JigCompilePath;
use Jig\JigTemplatePath;

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
    private $jigDispatcher = null;

    public function setUp()
    {
        parent::setUp();

        $this->compileDirectory = new JigCompilePath(dirname(__DIR__)."/./../tmp/generatedTemplates/");
        $this->templateDirectory = new JigTemplatePath(dirname(__DIR__)."/./templates/");

        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            Jig::COMPILE_ALWAYS,
            "php.tpl"
        );

        $injector = new \Auryn\Injector();
        $this->injector = $injector;

        $escaper = new ZendEscaperBridge(new ZendEscaper());
        $this->injector->alias('Jig\Escaper', get_class($escaper));
        $this->injector->share($escaper);
        
        $this->jigDispatcher = new JigDispatcher(
            $jigConfig,
            $injector
        );
    }

    public function teardown()
    {
        parent::teardown();
    }

    /**
     * @group DEBUGGING
     */
    
    public function testBasicDebugging()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/debugging.php");
        $contents = $this->jigDispatcher->renderTemplateFile('basic/debugging');
        $this->assertContains("debugging test passed.", $contents);
    }
    
    /**
     * @group DEBsdsdUGGING
     */
    
    public function testBasicConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigDispatcher->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
        $this->assertContains("new line\nstarts here", $contents);
    }

    /**
     * @group basic
     */
    public function testForeachConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/foreachTest.php");
        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jigDispatcher->renderTemplateFile('basic/foreachTest');
        $this->assertContains("Direct: redgreenblue", $contents);
        $this->assertContains("From function: redgreenblue", $contents);
    }

    /**
     * @group helper
     */
    public function testHelperBasic()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('basic/helper');
        $this->assertContains(PlaceHolderPlugin::GREETINGS_MESSAGE, $contents);
    }

    /**
     * @group blah
     */
    public function testDependencyInsertionConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/DependencyInsertion.php");
        //$contents = $this->jig->renderTemplateFile('basic/DependencyInsertion', $this->viewModel);

        $templateName = 'basic/DependencyInsertion';
        //$className = $this->jigRender->getClassName('basic/DependencyInsertion');
        $className = $this->jigDispatcher->getFQCNFromTemplateName('basic/DependencyInsertion');
        $this->jigDispatcher->compile($templateName);

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("Twitter", $contents);
        $this->assertContains("Stackoverflow", $contents);
    }

    public function testFunctionCall()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('basic/functionCall');
        $this->assertContains("checkRole works", $contents);
    }

    
    public function testStringExtendsConversion()
    {
        $templateString = <<< END
{extends file='extendTest/parentTemplate'}
{block name='secondBlock'}
This is the second child block.
{/block}

END;

        $renderedText = $this->jigDispatcher->renderTemplateFromString(
            $templateString,
            "testStringExtendsConversion123"
        );
    }

    public function testBasicCoversExistsConversion()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigDispatcher->renderTemplateFile('basic/basic');
        $this->assertContains("Basic test passed.", $contents);
    }

    public function testNonExistentConversion()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jigDispatcher->renderTemplateFile('nonExistantFile');
    }

    public function testMtimeCachesConversion()
    {
        $this->jigDispatcher->deleteCompiledFile('basic/simplest');
        $contents = $this->jigDispatcher->renderTemplateFile('basic/simplest');
        $this->assertContains("Hello, this is a template.", $contents);
    }

    /**
     * @group comments
     */
    public function testBasicComment()
    {
        @unlink(__DIR__."/generatedTemplates/Intahwebz/PHPCompiledTemplate/basic.php");
        $contents = $this->jigDispatcher->renderTemplateFile('basic/comments');
        
        $this->assertContains("Basic comment test passed.", $contents);
        $this->assertContains("bar", $contents);
    }

    public function testIncludeConversion()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('includeFile/includeTest');
        $this->assertContains("Include test passed.", $contents);
    }

    /**
     * @group blah
     */
    public function testStandardExtends()
    {
        $className = $this->jigDispatcher->getFQCNFromTemplateName('extendTest/child');
        $this->jigDispatcher->compile('extendTest/child');

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("This is the second child block.", $contents);
        $this->assertContains(\JigTest\PlaceHolder\ChildDependency::OUTPUT, $contents);
        $this->assertContains(\JigTest\PlaceHolder\ParentDependency::OUTPUT, $contents);
    }

    /**
     * @group functions
     */
    public function testFunctionBinding()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('binding/binding');
        $this->assertContains(
            \JigTest\PlaceHolder\PlaceHolderPlugin::FUNCTION_MESSAGE,
            $contents
        );
    }

    /**
     * @group functions
     */
    public function testBlockEscaping()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('binding/blocks');
        $this->assertContains("€¥™<>", $contents);

    }

    public function testBlockEscapingFromString()
    {
        $string = <<< END

{htmlEntityDecode}
&euro;&yen;&trade;&lt;&gt;
{/htmlEntityDecode}

The above should be decoded to characters

END;

        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        
        $contents = $this->jigDispatcher->renderTemplateFromString(
            $string,
            'Foo1'
        );
        $this->assertContains("€¥™<>", $contents);
    }

    public function testDynamicInclude()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('includeFile/dynamicIncludeTest');
        $this->assertContains("This is include 1.", $contents);
    }

    /**
     * @group blah
     */
    public function testInclude()
    {
        $templateName = 'includeFile/includeTest';
        $className = $this->jigDispatcher->getFQCNFromTemplateName($templateName);
        $this->jigDispatcher->compile($templateName);

        $contents = $this->injector->execute([$className, 'render']);

        $this->assertContains("Included start", $contents);
        $this->assertContains("Included end", $contents);
        $this->assertContains("This is an include test.", $contents);
        $this->assertContains("This is a foo", $contents);
    }


    public function testNoOutput()
    {
        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jigDispatcher->renderTemplateFile('coverageTesting/nooutput');
        $this->assertEquals(0, strlen(trim($contents)), "Output of [$contents] found when none expected.");
    }

    public function testIsset()
    {
        $contents = $this->jigDispatcher->renderTemplateFile('coverageTesting/checkIsset');
        $this->assertEquals(0, strlen(trim($contents)));
    }

    public function testBadIssetCall()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jigDispatcher->renderTemplateFile('coverageTesting/badIssetCall');
    }

    public function testFunctionNotBound()
    {
        $this->setExpectedException('Jig\JigException');
        $this->jigDispatcher->renderTemplateFile('coverageTesting/functionNotDefined');
    }

    public function testInjectBadName1()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $this->jigDispatcher->renderTemplateFile('coverageTesting/injectBadName1');
    }

    public function testInjectBadName2()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get name for injection");
        $viewModel = new PlaceHolderPlugin();
        $this->jigDispatcher->renderTemplateFile(
            'coverageTesting/injectBadName2',
            $viewModel
        );
    }

    public function testInjectBadValue1()
    {
        $this->setExpectedException('Jig\JigException', "Value must not be zero length");
        $viewModel = new PlaceHolderPlugin();
        $this->jigDispatcher->renderTemplateFile(
            'coverageTesting/injectBadValue1',
            $viewModel
        );
    }

    public function testInjectBadValue2()
    {
        $this->setExpectedException('Jig\JigException', "Failed to get value for injection");
        $viewModel = new PlaceHolderPlugin();
        $this->jigDispatcher->renderTemplateFile(
            'coverageTesting/injectBadValue2',
            $viewModel
        );
    }


    public function testBorkedCode()
    {
        $this->setExpectedException('Jig\JigException', "Failed to parse code");
        $this->jigDispatcher->renderTemplateFile(
            'coverageTesting/borkedCode'
        );
    }

    public function testBorkedExtends()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigDispatcher->renderTemplateFile('coverageTesting/borkedExtends');
    }

    public function testBorkedInclude1()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigDispatcher->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    public function testBorkedInclude2()
    {
        $this->setExpectedException('Jig\JigException', "Could not extract filename");
        $this->jigDispatcher->renderTemplateFile('coverageTesting/borkedInclude1');
    }

    public function testBlockNotSet()
    {
        $this->setExpectedException('Jig\JigException', '', JigException::UNKNOWN_BLOCK);
        $this->jigDispatcher->renderTemplateFile('coverageTesting/blockNotSet');
    }

    public function testStringCoverageObject()
    {
        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $this->setExpectedException('Jig\JigException');
        $this->jigDispatcher->renderTemplateFile('coverageTesting/stringCoverageObject');
    }

    public function testStringCoverageArray()
    {
        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        //TODO - make this test be resilient to the error message being changed.
        $this->setExpectedException(
            'Jig\JigException',
            \Jig\EscapeException::IMPLICIT_ARRAY_TO_STRING
        );
        $this->jigDispatcher->renderTemplateFile('coverageTesting/stringCoverageArray');
    }

    public function testDelete()
    {
        $templateName = 'basic/simplest';
        $this->jigDispatcher->renderTemplateFile($templateName);
        $this->jigDispatcher->deleteCompiledFile($templateName);
    }

    //TODO - This needs some assertion.
//    function testWithoutNameSpace() {
//        $this->jig->renderTemplateFile(
//            "templateInRoot",
//            $this->emptyHelper
//        );
//    }

    public function testCheckExistsCoverage()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            Jig::COMPILE_CHECK_EXISTS,
            "php.tpl"
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jig = $provider->make('Jig\JigDispatcher');
        $jig->renderTemplateFile("basic/simplest");
        $jig->renderTemplateFile("basic/simplest");
    }

    /**
     * @group debugging
     */
    public function testCheckMtimeCoverage()
    {
        $jigConfig = new JigConfig(
            $this->templateDirectory,
            $this->compileDirectory,
            Jig::COMPILE_CHECK_MTIME,
            "php.tpl"
        );

        $provider = new \Auryn\Injector();
        $provider->share($jigConfig);
        $provider->share($provider);

        $jig = new \Jig\JigDispatcher($jigConfig, $provider);
        $templateName = "coverageTesting/mtimeonce";
        $this->jigDispatcher->deleteCompiledFile($templateName);
        $jig->renderTemplateFile($templateName);
        $filename = $jig->getCompiledFilenameFromTemplateName($templateName);
        touch($filename, time() - 3600); // will break if the test takes an hour ;)
        $jig->renderTemplateFile($templateName);
    }

    /**
     *
     */
    public function testRenderBlock()
    {
        $blockRender = $this->injector->make('JigTest\PlaceHolder\PlaceHolderPlugin');
        
        $this->jigDispatcher->addPlugin($blockRender);
        $contents = $this->jigDispatcher->renderTemplateFile('block/renderBlock');

        $this->assertEquals(1, $blockRender->blockStartCallCount);
        $this->assertEquals(1, $blockRender->blockEndCallCount);
        $this->assertEquals("foo='bar'", $blockRender->passedSegementText);
        $this->assertContains("This is in a warning block", $contents);
        $this->assertContains("</span>", $contents);
        $this->assertContains("<span class='warning'>", $contents);

        $this->assertContains("This is in a warning block", $contents);
    }

    /**
     *
     */
    public function testCompileBlock()
    {
        $blockStartCallCount = 0;
        $blockEndCallCount = 0;

        $passedSegementText = null;

        $compileBlockStart = function (JigConverter $jigConverter, $segmentText) use (
            &$blockStartCallCount,
            &$passedSegementText
        ) {
            $blockStartCallCount++;
            $jigConverter->addText("compileBlockStart");
            $jigConverter->addText($segmentText);
            $passedSegementText = $segmentText;
        };

        $compileBlockEnd = function (JigConverter $jigConverter) use (&$blockEndCallCount) {
            $blockEndCallCount++;
            $jigConverter->addText("compileBlockEnd");
        };


        $this->jigDispatcher->bindCompileBlock(
            'compile',
            $compileBlockStart,
            $compileBlockEnd
        );

        $this->jigDispatcher->deleteCompiledFile('block/compileBlock');
        $contents = $this->jigDispatcher->renderTemplateFile('block/compileBlock');

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

    public function testRenderFromStringJigExceptionHandling()
    {
        $this->setExpectedException('Jig\JigException', "Could not parse template segment");
        $templateString = "This is an invalid template {not valid construct}";
        $this->jigDispatcher->renderTemplateFromString($templateString, "Exception1");
    }

    public function testRenderFromStringGenericExceptionHandling()
    {
        $this->setExpectedException('Jig\JigException', PlaceHolderPlugin::MESSAGE);
        $templateString = "
    {plugin type='JigTest\\PlaceHolder\\PlaceHolderPlugin'}
    
    This throws {throwup()}";
        $this->jigDispatcher->renderTemplateFromString($templateString, "Exception1");
    }

    /**
     * @group inlinephp
     */
    public function testCheckInlinePHP()
    {
        $contents = $this->jigDispatcher->renderTemplateFile("testCheckInlinePHP/testCheckInlinePHP");
        $this->assertContains('value is 5', $contents);
    }

    /**
     * @group filtertest
     */
    public function testFilterBinding()
    {
        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');
        $contents = $this->jigDispatcher->renderTemplateFile("filter/defaultFilter");
        $this->assertContains('HELLO', $contents);
    }

    public function testFilterInjection()
    {
        $contents = $this->jigDispatcher->renderTemplateFile("filter/injectedFilter");
        $this->assertContains('HELLO', $contents);
    }

    public function testUnknownFilter()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            \Jig\JigException::UNKNOWN_FILTER
        );

        $contents = $this->jigDispatcher->renderTemplateFile("filter/unknownFilter");
    }

    /**
     * @group injection
     */
    public function testErrorUnknownVariable()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            JigException::UNKNOWN_VARIABLE
        );

        $this->jigDispatcher->compile("unknownVariable/unknownVariable");
    }
    
    public function testErrorUnknownVariableForEach()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            JigException::UNKNOWN_VARIABLE
        );

        $this->jigDispatcher->compile("unknownVariableForEach/unknownVariableForEach");
    }
    
    public function testUnknownVariableWithFunction()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            JigException::UNKNOWN_VARIABLE
        );

        $this->jigDispatcher->compile("unknownVariableWithFunction/unknownVariableWithFunction");
    }
    
    public function testInjectVariableAsTwoTypes()
    {
        $this->setExpectedException(
            'Jig\JigException',
            '',
            JigException::INJECTION_ERROR
        );

        $this->jigDispatcher->compile("injectVariableAsTwoTypes/injectVariableAsTwoTypes");
    }

    public function bindTestStart(JigConverter $jigConverter, $segmentText)
    {
        $jigConverter->addText("Segment text was ".$segmentText);
        $jigConverter->addText("This is the start");
    }
    
    /**
     * @param JigConverter $jigConverter
     * @param $segmentText
     */
    public function bindTestEnd(JigConverter $jigConverter, $segmentText)
    {
        $jigConverter->addText("Segment text was ".$segmentText);
        $jigConverter->addText("This is the end");
    }

    /**
     * @group compiletime
     */
    public function testContainsPHPOpening()
    {
        $this->jigDispatcher->bindCompileBlock(
            'bindTest',
            [$this, 'bindTestStart'],
            [$this, 'bindTestEnd']
        );
        
        $result = $this->jigDispatcher->renderTemplateFile('testContainsPHPOpening');

        $this->assertContains('<?php', $result);
        $this->assertContains('?>', $result);
    }
    
    /**
     * @group phptags
     */
    public function testLiteralPHPOpening()
    {
        $result = $this->jigDispatcher->renderTemplateFile('testLiteralPHPOpening');
        $this->assertContains('{php}', $result);
    }

    /**
     * @group debug
     */
    public function testStubReqeatOnlyInsertedOnce()
    {
        $templateString = <<< TPL
{plugin type='JigTest\\PlaceHolder\\PlaceHolderPlugin'}
{plugin type='JigTest\\PlaceHolder\\PlaceHolderPlugin'}
TPL;

        $this->jigDispatcher->addDefaultPlugin('JigTest\PlaceHolder\PlaceHolderPlugin');

        $className = $this->jigDispatcher->getParsedTemplateFromString(
            $templateString,
            "testStubReqeatOnlyInsertedOnce1"
        );

        $result = call_user_func([$className, 'getDependencyList']);
    }

    public function testDuplicateCompilationDoesNotError()
    {
        $templateString = <<< TPL
Hello, I am a template
TPL;

        $this->jigDispatcher->renderTemplateFromString(
            $templateString,
            "testDuplicateCompilationDoesNotError1"
        );
        $this->jigDispatcher->renderTemplateFromString(
            $templateString,
            "testDuplicateCompilationDoesNotError1"
        );
    }

    public function testUpdatedIncludedTemplateIsCompiled()
    {
        $directory = realpath(__DIR__.'/../templates/includedTemplateIsCompiled/');
        $time = ''.time();
        file_put_contents(
            $directory.'/fileToInclude.php.tpl',
            " //This file is intentionally not in git.
            Time is $time"
        );

        $renderedOutput = $this->jigDispatcher->renderTemplateFile(
            'includedTemplateIsCompiled/includedTemplateIsCompiled'
        );
        $this->assertContains($time, $renderedOutput);
    }
    
    
    public function testUpdatedExtendedTemplateIsCompiled()
    {
        $directory = realpath(__DIR__.'/../templates/extendedTemplateIsCompiled/');
        $time = ''.time();
        
        $baseTemplate = <<< TPL

{block name='overridden'}
    This file is intentionally not in git.
    This is overridden by the extending template.
{/block}

{block name='notoverridden'}
    Time is $time"
{/block}
TPL;

        file_put_contents(
            $directory.'/fileToExtend.php.tpl',
            $baseTemplate
        );

        $renderedOutput = $this->jigDispatcher->renderTemplateFile(
            'extendedTemplateIsCompiled/extendedTemplateIsCompiled'
        );
        $this->assertContains($time, $renderedOutput);
    }
    
    
    /**
     * @group phptags
     * @group commentsbug
     */
    public function testCommentInsideLiteral()
    {
        $result = $this->jigDispatcher->renderTemplateFile('coverageTesting/commentInsideLiteral');
        $this->assertContains('{* This is a comment *}', $result);
    }

    /**
     * @group security
     */
    public function testEscapeJS()
    {
        $result = $this->jigDispatcher->renderTemplateFile('escapeJS/escapeJS');
    }
    
    /**
     * This checks that no spurious characters have been outputted.
     * The input template files are empty of characters.
     *
     * @group debuggg
     */
    public function testwhitespaceOnly()
    {
        $output = $this->jigDispatcher->renderTemplateFile('whitespaceOnly/whitespaceParent');
        $trimmedOutput = trim($output);
        $this->assertEquals(0, strlen($trimmedOutput), "non-whitespace characters detected in '$output'");
    }

    /**
     *
     */
    public function testForeachNoNewLines()
    {
        $templateString = <<< 'TPL'
{$foo = [1, 2, 3]}
{foreach $foo as $bar}
{$bar}
{/foreach}
TPL;
        $cacheID = "testForeachNoNewLines/testForeachNoNewLines".time();

        $output = $this->jigDispatcher->renderTemplateFromString(
            $templateString,
            $cacheID
        );
        $this->assertContains("1\n2\n3\n", $output);


        $output = $this->jigDispatcher->renderTemplateFile(
            'bugs/foreachNewLines_12'
        );

        $this->assertContains("1\n2\n3\n", $output);
    }
}
