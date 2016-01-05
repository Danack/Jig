<?php

namespace Jig;

use Jig\Converter\JigConverter;

/**
 * Class Jig
 * @package Jig
 */
class Jig
{
    /** Templates are never compiled. Useful for debugging only */
    const COMPILE_NEVER         = 'COMPILE_NEVER';
    
    /** Templates are only compiled if the compiled version does not exist */
    const COMPILE_CHECK_EXISTS  = 'COMPILE_CHECK_EXISTS';
    
    /** Checks the last modified time of template and generated class.  */
    const COMPILE_CHECK_MTIME   = 'COMPILE_CHECK_MTIME';
  
    /** Templates are always compiled, useful for unit tests */
    const COMPILE_ALWAYS        = 'COMPILE_ALWAYS';

    /**
     * @var Converter\JigConverter
     */
    protected $jigConverter;

    /**
     * @var \Jig\JigConfig
     */
    protected $jigConfig;

    /**
     * @param JigConfig $jigConfig
     * @param JigConverter $jigConverter
     */
    public function __construct(
        JigConfig $jigConfig,
        JigConverter $jigConverter = null
    ) {

        if ($jigConverter === null) {
            $jigConverter = new JigConverter($jigConfig);
            // Add basic useful stuff to Jig
            $jigConverter->addDefaultPlugin('Jig\Plugin\BasicPlugin');
        }

        $this->jigConfig = clone $jigConfig;
        $this->jigConverter = $jigConverter;
    }


    /**
     * @return JigConverter
     */
    public function getJigConverter()
    {
        return $this->jigConverter;
    }

    /**
     * @param $blockName
     * @param callable $startCallback
     * @param callable $endCallback
     */
    public function bindCompileBlock($blockName, callable $startCallback, callable $endCallback)
    {
        $this->jigConverter->bindCompileBlock($blockName, $startCallback, $endCallback);
    }

    /**
     * Delete the compiled version of a template.
     * @param $templateName
     * @return bool
     */
    public function deleteCompiledFile($templateName)
    {
        $className = $this->jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $this->jigConfig->getCompiledFilenameFromClassname($className);
        $deleted = @unlink($compileFilename);

        return $deleted;
    }
    
    
    public function deleteCompiledString($objectID)
    {
        $fqcn = $this->jigConfig->getFQCNFromTemplateName($objectID);
        $compileFilename = $this->jigConfig->getCompiledFilenameFromClassname($fqcn);
        $deleted = @unlink($compileFilename);

        return $deleted;
    }

    /**
     * @param $templateFilename
     * @return string
     */
    public function getCompiledFilenameFromTemplateName($templateFilename)
    {
        return self::getCompiledFilenameInternal(
            $templateFilename,
            $this->jigConverter,
            $this->jigConfig
        );
    }



    /**
     * @param $templateFilename
     * @return string The fully-qualified class name of the generated template.
     * @throws JigException
     */
    public function compile($templateFilename)
    {
        $className = $this->jigConfig->getFQCNFromTemplateName($templateFilename);

        if ($this->jigConfig->compileCheck === Jig::COMPILE_NEVER) {
            //This is useful when debugging templates. It allows you to edit the
            //generated code, without having it over-written.
            return $className;
        }

        if ($this->jigConfig->compileCheck === Jig::COMPILE_CHECK_EXISTS) {
            if (class_exists($className) === true) {
                goto check_dependencies;
            }
        }

        if ($this->jigConfig->compileCheck === Jig::COMPILE_CHECK_MTIME) {
            if ($this->isGeneratedFileOutOfDate($templateFilename) === false) {
                if (class_exists($className) === true) {
                    goto check_dependencies;
                }
            }
        }

        //Either class file did not exist or it was out of date.
        $this->compileTemplate($templateFilename);

check_dependencies:

        $templatesUsed = $className::getTemplatesUsed();
        foreach ($templatesUsed as $templateUsed) {
            $this->compile($templateUsed);
        }

        return $className;
    }

/*

                                                  .--.__
                                                .~ (@)  ~~~---_
                                               {     `-_~,,,,,,)
                                               {    (_  ',
                                                ~    . = _',
                                                 ~-   '.  =-'
                                                   ~     :
.                                             _,.-~     ('');
'.                                         .-~        \  \ ;
  ':-_                                _.--~            \  \;      _-=,.
    ~-:-.__                       _.-~                 {  '---- _'-=,.
       ~-._~--._             __.-~                     ~---------=,.`
           ~~-._~~-----~~~~~~       .+++~~~~~~~~-__   /
                ~-.,____           {   -     +   }  _/
                        ~~-.______{_    _ -=\ / /_.~
                             :      ~--~    // /         ..-
                             :   / /      // /         ((
                             :  / /      {   `-------,. ))
                             :   /        ''=--------. }o
                .=._________,'  )                     ))
                )  _________ -''                     ~~
               / /  _ _
              (_.-.'O'-'.        "Deinonychus"
*/

