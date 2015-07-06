<?php


namespace Jig;

use Jig\Converter\JigConverter;

JigFunctions::load();

/**
 * Class JigRender
 *
 */
class JigRender
{

    /**
     * @var Converter\JigConverter
     */
    private $jigConverter;

    /**
     * @var JigConfig
     */
    private $jigConfig;

    public function __construct(
        Jigconfig $jigConfig,
        JigConverter $jigConverter
    ) {
        $this->jigConfig = $jigConfig;
        $this->jigConverter = $jigConverter;
    }

    public function getClassName($templateFilename)
    {
        return $this->jigConfig->getFullClassname($templateFilename);
    }

    /**
     * @param $templateName
     * @return string
     */
    public function getCompileFilename($templateName)
    {
        $className = $this->jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $this->jigConfig->getCompiledFilename($className);
        
        return $compileFilename;
    }

    /**
     * @param $templateFilename
     * @return bool
     */
    public function isGeneratedFileOutOfDate($templateFilename)
    {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $classPath = getCompileFilename($templateFilename, $this->jigConverter, $this->jigConfig);
        $classPath = str_replace('\\', '/', $classPath);
        $templateTime = @filemtime($templateFullFilename);
        $classTime = @filemtime($classPath);
        
        if ($classTime < $templateTime) {
            return true;
        }

        return false;
    }

    /**
     * @param $templateFilename
     * @throws JigException
     * @return \Jig\Converter\ParsedTemplate
     */
    public function prepareTemplateFromFile($templateFilename)
    {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $fileLines = @file($templateFullFilename);

        if ($fileLines === false) {
            throw new JigException("Could not open template [".$templateFullFilename."] for reading.");
        }

        try {
            $parsedTemplate = $this->jigConverter->createFromLines($fileLines, $this);
            $className = $this->jigConverter->getClassNameFromFilename($templateFilename);
            $parsedTemplate->setClassName($className);

            return $parsedTemplate;
        }
        catch(JigException $pte) {
            throw new JigException("Error in file $templateFilename: ".$pte->getMessage(), $pte->getCode(), $pte);
        }
    }

    /**
     * @param $templateFilename
     * @throws JigException
     */
    public function checkTemplateCompiled($templateFilename)
    {
        if ($this->jigConfig->compileCheck == Jig::COMPILE_NEVER) {
            //This is useful when debugging templates. It allows you to edit the
            //generated code, without having it over-written.
            return;
        }
        
        $className = $this->jigConverter->getNamespacedClassNameFromFileName($templateFilename);
        if ($this->jigConfig->compileCheck == Jig::COMPILE_CHECK_EXISTS) {
            if (class_exists($className) == true) {
                return;
            }
        }

        if ($this->jigConfig->compileCheck == Jig::COMPILE_CHECK_MTIME) {
            if ($this->isGeneratedFileOutOfDate($templateFilename) == false) {
                if (class_exists($className) == true) {
                    return;
                }
            }
        }

        //Either class file did not exist or it was out of date. 
        $this->compileTemplate($className, $templateFilename);
    }

    /**
     * @param $className
     * @param $templateFilename
     * @throws JigException
     */
    public function compileTemplate($className, $templateFilename)
    {
        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename);
        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->checkTemplateCompiled($templateDependency);
        }

        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory
        );

        if (class_exists($className, false) == false) {
            if (function_exists('opcache_invalidate') == true) {
                opcache_invalidate($outputFilename);
            }
            //This is very stupid. We should be able to auto-load the class
            //if and only if it is required. But the Composer autoloader caches
            //the 'class doesn't exist' result from earlier, which means we
            //have to load it by hand.
            /** @noinspection PhpIncludeInspection */
            require($outputFilename);
        }
        else {
            //Warn - file was compiled when class already exists?
        }
    }

    /**
     *
     * This is an entry point
     * @param $templateString
     * @param $cacheName
     * @return mixed
     */
    public function getParsedTemplateFromString($templateString, $cacheName)
    {
        $templateString = str_replace("<?php", "&lt;php", $templateString);
        $templateString = str_replace("?>", "?&gt;", $templateString);

        $parsedTemplate = $this->jigConverter->createFromLines(array($templateString));
        $parsedTemplate->setClassName($cacheName);
        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->checkTemplateCompiled($templateDependency);
        }

        //This has to be after checking the dependencies are compiled
        //to ensure the getDepedency function is available.
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            false
        );
        
        //This is very stupid. We should be able to auto-load the class
        //if and only if it is required. But the Composer autoloader caches
        //the 'class doesn't exist' result from earlier, which means we
        //have to load it by hand.
        /** @noinspection PhpIncludeInspection */
        require($outputFilename);

        return $this->jigConfig->getFullClassname($parsedTemplate->getClassName());
    }
}
