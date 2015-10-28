<?php


namespace Jig;

use Jig\Converter\JigConverter;

/**
 * Class JigRender
 * Make sures that all templates are reader to render.
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
        JigConfig $jigConfig,
        JigConverter $jigConverter = null
    ) {
        $this->jigConfig = $jigConfig;

        if ($jigConverter == null) {
            $jigConverter = new JigConverter($jigConfig);
            $jigConverter->addDefaultPlugin('Jig\Plugin\BasicPlugin');
        }

        $this->jigConverter = $jigConverter;
    }
    
    public function getJigConverter()
    {
        return $this->jigConverter;
    }

    /**
     * @param $templateName
     * @return string
     */
    public function getCompileFilename($templateName)
    {
        $className = $this->jigConverter->getClassNameFromFilename($templateName);
        $compileFilename = $this->jigConfig->getCompiledFilenameFromClassname($className);
        
        return $compileFilename;
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

        if (count($fileLines) == 0) {
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
                goto check_dependencies;
            }
        }

        if ($this->jigConfig->compileCheck == Jig::COMPILE_CHECK_MTIME) {
            if ($this->isGeneratedFileOutOfDate($templateFilename) == false) {
                if (class_exists($className) == true) {
                    goto check_dependencies;
                }
            }
        }

        //Either class file did not exist or it was out of date.
        $this->compileTemplate($className, $templateFilename);

check_dependencies:

        $templatesUsed = $className::getTemplatesUsed();
        foreach ($templatesUsed as $templateUsed) {
            $this->checkTemplateCompiled($templateUsed);
        }
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
    public function compileTemplate($className, $templateFilename)
    {
        $parsedTemplate = $this->prepareTemplateFromFile($templateFilename);
        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->checkTemplateCompiled($templateDependency);
        }
        
        $fqcn = $this->jigConfig->getFQCNFromTemplateName($templateFilename);

        $outputFilename = $parsedTemplate->saveCompiledTemplate(
            $this->jigConfig->templateCompileDirectory,
            $fqcn
        );

        if (class_exists($fqcn, false) == false) {
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
        $parsedTemplate->setTemplateName($cacheName);
        $templateDependencies = $parsedTemplate->getTemplateDependencies();

        foreach ($templateDependencies as $templateDependency) {
            $this->checkTemplateCompiled($templateDependency);
        }

        //This has to be after checking the dependencies are compiled
        //to ensure the getDepedency function is available.
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

        return $this->jigConfig->getFQCNFromTemplateName($parsedTemplate->getTemplateName());
    }
}