    /**
     * @param $className
     * @param $templateFilename
     * @throws JigException
     */
    private function compileTemplate($templateFilename)
    {
        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename);
        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->compile($templateDependency);
        }
        
        $fqcn = $this->jigConfig->getFQCNFromTemplateName($templateFilename);

        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            $fqcn
        );

        if (class_exists($fqcn, false) === false) {
            if (function_exists('opcache_invalidate') === true) {
                // If the class exists in OPCache, it might be out of date.
                // Invalidate the cache for that entry to ensure we get the
                // newly compiled version.
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
     * @param $templateFilename
     * @throws JigException
     * @return \Jig\Converter\ParsedTemplate
     */
    private function prepareTemplateFromFile($templateFilename)
    {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $fileLines = @file($templateFullFilename);

        if ($fileLines === false) {
            throw new JigException("Could not open template [".$templateFullFilename."] for reading.");
        }

        if (count($fileLines) === 0) {
            // users probably prefer an empty template to it crashing.
            $fileLines = [''];
        }

        try {
            $parsedTemplate = $this->jigConverter->createFromLines($fileLines, $this);
            $parsedTemplate->setTemplateName($templateFilename);

            return $parsedTemplate;
        }
        catch (JigException $pte) {
            throw new JigException("Error in template $templateFilename: ".$pte->getMessage(), $pte->getCode(), $pte);
        }
    }

    
    /**
     * @param $templateFilename
     * @return bool
     */
    public function isGeneratedFileOutOfDate($templateFilename)
    {
        $templateFullFilename = $this->jigConfig->getTemplatePath($templateFilename);
        $classPath = Jig::getCompiledFilenameInternal($templateFilename, $this->jigConverter, $this->jigConfig);
        $classPath = str_replace('\\', '/', $classPath);
        $templateTime = @filemtime($templateFullFilename);
        $classTime = @filemtime($classPath);
        
        if ($classTime < $templateTime) {
            return true;
        }

        return false;
    }
    
    
    /**
     * @param $templateName
     * @return string
     */
    public function getFQCNFromTemplateName($templateName)
    {
        return $this->jigConfig->getFQCNFromTemplateName($templateName);
    }

    /**
     * @param $classname
     */
    public function addDefaultPlugin($classname)
    {
        $classname = (string)$classname;

        $this->jigConverter->addDefaultPlugin($classname);
    }

    /**
     * @param $templateString
     * @param $cacheName
     * @return mixed
     */
    public function getParsedTemplateFromString($templateString, $cacheName)
    {
        $templateString = str_replace("\r\n", "\n", $templateString);
        $lines = explode("\n", $templateString);
        $terminatedLines = [];
        foreach ($lines as $line) {
            $terminatedLines[] = $line."\n";
        }

        $parsedTemplate = $this->jigConverter->createFromLines($terminatedLines);
        $parsedTemplate->setTemplateName($cacheName);

        $fqcn = $this->jigConfig->getFQCNFromTemplateName($parsedTemplate->getTemplateName());

        if (class_exists($fqcn, true) === true) {
            return $fqcn;
        }

        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->compile($templateDependency);
        }

        //This has to be after checking the dependencies are compiled
        //to ensure the getDependency function is available.
        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            $this->jigConfig->getFQCNFromTemplateName($cacheName)
        );

        //This is very stupid. We should be able to auto-load the class
        //if and only if it is required. But the Composer autoloader caches
        //the 'class doesn't exist' result from earlier, which means we
        //have to load it by hand.
        /** @noinspection PhpIncludeInspection */
        require($outputFilename);

        return $fqcn;
    }

    /**
     * @param $templateName
     * @return string
     */
    public static function getCompiledFilenameInternal($templateName, JigConverter $jigConverter, JigConfig $jigConfig)
    {
        $className = $jigConverter->getClassNameFromFilename($templateName);
        $compiledFilename = $jigConfig->getCompiledFilenameFromClassname($className);

        return $compiledFilename;
    }
}
